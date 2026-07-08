import { ref, computed } from 'vue'

export function useCompetitionNav() {
  const selectedLomba = ref(null)
  const pendaftarans = ref([])
  const activeTab = ref('info')

  const availableTabs = computed(() => {
    const tabs = ['info', 'timeline', 'team']
    if (selectedLomba.value) {
      const reg = pendaftarans.value.find(p => p.lomba_id === selectedLomba.value.id)
      if (reg) {
        tabs.push('anggota')
        if (reg.status === 'verified') {
          tabs.push('submit')
        }
      }
    }
    return tabs
  })

  return {
    selectedLomba,
    pendaftarans,
    activeTab,
    availableTabs
  }
}
