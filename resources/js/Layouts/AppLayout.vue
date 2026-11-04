<template>
  <div class="layout-container" :class="{ 'zen-mode': isZenMode }">
    
    <button 
      id="sidebarToggle" 
      aria-label="Abrir menú lateral" 
      @click="toggleSidebar"
      v-show="isSidebarClosed"
      title="Abrir menú"
    >
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
        <line x1="9" y1="3" x2="9" y2="21" />
        <polyline points="14 9 17 12 14 15" />
      </svg>
    </button>

    <aside class="sidebar" :class="{ 'closed': isSidebarClosed }">
      <div class="logo-area">
        <Link :href="route('dashboard')" class="logo-brand-link" title="Ir al Dashboard">
          <img
            :src="isDarkMode ? '/imagenes/logo-cronos-trim-dark.png' : '/imagenes/logo-cronos-trim.png'"
            alt="Cronos Notes"
            class="sidebar-logo-img"
          />
        </Link>
        <button 
          class="btn-collapse-sidebar" 
          @click="toggleSidebar" 
          aria-label="Colapsar menú"
          title="Ocultar barra lateral"
        >
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6" />
          </svg>
        </button>
      </div>

      <div class="nav-section-title first-section">Principal</div>
      <ul class="nav-menu">
        <li>
          <Link :href="route('dashboard')" class="nav-item" :class="{ active: route().current('dashboard') }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>Página principal</span>
          </Link>
        </li>
        <li>
          <Link :href="route('gestion-perfil')" class="nav-item" :class="{ active: route().current('gestion-perfil') }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="7" height="7" rx="1"/>
              <rect x="14" y="3" width="7" height="7" rx="1"/>
              <rect x="14" y="14" width="7" height="7" rx="1"/>
              <rect x="3" y="14" width="7" height="7" rx="1"/>
            </svg>
            <span>Mis perfiles</span>
          </Link>
        </li>
        <li>
          <Link :href="route('gestion-tareas')" class="nav-item" :class="{ active: route().current('gestion-tareas') }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <line x1="16" y1="13" x2="8" y2="13"/>
              <line x1="16" y1="17" x2="8" y2="17"/>
              <polyline points="10 9 9 9 8 9"/>
            </svg>
            <span>Mis tareas</span>
          </Link>
        </li>
        <li>
          <Link href="/estadisticas" class="nav-item" :class="{ active: $page.url.startsWith('/estadisticas') }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="20" x2="18" y2="10"/>
              <line x1="12" y1="20" x2="12" y2="4"/>
              <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            <span>Estadísticas</span>
          </Link>
        </li>
        <li>
          <Link :href="route('calendario')" class="nav-item" :class="{ active: route().current('calendario') }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <span>Calendario</span>
          </Link>
        </li>
      </ul>

      <div class="nav-section-title">Pomodoro</div>
      <ul class="nav-menu">
        <li>
          <Link :href="route('pomodoro.index')" class="nav-item" :class="{ active: route().current('pomodoro.index') }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22c-4-4-8-9-8-14 0-5 6-6 8-6s8 1 8 6c0 5-4 10-8 14z"/>
              <path d="M12 22V12"/>
            </svg>
            <span>Espacio de Concentración</span>
          </Link>
        </li>
        <li>
          <Link :href="route('apuntes.index')" class="nav-item" :class="{ active: route().current('apuntes.index') || route().current('apuntes.create') || route().current('apuntes.edit') }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
            </svg>
            <span>Mis apuntes</span>
          </Link>
        </li>
        <li>
          <Link :href="route('salas.index')" class="nav-item" :class="{ active: route().current('salas.index') || route().current('salas.show') || $page.url.startsWith('/salas-estudio') }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span>Salas de Estudio</span>
          </Link>
        </li>
      </ul>
    </aside>

    <!-- Contenido principal de cada página -->
    <main id="content" :class="{ 'expanded': isSidebarClosed }">
      <!-- ===== NAVBAR SUPERIOR SAAS ===== -->
      <header class="top-bar">
        <!-- 1. Buscador Contextual (Solo visible en Mis Perfiles y Mis Tareas) -->
        <div v-if="contextoBusqueda" class="search-container" ref="searchContainerRef">
          <div class="navbar-search-box" :class="{ 'is-open': isSearchDropdownOpen }">
            <svg class="navbar-search-icon" viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8" />
              <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input
              ref="searchInputRef"
              type="text"
              v-model="globalSearchQuery"
              class="navbar-search-input"
              :placeholder="placeholderBusqueda"
              :aria-label="placeholderBusqueda"
              autocomplete="off"
              @focus="onSearchFocus"
              @input="onSearchInput"
              @keydown.esc="isSearchDropdownOpen = false"
            />
            <button
              v-if="globalSearchQuery"
              type="button"
              class="navbar-search-clear"
              @click="limpiarBusquedaGlobal"
              aria-label="Limpiar búsqueda"
              title="Mostrar todos"
            >
              &times;
            </button>
            <kbd v-else class="navbar-search-kbd">Ctrl K</kbd>
          </div>

          <!-- Dropdown desplegable con sugerencias contextuales -->
          <transition name="dropdown-fade">
            <div v-if="isSearchDropdownOpen && globalSearchQuery.trim().length > 0" class="search-results-dropdown">
              <div class="search-dropdown-header">
                <span>
                  {{ contextoBusqueda === 'perfiles' ? 'Perfiles' : 'Tareas del perfil' }} que coinciden con <strong>"{{ globalSearchQuery.trim() }}"</strong>
                </span>
                <span class="search-count-badge">{{ sugerenciasContextualizadas.length }}</span>
              </div>

              <div v-if="sugerenciasContextualizadas.length === 0" class="search-empty">
                <p class="search-empty-title">Sin coincidencias para "{{ globalSearchQuery.trim() }}"</p>
                <p class="search-empty-sub">
                  {{ contextoBusqueda === 'perfiles' ? 'No se encontraron perfiles con ese nombre.' : 'No hay tareas con ese nombre en este perfil.' }}
                </p>
              </div>

              <ul v-else class="search-results-list">
                <li
                  v-for="res in sugerenciasContextualizadas"
                  :key="res.uid"
                  class="search-result-item"
                  @click="seleccionarResultadoBusqueda(res)"
                >
                  <div class="search-result-icon" :class="`type-${res.tipo}`">
                    <!-- Icono Tarea -->
                    <svg v-if="res.tipo === 'tarea'" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                      <polyline points="14 2 14 8 20 8"/>
                      <line x1="16" y1="13" x2="8" y2="13"/>
                      <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    <!-- Icono Perfil -->
                    <svg v-else viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="3" width="7" height="7" rx="1"/>
                      <rect x="14" y="3" width="7" height="7" rx="1"/>
                      <rect x="14" y="14" width="7" height="7" rx="1"/>
                      <rect x="3" y="14" width="7" height="7" rx="1"/>
                    </svg>
                  </div>
                  <div class="search-result-info">
                    <div class="search-result-title">{{ res.titulo }}</div>
                    <div class="search-result-sub">{{ res.subtitulo }}</div>
                  </div>
                  <span class="search-result-badge" :class="`badge-${res.tipo}`">
                    {{ res.badge }}
                  </span>
                </li>
              </ul>
            </div>
          </transition>
        </div>

        <!-- Acciones derechas del Navbar -->
        <div class="top-bar-actions">
          <!-- 2. Toggle de Tema (Sol / Luna) -->
          <button
            type="button"
            class="icon-button theme-toggle-btn"
            @click="toggleMode"
            :title="isDarkMode ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
            aria-label="Cambiar tema"
          >
            <i v-if="isDarkMode" class="bi bi-sun-fill text-warning" style="font-size: 1.15rem;"></i>
            <i v-else class="bi bi-moon-fill" style="font-size: 1.12rem;"></i>
          </button>

          <!-- 3. Campana de Notificaciones (Tareas próximas a vencer en 24h) -->
          <div class="notifications-dropdown-container" ref="notificationsRef">
            <button
              type="button"
              class="icon-button bell-trigger"
              :class="{ 'is-active': isNotificationsOpen }"
              @click="toggleNotifications"
              title="Tareas próximas a vencer"
              aria-label="Notificaciones de tareas próximas a vencer"
            >
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
              </svg>
              <span v-if="unreadNotificationsCount > 0" class="notification-badge">
                {{ unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount }}
              </span>
            </button>

            <transition name="dropdown-fade">
              <div v-if="isNotificationsOpen" class="notifications-dropdown">
                <div class="notif-header">
                  <div class="notif-header-title-wrap">
                    <span class="notif-header-title">Tareas por vencer</span>
                    <span class="notif-count-pill">{{ notificacionesTareas.length }}</span>
                  </div>
                  <button
                    v-if="unreadNotificationsCount > 0"
                    type="button"
                    class="notif-mark-read-btn"
                    @click="marcarNotificacionesLeidas"
                  >
                    Marcar leídas
                  </button>
                </div>

                <div class="notif-body">
                  <!-- Estado cargando -->
                  <div v-if="cargandoNotificaciones" class="notif-loading">
                    <div class="notif-spinner"></div>
                    <span>Consultando tareas próximas a vencer...</span>
                  </div>

                  <!-- Estado sin tareas por vencer -->
                  <div v-else-if="notificacionesTareas.length === 0" class="notif-empty">
                    <div class="notif-empty-icon">
                      <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                      </svg>
                    </div>
                    <p class="notif-empty-title">¡Todo al día!</p>
                    <p class="notif-empty-sub">No tenés tareas pendientes que venzan en las próximas 24 horas.</p>
                  </div>

                  <!-- Lista de tareas próximas a vencer -->
                  <ul v-else class="notif-list">
                    <li
                      v-for="item in notificacionesTareas"
                      :key="item.idTarea"
                      class="notif-item"
                      :class="{ 'is-unread': !esNotificacionLeida(item.idTarea) }"
                      @click="irATareaNotificada(item)"
                    >
                      <div class="notif-item-icon" :class="item.venceHoy ? 'urgent' : 'soon'">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                          <circle cx="12" cy="12" r="10" />
                          <polyline points="12 6 12 12 16 14" />
                        </svg>
                      </div>
                      <div class="notif-item-content">
                        <div class="notif-item-top">
                          <span class="notif-item-title">{{ item.tituloTarea }}</span>
                          <span class="notif-hours-tag" :class="{ 'urgent': item.venceHoy }">
                            {{ item.horas_restantes }}h restantes
                          </span>
                        </div>
                        <div class="notif-item-meta">
                          <span class="notif-profile-badge">{{ item.perfil }}</span>
                          <span class="notif-dot">·</span>
                          <span class="notif-priority-text">Prioridad {{ item.prioridadTarea }}</span>
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>

                <div class="notif-footer">
                  <Link :href="route('calendario')" class="notif-footer-link" @click="isNotificationsOpen = false">
                    <span>Ver calendario de vencimientos</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="5" y1="12" x2="19" y2="12" />
                      <polyline points="12 5 19 12 12 19" />
                    </svg>
                  </Link>
                </div>
              </div>
            </transition>
          </div>

          <!-- 4. Menú de Perfil de Usuario (Dropdown mejorado) -->
          <div class="settings-dropdown-container" ref="dropdownRef">
            <button
              type="button"
              class="user-profile-trigger"
              :class="{ 'is-open': isSettingsDropdownOpen }"
              @click="toggleDropdown"
              aria-label="Menú de usuario"
              :aria-expanded="isSettingsDropdownOpen"
            >
              <span class="avatar">
                {{ inicialUsuario }}
              </span>
              <svg
                class="profile-chevron"
                :class="{ 'rotated': isSettingsDropdownOpen }"
                viewBox="0 0 24 24"
                width="15"
                height="15"
                fill="none"
                stroke="currentColor"
                stroke-width="2.3"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <polyline points="6 9 12 15 18 9" />
              </svg>
            </button>

            <transition name="dropdown-fade">
              <div v-if="isSettingsDropdownOpen" class="settings-dropdown">
                <!-- Cabecera con Nombre Completo y Correo -->
                <div class="dropdown-user-header">
                  <div class="dropdown-user-avatar">
                    {{ inicialUsuario }}
                  </div>
                  <div class="dropdown-user-info">
                    <div class="dropdown-user-name">{{ nombreCompletoUsuario }}</div>
                    <div class="dropdown-user-email">{{ emailUsuario }}</div>
                  </div>
                </div>

                <div class="dropdown-divider"></div>

                <!-- Opciones del menú con iconos -->
                <div class="dropdown-menu-items">
                  <Link :href="route('perfil-usuario')" class="dropdown-item" @click="closeDropdown">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                      <circle cx="12" cy="7" r="4" />
                    </svg>
                    <span>Mis Datos</span>
                  </Link>

                  <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    type="button"
                    class="dropdown-item dropdown-item-danger w-100 text-start"
                    @click="closeDropdown"
                  >
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                      <polyline points="16 17 21 12 16 7" />
                      <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    <span>Cerrar Sesión</span>
                  </Link>
                </div>
              </div>
            </transition>
          </div>
        </div>
      </header>

      <!-- Notificaciones Flash Globales -->
      <div v-if="$page.props.flash?.error" class="global-toast error-toast">
        <div class="toast-content">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>{{ $page.props.flash.error }}</span>
        </div>
        <button class="toast-close" @click="$page.props.flash.error = null">&times;</button>
      </div>

      <div v-if="$page.props.flash?.success" class="global-toast success-toast">
        <div class="toast-content">
          <i class="bi bi-check-circle-fill"></i>
          <span>{{ $page.props.flash.success }}</span>
        </div>
        <button class="toast-close" @click="$page.props.flash.success = null">&times;</button>
      </div>

      <slot />
    </main>

    <MiniTimerFlotante />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import MiniTimerFlotante from '@/Components/Pomodoro/MiniTimerFlotante.vue';
