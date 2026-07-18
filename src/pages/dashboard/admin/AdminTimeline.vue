<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from '../../../composables/useToast'
import { useConfirm } from '../../../composables/useConfirm'
import api from '../../../utils/api'
import {
  CalendarDays, Plus, Edit, Trash2, Eye, EyeOff, Save, X
} from 'lucide-vue-next'

const { showToast } = useToast()
const confirmModal = useConfirm()

const events = ref([])
const loading = ref(true)
const saving = ref(false)
const showModal = ref(false)
const modalTitle = ref('Tambah Timeline Event')

const form = ref({
  id: null,
  phase: '',
  title: '',
  date_range: '',
  description_items_raw: '', // text area, converted on save
  accent_color: '#8B5CF6',
  status: 'upcoming',
  order: 0,
  is_active: true
})

const statusOptions = [
  { value: 'upcoming', label: 'Upcoming' },
  { value: 'ongoing', label: 'Ongoing' },
  { value: 'completed', label: 'Completed' }
]

async function fetchEvents() {
  loading.value = true
  try {
    const res = await api.get('/admin/timeline')
    events.value = res.data.data
  } catch (e) {
    showToast('Gagal memuat data timeline', 'error')
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  modalTitle.value = 'Tambah Timeline Event'
  form.value = {
    id: null,
    phase: '',
    title: '',
    date_range: '',
    description_items_raw: '',
    accent_color: '#8B5CF6',
    status: 'upcoming',
    order: 0,
    is_active: true
  }
  showModal.value = true
}

function openEditModal(event) {
  modalTitle.value = 'Edit Timeline Event'
  // description_items is an array of strings, join with newline for raw editing
  const itemsRaw = event.description_items ? event.description_items.join('\n') : ''

  form.value = {
    id: event.id,
    phase: event.phase,
    title: event.title,
    date_range: event.date_range,
    description_items_raw: itemsRaw,
    accent_color: event.accent_color || '#8B5CF6',
    status: event.status || 'upcoming',
    order: event.order || 0,
    is_active: event.is_active
  }
  showModal.value = true
}

async function handleSave() {
  if (!form.value.phase || !form.value.title || !form.value.date_range) {
    showToast('Fase, Judul, dan Rentang Tanggal wajib diisi', 'warning')
    return
  }

  saving.value = true
  try {
    // split items by newline and filter out empty lines
    const items = form.value.description_items_raw
      ? form.value.description_items_raw.split('\n').map(i => i.trim()).filter(i => i !== '')
      : []

    const payload = {
      phase: form.value.phase,
      title: form.value.title,
      date_range: form.value.date_range,
      description_items: items,
      accent_color: form.value.accent_color || '#8B5CF6',
      status: form.value.status,
      order: parseInt(form.value.order) || 0,
      is_active: form.value.is_active
    }

    if (form.value.id) {
      await api.put(`/admin/timeline/${form.value.id}`, payload)
      showToast('Timeline event berhasil diperbarui', 'success')
    } else {
      await api.post('/admin/timeline', payload)
      showToast('Timeline event berhasil ditambahkan', 'success')
    }
    showModal.value = false
    await fetchEvents()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal menyimpan timeline event', 'error')
  } finally {
    saving.value = false
  }
}

async function handleDelete(event) {
  if (!await confirmModal.confirm(
    `Apakah Anda yakin ingin menghapus event "${event.title}"?`,
    'Hapus Timeline Event'
  )) return

  try {
    await api.delete(`/admin/timeline/${event.id}`)
    showToast('Timeline event berhasil dihapus', 'success')
    await fetchEvents()
  } catch (e) {
    showToast('Gagal menghapus timeline event', 'error')
  }
}

function getStatusLabel(statusVal) {
  return statusOptions.find(o => o.value === statusVal)?.label || statusVal
}

onMounted(fetchEvents)
</script>

<template>
  <div class="max-w-6xl">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[#04000D] flex items-center justify-center">
          <CalendarDays class="w-5 h-5 text-[#DCEEB1]" />
        </div>
        <div>
          <h1 class="text-xl font-black tracking-tight text-on-surface">Kelola Timeline</h1>
          <p class="text-xs text-on-surface-variant/60 mt-0.5 font-medium">Kelola fase dan jadwal kegiatan I-FEST yang tampil di landing page</p>
        </div>
      </div>
      <button
        @click="openAddModal"
        class="flex items-center justify-center gap-2 px-4 py-2 bg-[#04000D] hover:bg-[#04000D]/90 text-[#DCEEB1] font-bold text-xs uppercase tracking-wider rounded-xl transition-all shadow-[2px_2px_0px_0px_#FF3D8B]"
      >
        <Plus class="w-4 h-4" /> Tambah Event
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-pulse text-xs font-semibold text-on-surface-variant/40">Memuat data...</div>
    </div>

    <!-- Empty State -->
    <div v-else-if="events.length === 0" class="text-center py-16 bg-white border border-dashed border-slate-200/80 rounded-2xl">
      <p class="text-sm text-on-surface-variant/60 font-semibold">Belum ada timeline event yang ditambahkan.</p>
    </div>

    <!-- Timeline List Table -->
    <div v-else class="bg-white border border-slate-200/60 rounded-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-100 text-on-surface-variant font-bold">
              <th class="p-4 w-12">Order</th>
              <th class="p-4 w-16">Fase</th>
              <th class="p-4">Event</th>
              <th class="p-4">Tanggal</th>
              <th class="p-4 w-16">Aksen</th>
              <th class="p-4">Status</th>
              <th class="p-4 w-24">Visibilitas</th>
              <th class="p-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="event in events" :key="event.id" class="hover:bg-slate-50/30 transition-colors">
              <td class="p-4 font-mono font-bold text-on-surface-variant">{{ event.order }}</td>
              <td class="p-4 font-bold font-mono text-on-surface-variant">Phase {{ event.phase }}</td>
              <td class="p-4">
                <div class="font-bold text-on-surface text-sm">{{ event.title }}</div>
                <div class="text-[10px] text-on-surface-variant/60 mt-1 max-w-sm truncate" v-if="event.description_items && event.description_items.length > 0">
                  {{ event.description_items[0] }}...
                </div>
              </td>
              <td class="p-4 text-on-surface-variant font-mono">{{ event.date_range }}</td>
              <td class="p-4">
                <div class="flex items-center gap-1.5">
                  <div class="w-5 h-5 rounded-md border border-slate-200 shadow-sm" :style="{ backgroundColor: event.accent_color }"></div>
                </div>
              </td>
              <td class="p-4">
                <span class="px-2 py-0.5 rounded-full font-bold uppercase text-[9px] tracking-wider border"
                  :class="event.status === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                           event.status === 'ongoing' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                           'bg-slate-50 text-slate-600 border-slate-200'"
                >
                  {{ getStatusLabel(event.status) }}
                </span>
              </td>
              <td class="p-4">
                <span
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                  :class="event.is_active
                    ? 'bg-sky-50 text-sky-700 border border-sky-200'
                    : 'bg-slate-100 text-slate-500 border border-slate-200'"
                >
                  {{ event.is_active ? 'Tampil' : 'Sembunyi' }}
                </span>
              </td>
              <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="openEditModal(event)"
                    class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors border border-slate-200"
                    title="Edit Event"
                  >
                    <Edit class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="handleDelete(event)"
                    class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg transition-colors border border-rose-200"
                    title="Hapus Event"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
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
          <div class="grid grid-cols-2 gap-4">
            <!-- Phase -->
            <div>
              <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">Fase (ex: 01, 1)</label>
              <input
                type="text"
                v-model="form.phase"
                placeholder="Fase timeline (contoh: 01)"
                class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all"
              />
            </div>

            <!-- Status -->
            <div>
              <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">Status</label>
              <select
                v-model="form.status"
                class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all"
              >
                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </div>
          </div>

          <!-- Title -->
          <div>
            <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">Judul Rangkaian</label>
            <input
              type="text"
              v-model="form.title"
              placeholder="Contoh: Identity & Foundation"
              class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all"
            />
          </div>

          <!-- Date Range -->
          <div>
            <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">Rentang Tanggal/Bulan</label>
            <input
              type="text"
              v-model="form.date_range"
              placeholder="Contoh: Januari - Maret"
              class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all"
            />
          </div>

          <!-- Description Items -->
          <div>
            <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">Item Deskripsi Detail (1 Baris per Item)</label>
            <textarea
              v-model="form.description_items_raw"
              rows="4"
              placeholder="Januari: Pembentukan Tim Inti
Februari: Penyusunan Proposal Kegiatan"
              class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all resize-none"
            ></textarea>
            <p class="text-[9px] text-on-surface-variant/50 mt-1 font-medium">Format: [Bulan/Kegiatan]: [Penjelasan]. Contoh: "Maret: Launching Website"</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <!-- Accent Color -->
            <div>
              <label class="block text-[10px] uppercase tracking-wider text-on-surface-variant/80 mb-1.5 font-black">Warna Aksen</label>
              <div class="flex items-center gap-2">
                <input
                  type="color"
                  v-model="form.accent_color"
                  class="w-10 h-9 border-2 border-[#04000D] rounded-xl cursor-pointer p-0.5"
                />
                <input
                  type="text"
                  v-model="form.accent_color"
                  placeholder="#8B5CF6"
                  class="w-full bg-white border-2 border-[#04000D] rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:shadow-[2px_2px_0px_0px_#FF3D8B] transition-all"
                />
              </div>
            </div>

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
