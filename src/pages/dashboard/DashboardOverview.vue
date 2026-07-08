<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import api from '../../utils/api'
import { Trophy, CheckCircle, Clock, AlertTriangle, ChevronRight, Sparkles, Mail, Check, X } from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()
const lombaList = ref([])
const pendaftarans = ref([])
const invitations = ref([])
const loading = ref(true)
const actionLoading = ref(null)
const statusConfig = {
  pending: { icon: Clock, label: 'Pending', class: 'bg-[#FFF9E6] text-on-surface border-amber-200' },
  verified: { icon: CheckCircle, label: 'Terverifikasi', class: 'bg-[#DCEEB1] text-on-surface border-[#DCEEB1]' },
  rejected: { icon: AlertTriangle, label: 'Ditolak', class: 'bg-[#FF3D8B]/10 text-accent-magenta border-accent-magenta/20' },
}

function getRegistration(lombaId) {
  if (!lombaId) return null
  return pendaftarans.value.find(p => p.lomba_id === lombaId)
}

function sudahTerdaftar(lombaId) {
  return !!getRegistration(lombaId)
}

function openCompetition(lomba) {
  router.push({ path: '/dashboard/competitions', query: { id: lomba.id } })
}

async function handleAcceptInvite(id) {
  actionLoading.value = id
  try {
    await api.put(`/invitations/${id}/accept`)
    invitations.value = invitations.value.filter(i => i.id !== id)
  } catch (e) {
    console.error(e)
  } finally {
    actionLoading.value = null
  }
}

async function handleDeclineInvite(id) {
  actionLoading.value = id
  try {
    await api.put(`/invitations/${id}/reject`)
    invitations.value = invitations.value.filter(i => i.id !== id)
  } catch (e) {
    console.error(e)
  } finally {
    actionLoading.value = null
  }
}

