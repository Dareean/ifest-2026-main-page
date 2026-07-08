import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../utils/api'

export const useAdminStore = defineStore('admin', () => {
  const stats = ref(null)
  const statsLoading = ref(false)

  async function fetchStats() {
    statsLoading.value = true
    try {
      const res = await api.get('/admin/stats')
      stats.value = res.data
      return stats.value
    } finally {
      statsLoading.value = false
    }
  }

  function clearStats() {
    stats.value = null
  }

  return { stats, statsLoading, fetchStats, clearStats }
})