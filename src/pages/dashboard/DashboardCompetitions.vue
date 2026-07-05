<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useConfirm } from '../../composables/useConfirm'
import { useToast } from '../../composables/useToast'
import api from '../../utils/api'
import { useAuthStore } from '../../stores/auth'
import {
  Trophy, Plus, ExternalLink, CheckCircle, Clock, AlertTriangle,
  Send, X, Users, BookOpen, Calendar, ArrowLeft,
  ChevronRight, Award, FileText, Printer, Lock, Unlock, UserMinus, Mail, Check
} from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const confirmModal = useConfirm()
const { showToast } = useToast()
const pendaftarans = ref([])
const loading = ref(true)
const lombaList = ref([])

// Tab navigation & detail view state
const selectedLombaForDetail = ref(null)
const activeTab = ref('info')

// Forms state
const daftarForm = ref({ team_name: '', team_members: [] })
const submitForm = ref({ link_drive: '', catatan: '' })
const submitting = ref(false)
const submittingSubmit = ref(false)
const error = ref('')
const submitError = ref('')

const invitations = ref([])
const teamInvitations = ref([])
const inviteEmail = ref('')
const inviting = ref(false)
const inviteError = ref('')
const inviteSuccess = ref('')
const actionLoading = ref(null)

const paymentLink = ref('')
const uploadingPayment = ref(false)
const paymentUploadError = ref('')
const paymentUploadSuccess = ref('')

const isTeamLocked = computed(() => {
  return getRegistration(selectedLombaForDetail.value?.id)?.team_locked ?? true
})

const isFull = computed(() => {
  const max = getMaxMembers(selectedLombaForDetail?.team_requirements)
  const accepted = teamInvitations.value.filter(i => i.status === 'accepted').length
  return 1 + accepted >= max
})

const emptySlots = computed(() => {
  const max = getMaxMembers(selectedLombaForDetail?.team_requirements)
  const accepted = teamInvitations.value.filter(i => i.status === 'accepted').length
  return Math.max(0, max - 1 - accepted)
})

const getMaxMembers = (req) => {
  if (!req) return 3
  if (req.toLowerCase().includes('individu')) return 1
  const matches = req.match(/(\d+)\s*-\s*(\d+)/)
  if (matches) return parseInt(matches[2], 10)
  const matchesSingle = req.match(/(\d+)/)
  if (matchesSingle) return parseInt(matchesSingle[1], 10)
  return 3
}

// Competition opening dates config
const openDates = {
  'NAT-01': new Date('2025-01-01T00:00:00'),
  'NAT-02': new Date('2025-01-01T00:00:00'),
  'NAT-03': new Date('2025-01-01T00:00:00'),
  'REG-01': new Date('2025-01-01T00:00:00'),
  'REG-02': new Date('2025-01-01T00:00:00'),
  'REG-03': new Date('2025-01-01T00:00:00'),
}

const now = ref(new Date())
let timerId = null

const anggotaVisible = computed(() => {
  const reg = getRegistration(selectedLombaForDetail.value?.id)
  return !!reg && (reg.payment_status === 'verified' || reg.status === 'verified')
})

const availableTabs = computed(() => {
  const tabs = ['info', 'timeline', 'team']
  if (selectedLombaForDetail.value) {
    const reg = getRegistration(selectedLombaForDetail.value.id)
    if (reg) {
      if (reg.payment_status === 'verified' || reg.status === 'verified') {
        tabs.push('anggota')
      }
      if (reg.status === 'verified') {
        tabs.push('submit')
      }
    }
  }
  return tabs
})

const isLombaOpen = (lomba) => {
  if (!lomba) return false
  const openDate = openDates[lomba.kode]
  if (!openDate) return true
  return now.value >= openDate
}

const getLombaCountdownText = (lomba) => {
  if (!lomba) return ''
  const openDate = openDates[lomba.kode]
  if (!openDate) return ''
  const diff = openDate - now.value
  if (diff <= 0) return ''
  
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  
  if (days > 0) {
    return `${days}d : ${hours}h`
  }
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
  const seconds = Math.floor((diff % (1000 * 60)) / 1000)
  return `${hours}h : ${minutes}m : ${seconds}s`
}

const getRegistration = (lombaId) => {
  if (!lombaId) return null
  return pendaftarans.value.find(p => p.lomba_id === lombaId)
}

const sudahTerdaftar = (lombaId) => {
  if (!lombaId) return false
  return !!getRegistration(lombaId)
}

const statusConfig = {
  pending: { icon: Clock, label: 'Pending', class: 'bg-[#FFF9E6] text-on-surface border-amber-200' },
  verified: { icon: CheckCircle, label: 'Terverifikasi', class: 'bg-[#DCEEB1] text-on-surface border-[#DCEEB1]' },
  rejected: { icon: AlertTriangle, label: 'Ditolak', class: 'bg-[#FF3D8B]/10 text-accent-magenta border-accent-magenta/20' },
}

const paymentStatusConfig = {
  unpaid: { icon: Clock, label: 'Belum Bayar', class: 'bg-slate-100 text-on-surface-variant border-slate-200' },
  pending: { icon: Clock, label: 'Pending', class: 'bg-[#FFF9E6] text-on-surface border-amber-200' },
  verified: { icon: CheckCircle, label: 'Terverifikasi', class: 'bg-[#DCEEB1] text-on-surface border-[#DCEEB1]' },
  rejected: { icon: AlertTriangle, label: 'Ditolak', class: 'bg-[#FF3D8B]/10 text-accent-magenta border-accent-magenta/20' },
}

async function fetchInvitations() {
  try {
    const res = await api.get('/invitations/pending')
    invitations.value = res.data.data
    localStorage.setItem('cached_invitations', JSON.stringify(res.data.data))
  } catch (e) {
    console.error(e)
  }
}

