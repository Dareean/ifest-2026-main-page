<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { Mail, Lock, Eye, EyeOff, ArrowLeft } from 'lucide-vue-next'
import logoUntad from '../assets/logo_utama/logo_untad.webp'
import logoHmti from '../assets/logo_utama/HMTI LOGO.webp'
import logoIfest from '../assets/logo_utama/Logo-IFEST-2026.webp'

const router = useRouter()
const auth = useAuthStore()

const form = ref({ email: '', password: '' })
const showPassword = ref(false)
const error = ref('')
const isSubmitting = ref(false)

async function handleLogin() {
  error.value = ''
  isSubmitting.value = true
  try {
    await auth.login(form.value)
    router.push('/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message || 'Email atau password salah'
  } finally {
    isSubmitting.value = false
  }
}

async function handleGoogle() {
  try {
    await auth.googleLogin()
  } catch {
    error.value = 'Gagal membuka Google login'
  }
}
</script>

<template>
  <div class="min-h-screen riso-canvas bg-background flex items-center justify-center px-4 py-12 relative overflow-hidden">
    <div class="w-full max-w-md relative z-10">
      <!-- Back -->
      <router-link to="/" class="inline-flex items-center gap-2 font-mono text-xs font-bold uppercase tracking-widest text-on-surface-variant hover:text-on-surface transition-colors mb-8 group">
        <ArrowLeft class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" /> Kembali
      </router-link>

      <!-- Card -->
      <div class="bg-white shadow-[6px_6px_0px_0px_#04000D] rounded-2xl p-8">
        <div class="text-center mb-8">
          <!-- Logos -->
          <div class="flex items-center justify-center gap-4 mb-5">
            <img :src="logoUntad" alt="Logo UNTAD" class="h-12 w-auto object-contain" />
            <div class="w-px h-8 bg-outline-variant"></div>
            <img :src="logoHmti" alt="Logo HMTI" class="h-12 w-auto object-contain" />
            <div class="w-px h-8 bg-outline-variant"></div>
            <img :src="logoIfest" alt="Logo I-FEST 2026" class="h-12 w-auto object-contain" />
          </div>
          <h1 class="font-black text-2xl uppercase tracking-tighter text-on-surface riso-bleed">Masuk</h1>
          <p class="font-mono text-xs text-on-surface-variant mt-1.5">Masuk ke akun I-FEST 2026 kamu</p>
        </div>

        <div v-if="error" class="bg-[#FF3D8B]/5 border border-accent-magenta/30 rounded-xl px-4 py-3 mb-6 font-mono text-xs font-bold text-on-surface">{{ error }}</div>

        <form @submit.prevent="handleLogin" class="space-y-5">
          <div>
            <label class="block font-mono text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1.5">Email</label>
            <div class="relative">
              <Mail class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
              <input v-model="form.email" type="email" required placeholder="email@example.com" class="w-full bg-[#F5F5F5] border border-[#04000D]/30 rounded-xl py-3 pl-11 pr-4 font-mono text-sm font-bold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none focus:border-[#04000D] transition-colors" />
            </div>
          </div>

          <div>
            <label class="block font-mono text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1.5">Password</label>
            <div class="relative">
              <Lock class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
              <input v-model="form.password" :type="showPassword ? 'text' : 'password'" required placeholder="••••••••" class="w-full bg-[#F5F5F5] border border-[#04000D]/30 rounded-xl py-3 pl-11 pr-11 font-mono text-sm font-bold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none focus:border-[#04000D] transition-colors" />
              <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant/40 hover:text-on-surface transition-colors">
                <Eye v-if="!showPassword" class="w-4 h-4" />
                <EyeOff v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <button type="submit" :disabled="isSubmitting" class="riso-btn-plate w-full bg-[#04000D] text-white py-3.5 rounded-full font-mono text-xs font-black uppercase tracking-wider select-none disabled:opacity-40" style="--plate-color: #FDE047;">
            {{ isSubmitting ? 'Memproses...' : 'Masuk' }}
          </button>
        </form>

        <div class="flex items-center gap-3 my-6">
          <div class="flex-1 h-px bg-[#04000D]/20"></div>
          <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-on-surface-variant/50">Atau</span>
          <div class="flex-1 h-px bg-[#04000D]/20"></div>
        </div>

        <button @click="handleGoogle" class="riso-btn-plate w-full bg-[#F5F5F5] text-[#04000D] rounded-full py-3 font-mono text-xs font-bold uppercase tracking-wider flex items-center justify-center gap-3 select-none" style="--plate-color: #D6FF00;">
          <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
          Masuk dengan Google
        </button>

        <p class="text-center mt-6 font-mono text-xs text-on-surface-variant">
          Belum punya akun?
          <router-link to="/register" class="font-bold text-on-surface underline decoration-on-surface-variant/30 underline-offset-2 hover:decoration-on-surface transition-all">Daftar</router-link>
        </p>
      </div>
    </div>
  </div>
</template>
