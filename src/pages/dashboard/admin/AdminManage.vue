<script setup>
import { ref, onMounted } from 'vue'
import api from '../../../utils/api'
import { useAuthStore } from '../../../stores/auth'
import { useConfirm } from '../../../composables/useConfirm'
import { useToast } from '../../../composables/useToast'
import { Search, Shield, User, UserCog } from 'lucide-vue-next'

const auth = useAuthStore()
const confirmModal = useConfirm()
const { showToast } = useToast()
const loading = ref(true)
const updatingUserId = ref(null)
const data = ref(null)

async function fetch() {
  loading.value = true
  try {
    const res = await api.get('/admin/super/admins')
    data.value = res.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function changeRole(user, newRole) {
  if (auth.user?.id === user.id) {
    showToast('Anda tidak bisa mengubah role Anda sendiri!', 'error')
    fetch()
    return
  }

  const roleLabel = { user: 'User', admin: 'Admin' }
  const confirmMsg = `Apakah Anda yakin ingin mengubah role ${user.name} menjadi ${roleLabel[newRole] || newRole}?`
  if (!await confirmModal.confirm(confirmMsg, 'Ubah Role?')) {
    fetch()
    return
  }

  updatingUserId.value = user.id
  try {
    const res = await api.put(`/admin/users/${user.id}/role`, { role: newRole })
    user.role = newRole
    showToast(res.data.message || 'Role berhasil diubah', 'success')
  } catch (e) {
    const errorMsg = e.response?.data?.message || 'Gagal mengubah role'
    showToast(errorMsg, 'error')
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
      <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Admin</span>
      <h1 class="font-extrabold text-3xl md:text-4xl tracking-tight text-on-surface">Manage Admin</h1>
    </div>

    <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-5 mb-6">
      <p class="text-xs text-on-surface-variant/70">Kelola akun admin. Admin memiliki akses penuh ke seluruh sistem.</p>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="bg-slate-50 border border-slate-100 rounded-2xl overflow-hidden animate-pulse">
      <!-- Table header skeleton -->
      <div class="hidden md:block">
        <div class="border-b border-slate-100 bg-slate-100/30 px-5 py-3 flex items-center gap-8">
          <div class="h-3 w-16 bg-slate-100/80 rounded"></div>
          <div class="h-3 w-20 bg-slate-100/80 rounded"></div>
          <div class="h-3 w-12 bg-slate-100/80 rounded"></div>
          <div class="h-3 w-20 bg-slate-100/80 rounded"></div>
        </div>
        <div v-for="i in 5" :key="i" class="border-b border-slate-100 px-5 py-3.5 flex items-center gap-8">
          <div class="h-4 w-28 bg-slate-100/80 rounded"></div>
          <div class="h-3 w-36 bg-slate-100/80 rounded"></div>
          <div class="h-6 w-20 bg-slate-100/80 rounded-lg"></div>
          <div class="h-3 w-24 bg-slate-100/80 rounded"></div>
        </div>
      </div>
      <!-- Mobile skeleton -->
      <div class="md:hidden divide-y divide-slate-100">
        <div v-for="i in 3" :key="i" class="p-4 space-y-2">
          <div class="h-5 w-36 bg-slate-100/80 rounded"></div>
          <div class="h-3 w-48 bg-slate-100/80 rounded"></div>
          <div class="h-3 w-24 bg-slate-100/80 rounded"></div>
        </div>
      </div>
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
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Bergabung</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in data.data" :key="user.id" class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
              <td class="px-5 py-3.5 font-bold text-on-surface">{{ user.name }}</td>
              <td class="px-5 py-3.5 font-mono text-[10px] text-on-surface-variant/70">{{ user.email }}</td>
              <td class="px-5 py-3.5">
                <div v-if="auth.user?.id === user.id" class="inline-flex items-center gap-1">
                  <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase text-[#04000D] bg-[#DCEEB1] px-2 py-0.5 rounded-full">
                    <Shield class="w-2.5 h-2.5" /> Admin (Anda)
                  </span>
                </div>
                <div v-else class="inline-block relative">
                  <select
                    :value="user.role"
                    @change="changeRole(user, $event.target.value)"
                    :disabled="updatingUserId === user.id"
                    class="bg-slate-50 hover:bg-slate-100/80 border border-slate-200 focus:border-[#04000D]/40 rounded-lg py-1 pl-2 pr-6 text-[10px] font-mono font-bold uppercase text-on-surface focus:outline-none transition-all cursor-pointer appearance-none disabled:opacity-50"
                  >
                    <option value="admin">Admin</option>
                  </select>
                  <span class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant/50 text-[8px]">▼</span>
                </div>
              </td>
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
              <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase text-[#04000D] bg-[#DCEEB1] px-2 py-0.5 rounded-full">
                <Shield class="w-2.5 h-2.5" /> Anda
              </span>
            </div>
            <div v-else class="inline-block relative">
              <select
                :value="user.role"
                @change="changeRole(user, $event.target.value)"
                :disabled="updatingUserId === user.id"
                class="bg-slate-50 hover:bg-slate-100/80 border border-slate-200 focus:border-[#04000D]/40 rounded-lg py-1.5 pl-2 pr-7 text-[10px] font-mono font-bold uppercase text-on-surface focus:outline-none transition-all cursor-pointer appearance-none disabled:opacity-50 min-h-[32px]"
              >
                <option value="admin">Admin</option>
              </select>
              <span class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant/50 text-[8px]">▼</span>
            </div>
          </div>
          <div class="space-y-1 text-xs text-on-surface-variant/70">
            <p class="font-mono text-[10px] truncate">{{ user.email }}</p>
            <p class="text-[10px]">{{ new Date(user.created_at).toLocaleDateString('id-ID') }}</p>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="bg-white border border-[#04000D]/5 rounded-2xl p-12 text-center">
      <UserCog class="w-10 h-10 text-on-surface-variant/20 mx-auto mb-3" />
      <p class="text-sm text-on-surface-variant/60">Belum ada admin</p>
    </div>
  </div>
</template>
