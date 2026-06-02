<template>
  <div class="layout-container" :class="{ 'modal-open-blur': isModalOpen }">
    <header class="navbar-brand-container">
      <Link href="/" class="navbar-brand">
        <img src="/img/logo-cronos.png" alt="Logo" width="150px" />
      </Link>
    </header>

    <button 
      id="sidebarToggle" 
      aria-label="Toggle sidebar" 
      @click="toggleSidebar"
    >
      &#9776;
    </button>

    <nav id="sidebar" :class="{ 'closed': isSidebarClosed }" aria-label="Menú lateral">
      <h1>cronos</h1>
      <Link href="/perfiles" @click="closeSidebarOnMobile">Perfil</Link>
      <Link href="#" @click="closeSidebarOnMobile">Racha</Link>
      <Link href="/tareas" @click="closeSidebarOnMobile">Tareas del perfil</Link>
      <Link href="/pomodoro" @click="closeSidebarOnMobile">Sesión de Pomodoro</Link>
      <Link href="/dashboard" @click="closeSidebarOnMobile">Página principal</Link>
      <Link href="/profile" @click="closeSidebarOnMobile">Mi Cuenta</Link>
      <Link :href="route('logout')" method="post" as="button" class="logout-btn" @click="closeSidebarOnMobile">Cerrar sesión</Link>
    </nav>

    <main id="content" :class="{ 'expanded': isSidebarClosed }">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const isSidebarClosed = ref(true);
const isModalOpen = ref(false);

const toggleSidebar = () => {
  isSidebarClosed.value = !isSidebarClosed.value;
};

const closeSidebarOnMobile = () => {
  if (window.innerWidth < 768) {
    isSidebarClosed.value = true;
  }
};
</script>

<style scoped>
/* Transferencia exacta de tus estilos CSS a formato Scoped (solo afectan a este componente) */
.layout-container {
  background-color: #141421;
  color: white;
  min-height: 100vh;
  width: 100%;
  overflow-x: hidden;
  position: relative;
}

/* Efecto Blur cuando hay un modal abierto */
.layout-container.modal-open-blur #sidebar,
.layout-container.modal-open-blur #content,
.layout-container.modal-open-blur .navbar-brand-container {
  filter: blur(6px);
  pointer-events: none;
  user-select: none;
  transition: filter 0.3s ease;
}

h1 {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 30px;
  font-weight: bold;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.navbar-brand-container {
  padding: 15px 0 0 0;
}

.navbar-brand {
  margin-left: 90px;
  display: inline-block;
}

#sidebar {
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  width: 250px;
  background: linear-gradient(180deg, #b136d9, #1f1e31);
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  transition: transform 0.3s ease;
  z-index: 1040;
}

#sidebar.closed {
  transform: translateX(-250px);
}

#sidebar h1 {
  font-size: 28px;
  text-align: center;
  margin-bottom: 10px;
}

/* Estilos de los Links de Inertia (actúan como etiquetas 'a') */
#sidebar a {
  color: white;
  text-decoration: none;
  font-weight: 500;
  font-size: 1rem;
  padding: 8px 12px;
  border-radius: 6px;
  transition: background-color 0.2s;
}

#sidebar a:hover {
  background-color: rgba(255, 255, 255, 0.2);
}

.logout-btn {
  background: transparent;
  border: none;
  color: white;
  text-align: left;
  font-weight: 500;
  font-size: 1rem;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
  width: 100%;
  transition: background-color 0.2s;
}

.logout-btn:hover {
  background-color: rgba(255, 255, 255, 0.2);
}

#sidebarToggle {
  position: fixed;
  top: 15px;
  left: 15px;
  background: #b136d9;
  border: none;
  border-radius: 6px;
  width: 40px;
  height: 40px;
  color: white;
  font-weight: bold;
  font-size: 1.5rem;
  cursor: pointer;
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.3s;
}

#sidebarToggle:hover {
  background: #1f1e31;
}

#content {
  margin-left: 250px;
  padding: 20px;
  transition: margin-left 0.3s ease;
  position: relative;
  z-index: 1;
}

#content.expanded {
  margin-left: 0;
}

/* Media query original para dispositivos móviles */
@media (max-width: 768px) { 
  #sidebar {
    transform: translateX(-250px);
  }
  #sidebar.closed {
    transform: translateX(0);
  }
  #content {
    margin-left: 0;
  }
  .navbar-brand {
    margin-left: 70px;
  }
}
</style>