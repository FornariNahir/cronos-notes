<template>
  <AppLayout>
    <div class="notes-page">
      <div class="header-section">
        <div>
          <h1 class="page-title">Mis apuntes</h1>
          <p class="page-subtitle">Revisá, editá o grabá tus apuntes de clase en <b>{{ perfilActivo.tituloPerfil }}</b></p>
        </div>
        <Link :href="route('apuntes.create')" class="btn-create">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Nuevo apunte
        </Link>
      </div>

      <!-- Grid de Apuntes -->
      <div class="notes-grid">
        <div v-for="apunte in apuntes" :key="apunte.idApunte" class="note-card">
          <div class="note-card-content" @click="editNote(apunte.idApunte)">
            <h3 class="note-title">{{ apunte.tituloApunte }}</h3>
            <span class="note-date">📅 {{ formatDate(apunte.fechaCreacion) }}</span>
            <p class="note-preview">{{ stripHtml(apunte.contenidoApunte) || 'Sin contenido en esta nota...' }}</p>
          </div>
          <div class="note-card-actions">
            <button class="btn-action edit" @click="editNote(apunte.idApunte)" title="Editar">
              Editar
            </button>
            <button class="btn-action delete" @click="deleteNote(apunte.idApunte)" title="Eliminar">
              Eliminar
            </button>
          </div>
        </div>

        <!-- Estado Vacío -->
        <div class="empty-state" v-if="apuntes.length === 0" @click="createNote">
          <div class="empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
            </svg>
          </div>
          <h3>Crea tu primer apunte</h3>
          <p>Graba audios de clase y escribe notas de estudio en este perfil.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  apuntes: Array,
  perfilActivo: Object
});

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const stripHtml = (html) => {
  if (!html) return '';
  // Elimina etiquetas HTML y limita a 100 caracteres para la vista previa
  const clean = html.replace(/<[^>]*>?/gm, ' ').replace(/&nbsp;/g, ' ').trim();
  return clean.substring(0, 100) + (clean.length > 100 ? '...' : '');
};

const editNote = (id) => {
  router.visit(route('apuntes.edit', id));
};

const createNote = () => {
  router.visit(route('apuntes.create'));
};

const deleteNote = (id) => {
  if (confirm('¿Estás seguro de que deseas eliminar este apunte?')) {
    router.delete(route('apuntes.destroy', id), {
      preserveScroll: true
    });
  }
};
</script>

<style scoped>
.notes-page {
  background-color: #e8e5e1;
  min-height: 100vh;
  padding: 40px;
  margin: -20px; /* Compensa el padding de AppLayout */
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  color: #333;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  max-width: 1000px;
  margin-left: auto;
  margin-right: auto;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #333;
  text-align: left;
}

.page-subtitle {
  font-size: 14px;
  color: #666;
  margin-top: 4px;
  text-align: left;
}

.btn-create {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background-color: #612c2d;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: background-color 0.2s;
}

.btn-create:hover {
  background-color: #723f3f;
}

.btn-create svg {
  width: 18px;
  height: 18px;
}

/* Notes Grid */
.notes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 24px;
  max-width: 1000px;
  margin: 0 auto;
}

/* Note Card */
.note-card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e5e5;
  box-shadow: 0 4px 6px rgba(0,0,0,0.02);
  display: flex;
  flex-direction: column;
  height: 200px;
  cursor: pointer;
  transition: box-shadow 0.2s, transform 0.2s;
}

.note-card:hover {
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.note-card-content {
  padding: 20px;
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  text-align: left;
}

.note-title {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.note-date {
  font-size: 11px;
  color: #8b4c4c;
  margin-bottom: 12px;
}

.note-preview {
  font-size: 13px;
  color: #666;
  line-height: 1.5;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
}

.note-card-actions {
  display: flex;
  border-top: 1px solid #e5e5e5;
  background-color: #faf8f8;
  border-radius: 0 0 12px 12px;
}

.btn-action {
  flex: 1;
  border: none;
  background: transparent;
  padding: 10px 0;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s, color 0.2s;
}

.btn-action.edit {
  color: #666;
  border-right: 1px solid #e5e5e5;
}

.btn-action.edit:hover {
  background-color: #f3f0ed;
  color: #333;
}

.btn-action.delete {
  color: #d9534f;
}

.btn-action.delete:hover {
  background-color: #fee2e2;
  color: #d9534f;
}

/* Empty State */
.empty-state {
  grid-column: 1 / -1;
  background: #fff;
  border-radius: 12px;
  border: 1px dashed #bbb;
  padding: 48px;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.2s, background-color 0.2s;
}

.empty-state:hover {
  border-color: #612c2d;
  background-color: #faf8f8;
}

.empty-icon {
  width: 64px;
  height: 64px;
  margin: 0 auto 16px auto;
  color: #bbb;
}

.empty-icon svg {
  width: 100%;
  height: 100%;
}

.empty-state h3 {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
}

.empty-state p {
  font-size: 14px;
  color: #666;
}
</style>
