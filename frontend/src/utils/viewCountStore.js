import { reactive } from 'vue'

// Shared reactive store so ProjectView can pass updated view counts back to HomeView
// Keys are project IDs (strings), values are the latest view counts
export const viewCounts = reactive({})
