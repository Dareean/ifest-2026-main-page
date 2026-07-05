<script setup>
import { ref, onMounted } from 'vue'
import api from '../../../utils/api'
import { Send, Bell, AlertTriangle, CheckCircle, History, Search } from 'lucide-vue-next'

function autoResize(el) {
  el.style.height = 'auto'
  el.style.height = el.scrollHeight + 'px'
}

const form = ref({ judul: '', pesan: '' })
const submitting = ref(false)
const targetType = ref('all') // 'all' | 'specific'
const searchQuery = ref('')
const searchResults = ref([])
const searching = ref(false)
const selectedUser = ref(null)
const error = ref('')
const success = ref('')

const notifications = ref([])
const historyLoading = ref(true)

async function fetchHistory() {
  historyLoading.value = true
  try {
    const res = await api.get('/admin/notifications')
    notifications.value = res.data.data
  } catch (e) {
    console.error(e)
  } finally {
    historyLoading.value = false
  }
}

let searchTimeout = null
function handleSearchUsers() {
  if (searchTimeout) clearTimeout(searchTimeout)
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }
  searching.value = true
  searchTimeout = setTimeout(async () => {
    try {
      const res = await api.get('/admin/users', { params: { search: searchQuery.value } })
      searchResults.value = res.data.data || []
    } catch (e) {
      console.error(e)
    } finally {
      searching.value = false
    }
  }, 300)
}

function selectUser(user) {
  selectedUser.value = user
  searchQuery.value = ''
  searchResults.value = []
}

