<script setup>
import { ref, onMounted } from 'vue'
import { useAdminStore } from '../../../stores/admin'
import { Users, ClipboardList, Clock, CheckCircle, AlertTriangle, Shield } from 'lucide-vue-next'

const admin = useAdminStore()
const loading = ref(true)

onMounted(async () => {
  await admin.fetchStats()
  loading.value = false
})
</script>

<template>
  <div>
    <div class="mb-8">
      <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Admin Panel</span>
      <h1 class="font-extrabold text-3xl md:text-4xl tracking-tight text-on-surface">Dashboard</h1>
    </div>

    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
      <div v-for="i in 4" :key="i" class="h-28 bg-slate-50 border border-slate-100 rounded-2xl animate-pulse"></div>
    </div>

    <div v-else-if="admin.stats" class="space-y-8">
      <!-- Stat cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-5">
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-[#04000D]/5 border border-[#04000D]/10 flex items-center justify-center flex-shrink-0">
              <Users class="w-6 h-6 text-on-surface-variant" />
            </div>
            <div>
              <p class="font-mono text-[9px] font-bold uppercase text-on-surface-variant/50 tracking-wider">Total Pengguna</p>
              <p class="font-extrabold text-2xl text-on-surface">{{ admin.stats.total_users }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-5">
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-[#FFF9E6] border border-amber-200/50 flex items-center justify-center flex-shrink-0">
              <Clock class="w-6 h-6 text-amber-600" />
            </div>
            <div>
              <p class="font-mono text-[9px] font-bold uppercase text-on-surface-variant/50 tracking-wider">Pending</p>
              <p class="font-extrabold text-2xl text-on-surface">{{ admin.stats.by_status?.pending || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-5">
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 flex items-center justify-center flex-shrink-0">
              <CheckCircle class="w-6 h-6 text-green-700" />
            </div>
            <div>
              <p class="font-mono text-[9px] font-bold uppercase text-on-surface-variant/50 tracking-wider">Terverifikasi</p>
              <p class="font-extrabold text-2xl text-on-surface">{{ admin.stats.by_status?.verified || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-5">
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-[#FF3D8B]/10 border border-accent-magenta/20 flex items-center justify-center flex-shrink-0">
              <AlertTriangle class="w-6 h-6 text-accent-magenta" />
            </div>
            <div>
              <p class="font-mono text-[9px] font-bold uppercase text-on-surface-variant/50 tracking-wider">Ditolak</p>
              <p class="font-extrabold text-2xl text-on-surface">{{ admin.stats.by_status?.rejected || 0 }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Unlock requests alert -->
      <div v-if="admin.stats.pending_unlock_requests > 0" class="bg-accent-magenta/5 border border-accent-magenta/20 rounded-2xl p-5 flex items-start gap-4">
        <Shield class="w-6 h-6 text-accent-magenta mt-0.5 flex-shrink-0" />
        <div class="text-sm">
          <p class="font-bold text-on-surface">{{ admin.stats.pending_unlock_requests }} permohonan buka kunci tim menunggu</p>
          <p class="text-xs text-on-surface-variant/80 mt-1">Ada tim yang meminta perubahan anggota. Segera proses di menu Pendaftaran.</p>
        </div>
      </div>

      <!-- Recent registrations -->
      <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6">
        <h3 class="font-extrabold text-sm text-on-surface mb-4 flex items-center gap-2">
          <ClipboardList class="w-4 h-4 text-accent-magenta" /> Pendaftaran Terbaru
        </h3>
        <div v-if="admin.stats.recent_registrations?.length" class="space-y-2">
          <div v-for="reg in admin.stats.recent_registrations" :key="reg.id" class="flex items-center justify-between text-xs border-b border-slate-50 last:border-0 pb-2 last:pb-0">
            <div class="min-w-0 flex-1">
              <span class="font-bold text-on-surface">{{ reg.team_name || reg.user?.name }}</span>
              <p class="font-mono text-[10px] text-on-surface-variant/60">{{ reg.user?.name }} &middot; {{ reg.lomba?.kode }}</p>
            </div>
            <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full ml-3" :class="reg.status === 'verified' ? 'bg-[#DCEEB1]/30 text-green-700' : reg.status === 'rejected' ? 'bg-accent-magenta/10 text-accent-magenta' : 'bg-[#FFF9E6] text-amber-600'">
              {{ reg.status }}
            </span>
          </div>
        </div>
        <p v-else class="text-xs text-on-surface-variant/50 py-4 text-center">Belum ada pendaftaran</p>
      </div>

      <!-- Per-lomba stats -->
      <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6">
        <h3 class="font-extrabold text-sm text-on-surface mb-4">Per Lomba</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <div v-for="item in admin.stats.by_lomba" :key="item.lomba" class="bg-slate-50 border border-slate-100 rounded-xl p-3.5 text-xs">
            <p class="font-bold text-on-surface truncate">{{ item.lomba }}</p>
            <p class="font-mono text-[10px] text-on-surface-variant/60 mt-1">{{ item.total }} pendaftaran</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
