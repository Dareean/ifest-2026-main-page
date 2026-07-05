<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useConfirm } from '../../../composables/useConfirm'
import { useToast } from '../../../composables/useToast'
import api from '../../../utils/api'
import {
  ArrowLeft, Clock, CheckCircle, AlertTriangle, Lock, Unlock,
  Users, Mail, Send, Shield, X
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const confirmModal = useConfirm()
const { showToast } = useToast()
const loading = ref(true)
const error = ref('')
const data = ref(null)
const actionLoading = ref(false)
const rejectNotes = ref('')
const showRejectForm = ref(false)
const showRejectPaymentForm = ref(false)
const rejectPaymentNotes = ref('')

const reg = computed(() => data.value)

async function fetchDetail() {
  loading.value = true
  try {
    const res = await api.get(`/admin/pendaftarans/${route.params.id}`)
    data.value = res.data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal memuat detail'
  } finally {
    loading.value = false
  }
}

async function handleVerify() {
  if (!await confirmModal.confirm('Verifikasi pendaftaran ini?', 'Verifikasi Pendaftaran?')) return
  actionLoading.value = true
  try {
    await api.put(`/admin/pendaftarans/${route.params.id}/verify`)
    await fetchDetail()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal verifikasi', 'error')
  } finally {
    actionLoading.value = false
  }
}

async function handleReject() {
  actionLoading.value = true
  try {
    await api.put(`/admin/pendaftarans/${route.params.id}/reject`, { notes: rejectNotes.value })
    await fetchDetail()
    showRejectForm.value = false
    rejectNotes.value = ''
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal menolak', 'error')
  } finally {
    actionLoading.value = false
  }
}

async function handleApproveUnlock() {
  actionLoading.value = true
  try {
    await api.put(`/admin/pendaftarans/${route.params.id}/approve-unlock`)
    await fetchDetail()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal memproses', 'error')
  } finally {
    actionLoading.value = false
  }
}

async function handleVerifyPayment() {
  if (!await confirmModal.confirm('Verifikasi pembayaran ini?', 'Verifikasi Pembayaran?')) return
  actionLoading.value = true
  try {
    await api.put(`/admin/pendaftarans/${route.params.id}/verify-payment`)
    await fetchDetail()
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal verifikasi pembayaran', 'error')
  } finally {
    actionLoading.value = false
  }
}

async function handleRejectPayment() {
  if (!rejectPaymentNotes.value) {
    showToast('Alasan penolakan wajib diisi', 'error')
    return
  }
  actionLoading.value = true
  try {
    await api.put(`/admin/pendaftarans/${route.params.id}/reject-payment`, { payment_notes: rejectPaymentNotes.value })
    await fetchDetail()
    showRejectPaymentForm.value = false
    rejectPaymentNotes.value = ''
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal menolak pembayaran', 'error')
  } finally {
    actionLoading.value = false
  }
}

async function handleSendNotification() {
  const msg = await confirmModal.prompt('Tulis pesan notifikasi untuk ketua tim:', 'Kirim Notifikasi', {
    placeholder: 'Masukkan pesan notifikasi...',
  })
  if (!msg) return
  actionLoading.value = true
  try {
    await api.post('/admin/notifications', {
      judul: 'Pesan dari Admin',
      pesan: msg,
      user_ids: [reg.value.user_id],
    })
    showToast('Notifikasi terkirim', 'success')
  } catch (e) {
    showToast(e.response?.data?.message || 'Gagal', 'error')
  } finally {
    actionLoading.value = false
  }
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

onMounted(fetchDetail)
</script>

<template>
  <div>
    <div class="flex items-start gap-3 mb-8">
      <button @click="router.push('/dashboard/admin/pendaftaran')" class="mt-1 w-10 h-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition-colors flex items-center justify-center text-on-surface-variant shadow-sm flex-shrink-0">
        <ArrowLeft class="w-5 h-5" />
      </button>
      <div class="min-w-0 flex-1">
        <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Detail Pendaftaran</span>
        <h1 class="font-extrabold text-2xl md:text-3xl tracking-tight text-on-surface leading-tight truncate">{{ reg?.team_name || reg?.user?.name || 'Detail' }}</h1>
      </div>
    </div>

    <div v-if="loading" class="space-y-4">
      <div class="h-40 bg-slate-50 border border-slate-100 rounded-2xl animate-pulse"></div>
      <div class="h-40 bg-slate-50 border border-slate-100 rounded-2xl animate-pulse"></div>
    </div>

    <div v-else-if="error" class="bg-[#FF3D8B]/5 border border-accent-magenta/20 rounded-2xl p-6 text-center">
      <p class="text-sm font-bold text-on-surface">{{ error }}</p>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main info -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Status card -->
        <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-extrabold text-sm text-on-surface">Informasi Pendaftaran</h2>
            <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border" :class="statusConfig[reg?.status]?.class || ''">
              <component :is="statusConfig[reg?.status]?.icon" class="w-3 h-3" />
              {{ statusConfig[reg?.status]?.label }}
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div>
              <span class="text-[9px] font-bold uppercase text-on-surface-variant/50 tracking-wider">Nama Tim</span>
              <p class="font-bold text-on-surface mt-0.5">{{ reg?.team_name || '-' }}</p>
            </div>
            <div>
              <span class="text-[9px] font-bold uppercase text-on-surface-variant/50 tracking-wider">Lomba</span>
              <p class="font-bold text-on-surface mt-0.5">{{ reg?.lomba?.kode }} - {{ reg?.lomba?.title }}</p>
            </div>
            <div>
              <span class="text-[9px] font-bold uppercase text-on-surface-variant/50 tracking-wider">Ketua Tim</span>
              <p class="font-bold text-on-surface mt-0.5">{{ reg?.user?.name }}</p>
              <p class="font-mono text-[10px] text-on-surface-variant/60">{{ reg?.user?.email }}</p>
            </div>
            <div>
              <span class="text-[9px] font-bold uppercase text-on-surface-variant/50 tracking-wider">Tanggal Daftar</span>
              <p class="font-semibold text-on-surface mt-0.5">{{ new Date(reg?.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="reg?.notes" class="mt-4 bg-[#FF3D8B]/5 border border-accent-magenta/20 rounded-xl p-3.5 text-xs">
            <span class="text-[9px] font-bold uppercase text-accent-magenta">Catatan Penolakan</span>
            <p class="text-on-surface-variant/90 mt-1">{{ reg.notes }}</p>
          </div>
        </div>

        <!-- Team members -->
        <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6">
          <h2 class="font-extrabold text-sm text-on-surface mb-4 flex items-center gap-2">
            <Users class="w-4 h-4 text-accent-magenta" /> Anggota Tim
          </h2>
          <div class="space-y-2.5">
            <div class="flex items-center justify-between text-xs bg-slate-50 rounded-xl p-3 border border-slate-100">
              <div>
                <span class="font-bold text-on-surface">{{ reg?.user?.name }}</span>
                <span class="font-mono text-[9px] font-bold uppercase ml-2 px-1.5 py-0.5 rounded bg-black text-[#DCEEB1]">Ketua</span>
              </div>
            </div>
            <div v-for="inv in reg?.team_invitations?.filter(i => i.status === 'accepted')" :key="inv.id" class="flex items-center justify-between text-xs bg-white rounded-xl p-3 border border-slate-100">
              <div>
                <span class="font-bold text-on-surface">{{ inv.invited_user?.name }}</span>
                <span class="font-mono text-[9px] font-bold uppercase ml-2 px-1.5 py-0.5 rounded bg-slate-100 text-on-surface-variant">Anggota</span>
              </div>
              <span class="font-mono text-[9px] uppercase px-2 py-0.5 rounded-full bg-[#DCEEB1]/30 text-green-700 font-bold">Joined</span>
            </div>
            <div v-for="inv in reg?.team_invitations?.filter(i => i.status === 'pending')" :key="inv.id" class="flex items-center justify-between text-xs bg-white rounded-xl p-3 border border-dashed border-slate-200">
              <div>
                <span class="font-bold text-on-surface">{{ inv.email }}</span>
              </div>
              <span class="font-mono text-[9px] uppercase px-2 py-0.5 rounded-full bg-[#FFF9E6] text-amber-600 font-bold">Pending</span>
            </div>
            <p v-if="!reg?.team_invitations?.length" class="text-xs text-on-surface-variant/50 py-2">Belum ada anggota tim</p>
          </div>

          <!-- Unlock request -->
          <div v-if="reg?.unlock_requested" class="mt-4 bg-accent-magenta/5 border border-accent-magenta/20 rounded-xl p-4 flex items-start gap-3">
            <Lock class="w-5 h-5 text-accent-magenta mt-0.5 flex-shrink-0" />
            <div class="text-xs flex-1">
              <p class="font-bold text-on-surface">Permohonan Buka Kunci</p>
              <p class="text-on-surface-variant/80 mt-0.5">Tim ini meminta untuk membuka perubahan anggota.</p>
              <button @click="handleApproveUnlock" :disabled="actionLoading" class="mt-3 bg-[#04000D] hover:bg-black text-[#DCEEB1] px-4 py-1.5 rounded-lg font-bold text-[10px] uppercase tracking-wider transition-all disabled:opacity-40">
                {{ actionLoading ? 'Memproses...' : 'Setujui Buka Kunci' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Payment info -->
        <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6">
          <h2 class="font-extrabold text-sm text-on-surface mb-4 flex items-center gap-2">
            <Shield class="w-4 h-4 text-accent-magenta" /> Pembayaran
          </h2>
          <div class="flex items-center gap-3 mb-4">
            <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border" :class="paymentStatusConfig[reg?.payment_status]?.class || ''">
              <component :is="paymentStatusConfig[reg?.payment_status]?.icon" class="w-3 h-3" />
              {{ paymentStatusConfig[reg?.payment_status]?.label }}
            </span>
            <span v-if="reg?.payment_verified_at" class="font-mono text-[10px] text-on-surface-variant/60">
              {{ new Date(reg.payment_verified_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
            </span>
          </div>

          <!-- Payment Proof -->
          <div v-if="reg?.payment_proof" class="bg-slate-50 rounded-xl p-4 border border-slate-100">
            <span class="text-[9px] font-bold uppercase text-on-surface-variant/40 tracking-wider">Link Bukti Pembayaran</span>
            <a :href="reg.payment_proof" target="_blank" class="flex items-center gap-2 mt-2 text-xs font-bold text-sky-600 hover:underline truncate">
              {{ reg.payment_proof }}
            </a>
          </div>

          <!-- Payment Notes (rejected) -->
          <div v-if="reg?.payment_notes" class="mt-4 bg-accent-magenta/5 border border-accent-magenta/20 rounded-xl p-3.5 text-xs">
            <span class="text-[9px] font-bold uppercase text-accent-magenta">Catatan Penolakan</span>
            <p class="text-on-surface-variant/90 mt-1">{{ reg.payment_notes }}</p>
          </div>
        </div>

        <!-- Submission -->
        <div v-if="reg?.submission" class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6">
          <h2 class="font-extrabold text-sm text-on-surface mb-4 flex items-center gap-2">
            <Send class="w-4 h-4 text-accent-magenta" /> Karya
          </h2>
          <div class="text-xs">
            <a :href="reg.submission.link_drive" target="_blank" class="font-bold text-sky-600 hover:underline break-all">{{ reg.submission.link_drive }}</a>
            <p v-if="reg.submission.catatan" class="text-on-surface-variant/80 mt-2 bg-slate-50 rounded-xl p-3">{{ reg.submission.catatan }}</p>
          </div>
        </div>
      </div>

      <!-- Sidebar actions -->
      <div class="space-y-4">
        <!-- Quick actions -->
        <div v-if="reg?.status === 'pending'" class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-5 space-y-3">
          <h3 class="font-extrabold text-xs text-on-surface uppercase tracking-wider">Aksi Tim</h3>
          <button @click="handleVerify" :disabled="actionLoading || reg.payment_status !== 'verified'" class="w-full bg-[#04000D] hover:bg-black text-[#DCEEB1] py-3 rounded-xl text-xs font-bold transition-all disabled:opacity-40 shadow-sm flex items-center justify-center gap-1.5" :title="reg.payment_status !== 'verified' && reg.lomba?.fee?.toLowerCase() !== 'gratis' ? 'Verifikasi pembayaran terlebih dahulu' : ''">
            <CheckCircle class="w-4 h-4" /> {{ actionLoading ? 'Memproses...' : 'Verifikasi Tim' }}
          </button>
          <button @click="showRejectForm = !showRejectForm" class="w-full bg-white hover:bg-slate-50 text-accent-magenta border border-slate-200 py-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5">
            <AlertTriangle class="w-4 h-4" /> Tolak
          </button>
          <div v-if="showRejectForm" class="space-y-2.5 pt-2 border-t border-slate-100">
            <textarea v-model="rejectNotes" rows="3" placeholder="Catatan penolakan (opsional)" class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl p-3 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all resize-none"></textarea>
            <button @click="handleReject" :disabled="actionLoading" class="w-full bg-accent-magenta hover:bg-accent-magenta/90 text-white py-2.5 rounded-xl text-xs font-bold transition-all disabled:opacity-40">
              {{ actionLoading ? 'Memproses...' : 'Konfirmasi Tolak' }}
            </button>
          </div>
        </div>

        <!-- Payment actions -->
        <div v-if="reg?.payment_status === 'pending'" class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-5 space-y-3">
          <h3 class="font-extrabold text-xs text-on-surface uppercase tracking-wider">Aksi Pembayaran</h3>
          <button @click="handleVerifyPayment" :disabled="actionLoading" class="w-full bg-[#04000D] hover:bg-black text-[#DCEEB1] py-3 rounded-xl text-xs font-bold transition-all disabled:opacity-40 shadow-sm flex items-center justify-center gap-1.5">
            <CheckCircle class="w-4 h-4" /> {{ actionLoading ? 'Memproses...' : 'Verifikasi Pembayaran' }}
          </button>
          <button @click="showRejectPaymentForm = !showRejectPaymentForm" class="w-full bg-white hover:bg-slate-50 text-accent-magenta border border-slate-200 py-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5">
            <X class="w-4 h-4" /> Tolak Bukti Bayar
          </button>
          <div v-if="showRejectPaymentForm" class="space-y-2.5 pt-2 border-t border-slate-100">
            <textarea v-model="rejectPaymentNotes" rows="3" placeholder="Alasan penolakan (wajib diisi)" class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl p-3 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all resize-none"></textarea>
            <button @click="handleRejectPayment" :disabled="actionLoading || !rejectPaymentNotes" class="w-full bg-accent-magenta hover:bg-accent-magenta/90 text-white py-2.5 rounded-xl text-xs font-bold transition-all disabled:opacity-40">
              {{ actionLoading ? 'Memproses...' : 'Konfirmasi Tolak' }}
            </button>
          </div>
        </div>

        <!-- Kirim notifikasi -->
        <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-5">
          <h3 class="font-extrabold text-xs text-on-surface uppercase tracking-wider mb-3">Hubungi Peserta</h3>
          <button @click="handleSendNotification" class="w-full bg-white hover:bg-slate-50 text-on-surface border border-slate-200 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5">
            <Mail class="w-4 h-4" /> Kirim Notifikasi
          </button>
        </div>

        <!-- Info -->
        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5 text-xs space-y-3">
          <div>
            <span class="text-[9px] font-bold uppercase text-on-surface-variant/50 tracking-wider">Gelombang</span>
            <p class="font-bold text-on-surface mt-0.5">
              <span v-if="reg?.gelombang" class="inline-block font-mono text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full" :class="reg.gelombang === '1' ? 'bg-pink-100 text-pink-700 border border-pink-200' : 'bg-blue-100 text-blue-700 border border-blue-200'">
                Gelombang {{ reg.gelombang }}
              </span>
              <span v-else class="text-on-surface-variant/60">-</span>
            </p>
          </div>
          <div>
            <span class="text-[9px] font-bold uppercase text-on-surface-variant/50 tracking-wider">Terkunci</span>
            <p class="font-bold text-on-surface mt-0.5">{{ reg?.team_locked ? 'Ya' : 'Tidak' }}</p>
          </div>
          <div>
            <span class="text-[9px] font-bold uppercase text-on-surface-variant/50 tracking-wider">Minta Buka Kunci</span>
            <p class="font-bold text-on-surface mt-0.5">{{ reg?.unlock_requested ? 'Ya' : 'Tidak' }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
