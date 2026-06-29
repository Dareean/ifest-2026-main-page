<script setup>
import { ref, onMounted } from 'vue'
import api from '../../utils/api'
import { Trophy, Plus, ExternalLink, CheckCircle, Clock, AlertTriangle, Send, X, Users } from 'lucide-vue-next'

const pendaftarans = ref([])
const loading = ref(true)
const showDaftarModal = ref(false)
const lombaList = ref([])
const selectedLomba = ref(null)
const daftarForm = ref({ team_name: '', team_members: [] })
const error = ref('')
const submitting = ref(false)
const showSubmitModal = ref(false)
const submittingPendaftaran = ref(null)
const submitForm = ref({ link_drive: '', catatan: '' })
const submitError = ref('')
const submittingSubmit = ref(false)

const statusConfig = {
  pending: { icon: Clock, label: 'Pending', class: 'bg-[#FFF9E6] text-on-surface' },
  verified: { icon: CheckCircle, label: 'Terverifikasi', class: 'bg-[#DCEEB1] text-on-surface' },
  rejected: { icon: AlertTriangle, label: 'Ditolak', class: 'bg-[#FF3D8B]/10 text-accent-magenta' },
}

async function fetchData() {
  try {
    const [pendaftaranRes, lombaRes] = await Promise.all([
      api.get('/pendaftarans'),
      api.get('/lombas'),
    ])
    pendaftarans.value = pendaftaranRes.data.data
    lombaList.value = lombaRes.data.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function handleDaftar() {
  if (!selectedLomba.value) return
  error.value = ''
  submitting.value = true
  try {
    await api.post(`/lombas/${selectedLomba.value}/daftar`, {
      team_name: daftarForm.value.team_name || null,
      team_members: daftarForm.value.team_members.length > 0 ? daftarForm.value.team_members : null,
    })
    showDaftarModal.value = false
    daftarForm.value = { team_name: '', team_members: [] }
    selectedLomba.value = null
    await fetchData()
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal mendaftar'
  } finally {
    submitting.value = false
  }
}

function addMember() {
  daftarForm.value.team_members.push({ name: '', email: '' })
}

function removeMember(i) {
  daftarForm.value.team_members.splice(i, 1)
}

function openSubmitModal(p) {
  submittingPendaftaran.value = p
  submitForm.value = { link_drive: '', catatan: '' }
  submitError.value = ''
  showSubmitModal.value = true
}

async function handleSubmit() {
  if (!submitForm.value.link_drive) return
  submitError.value = ''
  submittingSubmit.value = true
  try {
    await api.post(`/lombas/${submittingPendaftaran.value.lomba_id}/submit`, {
      link_drive: submitForm.value.link_drive,
      catatan: submitForm.value.catatan || null,
    })
    showSubmitModal.value = false
    submittingPendaftaran.value = null
    await fetchData()
  } catch (e) {
    submitError.value = e.response?.data?.message || 'Gagal mengumpulkan karya'
  } finally {
    submittingSubmit.value = false
  }
}

const sudahTerdaftar = (lombaId) => pendaftarans.value.some(p => p.lomba_id === lombaId)

onMounted(fetchData)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-10">
      <div>
        <span class="font-mono text-xs font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Pendaftaran</span>
        <h1 class="font-black text-3xl md:text-4xl uppercase tracking-tighter text-on-surface riso-bleed">Lomba Saya</h1>
      </div>
      <button @click="showDaftarModal = true" class="inline-flex items-center gap-2 bg-primary text-on-primary px-6 py-3 rounded-full font-mono text-xs font-black uppercase tracking-wider hover:opacity-90 transition-opacity">
        <Plus class="w-4 h-4" /> Daftar Lomba
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="h-28 bg-[#F5F5F5] rounded-2xl animate-pulse"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="pendaftarans.length === 0" class="bg-white shadow-[6px_6px_0px_0px_#04000D] rounded-2xl p-12 md:p-16 text-center">
      <div class="w-14 h-14 rounded-2xl bg-[#F5F5F5] flex items-center justify-center mx-auto mb-5">
        <Trophy class="w-6 h-6 text-on-surface-variant/50" />
      </div>
      <h3 class="font-black text-xl uppercase tracking-tighter text-on-surface mb-2">Belum Ada Lomba</h3>
      <p class="font-mono text-sm text-on-surface-variant max-w-xs mx-auto mb-6">Kamu belum mendaftar lomba apapun. Yuk, daftar sekarang!</p>
      <button @click="showDaftarModal = true" class="inline-flex items-center gap-2 bg-primary text-on-primary px-8 py-3.5 rounded-full font-mono text-xs font-black uppercase tracking-wider hover:opacity-90 transition-opacity">
        <Plus class="w-4 h-4" /> Daftar Sekarang
      </button>
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <div v-for="p in pendaftarans" :key="p.id" class="bg-white shadow-[6px_6px_0px_0px_#04000D] rounded-2xl p-5 md:p-6 transition-all hover:shadow-[3px_3px_0px_0px_#04000D]">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary text-on-primary flex items-center justify-center font-mono font-black text-sm flex-shrink-0">
              {{ p.lomba?.title?.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() || '?' }}
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2.5 flex-wrap mb-1">
                <h3 class="font-black text-base uppercase tracking-tight text-on-surface">{{ p.lomba?.title }}</h3>
                <span
                  class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                  :class="[statusConfig[p.status]?.class]"
                >
                  <component :is="statusConfig[p.status]?.icon" class="w-2.5 h-2.5" />
                  {{ statusConfig[p.status]?.label }}
                </span>
              </div>
              <p class="font-mono text-[11px] text-on-surface-variant">{{ p.lomba?.scale }} · {{ p.lomba?.babak }}</p>
              <p v-if="p.team_name" class="font-mono text-[11px] text-on-surface-variant/70 mt-1 flex items-center gap-1.5">
                <Users class="w-3 h-3" /> {{ p.team_name }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2.5 flex-shrink-0">
            <template v-if="p.status === 'verified' && !p.submission">
              <button @click="openSubmitModal(p)" class="inline-flex items-center gap-1.5 bg-primary text-on-primary px-4 py-2.5 rounded-full font-mono text-[10px] font-black uppercase tracking-wider hover:opacity-90 transition-opacity">
                <Send class="w-3 h-3" /> Kumpulkan
              </button>
            </template>
            <template v-if="p.submission">
              <span class="inline-flex items-center gap-1.5 font-mono text-[10px] font-bold uppercase tracking-wider px-3 py-2 rounded-full bg-[#F5F5F5] text-on-surface-variant">
                <CheckCircle class="w-3 h-3" /> {{ p.submission.status === 'submitted' ? 'Terkumpul' : 'Draft' }}
              </span>
            </template>
            <a
              v-if="p.lomba?.registration_link && p.lomba.registration_link.startsWith('http')"
              :href="p.lomba.registration_link"
              target="_blank"
              class="w-9 h-9 rounded-full bg-[#F5F5F5] flex items-center justify-center text-on-surface-variant hover:bg-[#e6e1e3] hover:text-on-surface transition-colors"
            >
              <ExternalLink class="w-4 h-4" />
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Modal -->
    <Transition name="modal">
      <div v-if="showSubmitModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-[#04000D]/60 backdrop-blur-sm" @click="showSubmitModal = false">
        <div class="bg-white shadow-[6px_6px_0px_0px_#04000D] rounded-2xl w-full max-w-lg relative" @click.stop>
          <button @click="showSubmitModal = false" class="absolute -top-3 -right-3 w-9 h-9 bg-accent-magenta text-white rounded-full flex items-center justify-center hover:opacity-90 transition-opacity z-10">
            <X class="w-4 h-4" />
          </button>

          <div class="p-6 md:p-8">
            <div class="flex items-center gap-3 mb-6">
              <div class="w-10 h-10 rounded-xl bg-primary text-on-primary flex items-center justify-center">
                <Send class="w-5 h-5" />
              </div>
              <div>
                <h2 class="font-black text-lg uppercase tracking-tighter text-on-surface">Kumpulkan Karya</h2>
                <p class="font-mono text-[11px] text-on-surface-variant">{{ submittingPendaftaran?.lomba?.title }}</p>
              </div>
            </div>

            <div v-if="submitError" class="bg-[#FF3D8B]/5 border border-accent-magenta/30 rounded-xl px-4 py-3 mb-5 font-mono text-xs font-bold text-on-surface">{{ submitError }}</div>

            <div class="space-y-4">
              <div>
                <label class="block font-mono text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1.5">Link Google Drive <span class="text-accent-magenta">*</span></label>
                <input v-model="submitForm.link_drive" placeholder="https://drive.google.com/..." class="w-full bg-[#F5F5F5] border border-[#04000D]/30 rounded-xl py-2.5 px-4 font-mono text-xs font-bold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none focus:border-[#04000D] transition-colors" />
                <p class="font-mono text-[9px] text-on-surface-variant/60 mt-1">Pastikan akses link sudah diset ke "Siapa pun yang memiliki link"</p>
              </div>

              <div>
                <label class="block font-mono text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1.5">Catatan <span class="text-on-surface-variant/50">(opsional)</span></label>
                <textarea v-model="submitForm.catatan" rows="3" placeholder="Pesan untuk panitia..." class="w-full bg-[#F5F5F5] border border-[#04000D]/30 rounded-xl py-2.5 px-4 font-mono text-xs font-bold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none focus:border-[#04000D] transition-colors resize-none"></textarea>
              </div>

              <button @click="handleSubmit" :disabled="submittingSubmit || !submitForm.link_drive" class="w-full bg-primary text-on-primary py-3 rounded-full font-mono text-xs font-black uppercase tracking-wider hover:opacity-90 transition-opacity disabled:opacity-40">
                {{ submittingSubmit ? 'Mengirim...' : 'Kirim Karya' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Daftar Modal -->
    <Transition name="modal">
      <div v-if="showDaftarModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-[#04000D]/60 backdrop-blur-sm" @click="showDaftarModal = false">
        <div class="bg-white shadow-[6px_6px_0px_0px_#04000D] rounded-2xl w-full max-w-lg relative" @click.stop>
          <button @click="showDaftarModal = false" class="absolute -top-3 -right-3 w-9 h-9 bg-accent-magenta text-white rounded-full flex items-center justify-center hover:opacity-90 transition-opacity z-10">
            <X class="w-4 h-4" />
          </button>

          <div class="p-6 md:p-8">
            <div class="flex items-center gap-3 mb-6">
              <div class="w-10 h-10 rounded-xl bg-primary text-on-primary flex items-center justify-center">
                <Plus class="w-5 h-5" />
              </div>
              <div>
                <h2 class="font-black text-lg uppercase tracking-tighter text-on-surface">Daftar Lomba Baru</h2>
                <p class="font-mono text-[11px] text-on-surface-variant">Pilih lomba yang ingin kamu ikuti</p>
              </div>
            </div>

            <div v-if="error" class="bg-[#FF3D8B]/5 border border-accent-magenta/30 rounded-xl px-4 py-3 mb-5 font-mono text-xs font-bold text-on-surface">{{ error }}</div>

            <!-- Lomba selector -->
            <div class="space-y-2 mb-6">
              <button
                v-for="l in lombaList"
                :key="l.id"
                @click="selectedLomba = l.id"
                :disabled="sudahTerdaftar(l.id)"
                class="w-full text-left p-3.5 rounded-xl font-mono text-xs font-bold transition-all border"
                :class="selectedLomba === l.id
                  ? 'bg-primary text-on-primary border-primary'
                  : sudahTerdaftar(l.id)
                    ? 'bg-[#F5F5F5] text-on-surface-variant/50 border-transparent cursor-not-allowed'
                    : 'bg-white border-[#04000D]/30 text-on-surface hover:border-[#04000D] hover:bg-[#F5F5F5]'"
              >
                <div class="flex items-center justify-between">
                  <span>{{ l.kode }} — {{ l.title }}</span>
                  <span v-if="sudahTerdaftar(l.id)" class="text-[9px] uppercase tracking-wider opacity-50 flex items-center gap-1">
                    <CheckCircle class="w-3 h-3" /> Terdaftar
                  </span>
                </div>
              </button>
            </div>

            <!-- Team info -->
            <div v-if="selectedLomba" class="space-y-4 border-t border-[#04000D]/20 border-dashed pt-5">
              <div>
                <label class="block font-mono text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-1.5">Nama Tim <span class="text-on-surface-variant/50">(opsional)</span></label>
                <input v-model="daftarForm.team_name" placeholder="Nama tim kamu" class="w-full bg-[#F5F5F5] border border-[#04000D]/30 rounded-xl py-2.5 px-4 font-mono text-xs font-bold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none focus:border-[#04000D] transition-colors" />
              </div>

              <div>
                <div class="flex items-center justify-between mb-2">
                  <label class="font-mono text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Anggota Tim <span class="text-on-surface-variant/50">(opsional)</span></label>
                  <button @click="addMember" class="font-mono text-[10px] font-bold uppercase tracking-wider text-accent-magenta hover:text-on-surface transition-colors">+ Tambah</button>
                </div>
                <div v-for="(member, i) in daftarForm.team_members" :key="i" class="flex items-center gap-2 mb-2">
                  <input v-model="member.name" placeholder="Nama" class="flex-1 bg-[#F5F5F5] border border-[#04000D]/30 rounded-xl py-2.5 px-3 font-mono text-xs font-bold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none focus:border-[#04000D] transition-colors" />
                  <input v-model="member.email" placeholder="Email" type="email" class="flex-1 bg-[#F5F5F5] border border-[#04000D]/30 rounded-xl py-2.5 px-3 font-mono text-xs font-bold text-on-surface placeholder:text-on-surface-variant/30 focus:outline-none focus:border-[#04000D] transition-colors" />
                  <button @click="removeMember(i)" class="text-on-surface-variant hover:text-accent-magenta transition-colors p-1">
                    <X class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <button @click="handleDaftar" :disabled="submitting || !selectedLomba" class="w-full bg-primary text-on-primary py-3 rounded-full font-mono text-xs font-black uppercase tracking-wider hover:opacity-90 transition-opacity disabled:opacity-40">
                {{ submitting ? 'Memproses...' : 'Konfirmasi Pendaftaran' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
