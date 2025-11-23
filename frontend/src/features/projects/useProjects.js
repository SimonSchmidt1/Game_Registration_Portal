// Composable placeholder for future multi-project handling.
// Currently unused; provides a unified loading pattern.
import { ref } from 'vue'
import { apiClient } from '../../services/apiClient'

export function useProjects() {
  const projects = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function load(type = 'game') {
    loading.value = true
    error.value = null
    try {
      // For now, fallback to games endpoint; later map type -> endpoint
      const res = await apiClient.get('/games')
      projects.value = Array.isArray(res.data) ? res.data : []
    } catch (e) {
      error.value = e.message || 'Chyba načítania projektov'
    } finally {
      loading.value = false
    }
  }

  return { projects, loading, error, load }
}
