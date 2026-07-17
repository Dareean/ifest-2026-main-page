<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { competitionsData } from '../data/competitionsData'
import { useAuthStore } from '../stores/auth'
import { Lock } from 'lucide-vue-next'
import api from '../utils/api'

const auth = useAuthStore()
const dokumenAssetModules = import.meta.glob('../assets/dokumen/**/*', { eager: true })
const getAsset = (assetModules, folder, fileName) => {
  const path = `../assets/${folder}/${fileName}`
  return assetModules[path]?.default || assetModules[path] || ''
}

const route = useRoute()
const router = useRouter()
const isLoggedIn = computed(() => auth.isAuthenticated)

function handleDaftar() {
  if (!activeCompetition.value) return
  if (isLoggedIn) {
    router.push(`/dashboard/competitions?id=${activeCompetition.value.id}`)
  } else {
    router.push(`/login?redirect=${encodeURIComponent('/dashboard/competitions?id=' + activeCompetition.value.id)}`)
  }
}

const activeKodes = ref([])
const visibleCompetitions = computed(() =>
  competitionsData.filter(c => activeKodes.value.includes(c.id))
)
const activeCompetition = ref(null)
const isScrolled = ref(false)

const handleScroll = () => {
  isScrolled.value = window.scrollY > 50
}

const selectCompetition = (comp) => {
  activeCompetition.value = comp
  router.replace({ query: { id: comp.id } })
}

const selectCompetitionAndScroll = (comp) => {
  selectCompetition(comp)
  const el = document.getElementById('competition-details')
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const getShortName = (id) => {
  switch (id) {
    case 'NAT-01': return 'CP'
    case 'NAT-02': return 'UI/UX'
    case 'NAT-03': return 'Business Plan'
    case 'REG-01': return 'Video'
    case 'REG-02': return 'Poster'
    case 'REG-03': return 'Hackathon'
    default: return id
  }
}

onMounted(async () => {
  window.scrollTo(0, 0)
  window.addEventListener('scroll', handleScroll)

  // Initialize countdown immediately (non-blocking)
  calculateTimeLeft()
  countdownInterval = setInterval(calculateTimeLeft, 1000)

  // Fetch active lomba codes from API
  try {
    const res = await api.get('/lombas')
    activeKodes.value = res.data.data.map(l => l.kode)
  } catch {
    activeKodes.value = competitionsData.map(c => c.id)
  }
  
  const compId = route.query.id
  if (compId) {
    const found = visibleCompetitions.value.find(c => c.id === compId)
    if (found) {
      activeCompetition.value = found
    }
  }
  if (!activeCompetition.value && visibleCompetitions.value.length > 0) {
    activeCompetition.value = visibleCompetitions.value[0]
  }
})

// Countdown Lomba 11 Juli 2026
const announcementTarget = new Date('2026-07-09T00:00:00+08:00').getTime()
const countdown = ref({
  days: 0,
  hours: 0,
  minutes: 0,
  seconds: 0,
  expired: false
})

const calculateTimeLeft = () => {
  const now = new Date().getTime()
  const difference = announcementTarget - now

  if (difference <= 0) {
    countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0, expired: true }
    return
  }

  const days = Math.floor(difference / (1000 * 60 * 60 * 24))
  const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60))
  const seconds = Math.floor((difference % (1000 * 60)) / 1000)

  countdown.value = { days, hours, minutes, seconds, expired: false }
}

const formatTimeNumber = (num) => {
  return String(num).padStart(2, '0')
}

let countdownInterval = null

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  if (countdownInterval) clearInterval(countdownInterval)
})

watch(() => route.query.id, (newId) => {
  if (newId) {
    const found = visibleCompetitions.value.find(c => c.id === newId)
    if (found) {
      activeCompetition.value = found
    }
  }
})

</script>

