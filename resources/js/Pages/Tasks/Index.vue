<template>
  <AppLayout>
    <div v-if="perfilActivo" class="container pt-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold" style="color: #fff;">
          Tareas del Perfil: <span style="color: #d34cf5;">{{ perfilActivo.tituloPerfil }}</span>
        </h2>
        <div class="d-flex gap-2 align-items-center">
          <button v-if="mostrarCompletadas" class="btn btn-outline-light btn-sm" @click="toggleFilter">
            Ver Pendientes
          </button>
          <button v-else class="btn btn-outline-success btn-sm" @click="toggleFilter">
            Ver Completadas
          </button>
          <button class="btn btn-add" @click="showAddModal = true">+</button>
        </div>
      </div>

      <div class="row row-cols-1 row-cols-md-3 g-4">
        <div v-if="tareas.length === 0" class="col-12 text-center">
          <div class="alert alert-info" style="background-color: #1f1f35; border: 1px solid #a44fd1; color: #fff;">
            No hay tareas registradas en este perfil.
            <a href="#" style="color: #d34cf5;" @click.prevent="showAddModal = true">¡Agrega una nueva tarea!</a>
          </div>
        </div>

        <div v-for="tarea in tareas" :key="tarea.idTarea" class="col">
          <div class="task-card position-relative">
            <!-- Botón para completar directamente en la esquina superior izquierda -->
            <button 
              v-if="tarea.estadoTarea !== 'Completado'" 
              class="btn-completar-rapido" 
              @click.stop="completarTarea(tarea.idTarea)" 
              title="Marcar como completada"
            >
              <i class="fas fa-check"></i>
            </button>

            <div class="dropdown-wrapper" style="position: absolute; top: 10px; right: 10px;">
              <button class="btn btn-sm dropdown-toggle-custom" type="button" @click.stop="toggleDropdown(tarea.idTarea)">
                &#x22EE;
              </button>
              <div v-if="openDropdownId === tarea.idTarea" class="custom-dropdown-menu" @click.stop>
                <button v-if="tarea.estadoTarea !== 'Completado'" class="custom-dropdown-item text-success" @click="completarTarea(tarea.idTarea); openDropdownId = null">Completar</button>
                <button class="custom-dropdown-item" @click="openEdit(tarea); openDropdownId = null">Editar</button>
                <button class="custom-dropdown-item" @click="openView(tarea); openDropdownId = null">Ver tarea</button>
                <button class="custom-dropdown-item text-danger" @click="openDelete(tarea); openDropdownId = null">Eliminar</button>
              </div>
            </div>

            <h4>{{ tarea.tituloTarea }}</h4>
            <p>{{ tarea.descripcionTarea }}</p>

            <div class="d-flex flex-column gap-1 mb-3">
              <small class="text-muted"><i class="far fa-clock"></i> Límite: {{ formatDate(tarea.fechaLimite) }}</small>
              <small v-if="tarea.fechaFinTarea" class="text-muted"><i class="far fa-check-circle"></i> Finalizada: {{ formatDate(tarea.fechaFinTarea) }}</small>
              <small :class="estadoColor(tarea)">
                <i class="fas fa-circle"></i> {{ tarea.estadoTarea }}
              </small>
              <small class="text-muted"><i class="fas fa-flag"></i> Prioridad: {{ tarea.prioridadTarea }}</small>
              <small v-if="tarea.estimacionEsfuerzo" class="text-muted">
                <i class="fas fa-hourglass-half"></i> Esfuerzo: {{ tarea.estimacionEsfuerzo }} Pomodoros
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="row justify-content-center mt-5">
      <div class="col-md-8 text-center">
        <div class="task-card p-5" style="display: flex; flex-direction: column; align-items: center;">
          <h2 class="mb-3" style="font-weight: bold; font-size: 2rem; color: #d34cf5;">Selecciona un perfil</h2>
          <p class="fs-5 mb-4 text-muted">
            Para ver tus tareas, primero debes seleccionar con qué perfil deseas trabajar.
          </p>
          <Link :href="route('perfiles.index')" class="btn" style="background: #b136d9; color: white; padding: 12px 30px; border-radius: 12px; font-weight: bold; text-decoration: none;">
            Ir a Mis Perfiles
          </Link>
        </div>
      </div>
    </div>

    <AddTaskModal :isOpen="showAddModal" @close="showAddModal = false" />
    <EditTaskModal :isOpen="showEditModal" :tarea="selectedTarea" @close="showEditModal = false" />
    <ViewTaskModal :isOpen="showViewModal" :tarea="selectedTarea" @close="showViewModal = false" />
    <DeleteTaskModal :isOpen="showDeleteModal" :tarea="selectedTarea" @close="showDeleteModal = false" />
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AddTaskModal from '@/Components/AddTaskModal.vue';
import EditTaskModal from '@/Components/EditTaskModal.vue';
import ViewTaskModal from '@/Components/ViewTaskModal.vue';
import DeleteTaskModal from '@/Components/DeleteTaskModal.vue';

