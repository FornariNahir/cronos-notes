<template>
  <AppLayout>
    <div class="container pt-4">
      
      <div class="d-flex align-items-center justify-content-center mb-5 gap-3">
        <h1 class="m-0 text-white">Administrar perfiles</h1>
        <button class="btn btn-add" aria-label="Agregar perfil" @click="openAddModal">
          +
        </button>
      </div>

      <div class="row justify-content-center g-4">
        <div class="col-auto" v-for="perfil in perfiles" :key="perfil.idPerfil">
          <div class="perfil-card">
            <h3 class="text-center text-truncate w-100 px-2" style="font-weight: bold; color: white;">{{ perfil.tituloPerfil }}</h3>
            <p class="text-center small px-2" style="font-size: 0.85rem; color: rgba(255, 255, 255, 0.75) !important;">{{ perfil.descripcionPerfil || 'Sin descripción' }}</p>
            
            <div class="d-flex align-items-center justify-content-center gap-2 mt-2">
              <Link 
                :href="route('perfiles.activar')" 
                method="post" 
                as="button" 
                type="button"
                :data="{ idPerfil: perfil.idPerfil }"
                class="btn-action"
              >
                Entrar
              </Link>

              <button class="btn-editar" @click="openEditModal(perfil)" aria-label="Editar">
                <img src="/img/editar.png" alt="Editar" style="width: 24px; height: 24px;" />
              </button>

              <button class="btn-kill" @click="openDeleteModal(perfil)" aria-label="Eliminar">
                <img src="/img/eliminar.png" alt="Eliminar" style="width: 24px; height: 24px;" />
              </button>
            </div>

          </div>
        </div>

        <div class="col-12 text-center text-white mt-4" v-if="perfiles.length === 0">
          <p class="text-muted">No tienes perfiles creados. ¡Crea uno con el botón (+)!</p>
        </div>
      </div>

    </div>

    <AddProfileModal :isOpen="modalAddOpen" @close="modalAddOpen = false" />
    <EditProfileModal :isOpen="modalEditOpen" :perfil="selectedPerfil" @close="modalEditOpen = false" />
    <DeleteProfileModal :isOpen="modalDeleteOpen" :perfil="selectedPerfil" @close="modalDeleteOpen = false" />

  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AddProfileModal from '@/Components/AddProfileModal.vue';
import EditProfileModal from '@/Components/EditProfileModal.vue';
import DeleteProfileModal from '@/Components/DeleteProfileModal.vue';

defineProps({
  perfiles: Array
});

// Estados de control para modales
const modalAddOpen = ref(false);
const modalEditOpen = ref(false);
const modalDeleteOpen = ref(false);
const selectedPerfil = ref(null);

const openAddModal = () => {
  modalAddOpen.value = true;
};

const openEditModal = (perfil) => {
  selectedPerfil.value = { ...perfil };
  modalEditOpen.value = true;
};

const openDeleteModal = (perfil) => {
  selectedPerfil.value = perfil;
  modalDeleteOpen.value = true;
};
</script>