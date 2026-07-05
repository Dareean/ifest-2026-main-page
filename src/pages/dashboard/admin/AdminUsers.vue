<script setup>
import { ref, onMounted } from 'vue'
import api from '../../../utils/api'
import { useAuthStore } from '../../../stores/auth'
import { Search, Shield, User, Crown } from 'lucide-vue-next'

const auth = useAuthStore()
const loading = ref(true)
const updatingUserId = ref(null)
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

async function changeRole(user, newRole) {
  if (auth.user?.id === user.id) {
    alert('Anda tidak bisa mengubah role Anda sendiri!')
    return
  }

  const confirmMsg = `Apakah Anda yakin ingin mengubah role ${user.name} menjadi ${newRole.toUpperCase()}?`
  if (!confirm(confirmMsg)) {
    // Reset select to original value by re-fetching or forcing re-render
    fetch()
    return
  }

  updatingUserId.value = user.id
  try {
    const res = await api.put(`/admin/users/${user.id}/role`, { role: newRole })
    user.role = newRole
    alert(res.data.message || 'Role berhasil diubah')
  } catch (e) {
    const errorMsg = e.response?.data?.message || 'Gagal mengubah role'
    alert(errorMsg)
    fetch()
  } finally {
    updatingUserId.value = null
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

    <!-- Table (desktop) + Cards (mobile) -->
    <div v-else-if="data?.data?.length" class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl overflow-hidden">
      <div class="hidden md:block overflow-x-auto">
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
                <div v-if="auth.user?.id === user.id" class="inline-flex items-center gap-1">
                  <span v-if="user.role === 'super_admin'" class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase text-[#04000D] bg-amber-200 px-2 py-0.5 rounded-full">
                    <Crown class="w-2.5 h-2.5" /> Super Admin (Anda)
                  </span>
                  <span v-else class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase text-[#04000D] bg-[#DCEEB1] px-2 py-0.5 rounded-full">
                    <Shield class="w-2.5 h-2.5" /> Admin (Anda)
                  </span>
                </div>

                <div v-else-if="auth.isSuperAdmin" class="inline-block relative">
                  <select
                    :value="user.role"
                    @change="changeRole(user, $event.target.value)"
                    :disabled="updatingUserId === user.id"
                    class="bg-slate-50 hover:bg-slate-100/80 border border-slate-200 focus:border-[#04000D]/40 rounded-lg py-1 pl-2 pr-6 text-[10px] font-mono font-bold uppercase text-on-surface focus:outline-none transition-all cursor-pointer appearance-none disabled:opacity-50"
                  >
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                  </select>
                  <span class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant/50 text-[8px]">▼</span>
                </div>
                <div v-else class="inline-flex items-center gap-1">
                  <span v-if="user.role === 'super_admin'" class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase text-[#04000D] bg-amber-200 px-2 py-0.5 rounded-full">
                    <Crown class="w-2.5 h-2.5" /> Super Admin
                  </span>
                  <span v-else-if="user.role === 'admin'" class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase text-[#04000D] bg-[#DCEEB1] px-2 py-0.5 rounded-full">
                    <Shield class="w-2.5 h-2.5" /> Admin
                  </span>
                  <span v-else class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase text-on-surface-variant/50 bg-slate-100 px-2 py-0.5 rounded-full">
                    <User class="w-2.5 h-2.5" /> User
                  </span>
                </div>
              </td>
              <td class="px-5 py-3.5 font-semibold text-on-surface">{{ user.pendaftarans_count }}</td>
              <td class="px-5 py-3.5 font-mono text-[10px] text-on-surface-variant/60">{{ new Date(user.created_at).toLocaleDateString('id-ID') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Cards (mobile) -->
      <div class="md:hidden divide-y divide-slate-100">
        <div v-for="user in data.data" :key="user.id" class="p-4">
          <div class="flex items-start justify-between gap-2 mb-2">
            <p class="font-bold text-sm text-on-surface leading-tight">{{ user.name }}</p>
            <div v-if="auth.user?.id === user.id">
              <span v-if="user.role === 'super_admin'" class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase text-[#04000D] bg-amber-200 px-2 py-0.5 rounded-full">
                <Crown class="w-2.5 h-2.5" /> Anda
              </span>
              <span v-else class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase text-[#04000D] bg-[#DCEEB1] px-2 py-0.5 rounded-full">
                <Shield class="w-2.5 h-2.5" /> Anda
              </span>
            </div>
            <div v-else-if="auth.isSuperAdmin" class="inline-block relative">
              <select
                :value="user.role"
                @change="changeRole(user, $event.target.value)"
                :disabled="updatingUserId === user.id"
                class="bg-slate-50 hover:bg-slate-100/80 border border-slate-200 focus:border-[#04000D]/40 rounded-lg py-1.5 pl-2 pr-7 text-[10px] font-mono font-bold uppercase text-on-surface focus:outline-none transition-all cursor-pointer appearance-none disabled:opacity-50 min-h-[32px]"
              >
                <option value="user">User</option>
                <option value="admin">Admin</option>
                <option value="super_admin">Super Admin</option>
              </select>
              <span class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant/50 text-[8px]">▼</span>
            </div>
            <div v-else>
              <span v-if="user.role === 'super_admin'" class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase text-[#04000D] bg-amber-200 px-2 py-0.5 rounded-full">
                <Crown class="w-2.5 h-2.5" /> Super Admin
              </span>
              <span v-else-if="user.role === 'admin'" class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase text-[#04000D] bg-[#DCEEB1] px-2 py-0.5 rounded-full">
                <Shield class="w-2.5 h-2.5" /> Admin
              </span>
              <span v-else class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase text-on-surface-variant/50 bg-slate-100 px-2 py-0.5 rounded-full">
                <User class="w-2.5 h-2.5" /> User
              </span>
            </div>
          </div>
          <div class="space-y-1 text-xs text-on-surface-variant/70">
            <p class="font-mono text-[10px] truncate">{{ user.email }}</p>
            <div class="flex items-center gap-3 pt-1">
              <span>{{ user.pendaftarans_count }} pendaftaran</span>
              <span class="text-[10px]">{{ new Date(user.created_at).toLocaleDateString('id-ID') }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="bg-white border border-[#04000D]/5 rounded-2xl p-12 text-center">
      <p class="text-sm text-on-surface-variant/60">Tidak ada pengguna</p>
    </div>
  </div>
</template>
