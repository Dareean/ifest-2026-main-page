<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from '../../../composables/useToast'
import { useConfirm } from '../../../composables/useConfirm'
import api from '../../../utils/api'
import {
  HelpCircle, Plus, Edit, Trash2, Eye, EyeOff, Save, X
} from 'lucide-vue-next'

const { showToast } = useToast()
const confirmModal = useConfirm()

const faqs = ref([])
const loading = ref(true)
const saving = ref(false)
const showModal = ref(false)
const modalTitle = ref('Tambah FAQ')

const form = ref({
  id: null,
  question: '',
  answer: '',
  order: 0,
  is_active: true
})

async function fetchFaqs() {
  loading.value = true
  try {
    const res = await api.get('/admin/faqs')
    faqs.value = res.data.data
  } catch (e) {
    showToast('Gagal memuat data FAQ', 'error')
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  modalTitle.value = 'Tambah FAQ'
  form.value = {
    id: null,
    question: '',
    answer: '',
    order: 0,
    is_active: true
  }
  showModal.value = true
}

function openEditModal(faq) {
  modalTitle.value = 'Edit FAQ'
  form.value = {
    id: faq.id,
    question: faq.question,
    answer: faq.answer,
    order: faq.order || 0,
    is_active: faq.is_active
  }
  showModal.value = true
}

async function handleSave() {
  if (!form.value.question || !form.value.answer) {
    showToast('Pertanyaan dan Jawaban wajib diisi', 'warning')
    return
  }

  saving.value = true
  try {
    const payload = {
      question: form.value.question,
      answer: form.value.answer,
      order: parseInt(form.value.order) || 0,
      is_active: form.value.is_active
    }

    if (form.value.id) {
      await api.put(`/admin/faqs/${form.value.id}`, payload)
      showToast('FAQ berhasil diperbarui', 'success')
    } else {
      await api.post('/admin/faqs', payload)
      showToast('FAQ berhasil ditambahkan', 'success')
    }
    showModal.value = false
    await fetchFaqs()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal menyimpan FAQ', 'error')
  } finally {
    saving.value = false
  }
}

async function handleDelete(faq) {
  if (!await confirmModal.confirm(
    `Apakah Anda yakin ingin menghapus FAQ "${faq.question}"?`,
    'Hapus FAQ'
  )) return

  try {
    await api.delete(`/admin/faqs/${faq.id}`)
    showToast('FAQ berhasil dihapus', 'success')
    await fetchFaqs()
  } catch (e) {
    showToast('Gagal menghapus FAQ', 'error')
  }
}

onMounted(fetchFaqs)
</script>

<template>
  <div class="max-w-6xl">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[#04000D] flex items-center justify-center">
          <HelpCircle class="w-5 h-5 text-[#DCEEB1]" />
        </div>
        <div>
          <h1 class="text-xl font-black tracking-tight text-on-surface">Kelola FAQ</h1>
          <p class="text-xs text-on-surface-variant/60 mt-0.5 font-medium">Kelola daftar pertanyaan yang sering diajukan di landing page</p>
        </div>
      </div>
      <button
        @click="openAddModal"
        class="flex items-center justify-center gap-2 px-4 py-2 bg-[#04000D] hover:bg-[#04000D]/90 text-[#DCEEB1] font-bold text-xs uppercase tracking-wider rounded-xl transition-all shadow-[2px_2px_0px_0px_#FF3D8B]"
      >
        <Plus class="w-4 h-4" /> Tambah FAQ
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-pulse text-xs font-semibold text-on-surface-variant/40">Memuat data...</div>
    </div>

    <!-- Empty State -->
    <div v-else-if="faqs.length === 0" class="text-center py-16 bg-white border border-dashed border-slate-200/80 rounded-2xl">
      <p class="text-sm text-on-surface-variant/60 font-semibold">Belum ada FAQ yang ditambahkan.</p>
    </div>

    <!-- FAQ list -->
    <div v-else class="space-y-3">
      <div
        v-for="faq in faqs"
        :key="faq.id"
        class="bg-white border border-slate-200/60 rounded-2xl p-4 md:p-5 flex items-start gap-4 transition-all"
      >
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1.5 flex-wrap">
            <span class="font-mono text-[9px] font-bold bg-slate-100 text-on-surface-variant/70 px-2 py-0.5 rounded">Order: {{ faq.order }}</span>
            <span
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border"
              :class="faq.is_active
                ? 'bg-sky-50 text-sky-750 border-sky-200'
                : 'bg-slate-100 text-slate-500 border border-slate-200'"
            >
              {{ faq.is_active ? 'Tampil' : 'Sembunyi' }}
            </span>
          </div>
          <h4 class="font-extrabold text-sm text-on-surface leading-tight">{{ faq.question }}</h4>
          <p class="text-xs text-on-surface-variant/75 mt-2 leading-relaxed whitespace-pre-line">{{ faq.answer }}</p>
        </div>
        <div class="flex items-center gap-1.5 flex-shrink-0">
          <button
            @click="openEditModal(faq)"
            class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors border border-slate-200"
            title="Edit FAQ"
          >
            <Edit class="w-3.5 h-3.5" />
          </button>
          <button
            @click="handleDelete(faq)"
            class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg transition-colors border border-rose-200"
            title="Hapus FAQ"
          >
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Form (Tambah/Edit) -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-[#04000D]/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white border-3 border-[#04000D] rounded-2xl w-full max-w-lg overflow-hidden shadow-[6px_6px_0px_0px_#04000D] flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b-2 border-[#04000D] bg-slate-50 flex items-center justify-between">
          <h3 class="font-extrabold text-base uppercase tracking-wider text-[#04000D]">{{ modalTitle }}</h3>
          <button @click="showModal = false" class="text-slate-500 hover:text-slate-800 transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto space-y-4 text-xs font-semibold text-on-surface">
          <!-- Question -->
          <div>
            <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">Pertanyaan (Question)</label>
            <input
              type="text"
              v-model="form.question"
              placeholder="Contoh: Kapan pendaftaran lomba ditutup?"
              class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all"
            />
          </div>

          <!-- Answer -->
          <div>
            <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">Jawaban (Answer)</label>
            <textarea
              v-model="form.answer"
              rows="5"
              placeholder="Masukkan jawaban lengkap di sini..."
              class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all resize-none"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <!-- Order -->
            <div>
              <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">Order Tampil</label>
              <input
                type="number"
                v-model="form.order"
                min="0"
                class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all"
              />
            </div>

            <!-- Active Status -->
            <div class="flex flex-col justify-end pt-2">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.is_active"
                  class="w-4 h-4 rounded border-2 border-[#04000D] text-[#04000D] focus:ring-0 cursor-pointer"
                />
                <span class="text-xs font-bold text-on-surface">Tampilkan di Landing Page</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t-2 border-[#04000D] bg-slate-50 flex items-center justify-end gap-2">
          <button
            @click="showModal = false"
            class="px-4 py-2 border-2 border-slate-300 font-bold hover:bg-slate-100 rounded-xl transition-all"
          >
            Batal
          </button>
          <button
            @click="handleSave"
            :disabled="saving"
            class="flex items-center gap-1.5 px-4 py-2 bg-[#04000D] hover:bg-[#04000D]/90 text-[#DCEEB1] font-bold rounded-xl transition-all shadow-[2px_2px_0px_0px_#FF3D8B]"
          >
            <span v-if="saving" class="animate-spin inline-block w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full"></span>
            <Save class="w-4 h-4" v-else />
            Simpan
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
