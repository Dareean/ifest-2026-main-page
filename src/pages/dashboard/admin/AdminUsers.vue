<script setup>
import { ref, onMounted } from 'vue'
import api from '../../../utils/api'
import { Search, Shield, User } from 'lucide-vue-next'

const loading = ref(true)
const data = ref(null)
const searchQuery = ref('')

async function fetch() {
  loading.value = true
  try {
    const params = { per_page: 50 }
    if (searchQuery.value) params.search = searchQuery.value
    const res = await api.get('/admin/users', { params })
    data.value = res.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(fetch)
</script>

<template>
  <div>
    <div class="mb-8">
      <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Admin Panel</span>
      <h1 class="font-extrabold text-3xl md:text-4xl tracking-tight text-on-surface">Pengguna</h1>
    </div>

    <!-- Search -->
    <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-5 mb-6">
      <div class="relative flex-1 max-w-md">
        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
        <input v-model="searchQuery" @input="fetch" placeholder="Cari nama atau email..." class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 pl-10 pr-4 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all" />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="h-16 bg-slate-50 border border-slate-100 rounded-2xl animate-pulse"></div>
    </div>

    <!-- Table -->
    <div v-else-if="data?.data?.length" class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/50">
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Nama</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Email</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Role</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Pendaftaran</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Bergabung</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in data.data" :key="user.id" class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
              <td class="px-5 py-3.5 font-bold text-on-surface">{{ user.name }}</td>
              <td class="px-5 py-3.5 font-mono text-[10px] text-on-surface-variant/70">{{ user.email }}</td>
              <td class="px-5 py-3.5">
                <span v-if="user.role === 'admin'" class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase text-[#04000D] bg-[#DCEEB1] px-2 py-0.5 rounded-full">
                  <Shield class="w-2.5 h-2.5" /> Admin
                </span>
                <span v-else class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase text-on-surface-variant bg-slate-100 px-2 py-0.5 rounded-full">
                  <User class="w-2.5 h-2.5" /> User
                </span>
              </td>
              <td class="px-5 py-3.5 font-semibold text-on-surface">{{ user.pendaftarans_count }}</td>
              <td class="px-5 py-3.5 font-mono text-[10px] text-on-surface-variant/60">{{ new Date(user.created_at).toLocaleDateString('id-ID') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else class="bg-white border border-[#04000D]/5 rounded-2xl p-12 text-center">
      <p class="text-sm text-on-surface-variant/60">Tidak ada pengguna</p>
    </div>
  </div>
</template>