import { usePomodoroTimer } from '@/Composables/usePomodoroTimer';
import { useGlobalSearch } from '@/Composables/useGlobalSearch';

const page = usePage();
const isSidebarClosed = ref(window.innerWidth <= 768);
const isZenMode = ref(false);
const isSettingsDropdownOpen = ref(false);
const isNotificationsOpen = ref(false);
const isSearchDropdownOpen = ref(false);
const dropdownRef = ref(null);
const notificationsRef = ref(null);
const searchContainerRef = ref(null);
const searchInputRef = ref(null);

const { globalSearchQuery, setGlobalSearch, clearGlobalSearch } = useGlobalSearch();

// Determina si estamos en la vista de Perfiles ('perfiles'), en la de Tareas ('tareas') o en otra vista (null)
const contextoBusqueda = computed(() => {
  const url = page.url || '';
  if (url.startsWith('/gestion-perfil') || route().current('gestion-perfil')) {
    return 'perfiles';
  }
  if (url.startsWith('/gestion-tareas') || route().current('gestion-tareas')) {
    return 'tareas';
  }
  return null;
});

const placeholderBusqueda = computed(() => {
  if (contextoBusqueda.value === 'perfiles') {
    return 'Buscar perfiles...';
  }
  if (contextoBusqueda.value === 'tareas') {
    const nombrePerfil = page.props.perfilActivo?.tituloPerfil;
    return nombrePerfil ? `Buscar tareas en ${nombrePerfil}...` : 'Buscar tareas de este perfil...';
  }
  return 'Buscar...';
});

