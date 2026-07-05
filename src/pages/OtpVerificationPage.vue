<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { Mail, ArrowLeft, ArrowRight } from 'lucide-vue-next'
import logoUntad from '../assets/logo_utama/logo_untad.webp'
import logoHmti from '../assets/logo_utama/HMTI LOGO.webp'
import logoIfest from '../assets/logo_utama/Logo-IFEST-2026.webp'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const email = ref(route.query.email || '')
const otp = ref('')
const error = ref('')
const success = ref('')
const isSubmitting = ref(false)
const countdown = ref(0)

onMounted(() => {
  if (!email.value) {
    router.push('/login')
  }
})

async function handleVerify() {
  error.value = ''
  isSubmitting.value = true
  try {
    await auth.verifyOtp(email.value, otp.value)
    router.push('/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message || 'Kode OTP salah'
  } finally {
    isSubmitting.value = false
  }
}

async function handleResend() {
  if (countdown.value > 0) return
  try {
    await auth.sendOtp(email.value)
    success.value = 'Kode OTP telah dikirim ulang'
    error.value = ''
    countdown.value = 60
    const interval = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) clearInterval(interval)
    }, 1000)
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal mengirim ulang OTP'
  }
}
</script>

<template>
  <div class="min-h-screen riso-canvas bg-background flex items-center justify-center px-4 py-12 relative overflow-hidden">
    <div class="w-full max-w-md relative z-10">
      <router-link to="/login" class="inline-flex items-center gap-2 font-mono text-xs font-bold uppercase tracking-widest text-on-surface-variant hover:text-on-surface transition-colors mb-8 group">
        <ArrowLeft class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" /> Kembali
      </router-link>

      <div class="bg-white shadow-[6px_6px_0px_0px_#04000D] rounded-2xl p-8">
        <div class="text-center mb-8">
          <div class="flex items-center justify-center gap-4 mb-5">
            <img :src="logoUntad" alt="Logo UNTAD" class="h-12 w-auto object-contain" />
            <div class="w-px h-8 bg-outline-variant"></div>
            <img :src="logoHmti" alt="Logo HMTI" class="h-12 w-auto object-contain" />
            <div class="w-px h-8 bg-outline-variant"></div>
            <img :src="logoIfest" alt="Logo I-FEST 2026" class="h-12 w-auto object-contain" />
          </div>
          <div class="w-14 h-14 bg-accent-magenta/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <Mail class="w-6 h-6 text-accent-magenta" />
          </div>
          <h1 class="font-black text-2xl uppercase tracking-tighter text-on-surface riso-bleed">Verifikasi Email</h1>
          <p class="font-mono text-xs text-on-surface-variant mt-1.5">
            Kami telah mengirim kode OTP ke <span class="font-bold text-on-surface">{{ email }}</span>
          </p>
        </div>

        <div v-if="error" class="bg-[#FF3D8B]/5 border border-accent-magenta/30 rounded-xl px-4 py-3 mb-6 font-mono text-xs font-bold text-on-surface">{{ error }}</div>
        <div v-if="success" class="bg-[#D6FF00]/10 border border-accent-lime/30 rounded-xl px-4 py-3 mb-6 font-mono text-xs font-bold text-on-surface">{{ success }}</div>

        <form @submit.prevent="handleVerify" class="space-y-5">
          <div>
            <label class="block font-mono text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1.5">Kode OTP</label>
            <input
              v-model="otp"
              type="text"
              maxlength="6"
              required
              placeholder="000000"
              class="w-full bg-[#F5F5F5] border border-[#04000D]/30 rounded-xl py-3 px-4 font-mono text-sm font-bold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none focus:border-[#04000D] transition-colors text-center tracking-[0.5em]"
            />
          </div>

          <button type="submit" :disabled="isSubmitting || otp.length !== 6" class="riso-btn-plate w-full bg-[#04000D] text-white py-3.5 rounded-full font-mono text-xs font-black uppercase tracking-wider select-none disabled:opacity-40" style="--plate-color: #FDE047;">
            {{ isSubmitting ? 'Memverifikasi...' : 'Verifikasi' }}
          </button>
        </form>

        <div class="text-center mt-6">
          <p class="font-mono text-xs text-on-surface-variant">
            Tidak menerima kode?
            <button @click="handleResend" :disabled="countdown > 0" class="font-bold text-accent-magenta hover:text-accent-magenta/80 transition-colors disabled:text-on-surface-variant/40 disabled:cursor-not-allowed ml-1">
              Kirim Ulang{{ countdown > 0 ? ` (${countdown}s)` : '' }}
            </button>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
