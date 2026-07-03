<script setup>
import { ref } from 'vue'
import api from '../../../utils/api'
import { Send, Bell, AlertTriangle, CheckCircle } from 'lucide-vue-next'

const form = ref({ judul: '', pesan: '' })
const submitting = ref(false)
const sendToAll = ref(true)
const error = ref('')
const success = ref('')

async function handleBroadcast() {
  if (!form.value.judul || !form.value.pesan) return
  error.value = ''
  success.value = ''
  submitting.value = true
  try {
    const payload = {
      judul: form.value.judul,
      pesan: form.value.pesan,
    }
    if (!sendToAll.value) {
      // Send only to yourself (admin) for targeted messaging
      payload.user_ids = []
    }
    const res = await api.post('/admin/notifications', payload)
    success.value = res.data.message
    form.value = { judul: '', pesan: '' }
  } catch (e) {
    const data = e.response?.data
    if (data?.errors) {
      error.value = Object.values(data.errors).flat().join('. ')
    } else {
      error.value = data?.message || 'Gagal mengirim notifikasi'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div>
    <div class="mb-8">
      <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Admin Panel</span>
      <h1 class="font-extrabold text-3xl md:text-4xl tracking-tight text-on-surface">Notifikasi</h1>
    </div>

    <div class="max-w-xl">
      <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6 space-y-5">
        <div>
          <h2 class="font-extrabold text-sm text-on-surface flex items-center gap-2 mb-1">
            <Bell class="w-4 h-4 text-accent-magenta" /> Kirim Notifikasi Broadcast
          </h2>
          <p class="text-xs text-on-surface-variant/70">Kirim notifikasi ke seluruh pengguna I-FEST</p>
        </div>

        <div v-if="error" class="bg-[#FF3D8B]/5 border border-accent-magenta/20 rounded-xl px-4 py-3 text-xs font-semibold text-accent-magenta flex items-center gap-2">
          <AlertTriangle class="w-3.5 h-3.5 flex-shrink-0" /> {{ error }}
        </div>
        <div v-if="success" class="bg-[#DCEEB1]/10 border border-[#DCEEB1]/45 rounded-xl px-4 py-3 text-xs font-semibold text-green-700 flex items-center gap-2">
          <CheckCircle class="w-3.5 h-3.5 flex-shrink-0" /> {{ success }}
        </div>

        <div>
          <label class="block text-xs font-semibold text-on-surface-variant/80 mb-1.5">Judul <span class="text-accent-magenta">*</span></label>
          <input v-model="form.judul" placeholder="Contoh: Pengumuman Penting" class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 px-4 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-on-surface-variant/80 mb-1.5">Pesan <span class="text-accent-magenta">*</span></label>
          <textarea v-model="form.pesan" rows="5" placeholder="Tulis pesan notifikasi..." class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 px-4 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all resize-none"></textarea>
        </div>

        <div class="flex items-center gap-2.5">
          <input type="checkbox" id="sendAll" v-model="sendToAll" class="w-4 h-4 rounded border-slate-300" />
          <label for="sendAll" class="text-xs font-semibold text-on-surface-variant/80">Kirim ke semua pengguna</label>
        </div>

        <button @click="handleBroadcast" :disabled="submitting || !form.judul || !form.pesan" class="w-full bg-[#04000D] hover:bg-black text-[#DCEEB1] py-3 rounded-xl text-xs font-bold transition-all disabled:opacity-40 shadow-sm flex items-center justify-center gap-1.5">
          <Send class="w-4 h-4" /> {{ submitting ? 'Mengirim...' : 'Kirim Notifikasi' }}
        </button>
      </div>
    </div>
  </div>
</template>
