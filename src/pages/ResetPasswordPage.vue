<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../utils/api'
import { Lock, Eye, EyeOff, ArrowLeft, CheckCircle } from 'lucide-vue-next'
import logoUntad from '../assets/logo_utama/logo_untad.webp'
import logoHmti from '../assets/logo_utama/HMTI LOGO.webp'
import logoIfest from '../assets/logo_utama/Logo-IFEST-2026.webp'

const router = useRouter()
const route = useRoute()
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const error = ref('')
const success = ref(false)
const isSubmitting = ref(false)

const token = route.params.token
const email = route.query.email

onMounted(() => {
  if (!token || !email) {
    error.value = 'Link reset password tidak valid. Silakan coba lagi.'
  }
})

async function handleSubmit() {
  error.value = ''
  isSubmitting.value = true
  try {
    await api.post('/auth/reset-password', {
      email,
      token,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    success.value = true
  } catch (e) {
    error.value = e.response?.data?.message || e.response?.data?.errors?.password?.[0] || 'Gagal mereset password'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen riso-canvas bg-background flex items-center justify-center px-4 py-12 relative overflow-hidden">
    <div class="w-full max-w-md relative z-10">
      <router-link v-if="!success" to="/login" class="inline-flex items-center gap-2 font-mono text-xs font-bold uppercase tracking-widest text-on-surface-variant hover:text-on-surface transition-colors mb-8 group">
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
          <h1 class="font-black text-2xl uppercase tracking-tighter text-on-surface riso-bleed">Reset Password</h1>
          <p class="font-mono text-xs text-on-surface-variant mt-1.5">Buat password baru untuk akun kamu</p>
        </div>

        <div v-if="error" class="bg-[#FF3D8B]/5 border border-accent-magenta/30 rounded-xl px-4 py-3 mb-6 font-mono text-xs font-bold text-on-surface">{{ error }}</div>

        <div v-if="success" class="text-center">
          <div class="bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 rounded-xl px-5 py-6 mb-6">
            <CheckCircle class="w-10 h-10 text-green-700 mx-auto mb-3" />
            <p class="font-bold text-lg text-on-surface">Password Berhasil Diubah!</p>
            <p class="font-mono text-xs text-on-surface-variant/80 mt-1">Kamu sekarang bisa login dengan password baru.</p>
          </div>
          <router-link to="/login" class="riso-btn-plate inline-block w-full bg-[#04000D] text-white py-3.5 rounded-full font-mono text-xs font-black uppercase tracking-wider text-center select-none" style="--plate-color: #FDE047;">
            Kembali ke Login
          </router-link>
        </div>

        <form v-if="!success" @submit.prevent="handleSubmit" class="space-y-5">
          <div>
            <label class="block font-mono text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1.5">Password Baru</label>
            <div class="relative">
              <Lock class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
              <input v-model="password" :type="showPassword ? 'text' : 'password'" required minlength="8" placeholder="Minimal 8 karakter" class="w-full bg-[#F5F5F5] border border-[#04000D]/30 rounded-xl py-3 pl-11 pr-11 font-mono text-sm font-bold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none focus:border-[#04000D] transition-colors" />
              <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant/40 hover:text-on-surface transition-colors">
                <Eye v-if="!showPassword" class="w-4 h-4" />
                <EyeOff v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div>
            <label class="block font-mono text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1.5">Konfirmasi Password Baru</label>
            <div class="relative">
              <Lock class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
              <input v-model="passwordConfirmation" :type="showPassword ? 'text' : 'password'" required minlength="8" placeholder="Ulangi password baru" class="w-full bg-[#F5F5F5] border border-[#04000D]/30 rounded-xl py-3 pl-11 pr-4 font-mono text-sm font-bold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none focus:border-[#04000D] transition-colors" />
            </div>
          </div>

          <button type="submit" :disabled="isSubmitting" class="riso-btn-plate w-full bg-[#04000D] text-white py-3.5 rounded-full font-mono text-xs font-black uppercase tracking-wider select-none disabled:opacity-40" style="--plate-color: #FDE047;">
            {{ isSubmitting ? 'Memproses...' : 'Reset Password' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