<template>
  <div class="min-h-screen bg-off-white select-text pb-24 relative overflow-hidden">
    <!-- Coarse grid dots overlay -->
    <div class="absolute inset-0 bg-[radial-gradient(#04000D_1px,transparent_1px)] [background-size:24px_24px] opacity-[0.03] pointer-events-none z-0"></div>
    <div class="absolute inset-0 bg-noise-grain opacity-[0.02] pointer-events-none z-0"></div>

    <!-- HEADER CHROME -->
    <header 
      :class="[
        'fixed top-0 left-0 w-full z-50 transition-all duration-300 ease-in-out border-[#04000D] select-none',
        isScrolled 
          ? 'bg-white/85 backdrop-blur-md shadow-sm border-b' 
          : 'bg-white/100 py-6 h-auto border-b-4'
      ]"
    >
      <div class="max-w-container-max mx-auto px-4 sm:px-6 md:px-lg transition-all duration-300" :class="isScrolled ? 'py-2 h-16 flex items-center' : ''">
        <div 
          class="w-full flex transition-all duration-300 ease-in-out"
          :class="isScrolled ? 'flex-row items-center justify-between' : 'flex-col md:flex-row md:items-end justify-between gap-6'"
        >
          <div 
            class="flex transition-all duration-300 ease-in-out"
            :class="isScrolled ? 'flex-row items-center gap-4' : 'flex-col'"
          >
            <!-- Back button with Brutalist hover mechanics -->
            <router-link
              :to="{ path: '/', state: { scrollToSection: '' } }"
              :class="[
                'flex items-center gap-2 font-mono text-xs uppercase tracking-widest font-bold text-[#04000D] transition-all duration-300 cursor-pointer',
                isScrolled 
                  ? 'px-1 py-1' 
                  : 'bg-white border-2 border-[#04000D] px-3.5 py-1.5 transform hover:-translate-x-1 hover:-translate-y-0.5 active:translate-x-0 active:translate-y-0'
              ]"
              :style="isScrolled ? {} : { boxShadow: '3px 3px 0px 0px #04000D' }"
            >
              {{ isScrolled ? '← Home' : '← Kembali ke Home' }}
            </router-link>
            
            <span 
              :class="[
                'font-mono text-xs uppercase tracking-[0.25em] font-bold text-[#FF3D8B] transition-all duration-300',
                isScrolled ? 'opacity-0 pointer-events-none hidden' : 'block mb-1 mt-4'
              ]"
            >
              IFEST 2026 DIGITAL ARENA
            </span>
            <h1 
              :class="[
                'font-black text-[#04000D] riso-bleed uppercase transition-all duration-300',
                isScrolled 
                  ? 'text-lg md:text-xl font-bold uppercase tracking-normal' 
                  : 'text-4xl sm:text-5xl md:text-6xl tracking-[-0.04em] leading-none'
              ]"
            >
              DIGITAL COMPETITIONS.
            </h1>
          </div>
          
          <div 
            :class="[
              'font-mono text-xs text-[#04000D]/60 uppercase tracking-wider text-left md:text-right border-l-2 md:border-l-0 md:border-r-2 border-[#04000D] pl-4 md:pl-0 md:pr-4 py-1.5 transition-all duration-300',
              isScrolled ? 'opacity-0 pointer-events-none hidden' : 'block'
            ]"
          >
            <span>Central Sulawesi Technology Epoch</span>
          </div>
        </div>
      </div>

      <!-- Sticky Sub-Tabs Row (Visible on scroll) -->
      <div 
        v-if="isScrolled" 
        class="border-t border-[#04000D] bg-white/90 backdrop-blur-md py-1.5 px-4 sm:px-6 md:px-lg transition-all duration-300"
      >
        <div class="max-w-container-max mx-auto flex items-center gap-2 overflow-x-auto no-scrollbar font-mono text-[10px] uppercase font-bold text-[#04000D]">
          <span class="text-[#04000D]/50 mr-2 flex-shrink-0">Jump To:</span>
          <button 
            v-for="comp in visibleCompetitions" 
            :key="comp.id"
            @click="selectCompetitionAndScroll(comp)"
            class="px-2.5 py-1 border border-[#04000D] transition-all duration-150 flex-shrink-0 hover:bg-[#04000D] hover:text-white"
            :class="activeCompetition?.id === comp.id ? 'bg-[#04000D] text-white' : 'bg-white text-[#04000D]'"
          >
            {{ getShortName(comp.id) }}
          </button>
        </div>
      </div>
    </header>

    <div class="max-w-container-max mx-auto px-4 sm:px-6 md:px-lg pt-64 sm:pt-72 md:pt-80 relative z-10">
      
      <!-- LOCKED STATE SCREEN -->
      <div v-if="!countdown.expired" class="flex flex-col items-center justify-center py-12 md:py-16 text-center select-none">
        <div 
          class="bg-[#FFF9E6] border-4 border-[#04000D] p-8 md:p-12 max-w-2xl w-full relative"
          style="box-shadow: 10px 10px 0px 0px #04000D;"
        >
          <!-- Padlock Graphic (Neo-Brutalist Stamp Style) -->
          <div class="w-24 h-24 bg-[#FF3D8B] text-white border-4 border-[#04000D] rounded-none flex items-center justify-center mx-auto mb-8 shadow-[4px_4px_0px_0px_#04000D] transform -rotate-3 hover:rotate-0 transition-transform duration-200">
            <Lock class="w-12 h-12" stroke-width="2.5" />
          </div>

          <span class="font-mono text-xs uppercase tracking-widest text-[#FF3D8B] font-black block mb-2">
            ACCESS DENIED // AREA TERKUNCI
          </span>
          <h2 class="font-black text-3xl sm:text-4xl md:text-5xl tracking-tight leading-none text-[#04000D] uppercase mb-6">
            Lomba Belum Dirilis!
          </h2>
          
          <p class="font-mono text-xs sm:text-sm text-[#04000D]/85 leading-relaxed max-w-md mx-auto mb-8 border-y-2 border-dashed border-[#04000D]/15 py-4">
            Maaf, seluruh berkas panduan teknis, detail kriteria penilaian, dan formulir registrasi kompetisi IFEST 2026 masih dirahasiakan hingga peluncuran resmi.
          </p>

          <!-- Large Centered Countdown Timer -->
          <div class="flex items-center gap-2 sm:gap-4 justify-center mb-8">
            <div class="flex flex-col items-center">
              <div class="bg-white border-2 border-[#04000D] px-2 py-1 sm:px-3.5 sm:py-2 min-w-[50px] sm:min-w-[72px] text-center shadow-[3px_3px_0px_0px_#04000D]">
                <span class="font-mono text-lg sm:text-2xl font-black text-[#04000D] tracking-tight">{{ formatTimeNumber(countdown.days) }}</span>
              </div>
              <span class="font-mono text-[8px] sm:text-[9px] font-extrabold uppercase tracking-widest text-[#04000D]/55 mt-2.5">HARI</span>
            </div>
            <span class="font-mono font-black text-lg sm:text-xl text-[#04000D] mb-4 sm:mb-5">:</span>

            <div class="flex flex-col items-center">
              <div class="bg-white border-2 border-[#04000D] px-2 py-1 sm:px-3.5 sm:py-2 min-w-[50px] sm:min-w-[72px] text-center shadow-[3px_3px_0px_0px_#04000D]">
                <span class="font-mono text-lg sm:text-2xl font-black text-[#04000D] tracking-tight">{{ formatTimeNumber(countdown.hours) }}</span>
              </div>
              <span class="font-mono text-[8px] sm:text-[9px] font-extrabold uppercase tracking-widest text-[#04000D]/55 mt-2.5">JAM</span>
            </div>
            <span class="font-mono font-black text-lg sm:text-xl text-[#04000D] mb-4 sm:mb-5">:</span>

            <div class="flex flex-col items-center">
              <div class="bg-white border-2 border-[#04000D] px-2 py-1 sm:px-3.5 sm:py-2 min-w-[50px] sm:min-w-[72px] text-center shadow-[3px_3px_0px_0px_#04000D]">
                <span class="font-mono text-lg sm:text-2xl font-black text-[#04000D] tracking-tight">{{ formatTimeNumber(countdown.minutes) }}</span>
              </div>
              <span class="font-mono text-[8px] sm:text-[9px] font-extrabold uppercase tracking-widest text-[#04000D]/55 mt-2.5">MENIT</span>
            </div>
            <span class="font-mono font-black text-lg sm:text-xl text-[#04000D] mb-4 sm:mb-5">:</span>

            <div class="flex flex-col items-center">
              <div class="bg-white border-2 border-[#04000D] px-2 py-1 sm:px-3.5 sm:py-2 min-w-[50px] sm:min-w-[72px] text-center shadow-[3px_3px_0px_0px_#04000D]">
                <span class="font-mono text-lg sm:text-2xl font-black text-[#FF3D8B] tracking-tight">{{ formatTimeNumber(countdown.seconds) }}</span>
              </div>
              <span class="font-mono text-[8px] sm:text-[9px] font-extrabold uppercase tracking-widest text-[#04000D]/55 mt-2.5">DETIK</span>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <router-link to="/" class="riso-btn-plate bg-[#04000D] text-white px-8 py-3 rounded-full font-button text-xs font-bold text-center inline-block" style="--plate-color: #FF3D8B;">
              ← Kembali ke Beranda
            </router-link>
          </div>
        </div>
      </div>

      <!-- UNLOCKED STATE (ORIGINAL TWO-COLUMN GRID) -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-8 md:gap-12 items-start">
        
        <!-- SIDEBAR NAVIGATION / TAB SWITCHER -->
        <aside class="lg:sticky lg:top-36 z-20">
          <div class="flex items-center gap-2.5 mb-6 select-none">
            <span class="font-mono text-xs font-bold uppercase tracking-widest bg-[#04000D] text-white px-2 py-0.5">CATEGORIES</span>
            <span class="font-mono text-xs font-bold uppercase tracking-widest text-[#04000D]/40">KATEGORI LOMBA</span>
          </div>

          <!-- Vertical list on desktop, Horizontal scrolling strip on mobile -->
          <nav class="flex lg:flex-col gap-4 overflow-x-auto pb-4 lg:pb-0 lg:overflow-x-visible no-scrollbar -mx-4 px-4 lg:mx-0 lg:px-0">
            <button 
              v-for="comp in visibleCompetitions" 
              :key="comp.id"
              @click="selectCompetitionAndScroll(comp)"
              class="flex-shrink-0 lg:flex-shrink text-left font-mono font-bold text-xs uppercase border-2 md:border-3 border-[#04000D] p-4 flex flex-col justify-between transition-all duration-150 cursor-pointer min-w-[200px] lg:w-full select-none"
              :style="{ 
                backgroundColor: activeCompetition?.id === comp.id ? comp.cardBg : '#F5F5F5',
                transform: activeCompetition?.id === comp.id ? 'translate(0px, 0px)' : 'translate(-3px, -3px)',
                boxShadow: activeCompetition?.id === comp.id ? '0px 0px 0px 0px #04000D' : '4px 4px 0px 0px #04000D',
                color: activeCompetition?.id === comp.id ? comp.textColor : '#04000D'
              }"
            >
              <div class="flex justify-between items-start mb-4 w-full">
                <span 
                  class="text-[8px] px-1.5 py-0.5 rounded-none font-mono tracking-widest"
                  :style="{ 
                    backgroundColor: activeCompetition?.id === comp.id && comp.textColor === '#FFFFFF' ? '#FFFFFF' : '#04000D',
                    color: activeCompetition?.id === comp.id && comp.textColor === '#FFFFFF' ? '#04000D' : '#FFFFFF'
                  }"
                >
                  {{ comp.id }}
                </span>
                <span 
                  class="text-[9px] tracking-wider opacity-60"
                  :style="{ color: activeCompetition?.id === comp.id ? comp.textColor : '#04000D' }"
                >
                  {{ comp.scale }}
                </span>
              </div>
              
              <span 
                class="text-sm font-black leading-none uppercase select-none"
                :style="{ color: activeCompetition?.id === comp.id ? comp.textColor : '#04000D' }"
              >
                {{ comp.title }}
              </span>
            </button>
          </nav>

          <!-- Countdown Timer Sidebar Bento Card (Neo-Brutalist, hidden on mobile) -->
          <div 
            class="hidden lg:block mt-8 bg-[#FFF9E6] border-3 border-[#04000D] p-5 relative select-none"
            style="box-shadow: 5px 5px 0px 0px #FDE047;"
          >
            <div class="flex items-center gap-2 mb-2.5">
              <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FF3D8B] opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-[#FF3D8B]"></span>
              </span>
              <span class="font-mono text-[9px] font-black uppercase tracking-wider text-[#04000D]/60">
                OFFICIAL RELEASE
              </span>
            </div>
            
            <h3 class="font-black text-sm uppercase tracking-tight text-[#04000D] mb-3">
              Pendaftaran & Panduan Lomba
            </h3>

            <!-- Timer Ticker Grid (Compact) -->
            <div class="flex items-center gap-2 mb-3">
              <div class="flex flex-col items-center">
                <div class="bg-white border-2 border-[#04000D] px-2 py-1 min-w-[42px] text-center shadow-[1.5px_1.5px_0px_0px_#04000D]">
                  <span class="font-mono text-sm font-black text-[#04000D] tracking-tight">{{ formatTimeNumber(countdown.days) }}</span>
                </div>
                <span class="font-mono text-[8px] font-extrabold uppercase tracking-widest text-[#04000D]/55 mt-1.5">HARI</span>
              </div>
              <span class="font-mono font-black text-sm text-[#04000D] mb-3">:</span>

              <div class="flex flex-col items-center">
                <div class="bg-white border-2 border-[#04000D] px-2 py-1 min-w-[42px] text-center shadow-[1.5px_1.5px_0px_0px_#04000D]">
                  <span class="font-mono text-sm font-black text-[#04000D] tracking-tight">{{ formatTimeNumber(countdown.hours) }}</span>
                </div>
                <span class="font-mono text-[8px] font-extrabold uppercase tracking-widest text-[#04000D]/55 mt-1.5">JAM</span>
              </div>
              <span class="font-mono font-black text-sm text-[#04000D] mb-3">:</span>

              <div class="flex flex-col items-center">
                <div class="bg-white border-2 border-[#04000D] px-2 py-1 min-w-[42px] text-center shadow-[1.5px_1.5px_0px_0px_#04000D]">
                  <span class="font-mono text-sm font-black text-[#04000D] tracking-tight">{{ formatTimeNumber(countdown.minutes) }}</span>
                </div>
                <span class="font-mono text-[8px] font-extrabold uppercase tracking-widest text-[#04000D]/55 mt-1.5">MENIT</span>
              </div>
              <span class="font-mono font-black text-sm text-[#04000D] mb-3">:</span>

              <div class="flex flex-col items-center">
                <div class="bg-white border-2 border-[#04000D] px-2 py-1 min-w-[42px] text-center shadow-[1.5px_1.5px_0px_0px_#04000D]">
                  <span class="font-mono text-sm font-black text-[#FF3D8B] tracking-tight">{{ formatTimeNumber(countdown.seconds) }}</span>
                </div>
                <span class="font-mono text-[8px] font-extrabold uppercase tracking-widest text-[#04000D]/55 mt-1.5">DETIK</span>
              </div>
            </div>

            <!-- Date Announcement Badge -->
            <div class="mt-3 pt-2.5 border-t border-dashed border-[#04000D]/15 flex items-center justify-between">
              <span class="font-mono text-[8px] font-bold text-[#04000D]/65">RILIS:</span>
              <span class="bg-[#FF3D8B] text-white font-mono text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 border border-[#04000D] shadow-[1.5px_1.5px_0px_0px_#04000D]">
                11 JULI 2026
              </span>
            </div>
          </div>
        </aside>

        <!-- CONTENT AREA (THE DEEP CONTEXT) -->
        <main class="w-full">
          <!-- Dynamic details page for the active selection -->
          <article v-if="activeCompetition" id="competition-details" class="bg-white border-3 md:border-4 border-[#04000D] p-6 sm:p-8 md:p-12 relative" :style="{ boxShadow: '10px 10px 0px 0px #04000D' }">
            
            <!-- Category Banner Accent -->
            <div class="absolute -top-0.5 right-6 bg-[#04000D] text-white px-4 py-1.5 font-mono text-[9px] sm:text-xs font-bold uppercase tracking-widest">
              {{ activeCompetition.scale }}
            </div>
            
            <!-- Tagline and Title -->
            <div class="mb-8 border-b-2 border-dashed border-[#04000D]/20 pb-6">
              <span class="font-mono text-xs uppercase tracking-widest text-[#FF3D8B] font-extrabold block mb-2">
                {{ activeCompetition.tagline }}
              </span>
              <h2 class="font-black text-3xl sm:text-4xl md:text-5xl uppercase tracking-tighter leading-none text-[#04000D]">
                {{ activeCompetition.title }}
              </h2>
            </div>

            <!-- Deep Description -->
            <div class="mb-10 text-[#04000D]/90 leading-relaxed font-body text-sm sm:text-base md:text-lg">
              <p class="font-medium mb-4 text-[#04000D]">
                {{ activeCompetition.description }}
              </p>
              <p>
                {{ activeCompetition.longDescription }}
              </p>
            </div>

            <!-- QUICK TECHNICAL SPECS GRID -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 border-2 border-[#04000D] bg-[#04000D] gap-[2px] mb-10 select-none">
              
              <!-- Target -->
              <div class="bg-[#F5F5F5] p-5 flex flex-col justify-between">
                <span class="font-mono text-[9px] uppercase tracking-widest font-bold text-[#04000D]/50 mb-4 block">Target Peserta</span>
                <span class="font-mono text-xs font-bold text-[#04000D] leading-tight uppercase">{{ activeCompetition.target }}</span>
              </div>
              
              <!-- Team Limit -->
              <div class="bg-[#F5F5F5] p-5 flex flex-col justify-between">
                <span class="font-mono text-[9px] uppercase tracking-widest font-bold text-[#04000D]/50 mb-4 block">Ketentuan Tim</span>
                <span class="font-mono text-xs font-bold text-[#04000D] leading-tight uppercase">{{ activeCompetition.teamRequirements }}</span>
              </div>
              
              <!-- Tech / Stack -->
              <div class="bg-[#F5F5F5] p-5 flex flex-col justify-between">
                <span class="font-mono text-[9px] uppercase tracking-widest font-bold text-[#04000D]/50 mb-4 block">Teknologi / Stack</span>
                <span class="font-mono text-xs font-bold text-[#04000D] leading-tight uppercase">{{ activeCompetition.languages }}</span>
              </div>
              
              <!-- Biaya Registrasi -->
              <div class="bg-[#F5F5F5] p-5 flex flex-col justify-between">
                <span class="font-mono text-[9px] uppercase tracking-widest font-bold text-[#04000D]/50 mb-4 block">Biaya Registrasi</span>
                <div class="text-xs font-extrabold text-[#FF3D8B] leading-tight uppercase font-mono">
                  <span class="block">Gel 1: {{ activeCompetition.feeGelombang1 || activeCompetition.fee }}</span>
                  <span class="block text-[10px] text-[#04000D]/60">Gel 2: {{ activeCompetition.feeGelombang2 || activeCompetition.fee }}</span>
                </div>
              </div>

            </div>

            <!-- DETAILED SUB-THEMES (SUB-TEMA) -->
            <div class="mb-10 p-6 sm:p-8 bg-[#F5F5F5] border-2 border-[#04000D]" style="box-shadow: 4px 4px 0px 0px #04000D;">
              <h4 class="font-mono text-xs font-black uppercase tracking-widest text-white bg-[#04000D] inline-block px-3 py-1 mb-6">
                SUB-THEMES / SUB-TEMA PILIHAN
              </h4>
              
              <ul class="flex flex-col gap-4 font-mono text-xs md:text-sm text-[#04000D]/95">
                <li 
                  v-for="(theme, idx) in activeCompetition.subThemes" 
                  :key="idx"
                  class="flex items-start gap-3 border-b border-[#04000D]/10 pb-3 last:border-b-0 last:pb-0"
                >
                  <span class="text-[#FF3D8B] font-bold text-sm">✦</span>
                  <span class="font-bold leading-tight">{{ theme }}</span>
                </li>
              </ul>
            </div>

            <!-- RULES & GUIDELINES (ATURAN LOMBA) -->
            <div class="mb-10 p-6 sm:p-8 border-2 border-dashed border-[#04000D]">
              <h4 class="font-mono text-xs font-black uppercase tracking-widest text-[#04000D] mb-6">
                RULES &amp; INSTRUCTIONS / PERATURAN &amp; KETENTUAN UMUM
              </h4>
              
              <ol class="flex flex-col gap-4 font-body text-xs sm:text-sm text-[#04000D]/85 list-decimal pl-4">
                <li 
                  v-for="(rule, idx) in activeCompetition.rules" 
                  :key="idx"
                  class="leading-relaxed border-b border-[#04000D]/10 pb-3 last:border-0 last:pb-0"
                >
                  {{ rule }}
                </li>
              </ol>
            </div>

            <!-- TIMELINE BLOCK -->
            <div v-if="activeCompetition.schedule" class="mb-10 p-5 bg-[#DCEEB1]/20 border border-[#04000D] font-mono text-xs font-bold text-[#04000D] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <span class="text-accent-magenta">★ JADWAL PENTING:</span>
              <span class="bg-white border border-[#04000D] px-3 py-1">{{ activeCompetition.schedule }}</span>
            </div>

            <!-- CONTACT PERSON -->
            <div v-if="activeCompetition.contactPerson" class="mb-10 p-4 bg-[#04000D] text-white border-2 border-[#04000D] flex items-center gap-3 font-mono text-xs">
              <span class="uppercase tracking-widest font-black text-[#FDE047]">CP:</span>
              <span class="font-bold">{{ activeCompetition.contactPerson }}</span>
            </div>

            <!-- CALL TO ACTIONS (DAFTAR SEKARANG) -->
            <div class="border-t-3 border-[#04000D] pt-8 flex flex-col sm:flex-row items-center gap-6 select-none">
              
              <!-- DAFTAR SEKARANG Button -->
              <button
                @click="handleDaftar"
                class="riso-btn-plate flex-1 w-full bg-[#04000D] text-white py-4 rounded-full font-button text-xs text-center font-black select-none tracking-widest transition-transform hover:-translate-y-1 active:translate-y-0 cursor-pointer"
                :style="{ '--plate-color': activeCompetition.id === 'REG-03' ? '#FDE047' : activeCompetition.cardBg }"
              >
                {{ isLoggedIn ? 'DAFTAR via DASHBOARD' : 'DAFTAR SEKARANG' }}
              </button>
              
              <!-- UNDUH JUKNIS Button -->
              <a 
                v-if="activeCompetition.guidebookLink && activeCompetition.guidebookLink !== '#'"
                :href="activeCompetition.guidebookLink.startsWith('http') ? activeCompetition.guidebookLink : getAsset(dokumenAssetModules, 'dokumen', activeCompetition.guidebookLink)"
                download
                target="_blank"
                class="riso-btn-plate flex-1 w-full bg-white text-[#04000D] border-2 border-[#04000D] py-4 rounded-full font-button text-xs text-center font-black select-none tracking-widest transition-transform hover:-translate-y-1 active:translate-y-0"
                style="--plate-color: #FDE047;"
              >
                UNDUH JUKNIS LOMBA (GUIDEBOOK) ↓
              </a>
              
            </div>

          </article>
        </main>

      </div>

    </div>
  </div>
</template>

<style scoped>
/* Scroller styles for mobile horizontal navigation */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}
</style>
