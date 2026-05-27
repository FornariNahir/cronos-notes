<template>
  <AppLayout>
    <div class="container pt-4">

      <div v-if="perfilActivo" class="mb-4">
        <h2 class="text-white m-0">
          Perfil Activo: <span style="color: #b136d9;">{{ perfilActivo.tituloPerfil }}</span>
        </h2>
      </div>

      <div v-if="perfilActivo">

        <div id="estadisticas" class="mb-4">
          Estadísticas
        </div>

        <div class="row">
          <div class="col-md-4" v-for="tarea in tareas" :key="tarea.idTarea">
            <div class="task-card">
              <div class="icon"></div>
              <h3>{{ tarea.tituloTarea }}</h3>
              <p>{{ tarea.descripcionTarea }}</p>
            </div>
          </div>

          <div class="col-12 text-center text-white mt-4" v-if="tareas.length === 0">
            <p>No tienes tareas pendientes en este perfil. ¡Aprovecha para descansar!</p>
          </div>
        </div>
      </div>

      <div v-else class="row justify-content-center mt-5">
        <div class="col-md-8 text-center text-white">
          <div class="task-card p-5" style="display: flex; flex-direction: column; align-items: center;">
            <h2 class="mb-3" style="font-weight: bold; font-size: 2.5rem;">Selecciona un perfil</h2>
            <p class="fs-5 mb-4 text-muted">
              Para ver tus tareas, estadísticas y usar el Pomodoro, primero debes seleccionar con qué perfil deseas trabajar.
            </p>
            
            <Link :href="route('perfiles.index')" class="btn" style="background: #b136d9; color: white; padding: 12px 30px; border-radius: 12px; font-weight: bold; font-size: 1.2rem; text-decoration: none; transition: background 0.3s;">
              Ir a Mis Perfiles
            </Link>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// Recibimos las variables desde el DashboardController
defineProps({
  perfilActivo: {
    type: Object,
    default: null
  },
  tareas: {
    type: Array,
    default: () => []
  }
});
</script>