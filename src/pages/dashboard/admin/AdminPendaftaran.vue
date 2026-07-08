<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useConfirm } from '../../../composables/useConfirm'
import { useToast } from '../../../composables/useToast'
import api from '../../../utils/api'
import { Search, Filter, Clock, CheckCircle, AlertTriangle, Download, Check, X } from 'lucide-vue-next'

const router = useRouter()
const { showToast } = useToast()
const confirmModal = useConfirm()
const loading = ref(true)
const data = ref(null)
const statusFilter = ref('')
const paymentFilter = ref('')
const gelombangFilter = ref('')
const searchQuery = ref('')
const lombas = ref([])
const selectedLomba = ref('')
const currentPage = ref(1)

// Bulk selection
const selectedIds = ref([])
const selectAll = ref(false)

const allIds = computed(() => data.value?.data?.map(r => r.id) || [])

function toggleSelectAll() {
  if (selectedIds.value.length === allIds.value.length) {
    selectedIds.value = []
    selectAll.value = false
  } else {
    selectedIds.value = [...allIds.value]
    selectAll.value = true
  }
}

function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id)
  if (idx > -1) {
    selectedIds.value.splice(idx, 1)
  } else {
    selectedIds.value.push(id)
  }
  selectAll.value = selectedIds.value.length === allIds.value.length
}

async function batchVerify() {
  if (!selectedIds.value.length) return
  if (!await confirmModal.confirm(`Verifikasi ${selectedIds.value.length} pendaftaran?`, 'Verifikasi Massal')) return
  try {
    const res = await api.put('/admin/pendaftarans/batch/verify', { ids: selectedIds.value })
    showToast(res.data.message, 'success')
    selectedIds.value = []
    selectAll.value = false
    await fetch()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal', 'error')
  }
}

async function batchReject() {
  if (!selectedIds.value.length) return
  if (!await confirmModal.confirm(`Tolak ${selectedIds.value.length} pendaftaran?`, 'Tolak Massal')) return
  try {
    const res = await api.put('/admin/pendaftarans/batch/reject', { ids: selectedIds.value })
    showToast(res.data.message, 'success')
    selectedIds.value = []
    selectAll.value = false
    await fetch()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal', 'error')
  }
}

async function fetch() {
  loading.value = true
  try {
    const params = { per_page: 25, page: currentPage.value }
    if (statusFilter.value) params.status = statusFilter.value
    if (paymentFilter.value) params.payment_status = paymentFilter.value
    if (gelombangFilter.value) params.gelombang = gelombangFilter.value
    if (selectedLomba.value) params.lomba_id = selectedLomba.value
    if (searchQuery.value) params.search = searchQuery.value
    const res = await api.get('/admin/pendaftarans', { params })
    data.value = res.data
  } catch (e) {
    console.error('Gagal memuat daftar pendaftaran:', e)
    showToast('Gagal memuat data pendaftaran', 'error')
  } finally {
    loading.value = false
  }
}

async function fetchLombas() {
  try {
    const res = await api.get('/lombas')
    lombas.value = res.data.data
  } catch {}
}

function goToDetail(id) {
  router.push(`/dashboard/admin/pendaftaran/${id}`)
}

async function exportCsv() {
  try {
    const params = new URLSearchParams()
    if (statusFilter.value) params.set('status', statusFilter.value)
    if (paymentFilter.value) params.set('payment_status', paymentFilter.value)
    if (gelombangFilter.value) params.set('gelombang', gelombangFilter.value)
    if (selectedLomba.value) params.set('lomba_id', selectedLomba.value)

    showToast('Mengunduh CSV...', 'info')

    const res = await api.get(`/admin/pendaftarans/export?${params}`, {
      responseType: 'blob'
    })

    const blob = res.data
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `daftar_peserta_${new Date().toISOString().slice(0,10)}.csv`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (e) {
    showToast('Gagal mengunduh CSV', 'error')
    console.error('Export CSV error:', e)
  }
}

const lastPage = computed(() => data.value?.last_page || 1)

const visiblePages = computed(() => {
  const maxVisible = 5
  const last = lastPage.value
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(last, start + maxVisible - 1)
  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }
  return Array.from({ length: end - start + 1 }, (_, i) => start + i)
})

