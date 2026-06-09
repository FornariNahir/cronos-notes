<template>
  <div class="layout-container" :class="{ 'zen-mode': isZenMode }">
    
    <button 
      id="sidebarToggle" 
      aria-label="Toggle sidebar" 
      @click="toggleSidebar"
    >
      &#9776;
    </button>

    <aside class="sidebar" :class="{ 'closed': isSidebarClosed }">
      <div class="logo-area">
        <div class="logo-icon">
          <img src="/img/logo-cronos.png" alt="Logo">
        </div>
      </div>

      <ul class="nav-menu">
        <li>
          <Link :href="route('dashboard')" class="nav-item" :class="{ active: route().current('dashboard') }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Pagina principal
          </Link>
        </li>
        <li>
          <Link href="/gestion-perfil" class="nav-item" :class="{ active: $page.url === '/gestion-perfil' }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="7" height="7"/>
              <rect x="14" y="3" width="7" height="7"/>
              <rect x="14" y="14" width="7" height="7"/>
              <rect x="3" y="14" width="7" height="7"/>
            </svg>
            Mis perfiles
          </Link>
        </li>
        <li>
          <Link href="/gestion-tareas" class="nav-item" :class="{ active: $page.url === '/gestion-tareas' }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <line x1="16" y1="13" x2="8" y2="13"/>
              <line x1="16" y1="17" x2="8" y2="17"/>
              <polyline points="10 9 9 9 8 9"/>
            </svg>
            Mis tareas
          </Link>
        </li>
        <li>
          <Link href="/dashboard#estadisticas" class="nav-item" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="20" x2="18" y2="10"/>
              <line x1="12" y1="20" x2="12" y2="4"/>
              <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            Estadisticas
          </Link>
        </li>
        <li>
          <Link href="/calendario" class="nav-item" :class="{ active: $page.url === '/calendario' }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Calendario
          </Link>
        </li>
        <li>
          <Link href="/perfil-usuario" class="nav-item" :class="{ active: $page.url === '/perfil-usuario' }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
            Mi perfil
          </Link>
        </li>
        <li>

          <button @click="toggleMode" class="nav-item nav-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="3"/>
              <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
            </svg>
            Cambiar Modo
          </button>
        </li>

      </ul>

      <div class="nav-section-title">Pomodoro</div>
      <ul class="nav-menu">
        <li>
          <Link :href="route('pomodoro.index')" class="nav-item" :class="{ active: route().current('pomodoro.index') }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
            Iniciar una sesión rápida
          </Link>
        </li>
        <li>
          <Link :href="route('apuntes.index')" class="nav-item" :class="{ active: route().current('apuntes.index') || route().current('apuntes.create') || route().current('apuntes.edit') }" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
            </svg>
            Mis apuntes
          </Link>
        </li>
        <li class="logout-li">
          <Link :href="route('logout')" method="post" as="button" class="nav-item nav-btn logout-btn" @click="closeSidebarOnMobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Cerrar sesión
          </Link>
        </li>
      </ul>
    </aside>

    <!-- Contenido principal de cada página -->
    <main id="content" :class="{ 'expanded': isSidebarClosed }">
      <slot />
    </main>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const isSidebarClosed = ref(true);
const isZenMode = ref(false);

const toggleSidebar = () => {
  isSidebarClosed.value = !isSidebarClosed.value;
};

const closeSidebarOnMobile = () => {
  if (window.innerWidth < 768) {
    isSidebarClosed.value = true;
  }
};

const toggleMode = () => {
  isZenMode.value = !isZenMode.value;
  document.body.classList.toggle('light-mode');
};
</script>

<style scoped>
.layout-container {
  background-color: #141421;
  color: white;
  min-height: 100vh;
  width: 100%;
  overflow-x: hidden;
  position: relative;
}

.sidebar {
  width: 220px;
  background-color: #fff;
  padding: 24px 16px;
  display: flex;
  flex-direction: column;
  border-right: 1px solid #e5e5e5;
  position: fixed;
  height: 100vh;
  overflow-y: auto;
  left: 0;
  top: 0;
  transition: transform 0.3s ease;
  z-index: 1040;
}

.logo-area {
  display: flex;
  align-items: center;
  margin-bottom: 30px;
  padding: 0 8px;
}

.logo-icon img {
  width: 130px;
  height: auto;
  object-fit: contain;
}

.nav-menu {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 0;
  margin: 0;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 8px;
  color: #666;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  font-family: inherit;
  font-size: 1rem;
}

.nav-btn {
  background: transparent;
  border: none;
  width: 100%;
  text-align: left;
}

.nav-item:hover {
  background-color: #f5f5f5;
  color: #333;
}

.nav-item.active {
  background-color: #612c2d;
  color: #fff;
}

.nav-item svg {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.nav-section-title {
  font-size: 13px;
  font-weight: 600;
  color: #333;
  margin-top: 32px;
  margin-bottom: 12px;
  padding: 0 16px;
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
  top: 15px;
  left: 15px;
  background: #612c2d;
  border: none;
  border-radius: 6px;
  width: 40px;
  height: 40px;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  z-index: 1100;
  display: none; /* Oculto en pantallas grandes */
  align-items: center;
  justify-content: center;
}

#content {
  margin-left: 220px;
  padding: 20px;
  transition: margin-left 0.3s ease;
}

@media (max-width: 768px) {
  #sidebarToggle {
    display: flex;
  }
  
  .sidebar {
    transform: translateX(-220px);
  }
  
  .sidebar.closed {
    transform: translateX(0);
  }
  
  #content {
    margin-left: 0;
    padding-top: 70px; 
  }
}
</style>