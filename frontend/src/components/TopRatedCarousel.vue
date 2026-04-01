<template>
  <section class="top-rated">
    <div class="top-rated-header">
      <div>
        <h2 class="top-rated-title">{{ t('carousel.title') }}</h2>
        <p class="top-rated-sub">{{ t('carousel.subtitle') }}</p>
      </div>
    </div>

    <div
      class="carousel"
      :style="{ '--items-per-slide': itemsPerSlide }"
      role="region"
      :aria-label="t('carousel.title')"
      tabindex="0"
      @keydown.left.prevent="prev"
      @keydown.right.prevent="next"
    >
      <button
        class="carousel-btn carousel-btn-left"
        type="button"
        :disabled="!canPrev"
        :aria-disabled="!canPrev"
        :aria-label="t('carousel.prev')"
        @click="prev"
      >
        <span>‹</span>
      </button>
      <div
        class="carousel-viewport"
        @pointerdown="onPointerDown"
        @pointerup="onPointerUp"
        @pointercancel="onPointerUp"
        @pointerleave="onPointerUp"
      >
        <div class="carousel-track" :style="trackStyle">
          <div
            v-for="(slide, index) in slides"
            :key="index"
            class="carousel-slide"
            role="group"
            :aria-label="t('carousel.page_of', { current: index + 1, total: slides.length })"
          >
            <ProjectCard
              v-for="project in slide"
              :key="project.id"
              :project="project"
              :featured="true"
              @select="emitSelect"
            />
          </div>
        </div>
      </div>
      <button
        class="carousel-btn carousel-btn-right"
        type="button"
        :disabled="!canNext"
        :aria-disabled="!canNext"
        :aria-label="t('carousel.next')"
        @click="next"
      >
        <span>›</span>
      </button>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import ProjectCard from '@/components/ProjectCard.vue'

const { t } = useI18n()

const props = defineProps({
  projects: { type: Array, default: () => [] }
})

const emit = defineEmits(['select'])

const currentIndex = ref(0)
const itemsPerSlide = ref(3)
let pointerStartX = 0
let pointerActive = false

const slides = computed(() => {
  const out = []
  const items = props.projects || []
  for (let i = 0; i < items.length; i += itemsPerSlide.value) {
    out.push(items.slice(i, i + itemsPerSlide.value))
  }
  return out
})

const maxIndex = computed(() => Math.max(0, slides.value.length - 1))
const canPrev = computed(() => currentIndex.value > 0)
const canNext = computed(() => currentIndex.value < maxIndex.value)

const trackStyle = computed(() => ({
  transform: `translateX(-${currentIndex.value * 100}%)`
}))

function updateItemsPerSlide() {
  const width = window.innerWidth
  itemsPerSlide.value = width < 640 ? 1 : width < 1024 ? 2 : 3
  if (currentIndex.value > maxIndex.value) {
    currentIndex.value = maxIndex.value
  }
}

function prev() {
  if (canPrev.value) currentIndex.value -= 1
}

function next() {
  if (canNext.value) currentIndex.value += 1
}

function onPointerDown(event) {
  pointerActive = true
  pointerStartX = event.clientX
}

function onPointerUp(event) {
  if (!pointerActive) return
  const deltaX = event.clientX - pointerStartX
  pointerActive = false
  if (deltaX > 50) prev()
  else if (deltaX < -50) next()
}

function emitSelect(project) {
  emit('select', project)
}

onMounted(() => {
  updateItemsPerSlide()
  window.addEventListener('resize', updateItemsPerSlide)
})

onUnmounted(() => {
  window.removeEventListener('resize', updateItemsPerSlide)
})
</script>

<style scoped>
.top-rated {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  padding: 18px 20px;
  margin-bottom: 24px;
}
.top-rated-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
  padding: 0 40px;
}
.top-rated-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.top-rated-sub {
  font-size: 0.82rem;
  color: var(--color-text-muted);
  margin-top: 4px;
}
.carousel {
  position: relative;
  outline: none;
  padding: 2px 40px;
}
.carousel-viewport {
  overflow: hidden;
  touch-action: pan-y;
  border-radius: 4px;
  padding-top: 8px;
  margin-top: -8px;
}
.carousel-track {
  display: flex;
  transition: transform 0.35s ease;
}
.carousel-slide {
  flex: 0 0 100%;
  display: grid;
  grid-template-columns: repeat(var(--items-per-slide), minmax(0, 1fr));
  gap: 16px;
}

.carousel-btn {
  width: 38px;
  height: 38px;
  border-radius: 999px;
  border: 1px solid var(--color-border);
  background: rgba(var(--color-surface-rgb), 0.85);
  color: var(--color-text);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s, color 0.15s, box-shadow 0.2s, transform 0.2s;
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 2;
  box-shadow: 0 8px 18px rgba(0,0,0,0.35);
}
.carousel-btn:hover {
  background: var(--color-elevated);
  border-color: var(--color-accent);
  color: var(--color-text-strong);
  transform: translateY(-50%) scale(1.02);
}
.carousel-btn:disabled,
.carousel-btn[aria-disabled='true'] {
  opacity: 0.4;
  cursor: default;
  border-color: var(--color-border);
  color: var(--color-text-muted);
  box-shadow: none;
  transform: translateY(-50%);
}

.carousel-btn-left { left: -8px; }
.carousel-btn-right { right: -8px; }

.carousel::before,
.carousel::after {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  width: 60px;
  pointer-events: none;
  z-index: 1;
}
.carousel::before {
  left: 0;
  background: linear-gradient(90deg, rgba(var(--color-surface-rgb), 0.9), rgba(var(--color-surface-rgb), 0));
}
.carousel::after {
  right: 0;
  background: linear-gradient(270deg, rgba(var(--color-surface-rgb), 0.9), rgba(var(--color-surface-rgb), 0));
}

@media (max-width: 640px) {
  .top-rated-header {
    align-items: flex-start;
    flex-direction: column;
    padding: 0 28px;
  }
  .carousel { padding: 2px 28px; }
  .carousel-btn { width: 34px; height: 34px; }
  .carousel-btn-left { left: -6px; }
  .carousel-btn-right { right: -6px; }
}

@media (max-width: 900px) {
  .top-rated {
    padding: 14px;
  }

  .top-rated-header {
    padding: 0 30px;
  }

  .carousel {
    padding: 2px 30px;
  }

  .carousel::before,
  .carousel::after {
    width: 40px;
  }
}

@media (max-width: 640px) {
  .top-rated {
    padding: 12px;
  }

  .carousel::before,
  .carousel::after {
    width: 32px;
  }
}
</style>