function goToPage(page) {
  currentPage.value = page
  fetch()
}

let searchTimeout = null
watch([statusFilter, paymentFilter, gelombangFilter, selectedLomba], () => {
  currentPage.value = 1
  fetch()
})

watch(searchQuery, () => {
  currentPage.value = 1
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetch(), 400)
})

const gelombangConfig = {
  1: { label: 'Gel. 1', class: 'bg-blue-50 text-blue-700 border-blue-200' },
  2: { label: 'Gel. 2', class: 'bg-purple-50 text-purple-700 border-purple-200' },
}

const submissionExists = (reg) => reg.submission?.link_drive

const statusConfig = {
  pending: { icon: Clock, label: 'Pending', class: 'bg-[#FFF9E6] text-amber-600 border-amber-200' },
  verified: { icon: CheckCircle, label: 'Terverifikasi', class: 'bg-[#DCEEB1]/30 text-green-700 border-[#DCEEB1]' },
  rejected: { icon: AlertTriangle, label: 'Ditolak', class: 'bg-[#FF3D8B]/10 text-accent-magenta border-accent-magenta/20' },
}

const paymentStatusConfig = {
  unpaid: { icon: Clock, label: 'Belum Bayar', class: 'bg-slate-100 text-slate-500 border-slate-200' },
  pending: { icon: Clock, label: 'Pending', class: 'bg-[#FFF9E6] text-amber-600 border-amber-200' },
  verified: { icon: CheckCircle, label: 'Lunas', class: 'bg-[#DCEEB1]/30 text-green-700 border-[#DCEEB1]' },
  rejected: { icon: AlertTriangle, label: 'Ditolak', class: 'bg-[#FF3D8B]/10 text-accent-magenta border-accent-magenta/20' },
}

onMounted(() => {
  fetchLombas()
  fetch()
})
</script>

