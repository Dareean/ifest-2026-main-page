<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useConfirm } from '../../composables/useConfirm'
import { useToast } from '../../composables/useToast'
import { useBack } from '../../composables/useBack'
import api from '../../utils/api'
import { useAuthStore } from '../../stores/auth'
import { useCompetitionNav } from '../../composables/useCompetitionNav'
import ProgressStepper from '../../components/ProgressStepper.vue'
import {
  Trophy, Plus, ExternalLink, CheckCircle, Clock, AlertTriangle,
  Send, X, Users, BookOpen, Calendar, ArrowLeft,
  ChevronRight, Award, FileText, Printer, UserMinus, Mail, Check, Copy
} from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const confirmModal = useConfirm()
const { goBack } = useBack()
const { showToast } = useToast()
const { selectedLomba: selectedLombaForDetail, pendaftarans, activeTab, availableTabs } = useCompetitionNav()
const loading = ref(true)
const lombaList = ref([])

// Forms state
const daftarForm = ref({ team_name: '' })
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
const inviteErrorFound = ref(false)
const inviteErrorEmail = ref('')
const copied = ref(false)
const actionLoading = ref(null)
const unlockRequesting = ref(false)

const registerLink = computed(() => `${window.location.origin}/register`)

function copyRegisterLink() {
  navigator.clipboard.writeText(registerLink.value)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
}

const paymentLink = ref('')
const uploadingPayment = ref(false)
const paymentUploadError = ref('')
const paymentUploadSuccess = ref('')

const formIgFollow = ref('')
const formIgTwibbon = ref('')
const socialUploading = ref(false)

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

