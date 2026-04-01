<template>
  <article
    class="project-card"
    :class="{ 'project-card-featured': featured }"
    role="button"
    tabindex="0"
    @click="handleSelect"
    @keydown.enter.prevent="handleSelect"
    @keydown.space.prevent="handleSelect"
  >
    <div class="card-thumb">
      <span v-if="!project.splash_screen_path" class="card-thumb-empty">{{ t('common.no_preview') }}</span>
      <img
        v-else
        :src="splashUrl"
        :alt="project.title"
        class="card-thumb-img"
        loading="lazy"
        decoding="async"
      />
    </div>

    <div class="card-body">
      <h3 class="card-title">{{ project.title }}</h3>
      <p class="card-desc">{{ project.description || t('common.no_description') }}</p>

      <div class="card-meta">
        <div class="card-meta-left">
          <div class="card-rating" :aria-label="t('carousel.rating_aria')">
            <span
              v-for="star in 5"
              :key="star"
              :class="star <= Math.round(Number(project.rating || 0)) ? 'star-filled' : 'star-empty'"
            >★</span>
            <span class="rating-num">{{ Number(project.rating || 0).toFixed(1) }}</span>
          </div>
          <div class="card-views">
            <i class="pi pi-eye view-icon" aria-hidden="true"></i>
            <span class="view-num">{{ project.views || 0 }}</span>
          </div>
        </div>
        <span class="card-type">{{ typeLabel }}</span>
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  project: { type: Object, required: true },
  featured: { type: Boolean, default: false }
})

const emit = defineEmits(['select'])
const { t } = useI18n()

const API_URL = import.meta.env.VITE_API_URL
const splashUrl = computed(() => {
  const path = props.project.splash_screen_path
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${API_URL}/storage/${path}`
})

const typeLabel = computed(() => {
  const valid = ['game', 'web_app', 'mobile_app', 'library', 'other']
  const type = props.project.type
  return valid.includes(type) ? t(`project_types.${type}`) : t('project_types.other')
})

function handleSelect() {
  emit('select', props.project)
}
</script>

<style scoped>
.project-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  overflow: hidden;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  transition: background 0.15s, border-color 0.15s, transform 0.15s, box-shadow 0.2s;
  min-height: 320px;
}
.project-card:hover {
  background: var(--color-elevated);
  border-color: var(--color-accent);
  transform: translateY(-2px);
}
.project-card-featured {
  box-shadow: 0 10px 24px rgba(0,0,0,0.35);
}

.card-thumb {
  width: 100%;
  aspect-ratio: 16/9;
  background: var(--color-surface-deep);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.card-thumb-empty { font-size: 0.75rem; color: var(--color-text-subtle); }
.card-thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}
.project-card:hover .card-thumb-img {
  transform: scale(1.03);
}

.card-body {
  padding: 14px 16px 16px;
  display: flex;
  flex-direction: column;
  flex: 1;
}
.card-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text);
  line-height: 1.3;
  margin-bottom: 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.project-card:hover .card-title { color: var(--color-text-strong); }

.card-desc {
  font-size: 0.82rem;
  color: var(--color-text-muted);
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 12px;
  flex: 1;
}

.card-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border-top: 1px solid var(--color-border);
  padding-top: 10px;
}
.card-meta-left { display: flex; align-items: center; gap: 12px; }
.card-rating { display: flex; align-items: center; gap: 2px; }
.card-views { display: flex; align-items: center; gap: 4px; }
.view-icon { font-size: 0.8rem; color: var(--color-text-muted); position: relative; top: 1px; }
.view-num { font-size: 0.8rem; color: var(--color-text-muted); font-weight: 600; }
.star-filled { color: var(--color-warning); font-size: 0.75rem; }
.star-empty  { color: var(--color-border); font-size: 0.75rem; }
.rating-num  { font-size: 0.8rem; color: var(--color-text-muted); margin-left: 4px; font-weight: 600; }

.card-type {
  font-size: 0.72rem;
  padding: 2px 8px;
  border-radius: 2px;
  background: rgba(var(--color-accent-rgb), 0.12);
  border: 1px solid rgba(var(--color-accent-rgb), 0.3);
  color: var(--color-accent);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  font-weight: 600;
}
</style>
