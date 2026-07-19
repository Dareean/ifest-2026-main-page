<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from '../../../composables/useToast'
import { useConfirm } from '../../../composables/useConfirm'
import api from '../../../utils/api'
import {
  Trophy, ToggleLeft, ToggleRight, Calendar, ExternalLink, Lock, Unlock, ChevronDown, ChevronUp, Save, X,
  Eye, EyeOff, Plus
} from 'lucide-vue-next'

const { showToast } = useToast()
const confirmModal = useConfirm()
const loading = ref(true)
const saving = ref(null)
const lombas = ref([])
const expandedId = ref(null)
const editForm = ref({})

function formatDateForInput(date) {
  if (!date) return ''
  return date.split('T')[0]
}

function toggleExpand(lomba) {
  if (expandedId.value === lomba.id) {
    expandedId.value = null
    editForm.value = {}
  } else {
    expandedId.value = lomba.id
    
    // Parse schedule string into array of stages
    const scheduleStr = lomba.schedule || ''
    const stages = scheduleStr.split('|').filter(item => item.trim()).map(item => {
      const parts = item.split(':')
      const title = parts[0]?.trim() || ''
      const date = parts.slice(1).join(':')?.trim() || ''
      return { title, date }
    })

    editForm.value = {
      gelombang_1_start: formatDateForInput(lomba.gelombang_1_start),
      gelombang_1_end: formatDateForInput(lomba.gelombang_1_end),
      gelombang_2_end: formatDateForInput(lomba.gelombang_2_end),
      fee_gelombang_1: lomba.fee_gelombang_1 || '',
      fee_gelombang_2: lomba.fee_gelombang_2 || '',
      registration_link: lomba.registration_link || '',
      guidebook_link: lomba.guidebook_link || '',
      contact_person: lomba.contact_person || '',
      schedule: lomba.schedule || '',
      current_stage: lomba.current_stage !== undefined && lomba.current_stage !== null ? lomba.current_stage : 0,
      stages: stages.length > 0 ? stages : [{ title: '', date: '' }]
    }
  }
}

function addStage() {
  if (!editForm.value.stages) {
    editForm.value.stages = []
  }
  editForm.value.stages.push({ title: '', date: '' })
}

function removeStage(index) {
  if (editForm.value.stages && editForm.value.stages.length > 1) {
    editForm.value.stages.splice(index, 1)
    if (editForm.value.current_stage >= editForm.value.stages.length) {
      editForm.value.current_stage = editForm.value.stages.length - 1
    }
  }
}

async function fetchLombas() {
  loading.value = true
  try {
    const res = await api.get('/admin/lombas')
    lombas.value = res.data.data
  } catch (e) {
    showToast('Gagal memuat data lomba', 'error')
  } finally {
    loading.value = false
  }
}

async function handleToggleActive(lomba) {
  const action = lomba.is_active ? 'menyembunyikan' : 'menampilkan'
  if (!await confirmModal.confirm(
    `Yakin ingin ${action} lomba "${lomba.title}" dari landing page?`,
    'Konfirmasi'
  )) return

  saving.value = lomba.id
  try {
    const res = await api.put(`/admin/lombas/${lomba.id}/toggle-active`)
    showToast(res.data.message, 'success')
    await fetchLombas()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal mengubah status', 'error')
  } finally {
    saving.value = null
  }
}

async function handleToggle(lomba) {
  const action = lomba.is_submission_open ? 'menutup' : 'membuka'
  if (!await confirmModal.confirm(
    `Yakin ingin ${action} pengumpulan karya untuk "${lomba.title}"?`,
    'Konfirmasi'
  )) return

  saving.value = lomba.id
  try {
    const res = await api.put(`/admin/lombas/${lomba.id}/toggle-submission`)
    showToast(res.data.message, 'success')
    await fetchLombas()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal mengubah status', 'error')
  } finally {
    saving.value = null
  }
}

async function handleSave(lomba) {
  saving.value = lomba.id
  try {
    const compiledSchedule = (editForm.value.stages || [])
      .filter(s => s.title.trim())
      .map(s => `${s.title.trim()}: ${s.date.trim()}`)
      .join(' | ')

    const payload = {
      ...editForm.value,
      schedule: compiledSchedule
    }
    delete payload.stages

    const res = await api.put(`/admin/lombas/${lomba.id}`, payload)
    showToast(res.data.message, 'success')
    expandedId.value = null
    editForm.value = {}
    await fetchLombas()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal menyimpan', 'error')
  } finally {
    saving.value = null
  }
}

