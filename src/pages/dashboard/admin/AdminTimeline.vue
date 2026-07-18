<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from '../../../composables/useToast'
import { useConfirm } from '../../../composables/useConfirm'
import api from '../../../utils/api'
import { CalendarDays, Plus, Pencil, Trash2, Save, X, ChevronDown, ChevronUp } from 'lucide-vue-next'

const { showToast } = useToast()
const confirmModal = useConfirm()
const loading = ref(true)
const saving = ref(false)
const events = ref([])
const showForm = ref(false)
const editingId = ref(null)
const form = ref({
  title: '',
  date: '',
  description: '',
  icon: 'calendar',
  order: 0,
})

const iconOptions = [
  { value: 'calendar', label: 'Kalender' },
  { value: 'trophy', label: 'Trofi' },
  { value: 'star', label: 'Bintang' },
  { value: 'flag', label: 'Bendera' },
  { value: 'lightbulb', label: 'Lampu' },
  { value: 'users', label: 'Pengguna' },
  { value: 'file-text', label: 'Dokumen' },
  { value: 'check-circle', label: 'Checklist' },
]

async function fetchEvents() {
  loading.value = true
  try {
    const res = await api.get('/admin/timeline')
    events.value = res.data.data
  } catch {
    showToast('Gagal memuat data timeline', 'error')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.value = { title: '', date: '', description: '', icon: 'calendar', order: 0 }
  editingId.value = null
  showForm.value = false
}

function editEvent(e) {
  form.value = {
    title: e.title,
    date: e.date,
    description: e.description || '',
    icon: e.icon || 'calendar',
    order: e.order,
  }
  editingId.value = e.id
  showForm.value = true
}

async function handleSave() {
  saving.value = true
  try {
    if (editingId.value) {
      const res = await api.put(`/admin/timeline/${editingId.value}`, form.value)
      showToast(res.data.message, 'success')
    } else {
      const res = await api.post('/admin/timeline', form.value)
      showToast(res.data.message, 'success')
    }
    resetForm()
    await fetchEvents()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal menyimpan', 'error')
  } finally {
    saving.value = false
  }
}

async function handleDelete(e) {
  if (!await confirmModal.confirm(`Hapus event "${e.title}"?`, 'Konfirmasi Hapus')) return
  try {
    await api.delete(`/admin/timeline/${e.id}`)
    showToast('Event berhasil dihapus', 'success')
    await fetchEvents()
  } catch {
    showToast('Gagal menghapus event', 'error')
  }
}

onMounted(fetchEvents)
</script>

<template>
  <div class="max-w-4xl">
    <div class="flex items-center justify-between gap-3 mb-8">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[#04000D] flex items-center justify-center">
          <CalendarDays class="w-5 h-5 text-[#DCEEB1]" />
        </div>
        <div>
          <h1 class="text-xl font-black tracking-tight text-on-surface">Kelola Timeline</h1>
          <p class="text-xs text-on-surface-variant/60 mt-0.5 font-medium">Atur jadwal acara IFEST 2026</p>
        </div>
      </div>
      <button
        v-if="!showForm"
        @click="showForm = true"
        class="flex items-center gap-1.5 bg-[#04000D] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-black transition-all"
      >
        <Plus class="w-3.5 h-3.5" /> Tambah Event
      </button>
    </div>

    <Transition name="slide">
      <div v-if="showForm" class="bg-white border border-slate-200/60 rounded-2xl p-5 md:p-6 mb-6">
        <h4 class="text-xs font-extrabold uppercase tracking-wider text-on-surface-variant/60 mb-4">
          {{ editingId ? 'Edit Event' : 'Tambah Event Baru' }}
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Judul</label>
            <input v-model="form.title" placeholder="Nama event" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
          </div>
          <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Tanggal</label>
            <input v-model="form.date" type="date" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
          </div>
          <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Ikon</label>
            <select v-model="form.icon" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all">
              <option v-for="opt in iconOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Deskripsi</label>
            <textarea v-model="form.description" placeholder="Deskripsi event" rows="2" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all resize-none"></textarea>
          </div>
          <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Urutan</label>
            <input v-model.number="form.order" type="number" min="0" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
          </div>
        </div>
        <div class="flex items-center gap-3 mt-5 pt-4 border-t border-slate-200/60">
          <button
            @click="handleSave"
            :disabled="saving"
            class="flex items-center gap-1.5 bg-[#04000D] text-white px-5 py-2 rounded-xl text-xs font-bold hover:bg-black transition-all disabled:opacity-40"
          >
            <Save v-if="!saving" class="w-3.5 h-3.5" />
            <span v-else class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
            {{ saving ? 'Menyimpan...' : 'Simpan' }}
          </button>
          <button @click="resetForm" class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-on-surface-variant/60 hover:bg-slate-100 transition-all">
            <X class="w-3.5 h-3.5" /> Batal
          </button>
        </div>
      </div>
    </Transition>

    <div v-if="loading" class="text-center py-12">
      <div class="animate-pulse text-xs font-semibold text-on-surface-variant/40">Memuat data...</div>
    </div>

    <div v-else class="space-y-2">
      <div
        v-for="e in events"
        :key="e.id"
        class="bg-white border border-slate-200/60 rounded-2xl p-4 flex items-center gap-4 relative"
      >
        <div v-if="e.date" class="flex flex-col items-center justify-center w-12 h-12 rounded-xl bg-slate-100 flex-shrink-0">
          <span class="font-black text-xs text-on-surface">{{ new Date(e.date).getDate() }}</span>
          <span class="text-[8px] font-bold uppercase text-on-surface-variant/60">{{ new Date(e.date).toLocaleString('id', { month: 'short' }) }}</span>
        </div>
        <CalendarDays v-else class="w-8 h-8 text-slate-300 flex-shrink-0" />
        <div class="flex-1 min-w-0">
          <span class="font-bold text-sm text-on-surface truncate">{{ e.title }}</span>
          <p v-if="e.description" class="text-xs text-on-surface-variant/60 mt-0.5 truncate">{{ e.description }}</p>
        </div>
        <div class="flex items-center gap-1.5 flex-shrink-0">
          <button @click="editEvent(e)" class="p-2 rounded-lg hover:bg-slate-100 text-on-surface-variant/50 transition-all">
            <Pencil class="w-3.5 h-3.5" />
          </button>
          <button @click="handleDelete(e)" class="p-2 rounded-lg hover:bg-rose-50 text-rose-400 transition-all">
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>

    <div v-if="!loading && events.length === 0" class="text-center py-12 text-xs text-on-surface-variant/50">
      Belum ada event. Klik "Tambah Event" untuk memulai.
    </div>
  </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all 0.2s ease-out; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
