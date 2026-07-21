<script setup>
import { ref, nextTick, onMounted, onUnmounted } from 'vue'
import { roadshowTargets } from '../data/roadshowData'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'

const isScrolled = ref(false)

const handleScroll = () => {
  isScrolled.value = window.scrollY > 50
}

onMounted(async () => {
  window.scrollTo(0, 0)
  window.addEventListener('scroll', handleScroll)

  await nextTick()

  try {
    const isMobile = window.innerWidth < 768
    if (!isMobile) {
      gsap.registerPlugin(ScrollTrigger)

      const roadshowGrid = document.querySelector('#roadshow-grid')
      if (!roadshowGrid) return

      gsap.fromTo('#roadshow-grid > div',
        { opacity: 0, y: 40 },
        {
          opacity: 1,
          y: 0,
          duration: 0.6,
          stagger: 0.1,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: roadshowGrid,
            start: 'top 85%',
            toggleActions: 'play none none none'
          }
        }
      )
    }
  } catch (error) {
    console.error('Roadshow animation init failed:', error)
  }
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  ScrollTrigger.getAll().forEach((trigger) => trigger.kill())
})

// Asset loading for decorative risk stamp shards
const visualAssetModules = import.meta.glob('../assets/visual_assets/*', {
  eager: true,
  import: 'default',
})
const getAsset = (assetModules, folder, fileName) => assetModules[`../assets/${folder}/${fileName}`] ?? ''
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
          ? 'bg-white/85 backdrop-blur-md py-2 h-16 shadow-sm border-b' 
          : 'bg-white/100 py-6 h-auto border-b-4'
      ]"
    >
      <div class="max-w-container-max mx-auto px-4 sm:px-6 md:px-lg h-full flex items-center">
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
              IFEST 2026 ROADSHOW MAP
            </span>
            <h1 
              :class="[
                'font-black text-[#04000D] riso-bleed uppercase transition-all duration-300',
                isScrolled 
                  ? 'text-lg md:text-xl font-bold uppercase tracking-normal' 
                  : 'text-4xl sm:text-5xl md:text-6xl tracking-[-0.04em] leading-none'
              ]"
            >
              ROADSHOW INKLUSIF.
            </h1>
          </div>
          
          <div 
            :class="[
              'font-mono text-xs text-[#04000D]/60 uppercase tracking-wider text-left md:text-right border-l-2 md:border-l-0 md:border-r-2 border-[#04000D] pl-4 md:pl-0 md:pr-4 py-1.5 transition-all duration-300',
              isScrolled ? 'opacity-0 pointer-events-none hidden' : 'block'
            ]"
          >
            <span>Social Impact Program</span><br />
            <span>Central Sulawesi Tour Hub</span>
          </div>
        </div>
      </div>
    </header>

    <div class="max-w-container-max mx-auto px-4 sm:px-6 md:px-lg pt-64 sm:pt-72 md:pt-80 relative z-10">

      <!-- METRICS & IMPACT SUMMARY BLOCK -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-16 select-none">
        <!-- Metric 1 -->
        <div class="bg-white border-3 border-[#04000D] p-6 flex flex-col justify-between" style="box-shadow: 6px 6px 0px 0px #04000D;">
          <span class="font-mono text-xs text-[#04000D]/60 uppercase tracking-widest font-bold">PENJANGKAUAN WILAYAH</span>
          <div class="mt-4">
            <h3 class="text-3xl sm:text-4xl font-black text-[#04000D]">
              <AnimatedCounter :end="25" :duration="1400" />
              <span class="ml-2 text-accent-magenta">TITIK KUNJUNGAN</span>
            </h3>
            <p class="font-mono text-xs text-[#04000D]/80 mt-2">Palu, Sigi, Donggala (Pasigala)</p>
          </div>
        </div>
        
        <!-- Metric 2 -->
        <div class="bg-[#DCEEB1] border-3 border-[#04000D] p-6 flex flex-col justify-between" style="box-shadow: 6px 6px 0px 0px #04000D;">
          <span class="font-mono text-xs text-[#04000D]/60 uppercase tracking-widest font-bold">ESTIMASI IMPACT</span>
          <div class="mt-4">
            <h3 class="text-3xl sm:text-4xl font-black text-[#04000D]">
              <AnimatedCounter :end="800" :duration="1600" suffix="+" />
              <span class="ml-2 text-accent-magenta">PELAJAR &amp; MARGINAL</span>
            </h3>
            <p class="font-mono text-xs text-[#04000D]/80 mt-2">Pemberdayaan Digital Inklusif Terjangkau</p>
          </div>
        </div>
      </div>

      <!-- CORE SECTION 1: TARGET AUDIENS (5 Brutalist Cards) -->
      <section class="mb-16">
        <div class="flex items-center gap-3 mb-8 select-none border-b-2 border-dashed border-[#04000D]/10 pb-4">
          <span class="font-mono text-xs font-bold uppercase tracking-widest bg-[#04000D] text-white px-2.5 py-0.5">TARGET AUDIENS</span>
          <span class="font-mono text-xs font-bold uppercase tracking-widest text-[#04000D]/60">5 SASARAN PROGRAM SOSIAL</span>
        </div>

        <div id="roadshow-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 select-none">
          <div 
            v-for="card in roadshowTargets" 
            :key="card.title"
            class="flex flex-col border-2 md:border-3 border-[#04000D] transition-transform duration-200 hover:-rotate-1" 
            style="box-shadow: 4px 4px 0px 0px #04000D;"
          >
            <!-- Top Header Block (White Card with Accent Border) -->
            <div class="bg-white border-b-2 border-[#04000D] p-4 flex flex-col justify-center min-h-[95px] text-left">
              <h4 class="font-black text-sm text-[#04000D] leading-tight uppercase">{{ card.title }}</h4>
              <span class="font-mono text-[9px] font-bold mt-1 uppercase" :style="{ color: card.accentColor }">{{ card.subtitle }}</span>
            </div>
            
            <!-- Bottom Body Block (Midnight Deep Blue) -->
            <div class="bg-[#04000D] p-5 flex-1 flex flex-col justify-start">
              <ul class="flex flex-col gap-3 font-mono text-[10px] sm:text-[11px] text-white/95">
                <li v-for="point in card.points" :key="point" class="flex items-start gap-2">
                  <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 mt-1.5" :style="{ backgroundColor: card.accentColor }"></span>
                  <span class="leading-tight text-left">{{ point }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <!-- CORE SECTION 2: MAP & EXECUTION MATRIX -->
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_1.3fr] gap-8 md:gap-12 items-start mb-16">
        
        <!-- CURRICULUM PILLARS -->
        <div class="p-6 sm:p-8 bg-white border-3 border-[#04000D]" style="box-shadow: 6px 6px 0px 0px #04000D;">
          <div class="flex items-center gap-3 mb-6 border-b border-[#04000D]/10 pb-3 select-none">
            <span class="font-mono text-xs font-bold uppercase tracking-widest bg-[#FF3D8B] text-white px-2 py-0.5">SYLLABUS</span>
            <h3 class="font-mono text-xs font-bold uppercase tracking-widest text-[#04000D]">Matriks Materi Inti</h3>
          </div>

          <div class="flex flex-col gap-6 select-none">
            <div class="border-2 border-dashed border-[#04000D] p-5 bg-[#F5F5F5] relative">
              <div class="absolute -top-3 left-4 bg-[#FF3D8B] text-white font-mono text-[9px] px-2 py-0.5 font-bold uppercase">PILLAR 01</div>
              <h4 class="font-black uppercase text-base tracking-tight leading-none mb-3 text-[#04000D] mt-2">LITERASI &amp; KEAMANAN DIGITAL</h4>
              <p class="text-xs text-[#04000D]/80 leading-relaxed">Edukasi seputar siber, perlindungan data pribadi, dan anti-fraud finansial dasar.</p>
            </div>
            
            <div class="border-2 border-dashed border-[#04000D] p-5 bg-[#F5F5F5] relative">
              <div class="absolute -top-3 left-4 bg-[#FDE047] text-[#04000D] font-mono text-[9px] px-2 py-0.5 font-bold uppercase">PILLAR 02</div>
              <h4 class="font-black uppercase text-base tracking-tight leading-none mb-3 text-[#04000D] mt-2">TEKNOLOGI AKSESIBILITAS</h4>
              <p class="text-xs text-[#04000D]/80 leading-relaxed">Pemanfaatan piranti lunak pembaca layar, konverter teks-ke-suara, dan hardware adaptif.</p>
            </div>

            <div class="border-2 border-dashed border-[#04000D] p-5 bg-[#F5F5F5] relative">
              <div class="absolute -top-3 left-4 bg-[#8839FF] text-white font-mono text-[9px] px-2 py-0.5 font-bold uppercase">PILLAR 03</div>
              <h4 class="font-black uppercase text-base tracking-tight leading-none mb-3 text-[#04000D] mt-2">LITERASI AI &amp; ETIKA DIGITAL</h4>
              <p class="text-xs text-[#04000D]/80 leading-relaxed">Kolaborasi program Google Student Ambassador; memperkenalkan Gemini AI sebagai kolaborator produktif secara etis.</p>
            </div>

            <div class="border-2 border-dashed border-[#04000D] p-5 bg-[#F5F5F5] relative">
              <div class="absolute -top-3 left-4 bg-[#D86BFF] text-[#04000D] font-mono text-[9px] px-2 py-0.5 font-bold uppercase">PILLAR 04</div>
              <h4 class="font-black uppercase text-base tracking-tight leading-none mb-3 text-[#04000D] mt-2">KOLABORATIF PENGABDIAN</h4>
              <p class="text-xs text-[#04000D]/80 leading-relaxed">Sinergi terarah mahasiswa Informatika Universitas Tadulako langsung membantu problem riil di lapangan.</p>
            </div>
          </div>
        </div>

        <!-- EXECUTION STEPS & TIMELINE -->
        <div class="p-6 sm:p-8 bg-white border-3 border-[#04000D]" style="box-shadow: 6px 6px 0px 0px #04000D;">
          <div class="flex items-center gap-3 mb-6 border-b border-[#04000D]/10 pb-3 select-none">
            <span class="font-mono text-xs font-bold uppercase tracking-widest bg-[#FDE047] text-[#04000D] px-2 py-0.5">PLAN</span>
            <h3 class="font-mono text-xs font-bold uppercase tracking-widest text-[#04000D]">Alur Pelaksanaan Kunjungan</h3>
          </div>

          <div class="flex flex-col gap-8 relative pl-6 border-l-2 border-[#04000D] py-2 select-none">
            <!-- Stage 01 -->
            <div class="relative">
              <div class="absolute -left-[35px] top-0 w-6 h-6 bg-[#FF3D8B] border-2 border-[#04000D] rounded-full flex items-center justify-center font-mono text-[10px] font-bold text-white shadow-[2px_2px_0px_0px_#04000D]">
                1
              </div>
              <span class="font-mono text-[10px] font-bold uppercase text-[#FF3D8B] block mb-1">STAGE 01</span>
              <h4 class="font-black uppercase text-base text-[#04000D] mb-2">KONSOLIDASI &amp; FINALISASI KONSEP</h4>
              <p class="text-xs text-[#04000D]/80 leading-relaxed">
                Koordinasi internal panitia pelaksana, penyesuaian silabus modul ajar per segmen sasaran, serta konfirmasi tertulis dengan para mitra kolaborator wilayah.
              </p>
            </div>

            <!-- Stage 02 -->
            <div class="relative">
              <div class="absolute -left-[35px] top-0 w-6 h-6 bg-[#FDE047] border-2 border-[#04000D] rounded-full flex items-center justify-center font-mono text-[10px] font-bold text-[#04000D] shadow-[2px_2px_0px_0px_#04000D]">
                2
              </div>
              <span class="font-mono text-[10px] font-bold uppercase text-[#04000D]/60 block mb-1">STAGE 02</span>
              <h4 class="font-black uppercase text-base text-[#04000D] mb-2">EKSEKUSI KUNJUNGAN BERTAHAP</h4>
              <p class="text-xs text-[#04000D]/80 leading-relaxed">
                Pelaksanaan roadshow berkala ke tiap titik lokasi penjangkauan (SLB, sekolah umum, &amp; balai desa) secara terstruktur berdasarkan kesiapan rute operasional.
              </p>
            </div>

            <!-- Stage 03 -->
            <div class="relative">
              <div class="absolute -left-[35px] top-0 w-6 h-6 bg-[#8839FF] border-2 border-[#04000D] rounded-full flex items-center justify-center font-mono text-[10px] font-bold text-white shadow-[2px_2px_0px_0px_#04000D]">
                3
              </div>
              <span class="font-mono text-[10px] font-bold uppercase text-[#8839FF] block mb-1">STAGE 03</span>
              <h4 class="font-black uppercase text-base text-[#04000D] mb-2">DAMPAK &amp; PUBLIKASI MAKSIMAL</h4>
              <p class="text-xs text-[#04000D]/80 leading-relaxed">
                Penyusunan pelaporan dampak nyata, peluncuran aftermovie perjalanan, serta eskalasi konten komunikasi bersama ekosistem #TeamGoogle.
              </p>
            </div>
          </div>
        </div>

      </div>

      <!-- FOOTER ACTION & COLLABORATION PROPOSAL -->
      <div class="border-t-4 border-[#04000D] pt-8 flex flex-col md:flex-row items-center justify-between gap-6 select-none">
        <div class="flex items-start gap-3 flex-1 text-left">
          <span class="text-xl text-[#FF3D8B] select-none leading-none">✦</span>
          <p class="font-mono text-[10px] sm:text-xs text-[#04000D]/70 italic leading-relaxed">
            Tanggal &amp; lokasi spesifik dalam tahap koordinasi dengan pihak kolaborator daerah Pasigala. Proposal kolaborasi sekolah atau komunitas dapat diajukan secara resmi melalui panitia pelaksana.
          </p>
        </div>
      </div>

    </div>
  </div>
</template>