function formatDate(date) {
  if (!date) return '-'
  const cleanDate = date.split('T')[0]
  return new Date(cleanDate + 'T00:00:00').toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric'
  })
}


onMounted(fetchLombas)
</script>

<template>
  <div class="max-w-6xl">
    <div class="flex items-center gap-3 mb-8">
      <div class="w-10 h-10 rounded-xl bg-[#04000D] flex items-center justify-center">
        <Trophy class="w-5 h-5 text-[#DCEEB1]" />
      </div>
      <div>
        <h1 class="text-xl font-black tracking-tight text-on-surface">Kelola Lomba</h1>
        <p class="text-xs text-on-surface-variant/60 mt-0.5 font-medium">Atur jadwal dan buka/tutup pengumpulan karya</p>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="animate-pulse text-xs font-semibold text-on-surface-variant/40">Memuat data...</div>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="lomba in lombas"
        :key="lomba.id"
        class="bg-white border border-slate-200/60 rounded-2xl overflow-hidden transition-all"
      >
        <!-- Main row -->
        <div class="p-4 md:p-5 flex items-center gap-4">
          <div class="flex-1 min-w-0 grid grid-cols-12 gap-2 md:gap-4 items-center text-xs">
            <div class="col-span-12 md:col-span-2 flex items-center gap-2">
              <span class="font-mono text-[10px] font-bold bg-slate-100 text-on-surface-variant/60 px-2 py-0.5 rounded">{{ lomba.kode }}</span>
              <span class="font-bold text-on-surface truncate">{{ lomba.title }}</span>
            </div>
            <div class="col-span-6 md:col-span-2 text-on-surface-variant/70">
              <span class="md:hidden font-semibold text-[9px] uppercase tracking-wider block">Gel. 1</span>
              {{ formatDate(lomba.gelombang_1_start) }} - {{ formatDate(lomba.gelombang_1_end) }}
            </div>
            <div class="col-span-6 md:col-span-2">
              <span class="md:hidden font-semibold text-[9px] uppercase tracking-wider block mb-1">Tampil</span>
              <span
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                :class="lomba.is_active
                  ? 'bg-sky-50 text-sky-700 border border-sky-200'
                  : 'bg-slate-100 text-slate-500 border border-slate-200'"
              >
                <template v-if="lomba.is_active">
                  <Eye class="w-3 h-3" /> Tampil
                </template>
                <template v-else>
                  <EyeOff class="w-3 h-3" /> Sembunyi
                </template>
              </span>
            </div>
            <div class="col-span-6 md:col-span-2">
              <span class="md:hidden font-semibold text-[9px] uppercase tracking-wider block mb-1">Karya</span>
              <span
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                :class="lomba.is_submission_open
                  ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                  : 'bg-rose-50 text-rose-700 border border-rose-200'"
              >
                <template v-if="lomba.is_submission_open">
                  <Unlock class="w-3 h-3" /> Buka
                </template>
                <template v-else>
                  <Lock class="w-3 h-3" /> Tutup
                </template>
              </span>
            </div>
            <div class="col-span-6 md:col-span-4 flex items-center gap-1.5 justify-end">
              <button
                @click="handleToggleActive(lomba)"
                :disabled="saving === lomba.id"
                class="flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all"
                :class="lomba.is_active
                  ? 'bg-slate-100 text-slate-600 hover:bg-slate-200 border border-slate-200'
                  : 'bg-sky-50 text-sky-600 hover:bg-sky-100 border border-sky-200'"
              >
                <template v-if="saving === lomba.id">
                  <span class="animate-spin inline-block w-3 h-3 border-2 border-current border-t-transparent rounded-full"></span>
                </template>
                <template v-else>
                  <template v-if="lomba.is_active">
                    <EyeOff class="w-3.5 h-3.5" /> Sembunyi
                  </template>
                  <template v-else>
                    <Eye class="w-3.5 h-3.5" /> Tampil
                  </template>
                </template>
              </button>
              <button
                @click="handleToggle(lomba)"
                :disabled="saving === lomba.id"
                class="flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all"
                :class="lomba.is_submission_open
                  ? 'bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200'
                  : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border border-emerald-200'"
              >
                <template v-if="saving === lomba.id">
                  <span class="animate-spin inline-block w-3 h-3 border-2 border-current border-t-transparent rounded-full"></span>
                </template>
                <template v-else>
                  <template v-if="lomba.is_submission_open">
                    <ToggleRight class="w-3.5 h-3.5" /> Tutup
                  </template>
                  <template v-else>
                    <ToggleLeft class="w-3.5 h-3.5" /> Buka
                  </template>
                </template>
              </button>
              <button
                @click="toggleExpand(lomba)"
                class="p-1.5 rounded-lg hover:bg-slate-100 text-on-surface-variant/50 transition-all"
              >
                <ChevronDown v-if="expandedId !== lomba.id" class="w-4 h-4" />
                <ChevronUp v-else class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Expanded edit panel -->
        <Transition name="slide">
          <div v-if="expandedId === lomba.id" class="border-t border-slate-100 bg-slate-50/50 p-4 md:p-6">
            <h4 class="text-xs font-extrabold uppercase tracking-wider text-on-surface-variant/60 mb-4">Edit Jadwal & Data Lomba</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Gel. 1 Mulai</label>
                <input v-model="editForm.gelombang_1_start" type="date" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
              </div>
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Gel. 1 Akhir</label>
                <input v-model="editForm.gelombang_1_end" type="date" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
              </div>
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Gel. 2 Akhir</label>
                <input v-model="editForm.gelombang_2_end" type="date" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
              </div>
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Fee Gel. 1</label>
                <input v-model="editForm.fee_gelombang_1" placeholder="Rp 40.000 / Tim" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
              </div>
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Fee Gel. 2</label>
                <input v-model="editForm.fee_gelombang_2" placeholder="Rp 50.000 / Tim" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
              </div>
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Contact Person</label>
                <input v-model="editForm.contact_person" placeholder="Nama (+62 xxx)" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
              </div>
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Tahap Aktif Saat Ini</label>
                <select v-model="editForm.current_stage" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all">
                  <option v-for="(stage, index) in editForm.stages" :key="index" :value="index">
                    Tahap {{ index + 1 }}: {{ stage.title || `Tahap ${index + 1}` }}
                  </option>
                </select>
              </div>
              <div class="md:col-span-2">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Link Registrasi</label>
                <input v-model="editForm.registration_link" placeholder="https://forms.gle/..." class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Link Guidebook</label>
                <input v-model="editForm.guidebook_link" placeholder="https://drive.google.com/..." class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
              </div>
              <div class="md:col-span-3 border border-slate-200/80 rounded-xl p-4 bg-slate-50/20 space-y-3 mt-2">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50">Jadwal & Tahapan Lomba</label>
                
                <div v-for="(stage, idx) in editForm.stages" :key="idx" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 pb-2 border-b border-slate-100 last:border-0 last:pb-0">
                  <span class="text-[10px] font-bold text-on-surface-variant/55 min-w-[50px] select-none">Tahap {{ idx + 1 }}</span>
                  <input v-model="stage.title" placeholder="Nama Tahap (contoh: Technical Meeting)" class="flex-1 bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
                  <input v-model="stage.date" placeholder="Tanggal/Deskripsi (contoh: 11 Agustus 2026)" class="flex-1 bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
                  <button type="button" @click="removeStage(idx)" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-all self-end sm:self-auto" :disabled="editForm.stages.length <= 1">
                    <X class="w-3.5 h-3.5" />
                  </button>
                </div>
                
                <button type="button" @click="addStage" class="flex items-center gap-1 text-[10px] font-bold text-on-surface/70 hover:text-black mt-2 bg-white px-2.5 py-1.5 rounded-lg border border-slate-200 shadow-sm transition-all">
                  <Plus class="w-3 h-3" /> Tambah Tahap
                </button>
              </div>
            </div>
            <div class="flex items-center gap-3 mt-5 pt-4 border-t border-slate-200/60">
              <button
                @click="handleSave(lomba)"
                :disabled="saving === lomba.id"
                class="flex items-center gap-1.5 bg-[#04000D] text-white px-5 py-2 rounded-xl text-xs font-bold hover:bg-black transition-all disabled:opacity-40"
              >
                <Save v-if="saving !== lomba.id" class="w-3.5 h-3.5" />
                <span v-else class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                {{ saving === lomba.id ? 'Menyimpan...' : 'Simpan Perubahan' }}
              </button>
              <button
                @click="expandedId = null; editForm = {}"
                class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-on-surface-variant/60 hover:bg-slate-100 transition-all"
              >
                <X class="w-3.5 h-3.5" /> Batal
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </div>

    <div v-if="!loading && lombas.length === 0" class="text-center py-12 text-xs text-on-surface-variant/50">
      Belum ada data lomba.
    </div>
  </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active {
  transition: all 0.2s ease-out;
}
.slide-enter-from, .slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