async function fetchData() {
  try {
    const [lombaRes, pendaftaranRes, invRes] = await Promise.all([
      api.get('/lombas'),
      api.get('/pendaftarans'),
      api.get('/invitations/pending'),
    ])
    lombaList.value = lombaRes.data.data
    pendaftarans.value = pendaftaranRes.data.data
    invitations.value = invRes.data.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
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
        Pantau lomba, kelola tim, dan kumpulkan karya kamu di sini.
      </p>
    </div>

    <!-- Status Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
      <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm">
        <p class="font-mono text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider">Total</p>
        <p class="text-2xl font-extrabold text-on-surface mt-1">{{ pendaftarans.length }}</p>
        <p class="text-[10px] text-on-surface-variant/50 mt-0.5">Lomba diikuti</p>
      </div>
      <div class="bg-[#FFF9E6] border border-amber-200/30 rounded-2xl p-4 shadow-sm">
        <p class="font-mono text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider">Pending</p>
        <p class="text-2xl font-extrabold text-on-surface mt-1">{{ pendaftarans.filter(p => p.status === 'pending').length }}</p>
        <p class="text-[10px] text-on-surface-variant/50 mt-0.5">Menunggu verifikasi</p>
      </div>
      <div class="bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 rounded-2xl p-4 shadow-sm">
        <p class="font-mono text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider">Terverifikasi</p>
        <p class="text-2xl font-extrabold text-on-surface mt-1">{{ pendaftarans.filter(p => p.status === 'verified').length }}</p>
        <p class="text-[10px] text-on-surface-variant/50 mt-0.5">Tim terverifikasi</p>
      </div>
      <div class="bg-[#FF3D8B]/5 border border-accent-magenta/15 rounded-2xl p-4 shadow-sm">
        <p class="font-mono text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider">Ditolak</p>
        <p class="text-2xl font-extrabold text-accent-magenta mt-1">{{ pendaftarans.filter(p => p.status === 'rejected').length }}</p>
        <p class="text-[10px] text-on-surface-variant/50 mt-0.5">Pendaftaran ditolak</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 4" :key="i" class="h-28 bg-slate-50 border border-slate-100 rounded-2xl animate-pulse"></div>
    </div>

    <template v-else>
      <!-- Undangan Tim -->
      <div v-if="invitations.length > 0" class="mb-8 space-y-4">
        <h2 class="font-extrabold text-sm text-on-surface uppercase tracking-wider flex items-center gap-2">
          <Mail class="w-4 h-4 text-accent-magenta" /> Undangan Bergabung Tim
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="invite in invitations"
            :key="invite.id"
            class="bg-[#DCEEB1]/10 border border-[#DCEEB1]/45 shadow-sm rounded-2xl p-5 flex flex-col justify-between gap-4"
          >
            <div class="text-xs">
              <span class="font-mono text-[9px] font-bold uppercase tracking-wider text-accent-magenta">{{ invite.pendaftaran?.lomba?.kode }}</span>
              <h4 class="font-extrabold text-sm text-on-surface mt-1">{{ invite.pendaftaran?.team_name || 'Tanpa Nama Tim' }}</h4>
              <p class="text-on-surface-variant/80 mt-2 leading-relaxed">
                Anda diundang bergabung di <strong>{{ invite.pendaftaran?.lomba?.title }}</strong> oleh <strong>{{ invite.invited_by?.name }}</strong>.
              </p>
            </div>
            <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
              <button
                @click="handleAcceptInvite(invite.id)"
                :disabled="actionLoading !== null"
                class="flex-1 inline-flex items-center justify-center gap-1.5 bg-[#04000D] hover:bg-black text-[#DCEEB1] px-4 py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm disabled:opacity-50"
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

      <!-- Lomba Kamu -->
      <div v-if="pendaftarans.length > 0" class="mb-10">
        <h2 class="font-extrabold text-base text-on-surface mb-4 flex items-center gap-2">
          <Trophy class="w-4 h-4 text-accent-magenta" /> Lomba Kamu
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div
            v-for="p in pendaftarans"
            :key="p.id"
            @click="openCompetition(p.lomba)"
            class="bg-white border border-[#04000D]/5 shadow-[0_4px_20px_rgb(0,0,0,0.015)] rounded-2xl p-5 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md hover:border-[#04000D]/10 cursor-pointer flex flex-col justify-between min-h-[180px]"
          >
            <div>
              <div class="flex items-center justify-between gap-2 mb-2">
                <span class="font-mono text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/60">{{ p.lomba?.kode }}</span>
                <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full shadow-sm" :class="statusConfig[p.status]?.class || ''">
                  <component :is="statusConfig[p.status]?.icon" class="w-2.5 h-2.5" />
                  {{ statusConfig[p.status]?.label }}
                </span>
              </div>
              <h3 class="font-extrabold text-base text-on-surface leading-snug">{{ p.lomba?.title }}</h3>
              <p class="text-xs text-on-surface-variant/60 mt-1.5 font-semibold">{{ p.team_name }}</p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-50 flex items-center justify-between">
              <span v-if="p.gelombang" class="font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full" :class="p.gelombang === '1' ? 'bg-pink-100 text-pink-700 border border-pink-200' : 'bg-blue-100 text-blue-700 border border-blue-200'">
                Gelombang {{ p.gelombang }}
              </span>
              <span class="text-[11px] text-on-surface-variant/50">Kelola <ChevronRight class="w-3 h-3 inline" /></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Semua Lomba -->
      <div>
        <h2 class="font-extrabold text-base text-on-surface mb-4 flex items-center gap-2">
          <Trophy class="w-4 h-4 text-accent-magenta" /> Semua Lomba
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div
            v-for="l in lombaList"
            :key="l.id"
            @click="openCompetition(l)"
            class="bg-white border border-[#04000D]/5 shadow-[0_4px_20px_rgb(0,0,0,0.015)] rounded-2xl p-5 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md hover:border-[#04000D]/10 cursor-pointer flex flex-col justify-between min-h-[180px]"
          >
            <div>
              <div class="flex items-center justify-between gap-2 mb-2">
                <span class="font-mono text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/60">{{ l.kode }}</span>
                <template v-if="sudahTerdaftar(l.id)">
                  <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full shadow-sm" :class="statusConfig[getRegistration(l.id)?.status]?.class || ''">
                    <component :is="statusConfig[getRegistration(l.id)?.status]?.icon" class="w-2.5 h-2.5" />
                    {{ statusConfig[getRegistration(l.id)?.status]?.label }}
                  </span>
                </template>
                <span v-else class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 text-on-surface">
                  Buka
                </span>
              </div>
              <h3 class="font-extrabold text-base text-on-surface leading-snug">{{ l.title }}</h3>
              <p class="text-xs text-on-surface-variant/60 mt-1.5 line-clamp-2 leading-relaxed">{{ l.description }}</p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-50 flex items-center justify-between">
              <span class="text-[9px] font-bold uppercase text-on-surface-variant/40">Biaya</span>
              <span class="text-xs font-extrabold text-on-surface">{{ l.fee_gelombang_1 || l.fee }}</span>
            </div>
            <button
              v-if="!sudahTerdaftar(l.id)"
              @click.stop="openCompetition(l)"
              class="mt-3 w-full bg-[#04000D] hover:bg-black text-[#DCEEB1] py-2 rounded-lg text-[10px] font-bold transition-all shadow-sm"
            >
              Daftar Sekarang
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
