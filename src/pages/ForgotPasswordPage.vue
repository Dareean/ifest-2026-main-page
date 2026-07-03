<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../utils/api'
import { Mail, ArrowLeft, CheckCircle } from 'lucide-vue-next'
import logoUntad from '../assets/logo_utama/logo_untad.webp'
import logoHmti from '../assets/logo_utama/HMTI LOGO.webp'
import logoIfest from '../assets/logo_utama/Logo-IFEST-2026.webp'

const router = useRouter()
const email = ref('')
const error = ref('')
const success = ref(false)
const isSubmitting = ref(false)

async function handleSubmit() {
  error.value = ''
  success.value = false
  isSubmitting.value = true
  try {
    await api.post('/auth/forgot-password', { email: email.value })
    success.value = true
  } catch (e) {
    error.value = e.response?.data?.message || e.response?.data?.errors?.email?.[0] || 'Gagal mengirim link reset password'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen riso-canvas bg-background flex items-center justify-center px-4 py-12 relative overflow-hidden">
    <div class="w-full max-w-md relative z-10">
      <router-link to="/login" class="inline-flex items-center gap-2 font-mono text-xs font-bold uppercase tracking-widest text-on-surface-variant hover:text-on-surface transition-colors mb-8 group">
        <ArrowLeft class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" /> Kembali ke Login
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
          <h1 class="font-black text-2xl uppercase tracking-tighter text-on-surface riso-bleed">Lupa Password</h1>
          <p class="font-mono text-xs text-on-surface-variant mt-1.5">Masukkan email kamu untuk menerima link reset password</p>
        </div>

        <div v-if="error" class="bg-[#FF3D8B]/5 border border-accent-magenta/30 rounded-xl px-4 py-3 mb-6 font-mono text-xs font-bold text-on-surface">{{ error }}</div>

        <div v-if="success" class="bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 rounded-xl px-5 py-5 mb-6 flex items-start gap-3">
          <CheckCircle class="w-5 h-5 text-green-700 mt-0.5 flex-shrink-0" />
          <div>
            <p class="font-bold text-sm text-on-surface">Link terkirim!</p>
            <p class="font-mono text-xs text-on-surface-variant/80 mt-1">Cek email {{ email }} untuk link reset password. Jika tidak muncul, periksa folder spam.</p>
          </div>
        </div>

        <form v-if="!success" @submit.prevent="handleSubmit" class="space-y-5">
          <div>
            <label class="block font-mono text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1.5">Email</label>
            <div class="relative">
              <Mail class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
              <input v-model="email" type="email" required placeholder="email@example.com" class="w-full bg-[#F5F5F5] border border-[#04000D]/30 rounded-xl py-3 pl-11 pr-4 font-mono text-sm font-bold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none focus:border-[#04000D] transition-colors" />
            </div>
          </div>

          <button type="submit" :disabled="isSubmitting" class="riso-btn-plate w-full bg-[#04000D] text-white py-3.5 rounded-full font-mono text-xs font-black uppercase tracking-wider select-none disabled:opacity-40" style="--plate-color: #FDE047;">
            {{ isSubmitting ? 'Mengirim...' : 'Kirim Link Reset' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