const props = defineProps({
  tareas: Array,
  perfilActivo: Object,
  mostrarCompletadas: Boolean
});

const showAddModal = ref(false);
const showEditModal = ref(false);
const showViewModal = ref(false);
const showDeleteModal = ref(false);
const selectedTarea = ref(null);
const openDropdownId = ref(null);

const toggleDropdown = (id) => {
  openDropdownId.value = openDropdownId.value === id ? null : id;
};

const closeDropdown = (e) => {
  if (!e.target.closest('.dropdown-wrapper')) {
    openDropdownId.value = null;
  }
};

onMounted(() => {
  document.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown);
});

const toggleFilter = () => {
  router.get('/tareas', { completadas: props.mostrarCompletadas ? null : '1' }, { preserveState: true });
};

const completarTarea = (id) => {
  router.patch(route('tareas.completar', id), {}, {
    preserveScroll: true
  });
};

const openEdit = (tarea) => {
  selectedTarea.value = tarea;
  showEditModal.value = true;
};

const openView = (tarea) => {
  selectedTarea.value = tarea;
  showViewModal.value = true;
};

const openDelete = (tarea) => {
  selectedTarea.value = tarea;
  showDeleteModal.value = true;
};

const formatDate = (date) => {
  if (!date) return '';
  const dateStr = date.split(/[ T]/)[0];
  const [year, month, day] = dateStr.split('-');
  const d = new Date(year, month - 1, day);
  return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const estadoColor = (tarea) => {
  if (tarea.estadoTarea === 'Completado') return 'text-success';
  if (tarea.estadoTarea === 'En Progreso') return 'text-info';
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  
  const dateStr = tarea.fechaLimite.split(/[ T]/)[0];
  const [year, month, day] = dateStr.split('-');
  const limite = new Date(year, month - 1, day);
  
  if (limite < hoy) return 'text-danger';
  return 'text-warning';
};
</script>

<style>
.task-card {
  background-color: #1f1f35;
  border-radius: 20px;
  padding: 20px;
  height: 310px;
  position: relative;
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 10px;
  box-shadow: 0 4px 8px rgba(51, 0, 102, 0.6);
  transition: box-shadow 0.3s ease;
  padding-bottom: 60px;
}

.task-card:hover {
  box-shadow: 0 8px 16px rgba(153, 0, 255, 0.9);
}

.task-card h4 {
  color: #d34cf5;
  margin-bottom: 0.5rem;
  margin-top: 20px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.task-card p {
  color: #ffffff;
  margin-bottom: 0.5rem;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
}

.task-card .text-muted {
  color: #b8b8b8 !important;
}

.btn-add {
  background-color: #d34cf5;
  color: white;
  font-size: 2.2rem;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  border: none;
  line-height: 1;
}

.btn-add:hover {
  background-color: #b136d9;
  color: white;
}

.dropdown-wrapper {
  position: relative;
  z-index: 100;
}

.dropdown-toggle-custom {
  background-color: #a44fd1;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 2px 10px;
  font-size: 1.2rem;
  cursor: pointer;
}

.dropdown-toggle-custom:hover {
  background-color: #8a3fb8;
}

.custom-dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background-color: #2c2c2c;
  border: none;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
  min-width: 130px;
  padding: 5px 0;
  z-index: 2000;
}

.custom-dropdown-item {
  display: block;
  width: 100%;
  background: none;
  border: none;
  color: #ffffff;
  padding: 10px 20px;
  font-weight: 500;
  text-align: left;
  cursor: pointer;
  transition: background-color 0.2s ease;
  font-size: 0.9rem;
}

.custom-dropdown-item:hover {
  background-color: #444 !important;
  color: #fff;
}

.btn-outline-success {
  border-color: #28a745;
  color: #28a745;
}

.btn-outline-success:hover {
  background-color: #28a745;
  color: white;
}

.btn-outline-light:hover {
  background-color: #f8f9fa;
  color: #141421;
}

.btn-completar-rapido {
  position: absolute;
  top: 10px;
  left: 10px;
  background-color: rgba(40, 167, 69, 0.15);
  color: #28a745;
  border: 1px solid rgba(40, 167, 69, 0.4);
  border-radius: 50%;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  z-index: 100;
  padding: 0;
}

.btn-completar-rapido:hover {
  background-color: #28a745;
  color: white;
  transform: scale(1.15);
  box-shadow: 0 0 10px rgba(40, 167, 69, 0.8);
}
</style>