// Lista desplegable de sugerencias filtrada estrictamente según la pantalla actual:
// - En 'perfiles': solo perfiles del usuario (propios o compartidos).
// - En 'tareas': solo tareas pertenecientes al perfil activo actual.
const sugerenciasContextualizadas = computed(() => {
  const q = globalSearchQuery.value.trim().toLowerCase();
  if (!q || !contextoBusqueda.value) return [];

  if (contextoBusqueda.value === 'perfiles') {
    const perfilesPropios = Array.isArray(page.props.perfiles) ? page.props.perfiles : [];
    const perfilesComp = Array.isArray(page.props.perfilesCompartidos) ? page.props.perfilesCompartidos : [];
    const todosPerfiles = [...perfilesPropios, ...perfilesComp];

    return todosPerfiles
      .filter(p => {
        const titulo = (p.tituloPerfil || '').toLowerCase();
        const desc = (p.descripcionPerfil || '').toLowerCase();
        return titulo.includes(q) || desc.includes(q);
      })
      .slice(0, 8)
      .map(p => ({
        uid: 'perfil_' + p.idPerfil,
        tipo: 'perfil',
        id: p.idPerfil,
        idPerfil: p.idPerfil,
        titulo: p.tituloPerfil,
        subtitulo: p.descripcionPerfil || 'Perfil de trabajo',
        badge: 'Perfil',
      }));
  }

  if (contextoBusqueda.value === 'tareas') {
    const tareasPerfilActivo = Array.isArray(page.props.tareas) ? page.props.tareas : [];
    return tareasPerfilActivo
      .filter(t => {
        const titulo = (t.tituloTarea || '').toLowerCase();
        const desc = (t.descripcionTarea || '').toLowerCase();
        return titulo.includes(q) || desc.includes(q);
      })
      .slice(0, 10)
      .map(t => ({
        uid: 'tarea_' + t.idTarea,
        tipo: 'tarea',
        id: t.idTarea,
        idPerfil: t.idPerfil,
        titulo: t.tituloTarea,
        subtitulo: `${t.estadoTarea || 'Pendiente'} · Prioridad ${t.prioridadTarea || 'Media'}`,
        badge: 'Tarea',
      }));
  }

  return [];
});

// Al cambiar de sección (por ejemplo de Mis Perfiles a Mis Tareas), limpiamos el buscador
watch(
  () => (page.url || '').split('?')[0],
  (newPath, oldPath) => {
    if (newPath !== oldPath) {
      clearGlobalSearch();
      isSearchDropdownOpen.value = false;
    }
  }
);

// Estado de notificaciones de tareas próximas a vencer
const notificacionesTareas = ref([]);
const cargandoNotificaciones = ref(false);
const idsNotificacionesLeidas = ref([]);

const { isGlobalDarkMode: isDarkMode, setGlobalDarkMode, syncGlobalDarkModeFromStorage } = usePomodoroTimer();

// Datos computados del usuario autenticado
const authUser = computed(() => page.props.auth?.user || null);

const inicialUsuario = computed(() => {
  const u = authUser.value;
  if (!u) return 'A';
  const base = u.nombre || u.name || u.email || 'A';
  return base.charAt(0).toUpperCase();
});

const nombreCompletoUsuario = computed(() => {
  const u = authUser.value;
  if (!u) return 'Usuario';
  const partes = [u.nombre || u.name, u.apellido].filter(Boolean);
  return partes.length > 0 ? partes.join(' ') : 'Usuario Cronos';
});

const emailUsuario = computed(() => {
  return authUser.value?.email || 'Sin correo registrado';
});

// ===== EVENTOS DEL BUSCADOR CONTEXTUAL =====
const onSearchFocus = () => {
  if (globalSearchQuery.value.trim().length > 0) {
    isSearchDropdownOpen.value = true;
  }
};

const onSearchInput = () => {
  const q = globalSearchQuery.value.trim();
  if (!q) {
    isSearchDropdownOpen.value = false;
    return;
  }
  isSearchDropdownOpen.value = true;
  isNotificationsOpen.value = false;
  isSettingsDropdownOpen.value = false;
};

const limpiarBusquedaGlobal = () => {
  clearGlobalSearch();
  isSearchDropdownOpen.value = false;
};

const seleccionarResultadoBusqueda = (res) => {
  // Fijamos el nombre exacto del perfil o tarea seleccionada para que en pantalla quede únicamente ese elemento
  setGlobalSearch(res.titulo);
  isSearchDropdownOpen.value = false;
};

const handleKeydownShortcut = (e) => {
  if (!contextoBusqueda.value) return;
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault();
    searchInputRef.value?.focus();
  }
};

// ===== LÓGICA DE NOTIFICACIONES DE CAMPANA =====
const cargarIdsLeidosStorage = () => {
  try {
    const raw = localStorage.getItem('cn_read_task_notifications');
    idsNotificacionesLeidas.value = raw ? JSON.parse(raw) : [];
  } catch (e) {
    idsNotificacionesLeidas.value = [];
  }
};

const esNotificacionLeida = (idTarea) => {
  return idsNotificacionesLeidas.value.includes(idTarea);
};

const unreadNotificationsCount = computed(() => {
  return notificacionesTareas.value.filter(t => !idsNotificacionesLeidas.value.includes(t.idTarea)).length;
});

const marcarNotificacionesLeidas = () => {
  const todosIds = Array.from(new Set([
    ...idsNotificacionesLeidas.value,
    ...notificacionesTareas.value.map(t => t.idTarea)
  ]));
  idsNotificacionesLeidas.value = todosIds;
  try {
    localStorage.setItem('cn_read_task_notifications', JSON.stringify(todosIds));
  } catch (e) {
    // ignore
  }
};

const cargarNotificacionesTareas = async () => {
  if (!authUser.value) return;
  cargandoNotificaciones.value = true;
  try {
    const { data } = await axios.get('/notificaciones/tareas-por-vencer');
    if (Array.isArray(data?.notificaciones)) {
      notificacionesTareas.value = data.notificaciones;
    }
  } catch (e) {
    console.warn('No se pudieron obtener las notificaciones de tareas por vencer:', e);
  } finally {
    cargandoNotificaciones.value = false;
  }
};

