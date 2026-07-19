<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import api from '../../utils/api'
import { Save, Lock, Eye, EyeOff, CheckCircle, User, Building, Phone, Chrome, Camera, X, Shield, ShieldOff, Smartphone } from 'lucide-vue-next'

const apiBase = import.meta.env.VITE_API_URL?.replace(/\/api$/, '') || 'http://localhost:8000'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const googleMsg = ref('')
const connectLoading = ref(false)
const disconnectLoading = ref(false)

const form = ref({ name: '', phone: '', institution: '' })
const passwordForm = ref({ current_password: '', new_password: '', new_password_confirmation: '' })
const showCurrent = ref(false)
const showNew = ref(false)
const profileError = ref('')
const passwordError = ref('')
const profileSuccess = ref('')
const passwordSuccess = ref('')
const savingProfile = ref(false)
const savingPassword = ref(false)

// Avatar upload state
const avatarInput = ref(null)
const avatarPreview = ref(null)
const avatarFile = ref(null)
const uploadingAvatar = ref(false)
const avatarError = ref('')
const avatarSuccess = ref('')

const currentAvatar = () => {
  const av = auth.user?.avatar
  if (!av || av === 'null' || av === 'undefined') return null
  return av
}

function onAvatarPick(e) {
  const file = e.target.files?.[0]
  if (!file) return
  if (file.size > 2 * 1024 * 1024) {
    avatarError.value = 'Ukuran foto maksimal 2MB'
    return
  }
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
  avatarError.value = ''
  avatarSuccess.value = ''
}

function cancelAvatarPick() {
  avatarFile.value = null
  avatarPreview.value = null
  avatarError.value = ''
  if (avatarInput.value) avatarInput.value.value = ''
}

