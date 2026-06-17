<script setup>
import { Link, router } from '@inertiajs/vue3';

defineProps({
  perfiles: Array
});

const selectProfile = (idPerfil) => {
  router.post(route('perfiles.activar'), { idPerfil }, {
    onSuccess: () => {
      window.location.reload();
    }
  });
};
</script>

<template>
  <div class="profile-selector-container">
    <!-- Formas decorativas (Inspiradas en el login) -->
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    <div class="shape shape-4"></div>

    <div class="auth-card">
      <div class="illustration-panel">
        <img src="/imagenes/login.png" alt="Ilustración Selección de Perfil">
      </div>

      <div class="form-panel">
        <div class="form-header">
          <h2>¡Hola!</h2>
          <p>Selecciona un perfil para continuar</p>
        </div>

        <div class="profiles-list">
          <div v-if="perfiles.length === 0" class="no-profiles">
            <p>No tienes ningún perfil creado.</p>
            <Link :href="route('gestion-perfil')" class="btn-primary mt-3">Crear un perfil</Link>
          </div>
          <button
            v-else
            v-for="perfil in perfiles"
            :key="perfil.idPerfil"
            @click="selectProfile(perfil.idPerfil)"
            class="profile-option"
          >
            <div class="profile-icon">
              <i :class="perfil.iconoPerfil || 'bi bi-folder'"></i>
            </div>
            <div class="profile-info">
              <h4>{{ perfil.tituloPerfil }}</h4>
            </div>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.profile-selector-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: #e8e5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  overflow: hidden;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

/* Formas decorativas */
.shape {
  position: absolute;
  border-radius: 50%;
  z-index: 0;
}

.shape-1 {
  width: 350px;
  height: 350px;
  background-color: #a67c6d;
  opacity: 0.5;
  bottom: -100px;
  left: -100px;
}

.shape-2 {
  width: 200px;
  height: 200px;
  background-color: #a67c6d;
  bottom: -50px;
  left: 150px;
}

.shape-3 {
  width: 300px;
  height: 300px;
  background-color: #8b5a4b;
  opacity: 0.8;
  top: -150px;
  right: -50px;
}

.shape-4 {
  width: 150px;
  height: 150px;
  background-color: #8b5a4b;
  top: 100px;
  right: 150px;
}

/* Tarjeta principal */
.auth-card {
  position: relative;
  z-index: 1;
  display: flex;
  background-color: #ffffff;
  border-radius: 20px;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  width: 900px;
  max-width: 95%;
  min-height: 500px;
  animation: fadeIn 0.6s ease-out;
}

/* Panel de ilustración */
.illustration-panel {
  width: 420px;
  background-color: #7b413f;
  margin: 15px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 30px;
}

.illustration-panel img {
  width: 100%;
  height: auto;
  max-width: 350px;
}

/* Panel del formulario */
.form-panel {
  flex: 1;
  padding: 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.form-header {
  text-align: center;
  margin-bottom: 30px;
}

.form-header h2 {
  font-size: 28px;
  color: #333;
  margin-bottom: 8px;
  font-weight: 700;
}

.form-header p {
  color: #666;
  font-size: 15px;
}

.profiles-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: 350px;
  overflow-y: auto;
  padding-right: 5px;
}

.profiles-list::-webkit-scrollbar {
  width: 6px;
}

.profiles-list::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.profiles-list::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 4px;
}

.profile-option {
  display: flex;
  align-items: center;
  padding: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fafafa;
  cursor: pointer;
  transition: all 0.2s ease;
  width: 100%;
  text-align: left;
}

.profile-option:hover {
  background: #fff;
  border-color: #7b413f;
  box-shadow: 0 4px 12px rgba(123, 65, 63, 0.1);
  transform: translateY(-2px);
}

.profile-icon {
  font-size: 24px;
  color: #7b413f;
  margin-right: 16px;
}

.profile-info h4 {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.no-profiles {
  text-align: center;
  padding: 20px;
}

.btn-primary {
  display: inline-block;
  padding: 12px 24px;
  background: #7b413f;
  color: #fff;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
}

.btn-primary:hover {
  background: #612c2d;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .auth-card {
    flex-direction: column;
  }

  .illustration-panel {
    width: auto;
    min-height: 250px;
    order: -1;
    margin: 15px 15px 0 15px;
  }

  .form-panel {
    padding: 30px 25px;
  }
}
</style>
