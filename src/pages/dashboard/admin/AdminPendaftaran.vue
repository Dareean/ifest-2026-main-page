<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../../utils/api'
import { Search, Filter, Clock, CheckCircle, AlertTriangle, Download } from 'lucide-vue-next'

const router = useRouter()
const loading = ref(true)
const data = ref(null)
const statusFilter = ref('')
const searchQuery = ref('')
const lombas = ref([])
const selectedLomba = ref('')

async function fetch() {
  loading.value = true
  try {
    const params = { per_page: 50 }
    if (statusFilter.value) params.status = statusFilter.value
    if (selectedLomba.value) params.lomba_id = selectedLomba.value
    if (searchQuery.value) params.search = searchQuery.value
    const res = await api.get('/admin/pendaftarans', { params })
    data.value = res.data
  } catch (e) {
    console.error(e)
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

function exportCsv() {
  const token = localStorage.getItem('auth_token')
  const params = new URLSearchParams()
  if (statusFilter.value) params.set('status', statusFilter.value)
  if (selectedLomba.value) params.set('lomba_id', selectedLomba.value)

  fetch(`${import.meta.env.VITE_API_URL || ''}/api/admin/pendaftarans/export?${params}`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  .then(res => res.blob())
  .then(blob => {
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `daftar_peserta_${new Date().toISOString().slice(0,10)}.csv`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  })
  .catch(console.error)
}

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
      <div class="grid grid-cols-2 sm:flex sm:flex-row gap-2 sm:gap-3">
        <div class="relative col-span-2 sm:flex-1">
          <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
          <input v-model="searchQuery" @input="fetch" placeholder="Cari nama tim, peserta, email..." class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 pl-10 pr-4 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all" />
        </div>
        <select v-model="statusFilter" @change="fetch" class="bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-3 sm:px-4 text-xs font-semibold text-on-surface focus:outline-none">
          <option value="">Semua Status</option>
          <option value="pending">Pending</option>
          <option value="verified">Terverifikasi</option>
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
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Tim</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Ketua</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Lomba</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Pembayaran</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Status</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Tanggal</th>
              <th class="text-right px-5 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="reg in data.data" :key="reg.id" class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors cursor-pointer" @click="goToDetail(reg.id)">
              <td class="px-5 py-3.5 font-bold text-on-surface">{{ reg.team_name || '-' }}</td>
              <td class="px-5 py-3.5">
                <p class="font-semibold text-on-surface">{{ reg.user?.name }}</p>
                <p class="font-mono text-[10px] text-on-surface-variant/60">{{ reg.user?.email }}</p>
              </td>
              <td class="px-5 py-3.5">
                <span class="font-mono text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-slate-100 text-on-surface-variant">{{ reg.lomba?.kode }}</span>
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
          <div class="flex items-start justify-between gap-2 mb-2">
            <p class="font-bold text-sm text-on-surface leading-tight">{{ reg.team_name || '-' }}</p>
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
  </div>
</template>