const toggleNotifications = () => {
  isNotificationsOpen.value = !isNotificationsOpen.value;
  if (isNotificationsOpen.value) {
    isSettingsDropdownOpen.value = false;
    isSearchDropdownOpen.value = false;
    cargarNotificacionesTareas();
  }
};

const irATareaNotificada = async (item) => {
  if (!idsNotificacionesLeidas.value.includes(item.idTarea)) {
    idsNotificacionesLeidas.value.push(item.idTarea);
    try {
      localStorage.setItem('cn_read_task_notifications', JSON.stringify(idsNotificacionesLeidas.value));
    } catch (e) {
      // ignore
    }
  }
  isNotificationsOpen.value = false;

  if (item.idPerfil) {
    try {
      await axios.post(route('perfiles.activar'), { idPerfil: item.idPerfil });
      router.visit(route('gestion-tareas'));
      return;
    } catch (e) {
      // fallback al calendario
    }
  }
  router.visit(route('calendario'));
};

const toggleDropdown = () => {
  isSettingsDropdownOpen.value = !isSettingsDropdownOpen.value;
  if (isSettingsDropdownOpen.value) {
    isNotificationsOpen.value = false;
    isSearchDropdownOpen.value = false;
  }
};

const closeDropdown = () => {
  isSettingsDropdownOpen.value = false;
};

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isSettingsDropdownOpen.value = false;
  }
  if (notificationsRef.value && !notificationsRef.value.contains(event.target)) {
    isNotificationsOpen.value = false;
  }
  if (searchContainerRef.value && !searchContainerRef.value.contains(event.target)) {
    isSearchDropdownOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  document.addEventListener('keydown', handleKeydownShortcut);
  syncGlobalDarkModeFromStorage();
  cargarIdsLeidosStorage();
  cargarNotificacionesTareas();
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  document.removeEventListener('keydown', handleKeydownShortcut);
});

const toggleSidebar = () => {
  isSidebarClosed.value = !isSidebarClosed.value;
};

const closeSidebarOnMobile = () => {
  if (window.innerWidth <= 768) {
    isSidebarClosed.value = true;
  }
};

const toggleMode = () => {
  setGlobalDarkMode(!isDarkMode.value);
};
</script>

<style scoped>
.layout-container {
  background-color: #f8f9fa;
  color: #333;
  min-height: 100vh;
  width: 100%;
  overflow-x: hidden;
  position: relative;
}

.sidebar {
  width: 248px;
  background-color: #ffffff;
  padding: 0 14px 24px 14px;
  display: flex;
  flex-direction: column;
  border-right: 1px solid #eadcd7;
  box-shadow: 2px 0 16px rgba(74, 35, 40, 0.03);
  position: fixed;
  height: 100vh;
  overflow-y: auto;
  overflow-x: hidden;
  left: 0;
  top: 0;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 1040;
}

.logo-area {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 18px 14px 16px 16px;
  margin: 0 -14px 14px -14px;
  border-bottom: 1px solid #f0e5e1;
}

.logo-brand-link {
  display: inline-flex;
  align-items: center;
  text-decoration: none;
  outline: none;
}

.sidebar-logo-img {
  height: 44px;
  width: auto;
  max-width: 162px;
  object-fit: contain;
  display: block;
  transition: transform 0.2s ease;
}

.logo-brand-link:hover .sidebar-logo-img {
  transform: scale(1.02);
}

.nav-menu {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 5px;
  padding: 0;
  margin: 0;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-radius: 10px;
  color: #5a4645;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  font-family: inherit;
  font-size: 0.92rem;
  white-space: nowrap;
}

.nav-btn {
  background: transparent;
  border: none;
  width: 100%;
  text-align: left;
}

.nav-item:hover {
  background-color: #f6eeeb;
  color: #4A2328;
}

.nav-item.active {
  background-color: #4A2328;
  color: #ffffff;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(74, 35, 40, 0.16);
}

.nav-item svg {
  width: 19px;
  height: 19px;
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.nav-item:hover svg {
  transform: translateX(1px);
}

.nav-section-title {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #8c6d6a;
  margin-top: 24px;
  margin-bottom: 8px;
  padding: 0 14px;
}

.nav-section-title.first-section {
  margin-top: 2px;
}

.logout-li {
  margin-top: auto;
}

.logout-btn:hover {
  background-color: #fee2e2;
  color: #ef4444;
}

#sidebarToggle {
  position: fixed;
  top: 14px;
  left: 15px;
  background: #4A2328;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  width: 40px;
  height: 40px;
  color: #ffffff;
  cursor: pointer;
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(74, 35, 40, 0.22);
  transition: all 0.2s ease;
}

#sidebarToggle:hover {
  background: #612c2d;
  transform: translateY(-1px);
}

.sidebar.closed {
  transform: translateX(-248px);
}

.btn-collapse-sidebar {
  width: 32px;
  height: 32px;
  background: #fdfaf8;
  border: 1px solid #eadcd7;
  color: #612c2d;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border-radius: 8px;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.btn-collapse-sidebar:hover {
  background-color: #f5ebe8;
  border-color: #c9b1aa;
  color: #4A2328;
}

#content {
  margin-left: 248px;
  padding: 20px;
  transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

#content.expanded {
  margin-left: 0;
}

/* Espacio para el botón flotante cuando el sidebar está colapsado */
#content.expanded .top-bar {
  padding-left: 68px;
}

/* ===== TOP BAR (NAVBAR SUPERIOR SAAS) ===== */
.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 13px 24px;
  background-color: #ffffff;
  border-radius: 0;
  margin: -20px -20px 24px -20px;
  box-shadow: 0 2px 10px rgba(74, 35, 40, 0.04);
  border-bottom: 1px solid #eadcd7;
  position: sticky;
  top: 0;
  z-index: 1020;
}

/* Buscador Contextual + Dropdown de Resultados */
.search-container {
  position: relative;
  flex: 1;
  max-width: 440px;
}

.navbar-search-box {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
  background-color: #fdfaf8;
  border: 1px solid #e2d3ce;
  border-radius: 11px;
  padding: 0 10px 0 13px;
  height: 40px;
  transition: all 0.2s ease;
}

.navbar-search-box:focus-within,
.navbar-search-box.is-open {
  background-color: #ffffff;
  border-color: #612c2d;
  box-shadow: 0 0 0 3px rgba(97, 44, 45, 0.1);
}

.navbar-search-icon {
  color: #8c6d6a;
  flex-shrink: 0;
  margin-right: 9px;
}

.navbar-search-input {
  width: 100%;
  border: none !important;
  background: transparent !important;
  box-shadow: none !important;
  outline: none !important;
  font-size: 0.87rem;
  font-weight: 500;
  color: #2b1819;
  padding: 0;
}

.navbar-search-input::placeholder {
  color: #9a827f;
}

.navbar-search-clear {
  background: transparent;
  border: none;
  color: #8c6d6a;
  font-size: 1.2rem;
  line-height: 1;
  padding: 2px 6px;
  cursor: pointer;
}

.navbar-search-kbd {
  font-family: inherit;
  font-size: 0.7rem;
  font-weight: 600;
  color: #8c6d6a;
  background-color: #f3eae7;
  border: 1px solid #e0cfc9;
  border-radius: 6px;
  padding: 2px 6px;
  white-space: nowrap;
  flex-shrink: 0;
}

