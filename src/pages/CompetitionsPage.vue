<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { competitionsData } from '../data/competitionsData'

const route = useRoute()
const router = useRouter()

const activeCompetition = ref(competitionsData[0])

const selectCompetition = (comp) => {
  activeCompetition.value = comp
  router.replace({ query: { id: comp.id } })
}

onMounted(() => {
  window.scrollTo(0, 0)
  
  const compId = route.query.id
  if (compId) {
    const found = competitionsData.find(c => c.id === compId)
    if (found) {
      activeCompetition.value = found
    }
  }
})

watch(() => route.query.id, (newId) => {
  if (newId) {
    const found = competitionsData.find(c => c.id === newId)
    if (found) {
      activeCompetition.value = found
    }
  }
})

// Go back home helper
const goHome = () => {
  router.push('/')
}
</script>

<template>
  <div class="min-h-screen bg-off-white select-text pb-24 relative overflow-hidden">
    <!-- Coarse grid dots overlay -->
    <div class="absolute inset-0 bg-[radial-gradient(#04000D_1px,transparent_1px)] [background-size:24px_24px] opacity-[0.03] pointer-events-none z-0"></div>
    <div class="absolute inset-0 bg-noise-grain opacity-[0.02] pointer-events-none z-0"></div>

    <div class="max-w-container-max mx-auto px-4 sm:px-6 md:px-lg pt-12 md:pt-16 relative z-10">
      
      <!-- HEADER CHROME -->
      <header class="border-b-4 border-[#04000D] pb-6 mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6 select-none">
        <div>
          <!-- Back button with Brutalist hover mechanics -->
          <button 
            @click="goHome"
            class="flex items-center gap-2 font-mono text-xs uppercase tracking-widest font-bold text-[#04000D] mb-4 bg-white border-2 border-[#04000D] px-3.5 py-1.5 transform hover:-translate-x-1 hover:-translate-y-0.5 active:translate-x-0 active:translate-y-0 transition-transform duration-150 cursor-pointer"
            style="box-shadow: 3px 3px 0px 0px #04000D;"
          >
            ← Kembali ke Home
          </button>
          
          <span class="font-mono text-xs uppercase tracking-[0.25em] font-bold text-[#FF3D8B] block mb-1">
            IFEST 2026 DIGITAL ARENA
          </span>
          <h1 class="font-black text-4xl sm:text-5xl md:text-6xl tracking-[-0.04em] leading-none text-[#04000D] riso-bleed uppercase">
            DIGITAL COMPETITIONS.
          </h1>
        </div>
        
        <div class="font-mono text-xs text-[#04000D]/60 uppercase tracking-wider text-left md:text-right border-l-2 md:border-l-0 md:border-r-2 border-[#04000D] pl-4 md:pl-0 md:pr-4 py-1.5">
          <span>Swiss Clean Brutalism Layout</span><br />
          <span>Central Sulawesi Technology Epoch</span>
        </div>
      </header>

      <!-- TWO-COLUMN MAIN GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-8 md:gap-12 items-start">
        
        <!-- SIDEBAR NAVIGATION / TAB SWITCHER -->
        <aside class="lg:sticky lg:top-24 z-20">
          <div class="flex items-center gap-2.5 mb-6 select-none">
            <span class="font-mono text-xs font-bold uppercase tracking-widest bg-[#04000D] text-white px-2 py-0.5">CATEGORIES</span>
            <span class="font-mono text-xs font-bold uppercase tracking-widest text-[#04000D]/40">KATEGORI LOMBA</span>
          </div>

          <!-- Vertical list on desktop, Horizontal scrolling strip on mobile -->
          <nav class="flex lg:flex-col gap-4 overflow-x-auto pb-4 lg:pb-0 lg:overflow-x-visible no-scrollbar -mx-4 px-4 lg:mx-0 lg:px-0">
            <button 
              v-for="comp in competitionsData" 
              :key="comp.id"
              @click="selectCompetition(comp)"
              class="flex-shrink-0 lg:flex-shrink text-left font-mono font-bold text-xs uppercase border-2 md:border-3 border-[#04000D] p-4 flex flex-col justify-between transition-all duration-150 cursor-pointer min-w-[200px] lg:w-full select-none"
              :style="{ 
                backgroundColor: activeCompetition.id === comp.id ? comp.cardBg : '#F5F5F5',
                transform: activeCompetition.id === comp.id ? 'translate(0px, 0px)' : 'translate(-3px, -3px)',
                boxShadow: activeCompetition.id === comp.id ? '0px 0px 0px 0px #04000D' : '4px 4px 0px 0px #04000D'
              }"
            >
              <div class="flex justify-between items-start mb-4 w-full">
                <span class="bg-[#04000D] text-white text-[8px] px-1.5 py-0.5 rounded-none font-mono tracking-widest">{{ comp.id }}</span>
                <span class="text-[9px] opacity-60 tracking-wider">{{ comp.scale }}</span>
              </div>
              
              <span class="text-sm font-black text-[#04000D] leading-none uppercase select-none">
                {{ comp.title }}
              </span>
            </button>
          </nav>
        </aside>

        <!-- CONTENT AREA (THE DEEP CONTEXT) -->
        <main class="w-full">
          <!-- Dynamic details page for the active selection -->
          <article class="bg-white border-3 md:border-4 border-[#04000D] p-6 sm:p-8 md:p-12 relative" :style="{ boxShadow: '10px 10px 0px 0px #04000D' }">
            
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
                <span class="font-mono text-xs font-extrabold text-[#FF3D8B] leading-tight uppercase font-mono">{{ activeCompetition.fee }}</span>
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
            <div class="mb-10 p-5 bg-[#DCEEB1]/20 border border-[#04000D] font-mono text-xs font-bold text-[#04000D] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <span class="text-accent-magenta">★ JADWAL PENTING:</span>
              <span class="bg-white border border-[#04000D] px-3 py-1">{{ activeCompetition.schedule }}</span>
            </div>

            <!-- CALL TO ACTIONS (DAFTAR SEKARANG) -->
            <div class="border-t-3 border-[#04000D] pt-8 flex flex-col sm:flex-row items-center gap-6 select-none">
              
              <!-- DAFTAR SEKARANG Button -->
              <a 
                :href="activeCompetition.registrationLink"
                class="riso-btn-plate flex-1 w-full bg-[#04000D] text-white py-4 rounded-full font-button text-xs text-center font-black select-none tracking-widest transition-transform hover:-translate-y-1 active:translate-y-0"
                :style="{ '--plate-color': activeCompetition.id === 'REG-03' ? '#D6FF00' : activeCompetition.cardBg }"
              >
                DAFTAR SEKARANG
              </a>
              
              <!-- UNDUH JUKNIS Button -->
              <a 
                :href="activeCompetition.guidebookLink"
                target="_blank"
                class="riso-btn-plate flex-1 w-full bg-white text-[#04000D] border-2 border-[#04000D] py-4 rounded-full font-button text-xs text-center font-black select-none tracking-widest transition-transform hover:-translate-y-1 active:translate-y-0"
                style="--plate-color: #D6FF00;"
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
