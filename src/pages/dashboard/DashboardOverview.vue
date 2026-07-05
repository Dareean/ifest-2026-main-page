<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import api from '../../utils/api'
import { Trophy, Bell, CheckCircle, Clock, AlertTriangle, ArrowRight, Sparkles } from 'lucide-vue-next'

const auth = useAuthStore()
const stats = ref({ total: 0, verified: 0, pending: 0, rejected: 0 })
const recentNotifs = ref([])
const loading = ref(true)
const announcements = ref([])

async function fetchAnnouncements() {
  try {
    const res = await fetch('/announcements.json')
    if (res.ok) {
      announcements.value = await res.json()
    }
  } catch (e) {
    console.error('Gagal mengambil pengumuman:', e)
  }
}

// Countdown configuration
const targetDate = new Date('2026-07-11T00:00:00')
const countdownText = ref('')
const countdownActive = ref(true)
let timerId = null

function updateCountdown() {
  const now = new Date()
  const diff = targetDate - now

  if (diff <= 0) {
    countdownText.value = 'Pendaftaran lomba telah dibuka!'
    countdownActive.value = false
    if (timerId) clearInterval(timerId)
    return
  }

  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
  const seconds = Math.floor((diff % (1000 * 60)) / 1000)

  countdownText.value = `${days}d : ${hours}h : ${minutes}m : ${seconds}s`
}

async function fetchData() {
  try {
    const [pendaftaranRes, notifRes] = await Promise.all([
      api.get('/pendaftarans'),
      api.get('/notifications'),
    ])
    const daftar = pendaftaranRes.data.data
    stats.value.total = daftar.length
    stats.value.verified = daftar.filter(d => d.status === 'verified').length
    stats.value.pending = daftar.filter(d => d.status === 'pending').length
    stats.value.rejected = daftar.filter(d => d.status === 'rejected').length
    recentNotifs.value = notifRes.data.data.slice(0, 4)

    // Cache the data
    localStorage.setItem('cached_overview_stats', JSON.stringify(stats.value))
    localStorage.setItem('cached_recent_notifs', JSON.stringify(recentNotifs.value))
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  // Load from Cache first for instant render
  const cachedStats = localStorage.getItem('cached_overview_stats')
  const cachedRecentNotifs = localStorage.getItem('cached_recent_notifs')
  if (cachedStats && cachedRecentNotifs) {
    stats.value = JSON.parse(cachedStats)
    recentNotifs.value = JSON.parse(cachedRecentNotifs)
    loading.value = false
  } else {
    loading.value = true
  }

  fetchData()
  fetchAnnouncements()
  updateCountdown()
  timerId = setInterval(updateCountdown, 1000)
})

onUnmounted(() => {
  if (timerId) clearInterval(timerId)
})
</script>