const isLeader = computed(() => {
  const reg = getRegistration(selectedLombaForDetail.value?.id)
  return reg && reg.user_id === auth.user?.id
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

const now = ref(new Date())
let timerId = null

const anggotaVisible = computed(() => {
  return !!getRegistration(selectedLombaForDetail.value?.id)
})

// availableTabs is now provided by useCompetitionNav

// SIMULASI: semua lomba terbuka
const isLombaOpen = () => true

const getLombaCountdownText = () => ''

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
    console.error('fetchTeamInvitations error:', e.response?.status, e.response?.data || e.message)
    teamInvitations.value = []
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

async function handleUploadSocial(type) {
  const reg = getRegistration(selectedLombaForDetail.value?.id)
  if (!reg) return
  const field = type === 'follow' ? formIgFollow : formIgTwibbon
  if (!field.value) return
  socialUploading.value = true
  try {
    await api.post(`/pendaftarans/${reg.id}/social-proof`, { type, proof_url: field.value })
    field.value = ''
    await fetchData()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal mengirim bukti sosial media', 'error')
  } finally {
    socialUploading.value = false
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
    daftarForm.value = { team_name: '' }
    submitForm.value = { link_drive: '', catatan: '' }
  }
}

function closeDetail()
 {
  selectedLombaForDetail.value = null
  goBack('/dashboard')
}

async function handleDaftar() {
  if (!selectedLombaForDetail.value) return
  error.value = ''
  submitting.value = true
  try {
    await api.post(`/lombas/${selectedLombaForDetail.value.id}/daftar`, {
      team_name: daftarForm.value.team_name || null,
    })
    daftarForm.value = { team_name: '' }
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

async function handleSubmitKarya() {
  const reg = getRegistration(selectedLombaForDetail.value?.id)
  if (!reg || !submitForm.value.link_drive) return
  submitError.value = ''
  submittingSubmit.value = true
  try {
    await api.post(`/pendaftarans/${reg.id}/submit`, {
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
    const data = e.response?.data
    if (data && data.found === false) {
      inviteError.value = data.message
      inviteErrorFound.value = true
      inviteErrorEmail.value = inviteEmail.value
    } else {
      inviteError.value = data?.message || 'Gagal mengirim undangan'
      inviteErrorFound.value = false
    }
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
    showToast(e.response?.data?.message || 'Gagal membatalkan undangan', 'error')
  }
}

async function handleRequestUnlock() {
  const reg = getRegistration(selectedLombaForDetail.value?.id)
  if (!reg) return
  unlockRequesting.value = true
  try {
    await api.post(`/pendaftarans/${reg.id}/request-unlock`)
    showToast('Permintaan buka kunci terkirim ke admin', 'success')
    await fetchData()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal mengirim permintaan', 'error')
  } finally {
    unlockRequesting.value = false
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

  fetchData()
  timerId = setInterval(() => {
    now.value = new Date()
  }, 1000)
})

// Watch route.query.id and lombaList to open the competition automatically
watch(
  [() => route.query.id, lombaList],
  ([newId, list]) => {
    if (newId && list && list.length > 0) {
      const found = list.find((l) => l.id == newId || l.kode === newId)
      if (found) {
        if (selectedLombaForDetail.value?.id === found.id) return
        openDetail(found)
        return
      }
    }
    selectedLombaForDetail.value = null
  },
  { immediate: true }
)

watch(selectedLombaForDetail, (lomba) => {
  if (!lomba) return
  const reg = getRegistration(lomba.id)
  if (reg) {
    fetchTeamInvitations()
  }
})

// Re-fetch data when switching to relevant tabs
watch(activeTab, (tab) => {
  if (tab === 'anggota') {
    fetchTeamInvitations()
  }
  if (tab === 'validasi' || tab === 'anggota') {
    fetchData()
  }
})

onUnmounted(() => {
  if (timerId) clearInterval(timerId)
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

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-6">
      <!-- Header skeleton -->
      <div class="mb-8 space-y-3">
        <div class="h-4 w-32 bg-slate-50 border border-slate-100 rounded-lg animate-pulse"></div>
        <div class="h-9 w-52 bg-slate-50 border border-slate-100 rounded-xl animate-pulse"></div>
      </div>
      <!-- Card skeletons -->
      <div v-for="i in 3" :key="i" class="h-32 bg-slate-50 border border-slate-100 rounded-2xl animate-pulse"></div>
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
      <!-- Tabs are now managed via the sidebar layout -->

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

        <!-- Contact Person -->
        <div v-if="selectedLombaForDetail?.contact_person" class="pt-2">
          <div class="inline-flex items-center gap-2 bg-[#04000D] text-white px-5 py-3 rounded-xl text-xs font-bold shadow-sm">
            <span class="uppercase tracking-widest font-black text-[#FDE047]">CP:</span>
            <span>{{ selectedLombaForDetail.contact_person }}</span>
          </div>
        </div>

        <!-- CTA Daftar Sekarang -->
        <div class="pt-5 border-t border-slate-100 mt-6">
          <template v-if="!sudahTerdaftar(selectedLombaForDetail?.id)">
            <button 
              v-if="isLombaOpen(selectedLombaForDetail)" 
              @click="activeTab = 'team'" 
              class="w-full bg-[#04000D] hover:bg-black text-[#DCEEB1] py-3.5 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-2"
            >
              <Plus class="w-4 h-4" /> Daftar Sekarang
            </button>
            <div v-else class="bg-slate-50 border border-slate-100 rounded-xl p-4 text-center">
              <p class="text-xs font-semibold text-on-surface-variant/60">Pendaftaran belum dibuka</p>
            </div>
          </template>
          <button 
            v-else 
            @click="activeTab = 'team'" 
            class="w-full bg-[#DCEEB1] hover:bg-[#DCEEB1]/80 text-on-surface py-3 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-2"
          >
            <CheckCircle class="w-4 h-4" /> Lihat Status Pendaftaran
          </button>
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
        <div v-if="sudahTerdaftar(selectedLombaForDetail?.id)" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
          <!-- Progress Stepper (Left) -->
          <div class="lg:col-span-5 bg-white border border-slate-100 rounded-2xl p-5 shadow-sm">
            <h4 class="font-extrabold text-xs text-on-surface uppercase tracking-wider mb-2">Progress</h4>
            <ProgressStepper
              :reg="getRegistration(selectedLombaForDetail?.id)"
              :lomba="selectedLombaForDetail"
              :team-accepted-count="teamInvitations.filter(i => i.status === 'accepted').length"
            />
          </div>

          <!-- Status & Actions (Right) -->
          <div class="lg:col-span-7 space-y-4">
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

                <!-- Payment Accounts Info -->
                <div v-if="selectedLombaForDetail?.payment_accounts?.length && getRegistration(selectedLombaForDetail?.id)?.payment_status !== 'verified'" class="mt-3 bg-slate-50 rounded-xl p-4 border border-slate-100">
                  <span class="text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider block mb-2">Metode Pembayaran</span>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div v-for="(acct, i) in selectedLombaForDetail.payment_accounts" :key="i" class="bg-white rounded-xl p-3 border border-slate-100 flex items-center gap-3 shadow-sm">
                      <div class="w-10 h-10 rounded-xl bg-[#DCEEB1]/20 border border-[#DCEEB1]/30 flex items-center justify-center text-[8px] font-black uppercase flex-shrink-0 text-on-surface">
                        {{ acct.bank?.slice(0, 3) || acct.ewallet?.slice(0, 3) || 'BR' }}
                      </div>
                      <div class="min-w-0">
                        <p class="font-bold text-xs text-on-surface leading-tight">{{ acct.bank || acct.ewallet }}</p>
                        <p class="font-mono text-[10px] font-bold text-on-surface mt-0.5 select-all">{{ acct.nomor }}</p>
                        <p class="text-[10px] text-on-surface-variant/60 truncate">a.n. {{ acct.nama }}</p>
                      </div>
                    </div>
                  </div>
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

            <div class="flex flex-wrap gap-3">
              <router-link
                :to="'/invoice/' + getRegistration(selectedLombaForDetail?.id)?.id"
                target="_blank"
                class="inline-flex items-center gap-1.5 bg-[#04000D] hover:bg-black text-[#DCEEB1] px-5 py-3 rounded-xl text-xs font-bold transition-all shadow-sm"
              >
                <Printer class="w-4 h-4" /> Cetak Bukti Pendaftaran
              </router-link>
            </div>
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

      <!-- Tab Content: Anggota Tim -->
      <div v-if="activeTab === 'anggota'" class="space-y-6">
        <!-- Data Ketua -->
        <div class="bg-white border border-slate-200/60 rounded-2xl p-5 md:p-6">
          <div class="flex items-center justify-between border-b border-slate-200/60 pb-3 mb-4">
            <h4 class="font-extrabold text-sm text-on-surface flex items-center gap-2">
              <Users class="w-4 h-4 text-accent-magenta" /> Data Ketua Tim
            </h4>
            <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-black text-[#DCEEB1]">Ketua</span>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Nama Lengkap</label>
              <p class="text-sm font-bold">{{ auth.user?.name || '-' }}</p>
            </div>
            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Usia</label>
              <p class="text-sm">{{ auth.user?.age || '-' }}</p>
            </div>
            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Asal Sekolah/Univ/Instansi</label>
              <p class="text-sm">{{ auth.user?.institution || '-' }}</p>
            </div>
            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Nomor WhatsApp</label>
              <p class="text-sm">{{ auth.user?.phone || '-' }}</p>
            </div>
            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Username Instagram</label>
              <p class="text-sm">{{ auth.user?.instagram_username ? '@' + auth.user?.instagram_username : '-' }}</p>
            </div>
          </div>
          <button @click="router.push('/dashboard/profile')" class="mt-4 inline-flex items-center gap-1.5 text-[10px] font-bold text-accent-magenta hover:underline">
            Edit Profil
          </button>
        </div>

        <!-- Data Anggota -->
        <div class="bg-white border border-slate-200/60 rounded-2xl p-5 md:p-6">
          <div class="flex items-center justify-between border-b border-slate-200/60 pb-3 mb-4">
            <h4 class="font-extrabold text-sm text-on-surface flex items-center gap-2">
              <Users class="w-4 h-4 text-accent-magenta" /> Data Anggota Tim
            </h4>
            <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full" :class="emptySlots === 0 ? 'bg-[#DCEEB1] text-on-surface' : 'bg-white border border-slate-200 text-on-surface-variant'">
              {{ 1 + teamInvitations.filter(i => i.status === 'accepted').length }}/{{ getMaxMembers(selectedLombaForDetail?.team_requirements) }}
            </span>
          </div>

          <!-- Accepted Members -->
          <div v-for="invite in teamInvitations.filter(i => i.status === 'accepted')" :key="invite.id" class="mb-4 last:mb-0">
            <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-4">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-sm text-on-surface">{{ invite.invitedUser?.name || invite.email }}</span>
                  <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-on-surface-variant">Anggota</span>
                </div>
                <button v-if="isLeader" @click="handleRemoveMember(invite.id)" class="text-accent-magenta/50 hover:text-accent-magenta transition-colors p-1" title="Keluarkan anggota">
                  <UserMinus class="w-3.5 h-3.5" />
                </button>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                <div>
                  <span class="text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/50">Asal Instansi</span>
                  <p class="mt-0.5 font-semibold">{{ invite.invitedUser?.institution || '-' }}</p>
                </div>
                <div>
                  <span class="text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/50">WhatsApp</span>
                  <p class="mt-0.5 font-semibold">{{ invite.invitedUser?.phone || '-' }}</p>
                </div>
                <div>
                  <span class="text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/50">Usia</span>
                  <p class="mt-0.5 font-semibold">{{ invite.invitedUser?.age || '-' }}</p>
                </div>
                <div>
                  <span class="text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/50">Instagram</span>
                  <p class="mt-0.5 font-semibold">{{ invite.invitedUser?.instagram_username ? '@' + invite.invitedUser?.instagram_username : '-' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Pending Invitations -->
          <div v-for="invite in teamInvitations.filter(i => i.status === 'pending')" :key="invite.id" class="bg-[#FFF9E6] border border-amber-200/40 rounded-xl p-3 flex items-center justify-between text-xs shadow-sm mb-2">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <Mail class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" />
                <span class="font-bold text-on-surface">{{ invite.invitedUser?.name || invite.email }}</span>
                <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">Pending</span>
              </div>
              <p class="font-mono text-[10px] text-on-surface-variant/60 mt-0.5">{{ invite.email }}</p>
            </div>
            <div v-if="isLeader" class="flex items-center gap-1">
              <button @click="handleCancelInvite(invite.id)" class="text-on-surface-variant/40 hover:text-accent-magenta transition-colors p-1" title="Batalkan undangan">
                <X class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Empty Slots -->
          <div v-for="slot in emptySlots" :key="'slot-' + slot" class="border-2 border-dashed border-slate-200 rounded-xl p-3 flex items-center justify-center text-xs text-on-surface-variant/40 mb-2">
            <Users class="w-3.5 h-3.5 mr-2" /> Slot {{ slot }} (kosong)
          </div>

          <!-- Add Member Form -->
          <div v-if="isLeader && emptySlots > 0" class="mt-4 border-t border-slate-200/60 pt-4">
            <p class="text-xs font-bold text-on-surface mb-2">Undang Anggota Baru</p>
            <div v-if="inviteError && !inviteErrorFound" class="mb-2 bg-[#FF3D8B]/5 border border-accent-magenta/20 rounded-xl px-3 py-2 text-[11px] font-semibold text-accent-magenta">{{ inviteError }}</div>
            <div v-if="inviteError && inviteErrorFound" class="mb-2 bg-[#FFF9E6] border border-amber-200/40 rounded-xl px-3 py-3 text-[11px]">
              <p class="font-semibold text-amber-700">{{ inviteError }}</p>
              <p class="text-on-surface-variant/70 mt-1 leading-relaxed">Mintalah calon anggota untuk mendaftar akun terlebih dahulu, lalu undang kembali setelah mereka terdaftar.</p>
              <button @click="copyRegisterLink" class="mt-2 inline-flex items-center gap-1.5 bg-white hover:bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all shadow-sm">
                <Copy class="w-3 h-3" /> {{ copied ? 'Tersalin!' : 'Salin Link Pendaftaran' }}
              </button>
            </div>
            <div v-if="inviteSuccess" class="mb-2 bg-[#DCEEB1]/20 border border-[#DCEEB1]/40 rounded-xl px-3 py-2 text-[11px] font-semibold text-on-surface">{{ inviteSuccess }}</div>
            <div class="flex gap-2">
              <input v-model="inviteEmail" @keyup.enter="handleInvite" placeholder="email anggota@example.com" class="flex-1 bg-white border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2 px-3 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all" />
              <button @click="handleInvite" :disabled="inviting || !inviteEmail" class="bg-[#04000D] hover:bg-black text-[#DCEEB1] px-4 py-2 rounded-xl text-xs font-bold transition-all disabled:opacity-40 shadow-sm flex items-center gap-1.5">
                <Plus class="w-3.5 h-3.5" /> {{ inviting ? '...' : 'Undang' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Pembayaran -->
        <div class="bg-white border border-slate-200/60 rounded-2xl p-5 md:p-6">
          <div class="flex items-center justify-between border-b border-slate-200/60 pb-3 mb-4">
            <h4 class="font-extrabold text-sm text-on-surface flex items-center gap-2">
              <Award class="w-4 h-4 text-accent-magenta" /> Pembayaran
            </h4>
            <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full" :class="getRegistration(selectedLombaForDetail?.id)?.payment_status === 'verified' ? 'bg-[#DCEEB1] text-on-surface' : 'bg-amber-100 text-amber-700'">
              {{ getRegistration(selectedLombaForDetail?.id)?.payment_status === 'verified' ? 'Lunas' : 'Belum Bayar' }}
            </span>
          </div>

          <div class="bg-sky-50 border border-sky-200/60 rounded-xl p-4 mb-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-sky-700 mb-1">Informasi Pembayaran</p>
            <p class="text-xs font-semibold text-sky-800">Rp85.000 (Batch II / Gelombang 2)</p>
            <p class="text-xs text-sky-700 mt-1">BRI a.n <strong>LARA FAUZIA</strong> — <strong class="text-sm tracking-wider">5199 0100 5106 502</strong></p>
          </div>

          <div v-if="getRegistration(selectedLombaForDetail?.id)?.payment_status === 'verified'" class="bg-[#DCEEB1]/20 border border-[#DCEEB1]/40 rounded-xl p-4 flex items-center gap-3">
            <CheckCircle class="w-5 h-5 text-on-surface flex-shrink-0" />
            <div class="text-xs">
              <p class="font-bold">Pembayaran Terverifikasi</p>
              <p class="text-on-surface-variant/70 mt-0.5">Terima kasih, pembayaran Anda telah dikonfirmasi oleh admin.</p>
            </div>
          </div>

          <div v-else-if="getRegistration(selectedLombaForDetail?.id)?.payment_status === 'pending'" class="bg-[#FFF9E6] border border-amber-200/40 rounded-xl p-4 flex items-center gap-3">
            <Clock class="w-5 h-5 text-amber-600 flex-shrink-0 animate-pulse" />
            <div class="text-xs">
              <p class="font-bold text-amber-800">Menunggu Verifikasi</p>
              <p class="text-amber-700/80 mt-0.5">Bukti pembayaran sedang diverifikasi oleh admin. Silakan tunggu konfirmasi.</p>
            </div>
          </div>

          <div v-else class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-on-surface-variant/80 mb-1.5">Link Google Drive Bukti Transfer</label>
              <input v-model="paymentLink" placeholder="https://drive.google.com/..." class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 px-4 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:bg-white focus:outline-none transition-all" />
              <p class="text-[10px] text-on-surface-variant/50 mt-1">Upload screenshot bukti transfer ke Google Drive, lalu tempel link-nya di sini.</p>
            </div>
            <button @click="handleUploadPayment" :disabled="uploadingPayment || !paymentLink" class="w-full bg-[#04000D] hover:bg-black text-[#DCEEB1] py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm disabled:opacity-40 flex items-center justify-center gap-1.5">
              <Send class="w-3.5 h-3.5" /> {{ uploadingPayment ? 'Mengirim...' : 'Kirim Bukti Bayar' }}
            </button>
            <p v-if="paymentUploadError" class="text-[11px] font-semibold text-accent-magenta">{{ paymentUploadError }}</p>
            <p v-if="paymentUploadSuccess" class="text-[11px] font-semibold text-[#DCEEB1]">{{ paymentUploadSuccess }}</p>
          </div>
        </div>

        <!-- Lock status & unlock request -->
        <div v-if="isLeader && getRegistration(selectedLombaForDetail?.id)?.team_locked" class="bg-amber-50 border border-amber-200/50 rounded-xl p-4">
          <div class="flex items-center justify-between gap-3">
            <div class="min-w-0 text-xs">
              <p class="font-bold text-amber-800">Tim Terkunci</p>
              <p class="text-amber-700/80 mt-0.5 leading-relaxed">Tim Anda saat ini terkunci. Anda tidak dapat mengundang anggota baru atau mengubah komposisi tim.</p>
            </div>
            <button
              v-if="!getRegistration(selectedLombaForDetail?.id)?.unlock_requested"
              @click="handleRequestUnlock"
              :disabled="unlockRequesting"
              class="flex-shrink-0 bg-[#04000D] hover:bg-black text-white px-4 py-2 rounded-xl text-[10px] font-bold transition-all shadow-sm disabled:opacity-40"
            >
              {{ unlockRequesting ? 'Mengirim...' : 'Minta Buka Kunci' }}
            </button>
            <span v-else class="flex-shrink-0 font-mono text-[9px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200">
              Permintaan Terkirim
            </span>
          </div>
        </div>

        <div class="text-xs text-on-surface-variant/50 text-center">
          <Users class="w-3.5 h-3.5 inline mr-1" />
          <span v-if="emptySlots === 0">Semua slot terisi</span>
          <span v-else>{{ emptySlots }} slot tersedia</span>
        </div>
      </div>

      <!-- Tab Content: Validasi Sosial Media -->
      <div v-if="activeTab === 'validasi'" class="space-y-6">
        <div class="bg-white border border-slate-200/60 rounded-2xl p-5 md:p-6">
          <div class="border-b border-slate-200/60 pb-3 mb-4">
            <h4 class="font-extrabold text-sm text-on-surface flex items-center gap-2">
              <CheckCircle class="w-4 h-4 text-accent-magenta" /> Validasi Sosial Media
            </h4>
            <p class="text-[11px] text-on-surface-variant/70 mt-1">Syarat wajib: Follow Instagram I-FEST dan upload twibbon dengan tag akun resmi I-FEST.</p>
          </div>

          <div class="space-y-5">
            <!-- Follow Instagram -->
            <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-4">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h5 class="font-bold text-xs text-on-surface">1. Follow Instagram @ifest_untad</h5>
                  <p class="text-[10px] text-on-surface-variant/60 mt-0.5">Screenshot bukti follow akun Instagram I-FEST</p>
                </div>
                <span v-if="getRegistration(selectedLombaForDetail?.id)?.ig_follow_proof" class="font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[#DCEEB1] text-on-surface">Terkirim</span>
              </div>
              <div class="flex gap-2">
                <input v-model="formIgFollow" placeholder="https://drive.google.com/..." class="flex-1 bg-white border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2 px-3 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all" />
                <button @click="handleUploadSocial('follow')" :disabled="socialUploading || !formIgFollow" class="bg-[#04000D] hover:bg-black text-[#DCEEB1] px-4 py-2 rounded-xl text-[10px] font-bold transition-all disabled:opacity-40 shadow-sm flex items-center gap-1.5">
                  <Send class="w-3 h-3" /> {{ socialUploading ? '...' : 'Kirim' }}
                </button>
              </div>
              <a v-if="getRegistration(selectedLombaForDetail?.id)?.ig_follow_proof" :href="getRegistration(selectedLombaForDetail?.id)?.ig_follow_proof" target="_blank" class="mt-2 inline-flex items-center gap-1 text-[10px] font-bold text-sky-600 hover:underline">
                Lihat bukti <ExternalLink class="w-3 h-3" />
              </a>
            </div>

            <!-- Upload Twibbon -->
            <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-4">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h5 class="font-bold text-xs text-on-surface">2. Upload Twibbon + Tag @ifest_untad</h5>
                  <p class="text-[10px] text-on-surface-variant/60 mt-0.5">Screenshot twibbon yang sudah di-posting di Instagram dan menandai akun I-FEST</p>
                </div>
                <span v-if="getRegistration(selectedLombaForDetail?.id)?.ig_twibbon_proof" class="font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[#DCEEB1] text-on-surface">Terkirim</span>
              </div>
              <div class="flex gap-2">
                <input v-model="formIgTwibbon" placeholder="https://drive.google.com/..." class="flex-1 bg-white border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2 px-3 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all" />
                <button @click="handleUploadSocial('twibbon')" :disabled="socialUploading || !formIgTwibbon" class="bg-[#04000D] hover:bg-black text-[#DCEEB1] px-4 py-2 rounded-xl text-[10px] font-bold transition-all disabled:opacity-40 shadow-sm flex items-center gap-1.5">
                  <Send class="w-3 h-3" /> {{ socialUploading ? '...' : 'Kirim' }}
                </button>
              </div>
              <a v-if="getRegistration(selectedLombaForDetail?.id)?.ig_twibbon_proof" :href="getRegistration(selectedLombaForDetail?.id)?.ig_twibbon_proof" target="_blank" class="mt-2 inline-flex items-center gap-1 text-[10px] font-bold text-sky-600 hover:underline">
                Lihat bukti <ExternalLink class="w-3 h-3" />
              </a>
            </div>

            <div v-if="getRegistration(selectedLombaForDetail?.id)?.social_validated" class="bg-[#DCEEB1]/20 border border-[#DCEEB1]/40 rounded-xl p-4 flex items-center gap-3">
              <CheckCircle class="w-5 h-5 text-on-surface flex-shrink-0" />
              <div class="text-xs">
                <p class="font-bold">Validasi Sosial Media Selesai</p>
                <p class="text-on-surface-variant/70 mt-0.5">Kedua bukti sudah terkirim. Terima kasih!</p>
              </div>
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
