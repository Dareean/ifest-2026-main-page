<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from '../../../composables/useToast'
import { useConfirm } from '../../../composables/useConfirm'
import api from '../../../utils/api'
import {
  Handshake, Plus, Pencil, Trash2, Save, X, ChevronDown, ChevronUp
} from 'lucide-vue-next'

const { showToast } = useToast()
const confirmModal = useConfirm()
const loading = ref(true)
const saving = ref(false)
const partners = ref([])
const showForm = ref(false)
const editingId = ref(null)
const form = ref({
  type: 'media_partner',
  name: '',
  logo_url: '',
  instagram_url: '',
  description: '',
  tier_data: null,
  order: 0,
})

const typeOptions = [
  { value: 'main_strategic', label: 'Main Strategic' },
  { value: 'strategic_partner', label: 'Strategic Partner' },
  { value: 'media_partner', label: 'Media Partner' },
  { value: 'organizer', label: 'Organizer' },
  { value: 'sponsorship_tier', label: 'Sponsorship Tier' },
]

async function fetchPartners() {
  loading.value = true
  try {
    const res = await api.get('/admin/partners')
    partners.value = res.data.data
  } catch {
    showToast('Gagal memuat data partner', 'error')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.value = {
    type: 'media_partner',
    name: '',
    logo_url: '',
    instagram_url: '',
    description: '',
    tier_data: null,
    order: 0,
  }
  editingId.value = null
  showForm.value = false
}

function editPartner(p) {
  form.value = {
    type: p.type,
    name: p.name,
    logo_url: p.logo_url || '',
    instagram_url: p.instagram_url || '',
    description: p.description || '',
    tier_data: p.tier_data,
    order: p.order,
  }
  editingId.value = p.id
  showForm.value = true
}

async function handleSave() {
  saving.value = true
  try {
    if (editingId.value) {
      const res = await api.put(`/admin/partners/${editingId.value}`, form.value)
      showToast(res.data.message, 'success')
    } else {
      const res = await api.post('/admin/partners', form.value)
      showToast(res.data.message, 'success')
    }
    resetForm()
    await fetchPartners()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal menyimpan', 'error')
  } finally {
    saving.value = false
  }
}

async function handleDelete(p) {
  if (!await confirmModal.confirm(`Yakin ingin menghapus partner "${p.name}"?`, 'Konfirmasi Hapus')) return
  try {
    await api.delete(`/admin/partners/${p.id}`)
    showToast('Partner berhasil dihapus', 'success')
    await fetchPartners()
  } catch {
    showToast('Gagal menghapus partner', 'error')
  }
}

onMounted(fetchPartners)
</script>

<template>
  <div class="max-w-6xl">
    <div class="flex items-center justify-between gap-3 mb-8">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[#04000D] flex items-center justify-center">
          <Handshake class="w-5 h-5 text-[#DCEEB1]" />
        </div>
        <div>
          <h1 class="text-xl font-black tracking-tight text-on-surface">Kelola Partner</h1>
          <p class="text-xs text-on-surface-variant/60 mt-0.5 font-medium">Atur sponsor, media partner, dan organizer</p>
        </div>
      </div>
      <button
        v-if="!showForm"
        @click="showForm = true"
        class="flex items-center gap-1.5 bg-[#04000D] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-black transition-all"
      >
        <Plus class="w-3.5 h-3.5" /> Tambah Partner
      </button>
    </div>

    <!-- Form -->
    <Transition name="slide">
      <div v-if="showForm" class="bg-white border border-slate-200/60 rounded-2xl p-5 md:p-6 mb-6">
        <h4 class="text-xs font-extrabold uppercase tracking-wider text-on-surface-variant/60 mb-4">
          {{ editingId ? 'Edit Partner' : 'Tambah Partner Baru' }}
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Tipe</label>
            <select v-model="form.type" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all">
              <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Nama</label>
            <input v-model="form.name" placeholder="Nama partner" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
          </div>
          <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Logo URL</label>
            <input v-model="form.logo_url" placeholder="https://example.com/logo.png" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
          </div>
          <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Instagram URL</label>
            <input v-model="form.instagram_url" placeholder="https://instagram.com/..." class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Deskripsi</label>
            <textarea v-model="form.description" placeholder="Deskripsi partner" rows="2" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all resize-none"></textarea>
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

    <!-- Table -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-pulse text-xs font-semibold text-on-surface-variant/40">Memuat data...</div>
    </div>

    <div v-else class="space-y-2">
      <div
        v-for="p in partners"
        :key="p.id"
        class="bg-white border border-slate-200/60 rounded-2xl p-4 flex items-center gap-4"
      >
        <div class="w-10 h-10 rounded-xl bg-slate-100 flex-shrink-0 flex items-center justify-center overflow-hidden">
          <img v-if="p.logo_url" :src="p.logo_url" :alt="p.name" class="w-full h-full object-contain" />
          <Handshake v-else class="w-5 h-5 text-slate-400" />
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <span class="font-bold text-sm text-on-surface truncate">{{ p.name }}</span>
            <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-on-surface-variant/60">{{ p.type.replace('_', ' ') }}</span>
          </div>
          <p v-if="p.description" class="text-xs text-on-surface-variant/60 mt-0.5 truncate">{{ p.description }}</p>
        </div>
        <div class="flex items-center gap-1.5 flex-shrink-0">
          <button @click="editPartner(p)" class="p-2 rounded-lg hover:bg-slate-100 text-on-surface-variant/50 transition-all">
            <Pencil class="w-3.5 h-3.5" />
          </button>
          <button @click="handleDelete(p)" class="p-2 rounded-lg hover:bg-rose-50 text-rose-400 transition-all">
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>

    <div v-if="!loading && partners.length === 0" class="text-center py-12 text-xs text-on-surface-variant/50">
      Belum ada data partner. Klik "Tambah Partner" untuk memulai.
    </div>
  </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all 0.2s ease-out; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
