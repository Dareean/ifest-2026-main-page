<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import api from '../../utils/api'
import { Trophy, Bell, CheckCircle, Clock, AlertTriangle, ArrowRight, Sparkles } from 'lucide-vue-next'

const auth = useAuthStore()
const stats = ref({ total: 0, verified: 0, pending: 0, rejected: 0 })
const recentNotifs = ref([])
const loading = ref(true)

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
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>

<template>
  <div>
    <!-- Greeting -->
    <div class="mb-10">
      <div class="flex items-center gap-2 mb-2">
        <Sparkles class="w-5 h-5 text-accent-magenta" />
        <span class="font-mono text-xs font-bold uppercase tracking-widest text-accent-magenta">Dashboard Peserta</span>
      </div>
      <h1 class="font-black text-3xl md:text-4xl uppercase tracking-tighter text-on-surface riso-bleed">
        Halo, {{ auth.user?.name?.split(' ')[0] }}!
      </h1>
      <p class="font-mono text-sm text-on-surface-variant mt-1.5 max-w-lg">
        Selamat datang di dashboard peserta I-FEST 2026. Pantau lomba, kirim karya, dan dapatkan info terbaru.
      </p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
      <div class="bg-white shadow-[6px_6px_0px_0px_#04000D] rounded-2xl p-5">
        <div class="w-10 h-10 rounded-xl bg-accent-magenta/10 flex items-center justify-center mb-3">
          <Trophy class="w-5 h-5 text-accent-magenta" />
        </div>
        <p class="font-black text-2xl tracking-tight text-on-surface">{{ loading ? '—' : stats.total }}</p>
        <p class="font-mono text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mt-0.5">Total Lomba</p>
      </div>

      <div class="bg-white shadow-[6px_6px_0px_0px_#04000D] rounded-2xl p-5">
        <div class="w-10 h-10 rounded-xl bg-[#DCEEB1]/50 flex items-center justify-center mb-3">
          <CheckCircle class="w-5 h-5 text-on-surface" />
        </div>
        <p class="font-black text-2xl tracking-tight text-on-surface">{{ loading ? '—' : stats.verified }}</p>
        <p class="font-mono text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mt-0.5">Terverifikasi</p>
      </div>

      <div class="bg-white shadow-[6px_6px_0px_0px_#04000D] rounded-2xl p-5">
        <div class="w-10 h-10 rounded-xl bg-[#FFF9E6] flex items-center justify-center mb-3">
          <Clock class="w-5 h-5 text-on-surface" />
        </div>
        <p class="font-black text-2xl tracking-tight text-on-surface">{{ loading ? '—' : stats.pending }}</p>
        <p class="font-mono text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mt-0.5">Pending</p>
      </div>

      <div class="bg-white shadow-[6px_6px_0px_0px_#04000D] rounded-2xl p-5">
        <div class="w-10 h-10 rounded-xl bg-[#FF3D8B]/10 flex items-center justify-center mb-3">
          <AlertTriangle class="w-5 h-5 text-accent-magenta" />
        </div>
        <p class="font-black text-2xl tracking-tight text-on-surface">{{ loading ? '—' : stats.rejected }}</p>
        <p class="font-mono text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mt-0.5">Ditolak</p>
      </div>
    </div>

    <!-- Recent Notifications -->
    <div class="bg-white shadow-[6px_6px_0px_0px_#04000D] rounded-2xl p-6 md:p-8">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-accent-magenta/10 flex items-center justify-center">
            <Bell class="w-4 h-4 text-accent-magenta" />
          </div>
          <div>
            <h2 class="font-black text-lg uppercase tracking-tighter text-on-surface">Notifikasi</h2>
            <p class="font-mono text-[10px] text-on-surface-variant uppercase tracking-wider font-bold">Terbaru</p>
          </div>
        </div>
        <router-link to="/dashboard/notifications" class="font-mono text-[10px] font-bold uppercase tracking-wider text-accent-magenta hover:text-on-surface flex items-center gap-1 transition-colors">
          Semua <ArrowRight class="w-3 h-3" />
        </router-link>
      </div>

      <div v-if="loading" class="space-y-3">
        <div v-for="i in 3" :key="i" class="h-[72px] bg-[#F5F5F5] rounded-xl animate-pulse"></div>
      </div>

      <div v-else-if="recentNotifs.length === 0" class="py-12 text-center">
        <Bell class="w-8 h-8 text-on-surface-variant/30 mx-auto mb-3" />
        <p class="font-mono text-xs font-bold text-on-surface-variant/60">Belum ada notifikasi</p>
      </div>

      <div v-else class="space-y-2">
        <div
          v-for="notif in recentNotifs"
          :key="notif.id"
          class="rounded-xl p-4 flex items-start gap-3 transition-colors"
          :class="notif.is_read ? 'bg-[#F5F5F5]' : 'bg-[#F5F5F5] border border-[#04000D]/20'"
        >
          <div
            class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0"
            :class="notif.is_read ? 'bg-on-surface-variant/30' : 'bg-accent-magenta'"
          ></div>
          <div class="min-w-0 flex-1">
            <p class="font-mono text-xs font-bold text-on-surface">{{ notif.judul }}</p>
            <p class="font-mono text-[11px] text-on-surface-variant mt-0.5 leading-relaxed">{{ notif.pesan }}</p>
            <p class="font-mono text-[9px] text-on-surface-variant/50 mt-1.5">{{ new Date(notif.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
