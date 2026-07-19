<script setup>
import { ref, watch, onMounted } from 'vue'
import api from '../../../utils/api'
import { useAuthStore } from '../../../stores/auth'
import { useToast } from '../../../composables/useToast'
import { useConfirm } from '../../../composables/useConfirm'
import { Search, Shield, User, Trash2, Plus, X, Lock } from 'lucide-vue-next'

const auth = useAuthStore()
const toast = useToast()
const confirmModal = useConfirm()
const loading = ref(true)
const updatingUserId = ref(null)
const data = ref(null)
const searchQuery = ref('')

// Create User state
const showModal = ref(false)
const creating = ref(false)
const modalError = ref('')
const createForm = ref({
  name: '',
  email: '',
  password: '',
  role: 'user',
  phone: '',
  institution: ''
})

function generatePassword() {
  const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%'
  let pass = ''
  for (let i = 0; i < 10; i++) {
    pass += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  createForm.value.password = pass
}

function openCreateModal() {
  createForm.value = {
    name: '',
    email: '',
    password: '',
    role: 'user',
    phone: '',
    institution: ''
  }
  generatePassword()
  modalError.value = ''
  showModal.value = true
}

function closeCreateModal() {
  showModal.value = false
}

async function handleCreateUser() {
  creating.value = true
  modalError.value = ''
  try {
    const res = await api.post('/admin/users', createForm.value)
    toast.showToast(res.data.message || 'Pengguna berhasil ditambahkan', 'success')
    showModal.value = false
    fetch()
  } catch (e) {
    const data = e.response?.data
    if (data?.errors) {
      modalError.value = Object.values(data.errors).flat().join('. ')
    } else {
      modalError.value = data?.message || 'Gagal menambahkan pengguna'
    }
  } finally {
    creating.value = false
  }
}

async function fetch() {
  loading.value = true
  try {
    const params = { per_page: 50 }
    if (searchQuery.value) params.search = searchQuery.value
    const res = await api.get('/admin/users', { params })
    data.value = res.data
  } catch (e) {
    console.error('Gagal memuat pengguna:', e)
  } finally {
    loading.value = false
  }
}

async function deleteUser(user) {
  if (!await confirmModal.confirm(`Yakin ingin menghapus akun "${user.name}" (${user.email})? Semua data terkait akan ikut terhapus.`, 'Hapus Pengguna?')) return
  try {
    const res = await api.delete(`/admin/users/${user.id}`)
    data.value.data = data.value.data.filter(u => u.id !== user.id)
    toast.showToast(res.data.message || 'Akun berhasil dihapus', 'success')
  } catch (e) {
    toast.showToast(e.response?.data?.message || 'Gagal menghapus akun', 'error')
  }
}

async function changeRole(user, newRole) {
  if (auth.user?.id === user.id) {
    toast.showToast('Anda tidak bisa mengubah role Anda sendiri!', 'error')
    return
  }

  const confirmMsg = `Apakah Anda yakin ingin mengubah role ${user.name} menjadi ${newRole.toUpperCase()}?`
  if (!await confirmModal.confirm(confirmMsg, 'Ubah Role?')) {
    // Reset select to original value by re-fetching or forcing re-render
    fetch()
    return
  }

  updatingUserId.value = user.id
  try {
    const res = await api.put(`/admin/users/${user.id}/role`, { role: newRole })
    user.role = newRole
    toast.showToast(res.data.message || 'Role berhasil diubah', 'success')
  } catch (e) {
    toast.showToast(e.response?.data?.message || 'Gagal mengubah role', 'error')
    fetch()
  } finally {
    updatingUserId.value = null
  }
}

let searchTimeout = null
watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetch(), 400)
})

onMounted(fetch)
</script>