/* Dropdown de Resultados del Buscador */
.search-results-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: 100%;
  min-width: 320px;
  background-color: #ffffff;
  border: 1px solid #eadcd7;
  border-radius: 14px;
  box-shadow: 0 14px 34px rgba(43, 24, 25, 0.16);
  z-index: 1070;
  overflow: hidden;
}

.search-dropdown-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  background-color: #fdfaf8;
  border-bottom: 1px solid #efe4e0;
  font-size: 0.76rem;
  color: #6b7280;
}

.search-dropdown-header strong {
  color: #4A2328;
}

.search-count-badge {
  background-color: #f5ebe8;
  color: #612c2d;
  border: 1px solid #e2cdc6;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 999px;
}

.search-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 20px 14px;
  font-size: 0.82rem;
  color: #6b7280;
}

.search-empty {
  padding: 20px 16px;
  text-align: center;
}

.search-empty-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #2b1819;
  margin: 0 0 3px 0;
}

.search-empty-sub {
  font-size: 0.76rem;
  color: #6b7280;
  margin: 0;
}

.search-results-list {
  list-style: none;
  margin: 0;
  padding: 5px;
  max-height: 310px;
  overflow-y: auto;
}

.search-result-item {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 9px 11px;
  border-radius: 10px;
  cursor: pointer;
  transition: background-color 0.16s ease;
}

.search-result-item:hover {
  background-color: #f6eeeb;
}

.search-result-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.search-result-icon.type-tarea {
  background-color: #f5ebe8;
  color: #4A2328;
}

.search-result-icon.type-perfil {
  background-color: #fef3c7;
  color: #b45309;
}

.search-result-info {
  flex: 1;
  min-width: 0;
}

.search-result-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #2b1819;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.search-result-sub {
  font-size: 0.73rem;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.search-result-badge {
  font-size: 0.68rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 999px;
  flex-shrink: 0;
}

.badge-tarea {
  background-color: #f5ebe8;
  color: #612c2d;
}

.badge-perfil {
  background-color: #fef3c7;
  color: #92400e;
}

/* Contenedor de acciones derechas */
.top-bar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-left: auto;
}

.icon-button {
  position: relative;
  width: 40px;
  height: 40px;
  background: #fdfaf8;
  border: 1px solid #e6d7d2;
  color: #5a4645;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border-radius: 10px;
  transition: all 0.2s ease;
}

.icon-button:hover,
.icon-button.is-active {
  background-color: #f5ebe8;
  border-color: #cbb4ad;
  color: #4A2328;
}

/* Badge rojo de notificaciones */
.notification-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  min-width: 19px;
  height: 19px;
  padding: 0 5px;
  border-radius: 999px;
  background-color: #dc2626;
  color: #ffffff;
  font-size: 0.68rem;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 6px rgba(220, 38, 38, 0.35);
}

/* Dropdown de la Campana */
.notifications-dropdown-container,
.settings-dropdown-container {
  position: relative;
}

.notifications-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 350px;
  max-width: calc(100vw - 28px);
  background-color: #ffffff;
  border: 1px solid #eadcd7;
  border-radius: 14px;
  box-shadow: 0 12px 32px rgba(43, 24, 25, 0.14);
  z-index: 1060;
  overflow: hidden;
}

.notif-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid #efe4e0;
  background-color: #fdfaf8;
}

.notif-header-title-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}

.notif-header-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #4A2328;
}

.notif-count-pill {
  background-color: #f5ebe8;
  color: #612c2d;
  border: 1px solid #e2cdc6;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 1px 8px;
  border-radius: 999px;
}

.notif-mark-read-btn {
  background: transparent;
  border: none;
  color: #7b413f;
  font-size: 0.76rem;
  font-weight: 600;
  cursor: pointer;
  padding: 2px 4px;
  text-decoration: underline;
}

.notif-mark-read-btn:hover {
  color: #4A2328;
}

.notif-body {
  max-height: 320px;
  overflow-y: auto;
}

.notif-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 28px 16px;
  font-size: 0.83rem;
  color: #6b7280;
}

.notif-spinner {
  width: 18px;
  height: 18px;
  border: 2.5px solid #eadcd7;
  border-top-color: #612c2d;
  border-radius: 50%;
  animation: notifSpin 0.7s linear infinite;
}

@keyframes notifSpin {
  to { transform: rotate(360deg); }
}

.notif-empty {
  padding: 28px 20px;
  text-align: center;
}

.notif-empty-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background-color: #ecfdf5;
  color: #059669;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 10px;
}

.notif-empty-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #2b1819;
  margin: 0 0 4px 0;
}

.notif-empty-sub {
  font-size: 0.79rem;
  color: #6b7280;
  margin: 0;
  line-height: 1.4;
}

.notif-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid #f4ece9;
  cursor: pointer;
  transition: background-color 0.18s ease;
}

.notif-item:last-child {
  border-bottom: none;
}

.notif-item.is-unread {
  background-color: #fffaf6;
}

.notif-item:hover {
  background-color: #f6eeeb;
}

.notif-item-icon {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 2px;
}

.notif-item-icon.urgent {
  background-color: #fef2f2;
  color: #dc2626;
}

.notif-item-icon.soon {
  background-color: #fef3c7;
  color: #d97706;
}

.notif-item-content {
  flex: 1;
  min-width: 0;
}

.notif-item-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 4px;
}

.notif-item-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #2b1819;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notif-hours-tag {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 999px;
  background-color: #fef3c7;
  color: #92400e;
  white-space: nowrap;
  flex-shrink: 0;
}

.notif-hours-tag.urgent {
  background-color: #fee2e2;
  color: #b91c1c;
}

.notif-item-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.74rem;
  color: #6b7280;
}

.notif-profile-badge {
  font-weight: 600;
  color: #612c2d;
}

.notif-footer {
  padding: 10px 16px;
  background-color: #fdfaf8;
  border-top: 1px solid #efe4e0;
  text-align: center;
}

.notif-footer-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 0.8rem;
  font-weight: 700;
  color: #4A2328;
  text-decoration: none;
  transition: color 0.18s ease;
}

.notif-footer-link:hover {
  color: #7b413f;
}

/* Botón y Dropdown de Perfil Mejorado */
.user-profile-trigger {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 4px 10px 4px 5px;
  border-radius: 999px;
  background-color: #fdfaf8;
  border: 1px solid #e6d7d2;
  color: #4A2328;
  cursor: pointer;
  transition: all 0.2s ease;
}

.user-profile-trigger:hover,
.user-profile-trigger.is-open {
  background-color: #f5ebe8;
  border-color: #cbb4ad;
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: #4A2328;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.85rem;
  flex-shrink: 0;
}

.profile-chevron {
  color: #612c2d;
  transition: transform 0.2s ease;
}

.profile-chevron.rotated {
  transform: rotate(180deg);
}

.settings-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  background-color: #ffffff;
  border-radius: 14px;
  box-shadow: 0 12px 32px rgba(43, 24, 25, 0.14);
  min-width: 245px;
  z-index: 1060;
  overflow: hidden;
  border: 1px solid #eadcd7;
}

.dropdown-user-header {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 14px 16px;
  background-color: #fdfaf8;
}

.dropdown-user-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background-color: #4A2328;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.92rem;
  flex-shrink: 0;
}

