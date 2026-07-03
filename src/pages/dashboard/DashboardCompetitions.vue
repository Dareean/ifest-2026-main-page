<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../utils/api'
import { useAuthStore } from '../../stores/auth'
import { 
  Trophy, Plus, ExternalLink, CheckCircle, Clock, AlertTriangle, 
  Send, X, Users, BookOpen, Calendar, ArrowLeft,
  ChevronRight, Award, FileText, Printer, Lock, Unlock, UserMinus, Mail, Check
} from 'lucide-vue-next'

const auth = useAuthStore()
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
const inviteEmail = ref('')
const inviting = ref(false)
const inviteError = ref('')
const inviteSuccess = ref('')
const actionLoading = ref(null)

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
  'NAT-01': new Date('2026-06-01T00:00:00'),
  'NAT-02': new Date('2026-06-01T00:00:00'),
  'NAT-03': new Date('2026-06-01T00:00:00'),
  'REG-01': new Date('2026-06-01T00:00:00'),
  'REG-02': new Date('2026-06-01T00:00:00'),
  'REG-03': new Date('2026-06-01T00:00:00'), // Sulteng Digital Innovation Hub is already open
}

const now = ref(new Date())
let timerId = null

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

async function fetchInvitations() {
  try {
    const res = await api.get('/invitations/pending')
    invitations.value = res.data.data
    localStorage.setItem('cached_invitations', JSON.stringify(res.data.data))
  } catch (e) {
    console.error(e)
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
  } catch (e) {
    inviteError.value = e.response?.data?.message || 'Gagal mengirim undangan'
  } finally {
    inviting.value = false
  }
}

async function handleAcceptInvite(invitationId) {
  actionLoading.value = invitationId
  try {
    await api.put(`/invitations/${invitationId}/accept`)
    await fetchInvitations()
    await fetchData()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menerima undangan')
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
    alert(e.response?.data?.message || 'Gagal menolak undangan')
  } finally {
    actionLoading.value = null
  }
}

async function handleRemoveMember(memberId) {
  if (!selectedLombaForDetail.value) return
  const reg = getRegistration(selectedLombaForDetail.value.id)
  if (!confirm('Apakah Anda yakin ingin mengeluarkan anggota ini?')) return
  actionLoading.value = memberId
  try {
    await api.delete(`/pendaftarans/${reg.id}/members/${memberId}`)
    await fetchData()
    const updated = lombaList.value.find(l => l.id === selectedLombaForDetail.value.id)
    if (updated) openDetail(updated)
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal mengeluarkan anggota')
  } finally {
    actionLoading.value = null
  }
}

const router = useRouter()

function goToTeamPage() {
  const reg = getRegistration(selectedLombaForDetail.value?.id)
  if (reg) {
    router.push(`/dashboard/tim/${reg.id}`)
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
  }, 1000) // update every second for ticking countdown
})

