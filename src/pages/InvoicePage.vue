<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useConfirm } from '../composables/useConfirm'
import api from '../utils/api'
import { CheckCircle, Printer, ArrowLeft, Loader } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const pendaftaran = ref(null)
const loading = ref(true)
const confirmModal = useConfirm()

async function fetchInvoice() {
  try {
    const res = await api.get('/pendaftarans')
    const match = res.data.data.find(p => p.id === parseInt(route.params.id))
    if (match) {
      pendaftaran.value = match
    } else {
      await confirmModal.alert('Pendaftaran tidak ditemukan', 'Error')
      router.push('/dashboard/competitions')
    }
  } catch (e) {
    console.error(e)
    await confirmModal.alert('Gagal mengambil data kuitansi', 'Error')
  } finally {
    loading.value = false
  }
}

function handlePrint() {
  window.print()
}

onMounted(fetchInvoice)
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4 sm:p-6 font-sans">
    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center gap-3">
      <Loader class="w-8 h-8 animate-spin text-on-surface-variant" />
      <p class="text-xs text-on-surface-variant font-semibold">Mengambil Bukti Pendaftaran...</p>
    </div>

    <!-- Main Card -->
    <div v-else-if="pendaftaran" class="w-full max-w-xl bg-white border border-slate-200/80 rounded-3xl overflow-hidden shadow-sm relative p-6 sm:p-8 select-none print:shadow-none print:border-none print:p-0">
      
      <!-- Back & Print Buttons (Hidden during print) -->
      <div class="flex items-center justify-between gap-4 mb-8 print:hidden">
        <button @click="router.back()" class="inline-flex items-center gap-1.5 text-xs font-bold text-on-surface-variant hover:text-on-surface transition-colors">
          <ArrowLeft class="w-4 h-4" /> Kembali
        </button>
        <button @click="handlePrint" class="inline-flex items-center gap-1.5 bg-[#04000D] hover:bg-black text-[#DCEEB1] hover:text-[#DCEEB1]/90 px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm">
          <Printer class="w-4 h-4" /> Cetak Bukti
        </button>
      </div>

      <!-- Ticket Border & Details -->
      <div class="border-2 border-dashed border-slate-100 rounded-2xl p-6 sm:p-8 relative print:border-slate-300">
        <!-- Logo Header -->
        <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-5 mb-5 print:border-slate-200">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-black text-[#DCEEB1] flex items-center justify-center font-mono font-black text-xs rounded-lg print:border print:border-black">IF</div>
            <div>
              <h2 class="font-extrabold text-sm tracking-tight text-on-surface">I-FEST 2026</h2>
              <span class="font-mono text-[9px] text-accent-magenta font-bold block -mt-0.5 uppercase tracking-wider">Informatics Festival</span>
            </div>
          </div>
          <div class="text-right">
            <span class="font-mono text-[9px] font-bold uppercase tracking-widest text-slate-400">Bukti Pendaftaran</span>
            <p class="font-mono text-xs font-bold text-on-surface mt-0.5">#IF-{{ pendaftaran.id.toString().padStart(5, '0') }}</p>
          </div>
        </div>

        <!-- Ticket Body Details -->
        <div class="space-y-4 text-xs">
          <!-- Competition Name -->
          <div>
            <span class="text-[9px] font-bold uppercase text-slate-400 tracking-wider">Kategori Lomba</span>
            <p class="text-sm font-extrabold text-on-surface mt-0.5">{{ pendaftaran.lomba?.title }}</p>
            <p class="text-[10px] text-on-surface-variant/80 font-semibold">{{ pendaftaran.lomba?.scale }} · {{ pendaftaran.lomba?.babak }}</p>
          </div>

          <!-- Team Details -->
          <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4 print:border-slate-200">
            <div>
              <span class="text-[9px] font-bold uppercase text-slate-400 tracking-wider">Nama Tim</span>
              <p class="font-bold text-on-surface mt-0.5">{{ pendaftaran.team_name || 'Individu' }}</p>
            </div>
            <div>
              <span class="text-[9px] font-bold uppercase text-slate-400 tracking-wider">Tanggal Verifikasi</span>
              <p class="font-mono font-bold text-on-surface mt-0.5">
                {{ new Date(pendaftaran.updated_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
              </p>
            </div>
          </div>

          <!-- Team Members -->
          <div v-if="pendaftaran.team_members?.length" class="border-t border-slate-100 pt-4 print:border-slate-200">
            <span class="text-[9px] font-bold uppercase text-slate-400 tracking-wider block mb-2">Anggota Tim</span>
            <div class="space-y-1">
              <div 
                v-for="(member, idx) in pendaftaran.team_members" 
                :key="idx" 
                class="flex justify-between items-center bg-slate-50 border border-slate-100/50 rounded-lg px-3 py-1.5 text-[11px] print:bg-none print:border-none print:px-0 print:py-0.5"
              >
                <span class="font-bold text-on-surface">{{ member.name }}</span>
                <span class="font-mono text-on-surface-variant/60">{{ member.email }}</span>
              </div>
            </div>
          </div>

          <!-- Fee & Payment details -->
          <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4 print:border-slate-200">
            <div>
              <span class="text-[9px] font-bold uppercase text-slate-400 tracking-wider">Biaya Lunas</span>
              <p class="text-base font-extrabold text-on-surface mt-0.5">{{ pendaftaran.lomba?.fee }}</p>
            </div>
            <div class="flex flex-col items-end justify-center">
              <span class="inline-flex items-center gap-1 font-mono text-[9px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-full bg-[#DCEEB1] text-on-surface border border-[#DCEEB1]/70 print:border-slate-400">
                <CheckCircle class="w-3.5 h-3.5" /> Lunas / Verified
              </span>
            </div>
          </div>
        </div>

        <!-- Watermark/Sticker Footer -->
        <div class="mt-8 border-t border-slate-100 pt-5 text-center flex flex-col items-center justify-center print:border-slate-200">
          <p class="text-[10px] text-slate-400 leading-relaxed font-semibold max-w-xs">
            Bukti pendaftaran ini diterbitkan secara sah oleh Panitia I-FEST HMTI Universitas Tadulako 2026.
          </p>
          <div class="mt-3 font-mono text-[8px] font-bold uppercase tracking-widest text-slate-350">
            Secure Digital Receipt &bullet; I-FEST 2026
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
/* Clean printing styles */
@media print {
  body {
    background-color: white !important;
    color: black !important;
    padding: 0 !important;
  }
  .min-h-screen {
    min-height: auto !important;
    background-color: white !important;
    padding: 0 !important;
  }
}
</style>