.dropdown-user-info {
  min-width: 0;
  flex: 1;
}

.dropdown-user-name {
  font-size: 0.88rem;
  font-weight: 700;
  color: #2b1819;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dropdown-user-email {
  font-size: 0.75rem;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dropdown-divider {
  height: 1px;
  background-color: #efe4e0;
}

.dropdown-menu-items {
  padding: 6px;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  text-align: left;
  padding: 9px 12px;
  background: none;
  border: none;
  border-radius: 9px;
  font-size: 0.86rem;
  font-weight: 600;
  color: #3f2e2d;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.18s ease;
}

.dropdown-item:hover {
  background-color: #f6eeeb;
  color: #4A2328;
}

.dropdown-item-danger {
  color: #b91c1c;
}

.dropdown-item-danger:hover {
  background-color: #fef2f2;
  color: #991b1b;
}

/* Animación suave para los dropdowns */
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: opacity 0.16s ease, transform 0.16s ease;
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

@media (max-width: 768px) {
  #content {
    margin-left: 0 !important;
    padding-top: 70px;
  }

  .top-bar {
    margin-top: -70px;
    padding-left: 64px;
    padding-right: 16px;
    border-radius: 0;
  }

  .search-container {
    max-width: 220px;
  }

  .navbar-search-kbd {
    display: none;
  }
}

@media (max-width: 520px) {
  .search-container {
    max-width: 165px;
  }
}

/* Notificaciones Flash Globales */
.global-toast {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  border-radius: 8px;
  margin-bottom: 24px;
  font-size: 14px;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  animation: slideIn 0.3s ease-out;
  border: 1px solid transparent;
}

.toast-content {
  display: flex;
  align-items: center;
  gap: 10px;
}

.toast-content i {
  font-size: 16px;
}

.error-toast {
  background-color: #fdf2f2;
  color: #9b1c1c;
  border-color: #fbd5d5;
}

.success-toast {
  background-color: #f3faf7;
  color: #03543f;
  border-color: #def7ec;
}

.toast-close {
  background: transparent;
  border: none;
  font-size: 20px;
  color: inherit;
  cursor: pointer;
  line-height: 1;
  padding: 0;
  margin-left: 16px;
  opacity: 0.7;
  transition: opacity 0.2s;
}

.toast-close:hover {
  opacity: 1;
}

@keyframes slideIn {
  from { transform: translateY(-10px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
</style>

<style>
/* Global Dark Mode Overrides */
body.cn-body-dark .layout-container {
  background-color: #612c2d !important;
  color: #ffffff !important;
}

body.cn-body-dark #content {
  background-color: #612c2d !important;
}

body.cn-body-dark .sidebar {
  background-color: #4d2323 !important;
  border-right: 1px solid #7b413f !important;
  box-shadow: 4px 0 20px rgba(0, 0, 0, 0.25) !important;
}

body.cn-body-dark .logo-area {
  border-bottom-color: rgba(244, 190, 149, 0.16) !important;
}

body.cn-body-dark .btn-collapse-sidebar {
  background-color: #3b1919 !important;
  border-color: #7b413f !important;
  color: #fcd5b8 !important;
}

body.cn-body-dark .btn-collapse-sidebar:hover {
  background-color: #612c2d !important;
  border-color: #f4be95 !important;
  color: #ffffff !important;
}

body.cn-body-dark #sidebarToggle {
  background-color: #f4be95 !important;
  color: #3b1919 !important;
  border-color: #f4be95 !important;
}

body.cn-body-dark .nav-item {
  color: #fcd5b8 !important;
}

body.cn-body-dark .nav-item:hover {
  background-color: rgba(244, 190, 149, 0.14) !important;
  color: #ffffff !important;
}

body.cn-body-dark .nav-item.active {
  background-color: #f4be95 !important;
  color: #3b1919 !important;
  font-weight: 700 !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.22) !important;
}

body.cn-body-dark .nav-section-title {
  color: #e2b99b !important;
}

/* ===== Dark Mode Navbar, Buscador, Campana y Perfil ===== */
body.cn-body-dark .top-bar {
  background-color: #4d2323 !important;
  border-bottom: 1px solid #7b413f !important;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.28) !important;
}