onUnmounted(() => {
  if (timerId) clearInterval(timerId)
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
      <div v-if="!selectedLombaForDetail">
        <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Daftar Kompetisi</span>
        <h1 class="font-extrabold text-3xl md:text-4xl tracking-tight text-on-surface">Lomba</h1>
      </div>
      <div v-else class="flex items-center gap-3">
        <button @click="closeDetail" class="w-10 h-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition-colors flex items-center justify-center text-on-surface-variant shadow-sm">
          <ArrowLeft class="w-5 h-5" />
        </button>
        <div>
          <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">{{ selectedLombaForDetail.kode }}</span>
          <h1 class="font-extrabold text-2xl md:text-3xl tracking-tight text-on-surface leading-tight">{{ selectedLombaForDetail.title }}</h1>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="h-28 bg-slate-50 border border-slate-100 rounded-2xl animate-pulse"></div>
    </div>

    <!-- View 1: Grid Lomba -->
    <div v-else-if="!selectedLombaForDetail" class="space-y-8">
      
      <!-- Section: Undangan Tim Masuk -->
      <div v-if="invitations.length > 0" class="space-y-4">
        <h2 class="font-extrabold text-sm text-on-surface uppercase tracking-wider flex items-center gap-2">
          <Mail class="w-4 h-4 text-accent-magenta" /> Undangan Bergabung Tim
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div 
            v-for="invite in invitations" 
            :key="invite.id" 
            class="bg-[#DCEEB1]/10 border border-[#DCEEB1]/45 shadow-sm rounded-2xl p-5 flex flex-col justify-between gap-4 animate-fade-in"
          >
            <div class="text-xs">
              <span class="font-mono text-[9px] font-bold uppercase tracking-wider text-accent-magenta">{{ invite.pendaftaran?.lomba?.kode }}</span>
              <h4 class="font-extrabold text-sm text-on-surface mt-1">{{ invite.pendaftaran?.team_name || 'Tanpa Nama Tim' }}</h4>
              <p class="text-on-surface-variant/80 mt-2 leading-relaxed">
                Anda diundang untuk bergabung di kompetisi <strong>{{ invite.pendaftaran?.lomba?.title }}</strong> oleh Ketua Tim <strong>{{ invite.invited_by?.name }}</strong> ({{ invite.email }}).
              </p>
            </div>
            
            <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
              <button 
                @click="handleAcceptInvite(invite.id)" 
                :disabled="actionLoading !== null"
                class="flex-1 inline-flex items-center justify-center gap-1.5 bg-[#04000D] hover:bg-black text-[#DCEEB1] hover:text-[#DCEEB1]/90 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm disabled:opacity-50"
              >
                <Check class="w-3.5 h-3.5" /> {{ actionLoading === invite.id ? 'Memproses...' : 'Terima' }}
              </button>
              <button 
                @click="handleDeclineInvite(invite.id)" 
                :disabled="actionLoading !== null"
                class="flex-1 inline-flex items-center justify-center gap-1.5 bg-white hover:bg-slate-50 text-accent-magenta border border-slate-200 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm disabled:opacity-50"
              >
                <X class="w-3.5 h-3.5" /> {{ actionLoading === invite.id ? 'Memproses...' : 'Tolak' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="l in lombaList" 
          :key="l.id" 
          class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md hover:border-[#04000D]/10 flex flex-col justify-between h-full min-h-[220px]"
        >
          <div>
            <div class="flex items-center justify-between gap-2 mb-3">
              <span class="font-mono text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-slate-50 border border-slate-100 text-on-surface-variant/70">{{ l.scale }}</span>
              
              <!-- Status Badges -->
              <span v-if="sudahTerdaftar(l.id)" class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full shadow-sm animate-fade-in" :class="statusConfig[getRegistration(l.id)?.status]?.class || ''">
                <component :is="statusConfig[getRegistration(l.id)?.status]?.icon" class="w-2.5 h-2.5" />
                {{ statusConfig[getRegistration(l.id)?.status]?.label }}
              </span>
              <span v-else-if="!isLombaOpen(l)" class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-slate-50 border border-slate-100 text-accent-magenta/60">
                <Clock class="w-2.5 h-2.5 text-accent-magenta" />
                Belum Buka
              </span>
              <span v-else class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 text-on-surface">
                Buka
              </span>
            </div>
            
            <h3 class="font-extrabold text-lg text-on-surface leading-snug">{{ l.title }}</h3>
            <p class="text-xs text-on-surface-variant/75 mt-1.5 line-clamp-2 leading-relaxed">{{ l.description }}</p>
          </div>
          
          <div class="mt-6 pt-4 border-t border-slate-50 flex items-center justify-between gap-4">
            <!-- Left aspect: Fee or Countdown -->
            <div v-if="!sudahTerdaftar(l.id) && !isLombaOpen(l)" class="min-w-0">
              <p class="text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider">Mulai Dalam</p>
              <p class="font-mono text-[11px] font-extrabold text-accent-magenta tracking-wide mt-0.5">{{ getLombaCountdownText(l) }}</p>
            </div>
            <div v-else class="min-w-0">
              <p class="text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider">Biaya</p>
              <p class="text-xs font-extrabold text-on-surface mt-0.5">{{ l.fee }}</p>
            </div>
            
            <!-- Button -->
            <button @click="openDetail(l)" class="bg-[#04000D] hover:bg-black text-[#DCEEB1] hover:text-[#DCEEB1]/90 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1">
              Detail & Kelola <ChevronRight class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- View 2: Focused Detail View -->
    <div v-else class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6 md:p-8">
      <!-- Tabs -->
      <div class="flex border-b border-slate-100 overflow-x-auto gap-6 mb-8 scrollbar-none">
        <button 
          v-for="tab in ['info', 'timeline', 'team', 'submit']" 
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
              : ''
          ]"
        >
          {{ 
            tab === 'info' ? 'Detail & Juknis' :
            tab === 'timeline' ? 'Timeline' :
            tab === 'team' ? 'Registrasi & Tim' : 'Pengumpulan Karya'
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
        <!-- Status Registered -->
        <div v-if="sudahTerdaftar(selectedLombaForDetail?.id)" class="space-y-6">
          <div 
            class="p-4 border rounded-2xl flex items-start gap-3 shadow-sm text-xs"
            :class="statusConfig[getRegistration(selectedLombaForDetail?.id)?.status]?.class || ''"
          >
            <component :is="statusConfig[getRegistration(selectedLombaForDetail?.id)?.status]?.icon || Clock" class="w-5 h-5 mt-0.5 flex-shrink-0" />
            <div>
              <p class="font-bold">Status Pendaftaran: {{ statusConfig[getRegistration(selectedLombaForDetail?.id)?.status]?.label }}</p>
              
              <!-- Custom status notes -->
              <p class="text-[11px] opacity-80 mt-1 leading-relaxed" v-if="getRegistration(selectedLombaForDetail?.id)?.status === 'pending'">
                Pendaftaran tim Anda telah masuk sistem. Mohon tunggu verifikasi administrasi dan pembayaran oleh panitia. Akses untuk pengisian berkas (karya) akan terbuka setelah pendaftaran diverifikasi.
              </p>
              <p class="text-[11px] opacity-80 mt-1 leading-relaxed" v-else-if="getRegistration(selectedLombaForDetail?.id)?.status === 'verified'">
                Tim Anda telah disetujui! Anda sekarang dapat mengumpulkan tautan berkas karya Anda pada tab **Pengumpulan Karya**.
              </p>
              <p class="text-[11px] opacity-80 mt-1 leading-relaxed" v-else-if="getRegistration(selectedLombaForDetail?.id)?.status === 'rejected'">
                Pendaftaran ditolak karena berkas tidak memenuhi syarat. Catatan panitia: {{ getRegistration(selectedLombaForDetail?.id)?.notes || 'Tidak ada catatan.' }}. Silakan hubungi panitia untuk info lebih lanjut.
              </p>
            </div>
          </div>

          <!-- Team Details -->
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

            <!-- Other members from old JSON format -->
            <div v-for="member in getRegistration(selectedLombaForDetail?.id)?.team_members" :key="member.user_id" class="bg-white border border-slate-100 rounded-xl p-3 flex items-center justify-between text-xs shadow-sm">
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-on-surface">{{ member.name }}</span>
                  <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-on-surface-variant">Anggota</span>
                </div>
                <p class="font-mono text-[10px] text-on-surface-variant/60 mt-0.5">{{ member.email }}</p>
              </div>
              <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full" :class="member.status === 'joined' ? 'bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 text-on-surface' : 'bg-[#FFF9E6] border border-amber-200 text-amber-600'">
                {{ member.status === 'joined' ? 'Joined' : 'Pending' }}
              </span>
            </div>

            <button @click="goToTeamPage" class="w-full bg-[#04000D] hover:bg-black text-[#DCEEB1] hover:text-[#DCEEB1]/90 px-4 py-3 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-1.5">
              <Users class="w-3.5 h-3.5" /> Kelola Tim & Undang Anggota
            </button>
          </div>

          <!-- Actions: Print & Certificate -->
          <div v-if="getRegistration(selectedLombaForDetail?.id)?.status === 'verified'" class="flex flex-wrap gap-3 pt-2">
            <router-link 
              :to="'/invoice/' + getRegistration(selectedLombaForDetail?.id)?.id" 
              target="_blank" 
              class="inline-flex items-center gap-1.5 bg-[#04000D] hover:bg-black text-[#DCEEB1] hover:text-[#DCEEB1]/90 px-5 py-3 rounded-xl text-xs font-bold transition-all shadow-sm"
            >
              <Printer class="w-4 h-4" /> Cetak Bukti Pendaftaran
            </router-link>
            
            <a 
              :href="'https://drive.google.com/drive/folders/ifest-2026-' + selectedLombaForDetail?.kode.toLowerCase() + '-certs'" 
              target="_blank" 
              class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-50 text-on-surface border border-slate-200 px-5 py-3 rounded-xl text-xs font-bold transition-all shadow-sm"
            >
              <Award class="w-4 h-4 text-accent-magenta" /> Unduh Sertifikat Peserta
            </a>
          </div>
        </div>

        <!-- Status Not Registered Yet -->
        <div v-else>
          <!-- Checks opening status -->
          <div v-if="!isLombaOpen(selectedLombaForDetail)" class="p-5 border border-slate-100 rounded-2xl bg-slate-50/50 flex flex-col items-center justify-center text-center max-w-xl mx-auto">
            <Clock class="w-8 h-8 text-accent-magenta animate-pulse mb-3" />
            <h4 class="font-extrabold text-sm text-on-surface">Pendaftaran Belum Dibuka</h4>
            <p class="text-xs text-on-surface-variant/80 mt-1 max-w-xs">Lomba ini baru akan dibuka pendaftarannya dalam:</p>
            <div class="mt-4 bg-black text-[#DCEEB1] font-mono text-xs font-bold px-4 py-2 rounded-xl tracking-widest shadow-sm">
              {{ getLombaCountdownText(selectedLombaForDetail) }}
            </div>
          </div>

          <!-- Open Form Grid -->
          <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Form Block -->
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

            <!-- Sidebar Info -->
            <div class="lg:col-span-5 bg-slate-50/70 border border-slate-100 rounded-2xl p-5 space-y-4">
              <h4 class="font-extrabold text-xs text-on-surface uppercase tracking-wider">Ringkasan Pendaftaran</h4>
              <div class="space-y-3 text-xs border-b border-slate-100 pb-4">
                <div class="flex justify-between">
                  <span class="text-on-surface-variant/70">Biaya Pendaftaran</span>
                  <span class="font-extrabold text-on-surface">{{ selectedLombaForDetail?.fee }}</span>
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
                <strong>Ketentuan Verifikasi:</strong> Setelah mengisi form pendaftaran, lakukan transfer pembayaran. Tim Anda akan diverifikasi oleh Admin dalam waktu 1x24 jam sebelum dapat mengumpulkan berkas karya.
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
