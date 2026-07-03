<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../utils/api'
import { useAuthStore } from '../../stores/auth'
import {
  ArrowLeft, Users, Mail, UserMinus, Lock, Clock,
  CheckCircle, AlertTriangle, X, ChevronRight
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const loading = ref(true)
const error = ref('')
const team = ref(null)
const inviteEmail = ref('')
const inviting = ref(false)
const inviteError = ref('')
const inviteSuccess = ref('')
const actionLoading = ref(null)

const pendaftaran = computed(() => team.value?.pendaftaran)
const maxMembers = computed(() => team.value?.max_members || 1)
const currentCount = computed(() => team.value?.current_count || 1)
const canInvite = computed(() =>
  team.value?.ketua?.id === auth.user?.id &&
  !team.value?.team_locked &&
  currentCount.value < maxMembers.value
)
const slotsLeft = computed(() => Math.max(0, maxMembers.value - currentCount.value))

async function fetchTeam() {
  try {
    const res = await api.get(`/pendaftarans/${route.params.id}/team`)
    team.value = res.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal memuat data tim'
  } finally {
    loading.value = false
  }
}

async function handleInvite() {
  if (!inviteEmail.value) return
  inviteError.value = ''
  inviteSuccess.value = ''
  inviting.value = true
  try {
    await api.post(`/pendaftarans/${route.params.id}/invite`, { email: inviteEmail.value })
    inviteSuccess.value = 'Undangan berhasil dikirim!'
    inviteEmail.value = ''
    await fetchTeam()
  } catch (e) {
    inviteError.value = e.response?.data?.message || 'Gagal mengirim undangan'
  } finally {
    inviting.value = false
  }
}

async function handleRemoveMember(invitationId) {
  if (!confirm('Apakah Anda yakin ingin mengeluarkan anggota ini?')) return
  actionLoading.value = invitationId
  try {
    await api.delete(`/pendaftarans/${route.params.id}/members/${invitationId}`)
    await fetchTeam()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal mengeluarkan anggota')
  } finally {
    actionLoading.value = null
  }
}

async function handleCancelInvitation(invitationId) {
  actionLoading.value = invitationId
  try {
    await api.put(`/invitations/${invitationId}/reject`)
    await fetchTeam()
  } finally {
    actionLoading.value = null
  }
}

async function handleRequestUnlock() {
  try {
    await api.post(`/pendaftarans/${route.params.id}/request-changes`)
    await fetchTeam()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal mengajukan permohonan')
  }
}

onMounted(fetchTeam)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-start gap-3 mb-8">
      <button @click="router.push('/dashboard/competitions')" class="mt-1 w-10 h-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition-colors flex items-center justify-center text-on-surface-variant shadow-sm flex-shrink-0">
        <ArrowLeft class="w-5 h-5" />
      </button>
      <div class="min-w-0 flex-1">
        <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Kelola Tim</span>
        <h1 v-if="pendaftaran" class="font-extrabold text-2xl md:text-3xl tracking-tight text-on-surface leading-tight truncate">
          {{ pendaftaran.team_name || 'Tim ' + team?.ketua?.name }}
        </h1>
        <div v-if="pendaftaran" class="flex items-center gap-2 mt-1.5">
          <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-on-surface-variant">{{ pendaftaran.lomba?.kode }}</span>
          <span class="text-xs text-on-surface-variant/70 truncate">{{ pendaftaran.lomba?.title }}</span>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-4">
      <div class="h-28 bg-slate-50 border border-slate-100 rounded-2xl animate-pulse"></div>
      <div class="h-64 bg-slate-50 border border-slate-100 rounded-2xl animate-pulse"></div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="bg-[#FF3D8B]/5 border border-accent-magenta/20 rounded-2xl p-6 text-center max-w-lg mx-auto">
      <AlertTriangle class="w-8 h-8 text-accent-magenta mx-auto mb-3" />
      <p class="text-sm font-bold text-on-surface">{{ error }}</p>
    </div>

    <div v-else class="space-y-6 max-w-2xl">
      <!-- Lock banner (if locked) -->
      <div v-if="team?.team_locked" class="bg-[#FF3D8B]/5 border border-accent-magenta/10 rounded-2xl p-5 flex items-start gap-4 shadow-sm">
        <div class="w-10 h-10 rounded-xl bg-accent-magenta/10 border border-accent-magenta/20 flex items-center justify-center flex-shrink-0">
          <Lock class="w-5 h-5 text-accent-magenta" />
        </div>
        <div class="flex-1 text-sm">
          <p class="font-bold text-on-surface">Anggota Tim Terkunci</p>
          <p class="text-on-surface-variant/80 mt-1 leading-relaxed text-xs">
            Struktur tim ini sedang dikunci. Anggota tidak dapat ditambah atau dikeluarkan.
          </p>
          <div v-if="team?.ketua?.id === auth.user?.id" class="mt-3">
            <span v-if="team?.unlock_requested" class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase text-amber-600 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-lg">
              <Clock class="w-3.5 h-3.5" /> Menunggu Persetujuan Admin...
            </span>
            <button v-else @click="handleRequestUnlock" class="riso-btn-plate inline-flex items-center gap-1.5 bg-[#04000D] hover:bg-black text-[#DCEEB1] px-4 py-2 rounded-lg font-bold text-[10px] uppercase tracking-wider transition-all shadow-sm">
              <Lock class="w-3.5 h-3.5" /> Ajukan Buka Kunci
            </button>
          </div>
        </div>
      </div>

      <!-- Main card -->
      <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl overflow-hidden">
        <!-- Card header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
          <h2 class="font-extrabold text-sm text-on-surface flex items-center gap-2">
            <Users class="w-4 h-4 text-accent-magenta" /> Anggota Tim
          </h2>
          <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-1 rounded bg-slate-50 border border-slate-200 text-on-surface-variant">
            {{ currentCount }} / {{ maxMembers }}
          </span>
        </div>

        <!-- Member list -->
        <div class="p-5 space-y-2.5">
          <!-- Ketua -->
          <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3.5 flex items-center justify-between text-xs shadow-sm">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <span class="font-bold text-on-surface">{{ team?.ketua?.name }}</span>
                <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-black text-[#DCEEB1]">Ketua</span>
              </div>
              <p class="font-mono text-[10px] text-on-surface-variant/60 mt-0.5">{{ team?.ketua?.email }}</p>
            </div>
            <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 text-on-surface flex-shrink-0 ml-3">
              Joined
            </span>
          </div>

          <!-- Accepted members (dengan invitation_id) -->
          <div v-for="member in team?.accepted_members" :key="member.invitation_id" class="rounded-xl border border-slate-100 bg-white p-3.5 flex items-center justify-between text-xs shadow-sm">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <span class="font-bold text-on-surface">{{ member.name }}</span>
                <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-on-surface-variant">Anggota</span>
              </div>
              <p class="font-mono text-[10px] text-on-surface-variant/60 mt-0.5">{{ member.email }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0 ml-3">
              <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 text-on-surface">Joined</span>
              <button
                v-if="team?.ketua?.id === auth.user?.id && !team?.team_locked"
                @click="handleRemoveMember(member.invitation_id)"
                :disabled="actionLoading === member.invitation_id"
                class="text-on-surface-variant/50 hover:text-accent-magenta hover:bg-accent-magenta/5 transition-all p-1.5 rounded-lg"
                title="Keluarkan anggota"
              >
                <UserMinus class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Pending invitations -->
          <div v-for="inv in team?.pending_invitations" :key="'p-' + inv.id" class="rounded-xl border border-dashed border-slate-200 bg-white p-3.5 flex items-center justify-between text-xs shadow-sm">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <Mail class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" />
                <span class="font-bold text-on-surface">{{ inv.name || inv.email }}</span>
              </div>
              <p class="font-mono text-[10px] text-on-surface-variant/60 mt-0.5" v-if="inv.name">{{ inv.email }} &middot; Menunggu konfirmasi</p>
              <p class="font-mono text-[10px] text-on-surface-variant/60 mt-0.5" v-else>Menunggu konfirmasi</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0 ml-3">
              <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[#FFF9E6] border border-amber-200 text-amber-600">Pending</span>
              <button
                v-if="team?.ketua?.id === auth.user?.id && !team?.team_locked"
                @click="handleCancelInvitation(inv.id)"
                :disabled="actionLoading === inv.id"
                class="text-on-surface-variant/50 hover:text-accent-magenta hover:bg-accent-magenta/5 transition-all p-1.5 rounded-lg"
                title="Batalkan undangan"
              >
                <X class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Empty state -->
          <div v-if="!team?.accepted_members?.length && !team?.pending_invitations?.length" class="py-10 text-center">
            <Users class="w-8 h-8 text-on-surface-variant/20 mx-auto mb-3" />
            <p class="text-xs font-semibold text-on-surface-variant/50">Belum ada anggota tim</p>
          </div>
        </div>

        <!-- Invite form (ketua only, not locked, not full) -->
        <div v-if="canInvite" class="border-t border-slate-100 px-6 py-5">
          <div class="flex items-center justify-between mb-3">
            <h5 class="font-extrabold text-xs text-on-surface uppercase tracking-wider">Undang Anggota Baru</h5>
            <span class="font-mono text-[9px] text-on-surface-variant/50">
              {{ slotsLeft }} slot tersisa
            </span>
          </div>
          <div class="flex items-center gap-2.5">
            <div class="relative flex-1">
              <Mail class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
              <input
                v-model="inviteEmail"
                type="email"
                placeholder="Masukkan email anggota"
                @keyup.enter="handleInvite"
                class="w-full bg-white border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 pl-10 pr-4 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all"
              />
            </div>
            <button
              @click="handleInvite"
              :disabled="inviting || !inviteEmail"
              class="bg-[#04000D] hover:bg-black text-[#DCEEB1] hover:text-[#DCEEB1]/90 px-5 py-2.5 rounded-xl text-xs font-bold transition-all disabled:opacity-40 shadow-sm whitespace-nowrap"
            >
              {{ inviting ? 'Mengundang...' : 'Kirim' }}
            </button>
          </div>
          <p v-if="inviteError" class="mt-3 text-[11px] font-semibold text-accent-magenta bg-[#FF3D8B]/5 border border-accent-magenta/20 rounded-xl px-4 py-2.5 flex items-center gap-2">
            <AlertTriangle class="w-3.5 h-3.5 flex-shrink-0" /> {{ inviteError }}
          </p>
          <p v-if="inviteSuccess" class="mt-3 text-[11px] font-semibold text-green-700 bg-[#DCEEB1]/10 border border-[#DCEEB1]/45 rounded-xl px-4 py-2.5 flex items-center gap-2">
            <CheckCircle class="w-3.5 h-3.5 flex-shrink-0" /> {{ inviteSuccess }}
          </p>
        </div>

        <!-- Footer info for non-ketua -->
        <div v-if="team?.ketua?.id !== auth.user?.id" class="border-t border-slate-100 px-6 py-4 bg-slate-50/50">
          <p class="text-[11px] text-on-surface-variant/60 flex items-center gap-1.5">
            <Users class="w-3.5 h-3.5" /> Kamu adalah anggota tim ini. Hanya ketua yang dapat mengelola anggota.
          </p>
        </div>

        <!-- Footer info for ketua when locked -->
        <div v-if="team?.ketua?.id === auth.user?.id && team?.team_locked" class="border-t border-slate-100 px-6 py-4 bg-slate-50/50">
          <p class="text-[11px] text-on-surface-variant/60 flex items-center gap-1.5">
            <Lock class="w-3.5 h-3.5" /> Tim terkunci.
            <span v-if="!team?.unlock_requested">
              <button @click="handleRequestUnlock" class="text-accent-magenta hover:underline font-semibold">Ajukan buka kunci</button>
              untuk mengubah anggota.
            </span>
            <span v-else>Permohonan buka kunci telah dikirim ke admin.</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
