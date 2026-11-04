<?php

namespace App\Http\Controllers;

use App\Models\IntegracionExterna;
use App\Models\Perfil;
use App\Models\PerfilCompartido;
use App\Models\SalaEstudio;
use App\Services\GoogleMeetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SalaEstudioController extends Controller
{
    /**
     * Obtiene los IDs de todos los perfiles a los que el usuario tiene acceso (propios y compartidos).
     */
    protected function obtenerPerfilesAccesibles(int $idUsuario)
    {
        $propios = Perfil::where('idUsuario', $idUsuario)
            ->with('usuario:idUsuario,nombre,apellido')
            ->withCount('usuariosCompartidos')
            ->get()
            ->map(function ($p) {
                $p->esCompartido = false;
                $p->permiso = 'Administrador';
                $p->propietarioNombre = $p->usuario ? ($p->usuario->nombre . ' ' . $p->usuario->apellido) : 'Vos';
                return $p;
            });

        $permisosCompartidos = PerfilCompartido::where('idUsuario', $idUsuario)
            ->pluck('permiso', 'idPerfil');

        $compartidos = collect();
        if ($permisosCompartidos->isNotEmpty()) {
            $compartidos = Perfil::whereIn('idPerfil', $permisosCompartidos->keys())
                ->with('usuario:idUsuario,nombre,apellido')
                ->withCount('usuariosCompartidos')
                ->get()
                ->map(function ($p) use ($permisosCompartidos) {
                    $p->esCompartido = true;
                    $p->permiso = $permisosCompartidos[$p->idPerfil] ?? 'Lector';
                    $p->propietarioNombre = $p->usuario ? ($p->usuario->nombre . ' ' . $p->usuario->apellido) : 'Compañero';
                    return $p;
                });
        }

        return $propios->concat($compartidos)->values();
    }

    protected function usuarioTieneAccesoAPerfil(int $idUsuario, int $idPerfil): bool
    {
        $esPropio = Perfil::where('idPerfil', $idPerfil)->where('idUsuario', $idUsuario)->exists();
        if ($esPropio) {
            return true;
        }

        return PerfilCompartido::where('idPerfil', $idPerfil)->where('idUsuario', $idUsuario)->exists();
    }

    /**
     * Vista principal con el listado de salas de estudio activas y perfiles disponibles.
     */
    public function index()
    {
        $user = Auth::user();
        $perfiles = $this->obtenerPerfilesAccesibles($user->idUsuario);
        $perfilIds = $perfiles->pluck('idPerfil');

        $salasActivas = collect();
        if ($perfilIds->isNotEmpty()) {
            $umbralActivo = Carbon::now()->subMinutes(2);

            $conteoParticipantesPorPerfil = DB::table('PresenciaSalaEstudio')
                ->whereIn('idPerfil', $perfilIds)
                ->where('estadoUsuario', '!=', 'Desconectado')
                ->where('ultimoPing', '>=', $umbralActivo)
                ->selectRaw('idPerfil, COUNT(*) as conectados')
                ->groupBy('idPerfil')
                ->pluck('conectados', 'idPerfil');

            $perfilesMap = $perfiles->keyBy('idPerfil');

            $salasActivas = SalaEstudio::whereIn('idPerfil', $perfilIds)
                ->where('estado', 'Activa')
                ->with('creador:idUsuario,nombre,apellido')
                ->orderByDesc('fechaCreacion')
                ->get()
                ->map(function ($sala) use ($perfilesMap, $conteoParticipantesPorPerfil) {
                    $perfilInfo = $perfilesMap->get($sala->idPerfil);
                    return [
                        'idSalaEstudio' => $sala->idSalaEstudio,
                        'idPerfil' => $sala->idPerfil,
                        'tituloEvento' => $sala->tituloEvento,
                        'googleMeetUrl' => $sala->googleMeetUrl,
                        'googleMeetId' => $sala->googleMeetId,
                        'estado' => $sala->estado,
                        'fechaCreacion' => $sala->fechaCreacion?->diffForHumans() ?? 'Recién creada',
                        'creadorNombre' => $sala->creador ? ($sala->creador->nombre . ' ' . $sala->creador->apellido) : 'Anfitrión',
                        'idUsuarioCreador' => $sala->idUsuarioCreador,
                        'perfilTitulo' => $perfilInfo?->tituloPerfil ?? 'Perfil',
                        'esCompartido' => (bool) ($perfilInfo?->esCompartido || ($perfilInfo?->usuarios_compartidos_count ?? 0) > 0),
                        'conectadosCount' => (int) ($conteoParticipantesPorPerfil[$sala->idPerfil] ?? 0),
                        'pomodoro' => $this->obtenerEstadoPomodoroGrupal($sala->idSalaEstudio),
                    ];
                });
        }

        $tieneGoogleVinculado = IntegracionExterna::where('idUsuario', $user->idUsuario)
            ->where('plataforma', 'GoogleAuth')
            ->exists();

        return Inertia::render('sala-estudio/Index', [
            'salasActivas' => $salasActivas,
            'perfiles' => $perfiles,
            'perfilActivoId' => session('perfilActivo') ? (int) session('perfilActivo') : ($perfiles->first()?->idPerfil ?? null),
            'tieneGoogleVinculado' => $tieneGoogleVinculado,
        ]);
    }

    /**
     * Crea una nueva Sala de Estudio Virtual asociada a un perfil.
     */
    public function store(Request $request, GoogleMeetService $googleMeetService)
    {
        $request->validate([
            'idPerfil' => 'required|integer|exists:Perfil,idPerfil',
            'tituloEvento' => 'required|string|max:150',
            'enlaceMeet' => 'nullable|string|max:500',
            'duracionTrabajo' => 'nullable|integer|min:1|max:120',
            'duracionDescansoCorto' => 'nullable|integer|min:1|max:30',
            'duracionDescansoLargo' => 'nullable|integer|min:5|max:60',
            'ciclosObjetivo' => 'nullable|integer|min:1|max:10',
        ]);

        $user = Auth::user();
        $idPerfil = (int) $request->idPerfil;

        if (!$this->usuarioTieneAccesoAPerfil($user->idUsuario, $idPerfil)) {
            abort(403, 'No tenés acceso a este perfil.');
        }

        // Si ya había una sala activa en este perfil, la marcamos como finalizada antes de abrir la nueva
        SalaEstudio::where('idPerfil', $idPerfil)
            ->where('estado', 'Activa')
            ->update([
                'estado' => 'Finalizada',
                'fechaFin' => Carbon::now(),
            ]);

        $datosMeet = $googleMeetService->crearReunionMeet(
            $user->idUsuario,
            $request->tituloEvento,
            $request->enlaceMeet
        );

        $sala = SalaEstudio::create([
            'idPerfil' => $idPerfil,
            'idUsuarioCreador' => $user->idUsuario,
            'googleMeetUrl' => $datosMeet['url'],
            'googleMeetId' => $datosMeet['meetId'],
            'tituloEvento' => $request->tituloEvento,
            'estado' => 'Activa',
            'fechaCreacion' => Carbon::now(),
        ]);

        Perfil::where('idPerfil', $idPerfil)->update(['es_sala_estudio' => true]);

        // Inicializar estado del temporizador Pomodoro grupal en Cache
        $duracionTrabajo = (int) ($request->duracionTrabajo ?: 25);
        $duracionDescansoCorto = (int) ($request->duracionDescansoCorto ?: 5);
        $duracionDescansoLargo = (int) ($request->duracionDescansoLargo ?: 15);
        $ciclosObjetivo = (int) ($request->ciclosObjetivo ?: 4);

        $estadoInicialPomodoro = [
            'corriendo' => false,
            'fase' => 'work', // 'work' | 'shortBreak' | 'longBreak'
            'cicloActual' => 1,
            'ciclosObjetivo' => $ciclosObjetivo,
            'duracionTrabajo' => $duracionTrabajo,
            'duracionDescansoCorto' => $duracionDescansoCorto,
            'duracionDescansoLargo' => $duracionDescansoLargo,
            'segundosTotalesFase' => $duracionTrabajo * 60,
            'segundosRestantesAlPausar' => $duracionTrabajo * 60,
            'finFaseTimestamp' => null,
            'actualizadoPor' => $user->nombre . ' ' . $user->apellido,
        ];

        Cache::put("sala_pomodoro_{$sala->idSalaEstudio}", $estadoInicialPomodoro, now()->addHours(12));

        // Registrar presencia inicial del creador
        $this->registrarPresencia($idPerfil, $user->idUsuario, 'EnLinea');

        return redirect()->route('salas.show', $sala->idSalaEstudio)
            ->with('success', $datosMeet['generadoPorApi']
                ? '¡Sala de estudio creada y enlace de Google Meet generado automáticamente!'
                : '¡Sala de estudio creada con éxito!');
    }

    /**
     * Muestra la interfaz en tiempo real de la Sala de Estudio activa.
     */
    public function show($id)
    {
        $user = Auth::user();
        $sala = SalaEstudio::with(['perfil', 'creador:idUsuario,nombre,apellido'])->findOrFail($id);

        if (!$this->usuarioTieneAccesoAPerfil($user->idUsuario, $sala->idPerfil)) {
            abort(403, 'No tenés permiso para ingresar a esta sala de estudio.');
        }

        if ($sala->estado !== 'Activa') {
            return redirect()->route('salas.index')
                ->with('error', 'Esta sala de estudio ya ha finalizado.');
        }

        // Registrar o mantener el estado activo del usuario al entrar
        $presenciaActual = DB::table('PresenciaSalaEstudio')
            ->where('idPerfil', $sala->idPerfil)
            ->where('idUsuario', $user->idUsuario)
            ->first();

        $estadoInicial = ($presenciaActual && $presenciaActual->estadoUsuario !== 'Desconectado')
            ? $presenciaActual->estadoUsuario
            : 'EnLinea';

        $this->registrarPresencia($sala->idPerfil, $user->idUsuario, $estadoInicial);

        $pomodoroGrupal = $this->obtenerEstadoPomodoroGrupal($sala->idSalaEstudio);
        $participantes = $this->obtenerParticipantesSala($sala->idPerfil);

        $esAnfitrionOAdmin = $sala->idUsuarioCreador === $user->idUsuario
            || $sala->perfil?->idUsuario === $user->idUsuario
            || PerfilCompartido::where('idPerfil', $sala->idPerfil)
                ->where('idUsuario', $user->idUsuario)
                ->whereIn('permiso', ['Administrador', 'Editor'])
                ->exists();

        return Inertia::render('sala-estudio/Detalle', [
            'sala' => [
                'idSalaEstudio' => $sala->idSalaEstudio,
                'idPerfil' => $sala->idPerfil,
                'idUsuarioCreador' => $sala->idUsuarioCreador,
                'tituloEvento' => $sala->tituloEvento,
                'googleMeetUrl' => $sala->googleMeetUrl,
                'googleMeetId' => $sala->googleMeetId,
                'estado' => $sala->estado,
                'creadorNombre' => $sala->creador ? ($sala->creador->nombre . ' ' . $sala->creador->apellido) : 'Anfitrión',
                'perfilTitulo' => $sala->perfil?->tituloPerfil ?? 'Equipo',
            ],
            'participantesIniciales' => $participantes,
            'pomodoroInicial' => $pomodoroGrupal,
            'miEstadoInicial' => $estadoInicial,
            'puedeControlarSala' => $esAnfitrionOAdmin,
            'serverTime' => time(),
        ]);
    }

    /**
     * Endpoint JSON ultraligero para el polling de presencia y reloj Pomodoro grupal.
     */
    public function estado(Request $request, $id)
    {
        $user = Auth::user();
        $sala = SalaEstudio::findOrFail($id);

        if ($sala->estado !== 'Activa') {
            return response()->json([
                'finalizada' => true,
            ]);
        }

        $nuevoEstadoUsuario = $request->input('estadoUsuario');
        $estadosValidos = ['EnLinea', 'EnPomodoro', 'EnMeet', 'Desconectado'];
        if (!in_array($nuevoEstadoUsuario, $estadosValidos, true)) {
            $nuevoEstadoUsuario = null;
        }

        $this->registrarPresencia($sala->idPerfil, $user->idUsuario, $nuevoEstadoUsuario);

        $pomodoroGrupal = $this->obtenerEstadoPomodoroGrupal($sala->idSalaEstudio);
        $participantes = $this->obtenerParticipantesSala($sala->idPerfil);

        return response()->json([
            'finalizada' => false,
            'sala' => [
                'googleMeetUrl' => $sala->googleMeetUrl,
                'googleMeetId' => $sala->googleMeetId,
                'tituloEvento' => $sala->tituloEvento,
            ],
            'participantes' => $participantes,
            'pomodoro' => $pomodoroGrupal,
            'serverTime' => time(),
        ]);
    }

    /**
     * Controla el Temporizador Pomodoro Grupal de la sala (iniciar, pausar, saltar, reiniciar, configurar).
     */
    public function pomodoro(Request $request, $id)
    {
        $request->validate([
            'accion' => 'required|in:iniciar,pausar,saltar,reiniciar,configurar',
            'duracionTrabajo' => 'nullable|integer|min:1|max:120',
            'duracionDescansoCorto' => 'nullable|integer|min:1|max:30',
            'duracionDescansoLargo' => 'nullable|integer|min:5|max:60',
            'ciclosObjetivo' => 'nullable|integer|min:1|max:10',
        ]);

        $user = Auth::user();
        $sala = SalaEstudio::findOrFail($id);

        if (!$this->usuarioTieneAccesoAPerfil($user->idUsuario, $sala->idPerfil)) {
            abort(403);
        }

        $state = $this->obtenerEstadoPomodoroGrupal($sala->idSalaEstudio);
        $ahora = time();
        $accion = $request->input('accion');

        if ($accion === 'iniciar') {
            if (!$state['corriendo']) {
                $restantes = max(1, (int) $state['segundosRestantes']);
                $state['corriendo'] = true;
                $state['segundosRestantesAlPausar'] = $restantes;
                $state['finFaseTimestamp'] = $ahora + $restantes;
            }
            $this->registrarPresencia($sala->idPerfil, $user->idUsuario, 'EnPomodoro');
        } elseif ($accion === 'pausar') {
            if ($state['corriendo']) {
                $restantes = max(0, (int) ($state['finFaseTimestamp'] - $ahora));
                $state['corriendo'] = false;
                $state['segundosRestantesAlPausar'] = $restantes;
                $state['finFaseTimestamp'] = null;
            }
        } elseif ($accion === 'reiniciar') {
            $duracionFaseSegundos = $this->calcularDuracionFaseSegundos($state, $state['fase']);
            $state['corriendo'] = false;
            $state['segundosTotalesFase'] = $duracionFaseSegundos;
            $state['segundosRestantesAlPausar'] = $duracionFaseSegundos;
            $state['finFaseTimestamp'] = null;
        } elseif ($accion === 'saltar') {
            $state = $this->avanzarSiguienteFaseGrupal($state, false);
        } elseif ($accion === 'configurar') {
            $state['duracionTrabajo'] = (int) ($request->duracionTrabajo ?: $state['duracionTrabajo']);
            $state['duracionDescansoCorto'] = (int) ($request->duracionDescansoCorto ?: $state['duracionDescansoCorto']);
            $state['duracionDescansoLargo'] = (int) ($request->duracionDescansoLargo ?: $state['duracionDescansoLargo']);
            $state['ciclosObjetivo'] = (int) ($request->ciclosObjetivo ?: $state['ciclosObjetivo']);

            $duracionFaseSegundos = $this->calcularDuracionFaseSegundos($state, $state['fase']);
            $state['corriendo'] = false;
            $state['segundosTotalesFase'] = $duracionFaseSegundos;
            $state['segundosRestantesAlPausar'] = $duracionFaseSegundos;
            $state['finFaseTimestamp'] = null;
        }

        $state['actualizadoPor'] = $user->nombre . ' ' . $user->apellido;
        $state['segundosRestantes'] = $state['corriendo']
            ? max(0, (int) ($state['finFaseTimestamp'] - $ahora))
            : (int) $state['segundosRestantesAlPausar'];

        Cache::put("sala_pomodoro_{$sala->idSalaEstudio}", $state, now()->addHours(12));

        return response()->json([
            'pomodoro' => $state,
            'serverTime' => $ahora,
        ]);
    }

    /**
     * Permite actualizar el enlace o código de Google Meet de la sala en tiempo real.
     */
    public function actualizarMeet(Request $request, $id, GoogleMeetService $googleMeetService)
    {
        $request->validate([
            'enlaceMeet' => 'required|string|max:500',
        ]);

        $user = Auth::user();
        $sala = SalaEstudio::findOrFail($id);

        if (!$this->usuarioTieneAccesoAPerfil($user->idUsuario, $sala->idPerfil)) {
            abort(403);
        }

        $urlNormalizada = $googleMeetService->normalizarEnlaceMeet($request->enlaceMeet);
        if (!$urlNormalizada) {
            return response()->json([
                'error' => 'Ingresá un código de Meet válido (ej: abc-defg-hij) o un enlace https://meet.google.com/...',
            ], 422);
        }

        $meetId = $googleMeetService->extraerMeetId($urlNormalizada);

        $sala->update([
            'googleMeetUrl' => $urlNormalizada,
            'googleMeetId' => $meetId,
        ]);

        return response()->json([
            'googleMeetUrl' => $sala->googleMeetUrl,
            'googleMeetId' => $sala->googleMeetId,
        ]);
    }

    /**
     * Marca al usuario como desconectado de la sala y vuelve al listado.
     */
    public function salir($id)
    {
        $user = Auth::user();
        $sala = SalaEstudio::findOrFail($id);

        $this->registrarPresencia($sala->idPerfil, $user->idUsuario, 'Desconectado');

        return redirect()->route('salas.index');
    }

    /**
     * Cierra definitivamente la Sala de Estudio.
     */
    public function finalizar($id)
    {
        $user = Auth::user();
        $sala = SalaEstudio::with('perfil')->findOrFail($id);

        $esAutorizado = $sala->idUsuarioCreador === $user->idUsuario
            || $sala->perfil?->idUsuario === $user->idUsuario
            || PerfilCompartido::where('idPerfil', $sala->idPerfil)
                ->where('idUsuario', $user->idUsuario)
                ->where('permiso', 'Administrador')
                ->exists();

        if (!$esAutorizado) {
            abort(403, 'Solo el anfitrión o el administrador del perfil pueden finalizar la sala.');
        }

        $sala->update([
            'estado' => 'Finalizada',
            'fechaFin' => Carbon::now(),
        ]);

        DB::table('PresenciaSalaEstudio')
            ->where('idPerfil', $sala->idPerfil)
            ->update(['estadoUsuario' => 'Desconectado']);

        Perfil::where('idPerfil', $sala->idPerfil)->update(['es_sala_estudio' => false]);
        Cache::forget("sala_pomodoro_{$sala->idSalaEstudio}");

        return redirect()->route('salas.index')
            ->with('success', 'La sala de estudio ha finalizado.');
    }

    protected function registrarPresencia(int $idPerfil, int $idUsuario, ?string $estadoUsuario = null): void
    {
        $ahora = Carbon::now();
        $existente = DB::table('PresenciaSalaEstudio')
            ->where('idPerfil', $idPerfil)
            ->where('idUsuario', $idUsuario)
            ->first();

        if ($existente) {
            $updateData = ['ultimoPing' => $ahora];
            if ($estadoUsuario !== null) {
                $updateData['estadoUsuario'] = $estadoUsuario;
            }
            DB::table('PresenciaSalaEstudio')
                ->where('idPerfil', $idPerfil)
                ->where('idUsuario', $idUsuario)
                ->update($updateData);
        } else {
            DB::table('PresenciaSalaEstudio')->insert([
                'idPerfil' => $idPerfil,
                'idUsuario' => $idUsuario,
                'estadoUsuario' => $estadoUsuario ?? 'EnLinea',
                'ultimoPing' => $ahora,
            ]);
        }
    }

    protected function obtenerParticipantesSala(int $idPerfil): array
    {
        $umbralActivo = Carbon::now()->subMinutes(2);

        return DB::table('PresenciaSalaEstudio')
            ->join('Usuario', 'PresenciaSalaEstudio.idUsuario', '=', 'Usuario.idUsuario')
            ->where('PresenciaSalaEstudio.idPerfil', $idPerfil)
            ->where('PresenciaSalaEstudio.estadoUsuario', '!=', 'Desconectado')
            ->where('PresenciaSalaEstudio.ultimoPing', '>=', $umbralActivo)
            ->select(
                'Usuario.idUsuario',
                'Usuario.nombre',
                'Usuario.apellido',
                'Usuario.email',
                'PresenciaSalaEstudio.estadoUsuario',
                'PresenciaSalaEstudio.ultimoPing'
            )
            ->orderBy('Usuario.nombre')
            ->get()
            ->map(function ($row) {
                return [
                    'idUsuario' => $row->idUsuario,
                    'nombreCompleto' => trim($row->nombre . ' ' . $row->apellido),
                    'inicial' => mb_strtoupper(mb_substr($row->nombre ?: 'U', 0, 1)),
                    'email' => $row->email,
                    'estadoUsuario' => $row->estadoUsuario,
                ];
            })
            ->values()
            ->all();
    }

    protected function obtenerEstadoPomodoroGrupal(int $idSalaEstudio): array
    {
        $cacheKey = "sala_pomodoro_{$idSalaEstudio}";
        $state = Cache::get($cacheKey);

        if (!$state) {
            $state = [
                'corriendo' => false,
                'fase' => 'work',
                'cicloActual' => 1,
                'ciclosObjetivo' => 4,
                'duracionTrabajo' => 25,
                'duracionDescansoCorto' => 5,
                'duracionDescansoLargo' => 15,
                'segundosTotalesFase' => 25 * 60,
                'segundosRestantesAlPausar' => 25 * 60,
                'finFaseTimestamp' => null,
                'actualizadoPor' => 'Sistema',
            ];
            Cache::put($cacheKey, $state, now()->addHours(12));
        }

        $ahora = time();
        if ($state['corriendo'] && !empty($state['finFaseTimestamp'])) {
            $restantes = (int) ($state['finFaseTimestamp'] - $ahora);
            if ($restantes <= 0) {
                // La fase concluyó -> transicionar automáticamente a la siguiente fase (pausada lista para iniciar o en espera)
                $state = $this->avanzarSiguienteFaseGrupal($state, false);
                Cache::put($cacheKey, $state, now()->addHours(12));
                $state['segundosRestantes'] = (int) $state['segundosRestantesAlPausar'];
            } else {
                $state['segundosRestantes'] = $restantes;
            }
        } else {
            $state['segundosRestantes'] = (int) ($state['segundosRestantesAlPausar'] ?? ($state['duracionTrabajo'] * 60));
        }

        return $state;
    }

    protected function calcularDuracionFaseSegundos(array $state, string $fase): int
    {
        if ($fase === 'work') {
            return ((int) $state['duracionTrabajo']) * 60;
        }
        if ($fase === 'shortBreak') {
            return ((int) $state['duracionDescansoCorto']) * 60;
        }
        return ((int) $state['duracionDescansoLargo']) * 60;
    }

    protected function avanzarSiguienteFaseGrupal(array $state, bool $autoIniciar = false): array
    {
        if ($state['fase'] === 'work') {
            if ($state['cicloActual'] >= $state['ciclosObjetivo']) {
                $state['fase'] = 'longBreak';
            } else {
                $state['cicloActual'] = ((int) $state['cicloActual']) + 1;
                $state['fase'] = 'shortBreak';
            }
        } else {
            if ($state['fase'] === 'longBreak') {
                $state['cicloActual'] = 1;
            }
            $state['fase'] = 'work';
        }

        $segundosFase = $this->calcularDuracionFaseSegundos($state, $state['fase']);
        $state['segundosTotalesFase'] = $segundosFase;
        $state['segundosRestantesAlPausar'] = $segundosFase;
        $state['corriendo'] = $autoIniciar;
        $state['finFaseTimestamp'] = $autoIniciar ? (time() + $segundosFase) : null;

        return $state;
    }
}