async function handleBroadcast() {
  if (!form.value.judul || !form.value.pesan) return
  if (targetType.value === 'specific' && !selectedUser.value) {
    error.value = 'Silakan pilih pengguna terlebih dahulu'
    return
  }
  error.value = ''
  success.value = ''
  submitting.value = true
  try {
    const payload = {
      judul: form.value.judul,
      pesan: form.value.pesan,
    }
    if (targetType.value === 'specific') {
      payload.user_ids = [selectedUser.value.id]
    }
    const res = await api.post('/admin/notifications', payload)
    success.value = res.data.message
    form.value = { judul: '', pesan: '' }
    selectedUser.value = null
    targetType.value = 'all'
    fetchHistory()
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

onMounted(fetchHistory)
</script>

<template>
  <div>
    <div class="mb-8">
      <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Admin Panel</span>
      <h1 class="font-extrabold text-3xl md:text-4xl tracking-tight text-on-surface">Notifikasi</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
      <!-- Form Block (Left Column) -->
      <div class="lg:col-span-5 bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6 space-y-5">
        <div>
          <h2 class="font-extrabold text-sm text-on-surface flex items-center gap-2 mb-1">
            <Bell class="w-4 h-4 text-accent-magenta" /> Kirim Notifikasi
          </h2>
          <p class="text-xs text-on-surface-variant/70">Kirim notifikasi ke pengguna I-FEST</p>
        </div>

        <div v-if="error" class="bg-[#FF3D8B]/5 border border-accent-magenta/20 rounded-xl px-4 py-3 text-xs font-semibold text-accent-magenta flex items-center gap-2">
          <AlertTriangle class="w-3.5 h-3.5 flex-shrink-0" /> {{ error }}
        </div>
        <div v-if="success" class="bg-[#DCEEB1]/10 border border-[#DCEEB1]/45 rounded-xl px-4 py-3 text-xs font-semibold text-green-700 flex items-center gap-2">
          <CheckCircle class="w-3.5 h-3.5 flex-shrink-0" /> {{ success }}
        </div>

        <div>
          <label class="block text-xs font-semibold text-on-surface-variant/80 mb-2">Tujuan Pengiriman</label>
          <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 text-xs font-semibold text-on-surface-variant/85 cursor-pointer">
              <input type="radio" value="all" v-model="targetType" class="w-4 h-4 text-[#04000D] border-slate-300 focus:ring-0 focus:ring-offset-0" />
              <span>Semua Pengguna</span>
            </label>
            <label class="flex items-center gap-2 text-xs font-semibold text-on-surface-variant/85 cursor-pointer">
              <input type="radio" value="specific" v-model="targetType" class="w-4 h-4 text-[#04000D] border-slate-300 focus:ring-0 focus:ring-offset-0" />
              <span>Pengguna Spesifik</span>
            </label>
          </div>
        </div>

        <!-- User Selector (Shown only when targetType is 'specific') -->
        <div v-if="targetType === 'specific'" class="space-y-2 pt-1">
          <label class="block text-xs font-semibold text-on-surface-variant/80">Pilih Pengguna <span class="text-accent-magenta">*</span></label>
          
          <div v-if="selectedUser" class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 text-xs">
            <div class="min-w-0">
              <p class="font-bold text-on-surface truncate">{{ selectedUser.name }}</p>
              <p class="text-on-surface-variant/60 font-mono text-[10px] truncate mt-0.5">{{ selectedUser.email }}</p>
            </div>
            <button @click="selectedUser = null" type="button" class="text-accent-magenta hover:text-red-700 p-1 font-bold text-xs transition-colors">
              Ganti
            </button>
          </div>
          
          <div v-else class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
              <Search class="h-3.5 w-3.5 text-on-surface-variant/30" />
            </div>
            <input 
              v-model="searchQuery" 
              @input="handleSearchUsers"
              placeholder="Cari nama atau email..." 
              class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 pl-10 pr-4 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all" 
            />
            <div v-if="searching" class="absolute right-3.5 top-3 text-[10px] text-on-surface-variant/40 font-semibold">
              Mencari...
            </div>
            
            <!-- Search Results Dropdown -->
            <div v-if="searchResults.length > 0" class="absolute z-10 w-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto divide-y divide-slate-100">
              <button 
                v-for="user in searchResults" 
                :key="user.id"
                @click="selectUser(user)"
                type="button"
                class="w-full text-left px-4 py-2.5 hover:bg-slate-50 text-xs transition-colors flex flex-col gap-0.5"
              >
                <span class="font-bold text-on-surface">{{ user.name }}</span>
                <span class="text-on-surface-variant/60 font-mono text-[10px]">{{ user.email }}</span>
              </button>
            </div>
            <div v-else-if="searchQuery.length >= 2 && !searching && searchResults.length === 0" class="absolute z-10 w-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-lg p-4 text-center text-xs text-on-surface-variant/60">
              Pengguna tidak ditemukan
            </div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-on-surface-variant/80 mb-1.5">Judul <span class="text-accent-magenta">*</span></label>
          <input v-model="form.judul" placeholder="Contoh: Pengumuman Penting" class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 px-4 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-on-surface-variant/80 mb-1.5">Pesan <span class="text-accent-magenta">*</span></label>
          <textarea v-model="form.pesan" rows="5" placeholder="Tulis pesan notifikasi..." @input="autoResize($event.target)" class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 px-4 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all resize-none overflow-hidden"></textarea>
        </div>

        <button @click="handleBroadcast" :disabled="submitting || !form.judul || !form.pesan || (targetType === 'specific' && !selectedUser)" class="w-full bg-[#04000D] hover:bg-black text-[#DCEEB1] py-3 rounded-xl text-xs font-bold transition-all disabled:opacity-40 shadow-sm flex items-center justify-center gap-1.5">
          <Send class="w-4 h-4" /> {{ submitting ? 'Mengirim...' : 'Kirim Notifikasi' }}
        </button>
      </div>

      <!-- History Block (Right Column) -->
      <div class="lg:col-span-7 bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-6">
        <div class="mb-4">
          <h2 class="font-extrabold text-sm text-on-surface flex items-center gap-2 mb-1">
            <History class="w-4 h-4 text-accent-magenta" /> Riwayat Notifikasi
          </h2>
          <p class="text-xs text-on-surface-variant/70">Daftar notifikasi yang telah dikirim sebelumnya</p>
        </div>

        <div v-if="historyLoading" class="space-y-3">
          <div v-for="i in 3" :key="i" class="h-20 bg-slate-50 border border-slate-100 rounded-xl animate-pulse"></div>
        </div>

        <div v-else-if="notifications.length === 0" class="py-12 text-center border border-dashed border-[#04000D]/10 rounded-2xl bg-slate-50/50">
          <Bell class="w-8 h-8 text-on-surface-variant/30 mx-auto mb-3" />
          <p class="text-xs font-semibold text-on-surface-variant/60">Belum ada riwayat notifikasi</p>
        </div>

        <div v-else class="space-y-3 max-h-[480px] overflow-y-auto pr-1">
          <div
            v-for="notif in notifications"
            :key="notif.id"
            class="p-4 bg-slate-50 hover:bg-slate-100/80 border border-slate-100 rounded-xl transition-all duration-200"
          >
            <div class="flex items-start justify-between gap-4 mb-1.5">
              <h3 class="text-xs font-bold text-on-surface">{{ notif.judul }}</h3>
              <span class="font-mono text-[9px] text-on-surface-variant/50 whitespace-nowrap">{{ new Date(notif.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}</span>
            </div>
            <p class="text-xs text-on-surface-variant/90 leading-relaxed">{{ notif.pesan }}</p>
            <div class="flex items-center gap-1.5 mt-2.5 pt-2 border-t border-slate-200/50 text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/50">
              <span class="bg-slate-200/60 px-1.5 py-0.5 rounded">Penerima: {{ notif.user?.name || 'Semua Pengguna' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
