<script setup>
import { computed } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Aviso',
  },
  message: {
    type: String,
    default: '',
  },
  maxWidth: {
    type: String,
    default: 'sm',
  },
});

const emit = defineEmits(['close']);

const close = () => {
  emit('close');
};

const maxWidthClass = computed(() => {
  return {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
  }[props.maxWidth];
});
</script>

<template>
  <transition leave-active-class="duration-200">
    <div v-show="show" class="alert-modal-container" scroll-region>
      <transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-show="show" class="alert-backdrop" @click="close"></div>
      </transition>

      <transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
      >
        <div v-show="show" class="alert-card-wrapper" :class="maxWidthClass">
          <div class="alert-card">
            <div class="alert-header">
              <div class="alert-icon">
                <i class="bi bi-info-circle-fill"></i>
              </div>
              <h3 class="alert-title">{{ title }}</h3>
            </div>
            
            <div class="alert-body">
              <p class="alert-message">{{ message }}</p>
            </div>

            <div class="alert-footer">
              <button @click="close" class="btn-primary" autofocus>
                Aceptar
              </button>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </transition>
</template>

<style scoped>
.alert-modal-container {
  position: fixed;
  inset: 0;
  overflow-y: auto;
  padding: 1rem;
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.alert-backdrop {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(2px);
  transition: all 0.3s;
}

.alert-card-wrapper {
  margin: auto;
  width: 100%;
  transition: all 0.3s;
  position: relative;
  z-index: 10001;
}

.sm\:max-w-sm { max-width: 24rem; }
.sm\:max-w-md { max-width: 28rem; }
.sm\:max-w-lg { max-width: 32rem; }
.sm\:max-w-xl { max-width: 36rem; }
.sm\:max-w-2xl { max-width: 42rem; }

.alert-card {
  background-color: #ffffff;
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  padding: 24px;
}

.alert-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.alert-icon {
  font-size: 24px;
  color: #7b413f;
  display: flex;
  align-items: center;
  justify-content: center;
}

.alert-title {
  font-size: 18px;
  font-weight: 700;
  color: #333333;
  margin: 0;
}

.alert-body {
  margin-bottom: 24px;
}

.alert-message {
  font-size: 15px;
  color: #555555;
  line-height: 1.5;
  margin: 0;
}

.alert-footer {
  display: flex;
  justify-content: flex-end;
}

.btn-primary {
  background-color: #7b413f;
  color: #ffffff;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.1s ease;
}

.btn-primary:hover {
  background-color: #612c2d;
}

.btn-primary:active {
  transform: scale(0.98);
}
</style>

<style>
/* Estilos en modo oscuro globales para AlertModal */
body.cn-body-dark .alert-card {
  background-color: #4d2323 !important;
  border: 1px solid #7b413f !important;
  color: #ffffff !important;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5) !important;
}

body.cn-body-dark .alert-title {
  color: #ffffff !important;
}

body.cn-body-dark .alert-message {
  color: #fcd5b8 !important;
}

body.cn-body-dark .alert-icon {
  color: #fcd5b8 !important;
}

body.cn-body-dark .alert-card .btn-primary {
  background-color: #f4be95 !important;
  color: #612c2d !important;
}

body.cn-body-dark .alert-card .btn-primary:hover {
  background-color: #fcd5b8 !important;
  color: #612c2d !important;
}
</style>

