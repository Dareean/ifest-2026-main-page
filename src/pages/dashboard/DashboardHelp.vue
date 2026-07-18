<script setup>
import { ref, onMounted } from 'vue'
import { HelpCircle, ChevronDown, ChevronUp, MessageSquare, PhoneCall } from 'lucide-vue-next'
import api from '../../utils/api'

const faqs = ref([
  {
    question: 'Bagaimana cara membayar biaya pendaftaran?',
    answer: 'Pembayaran dilakukan sesuai dengan metode pembayaran yang diinstruksikan oleh panitia (melalui transfer Bank atau E-Wallet). Bukti transfer dikirimkan saat pengisian formulir pendaftaran. Setelah itu, tim Anda akan diverifikasi oleh admin dalam waktu maksimal 1x24 jam.',
    open: false
  },
  {
    question: 'Apakah diperbolehkan mengganti anggota tim setelah mendaftar?',
    answer: 'Pergantian anggota tim diperbolehkan sebelum batas waktu pendaftaran ditutup. Untuk melakukan pergantian anggota, silakan ajukan permohonan dengan menghubungi WhatsApp bantuan admin dengan menyertakan nama tim dan identitas anggota baru.',
    open: false
  },
  {
    question: 'Format berkas apa yang harus dikirim di tab pengumpulan karya?',
    answer: 'Anda cukup mengumpulkan tautan (link) Google Drive yang berisi dokumen atau file karya sesuai petunjuk teknis (Juknis) dari lomba masing-masing. Pastikan status akses link Google Drive Anda telah disetel menjadi "Public/Siapa saja yang memiliki link dapat melihat".',
    open: false
  },
  {
    question: 'Kapan pengumuman babak penyisihan dan final dilakukan?',
    answer: 'Jadwal lengkap babak penyisihan, technical briefing, dan babak final dapat Anda lihat secara berkala di dalam menu "Lomba", lalu pilih lomba yang Anda ikuti dan buka Tab "Timeline".',
    open: false
  },
  {
    question: 'Bagaimana jika pendaftaran saya ditolak?',
    answer: 'Pendaftaran biasanya ditolak karena berkas bukti pembayaran kurang jelas atau tidak sesuai. Anda dapat melihat alasan penolakan pada catatan panitia di Tab "Registrasi & Tim". Silakan hubungi admin WhatsApp untuk bantuan lebih lanjut.',
    open: false
  }
])

function toggleFaq(index) {
  faqs.value[index].open = !faqs.value[index].open
}

onMounted(async () => {
  try {
    const res = await api.get('/faqs')
    const data = res.data.data
    if (data?.length) {
      faqs.value = data.map(f => ({
        question: f.question,
        answer: f.answer,
        open: false,
      }))
    }
  } catch {
    // fallback to hardcoded faqs
  }
})
</script>

<template>
  <div class="w-full">
    <!-- Header -->
    <div class="mb-8">
      <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-accent-magenta mb-1 block">Help Center</span>
      <h1 class="font-extrabold text-3xl md:text-4xl tracking-tight text-on-surface">Bantuan & FAQ</h1>
      <p class="text-xs text-on-surface-variant/80 mt-2 leading-relaxed">
        Punya pertanyaan seputar perlombaan I-FEST 2026? Temukan jawaban cepat di bawah ini atau hubungi langsung panitia pendamping kami.
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- FAQ Accordion (Left) -->
      <div class="lg:col-span-8 bg-white border border-[#04000D]/5 shadow-[0_8px_30px_rgb(0,0,0,0.015)] rounded-2xl p-5 md:p-6 space-y-3">
        <div 
          v-for="(faq, index) in faqs" 
          :key="index" 
          class="border-b border-slate-50 last:border-b-0 pb-4 last:pb-0 pt-3 first:pt-0"
        >
          <button 
            @click="toggleFaq(index)" 
            class="w-full flex items-center justify-between text-left gap-4 font-bold text-xs md:text-sm text-on-surface hover:text-accent-magenta transition-colors focus:outline-none"
          >
            <span>{{ faq.question }}</span>
            <component :is="faq.open ? ChevronUp : ChevronDown" class="w-4 h-4 text-on-surface-variant/60 flex-shrink-0" />
          </button>
          
          <Transition name="fade-slide">
            <div v-if="faq.open" class="mt-2.5 text-xs text-on-surface-variant/85 leading-relaxed pr-6">
              {{ faq.answer }}
            </div>
          </Transition>
        </div>
      </div>

      <!-- Contact CTA (Right) -->
      <div class="lg:col-span-4 bg-[#DCEEB1]/20 border border-[#DCEEB1]/55 rounded-2xl p-6 flex flex-col gap-5 shadow-sm">
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 rounded-xl bg-white border border-[#DCEEB1]/65 flex items-center justify-center text-on-surface flex-shrink-0">
            <MessageSquare class="w-5 h-5" />
          </div>
          <div>
            <h4 class="font-extrabold text-sm text-on-surface">Masih Butuh Bantuan?</h4>
            <p class="text-xs text-on-surface-variant/80 mt-1 leading-relaxed">
              Hubungi Customer Service kami secara langsung di WhatsApp untuk pendaftaran dan kendala teknis.
            </p>
          </div>
        </div>
        <a 
          href="https://wa.me/6282197762612?text=Halo%20Admin%20I-FEST%202026%2C%20saya%20butuh%20bantuan%20terkait%20dashboard..." 
          target="_blank"
          class="inline-flex items-center justify-center gap-1.5 bg-[#04000D] hover:bg-black text-[#DCEEB1] hover:text-[#DCEEB1]/90 px-5 py-3.5 rounded-xl text-xs font-bold transition-all shadow-sm w-full text-center"
        >
          <PhoneCall class="w-3.5 h-3.5" /> Hubungi WhatsApp Admin
        </a>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-slide-enter-active, .fade-slide-leave-active {
  transition: all 0.2s ease;
}
.fade-slide-enter-from, .fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
