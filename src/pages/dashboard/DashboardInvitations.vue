<script setup>
import { ref, onMounted } from 'vue'
import api from '../../utils/api'
import { useToast } from '../../composables/useToast'
import { Mail, Check, X, Inbox, ExternalLink } from 'lucide-vue-next'

const invitations = ref([])
const { showToast } = useToast()
const loading = ref(true)
const actionLoading = ref(null)

async function fetchInvitations() {
  try {
    const res = await api.get('/invitations/pending')
    invitations.value = res.data.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function handleAccept(invitation) {
  actionLoading.value = invitation.id
  try {
    await api.put(`/invitations/${invitation.id}/accept`)
    await fetchInvitations()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal menerima undangan', 'error')
  } finally {
    actionLoading.value = null
  }
}

async function handleReject(invitation) {
  actionLoading.value = invitation.id
  try {
    await api.put(`/invitations/${invitation.id}/reject`)
    await fetchInvitations()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal menolak undangan', 'error')
  } finally {
    actionLoading.value = null
  }
}

onMounted(fetchInvitations)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-8">
      <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Undangan</span>
      <h1 class="font-extrabold text-3xl md:text-4xl tracking-tight text-on-surface">Undangan Tim</h1>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 2" :key="i" class="h-28 bg-slate-50 border border-slate-100 rounded-2xl animate-pulse"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="invitations.length === 0" class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-12 md:p-16 text-center">
      <div class="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-5">
        <Inbox class="w-6 h-6 text-on-surface-variant/40" />
      </div>
      <h3 class="font-bold text-lg text-on-surface mb-2">Tidak Ada Undangan</h3>
      <p class="text-sm text-on-surface-variant/80 max-w-xs mx-auto">Kamu belum memiliki undangan bergabung tim</p>
    </div>

    <!-- List -->
    <div v-else class="space-y-4">
      <p class="text-xs text-on-surface-variant/70">Kamu memiliki {{ invitations.length }} undangan bergabung tim</p>
      <div
        v-for="inv in invitations"
        :key="inv.id"
        class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6"
      >
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 rounded-xl bg-[#DCEEB1]/20 border border-[#DCEEB1]/30 flex items-center justify-center flex-shrink-0">
            <Mail class="w-5 h-5 text-green-600" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
              <div class="text-sm">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-mono text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-on-surface-variant">{{ inv.pendaftaran?.lomba?.kode }}</span>
                  <span class="font-extrabold text-on-surface">{{ inv.pendaftaran?.team_name || 'Tim ' + inv.invited_by?.name }}</span>
                </div>
                <p class="text-on-surface-variant/80 mt-2 leading-relaxed">
                  Kamu diundang bergabung di kompetisi <strong>{{ inv.pendaftaran?.lomba?.title }}</strong> oleh <strong>{{ inv.invited_by?.name }}</strong> ({{ inv.email }})
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3 mt-4 pt-4 border-t border-slate-100">
              <button
                @click="handleAccept(inv)"
                :disabled="actionLoading !== null"
                class="flex-1 inline-flex items-center justify-center gap-1.5 bg-[#04000D] hover:bg-black text-[#DCEEB1] hover:text-[#DCEEB1]/90 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm disabled:opacity-50"
              >
                <Check class="w-3.5 h-3.5" /> {{ actionLoading === inv.id ? 'Memproses...' : 'Terima' }}
              </button>
              <button
                @click="handleReject(inv)"
                :disabled="actionLoading !== null"
                class="flex-1 inline-flex items-center justify-center gap-1.5 bg-white hover:bg-slate-50 text-accent-magenta border border-slate-200 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm disabled:opacity-50"
              >
                <X class="w-3.5 h-3.5" /> {{ actionLoading === inv.id ? 'Memproses...' : 'Tolak' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