<template>
  <div>
    <!-- Greeting -->
    <div class="mb-8">
      <div class="flex items-center gap-2 mb-2">
        <Sparkles class="w-4 h-4 text-accent-magenta" />
        <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta">Dashboard Peserta</span>
      </div>
      <h1 class="font-extrabold text-3xl md:text-4xl tracking-tight text-on-surface">
        Halo, {{ auth.user?.name?.split(' ')[0] }}!
      </h1>
      <p class="text-sm text-on-surface-variant/80 mt-2 max-w-lg">
        Selamat datang di dashboard peserta I-FEST 2026. Pantau lomba, kirim karya, dan dapatkan info terbaru.
      </p>
    </div>

    <!-- Announcement Banner -->
    <div v-for="ann in announcements" :key="ann.id" class="mb-6 p-4 bg-[#FF3D8B]/5 border border-accent-magenta/15 rounded-2xl flex items-start gap-3 shadow-[0_4px_20px_rgba(0,0,0,0.005)]">
      <div class="w-8 h-8 rounded-lg bg-[#FF3D8B]/10 flex items-center justify-center flex-shrink-0">
        <Sparkles class="w-4 h-4 text-accent-magenta animate-pulse" />
      </div>
      <div class="min-w-0 flex-1">
        <span class="font-mono text-[9px] font-bold uppercase text-accent-magenta tracking-wider">Pengumuman Penting</span>
        <p class="text-xs text-on-surface mt-0.5 leading-relaxed">{{ ann.text }}</p>
      </div>
    </div>

    <!-- Countdown Banner -->
    <div v-if="countdownActive" class="mb-8 p-4 bg-slate-50 border border-slate-200/80 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-[0_4px_20px_rgba(0,0,0,0.01)]">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-accent-magenta/5 flex items-center justify-center">
          <Clock class="w-5 h-5 text-accent-magenta animate-pulse" />
        </div>
        <div>
          <p class="text-xs font-semibold text-on-surface-variant/80">Lomba Nasional I-FEST 2026</p>
          <p class="text-[11px] text-on-surface-variant/60 mt-0.5">Pendaftaran Competitive Programming, UI/UX, & Business Plan akan dibuka dalam:</p>
        </div>
      </div>
      <div class="bg-black text-[#DCEEB1] font-mono text-xs font-bold px-4 py-2 rounded-xl text-center shadow-sm whitespace-nowrap self-start sm:self-auto tracking-widest">
        {{ countdownText }}
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10">
      <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md hover:border-accent-magenta/25 group">
        <div class="w-12 h-12 rounded-xl bg-accent-magenta/5 flex items-center justify-center mb-4 transition-transform group-hover:scale-105 duration-300">
          <Trophy class="w-5 h-5 text-accent-magenta" />
        </div>
        <p class="font-extrabold text-3xl tracking-tight text-on-surface">{{ loading ? '—' : stats.total }}</p>
        <p class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant/70 mt-1">Total Lomba</p>
      </div>

      <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md hover:border-[#DCEEB1]/70 group">
        <div class="w-12 h-12 rounded-xl bg-[#DCEEB1]/20 flex items-center justify-center mb-4 transition-transform group-hover:scale-105 duration-300">
          <CheckCircle class="w-5 h-5 text-on-surface" />
        </div>
        <p class="font-extrabold text-3xl tracking-tight text-on-surface">{{ loading ? '—' : stats.verified }}</p>
        <p class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant/70 mt-1">Terverifikasi</p>
      </div>

      <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md hover:border-amber-500/20 group">
        <div class="w-12 h-12 rounded-xl bg-amber-500/[0.06] flex items-center justify-center mb-4 transition-transform group-hover:scale-105 duration-300">
          <Clock class="w-5 h-5 text-amber-600" />
        </div>
        <p class="font-extrabold text-3xl tracking-tight text-on-surface">{{ loading ? '—' : stats.pending }}</p>
        <p class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant/70 mt-1">Pending</p>
      </div>

      <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md hover:border-[#FF3D8B]/20 group">
        <div class="w-12 h-12 rounded-xl bg-[#FF3D8B]/5 flex items-center justify-center mb-4 transition-transform group-hover:scale-105 duration-300">
          <AlertTriangle class="w-5 h-5 text-accent-magenta" />
        </div>
        <p class="font-extrabold text-3xl tracking-tight text-on-surface">{{ loading ? '—' : stats.rejected }}</p>
        <p class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant/70 mt-1">Ditolak</p>
      </div>
    </div>

    <!-- Recent Notifications -->
    <div class="bg-white border border-[#04000D]/5 shadow-[0_12px_40px_rgba(0,0,0,0.015)] rounded-2xl p-6 md:p-8">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-accent-magenta/5 flex items-center justify-center">
            <Bell class="w-4 h-4 text-accent-magenta" />
          </div>
          <div>
            <h2 class="font-extrabold text-lg tracking-tight text-on-surface">Notifikasi</h2>
            <p class="text-[10px] text-on-surface-variant/70 uppercase tracking-wider font-semibold">Terbaru</p>
          </div>
        </div>
        <router-link to="/dashboard/notifications" class="font-mono text-[10px] font-bold uppercase tracking-wider text-accent-magenta hover:text-on-surface flex items-center gap-1 transition-colors">
          Semua <ArrowRight class="w-3 h-3" />
        </router-link>
      </div>

      <div v-if="loading" class="space-y-3">
        <div v-for="i in 3" :key="i" class="h-20 bg-slate-50 border border-slate-100 rounded-xl animate-pulse"></div>
      </div>

      <div v-else-if="recentNotifs.length === 0" class="py-12 text-center border border-dashed border-[#04000D]/10 rounded-2xl bg-slate-50/50">
        <Bell class="w-8 h-8 text-on-surface-variant/30 mx-auto mb-3" />
        <p class="text-xs font-semibold text-on-surface-variant/60">Belum ada notifikasi terbaru</p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="notif in recentNotifs"
          :key="notif.id"
          class="rounded-xl p-4 flex items-start gap-4 transition-all duration-200"
          :class="notif.is_read
            ? 'bg-slate-50/70 border border-transparent hover:bg-slate-50'
            : 'bg-white border border-accent-magenta/10 hover:border-accent-magenta/25 shadow-sm'"
        >
          <div
            class="w-2.5 h-2.5 rounded-full mt-1 flex-shrink-0"
            :class="notif.is_read ? 'bg-on-surface-variant/20' : 'bg-accent-magenta'"
          ></div>
          <div class="min-w-0 flex-1">
            <p class="font-bold text-xs text-on-surface">{{ notif.judul }}</p>
            <p class="text-xs text-on-surface-variant/90 mt-1 leading-relaxed">{{ notif.pesan }}</p>
            <p class="font-mono text-[9px] text-on-surface-variant/40 mt-2">{{ new Date(notif.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