async function uploadAvatar() {
  if (!avatarFile.value) return
  uploadingAvatar.value = true
  avatarError.value = ''
  avatarSuccess.value = ''
  const formData = new FormData()
  formData.append('avatar', avatarFile.value)
  try {
    const res = await api.post('/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    auth.user = res.data.user
    avatarSuccess.value = 'Foto profil berhasil diperbarui!'
    avatarFile.value = null
    avatarPreview.value = null
    if (avatarInput.value) avatarInput.value.value = ''
  } catch (e) {
    avatarError.value = e.response?.data?.errors?.avatar?.[0]
      || e.response?.data?.message
      || 'Gagal mengunggah foto'
  } finally {
    uploadingAvatar.value = false
  }
}

function initForm() {
  form.value.name = auth.user?.name || ''
  form.value.phone = auth.user?.phone || ''
  form.value.institution = auth.user?.institution || ''
}

const twoFactorEnabled = ref(false)
const twoFactorLoading = ref(false)
const twoFactorSecret = ref('')
const twoFactorQrUrl = ref('')
const twoFactorRecoveryCodes = ref([])
const twoFactorCode = ref('')
const twoFactorError = ref('')
const twoFactorSuccess = ref('')
const twoFactorSetupMode = ref(false)

async function load2faStatus() {
  try {
    const res = await auth.get2faStatus()
    twoFactorEnabled.value = res.enabled
  } catch { /* ignore */ }
}

async function enable2fa() {
  twoFactorError.value = ''
  twoFactorLoading.value = true
  try {
    const res = await auth.enable2fa()
    twoFactorSecret.value = res.secret
    twoFactorQrUrl.value = res.qr_code_url
    twoFactorRecoveryCodes.value = res.recovery_codes
    twoFactorSetupMode.value = true
  } catch (e) {
    twoFactorError.value = e.response?.data?.message || 'Gagal mengaktifkan 2FA'
  } finally {
    twoFactorLoading.value = false
  }
}

async function confirm2faSetup() {
  twoFactorError.value = ''
  twoFactorLoading.value = true
  try {
    await auth.confirm2fa(twoFactorCode.value)
    twoFactorEnabled.value = true
    twoFactorSetupMode.value = false
    twoFactorCode.value = ''
    twoFactorSuccess.value = '2FA berhasil diaktifkan'
    setTimeout(() => twoFactorSuccess.value = '', 5000)
  } catch (e) {
    twoFactorError.value = e.response?.data?.message || 'Kode 2FA tidak valid'
  } finally {
    twoFactorLoading.value = false
  }
}

async function disable2fa() {
  twoFactorError.value = ''
  const confirmed = confirm('Nonaktifkan 2FA? Masukkan kode 2FA untuk konfirmasi.')
  if (!confirmed) return
  const code = prompt('Masukkan kode 2FA:')
  if (!code) return
  twoFactorLoading.value = true
  try {
    await auth.disable2fa(code)
    twoFactorEnabled.value = false
    twoFactorSuccess.value = '2FA berhasil dinonaktifkan'
    setTimeout(() => twoFactorSuccess.value = '', 5000)
  } catch (e) {
    twoFactorError.value = e.response?.data?.message || 'Gagal menonaktifkan 2FA'
  } finally {
    twoFactorLoading.value = false
  }
}

function cancel2faSetup() {
  twoFactorSetupMode.value = false
  twoFactorCode.value = ''
  twoFactorError.value = ''
}

onMounted(async () => {
  try {
    await auth.fetchUser()
  } catch (e) {
    console.error('Gagal mengambil data profil terbaru:', e)
  }
  initForm()
  load2faStatus()

  if (route.query.google === 'connected') {
    googleMsg.value = 'Akun Google berhasil dihubungkan!'
    router.replace({ query: {} })
  } else if (route.query.google === 'error') {
    googleMsg.value = 'Gagal menghubungkan Google'
    router.replace({ query: {} })
  }
})

function handleConnectGoogle() {
  connectLoading.value = true
  const apiBase = import.meta.env.VITE_API_URL || `${window.location.origin}/api`
  window.location.href = `${apiBase}/auth/google/connect?redirect=true`
}

async function handleDisconnectGoogle() {
  disconnectLoading.value = true
  try {
    await auth.disconnectGoogle()
    googleMsg.value = 'Google berhasil diputuskan'
  } catch {
    googleMsg.value = 'Gagal memutuskan Google'
  } finally {
    disconnectLoading.value = false
  }
}

async function saveProfile() {
  profileError.value = ''
  profileSuccess.value = ''
  savingProfile.value = true
  try {
    const res = await api.put('/profile', form.value)
    auth.user = res.data.user
    profileSuccess.value = 'Profil berhasil diperbarui'
  } catch (e) {
    profileError.value = e.response?.data?.errors
      ? Object.values(e.response.data.errors).flat().join(', ')
      : (e.response?.data?.message || 'Gagal menyimpan profil')
  } finally {
    savingProfile.value = false
  }
}

async function savePassword() {
  passwordError.value = ''
  passwordSuccess.value = ''
  savingPassword.value = true
  try {
    await api.put('/password', passwordForm.value)
    passwordSuccess.value = 'Password berhasil diubah'
    passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  } catch (e) {
    passwordError.value = e.response?.data?.errors
      ? Object.values(e.response.data.errors).flat().join(', ')
      : (e.response?.data?.message || 'Gagal mengubah password')
  } finally {
    savingPassword.value = false
  }
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-10">
      <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Akun</span>
      <h1 class="font-extrabold text-3xl md:text-4xl tracking-tight text-on-surface">Profil</h1>
    </div>

    <!-- Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- Left Column: Avatar & Google Account Connection -->
      <div class="lg:col-span-5 space-y-6">
        
        <!-- Avatar Card -->
        <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6 md:p-8">
          <div class="flex flex-col items-center text-center">
            
            <!-- Avatar Zone: click to upload -->
            <div class="relative group mb-5">
              <div 
                @click="avatarInput.click()"
                class="w-28 h-28 rounded-2xl overflow-hidden bg-black cursor-pointer ring-2 ring-transparent group-hover:ring-accent-magenta/40 transition-all duration-200 relative shadow-sm"
              >
                <!-- Preview (picked file or current avatar) -->
                <img 
                  v-if="avatarPreview || currentAvatar()" 
                  :src="avatarPreview || (currentAvatar()?.startsWith('/storage') ? apiBase + currentAvatar() : currentAvatar())" 
                  class="w-full h-full object-cover"
                  alt="Avatar"
                />
                <!-- Fallback initial -->
                <div v-else class="w-full h-full flex items-center justify-center text-[#DCEEB1] font-mono font-bold text-3xl">
                  {{ auth.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                </div>

                <!-- Hover Overlay -->
                <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                  <Camera class="w-7 h-7 text-white" />
                </div>
              </div>

              <!-- Invisible file input -->
              <input 
                ref="avatarInput"
                type="file" 
                accept="image/jpeg,image/png,image/jpg,image/webp"
                class="hidden"
                @change="onAvatarPick"
              />
            </div>

            <!-- Profile Info -->
            <h2 class="font-extrabold text-xl tracking-tight text-on-surface mb-1 truncate max-w-full">{{ auth.user?.name }}</h2>
            <p class="font-mono text-xs text-on-surface-variant/80 truncate max-w-full mb-3">{{ auth.user?.email }}</p>

            <div class="flex flex-col items-center w-full">
              <span v-if="auth.user?.google_id" class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-[#3B82F6] bg-[#3B82F6]/5 border border-[#3B82F6]/10 px-3 py-1 rounded-full">
                <Chrome class="w-3 h-3 text-[#3B82F6]" /> Terhubung Google
              </span>

              <!-- Pick state: show upload/cancel buttons -->
              <div v-if="avatarFile" class="mt-4 flex flex-col items-center gap-2 w-full">
                <span class="text-[10px] text-on-surface-variant/60 font-semibold truncate max-w-full px-4">{{ avatarFile.name }}</span>
                <div class="flex items-center gap-2">
                  <button 
                    @click="uploadAvatar" 
                    :disabled="uploadingAvatar"
                    class="inline-flex items-center gap-1.5 bg-[#04000D] text-[#DCEEB1] px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all hover:bg-black disabled:opacity-50"
                  >
                    <Camera class="w-3 h-3" /> {{ uploadingAvatar ? 'Mengunggah...' : 'Simpan Foto' }}
                  </button>
                  <button 
                    @click="cancelAvatarPick"
                    :disabled="uploadingAvatar"
                    class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-accent-magenta hover:text-on-surface transition-colors disabled:opacity-40"
                  >
                    <X class="w-3 h-3" /> Batal
                  </button>
                </div>
              </div>

              <!-- Idle state: hint text -->
              <p v-else class="text-[10px] text-on-surface-variant/40 mt-1">
                Klik foto untuk mengubah · JPEG, PNG, WEBP · Maks 2MB
              </p>
            </div>

          </div>

          <!-- Avatar feedback messages -->
          <div class="mt-4">
            <div v-if="avatarSuccess" class="bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 rounded-xl px-4 py-3 text-xs font-semibold text-on-surface flex items-center gap-2">
              <CheckCircle class="w-4 h-4 text-on-surface-variant flex-shrink-0" /> {{ avatarSuccess }}
            </div>
            <div v-if="avatarError" class="bg-[#FF3D8B]/5 border border-accent-magenta/20 rounded-xl px-4 py-3 text-xs font-semibold text-accent-magenta">{{ avatarError }}</div>
          </div>
        </div>

        <!-- Google Connection Card -->
        <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6 md:p-8">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center">
              <Chrome class="w-4 h-4 text-on-surface-variant" />
            </div>
            <div>
              <h2 class="font-extrabold text-lg tracking-tight text-on-surface">Akun Google</h2>
              <p class="text-[10px] text-on-surface-variant/70 uppercase tracking-wider font-semibold">Koneksi akun</p>
            </div>
          </div>

          <div v-if="googleMsg" class="bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 rounded-xl px-4 py-3 mb-6 text-xs font-semibold text-on-surface flex items-center gap-2">
            <CheckCircle class="w-4 h-4 text-on-surface-variant" /> {{ googleMsg }}
          </div>

          <div v-if="auth.user?.google_id" class="flex flex-col gap-4 p-4 bg-slate-50 border border-slate-100/50 rounded-xl">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center overflow-hidden border border-slate-200 flex-shrink-0">
                <img v-if="auth.user?.avatar" :src="auth.user.avatar" class="w-full h-full object-cover" />
                <Chrome v-else class="w-5 h-5 text-on-surface-variant" />
              </div>
              <div class="min-w-0">
                <p class="text-sm font-bold text-on-surface truncate">Terhubung dengan Google</p>
                <p class="text-[10px] text-on-surface-variant/60">Gunakan Google untuk masuk cepat</p>
              </div>
            </div>
            <button @click="handleDisconnectGoogle" :disabled="disconnectLoading" class="text-xs font-bold uppercase tracking-wider text-accent-magenta hover:text-white border border-accent-magenta/20 hover:bg-[#FF3D8B]/10 w-full py-3 rounded-xl transition-all disabled:opacity-40">
              {{ disconnectLoading ? 'Memproses...' : 'Putuskan Koneksi' }}
            </button>
          </div>

          <div v-else>
            <p class="text-xs text-on-surface-variant/80 mb-4 leading-relaxed">Hubungkan akun Google untuk memudahkan login tanpa perlu mengingat password.</p>
            <button @click="handleConnectGoogle" :disabled="connectLoading" class="inline-flex items-center justify-center gap-2 bg-slate-50 hover:bg-slate-100 text-on-surface w-full py-3 rounded-xl text-xs font-bold hover:border-slate-300 transition-all disabled:opacity-40 border border-slate-200">
              <Chrome class="w-4 h-4" /> {{ connectLoading ? 'Mengalihkan...' : 'Hubungkan Google' }}
            </button>
          </div>
        </div>

      </div>

      <!-- Right Column: Personal Data & Password Forms -->
      <div class="lg:col-span-7 space-y-6">
        
        <!-- Personal Data Form Card -->
        <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6 md:p-8">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center">
              <User class="w-4 h-4 text-on-surface-variant" />
            </div>
            <div>
              <h2 class="font-extrabold text-lg tracking-tight text-on-surface">Data Diri</h2>
              <p class="text-[10px] text-on-surface-variant/70 uppercase tracking-wider font-semibold">Informasi profil utama</p>
            </div>
          </div>

          <!-- Success / Error for profile form -->
          <div v-if="profileSuccess" class="bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 rounded-xl px-4 py-3 mb-6 text-xs font-semibold text-on-surface flex items-center gap-2">
            <CheckCircle class="w-4 h-4 text-on-surface-variant" /> {{ profileSuccess }}
          </div>
          <div v-if="profileError" class="bg-[#FF3D8B]/5 border border-accent-magenta/20 rounded-xl px-4 py-3 mb-6 text-xs font-semibold text-accent-magenta">{{ profileError }}</div>

          <form @submit.prevent="saveProfile" class="space-y-5">
            <div>
              <label class="block text-xs font-semibold text-on-surface-variant/80 mb-1.5">Nama Lengkap</label>
              <div class="relative">
                <User class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
                <input v-model="form.name" type="text" required class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-3 pl-11 pr-4 text-sm font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:bg-white focus:outline-none transition-all" />
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-on-surface-variant/80 mb-1.5">No. Telepon</label>
                <div class="relative">
                  <Phone class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
                  <input v-model="form.phone" type="text" class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-3 pl-11 pr-4 text-sm font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:bg-white focus:outline-none transition-all" />
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-on-surface-variant/80 mb-1.5">Institusi</label>
                <div class="relative">
                  <Building class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
                  <input v-model="form.institution" type="text" class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-3 pl-11 pr-4 text-sm font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:bg-white focus:outline-none transition-all" />
                </div>
              </div>
            </div>
            <div class="pt-2">
              <button type="submit" :disabled="savingProfile" class="inline-flex items-center gap-2 bg-[#04000D] text-[#DCEEB1] hover:bg-black px-6 py-3 rounded-xl font-bold transition-all shadow-sm text-xs uppercase tracking-wider">
                <Save class="w-4 h-4" /> {{ savingProfile ? 'Menyimpan...' : 'Simpan Perubahan' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Change Password Card -->
        <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6 md:p-8">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center">
              <Lock class="w-4 h-4 text-on-surface-variant" />
            </div>
            <div>
              <h2 class="font-extrabold text-lg tracking-tight text-on-surface">Ubah Password</h2>
              <p class="text-[10px] text-on-surface-variant/70 uppercase tracking-wider font-semibold">Keamanan akun</p>
            </div>
          </div>

          <!-- Success / Error -->
          <div v-if="passwordSuccess" class="bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 rounded-xl px-4 py-3 mb-6 text-xs font-semibold text-on-surface flex items-center gap-2">
            <CheckCircle class="w-4 h-4 text-on-surface-variant" /> {{ passwordSuccess }}
          </div>
          <div v-if="passwordError" class="bg-[#FF3D8B]/5 border border-accent-magenta/20 rounded-xl px-4 py-3 mb-6 text-xs font-semibold text-accent-magenta">{{ passwordError }}</div>

          <form @submit.prevent="savePassword" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-on-surface-variant/80 mb-1.5">
                Password Saat Ini
                <span v-if="auth.user?.google_id" class="text-[10px] text-on-surface-variant/50 font-normal font-sans ml-1">(Opsional untuk login Google)</span>
              </label>
              <div class="relative">
                <Lock class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
                <input 
                  v-model="passwordForm.current_password" 
                  :type="showCurrent ? 'text' : 'password'" 
                  :required="!auth.user?.google_id" 
                  placeholder="Masukkan password saat ini"
                  class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-3 pl-11 pr-11 text-sm font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:bg-white focus:outline-none transition-all" 
                />
                <button type="button" @click="showCurrent = !showCurrent" class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant/40 hover:text-on-surface transition-colors">
                  <Eye v-if="!showCurrent" class="w-4 h-4" />
                  <EyeOff v-else class="w-4 h-4" />
                </button>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-on-surface-variant/80 mb-1.5">Password Baru</label>
                <div class="relative">
                  <Lock class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
                  <input v-model="passwordForm.new_password" :type="showNew ? 'text' : 'password'" required minlength="8" class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-3 pl-11 pr-11 text-sm font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:bg-white focus:outline-none transition-all" />
                  <button type="button" @click="showNew = !showNew" class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant/40 hover:text-on-surface transition-colors">
                    <Eye v-if="!showNew" class="w-4 h-4" />
                    <EyeOff v-else class="w-4 h-4" />
                  </button>
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-on-surface-variant/80 mb-1.5">Konfirmasi Password Baru</label>
                <div class="relative">
                  <Lock class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
                  <input v-model="passwordForm.new_password_confirmation" type="password" required class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-3 pl-11 pr-4 text-sm font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:bg-white focus:outline-none transition-all" />
                </div>
              </div>
            </div>
            <div class="pt-2">
              <button type="submit" :disabled="savingPassword" class="inline-flex items-center gap-2 bg-[#04000D] text-white px-6 py-3 rounded-xl font-bold transition-all shadow-sm text-xs uppercase tracking-wider">
                <Lock class="w-4 h-4" /> {{ savingPassword ? 'Menyimpan...' : 'Perbarui Password' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Two-Factor Authentication Card -->
        <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6 md:p-8">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center">
              <Shield class="w-4 h-4 text-on-surface-variant" />
            </div>
            <div>
              <h2 class="font-extrabold text-lg tracking-tight text-on-surface">Autentikasi 2 Faktor</h2>
              <p class="text-[10px] text-on-surface-variant/70 uppercase tracking-wider font-semibold">Keamanan tambahan</p>
            </div>
          </div>

          <div v-if="twoFactorSuccess" class="bg-[#DCEEB1]/30 border border-[#DCEEB1]/50 rounded-xl px-4 py-3 mb-6 text-xs font-semibold text-on-surface flex items-center gap-2">
            <CheckCircle class="w-4 h-4 text-on-surface-variant" /> {{ twoFactorSuccess }}
          </div>
          <div v-if="twoFactorError" class="bg-[#FF3D8B]/5 border border-accent-magenta/20 rounded-xl px-4 py-3 mb-6 text-xs font-semibold text-accent-magenta">{{ twoFactorError }}</div>

          <template v-if="twoFactorSetupMode">
            <div class="space-y-4">
              <div class="bg-blue-50/50 border border-blue-200/40 rounded-xl px-4 py-3">
                <p class="text-xs font-semibold text-on-surface">1. Scan QR code berikut dengan Google Authenticator:</p>
              </div>
              <div class="flex justify-center py-4">
                <img :src="twoFactorQrUrl" alt="QR Code 2FA" class="w-48 h-48 border-2 border-dashed border-slate-200 rounded-xl p-2" />
              </div>
              <div>
                <p class="text-xs font-semibold text-on-surface-variant/80 mb-2">Atau masukkan kode manual:</p>
                <code class="block bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 font-mono text-sm font-bold text-center text-on-surface select-all">{{ twoFactorSecret }}</code>
              </div>
              <div class="bg-amber-50/50 border border-amber-200/40 rounded-xl px-4 py-3">
                <p class="text-xs font-semibold text-on-surface">2. Masukkan kode 6 digit dari aplikasi untuk verifikasi:</p>
              </div>
              <div class="flex gap-3 items-end">
                <div class="flex-1">
                  <input v-model="twoFactorCode" type="text" inputmode="numeric" maxlength="6" placeholder="000000" class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-3 px-4 font-mono text-sm font-bold text-center text-on-surface placeholder:text-on-surface-variant/30 focus:bg-white focus:outline-none transition-all tracking-[0.5em]" />
                </div>
                <button @click="confirm2faSetup" :disabled="twoFactorLoading || twoFactorCode.length !== 6" class="bg-[#04000D] text-white px-6 py-3 rounded-xl font-bold transition-all shadow-sm text-xs uppercase tracking-wider disabled:opacity-40">
                  {{ twoFactorLoading ? '...' : 'Verifikasi' }}
                </button>
              </div>
              <div class="bg-green-50/50 border border-green-200/40 rounded-xl px-4 py-3">
                <p class="text-xs font-semibold text-on-surface">3. Simpan kode recovery berikut di tempat aman:</p>
                <div class="mt-2 grid grid-cols-2 gap-2">
                  <div v-for="(code, i) in twoFactorRecoveryCodes" :key="i" class="bg-white border border-slate-200 rounded-lg px-3 py-2 font-mono text-xs font-bold text-center text-on-surface select-all">
                    {{ code }}
                  </div>
                </div>
              </div>
              <button @click="cancel2faSetup" class="text-xs font-semibold text-on-surface-variant hover:text-on-surface transition-colors">
                Batal
              </button>
            </div>
          </template>

          <template v-else>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold text-on-surface">
                  {{ twoFactorEnabled ? '2FA aktif' : '2FA tidak aktif' }}
                </p>
                <p class="text-xs text-on-surface-variant/70 mt-0.5">
                  {{ twoFactorEnabled ? 'Kode tambahan dari Google Authenticator diperlukan saat login.' : 'Tingkatkan keamanan akun dengan autentikasi dua faktor.' }}
                </p>
              </div>
              <button v-if="twoFactorEnabled" @click="disable2fa" :disabled="twoFactorLoading" class="flex items-center gap-2 bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 px-4 py-2.5 rounded-xl font-bold transition-all text-xs uppercase tracking-wider disabled:opacity-40">
                <ShieldOff class="w-4 h-4" /> Nonaktifkan
              </button>
              <button v-else @click="enable2fa" :disabled="twoFactorLoading" class="flex items-center gap-2 bg-[#04000D] text-white px-4 py-2.5 rounded-xl font-bold transition-all shadow-sm text-xs uppercase tracking-wider disabled:opacity-40">
                <Smartphone class="w-4 h-4" /> Aktifkan
              </button>
            </div>
          </template>
        </div>

      </div>

    </div>
  </div>
</template>