<template>
  <div>
    <div class="mb-8">
      <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Admin Panel</span>
      <h1 class="font-extrabold text-3xl md:text-4xl tracking-tight text-on-surface">Pendaftaran</h1>
    </div>

    <!-- Filters -->
    <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-4 sm:p-5 mb-6">
      <div class="grid grid-cols-2 sm:flex sm:flex-row gap-2 sm:gap-3 flex-wrap">
        <div class="relative col-span-2 sm:flex-1 min-w-[200px]">
          <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
          <input v-model="searchQuery" placeholder="Cari nama tim, peserta, email..." class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 pl-10 pr-4 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all" />
        </div>
        <select v-model="gelombangFilter" @change="fetch" class="bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 sm:px-4 text-xs font-semibold text-on-surface focus:outline-none">
          <option value="">Semua Gelombang</option>
          <option value="1">Gelombang 1</option>
          <option value="2">Gelombang 2</option>
        </select>
        <select v-model="statusFilter" @change="fetch" class="bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 sm:px-4 text-xs font-semibold text-on-surface focus:outline-none">
          <option value="">Semua Status</option>
          <option value="pending">Pending</option>
          <option value="verified">Terverifikasi</option>
          <option value="rejected">Ditolak</option>
        </select>
        <select v-model="paymentFilter" @change="fetch" class="bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 sm:px-4 text-xs font-semibold text-on-surface focus:outline-none">
          <option value="">Semua Pembayaran</option>
          <option value="unpaid">Belum Bayar</option>
          <option value="pending">Pending</option>
          <option value="verified">Lunas</option>
          <option value="rejected">Ditolak</option>
        </select>
        <select v-model="selectedLomba" @change="fetch" class="bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 sm:px-4 text-xs font-semibold text-on-surface focus:outline-none">
          <option value="">Semua Lomba</option>
          <option v-for="l in lombas" :key="l.id" :value="l.id">{{ l.kode }}</option>
        </select>
        <button @click="exportCsv" class="col-span-2 sm:col-auto inline-flex items-center justify-center gap-1.5 bg-[#04000D] hover:bg-black text-[#DCEEB1] rounded-xl py-2.5 px-4 text-xs font-bold transition-all shadow-sm flex-shrink-0 whitespace-nowrap">
          <Download class="w-3.5 h-3.5" /> Export CSV
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="h-16 bg-slate-50 border border-slate-100 rounded-2xl animate-pulse"></div>
    </div>

    <!-- Bulk Action Bar -->
    <div v-if="selectedIds.length > 0" class="flex items-center gap-3 mb-4 bg-[#04000D] text-white rounded-2xl px-5 py-3 shadow-sm">
      <span class="text-xs font-semibold">{{ selectedIds.length }} dipilih</span>
      <div class="flex-1"></div>
      <button @click="batchVerify" class="inline-flex items-center gap-1.5 bg-[#DCEEB1] hover:bg-[#DCEEB1]/80 text-on-surface px-4 py-2 rounded-xl text-xs font-bold transition-all">
        <Check class="w-3.5 h-3.5" /> Verifikasi
      </button>
      <button @click="batchReject" class="inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all">
        <X class="w-3.5 h-3.5" /> Tolak
      </button>
      <button @click="selectedIds = []; selectAll = false" class="text-white/50 hover:text-white/80 transition-colors text-[10px] font-semibold">Batal</button>
    </div>

    <!-- Empty -->
    <div v-else-if="!data?.data?.length" class="bg-white border border-[#04000D]/5 rounded-2xl p-12 text-center">
      <p class="text-sm text-on-surface-variant/60">Tidak ada pendaftaran</p>
    </div>

    <!-- Table (desktop) -->
    <div v-else class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl overflow-hidden">
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-xs">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/50">
              <th class="w-10 px-4 py-3">
                <input type="checkbox" :checked="selectAll" @change="toggleSelectAll" class="rounded border-slate-300 text-[#04000D] focus:ring-[#04000D]/30 cursor-pointer" />
              </th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Tim</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Ketua</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Lomba</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Gel.</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Karya</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Pembayaran</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Status</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Tanggal</th>
              <th class="text-right px-5 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="reg in data.data" :key="reg.id" class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors cursor-pointer" @click="goToDetail(reg.id)">
              <td class="px-4 py-3.5" @click.stop>
                <input type="checkbox" :checked="selectedIds.includes(reg.id)" @change="toggleSelect(reg.id)" class="rounded border-slate-300 text-[#04000D] focus:ring-[#04000D]/30 cursor-pointer" />
              </td>
              <td class="px-5 py-3.5 font-bold text-on-surface">{{ reg.team_name || '-' }}</td>
              <td class="px-5 py-3.5">
                <p class="font-semibold text-on-surface">{{ reg.user?.name }}</p>
                <p class="font-mono text-[10px] text-on-surface-variant/60">{{ reg.user?.email }}</p>
              </td>
              <td class="px-5 py-3.5">
                <span class="font-mono text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-slate-100 text-on-surface-variant">{{ reg.lomba?.kode }}</span>
              </td>
              <td class="px-5 py-3.5">
                <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border" :class="gelombangConfig[reg.gelombang]?.class || ''">
                  {{ gelombangConfig[reg.gelombang]?.label || reg.gelombang }}
                </span>
              </td>
              <td class="px-5 py-3.5">
                <span v-if="submissionExists(reg)" class="inline-flex items-center gap-1 font-mono text-[9px] font-bold text-green-600">
                  <Check class="w-2.5 h-2.5" /> Ada
                </span>
                <span v-else class="font-mono text-[9px] text-slate-400">-</span>
              </td>
              <td class="px-5 py-3.5">
                <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border" :class="paymentStatusConfig[reg.payment_status]?.class || ''">
                  <component :is="paymentStatusConfig[reg.payment_status]?.icon" class="w-2.5 h-2.5" />
                  {{ paymentStatusConfig[reg.payment_status]?.label }}
                </span>
              </td>
              <td class="px-5 py-3.5">
                <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border" :class="statusConfig[reg.status]?.class || ''">
                  <component :is="statusConfig[reg.status]?.icon" class="w-2.5 h-2.5" />
                  {{ statusConfig[reg.status]?.label }}
                </span>
              </td>
              <td class="px-5 py-3.5 font-mono text-[10px] text-on-surface-variant/60">{{ new Date(reg.created_at).toLocaleDateString('id-ID') }}</td>
              <td class="px-5 py-3.5 text-right">
                <span class="text-on-surface-variant/40 text-[10px] font-semibold">Detail &rarr;</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Cards (mobile) -->
      <div class="md:hidden divide-y divide-slate-100">
        <div v-for="reg in data.data" :key="reg.id" @click="goToDetail(reg.id)" class="p-4 hover:bg-slate-50/50 transition-colors cursor-pointer active:bg-slate-100">
          <div class="flex items-center gap-3 mb-2">
            <input type="checkbox" :checked="selectedIds.includes(reg.id)" @change.stop="toggleSelect(reg.id)" class="rounded border-slate-300 text-[#04000D] focus:ring-[#04000D]/30 cursor-pointer flex-shrink-0" />
            <p class="font-bold text-sm text-on-surface leading-tight flex-1 min-w-0">{{ reg.team_name || '-' }}</p>
            <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border flex-shrink-0" :class="statusConfig[reg.status]?.class || ''">
              <component :is="statusConfig[reg.status]?.icon" class="w-2.5 h-2.5" />
              {{ statusConfig[reg.status]?.label }}
            </span>
          </div>
          <div class="space-y-1 text-xs text-on-surface-variant/70">
            <p class="font-semibold text-on-surface">{{ reg.user?.name }}</p>
            <p class="font-mono text-[10px] truncate">{{ reg.user?.email }}</p>
            <div class="flex items-center gap-2 pt-1">
              <span class="font-mono text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-slate-100 text-on-surface-variant">{{ reg.lomba?.kode }}</span>
              <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border" :class="paymentStatusConfig[reg.payment_status]?.class || ''">
                <component :is="paymentStatusConfig[reg.payment_status]?.icon" class="w-2.5 h-2.5" />
                {{ paymentStatusConfig[reg.payment_status]?.label }}
              </span>
              <span class="text-[10px]">{{ new Date(reg.created_at).toLocaleDateString('id-ID') }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="data && lastPage > 1" class="flex items-center justify-between mt-6">
      <span class="text-xs text-on-surface-variant/60">Hal. {{ data.current_page }} dari {{ lastPage }} ({{ data.total }} total)</span>
      <div class="flex items-center gap-1">
        <button @click="goToPage(1)" :disabled="currentPage === 1" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-all disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-100" :class="currentPage === 1 ? 'text-slate-300' : 'text-on-surface'">&laquo;</button>
        <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-all disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-100" :class="currentPage === 1 ? 'text-slate-300' : 'text-on-surface'">&lsaquo;</button>
        <template v-for="p in visiblePages" :key="p">
          <button @click="goToPage(p)" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all" :class="currentPage === p ? 'bg-[#04000D] text-white' : 'text-on-surface hover:bg-slate-100'">{{ p }}</button>
        </template>
        <button @click="goToPage(currentPage + 1)" :disabled="currentPage === lastPage" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-all disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-100" :class="currentPage === lastPage ? 'text-slate-300' : 'text-on-surface'">&rsaquo;</button>
        <button @click="goToPage(lastPage)" :disabled="currentPage === lastPage" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-all disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-100" :class="currentPage === lastPage ? 'text-slate-300' : 'text-on-surface'">&raquo;</button>
      </div>
    </div>
  </div>
</template>