<template>
  <div>
    <div class="mb-8">
      <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Admin Panel</span>
      <h1 class="font-extrabold text-3xl md:text-4xl tracking-tight text-on-surface">Pengguna</h1>
    </div>

    <!-- Search & Action -->
    <div class="bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-5 mb-6 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
      <div class="relative flex-1 max-w-md">
        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant/40" />
        <input v-model="searchQuery" placeholder="Cari nama atau email..." class="w-full bg-slate-50 border border-slate-200 focus:border-[#04000D]/40 rounded-xl py-2.5 pl-10 pr-4 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none transition-all" />
      </div>
      <button @click="openCreateModal" class="flex items-center justify-center gap-2 bg-[#04000D] hover:bg-black text-white px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm">
        <Plus class="w-4 h-4 text-[#DCEEB1]" /> Tambah Pengguna
      </button>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-4">
      <!-- Search skeleton -->
      <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5 animate-pulse">
        <div class="h-10 w-72 bg-slate-100/80 border border-slate-200/60 rounded-xl"></div>
      </div>
      <!-- Table skeleton -->
      <div class="bg-slate-50 border border-slate-100 rounded-2xl overflow-hidden animate-pulse">
        <div class="hidden md:block">
          <div class="border-b border-slate-100 bg-slate-100/30 px-5 py-3 flex items-center gap-8">
            <div class="h-3 w-16 bg-slate-100/80 rounded"></div>
            <div class="h-3 w-20 bg-slate-100/80 rounded"></div>
            <div class="h-3 w-12 bg-slate-100/80 rounded"></div>
            <div class="h-3 w-20 bg-slate-100/80 rounded"></div>
            <div class="h-3 w-16 bg-slate-100/80 rounded"></div>
            <div class="flex-1"></div>
          </div>
          <div v-for="i in 5" :key="i" class="border-b border-slate-100 px-5 py-3.5 flex items-center gap-8">
            <div class="h-4 w-28 bg-slate-100/80 rounded"></div>
            <div class="h-3 w-40 bg-slate-100/80 rounded"></div>
            <div class="h-6 w-16 bg-slate-100/80 rounded-lg"></div>
            <div class="h-3 w-12 bg-slate-100/80 rounded"></div>
            <div class="h-3 w-24 bg-slate-100/80 rounded"></div>
            <div class="h-4 w-4 bg-slate-100/80 rounded"></div>
          </div>
        </div>
        <!-- Mobile skeleton -->
        <div class="md:hidden divide-y divide-slate-100">
          <div v-for="i in 3" :key="i" class="p-4 space-y-2">
            <div class="flex items-center justify-between">
              <div class="h-5 w-36 bg-slate-100/80 rounded"></div>
              <div class="h-5 w-14 bg-slate-100/80 rounded-lg"></div>
            </div>
            <div class="h-3 w-48 bg-slate-100/80 rounded"></div>
            <div class="flex items-center gap-3">
              <div class="h-3 w-16 bg-slate-100/80 rounded"></div>
              <div class="h-3 w-20 bg-slate-100/60 rounded"></div>
            </div>
          </div>
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
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Pendaftaran</th>
              <th class="text-left font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Bergabung</th>
              <th class="text-right font-mono text-[9px] font-bold uppercase tracking-wider text-on-surface-variant/60 px-5 py-3">Aksi</th>
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
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                  </select>
                  <span class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant/50 text-[8px]">▼</span>
                </div>
              </td>
              <td class="px-5 py-3.5 font-semibold text-on-surface">{{ user.pendaftarans_count }}</td>
              <td class="px-5 py-3.5 font-mono text-[10px] text-on-surface-variant/60">{{ new Date(user.created_at).toLocaleDateString('id-ID') }}</td>
              <td v-if="auth.user?.id !== user.id" class="px-5 py-3.5 text-right">
                <button @click="deleteUser(user)" class="text-accent-magenta/50 hover:text-accent-magenta transition-colors" title="Hapus akun">
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </td>
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
            <div v-else class="flex items-center gap-1.5">
              <div class="inline-block relative">
                <select
                  :value="user.role"
                  @change="changeRole(user, $event.target.value)"
                  :disabled="updatingUserId === user.id"
                  class="bg-slate-50 hover:bg-slate-100/80 border border-slate-200 focus:border-[#04000D]/40 rounded-lg py-1.5 pl-2 pr-7 text-[10px] font-mono font-bold uppercase text-on-surface focus:outline-none transition-all cursor-pointer appearance-none disabled:opacity-50 min-h-[32px]"
                >
                  <option value="user">User</option>
                  <option value="admin">Admin</option>
                </select>
                <span class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant/50 text-[8px]">▼</span>
              </div>
              <button @click="deleteUser(user)" class="text-accent-magenta/50 hover:text-accent-magenta transition-colors p-1" title="Hapus akun">
                <Trash2 class="w-3.5 h-3.5" />
              </button>
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

    <!-- Create User Modal -->
    <Transition name="fade">
      <div v-if="showModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white border-2 border-[#04000D] rounded-2xl w-full max-w-md overflow-hidden shadow-2xl animate-scale-up">
          <div class="bg-[#04000D] px-6 py-4 flex items-center justify-between text-white">
            <h3 class="font-extrabold text-sm uppercase tracking-wider flex items-center gap-2">
              <User class="w-4 h-4 text-[#DCEEB1]" /> Tambah Pengguna Baru
            </h3>
            <button @click="closeCreateModal" class="text-white/60 hover:text-white transition-colors">
              <X class="w-4 h-4" />
            </button>
          </div>
          
          <form @submit.prevent="handleCreateUser" class="p-6 space-y-4">
            <div v-if="modalError" class="p-3 bg-rose-50 border border-rose-100 rounded-xl text-xs text-rose-600 font-semibold leading-relaxed">
              {{ modalError }}
            </div>

            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Nama Lengkap</label>
              <input v-model="createForm.name" required placeholder="Nama Lengkap" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
            </div>

            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Alamat Email</label>
              <input v-model="createForm.email" type="email" required placeholder="email@contoh.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
            </div>

            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Kata Sandi (Min. 8 Karakter)</label>
              <div class="flex gap-2">
                <input v-model="createForm.password" type="text" required placeholder="Kata Sandi" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
                <button type="button" @click="generatePassword" class="px-3 bg-slate-100 hover:bg-slate-200 rounded-xl text-xs font-bold text-on-surface transition-all">Acak</button>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Hak Akses (Role)</label>
                <select v-model="createForm.role" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all">
                  <option value="user">User</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">No. Telepon (Opsional)</label>
                <input v-model="createForm.phone" placeholder="+62 xxx" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
              </div>
            </div>

            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50 mb-1">Asal Institusi / Sekolah (Opsional)</label>
              <input v-model="createForm.institution" placeholder="Nama Sekolah atau Universitas" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-[#04000D]/40 transition-all" />
            </div>

            <div class="flex items-center gap-3 pt-2">
              <button
                type="submit"
                :disabled="creating"
                class="flex-1 flex items-center justify-center gap-1.5 bg-[#04000D] text-white py-2.5 rounded-xl text-xs font-bold hover:bg-black transition-all disabled:opacity-40"
              >
                <Plus v-if="!creating" class="w-3.5 h-3.5" />
                <span v-else class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                Tambah Pengguna
              </button>
              <button
                type="button"
                @click="closeCreateModal"
                class="px-4 py-2.5 rounded-xl text-xs font-bold text-on-surface-variant/60 hover:bg-slate-100 transition-all"
              >
                Batal
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
