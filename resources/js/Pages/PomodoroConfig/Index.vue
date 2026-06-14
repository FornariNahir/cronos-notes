<template>
  <Head title="Configuración Pomodoro" />
  <AppLayout>
    <div class="config-page">
      <div class="d-flex justify-content-between align-items-center mb-4" style="max-width: 900px; margin: auto;">
        <h2 class="fw-bold" style="color: #fff;">Configuración Pomodoro</h2>
        <button class="btn btn-add" @click="showAddModal = true">+</button>
      </div>

      <div v-if="configs.length === 0" class="text-center" style="max-width: 900px; margin: auto;">
        <div class="alert alert-info" style="background-color: #1f1f35; border: 1px solid #a44fd1; color: #fff;">
          No tienes configuraciones guardadas.
          <a href="#" style="color: #d34cf5;" @click.prevent="showAddModal = true">¡Crea una!</a>
        </div>
      </div>

      <div class="config-grid">
        <div v-for="config in configs" :key="config.idConfiguracionPomodoro" class="config-card">
          <div class="dropdown-wrapper" style="position: absolute; top: 10px; right: 10px;">
            <button class="btn btn-sm dropdown-toggle-custom" @click.stop="toggleDropdown(config.idConfiguracionPomodoro)">
              &#x22EE;
            </button>
            <div v-if="openDropdownId === config.idConfiguracionPomodoro" class="custom-dropdown-menu" @click.stop>
              <button class="custom-dropdown-item" @click="openEdit(config)">Editar</button>
              <button class="custom-dropdown-item text-danger" @click="openDelete(config)">Eliminar</button>
            </div>
          </div>

          <h4>Configuración #{{ config.idConfiguracionPomodoro }}</h4>
          <div class="config-details">
            <div class="config-item">
              <span class="config-label">Sesión</span>
              <span class="config-value">{{ config.duracionSesion }} min</span>
            </div>
            <div class="config-item">
              <span class="config-label">Descanso corto</span>
              <span class="config-value">{{ config.duracionDescansoCorto }} min</span>
            </div>
            <div class="config-item">
              <span class="config-label">Descanso largo</span>
              <span class="config-value">{{ config.duracionDescansoLargo }} min</span>
            </div>
            <div class="config-item">
              <span class="config-label">Ciclos antes de largo</span>
              <span class="config-value">{{ config.sesionesPrevioDescansoLargo }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <AddConfigModal :isOpen="showAddModal" @close="showAddModal = false" />
    <EditConfigModal :isOpen="showEditModal" :config="selectedConfig" @close="showEditModal = false" />
    <DeleteConfigModal :isOpen="showDeleteModal" :config="selectedConfig" @close="showDeleteModal = false" />
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AddConfigModal from '@/Components/AddConfigModal.vue';
import EditConfigModal from '@/Components/EditConfigModal.vue';
import DeleteConfigModal from '@/Components/DeleteConfigModal.vue';

const props = defineProps({
  configs: Array
});

const showAddModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const selectedConfig = ref(null);
const openDropdownId = ref(null);

const toggleDropdown = (id) => {
  openDropdownId.value = openDropdownId.value === id ? null : id;
};

const closeDropdown = (e) => {
  if (!e.target.closest('.dropdown-wrapper')) {
    openDropdownId.value = null;
  }
};

const openEdit = (config) => {
  selectedConfig.value = config;
  showEditModal.value = true;
  openDropdownId.value = null;
};

const openDelete = (config) => {
  selectedConfig.value = config;
  showDeleteModal.value = true;
  openDropdownId.value = null;
};

onMounted(() => document.addEventListener('click', closeDropdown));
onUnmounted(() => document.removeEventListener('click', closeDropdown));
</script>

<style scoped>
.config-page {
  max-width: 1000px;
  margin: auto;
  padding: 20px;
}

.config-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.config-card {
  background-color: #1f1f35;
  border-radius: 20px;
  padding: 25px;
  position: relative;
  box-shadow: 0 4px 8px rgba(51, 0, 102, 0.6);
  transition: box-shadow 0.3s ease;
}

.config-card:hover {
  box-shadow: 0 8px 16px rgba(153, 0, 255, 0.9);
}

.config-card h4 {
  color: #d34cf5;
  margin-bottom: 20px;
  padding-right: 30px;
}

.config-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.config-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.config-label {
  color: #b8b8b8;
  font-size: 0.9rem;
}

.config-value {
  color: #fff;
  font-weight: 600;
  font-size: 1rem;
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
  border: none;
  line-height: 1;
}

.btn-add:hover {
  background-color: #b136d9;
  color: white;
}

.dropdown-wrapper { position: relative; z-index: 100; }

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
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
  min-width: 120px;
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
  font-size: 0.9rem;
}

.custom-dropdown-item:hover {
  background-color: #444 !important;
}
</style>
