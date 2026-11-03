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
      <header class="top-bar">
          <div class="search-container">
          </div>
          <div class="top-bar-actions">
              <button class="icon-button" @click="toggleMode" title="Cambiar Modo">
                  <i v-if="isDarkMode" class="bi bi-sun-fill text-warning" style="font-size: 1.25rem;"></i>
                  <i v-else class="bi bi-moon-fill" style="font-size: 1.25rem;"></i>
              </button>
              <div class="settings-dropdown-container" ref="dropdownRef">
                  <button class="avatar" @click="toggleDropdown" aria-label="Menú de usuario">
                      {{ $page.props.auth.user?.nombre?.charAt(0).toUpperCase() || $page.props.auth.user?.name?.charAt(0).toUpperCase() || 'A' }}
                  </button>
                  <div v-if="isSettingsDropdownOpen" class="settings-dropdown">
                      <Link :href="route('perfil-usuario')" class="dropdown-item" @click="closeDropdown">Mis Datos</Link>
                      <Link :href="route('logout')" method="post" as="button" class="dropdown-item w-100 text-start" type="button" @click="closeDropdown">Cerrar Sesión</Link>
                  </div>
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
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import MiniTimerFlotante from '@/Components/Pomodoro/MiniTimerFlotante.vue';
import { usePomodoroTimer } from '@/Composables/usePomodoroTimer';

const isSidebarClosed = ref(window.innerWidth <= 768);
const isZenMode = ref(false);
const isSettingsDropdownOpen = ref(false);
const dropdownRef = ref(null);
const { isGlobalDarkMode: isDarkMode, setGlobalDarkMode, syncGlobalDarkModeFromStorage } = usePomodoroTimer();

const toggleDropdown = () => {
  isSettingsDropdownOpen.value = !isSettingsDropdownOpen.value;
};

const closeDropdown = () => {
  isSettingsDropdownOpen.value = false;
};

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isSettingsDropdownOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  
  syncGlobalDarkModeFromStorage();
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
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
  padding-left: 64px;
}

@media (max-width: 768px) {
  #content {
    margin-left: 0 !important;
    padding-top: 70px;
  }

  .top-bar {
    margin-top: -70px;
    padding-left: 64px;
    border-radius: 0;
  }

  .search-container {
    display: none;
  }
  
  .top-bar-actions {
    margin-left: auto;
  }
}

/* Top Bar Styles */
.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  background-color: #fff;
  border-radius: 0;
  margin: -20px -20px 24px -20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  border-bottom: 1px solid transparent; /* Evita desplazamiento de 1px entre modos */
}

.search-container {
  flex: 1;
  max-width: 400px;
}

.search-input {
  width: 100%;
  padding: 10px 16px;
  border-radius: 20px;
  border: 1px solid #e5e5e5;
  background-color: #f8f9fa;
  font-size: 14px;
  color: #333;
  outline: none;
  transition: all 0.2s ease;
}

.search-input:focus {
  border-color: #612c2d;
  background-color: #fff;
  box-shadow: 0 0 0 2px rgba(97, 44, 45, 0.1);
}

.top-bar-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

.icon-button {
  background: transparent;
  border: none;
  color: #666;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  border-radius: 50%;
  transition: background-color 0.2s ease, color 0.2s ease;
}

.icon-button:hover {
  background-color: #f5f5f5;
  color: #333;
}

.icon-button svg {
  width: 20px;
  height: 20px;
}

.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background-color: #612c2d;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  border: none;
  padding: 0;
}

.settings-dropdown-container {
  position: relative;
}

.settings-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  min-width: 150px;
  z-index: 1000;
  overflow: hidden;
  border: 1px solid #e5e5e5;
}

.dropdown-item {
  display: block;
  width: 100%;
  text-align: left;
  padding: 10px 16px;
  background: none;
  border: none;
  font-size: 14px;
  color: #333;
  cursor: pointer;
  text-decoration: none;
  transition: background-color 0.2s ease;
}

.dropdown-item:hover {
  background-color: #f5f5f5;
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

body.cn-body-dark .top-bar {
  background-color: #4d2323 !important;
  border-bottom: 1px solid #7b413f !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3) !important;
}

body.cn-body-dark .search-input {
  background-color: #f3f4f6 !important;
  border-color: transparent !important;
  color: #333333 !important;
}

body.cn-body-dark .search-input::placeholder {
  color: #666666 !important;
  opacity: 0.8;
}

body.cn-body-dark .search-input:focus {
  border-color: #f4be95 !important;
  background-color: #ffffff !important;
  color: #1a1a1a !important;
  box-shadow: 0 0 0 2px rgba(244, 190, 149, 0.25) !important;
}

body.cn-body-dark .icon-button {
  color: #ffffff !important;
}

body.cn-body-dark .icon-button:hover {
  background-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .avatar {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
}

body.cn-body-dark .settings-dropdown {
  background-color: #4d2323 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .dropdown-item {
  color: #ffffff !important;
}

body.cn-body-dark .dropdown-item:hover {
  background-color: #7b413f !important;
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