async function fetchTeamInvitations() {
  if (!selectedLombaForDetail.value) return
  const reg = getRegistration(selectedLombaForDetail.value.id)
  if (!reg) return
  try {
    const res = await api.get(`/pendaftarans/${reg.id}/invitations`)
    teamInvitations.value = res.data.data
  } catch (e) {
    console.error(e)
  }
}

async function handleUploadPayment() {
  const reg = getRegistration(selectedLombaForDetail.value?.id)
  if (!reg || !paymentLink.value) return
  uploadingPayment.value = true
  paymentUploadError.value = ''
  paymentUploadSuccess.value = ''
  try {
    await api.post(`/pendaftarans/${reg.id}/payment/upload`, { payment_proof: paymentLink.value })
    paymentUploadSuccess.value = 'Bukti pembayaran berhasil dikirim!'
    paymentLink.value = ''
    await fetchData()
  } catch (e) {
    paymentUploadError.value = e.response?.data?.message || e.response?.data?.errors?.payment_proof?.[0] || 'Gagal mengirim link bukti bayar'
  } finally {
    uploadingPayment.value = false
  }
}

async function fetchData() {
  try {
    const [pendaftaranRes, lombaRes, invitationsRes] = await Promise.all([
      api.get('/pendaftarans'),
      api.get('/lombas'),
      api.get('/invitations/pending')
    ])
    pendaftarans.value = pendaftaranRes.data.data
    lombaList.value = lombaRes.data.data
    invitations.value = invitationsRes.data.data
    // Cache the data
    localStorage.setItem('cached_pendaftarans', JSON.stringify(pendaftaranRes.data.data))
    localStorage.setItem('cached_lombas', JSON.stringify(lombaRes.data.data))
    localStorage.setItem('cached_invitations', JSON.stringify(invitationsRes.data.data))
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

// Open detailed page for specific competition
function openDetail(lomba) {
  selectedLombaForDetail.value = lomba
  activeTab.value = 'info'
  error.value = ''
  submitError.value = ''
  
  // Set up forms
  const reg = getRegistration(lomba?.id)
  if (reg) {
    submitForm.value = {
      link_drive: reg.submission?.link_drive || '',
      catatan: reg.submission?.catatan || ''
    }
  } else {
    daftarForm.value = { team_name: '', team_members: [] }
  }
}

function closeDetail() {
  selectedLombaForDetail.value = null
  router.push('/dashboard')
}

async function handleDaftar() {
  if (!selectedLombaForDetail.value) return
  error.value = ''
  submitting.value = true
  try {
    await api.post(`/lombas/${selectedLombaForDetail.value.id}/daftar`, {
      team_name: daftarForm.value.team_name || null,
      team_members: daftarForm.value.team_members.length > 0 ? daftarForm.value.team_members : null,
    })
    daftarForm.value = { team_name: '', team_members: [] }
    await fetchData()
    // reload state
    const updated = lombaList.value.find(l => l.id === selectedLombaForDetail.value.id)
    if (updated) openDetail(updated)
  } catch (e) {
    const data = e.response?.data
    if (data?.errors) {
      error.value = Object.values(data.errors).flat().join('. ')
    } else {
      error.value = data?.message || 'Gagal mendaftar'
    }
  } finally {
    submitting.value = false
  }
}

function addMember() {
  daftarForm.value.team_members.push({ name: '', email: '' })
}

function removeMember(i) {
  daftarForm.value.team_members.splice(i, 1)
}

async function handleSubmitKarya() {
  const reg = getRegistration(selectedLombaForDetail.value?.id)
  if (!reg || !submitForm.value.link_drive) return
  submitError.value = ''
  submittingSubmit.value = true
  try {
    await api.post(`/lombas/${selectedLombaForDetail.value.id}/submit`, {
      link_drive: submitForm.value.link_drive,
      catatan: submitForm.value.catatan || null,
    })
    await fetchData()
    // refresh detail state
    openDetail(selectedLombaForDetail.value)
  } catch (e) {
    submitError.value = e.response?.data?.message || 'Gagal mengumpulkan karya'
  } finally {
    submittingSubmit.value = false
  }
}

async function handleInvite() {
  if (!selectedLombaForDetail.value || !inviteEmail.value) return
  inviteError.value = ''
  inviteSuccess.value = ''
  inviting.value = true
  const reg = getRegistration(selectedLombaForDetail.value.id)
  try {
    const res = await api.post(`/pendaftarans/${reg.id}/invite`, { email: inviteEmail.value })
    inviteSuccess.value = 'Undangan berhasil dikirim!'
    inviteEmail.value = ''

    // Optimistic update — patch local state directly from API response, no full reload
    const updatedPendaftaran = res.data.data
    if (updatedPendaftaran) {
      const idx = pendaftarans.value.findIndex(p => p.id === reg.id)
      if (idx !== -1) {
        pendaftarans.value[idx] = updatedPendaftaran
        localStorage.setItem('cached_pendaftarans', JSON.stringify(pendaftarans.value))
      }
    }
    // Background refresh to ensure server truth without blocking UI
    fetchData()
    fetchTeamInvitations()
  } catch (e) {
    inviteError.value = e.response?.data?.message || 'Gagal mengirim undangan'
  } finally {
    inviting.value = false
  }
}

async function handleCancelInvite(invitationId) {
  if (!await confirmModal.confirm('Batalkan undangan ini?', 'Batalkan Undangan?')) return
  try {
    await api.put(`/invitations/${invitationId}/reject`)
    fetchTeamInvitations()
  } catch (e) {
    console.error(e)
  }
}

async function handleAcceptInvite(invitationId) {
  actionLoading.value = invitationId
  try {
    await api.put(`/invitations/${invitationId}/accept`)
    await fetchInvitations()
    await fetchData()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal menerima undangan', 'error')
  } finally {
    actionLoading.value = null
  }
}

async function handleDeclineInvite(invitationId) {
  actionLoading.value = invitationId
  try {
    await api.put(`/invitations/${invitationId}/reject`)
    await fetchInvitations()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal menolak undangan', 'error')
  } finally {
    actionLoading.value = null
  }
}

async function handleRemoveMember(memberId) {
  if (!selectedLombaForDetail.value) return
  const reg = getRegistration(selectedLombaForDetail.value.id)
  if (!await confirmModal.confirm('Apakah Anda yakin ingin mengeluarkan anggota ini?', 'Keluarkan Anggota?')) return
  actionLoading.value = memberId
  try {
    await api.delete(`/pendaftarans/${reg.id}/members/${memberId}`)
    await fetchData()
    const updated = lombaList.value.find(l => l.id === selectedLombaForDetail.value.id)
    if (updated) selectedLombaForDetail.value = updated
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal mengeluarkan anggota', 'error')
  } finally {
    actionLoading.value = null
  }
}

function goToTeamPage() {
  const reg = getRegistration(selectedLombaForDetail.value?.id)
  if (reg) {
    router.push(`/dashboard/tim/${reg.id}`)
  }
}

async function handleRequestChanges() {
  if (!selectedLombaForDetail.value) return
  const reg = getRegistration(selectedLombaForDetail.value.id)
  if (!await confirmModal.confirm('Ajukan permohonan buka kunci tim ke admin?', 'Ajukan Buka Kunci?')) return
  try {
    await api.post(`/pendaftarans/${reg.id}/request-changes`)
    reg.unlock_requested = true
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal mengirim permohonan', 'error')
  }
}

const autoLockRemaining = ref(0)
let autoLockTimer = null

function startAutoLockCountdown(autoLockAt) {
  if (autoLockTimer) clearInterval(autoLockTimer)
  if (!autoLockAt) {
    autoLockRemaining.value = 0
    return
  }
  function tick() {
    const diff = new Date(autoLockAt).getTime() - Date.now()
    if (diff <= 0) {
      autoLockRemaining.value = 0
      clearInterval(autoLockTimer)
      return
    }
    autoLockRemaining.value = Math.ceil(diff / 1000)
  }
  tick()
  autoLockTimer = setInterval(tick, 1000)
}

onMounted(() => {
  // Load from Cache first for instant render
  const cachedPendaftarans = localStorage.getItem('cached_pendaftarans')
  const cachedLombas = localStorage.getItem('cached_lombas')
  const cachedInvitations = localStorage.getItem('cached_invitations')
  if (cachedPendaftarans && cachedLombas) {
    pendaftarans.value = JSON.parse(cachedPendaftarans)
    lombaList.value = JSON.parse(cachedLombas)
    if (cachedInvitations) {
      invitations.value = JSON.parse(cachedInvitations)
    }
    loading.value = false
  } else {
    loading.value = true
  }

  fetchData().then(() => {
    // Open competition from query param
    if (route.query.id) {
      const found = lombaList.value.find(l => l.id == route.query.id)
      if (found) openDetail(found)
    }
  })
  timerId = setInterval(() => {
    now.value = new Date()
  }, 1000)
})

watch(selectedLombaForDetail, (lomba) => {
  if (!lomba) return
  const reg = getRegistration(lomba.id)
  if (reg) {
    if (reg.payment_status === 'verified' || reg.status === 'verified') {
      fetchTeamInvitations()
    }
    if (reg.auto_lock_at) {
      startAutoLockCountdown(reg.auto_lock_at)
    }
  }
})

onUnmounted(() => {
  if (timerId) clearInterval(timerId)
  if (autoLockTimer) clearInterval(autoLockTimer)
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-8">
      <template v-if="selectedLombaForDetail">
        <button @click="closeDetail" class="inline-flex items-center gap-1.5 font-mono text-[10px] font-bold uppercase tracking-widest text-on-surface-variant hover:text-on-surface transition-colors mb-3 group">
          <ArrowLeft class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-0.5" /> Kembali ke Overview
        </button>
        <div>
          <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">{{ selectedLombaForDetail.kode }}</span>
          <h1 class="font-extrabold text-2xl md:text-3xl tracking-tight text-on-surface leading-tight">{{ selectedLombaForDetail.title }}</h1>
        </div>
      </template>
      <template v-else>
        <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Panel Manajemen</span>
        <h1 class="font-extrabold text-3xl md:text-4xl tracking-tight text-on-surface">Lomba</h1>
      </template>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="h-28 bg-slate-50 border border-slate-100 rounded-2xl animate-pulse"></div>
    </div>

    <!-- Fallback: No competition selected -->
    <div v-else-if="!selectedLombaForDetail" class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-12 md:p-16 text-center">
      <Trophy class="w-10 h-10 text-on-surface-variant/20 mx-auto mb-4" />
      <h2 class="font-extrabold text-lg text-on-surface mb-2">Belum Ada Lomba Dipilih</h2>
      <p class="text-xs text-on-surface-variant/60 max-w-sm mx-auto leading-relaxed">
        Pilih lomba dari halaman Overview untuk melihat detail dan mengelola pendaftaran.
      </p>
      <router-link to="/dashboard" class="inline-flex items-center gap-1.5 mt-6 bg-[#04000D] hover:bg-black text-[#DCEEB1] px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm">
        <ArrowLeft class="w-3.5 h-3.5" /> Ke Overview
      </router-link>
    </div>

    <!-- View: Focused Detail View -->
    <div v-if="selectedLombaForDetail" class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6 md:p-8">
      <!-- Tabs -->
      <div class="flex border-b border-slate-100 overflow-x-auto gap-6 mb-8 scrollbar-none">
        <button
          v-for="tab in availableTabs"
          :key="tab"
          @click="activeTab = tab"
          :disabled="tab === 'submit' && (!sudahTerdaftar(selectedLombaForDetail?.id) || getRegistration(selectedLombaForDetail?.id)?.status !== 'verified')"
          class="py-3 px-1 border-b-2 font-bold text-xs uppercase tracking-wider transition-all whitespace-nowrap"
          :class="[
            activeTab === tab 
              ? 'border-black text-on-surface' 
              : 'border-transparent text-on-surface-variant/60 hover:text-on-surface',
            tab === 'submit' && (!sudahTerdaftar(selectedLombaForDetail?.id) || getRegistration(selectedLombaForDetail?.id)?.status !== 'verified')
              ? 'opacity-40 cursor-not-allowed'
              : '',
            tab === 'anggota' && !anggotaVisible ? 'hidden' : ''
          ]"
        >
          {{ 
            tab === 'info' ? 'Detail & Juknis' :
            tab === 'timeline' ? 'Timeline' :
            tab === 'team' ? 'Pendaftaran' :
            tab === 'anggota' ? 'Anggota Tim' : 'Pengumpulan Karya'
          }}
        </button>
      </div>

      <!-- Tab Content: Info & Juknis -->
      <div v-if="activeTab === 'info'" class="space-y-6">
        <div>
          <h3 class="font-extrabold text-lg text-on-surface mb-2">{{ selectedLombaForDetail?.tagline }}</h3>
          <p class="text-xs text-on-surface-variant/80 leading-relaxed">{{ selectedLombaForDetail?.long_description }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 border-y border-slate-100 py-6">
          <div>
            <span class="text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider">Persyaratan Tim</span>
            <p class="text-xs font-bold text-on-surface mt-0.5">{{ selectedLombaForDetail?.team_requirements }}</p>
          </div>
          <div>
            <span class="text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider">Target Peserta</span>
            <p class="text-xs font-bold text-on-surface mt-0.5">{{ selectedLombaForDetail?.target }}</p>
          </div>
          <div>
            <span class="text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider">Media/Platform</span>
            <p class="text-xs font-bold text-on-surface mt-0.5 leading-snug">{{ selectedLombaForDetail?.languages }}</p>
          </div>
        </div>

        <!-- Rules -->
        <div v-if="selectedLombaForDetail?.rules">
          <h4 class="font-extrabold text-sm text-on-surface mb-3 flex items-center gap-2">
            <Award class="w-4 h-4 text-accent-magenta" /> Aturan Kompetisi
          </h4>
          <ul class="space-y-2">
            <li 
              v-for="(rule, idx) in JSON.parse(selectedLombaForDetail.rules || '[]')" 
              :key="idx" 
              class="text-xs text-on-surface-variant/80 flex items-start gap-2.5 leading-relaxed"
            >
              <span class="font-mono text-accent-magenta font-black mt-0.5">{{ idx + 1 }}.</span>
              <span>{{ rule }}</span>
            </li>
          </ul>
        </div>

        <!-- Juknis button -->
        <div v-if="selectedLombaForDetail?.guidebook_link" class="pt-2">
          <a 
            :href="selectedLombaForDetail.guidebook_link.startsWith('http') ? selectedLombaForDetail.guidebook_link : '/' + selectedLombaForDetail.guidebook_link" 
            target="_blank" 
            class="inline-flex items-center gap-2 bg-slate-50 hover:bg-slate-100 text-on-surface border border-slate-200 px-5 py-3 rounded-xl text-xs font-bold transition-all shadow-sm"
          >
            <BookOpen class="w-4 h-4" /> Download Petunjuk Teknis (Juknis)
          </a>
        </div>
      </div>

      <!-- Tab Content: Timeline -->
      <div v-else-if="activeTab === 'timeline'" class="space-y-8 max-w-lg">
        <div v-if="selectedLombaForDetail?.schedule" class="relative pl-6 border-l-2 border-slate-100 space-y-8 ml-3 py-2">
          <div 
            v-for="(sched, i) in selectedLombaForDetail.schedule.split('|')" 
            :key="i"
            class="relative"
          >
            <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-white border-2 border-accent-magenta flex items-center justify-center">
              <div class="w-1.5 h-1.5 rounded-full bg-accent-magenta animate-ping" v-if="i === 0"></div>
            </div>
            <div>
              <span class="font-mono text-[9px] font-bold uppercase text-accent-magenta tracking-wider">Tahap {{ i + 1 }}</span>
              <p class="text-xs font-bold text-on-surface mt-0.5 leading-snug">{{ sched.trim().split(':')[0] }}</p>
              <p class="text-xs text-on-surface-variant/80 mt-0.5">{{ sched.trim().split(':')[1] || sched }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab Content: Registrasi & Tim -->
      <div v-else-if="activeTab === 'team'" class="space-y-6">
        <!-- Status: Registered -->
        <div v-if="sudahTerdaftar(selectedLombaForDetail?.id)">
          <div
            class="p-4 border rounded-2xl flex items-start gap-3 shadow-sm text-xs"
            :class="statusConfig[getRegistration(selectedLombaForDetail?.id)?.status]?.class || ''"
          >
            <component :is="statusConfig[getRegistration(selectedLombaForDetail?.id)?.status]?.icon || Clock" class="w-5 h-5 mt-0.5 flex-shrink-0" />
            <div class="flex-1 min-w-0">
              <p class="font-bold">Status: {{ statusConfig[getRegistration(selectedLombaForDetail?.id)?.status]?.label }}</p>
              <div v-if="getRegistration(selectedLombaForDetail?.id)?.gelombang" class="flex items-center gap-1.5 mt-1.5">
                <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full" :class="getRegistration(selectedLombaForDetail?.id)?.gelombang === '1' ? 'bg-pink-100 text-pink-700 border border-pink-200' : 'bg-blue-100 text-blue-700 border border-blue-200'">
                  Gelombang {{ getRegistration(selectedLombaForDetail?.id)?.gelombang }}
                </span>
              </div>

              <!-- Pending + Unpaid (not free) → show upload payment -->
              <template v-if="getRegistration(selectedLombaForDetail?.id)?.status === 'pending' && getRegistration(selectedLombaForDetail?.id)?.payment_status === 'unpaid'">
                <div v-if="selectedLombaForDetail?.fee && selectedLombaForDetail.fee.toLowerCase() !== 'gratis'" class="mt-3">
                  <p class="text-[11px] opacity-80 leading-relaxed">Silakan upload bukti pembayaran untuk melanjutkan proses verifikasi.</p>
                  <div class="mt-3 bg-white/60 rounded-xl p-3 border border-dashed border-slate-200/60 space-y-2.5">
                    <div v-if="paymentUploadError" class="bg-accent-magenta/5 border border-accent-magenta/20 rounded-lg px-3 py-2 text-[11px] font-semibold text-accent-magenta">{{ paymentUploadError }}</div>
                    <div v-if="paymentUploadSuccess" class="bg-[#DCEEB1]/20 border border-[#DCEEB1]/40 rounded-lg px-3 py-2 text-[11px] font-semibold text-on-surface">{{ paymentUploadSuccess }}</div>
                    <div>
                      <label class="text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider mb-1.5 block">Link Google Drive Bukti Bayar</label>
                      <input v-model="paymentLink" placeholder="https://drive.google.com/file/d/..." class="w-full bg-white border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 px-3 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all" />
                    </div>
                    <button @click="handleUploadPayment" :disabled="uploadingPayment || !paymentLink" class="w-full bg-[#04000D] hover:bg-black text-[#DCEEB1] py-2.5 rounded-xl text-xs font-bold transition-all disabled:opacity-40 flex items-center justify-center gap-1.5">
                      {{ uploadingPayment ? 'Mengirim...' : 'Kirim Bukti Bayar' }}
                    </button>
                  </div>
                </div>
              </template>

              <!-- Pending + Payment Pending -->
              <template v-else-if="getRegistration(selectedLombaForDetail?.id)?.status === 'pending' && getRegistration(selectedLombaForDetail?.id)?.payment_status === 'pending'">
                <div class="mt-3 space-y-3">
                  <p class="text-[11px] opacity-80 leading-relaxed">Bukti pembayaran sedang diverifikasi oleh admin.</p>
                  <div v-if="getRegistration(selectedLombaForDetail?.id)?.payment_proof" class="bg-white/60 rounded-xl p-3 border border-slate-200/60">
                    <span class="text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider">Link Bukti Bayar</span>
                    <a :href="getRegistration(selectedLombaForDetail?.id)?.payment_proof" target="_blank" class="flex items-center gap-2 mt-1.5 text-xs font-bold text-sky-600 hover:underline truncate">
                      {{ getRegistration(selectedLombaForDetail?.id)?.payment_proof }}
                    </a>
                  </div>
                </div>
              </template>

              <!-- Pending + Payment Rejected -->
              <template v-else-if="getRegistration(selectedLombaForDetail?.id)?.status === 'pending' && getRegistration(selectedLombaForDetail?.id)?.payment_status === 'rejected'">
                <div class="mt-3">
                  <p class="text-[11px] opacity-80 leading-relaxed">Bukti pembayaran ditolak.</p>
                  <div v-if="getRegistration(selectedLombaForDetail?.id)?.payment_notes" class="mt-2 bg-accent-magenta/5 border border-accent-magenta/20 rounded-lg px-3 py-2 text-[11px]">
                    <span class="font-bold text-accent-magenta">Alasan:</span>
                    <span class="text-on-surface-variant/80 ml-1">{{ getRegistration(selectedLombaForDetail?.id)?.payment_notes }}</span>
                  </div>
                  <p class="text-[11px] mt-2">Silakan kirim ulang link bukti pembayaran yang valid.</p>
                  <div class="mt-3 bg-white/60 rounded-xl p-3 border border-dashed border-slate-200/60 space-y-2.5">
                    <div v-if="paymentUploadError" class="bg-accent-magenta/5 border border-accent-magenta/20 rounded-lg px-3 py-2 text-[11px] font-semibold text-accent-magenta">{{ paymentUploadError }}</div>
                    <div v-if="paymentUploadSuccess" class="bg-[#DCEEB1]/20 border border-[#DCEEB1]/40 rounded-lg px-3 py-2 text-[11px] font-semibold text-on-surface">{{ paymentUploadSuccess }}</div>
                    <div>
                      <label class="text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider mb-1.5 block">Link Google Drive Bukti Bayar</label>
                      <input v-model="paymentLink" placeholder="https://drive.google.com/file/d/..." class="w-full bg-white border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 px-3 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all" />
                    </div>
                    <button @click="handleUploadPayment" :disabled="uploadingPayment || !paymentLink" class="w-full bg-[#04000D] hover:bg-black text-[#DCEEB1] py-2.5 rounded-xl text-xs font-bold transition-all disabled:opacity-40 flex items-center justify-center gap-1.5">
                      {{ uploadingPayment ? 'Mengirim...' : 'Kirim Ulang Bukti Bayar' }}
                    </button>
                  </div>
                </div>
              </template>

              <!-- Pending + Payment Verified (waiting for team verification) -->
              <template v-else-if="getRegistration(selectedLombaForDetail?.id)?.status === 'pending' && getRegistration(selectedLombaForDetail?.id)?.payment_status === 'verified'">
                <p class="text-[11px] opacity-80 mt-1 leading-relaxed">Pembayaran terverifikasi! Silakan undang anggota tim Anda pada tab Anggota Tim.</p>
              </template>

              <!-- Free competition (auto payment verified) -->
              <template v-else-if="getRegistration(selectedLombaForDetail?.id)?.status === 'pending' && selectedLombaForDetail?.fee && selectedLombaForDetail.fee.toLowerCase() === 'gratis'">
                <p class="text-[11px] opacity-80 mt-1 leading-relaxed">Lomba gratis — pendaftaran Anda sedang menunggu verifikasi admin.</p>
              </template>

              <p class="text-[11px] opacity-80 mt-1 leading-relaxed" v-else-if="getRegistration(selectedLombaForDetail?.id)?.status === 'verified'">
                Tim Anda telah diverifikasi! Kelola anggota tim di tab <strong>Anggota Tim</strong>.
              </p>
              <p class="text-[11px] opacity-80 mt-1 leading-relaxed" v-else-if="getRegistration(selectedLombaForDetail?.id)?.status === 'rejected'">
                Pendaftaran ditolak. Catatan: {{ getRegistration(selectedLombaForDetail?.id)?.notes || 'Tidak ada catatan.' }} Hubungi panitia untuk info lebih lanjut.
              </p>
            </div>
          </div>

          <div class="flex flex-wrap gap-3 pt-4">
            <router-link
              :to="'/invoice/' + getRegistration(selectedLombaForDetail?.id)?.id"
              target="_blank"
              class="inline-flex items-center gap-1.5 bg-[#04000D] hover:bg-black text-[#DCEEB1] px-5 py-3 rounded-xl text-xs font-bold transition-all shadow-sm"
            >
              <Printer class="w-4 h-4" /> Cetak Bukti Pendaftaran
            </router-link>
          </div>
        </div>

        <!-- Status: Not Registered Yet -->
        <div v-else>
          <div v-if="!isLombaOpen(selectedLombaForDetail)" class="p-5 border border-slate-100 rounded-2xl bg-slate-50/50 flex flex-col items-center justify-center text-center max-w-xl mx-auto">
            <Clock class="w-8 h-8 text-accent-magenta animate-pulse mb-3" />
            <h4 class="font-extrabold text-sm text-on-surface">Pendaftaran Belum Dibuka</h4>
            <p class="text-xs text-on-surface-variant/80 mt-1 max-w-xs">Lomba ini baru akan dibuka pendaftarannya dalam:</p>
            <div class="mt-4 bg-black text-[#DCEEB1] font-mono text-xs font-bold px-4 py-2 rounded-xl tracking-widest shadow-sm">
              {{ getLombaCountdownText(selectedLombaForDetail) }}
            </div>
          </div>

          <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-7 space-y-5">
              <div v-if="error" class="bg-[#FF3D8B]/5 border border-accent-magenta/20 rounded-xl px-4 py-3 text-xs font-semibold text-accent-magenta">{{ error }}</div>
              <div>
                <label class="block text-xs font-semibold text-on-surface-variant/80 mb-1.5">
                  Nama Tim
                  <span v-if="getMaxMembers(selectedLombaForDetail?.team_requirements) > 1" class="text-accent-magenta">*</span>
                  <span v-else class="text-on-surface-variant/50">(opsional)</span>
                </label>
                <input v-model="daftarForm.team_name" :placeholder="getMaxMembers(selectedLombaForDetail?.team_requirements) > 1 ? 'Masukkan nama tim kamu' : 'Nama tim (opsional)'" class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 px-4 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:bg-white focus:outline-none transition-all" />
              </div>
              <button @click="handleDaftar" :disabled="submitting" class="w-full bg-[#04000D] text-white hover:bg-black py-3.5 rounded-xl font-bold transition-all disabled:opacity-40 shadow-sm mt-4 flex items-center justify-center gap-1.5">
                <Plus class="w-4 h-4" /> {{ submitting ? 'Memproses...' : 'Daftar Kompetisi' }}
              </button>
            </div>
            <div class="lg:col-span-5 bg-slate-50/70 border border-slate-100 rounded-2xl p-5 space-y-4">
              <h4 class="font-extrabold text-xs text-on-surface uppercase tracking-wider">Ringkasan Pendaftaran</h4>
              <div class="space-y-3 text-xs border-b border-slate-100 pb-4">
                <div class="flex justify-between">
                  <span class="text-on-surface-variant/70">Biaya Pendaftaran</span>
                  <span class="font-extrabold text-on-surface text-right">
                    <span class="block">Gel 1: {{ selectedLombaForDetail?.fee_gelombang_1 || selectedLombaForDetail?.fee }}</span>
                    <span class="block text-[10px] text-on-surface-variant/60">Gel 2: {{ selectedLombaForDetail?.fee_gelombang_2 || selectedLombaForDetail?.fee }}</span>
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-on-surface-variant/70">Kategori Peserta</span>
                  <span class="font-bold text-on-surface">{{ selectedLombaForDetail?.target }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-on-surface-variant/70">Tipe Lomba</span>
                  <span class="font-semibold text-on-surface">{{ selectedLombaForDetail?.team_requirements }}</span>
                </div>
              </div>
              <div class="bg-[#FFF9E6] border border-amber-200/50 rounded-xl p-3.5 text-[11px] text-on-surface-variant leading-relaxed">
                <strong>Ketentuan:</strong> Setelah daftar, tim akan diverifikasi oleh admin. Setelah verifikasi, kamu bisa menambahkan anggota tim.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab Content: Submit (Only verified) -->
      <div v-else-if="activeTab === 'submit'">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          
          <!-- Form Block (Left) -->
          <div class="lg:col-span-7 space-y-6">
            <div v-if="getRegistration(selectedLombaForDetail?.id)?.submission" class="p-5 border border-[#DCEEB1]/50 bg-[#DCEEB1]/10 rounded-2xl flex items-start gap-3 shadow-sm">
              <CheckCircle class="w-5 h-5 text-on-surface-variant mt-0.5 flex-shrink-0" />
              <div class="min-w-0 flex-1 text-xs">
                <h4 class="font-bold">Karya Berhasil Dikumpulkan</h4>
                <p class="text-[11px] opacity-80 mt-1">Anda telah mengumpulkan link karya Anda. Anda tetap dapat memperbarui/mengirim ulang link karya tersebut sebelum batas waktu pendaftaran berakhir.</p>
                <div class="mt-4 pt-4 border-t border-[#DCEEB1]/45 space-y-2.5">
                  <div>
                    <span class="text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider">Link Karya (Google Drive)</span>
                    <a :href="getRegistration(selectedLombaForDetail?.id)?.submission?.link_drive" target="_blank" class="text-xs font-bold text-on-surface hover:underline flex items-center gap-1 mt-0.5 truncate text-sky-600">
                      {{ getRegistration(selectedLombaForDetail?.id)?.submission?.link_drive }} <ExternalLink class="w-3 h-3 flex-shrink-0" />
                    </a>
                  </div>
                  <div v-if="getRegistration(selectedLombaForDetail?.id)?.submission?.catatan">
                    <span class="text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider">Catatan</span>
                    <p class="text-xs text-on-surface mt-0.5 leading-relaxed bg-white/50 border border-slate-100 rounded-xl p-3">{{ getRegistration(selectedLombaForDetail?.id)?.submission?.catatan }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit/Re-submit Form -->
            <div class="space-y-5">
              <h4 class="font-extrabold text-sm text-on-surface">
                {{ getRegistration(selectedLombaForDetail?.id)?.submission ? 'Perbarui Karya Kamu' : 'Kumpulkan Karya Baru' }}
              </h4>
              
              <div v-if="submitError" class="bg-[#FF3D8B]/5 border border-accent-magenta/20 rounded-xl px-4 py-3 text-xs font-semibold text-accent-magenta">{{ submitError }}</div>

              <div>
                <label class="block text-xs font-semibold text-on-surface-variant/80 mb-1.5">Link Google Drive Karya <span class="text-accent-magenta">*</span></label>
                <div class="relative">
                  <FileText class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
                  <input v-model="submitForm.link_drive" placeholder="https://drive.google.com/..." class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 pl-11 pr-4 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:bg-white focus:outline-none transition-all" />
                </div>
                <p class="text-[10px] text-on-surface-variant/50 mt-1.5 leading-normal">Pastikan status akses link Google Drive Anda telah disetel menjadi **Public (Siapa saja yang memiliki link dapat melihat)** agar juri dapat menilai karya Anda.</p>
              </div>

              <div>
                <label class="block text-xs font-semibold text-on-surface-variant/80 mb-1.5">Catatan untuk Juri <span class="text-on-surface-variant/50">(opsional)</span></label>
                <textarea v-model="submitForm.catatan" rows="4" placeholder="Tuliskan catatan tambahan mengenai berkas/karya Anda di sini jika ada..." class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 px-4 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:bg-white focus:outline-none transition-all resize-none"></textarea>
              </div>

              <button @click="handleSubmitKarya" :disabled="submittingSubmit || !submitForm.link_drive" class="w-full bg-[#04000D] text-white hover:bg-black py-3 rounded-xl font-bold transition-all disabled:opacity-40 shadow-sm mt-2 flex items-center justify-center gap-1.5">
                <Send class="w-3.5 h-3.5" /> {{ submittingSubmit ? 'Mengirim...' : 'Kirim Karya' }}
              </button>
            </div>
          </div>

          <!-- Guide Sidebar (Right) -->
          <div class="lg:col-span-5 bg-slate-50/70 border border-slate-100 rounded-2xl p-5 space-y-4">
            <h4 class="font-extrabold text-xs text-on-surface uppercase tracking-wider">Petunjuk Pengumpulan</h4>
            <div class="space-y-3.5 text-xs text-on-surface-variant/80 leading-relaxed">
              <p>
                <strong>1. Persiapkan Link Google Drive:</strong> Pastikan folder atau berkas karya Anda sudah diunggah secara lengkap ke Google Drive.
              </p>
              <p>
                <strong>2. Atur Akses Link:</strong> Klik kanan folder di Google Drive $\rightarrow$ <em>Share</em> $\rightarrow$ Ubah akses umum menjadi <strong>"Anyone with the link / Siapa saja yang memiliki link"</strong> dengan peran <strong>Viewer</strong>.
              </p>
              <p>
                <strong>3. Tempel Tautan:</strong> Salin tautan Google Drive tersebut dan tempelkan pada kolom input yang tersedia di samping kiri.
              </p>
              <p>
                <strong>4. Batas Waktu:</strong> Anda dapat memperbarui kiriman tautan berkas karya Anda berulang kali hingga batas waktu pengumpulan resmi ditutup.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab Content: Anggota Tim (Only verified) -->
      <div v-if="activeTab === 'anggota'">
        <div class="bg-slate-50 border border-slate-200/60 rounded-2xl p-5 md:p-6 space-y-4">
          <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
            <h4 class="font-extrabold text-sm text-on-surface flex items-center gap-2">
              <Users class="w-4 h-4 text-accent-magenta" /> Anggota Tim
            </h4>
            <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-white border border-slate-200 text-on-surface-variant">
              Max: {{ getMaxMembers(selectedLombaForDetail?.team_requirements) }} Orang
            </span>
          </div>

          <!-- Ketua -->
          <div class="bg-white border border-slate-100 rounded-xl p-3 flex items-center justify-between text-xs shadow-sm">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span class="font-bold text-on-surface">{{ getRegistration(selectedLombaForDetail?.id)?.user?.name || auth.user?.name }}</span>
                <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-black text-[#DCEEB1]">Ketua</span>
              </div>
              <p class="font-mono text-[10px] text-on-surface-variant/60 mt-0.5">{{ getRegistration(selectedLombaForDetail?.id)?.user?.email || auth.user?.email }}</p>
            </div>
            <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 text-on-surface">Joined</span>
          </div>

          <!-- Accepted Members -->
          <div v-for="invite in teamInvitations.filter(i => i.status === 'accepted')" :key="invite.id" class="bg-white border border-slate-100 rounded-xl p-3 flex items-center justify-between text-xs shadow-sm">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span class="font-bold text-on-surface">{{ invite.invitedUser?.name || invite.email }}</span>
                <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-on-surface-variant">Anggota</span>
              </div>
              <p class="font-mono text-[10px] text-on-surface-variant/60 mt-0.5">{{ invite.invitedUser?.email || invite.email }}</p>
            </div>
            <button v-if="!isTeamLocked" @click="handleRemoveMember(invite.id)" class="text-accent-magenta/50 hover:text-accent-magenta transition-colors p-1" title="Keluarkan anggota">
              <UserMinus class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Pending Invitations -->
          <div v-for="invite in teamInvitations.filter(i => i.status === 'pending')" :key="invite.id" class="bg-[#FFF9E6] border border-amber-200/40 rounded-xl p-3 flex items-center justify-between text-xs shadow-sm">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span class="font-bold text-on-surface">{{ invite.invitedUser?.name || invite.email }}</span>
                <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">Pending</span>
              </div>
              <p class="font-mono text-[10px] text-on-surface-variant/60 mt-0.5">{{ invite.email }}</p>
            </div>
            <div class="flex items-center gap-1">
              <button v-if="!isTeamLocked" @click="handleCancelInvite(invite.id)" class="text-on-surface-variant/40 hover:text-accent-magenta transition-colors p-1" title="Batalkan undangan">
                <X class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Empty Slots -->
          <div v-for="slot in emptySlots" :key="'slot-' + slot" class="border-2 border-dashed border-slate-200 rounded-xl p-3 flex items-center justify-center text-xs text-on-surface-variant/40">
            <Users class="w-3.5 h-3.5 mr-2" /> Slot Anggota {{ slot }}
          </div>

          <!-- Add Member Form (when not locked) -->
          <div v-if="!isTeamLocked && emptySlots > 0" class="border-t border-slate-200/60 pt-4">
            <p class="text-xs font-bold text-on-surface mb-2">Undang Anggota Baru</p>
            <div v-if="inviteError" class="mb-2 bg-[#FF3D8B]/5 border border-accent-magenta/20 rounded-xl px-3 py-2 text-[11px] font-semibold text-accent-magenta">{{ inviteError }}</div>
            <div v-if="inviteSuccess" class="mb-2 bg-[#DCEEB1]/20 border border-[#DCEEB1]/40 rounded-xl px-3 py-2 text-[11px] font-semibold text-on-surface">{{ inviteSuccess }}</div>
            <div class="flex gap-2">
              <input v-model="inviteEmail" @keyup.enter="handleInvite" placeholder="email anggota@example.com" class="flex-1 bg-white border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2 px-3 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all" />
              <button @click="handleInvite" :disabled="inviting || !inviteEmail" class="bg-[#04000D] hover:bg-black text-[#DCEEB1] px-4 py-2 rounded-xl text-xs font-bold transition-all disabled:opacity-40 shadow-sm flex items-center gap-1.5">
                <Plus class="w-3.5 h-3.5" /> {{ inviting ? '...' : 'Undang' }}
              </button>
            </div>
          </div>

          <!-- Lock Status & Actions -->
          <div class="border-t border-slate-200/60 pt-4 mt-2">
            <div v-if="isTeamLocked && isFull" class="flex items-center gap-2 text-xs">
              <Lock class="w-4 h-4 text-on-surface-variant/40" />
              <span class="text-on-surface-variant/70">Tim terkunci (semua slot terisi)</span>
              <button v-if="!getRegistration(selectedLombaForDetail?.id)?.unlock_requested" @click="handleRequestChanges" class="ml-auto bg-white hover:bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all shadow-sm">
                Ajukan Buka Kunci
              </button>
              <span v-else class="ml-auto font-mono text-[10px] font-bold text-amber-600 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-lg">
                Menunggu Persetujuan Admin
              </span>
            </div>
            <div v-else-if="isTeamLocked && !isFull" class="flex items-center gap-2 text-xs">
              <Lock class="w-4 h-4 text-on-surface-variant/40" />
              <span class="text-on-surface-variant/70">Tim terkunci</span>
              <button @click="handleRequestChanges" class="ml-auto bg-white hover:bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all shadow-sm">
                Ajukan Buka Kunci
              </button>
            </div>
            <div v-else-if="!isTeamLocked && emptySlots === 0" class="flex items-center gap-2 text-xs">
              <Unlock class="w-4 h-4 text-[#DCEEB1]" />
              <span class="text-on-surface-variant/70">Tim terbuka — semua slot terisi, akan terkunci otomatis.</span>
            </div>
            <div v-else-if="autoLockRemaining > 0" class="flex items-center gap-2 text-xs">
              <Unlock class="w-4 h-4 text-amber-500" />
              <span class="text-on-surface-variant/70">Tim dibuka sementara — otomatis terkunci dalam </span>
              <span class="font-mono font-bold text-accent-magenta">{{ Math.floor(autoLockRemaining / 60) }}:{{ String(autoLockRemaining % 60).padStart(2, '0') }}</span>
            </div>
            <div v-else class="flex items-center gap-2 text-xs">
              <Unlock class="w-4 h-4 text-[#DCEEB1]" />
              <span class="text-on-surface-variant/70">Tim terbuka — {{ emptySlots }} slot tersedia</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom horizontal scrollbar hiding */
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}
</style>
