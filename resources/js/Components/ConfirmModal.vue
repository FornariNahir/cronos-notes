<script setup>
import { computed } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Confirmación',
  },
  message: {
    type: String,
    default: '',
  },
  confirmText: {
    type: String,
    default: 'Aceptar',
  },
  cancelText: {
    type: String,
    default: 'Cancelar',
  },
  isDanger: {
    type: Boolean,
    default: false,
  },
  maxWidth: {
    type: String,
    default: 'sm',
  },
});

const emit = defineEmits(['close', 'confirm']);

const close = () => {
  emit('close');
};

const confirm = () => {
  emit('confirm');
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
    <div v-show="show" class="confirm-modal-container" scroll-region>
      <transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-show="show" class="confirm-backdrop" @click="close"></div>
      </transition>

      <transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
      >
        <div v-show="show" class="confirm-card-wrapper" :class="maxWidthClass">
          <div class="confirm-card">
            <div class="confirm-header">
              <div class="confirm-icon" :class="{ 'text-danger-icon': isDanger }">
                <i class="bi bi-exclamation-triangle-fill"></i>
              </div>
              <h3 class="confirm-title">{{ title }}</h3>
            </div>
            
            <div class="confirm-body">
              <p class="confirm-message">{{ message }}</p>
            </div>

            <div class="confirm-footer">
              <button type="button" @click="close" class="btn-secondary">
                {{ cancelText }}
              </button>
              <button type="button" @click="confirm" class="btn-confirm" :class="{ 'btn-danger-confirm': isDanger }">
                {{ confirmText }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </transition>
</template>

<style scoped>
.confirm-modal-container {
  position: fixed;
  inset: 0;
  overflow-y: auto;
  padding: 1rem;
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.confirm-backdrop {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(2px);
  transition: all 0.3s;
}

.confirm-card-wrapper {
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

.confirm-card {
  background-color: #ffffff;
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  padding: 24px;
}

.confirm-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.confirm-icon {
  font-size: 24px;
  color: #c48c3f;
  display: flex;
  align-items: center;
  justify-content: center;
}

.confirm-icon.text-danger-icon {
  color: #dc3545;
}

.confirm-title {
  font-size: 18px;
  font-weight: 700;
  color: #333333;
  margin: 0;
}

.confirm-body {
  margin-bottom: 24px;
  text-align: left;
}

.confirm-message {
  font-size: 15px;
  color: #555555;
  line-height: 1.5;
  margin: 0;
}

.confirm-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.btn-secondary {
  background-color: #f1f3f5;
  color: #495057;
  border: 1px solid #dee2e6;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background-color: #e9ecef;
}

.btn-confirm {
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

.btn-confirm:hover {
  background-color: #612c2d;
}

.btn-danger-confirm {
  background-color: #dc3545;
}

.btn-danger-confirm:hover {
  background-color: #bd2130;
}

.btn-confirm:active, .btn-secondary:active {
  transform: scale(0.98);
}
</style>

<style>
/* Estilos en modo oscuro globales para ConfirmModal */
body.cn-body-dark .confirm-card {
  background-color: #4d2323 !important;
  border: 1px solid #7b413f !important;
  color: #ffffff !important;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5) !important;
}

body.cn-body-dark .confirm-title {
  color: #ffffff !important;
}

body.cn-body-dark .confirm-message {
  color: #fcd5b8 !important;
}

body.cn-body-dark .confirm-icon {
  color: #fcd5b8 !important;
}

body.cn-body-dark .confirm-icon.text-danger-icon {
  color: #dc3545 !important;
}

body.cn-body-dark .confirm-card .btn-confirm {
  background-color: #f4be95 !important;
  color: #612c2d !important;
}

body.cn-body-dark .confirm-card .btn-confirm:hover {
  background-color: #fcd5b8 !important;
  color: #612c2d !important;
}

body.cn-body-dark .confirm-card .btn-confirm.btn-danger-confirm {
  background-color: #dc3545 !important;
  color: #ffffff !important;
}

body.cn-body-dark .confirm-card .btn-confirm.btn-danger-confirm:hover {
  background-color: #bd2130 !important;
}

body.cn-body-dark .confirm-card .btn-secondary {
  background-color: #612c2d !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .confirm-card .btn-secondary:hover {
  background-color: #7b413f !important;
  color: #ffffff !important;
}
</style>