body.cn-body-dark .navbar-search-box {
  background-color: #3b1919 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .navbar-search-box:focus-within,
body.cn-body-dark .navbar-search-box.is-open {
  background-color: #2f1212 !important;
  border-color: #f4be95 !important;
  box-shadow: 0 0 0 3px rgba(244, 190, 149, 0.2) !important;
}

body.cn-body-dark .navbar-search-icon,
body.cn-body-dark .navbar-search-clear {
  color: #e2b99b !important;
}

body.cn-body-dark .navbar-search-input {
  background-color: transparent !important;
  color: #ffffff !important;
}

body.cn-body-dark .navbar-search-input::placeholder {
  color: #c69c89 !important;
}

body.cn-body-dark .navbar-search-kbd {
  background-color: #4d2323 !important;
  border-color: #7b413f !important;
  color: #fcd5b8 !important;
}

body.cn-body-dark .icon-button,
body.cn-body-dark .user-profile-trigger {
  background-color: #3b1919 !important;
  border-color: #7b413f !important;
  color: #fcd5b8 !important;
}

body.cn-body-dark .icon-button:hover,
body.cn-body-dark .icon-button.is-active,
body.cn-body-dark .user-profile-trigger:hover,
body.cn-body-dark .user-profile-trigger.is-open {
  background-color: #612c2d !important;
  border-color: #f4be95 !important;
  color: #ffffff !important;
}

body.cn-body-dark .notification-badge {
  border-color: #4d2323 !important;
}

body.cn-body-dark .avatar,
body.cn-body-dark .dropdown-user-avatar {
  background-color: #f4be95 !important;
  color: #3b1919 !important;
}

body.cn-body-dark .profile-chevron {
  color: #fcd5b8 !important;
}

/* Superficies oscuras de los Dropdowns (Buscador, Campana y Perfil) */
body.cn-body-dark .search-results-dropdown,
body.cn-body-dark .notifications-dropdown,
body.cn-body-dark .settings-dropdown {
  background-color: #3b1919 !important;
  border-color: #7b413f !important;
  box-shadow: 0 14px 36px rgba(0, 0, 0, 0.48) !important;
}

body.cn-body-dark .search-dropdown-header,
body.cn-body-dark .notif-header,
body.cn-body-dark .notif-footer,
body.cn-body-dark .dropdown-user-header {
  background-color: #2f1212 !important;
  border-color: #7b413f !important;
  color: #e2b99b !important;
}

body.cn-body-dark .search-dropdown-header strong,
body.cn-body-dark .search-result-title,
body.cn-body-dark .search-empty-title,
body.cn-body-dark .notif-header-title,
body.cn-body-dark .notif-item-title,
body.cn-body-dark .notif-empty-title,
body.cn-body-dark .dropdown-user-name {
  color: #ffffff !important;
}

body.cn-body-dark .search-count-badge,
body.cn-body-dark .notif-count-pill {
  background-color: #4d2323 !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .search-result-item:hover {
  background-color: #4d2323 !important;
}

body.cn-body-dark .search-result-sub,
body.cn-body-dark .search-empty-sub,
body.cn-body-dark .search-loading {
  color: #e2b99b !important;
}

body.cn-body-dark .search-result-icon.type-tarea,
body.cn-body-dark .badge-tarea {
  background-color: rgba(244, 190, 149, 0.18) !important;
  color: #fcd5b8 !important;
}

body.cn-body-dark .search-result-icon.type-perfil,
body.cn-body-dark .badge-perfil {
  background-color: rgba(251, 191, 36, 0.18) !important;
  color: #fde68a !important;
}

body.cn-body-dark .notif-mark-read-btn,
body.cn-body-dark .notif-footer-link {
  color: #f4be95 !important;
}

body.cn-body-dark .notif-mark-read-btn:hover,
body.cn-body-dark .notif-footer-link:hover {
  color: #fcd5b8 !important;
}

body.cn-body-dark .notif-item {
  border-bottom-color: rgba(123, 65, 63, 0.55) !important;
}

body.cn-body-dark .notif-item.is-unread {
  background-color: rgba(244, 190, 149, 0.07) !important;
}

body.cn-body-dark .notif-item:hover {
  background-color: #4d2323 !important;
}

body.cn-body-dark .notif-item-icon.urgent {
  background-color: rgba(239, 68, 68, 0.2) !important;
  color: #fca5a5 !important;
}

body.cn-body-dark .notif-item-icon.soon {
  background-color: rgba(245, 158, 11, 0.2) !important;
  color: #fde68a !important;
}

body.cn-body-dark .notif-hours-tag {
  background-color: rgba(245, 158, 11, 0.2) !important;
  color: #fde68a !important;
}

body.cn-body-dark .notif-hours-tag.urgent {
  background-color: rgba(239, 68, 68, 0.22) !important;
  color: #fca5a5 !important;
}

body.cn-body-dark .notif-item-meta,
body.cn-body-dark .notif-empty-sub,
body.cn-body-dark .notif-loading,
body.cn-body-dark .dropdown-user-email {
  color: #e2b99b !important;
}

body.cn-body-dark .notif-profile-badge {
  color: #fcd5b8 !important;
}

body.cn-body-dark .notif-empty-icon {
  background-color: rgba(16, 185, 129, 0.18) !important;
  color: #6ee7b7 !important;
}

body.cn-body-dark .dropdown-divider {
  background-color: #7b413f !important;
}

body.cn-body-dark .dropdown-item {
  color: #fcd5b8 !important;
}

body.cn-body-dark .dropdown-item:hover {
  background-color: #4d2323 !important;
  color: #ffffff !important;
}

body.cn-body-dark .dropdown-item-danger {
  color: #fca5a5 !important;
}

body.cn-body-dark .dropdown-item-danger:hover {
  background-color: rgba(239, 68, 68, 0.2) !important;
  color: #fecaca !important;
}

body.cn-body-dark .error-toast {
  background-color: #4d2323 !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .success-toast {
  background-color: #4d2323 !important;
  color: #ffffff !important;
  border-color: #f4be95 !important;
}

/* Dynamic sub-page elements (Dashboard, Calendario, Tareas, etc.) */
body.cn-body-dark .welcome-title,
body.cn-body-dark .section-title,
body.cn-body-dark .bar-chart-title,
body.cn-body-dark .donut-card-title,
body.cn-body-dark .modal-title,
body.cn-body-dark h1,
body.cn-body-dark h2,
body.cn-body-dark h3,
body.cn-body-dark h4,
body.cn-body-dark h5,
body.cn-body-dark h6 {
  color: #ffffff !important;
}

body.cn-body-dark p,
body.cn-body-dark .section-subtitle,
body.cn-body-dark .streak-label,
body.cn-body-dark .no-data-text,
body.cn-body-dark .profile-date,
body.cn-body-dark .text-muted,
body.cn-body-dark .text-secondary {
  color: #fcd5b8 !important;
}

body.cn-body-dark .profile-card,
body.cn-body-dark .streak-card,
body.cn-body-dark .donut-card,
body.cn-body-dark .bar-chart-card,
body.cn-body-dark .task-card-light,
body.cn-body-dark .info-card,
body.cn-body-dark .card,
body.cn-body-dark .modal-content,
body.cn-body-dark .rounded-xl,
body.cn-body-dark .streak-card-with-accent {
  background-color: #4d2323 !important;
  border: 1px solid #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .profile-card:hover,
body.cn-body-dark .task-card-light:hover,
body.cn-body-dark .info-card:hover,
body.cn-body-dark .rounded-xl:hover {
  border-color: #f4be95 !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4) !important;
}

body.cn-body-dark .profile-card.active-card {
  border-color: #f4be95 !important;
  box-shadow: 0 2px 8px rgba(244, 190, 149, 0.25) !important;
}

body.cn-body-dark .profile-icon {
  border: 2px solid #f4be95 !important;
  color: #f4be95 !important;
  background-color: #612c2d !important;
}

body.cn-body-dark .profile-card .progress-bar {
  background-color: #3b1717 !important;
}

body.cn-body-dark .profile-card .progress-fill {
  background-color: #f4be95 !important;
}

body.cn-body-dark .streak-number,
body.cn-body-dark .streak-icon {
  color: #f4be95 !important;
}

body.cn-body-dark .add-profile-card {
  border: 2px dashed #a55e57 !important;
  color: #f4be95 !important;
  background-color: transparent !important;
}

body.cn-body-dark .add-profile-card:hover {
  border-color: #f4be95 !important;
  background-color: rgba(244, 190, 149, 0.05) !important;
}

body.cn-body-dark .dashboard-page {
  background-color: #612c2d !important;
}

/* Streak Card Left Accent & Values */
.streak-value {
  color: #612c2d;
}
.streak-text {
  color: #666;
}
.streak-icon-svg {
  color: #612c2d;
  opacity: 0.8;
}
.streak-accent-bar {
  display: none;
}

body.cn-body-dark .streak-card-with-accent {
  position: relative !important;
  padding-left: 0 !important;
}

body.cn-body-dark .streak-card-with-accent .flex-1 {
  padding-left: 12px !important;
}

body.cn-body-dark .streak-accent-bar {
  display: block !important;
  position: absolute !important;
  left: 0 !important;
  top: 0 !important;
  bottom: 0 !important;
  width: 12px !important;
  background-color: #f4be95 !important;
  border-top-left-radius: 12px !important;
  border-bottom-left-radius: 12px !important;
}

body.cn-body-dark .streak-value {
  color: #ffffff !important;
}

body.cn-body-dark .streak-text {
  color: #fcd5b8 !important;
}

body.cn-body-dark .streak-icon-svg {
  color: #f4be95 !important;
}

body.cn-body-dark .range-select {
  background-color: #612c2d !important;
  color: #ffffff !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .range-select:focus {
  border-color: #f4be95 !important;
}

body.cn-body-dark .owner-avatar-badge-card {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
  border-color: #4d2323 !important;
}

body.cn-body-dark .shared-icon-badge-card {
  color: #f4be95 !important;
}

body.cn-body-dark .btn-primary,
body.cn-body-dark .btn-premium {
  background-color: #f4be95 !important;
  border-color: #f4be95 !important;
  color: #4d2323 !important;
  font-weight: 600 !important;
}

body.cn-body-dark .btn-primary:hover,
body.cn-body-dark .btn-premium:hover {
  background-color: #a55e57 !important;
  border-color: #a55e57 !important;
  color: #ffffff !important;
}

body.cn-body-dark .btn-outline-primary,
body.cn-body-dark .btn-outline-premium {
  border-color: #f4be95 !important;
  color: #f4be95 !important;
  background-color: transparent !important;
}

body.cn-body-dark .btn-outline-primary:hover,
body.cn-body-dark .btn-outline-premium:hover {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
}

body.cn-body-dark .form-control,
body.cn-body-dark .form-select,
body.cn-body-dark input,
body.cn-body-dark select,
body.cn-body-dark textarea {
  background-color: #612c2d !important;
  color: #ffffff !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .form-control:focus,
body.cn-body-dark .form-select:focus,
body.cn-body-dark input:focus,
body.cn-body-dark select:focus,
body.cn-body-dark textarea:focus {
  border-color: #f4be95 !important;
  box-shadow: 0 0 0 2px rgba(244, 190, 149, 0.25) !important;
}

body.cn-body-dark .add-button {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
  font-weight: 600 !important;
}

body.cn-body-dark .add-button:hover {
  background-color: #fbe8da !important;
  color: #4d2323 !important;
}

body.cn-body-dark .text-\[\#666\],
body.cn-body-dark .text-\[\#1a1a1a\] {
  color: #fcd5b8 !important;
}

/* Profiles Management Page (GestionPerfil.vue) Dark Mode Overrides */
body.cn-body-dark .btn-marron {
  background-color: #f4be95 !important;
  border-color: #f4be95 !important;
  color: #4d2323 !important;
  font-weight: 600 !important;
}

body.cn-body-dark .btn-marron:hover,
body.cn-body-dark .btn-marron:focus {
  background-color: #fbe8da !important;
  border-color: #fbe8da !important;
  color: #4d2323 !important;
}

body.cn-body-dark .btn-filtro {
  background-color: #4d2323 !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .btn-filtro:hover {
  background-color: #7b413f !important;
  color: #ffffff !important;
  border-color: #f4be95 !important;
}

body.cn-body-dark .btn-filtro.active {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
  border-color: #f4be95 !important;
  font-weight: 600 !important;
}

body.cn-body-dark .conmutador-vistas-box {
  background-color: #4d2323 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .btn-vista {
  color: #fcd5b8 !important;
}

body.cn-body-dark .btn-vista:hover {
  background-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .btn-vista.active {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
}

body.cn-body-dark .card-perfil {
  background-color: #4d2323 !important;
  border-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .icon-box-perfil {
  background-color: #612c2d !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .btn-outline-secondary {
  border-color: #7b413f !important;
  color: #fcd5b8 !important;
  background-color: transparent !important;
}

body.cn-body-dark .btn-outline-secondary:hover {
  background-color: #7b413f !important;
  color: #ffffff !important;
  border-color: #f4be95 !important;
}

body.cn-body-dark .btn-outline-danger {
  border-color: #7b413f !important;
  color: #fca5a5 !important;
  background-color: transparent !important;
}

body.cn-body-dark .btn-outline-danger:hover {
  background-color: #991b1b !important;
  color: #ffffff !important;
  border-color: #fca5a5 !important;
}

/* Modal Headers & Controls Globally */
body.cn-body-dark .modal-header {
  background-color: #4d2323 !important;
  border-bottom: 1px solid #7b413f !important;
}

body.cn-body-dark .modal-header .modal-title,
body.cn-body-dark .modal-header h5,
body.cn-body-dark .modal-header .text-dark,
body.cn-body-dark .modal-content .text-dark {
  color: #ffffff !important;
}

body.cn-body-dark .modal-header .btn-close {
  filter: invert(1) !important;
}

body.cn-body-dark .input-custom,
body.cn-body-dark .select-custom {
  background-color: #612c2d !important;
  color: #ffffff !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .input-custom:focus,
body.cn-body-dark .select-custom:focus {
  border-color: #f4be95 !important;
  box-shadow: 0 0 0 2px rgba(244, 190, 149, 0.25) !important;
}

body.cn-body-dark .btn-light-custom {
  background-color: #612c2d !important;
  color: #fcd5b8 !important;
  border: 1px solid #7b413f !important;
}

body.cn-body-dark .btn-light-custom:hover {
  background-color: #7b413f !important;
  color: #ffffff !important;
}

/* Icon Selection Buttons in Modal */
body.cn-body-dark .modal-body .btn.border {
  background-color: #612c2d !important;
  border-color: #7b413f !important;
  color: #fcd5b8 !important;
}

body.cn-body-dark .modal-body .btn.border:hover {
  background-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .modal-body .btn.border.border-marron {
  background-color: #f4be95 !important;
  border-color: #f4be95 !important;
  color: #4d2323 !important;
}

/* Tasks Management Page (GestionTareas.vue) Dark Mode Overrides */
body.cn-body-dark .barra-filtros-tareas {
  background-color: #4d2323 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .text-marron-institucional {
  color: #f4be95 !important;
}

body.cn-body-dark .btn-light {
  background-color: #612c2d !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .btn-light:hover {
  background-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .badge-estado {
  background-color: #612c2d !important;
  border-color: #7b413f !important;
  color: #fcd5b8 !important;
}

body.cn-body-dark .sugerencia-ia-box {
  background-color: #3b1717 !important;
  color: #ffffff !important;
  border-color: #0dcaf0 !important;
}

body.cn-body-dark .sugerencia-ia-box strong {
  color: #0dcaf0 !important;
}

/* Ver Detalle Modal (GestionTareas.vue) Dark Mode Overrides */
body.cn-body-dark .header-detalle-marron {
  background-color: #3b1717 !important;
  border-bottom: 1px solid #7b413f !important;
}

body.cn-body-dark .header-detalle-marron .badge.bg-white {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
}

body.cn-body-dark .modal-body.bg-white {
  background-color: #4d2323 !important;
}

body.cn-body-dark .modal-body .text-dark,
body.cn-body-dark .modal-body .fw-semibold.text-dark {
  color: #ffffff !important;
}

body.cn-body-dark .modal-body .text-marron-institucional {
  color: #f4be95 !important;
}

body.cn-body-dark .modal-body .border-top {
  border-color: #7b413f !important;
}

/* Alertas de Bootstrap en Modo Oscuro con Alto Contraste */
body.cn-body-dark .alert-info {
  background-color: rgba(13, 202, 240, 0.15) !important;
  color: #6edff6 !important;
  border-color: rgba(13, 202, 240, 0.3) !important;
}

body.cn-body-dark .alert-danger {
  background-color: rgba(220, 53, 69, 0.15) !important;
  color: #ea868f !important;
  border-color: rgba(220, 53, 69, 0.3) !important;
}

body.cn-body-dark .alert-success {
  background-color: rgba(40, 167, 69, 0.15) !important;
  color: #75ec88 !important;
  border-color: rgba(40, 167, 69, 0.3) !important;
}

body.cn-body-dark .alert-warning {
  background-color: rgba(255, 193, 7, 0.15) !important;
  color: #ffda6a !important;
  border-color: rgba(255, 193, 7, 0.3) !important;
}

/* Inversión global de botones de cerrar en modo oscuro */
body.cn-body-dark .btn-close {
  filter: invert(1) !important;
}
</style>
