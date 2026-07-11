import { ref, computed } from 'vue'

// Module-level singleton state — shared across all consumers
const selectedLomba = ref(null)
const pendaftarans = ref([])
const activeTab = ref('info')

const availableTabs = computed(() => {
  const tabs = ['info', 'timeline', 'team']
  if (selectedLomba.value) {
    const reg = pendaftarans.value.find(p => p.lomba_id === selectedLomba.value.id)
    if (reg) {
      tabs.push('anggota')
      tabs.push('validasi')
      if (reg.status === 'verified') {
        tabs.push('submit')
      }
    }
  }
  return tabs
})

export function useCompetitionNav() {
  return {
    selectedLomba,
    pendaftarans,
    activeTab,
    availableTabs
  }
}
