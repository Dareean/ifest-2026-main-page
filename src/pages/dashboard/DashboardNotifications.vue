<script setup>
import { ref, onMounted } from 'vue'
import api from '../../utils/api'
import { Bell, CheckCheck, Inbox } from 'lucide-vue-next'

const notifications = ref([])
const loading = ref(true)

async function fetchNotifs() {
  try {
    const res = await api.get('/notifications')
    notifications.value = res.data.data

  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function markRead(notif) {
  if (notif.is_read) return
  try {
    await api.put(`/notifications/${notif.id}/read`)
    notif.is_read = true
  } catch (e) {
    console.error(e)
  }
}

async function markAllRead() {
  try {
    await api.put('/notifications/read-all')
    notifications.value.forEach(n => n.is_read = true)
  } catch (e) {
    console.error(e)
  }
}

const unreadCount = () => notifications.value.filter(n => !n.is_read).length

onMounted(() => {
  fetchNotifs()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-10">
      <div>
        <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Pemberitahuan</span>
        <h1 class="font-extrabold text-3xl md:text-4xl tracking-tight text-on-surface">Notifikasi</h1>
      </div>
      <button v-if="unreadCount() > 0" @click="markAllRead" class="inline-flex items-center gap-1.5 font-mono text-xs font-bold uppercase tracking-wider text-accent-magenta hover:text-on-surface transition-colors">
        <CheckCheck class="w-4 h-4" /> Tandai Semua Dibaca
      </button>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="bg-slate-50 border border-slate-100 rounded-2xl p-5 animate-pulse">
        <div class="flex items-start gap-4">
          <div class="w-2.5 h-2.5 rounded-full bg-slate-100/80 mt-1 flex-shrink-0"></div>
          <div class="flex-1 min-w-0 space-y-2">
            <div class="flex items-start justify-between gap-4">
              <div class="h-4 w-2/3 bg-slate-100/80 rounded"></div>
              <div class="h-3 w-16 bg-slate-100/80 rounded flex-shrink-0"></div>
            </div>
            <div class="h-3 w-full bg-slate-100/80 rounded"></div>
            <div class="h-3 w-4/5 bg-slate-100/80 rounded"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="notifications.length === 0" class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-12 md:p-16 text-center">
      <div class="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-5">
        <Inbox class="w-6 h-6 text-on-surface-variant/40" />
      </div>
      <h3 class="font-bold text-lg text-on-surface mb-2">Kotak Masuk Kosong</h3>
      <p class="text-sm text-on-surface-variant/80 max-w-xs mx-auto">Kamu akan mendapat notifikasi setelah mendaftar lomba atau ada pengumuman baru</p>
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <div
        v-for="notif in notifications"
        :key="notif.id"
        @click="markRead(notif)"
        class="rounded-2xl p-5 transition-all duration-200 cursor-pointer"
        :class="notif.is_read
          ? 'bg-slate-50 border border-transparent hover:bg-slate-100/80 text-on-surface-variant/70'
          : 'bg-white border border-accent-magenta/10 hover:border-accent-magenta/25 shadow-sm text-on-surface'"
      >
        <div class="flex items-start gap-4">
          <div
            class="w-2.5 h-2.5 rounded-full mt-1.5 flex-shrink-0"
            :class="notif.is_read ? 'bg-on-surface-variant/20' : 'bg-accent-magenta'"
          ></div>
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-4 mb-1">
              <h3 class="text-sm font-bold text-on-surface" :class="{ 'opacity-60': notif.is_read }">{{ notif.judul }}</h3>
              <span class="font-mono text-[9px] text-on-surface-variant/40 whitespace-nowrap flex-shrink-0">{{ new Date(notif.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
            </div>
            <p class="text-xs text-on-surface-variant/90 leading-relaxed" :class="{ 'opacity-50': notif.is_read }">{{ notif.pesan }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
