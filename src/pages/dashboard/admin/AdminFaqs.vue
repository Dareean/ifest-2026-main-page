<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from '../../../composables/useToast'
import { useConfirm } from '../../../composables/useConfirm'
import api from '../../../utils/api'
import { HelpCircle, Plus, Pencil, Trash2, Save, X, ChevronDown, ChevronUp } from 'lucide-vue-next'

const { showToast } = useToast()
const confirmModal = useConfirm()
const loading = ref(true)
const saving = ref(false)
const faqs = ref([])
const showForm = ref(false)
const editingId = ref(null)
const form = ref({
  question: '',
  answer: '',
  category: 'umum',
  order: 0,
})

const catOptions = [
  { value: 'umum', label: 'Umum' },
  { value: 'pendaftaran', label: 'Pendaftaran' },
  { value: 'lomba', label: 'Lomba' },
  { value: 'teknis', label: 'Teknis' },
  { value: 'pembayaran', label: 'Pembayaran' },
]

async function fetchFaqs() {
  loading.value = true
  try {
    const res = await api.get('/admin/faqs')
    faqs.value = res.data.data
  } catch {
    showToast('Gagal memuat data FAQ', 'error')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.value = { question: '', answer: '', category: 'umum', order: 0 }
  editingId.value = null
  showForm.value = false
}

function editFaq(f) {
  form.value = {
    question: f.question,
    answer: f.answer,
    category: f.category,
    order: f.order,
  }
  editingId.value = f.id
  showForm.value = true
}

async function handleSave() {
  saving.value = true
  try {
    if (editingId.value) {
      const res = await api.put(`/admin/faqs/${editingId.value}`, form.value)
      showToast(res.data.message, 'success')
    } else {
      const res = await api.post('/admin/faqs', form.value)
      showToast(res.data.message, 'success')
    }
    resetForm()
    await fetchFaqs()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal menyimpan', 'error')
  } finally {
    saving.value = false
  }
}

async function handleDelete(f) {
  if (!await confirmModal.confirm(`Hapus FAQ "${f.question}"?`, 'Konfirmasi Hapus')) return
  try {
    await api.delete(`/admin/faqs/${f.id}`)
    showToast('FAQ berhasil dihapus', 'success')
    await fetchFaqs()
  } catch {
    showToast('Gagal menghapus FAQ', 'error')
  }
}

const expandedId = ref(null)

function toggleExpand(id) {
  expandedId.value = expandedId.value === id ? null : id
}

onMounted(fetchFaqs)
</script>

<template>
  <div class="max-w-4xl">
    <div class="flex items-center justify-between gap-3 mb-8">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[#04000D] flex items-center justify-center">
          <HelpCircle class="w-5 h-5 text-[#DCEEB1]" />
        </div>
        <div>
          <h1 class="text-xl font-black tracking-tight text-on-surface">Kelola FAQ</h1>
          <p class="text-xs text-on-surface-variant/60 mt-0.5 font-medium">Atur pertanyaan yang sering diajukan</p>
        </div>
      </div>
      <button
        v-if="!showForm"
        @click="showForm = true"
        class="flex items-center gap-1.5 bg-[#04000D] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-black transition-all"
      >
        <Plus class="w-3.5 h-3.5" /> Tambah FAQ
      </button>
    </div>

    <Transition name="slide">
      <div v-if="showForm" class="bg-white border border-slate-200/60 rounded-2xl p-5 md:p-6 mb-6">
        <h4 class="text-xs font-extrabold uppercase tracking-wider text-on-surface-variant/60 mb-4">
          {{ editingId ? 'Edit FAQ' : 'Tambah FAQ Baru' }}
        </h4>
        <div class="grid grid-cols-1 gap-4">
          <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Kategori</label>
            <select v-model="form.category" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all">
              <option v-for="opt in catOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Pertanyaan</label>
            <input v-model="form.question" placeholder="Tulis pertanyaan..." class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
          </div>
          <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Jawaban</label>
            <textarea v-model="form.answer" placeholder="Tulis jawaban..." rows="3" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all resize-none"></textarea>
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
        v-for="f in faqs"
        :key="f.id"
        class="bg-white border border-slate-200/60 rounded-2xl overflow-hidden"
      >
        <div class="flex items-start gap-3 p-4 cursor-pointer" @click="toggleExpand(f.id)">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span class="font-bold text-sm text-on-surface">{{ f.question }}</span>
              <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-on-surface-variant/60">{{ catOptions.find(o => o.value === f.category)?.label || f.category }}</span>
            </div>
            <p v-if="expandedId === f.id" class="text-xs text-on-surface-variant/70 mt-1.5 leading-relaxed">{{ f.answer }}</p>
          </div>
          <button class="flex-shrink-0 p-1 text-on-surface-variant/40">
            <ChevronDown v-if="expandedId !== f.id" class="w-4 h-4" />
            <ChevronUp v-else class="w-4 h-4" />
          </button>
        </div>
        <div v-if="expandedId === f.id" class="flex items-center gap-1.5 px-4 pb-3 pt-0 border-t border-slate-100 ml-4">
          <button @click="editFaq(f)" class="flex items-center gap-1 px-3 py-1.5 rounded-lg hover:bg-slate-100 text-xs font-bold text-on-surface-variant/50 transition-all">
            <Pencil class="w-3 h-3" /> Edit
          </button>
          <button @click="handleDelete(f)" class="flex items-center gap-1 px-3 py-1.5 rounded-lg hover:bg-rose-50 text-xs font-bold text-rose-400 transition-all">
            <Trash2 class="w-3 h-3" /> Hapus
          </button>
        </div>
      </div>
    </div>

    <div v-if="!loading && faqs.length === 0" class="text-center py-12 text-xs text-on-surface-variant/50">
      Belum ada FAQ. Klik "Tambah FAQ" untuk memulai.
    </div>
  </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all 0.2s ease-out; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
