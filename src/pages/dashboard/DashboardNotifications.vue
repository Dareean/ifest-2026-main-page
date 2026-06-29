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

onMounted(fetchNotifs)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-10">
      <div>
        <span class="font-mono text-xs font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Pemberitahuan</span>
        <h1 class="font-black text-3xl md:text-4xl uppercase tracking-tighter text-on-surface riso-bleed">Notifikasi</h1>
      </div>
      <button v-if="unreadCount() > 0" @click="markAllRead" class="inline-flex items-center gap-1.5 font-mono text-xs font-bold uppercase tracking-wider text-accent-magenta hover:text-on-surface transition-colors">
        <CheckCheck class="w-4 h-4" /> Tandai Semua Dibaca
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="h-20 bg-[#F5F5F5] rounded-2xl animate-pulse"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="notifications.length === 0" class="bg-white shadow-[6px_6px_0px_0px_#04000D] rounded-2xl p-12 md:p-16 text-center">
      <div class="w-14 h-14 rounded-2xl bg-[#F5F5F5] flex items-center justify-center mx-auto mb-5">
        <Inbox class="w-6 h-6 text-on-surface-variant/50" />
      </div>
      <h3 class="font-black text-xl uppercase tracking-tighter text-on-surface mb-2">Kotak Masuk Kosong</h3>
      <p class="font-mono text-sm text-on-surface-variant max-w-xs mx-auto">Kamu akan mendapat notifikasi setelah mendaftar lomba atau ada pengumuman baru</p>
    </div>

    <!-- List -->
    <div v-else class="space-y-2">
      <div
        v-for="notif in notifications"
        :key="notif.id"
        @click="markRead(notif)"
        class="rounded-2xl p-5 transition-all cursor-pointer"
        :class="notif.is_read
          ? 'bg-white shadow-[6px_6px_0px_0px_#04000D] hover:shadow-[3px_3px_0px_0px_#04000D]'
          : 'bg-[#F5F5F5] border border-[#04000D]/20'"
      >
        <div class="flex items-start gap-4">
          <div
            class="w-3 h-3 rounded-full mt-1 flex-shrink-0"
            :class="notif.is_read ? 'bg-on-surface-variant/20' : 'bg-accent-magenta'"
          ></div>
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-4 mb-1">
              <h3 class="font-mono text-sm font-bold text-on-surface" :class="{ 'opacity-60': notif.is_read }">{{ notif.judul }}</h3>
              <span class="font-mono text-[10px] text-on-surface-variant/50 whitespace-nowrap flex-shrink-0">{{ new Date(notif.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
            </div>
            <p class="font-mono text-xs text-on-surface-variant leading-relaxed" :class="{ 'opacity-50': notif.is_read }">{{ notif.pesan }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
