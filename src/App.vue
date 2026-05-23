<script setup>
import { ref, computed, onBeforeUnmount, onMounted } from 'vue'
import RisoLoader from './components/RisoLoader.vue'

const showContent = ref(false)
const isLoading = ref(true)
const isMenuOpen = ref(false)
const activeZineIndex = ref(0)

const isMobile = ref(false)
const updateViewport = () => {
  isMobile.value = typeof window !== 'undefined' ? window.innerWidth < 768 : false
}

// SECTION K: Animated Counter States
const countPartisipan = ref(0)
const countRoadshow = ref(0)
const countTalent = ref(0)
const countExposure = ref(0)
const hasAnimated = ref(false)

const formattedPartisipan = computed(() => {
  return countPartisipan.value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") + '+'
})

const formattedRoadshow = computed(() => {
  return countRoadshow.value + ' TITIK'
})

const formattedTalent = computed(() => {
  return countTalent.value + '+'
})

const formattedExposure = computed(() => {
  return countExposure.value + 'K+'
})

const animateCounters = () => {
  if (hasAnimated.value) return
  hasAnimated.value = true

  const duration = 1500 // Snappy 1.5 seconds mechanical ticker count-up
  const startTime = performance.now()

  const tick = (now) => {
    const elapsed = now - startTime
    const progress = Math.min(elapsed / duration, 1)

    // Steep easeOutQuart to count up rapidly and lock violently
    const ease = 1 - Math.pow(1 - progress, 4)

    countPartisipan.value = Math.floor(ease * 8000)
    countRoadshow.value = Math.floor(ease * 25)
    countTalent.value = Math.floor(ease * 500)
    countExposure.value = Math.floor(ease * 800)

    if (progress < 1) {
      requestAnimationFrame(tick)
    } else {
      // Violent lock at final targets
      countPartisipan.value = 8000
      countRoadshow.value = 25
      countTalent.value = 500
      countExposure.value = 800
    }
  }

  requestAnimationFrame(tick)
}

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value
  if (isMenuOpen.value) {
    document.body.style.overflow = 'hidden'
  } else {
    if (!isLoading.value) {
      document.body.style.overflow = ''
    }
  }
}

const onSplit = () => {
  showContent.value = true
  document.body.style.overflow = ''
}

const onLoaded = () => {
  isLoading.value = false
}

let observer = null

const visualAssetModules = import.meta.glob('./assets/visual_assets/*', {
  eager: true,
  import: 'default',
})

const mediaPartnerAssetModules = import.meta.glob('./assets/medpart/*', {
  eager: true,
  import: 'default',
})

const strategicPartnerAssetModules = import.meta.glob('./assets/sponsor-strategic_partner/*', {
  eager: true,
  import: 'default',
})

const mainLogoAssetModules = import.meta.glob('./assets/logo_utama/*', {
  eager: true,
  import: 'default',
})

const getAsset = (assetModules, folder, fileName) => assetModules[`./assets/${folder}/${fileName}`] ?? ''

const galleryImages = [
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'academic_workshop.png'),
    alt: 'Tema Utama: Resonansi Digital',
    title: 'Tema Utama: Resonansi Digital',
    description: 'Capturing the 2025 spirit of youth collaboration impacting Central Sulawesi technology growth.'
  },
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'palu_dev_day.png'),
    alt: '2.525 Penerima Manfaat',
    title: '2.525 Penerima Manfaat',
    description: 'Direct impact tracking over 13 High Schools visited and 1,082 participants crowding the Tadulako Main Auditorium.'
  },
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'smart_helmet.png'),
    alt: '30 Produk Teknologi Lokal',
    title: '30 Produk Teknologi Lokal',
    description: 'Showcasing 30 native inventions across Mobile Apps, VR Megalith Showcases, IoT, and Web Development (Ternary AI).'
  },
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'tech_meetup.png'),
    alt: '390.1K Instagram Reach',
    title: '390.1K Instagram Reach',
    description: 'Capturing our massive digital exposure footprint under the official @ifest_untad network during the campaign.'
  }
]

const heroDecorations = [
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'Component 1.png'),
    alt: 'Abstract visual asset',
    className: 'absolute top-24 -left-12 md:-left-4 lg:left-6 w-48 md:w-80 opacity-75 md:opacity-85 mix-blend-multiply contrast-125 animate-float pointer-events-none z-0 filter',
    delay: '0s',
  },
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'rb1 1.png'),
    alt: 'Abstract visual asset',
    className: 'absolute bottom-40 -right-12 md:right-6 w-56 md:w-96 opacity-75 md:opacity-85 mix-blend-multiply contrast-125 animate-float pointer-events-none z-0 filter',
    delay: '-2s',
  },
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'ry1 1.png'),
    alt: 'Abstract visual asset',
    className: 'absolute bottom-32 -left-12 md:left-[5%] lg:left-[10%] w-40 md:w-64 opacity-70 md:opacity-80 mix-blend-multiply contrast-125 animate-float pointer-events-none z-0 filter',
    delay: '-4s',
  },
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'cat1 1.png'),
    alt: 'Abstract visual asset',
    className: 'absolute top-24 -right-12 md:right-[10%] lg:right-[16%] w-32 md:w-56 opacity-75 md:opacity-85 mix-blend-multiply contrast-125 animate-float pointer-events-none z-0 filter',
    delay: '-1s',
  },
]

const mediaPartners = [
  { name: 'RRI CENTRAL SULAWESI', src: '' },
  { name: 'INFOCAMABA', src: getAsset(mediaPartnerAssetModules, 'medpart', '(1) INFOCAMABA.png') },
  { name: 'HMPTI UNISA PALU', src: getAsset(mediaPartnerAssetModules, 'medpart', '(2) HMPTI UNISA PALU.png') },
  { name: 'LPM HITAM PUTIH', src: getAsset(mediaPartnerAssetModules, 'medpart', '(3) LPM HITAM PUTIH.JPG') },
  { name: 'LPM NASEHA', src: getAsset(mediaPartnerAssetModules, 'medpart', '(4) LPM NASEHA.png') },
  { name: 'HIMA - SI UIN', src: getAsset(mediaPartnerAssetModules, 'medpart', '(5) HIMA - SI UIN.png') },
  { name: 'PROGRAMMING TADULAKO', src: getAsset(mediaPartnerAssetModules, 'medpart', '(6) programmig_tad.png') },
  { name: 'HMPSI STMIK ADHI GUNA PALU', src: getAsset(mediaPartnerAssetModules, 'medpart', '(7) HMPSI STMIK Adhi Guna Palu (1) (1).png') },
  { name: 'ANIMEDIA TADULAKO', src: getAsset(mediaPartnerAssetModules, 'medpart', '(8) Animedia Tadulako.png') },
  { name: 'PERMIKOMNAS WILAYAH X', src: getAsset(mediaPartnerAssetModules, 'medpart', '(9) Permikomnas Wilayah X.png') },
  { name: 'HIMATIF UIN', src: getAsset(mediaPartnerAssetModules, 'medpart', '(10) HIMATIF UIN.jpeg') },
]

const mainStrategicPartner = {
  name: 'Hannah Asa Indonesia',
  shortName: 'HANNAH ASA INDONESIA',
  src: getAsset(strategicPartnerAssetModules, 'sponsor-strategic_partner', 'Hannah Asa.png'),
}

const strategicPartners = [
  {
    name: 'Sultan Music',
    shortName: 'SULTAN MUSIC',
    src: getAsset(strategicPartnerAssetModules, 'sponsor-strategic_partner', 'Sultan Music.png'),
    description: 'Official Production & Vendor partner menjamin mutu infrastruktur panggung dan malam puncak acara.',
    logoMaxWidth: 'max-w-[200px]',
  },
  {
    name: 'Google Student Ambasador',
    shortName: 'GSA',
    src: getAsset(strategicPartnerAssetModules, 'sponsor-strategic_partner', 'gsa.png'),
    description: 'Strategic execution partner yang mendukung operasional kolaborasi dan aktivasi lintas program.',
    logoMaxWidth: 'max-w-[180px]',
  },
]

const tickerPartners = [
  {
    ...mainStrategicPartner,
    roleLabel: 'MAIN STRATEGIC PARTNER',
    logoClass: 'h-8 md:h-9',
  },
  ...strategicPartners.map((partner) => ({
    ...partner,
    roleLabel: 'STRATEGIC PARTNER',
    logoClass: 'h-7 md:h-8',
  })),
]

onMounted(() => {
  updateViewport()
  window.addEventListener('resize', updateViewport)
  if (isLoading.value) {
    document.body.style.overflow = 'hidden'
  }
  const revealElements = document.querySelectorAll('[data-reveal]')

  const revealCallback = (entries, activeObserver) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible')
        activeObserver.unobserve(entry.target)
      }
    })
  }

  observer = new IntersectionObserver(revealCallback, {
    threshold: 0.15,
    rootMargin: '0px 0px -50px 0px',
  })

  revealElements.forEach((element) => observer.observe(element))

  // Scroll trigger counter observer for Section K
  const keyNumbersSection = document.querySelector('#impact-dashboard')
  if (keyNumbersSection) {
    const keyNumbersObserver = new IntersectionObserver((entries, observerInstance) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateCounters()
          observerInstance.unobserve(entry.target)
        }
      })
    }, {
      threshold: 0.15,
    })
    keyNumbersObserver.observe(keyNumbersSection)
  }

  const interactiveElements = document.querySelectorAll('button, a')
  const setPressedScale = (element, scale) => {
    element.style.transform = scale
  }

  interactiveElements.forEach((element) => {
    const handleMouseDown = () => setPressedScale(element, 'scale(0.96)')
    const handleMouseUp = () => setPressedScale(element, 'scale(1)')
    const handleMouseLeave = () => setPressedScale(element, 'scale(1)')

    element.addEventListener('mousedown', handleMouseDown)
    element.addEventListener('mouseup', handleMouseUp)
    element.addEventListener('mouseleave', handleMouseLeave)

    element.__ifestHandlers = { handleMouseDown, handleMouseUp, handleMouseLeave }
  })

  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', (event) => {
      event.preventDefault()
      const targetId = anchor.getAttribute('href')

      if (targetId === '#') return

      const targetElement = document.querySelector(targetId)
      if (targetElement) {
        targetElement.scrollIntoView({ behavior: 'smooth' })
      }
    })
  })
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateViewport)
  if (observer) {
    observer.disconnect()
    observer = null
  }
})
</script>

<template>
  <RisoLoader @split="onSplit" @loaded="onLoaded" />

  <div 
    class="riso-canvas bg-off-white min-h-screen text-[#04000D] font-body-md select-text pb-12 transition-all duration-700 ease-out"
    :class="{ 'opacity-0 scale-[0.98] pointer-events-none h-screen overflow-hidden': !showContent }"
  >
    
    <!-- SECTION A: Top Navigation Chrome -->
    <header class="fixed inset-x-0 top-0 z-[60] w-full border-b border-dashed border-[#04000D]/30 bg-off-white/95 backdrop-blur-sm">
      <div class="max-w-container-max mx-auto flex justify-between items-center px-3 sm:px-4 md:px-lg py-3 md:py-sm">
        
        <!-- Logo Flex Container with UNTAD -> HMTI -> I-FEST -->
        <div class="flex items-center gap-2 md:gap-4 select-none">
          <div class="flex items-center gap-1.5 md:gap-3">
            <img alt="UNTAD Logo" class="h-7 md:h-10 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'logo_untad.png')" />
            <img alt="HMTI Logo" class="h-7 md:h-10 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'HMTI LOGO.png')" />
            <img alt="I-FEST Logo" class="h-7 md:h-10 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'Logo-IFEST-2026.png')" />
          </div>
          <span class="hidden sm:inline-block font-mono text-base md:text-lg font-bold tracking-widest text-[#04000D] border-l border-[#04000D]/20 pl-3 md:pl-4 riso-bleed">I-FEST 2026</span>
        </div>

        <nav class="hidden md:flex items-center space-x-xl select-none">
          <a class="font-body-md text-body-md text-[#04000D] font-bold border-b border-[#04000D] pb-0.5 hover:text-accent-magenta transition-colors duration-200" href="#pillars">Roadshow</a>
          <a class="font-body-md text-body-md text-[#04000D]/75 hover:text-accent-magenta transition-colors duration-200" href="#pillars">Kompetisi</a>
          <a class="font-body-md text-body-md text-[#04000D]/75 hover:text-accent-magenta transition-colors duration-200" href="#pillars">Konser</a>
          <a class="font-body-md text-body-md text-[#04000D]/75 hover:text-accent-magenta transition-colors duration-200" href="#partners">Network</a>
        </nav>

        <div class="flex items-center gap-2 select-none">
          <button class="riso-btn-plate bg-[#04000D] text-white px-3 md:px-xl py-1.5 md:py-xs rounded-full font-button text-xs md:text-button select-none font-bold" style="--plate-color: #FF3D8B;">
            AMANKAN TIKET
          </button>
          <button @click="toggleMenu" class="p-1.5 flex items-center justify-center border border-[#04000D] rounded bg-white hover:bg-off-white md:hidden transition-colors" aria-label="Toggle menu">
            <span class="material-symbols-outlined text-xl text-[#04000D] font-bold">
              {{ isMenuOpen ? 'close' : 'menu' }}
            </span>
          </button>
        </div>
      </div>
    </header>

    <!-- Mobile Drawer Menu Overlay -->
    <div v-if="isMenuOpen" class="fixed inset-0 z-50 w-full h-screen bg-off-white flex flex-col justify-center items-center p-8 md:hidden">
      <div class="absolute inset-0 bg-[radial-gradient(#04000D_1px,transparent_1px)] [background-size:16px_16px] opacity-[0.04] pointer-events-none z-0"></div>
      <div class="absolute inset-0 bg-noise-grain opacity-[0.03] pointer-events-none z-0"></div>
      
      <nav class="flex flex-col items-center gap-8 z-10 text-center">
        <a @click="toggleMenu" class="font-mono text-2xl font-bold text-[#04000D] border-b-2 border-dashed border-[#FF3D8B] pb-1 hover:text-accent-magenta" href="#pillars">Roadshow</a>
        <a @click="toggleMenu" class="font-mono text-2xl font-bold text-[#04000D] border-b-2 border-dashed border-[#D6FF00] pb-1 hover:text-accent-magenta" href="#pillars">Kompetisi</a>
        <a @click="toggleMenu" class="font-mono text-2xl font-bold text-[#04000D] border-b-2 border-dashed border-[#8839FF] pb-1 hover:text-accent-magenta" href="#pillars">Konser</a>
        <a @click="toggleMenu" class="font-mono text-2xl font-bold text-[#04000D] border-b-2 border-dashed border-[#04000D]/30 pb-1 hover:text-accent-magenta" href="#partners">Network</a>
        
        <button @click="toggleMenu" class="riso-btn-plate bg-[#04000D] text-white px-8 py-3 rounded-full font-button text-base font-bold mt-8" style="--plate-color: #FF3D8B;">
          AMANKAN TIKET
        </button>
      </nav>
    </div>

    <!-- SECTION B: Hero Section (Tactile Colored Paper Canvas) -->
    <section id="hero" class="bg-off-white riso-canvas min-h-[95vh] pt-[120px] md:pt-[140px] pb-[100px] relative overflow-hidden flex flex-col justify-center border-b border-dashed border-[#04000D]/30">
      
      <!-- Layered, Floating 3D Assets (Stamps Bleeding Into The Colored Paper Fibers) -->
      <img
        v-for="asset in heroDecorations"
        :key="asset.src"
        :alt="asset.alt"
        :class="asset.className"
        :src="asset.src"
        :style="{ animationDelay: asset.delay }"
      />
      
      <div class="max-w-container-max mx-auto px-6 md:px-lg w-full py-xl relative z-10 flex flex-col items-center text-center">
        <div data-reveal id="hero-content" class="flex flex-col items-center">
          <p class="font-mono text-[#FF3D8B] text-xs md:text-base tracking-[0.22em] uppercase mb-md font-bold select-none riso-bleed px-4">
            THE BIGGEST IT FESTIVAL IN EASTERN INDONESIA
          </p>
          
          <!-- Massive Condensed Typography with Magenta Misregistration Plate -->
          <h1 class="font-bold text-[#04000D] text-4xl sm:text-6xl md:text-8xl lg:text-[120px] tracking-tighter leading-none md:leading-[0.85] mb-lg max-w-5xl px-4 riso-text-shadow-magenta riso-bleed">
            DIGITAL SYMPHONY
          </h1>
          
          <p class="font-body-md text-base md:text-xl text-[#04000D]/80 max-w-2xl mb-xl leading-relaxed mx-auto px-4">
            Mengorkestrasi Inovasi Global untuk Masa Depan Berkelanjutan. HMTI Universitas Tadulako memanggil 8.000+ inovator untuk bergabung dalam revolusi digital terbesar di Sulawesi Tengah.
          </p>
          
          <button class="riso-btn-plate bg-[#04000D] text-white px-8 md:px-xl py-3 md:py-md rounded-full font-button text-button select-none font-bold" style="--plate-color: #D6FF00;">
            EXPLORE THE SYMPHONY ↓
          </button>
        </div>
      </div>
      
      <!-- Ticker Tape Ribbon (Redesigned for Professional Riso Vibe) -->
      <div class="absolute bottom-0 left-0 w-full bg-[#D6FF00] border-t-2 border-[#04000D] py-3 md:py-4 overflow-hidden z-20 select-none shadow-[0_-4px_0_0_rgba(4,0,13,0.05)]">
        <div class="flex whitespace-nowrap animate-marquee items-center">
          <div v-for="group in 2" :key="group" class="flex items-center">
            <div v-for="item in 3" :key="item" class="flex items-center px-4 md:px-8">
              <template v-for="partner in tickerPartners" :key="`${group}-${item}-${partner.name}`">
                <img
                  :alt="partner.name"
                  :class="partner.logoClass"
                  class="w-auto object-contain filter brightness-0 invert mr-3 md:mr-5"
                  :src="partner.src"
                />
                <span class="font-mono text-[#04000D] text-sm md:text-base uppercase tracking-widest font-bold mr-6 md:mr-10">{{ partner.roleLabel }}: {{ partner.shortName }}</span>
              </template>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION C: Intro (Tactile Text Section) -->
    <section class="bg-off-white riso-canvas py-[80px] md:py-[110px] px-6 md:px-lg border-b border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
      <!-- Subtle Decorative Background Assets (Literal Riso stamped look) -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'ry2 1.png')" 
        alt="Decorative Risograph Star Shard" 
        class="absolute top-12 left-4 md:left-12 lg:left-24 w-16 md:w-28 opacity-25 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'rb4 1.png')" 
        alt="Decorative Risograph Star Shard" 
        class="absolute bottom-12 right-4 md:right-12 lg:right-24 w-20 md:w-32 opacity-25 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />

      <div class="max-w-container-max mx-auto text-center relative z-10">
        <h2 class="font-bold text-2xl sm:text-3xl md:text-5xl text-[#04000D] tracking-tighter leading-tight md:leading-none mb-lg px-2 riso-text-shadow-magenta riso-bleed">
          Bukan Sekadar Festival. Ini Adalah Episentrum Perubahan.
        </h2>
        <p class="font-body-lg text-base md:text-xl text-[#04000D]/85 max-w-3xl mx-auto leading-relaxed px-2">
          Disrupsi kecerdasan buatan dan teknologi bergerak lebih cepat dari sebelumnya. I-FEST 2026 hadir untuk menjembatani digital divide, mempertemukan talenta akar rumput dengan standar industri nasional. Dari ruang kelas pedalaman hingga panggung konser raksasa, mari ciptakan resonansi yang nyata.
        </p>
      </div>
    </section>

    <!-- SECTION P: 3 Core Pillars (Brutalist Trilogy Engine) -->
    <section id="pillars" class="bg-[#F5F5F5] riso-canvas py-20 md:py-24 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
      <!-- Background Decorative Stamp Shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'ry6 1.png')" 
        alt="Decorative Riso Plate Shard" 
        class="absolute top-10 right-10 w-36 md:w-56 opacity-20 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />
      
      <div class="max-w-container-max mx-auto relative z-10">
        
        <!-- Section Header -->
        <div class="mb-12 md:mb-16 text-left">
          <span class="font-mono text-xs uppercase tracking-widest font-bold text-[#04000D] block mb-2">
            THE TRILOGY ENGINE
          </span>
          <h2 class="font-black text-4xl sm:text-5xl md:text-7xl tracking-[-0.04em] leading-none text-[#04000D] riso-bleed riso-text-shadow-magenta">
            Tiga Pilar Simfoni.
          </h2>
        </div>

        <!-- The Brutalist Trilogy Grid System -->
        <div class="flex flex-col md:grid md:grid-cols-[1.2fr_1fr_1.1fr] border-2 md:border-3 border-[#04000D] bg-[#04000D] gap-[2px] md:gap-[3px] rounded-none overflow-hidden select-none" style="box-shadow: 6px 6px 0px 0px #04000D;">
          
          <!-- CELL 1: ROADSHOW INKLUSIF -->
          <div class="bg-[#D6FF00] p-6 sm:p-8 md:p-10 flex flex-col justify-between min-h-[320px] md:min-h-[360px] text-[#04000D] transition-all duration-200 hover:bg-[#d9ff1a]">
            <div>
              <div class="flex justify-between items-start mb-6">
                <span class="font-mono text-[10px] font-extrabold uppercase bg-[#04000D] text-[#D6FF00] px-2 py-0.5">
                  PHASE 01
                </span>
                <span class="font-mono text-[10px] font-bold text-[#04000D]/60">[PILAR-01]</span>
              </div>
              <h3 class="font-black uppercase text-xl sm:text-2xl md:text-3xl tracking-tight leading-none mb-4">
                01 / ROADSHOW INKLUSIF
              </h3>
              <p class="font-body text-sm md:text-base leading-relaxed opacity-95">
                Menggerakkan Digital Symphony Tour ke 25 titik krusial di Palu, Sigi, dan Donggala. Kami membawa modul literasi siber, AI, dan aksesibilitas teknologi inklusif langsung ke sekolah umum, SLB, dan desa terpencil bersama Main Strategic Partner kita, Hannah Asa Indonesia.
              </p>
            </div>
            <div class="mt-8 border-t border-[#04000D]/10 pt-4 flex justify-between items-center font-mono text-[9px] font-bold uppercase tracking-widest text-[#04000D]/75">
              <span>25 SITES</span>
              <span>INCLUSIVE TECH</span>
            </div>
          </div>

          <!-- CELL 2: INCUBATION ARENA -->
          <div class="bg-[#D86BFF] p-6 sm:p-8 md:p-10 flex flex-col justify-between min-h-[320px] md:min-h-[360px] text-[#04000D] transition-all duration-200 hover:bg-[#df85ff]">
            <div>
              <div class="flex justify-between items-start mb-6">
                <span class="font-mono text-[10px] font-extrabold uppercase bg-[#04000D] text-[#D86BFF] px-2 py-0.5">
                  PHASE 02
                </span>
                <span class="font-mono text-[10px] font-bold text-[#04000D]/60">[PILAR-02]</span>
              </div>
              <h3 class="font-black uppercase text-xl sm:text-2xl md:text-3xl tracking-tight leading-none mb-4">
                02 / INCUBATION ARENA
              </h3>
              <p class="font-body text-sm md:text-base leading-relaxed opacity-95">
                Wadah pembuktian inovasi skala nasional melalui UI/UX Design, Competitive Programming, dan Business Plan. Diperkuat dengan Sulteng Digital Innovation Hub (S-DIH) Hackathon murni untuk melahirkan solusi Agri-Tech dan Fin-Tech berbasis kearifan lokal.
              </p>
            </div>
            <div class="mt-8 border-t border-[#04000D]/10 pt-4 flex justify-between items-center font-mono text-[9px] font-bold uppercase tracking-widest text-[#04000D]/75">
              <span>ARENA INCUBATION</span>
              <span>S-DIH HACKATHON</span>
            </div>
          </div>

          <!-- CELL 3: GRAND CLOSING CONCERT -->
          <div class="bg-[#8839FF] p-6 sm:p-8 md:p-10 flex flex-col justify-between min-h-[320px] md:min-h-[360px] text-white transition-all duration-200 hover:bg-[#9752ff]">
            <div>
              <div class="flex justify-between items-start mb-6">
                <span class="font-mono text-[10px] font-extrabold uppercase bg-[#D6FF00] text-[#04000D] px-2 py-0.5">
                  PHASE 03
                </span>
                <span class="font-mono text-[10px] font-bold text-white/60">[PILAR-03]</span>
              </div>
              <h3 class="font-black uppercase text-xl sm:text-2xl md:text-3xl tracking-tight leading-none mb-4 text-[#D6FF00] riso-text-shadow-magenta riso-bleed">
                03 / GRAND CLOSING CONCERT
              </h3>
              <p class="font-body text-sm md:text-base leading-relaxed text-[#F5F5F5]/90">
                Malam puncak perayaan orkestrasi digital selama 3 hari penuh. Menggabungkan etalase produk kreatif di Expo UMKM, panggung inspirasi TEDx Talks, dan ditutup dengan konser mahakarya masif bersama solois terbaik nasional, TULUS, didukung oleh Strategic Partner produksi kita, Sultan Music.
              </p>
            </div>
            <div class="mt-8 border-t border-white/10 pt-4 flex justify-between items-center font-mono text-[9px] font-bold uppercase tracking-widest text-[#D6FF00]/95">
              <span>EXPO &amp; TEDX</span>
              <span>TULUS SHOWCASE</span>
            </div>
          </div>

        </div>
      </div>
    </section>


    <!-- SECTION K: KEY NUMBERS (The Impact Dashboard) -->
    <section id="impact-dashboard" class="bg-[#F5F5F5] riso-canvas py-12 md:py-16 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 animate-fade-in relative overflow-hidden" data-reveal>
      <!-- Background Decorative Stamp Shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'ry6 1.png')" 
        alt="Decorative Riso Plate Shard" 
        class="absolute -top-6 -left-12 w-28 md:w-44 opacity-20 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />

      <div class="max-w-container-max mx-auto relative z-10">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8 select-none">
          
          <!-- Stat 1 -->
          <div class="relative group">
            <div class="absolute inset-0 bg-[#04000D] translate-x-[2px] translate-y-[1.5px] border border-[#04000D] z-0"></div>
            <div class="relative bg-[#F5F5F5] border border-dashed border-[#04000D] p-5 sm:p-6 z-10 flex flex-col justify-between min-h-[140px]">
              <span class="font-mono text-[9px] sm:text-[10px] uppercase tracking-wider text-[#04000D]/60 font-bold">ESTIMASI TARGET PARTISIPAN</span>
              <div>
                <h3 class="text-3xl sm:text-5xl font-bold tracking-tighter text-[#04000D] leading-none mb-2 font-headline-lg riso-bleed tabular-nums">{{ formattedPartisipan }}</h3>
                <p class="font-mono text-[9px] sm:text-[10px] tracking-widest text-[#04000D]/80 font-bold uppercase leading-none">TARGET PARTICIPANTS</p>
              </div>
            </div>
          </div>

          <!-- Stat 2 -->
          <div class="relative group">
            <div class="absolute inset-0 bg-[#D6FF00] translate-x-[2px] translate-y-[1.5px] border border-[#04000D] z-0"></div>
            <div class="relative bg-[#F5F5F5] border border-dashed border-[#04000D] p-5 sm:p-6 z-10 flex flex-col justify-between min-h-[140px]">
              <span class="font-mono text-[9px] sm:text-[10px] uppercase tracking-wider text-[#04000D]/60 font-bold">TITIK ROADSHOW INKLUSIF</span>
              <div>
                <h3 class="text-3xl sm:text-5xl font-bold tracking-tighter text-[#04000D] leading-none mb-2 font-headline-lg riso-bleed tabular-nums">{{ formattedRoadshow }}</h3>
                <p class="font-mono text-[9px] sm:text-[10px] tracking-widest text-[#04000D]/80 font-bold uppercase leading-none">REGIONAL ROADSHOWS</p>
              </div>
            </div>
          </div>

          <!-- Stat 3 -->
          <div class="relative group">
            <div class="absolute inset-0 bg-[#04000D] translate-x-[2px] translate-y-[1.5px] border border-[#04000D] z-0"></div>
            <div class="relative bg-[#F5F5F5] border border-dashed border-[#04000D] p-5 sm:p-6 z-10 flex flex-col justify-between min-h-[140px]">
              <span class="font-mono text-[9px] sm:text-[10px] uppercase tracking-wider text-[#04000D]/60 font-bold">ESTIMASI TALENTA DIGITAL</span>
              <div>
                <h3 class="text-3xl sm:text-5xl font-bold tracking-tighter text-[#04000D] leading-none mb-2 font-headline-lg riso-bleed tabular-nums">{{ formattedTalent }}</h3>
                <p class="font-mono text-[9px] sm:text-[10px] tracking-widest text-[#04000D]/80 font-bold uppercase leading-none">DIGITAL TALENTS INCUBATED</p>
              </div>
            </div>
          </div>

          <!-- Stat 4 -->
          <div class="relative group">
            <div class="absolute inset-0 bg-[#D6FF00] translate-x-[2px] translate-y-[1.5px] border border-[#04000D] z-0"></div>
            <div class="relative bg-[#F5F5F5] border border-dashed border-[#04000D] p-5 sm:p-6 z-10 flex flex-col justify-between min-h-[140px]">
              <span class="font-mono text-[9px] sm:text-[10px] uppercase tracking-wider text-[#04000D]/60 font-bold">ESTIMASI MEDIA EXPOSURE</span>
              <div>
                <h3 class="text-3xl sm:text-5xl font-bold tracking-tighter text-[#04000D] leading-none mb-2 font-headline-lg riso-bleed tabular-nums">{{ formattedExposure }}</h3>
                <p class="font-mono text-[9px] sm:text-[10px] tracking-widest text-[#04000D]/80 font-bold uppercase leading-none">ESTIMATED MEDIA EXPOSURE</p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- SECTION L: TIMELINE KEGIATAN (The Print Ticker) -->
    <section class="bg-[#F5F5F5] riso-canvas py-12 md:py-16 px-0 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 animate-fade-in relative overflow-hidden" data-reveal>
      <!-- Background Decorative Stamp Shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'sb2 1.png')" 
        alt="Decorative Riso Plate Shard" 
        class="absolute -bottom-12 -right-16 w-36 md:w-56 opacity-25 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />

      <div class="max-w-container-max mx-auto sm:px-4 lg:px-0 relative z-10">
        <div class="bg-[#D6FF00] border-y-2 sm:border-2 border-[#04000D] rounded-none sm:rounded-[32px] p-6 sm:p-8 md:p-12 relative overflow-hidden select-none mx-0 sm:mx-4 lg:mx-0">
          <!-- Raw noise and dot grain filter inside lime block -->
          <div class="absolute inset-0 bg-[radial-gradient(#04000D_1px,transparent_1px)] [background-size:16px_16px] opacity-[0.05] pointer-events-none z-0"></div>
          <div class="absolute inset-0 bg-noise-grain opacity-[0.03] pointer-events-none z-0"></div>
          
          <div class="relative z-10">
            <!-- Timeline Title -->
            <div class="mb-10 text-center lg:text-left">
              <span class="font-mono text-xs uppercase tracking-widest font-bold text-[#04000D]/60">ROAD TO DIGITAL SYMPHONY</span>
              <h2 class="font-bold text-3xl md:text-4xl tracking-tighter text-[#04000D] mt-1 riso-bleed">Timeline Kegiatan</h2>
            </div>
            
            <!-- Timeline track -->
            <div class="relative flex flex-col lg:flex-row gap-8 lg:gap-12 mt-12">
              
              <!-- Connection Line -->
              <!-- Desktop: horizontal dashed line -->
              <div 
                v-motion
                :initial="{ opacity: 0 }"
                :visible-once="{ 
                  opacity: 1, 
                  transition: { 
                    duration: 600, 
                    delay: 100 
                  } 
                }"
                class="hidden lg:block absolute top-[28px] left-[5%] right-[5%] h-0.5 border-t-2 border-dashed border-[#04000D]/30 z-0"
              ></div>
              <!-- Mobile: vertical dashed line -->
              <div 
                v-motion
                :initial="{ opacity: 0 }"
                :visible-once="{ 
                  opacity: 1, 
                  transition: { 
                    duration: 600, 
                    delay: 100 
                  } 
                }"
                class="lg:hidden absolute top-[40px] bottom-[40px] left-[27px] w-0.5 border-l-2 border-dashed border-[#04000D]/30 z-0"
              ></div>

              <!-- Step 1 -->
              <div 
                v-motion
                :initial="{ opacity: 0, scale: 1.1, rotate: 0 }"
                :visible-once="{ 
                  opacity: 1, 
                  scale: 1,
                  rotate: isMobile ? -0.5 : -1.5,
                  transition: { 
                    type: 'spring', 
                    stiffness: 120, 
                    damping: 14, 
                    mass: 1.2,
                    delay: 0 
                  } 
                }"
                class="relative flex lg:flex-col items-start gap-6 lg:gap-4 lg:flex-1 z-10 group origin-center"
              >
                <!-- Bullet Circle -->
                <div class="w-14 h-14 rounded-full bg-[#F5F5F5] border-2 border-[#04000D] flex items-center justify-center font-mono text-sm font-bold text-[#04000D] shrink-0 z-10 transition-transform duration-200">
                  01
                </div>
                <div class="flex-1 lg:mt-2">
                  <span class="inline-block bg-[#04000D] text-[#D6FF00] px-2 py-0.5 font-mono text-[10px] font-bold tracking-widest uppercase mb-1">JUNI 2026</span>
                  <h4 class="font-bold text-lg text-[#04000D] tracking-tight leading-tight mt-1">KICK-OFF &amp; ROADSHOW</h4>
                  <p class="text-sm text-[#04000D]/80 leading-relaxed mt-2 max-w-sm">
                    Kick-off Phase &amp; 25-Titik Regional Roadshow Launch.
                  </p>
                </div>
              </div>

              <!-- Step 2 -->
              <div 
                v-motion
                :initial="{ opacity: 0, scale: 1.1, rotate: 0 }"
                :visible-once="{ 
                  opacity: 1, 
                  scale: 1,
                  rotate: isMobile ? 0.5 : 1,
                  transition: { 
                    type: 'spring', 
                    stiffness: 120, 
                    damping: 14, 
                    mass: 1.2,
                    delay: 200 
                  } 
                }"
                class="relative flex lg:flex-col items-start gap-6 lg:gap-4 lg:flex-1 z-10 group origin-center"
              >
                <!-- Bullet Circle -->
                <div class="w-14 h-14 rounded-full bg-[#F5F5F5] border-2 border-[#04000D] flex items-center justify-center font-mono text-sm font-bold text-[#04000D] shrink-0 z-10 transition-transform duration-200">
                  02
                </div>
                <div class="flex-1 lg:mt-2">
                  <span class="inline-block bg-[#04000D] text-[#D6FF00] px-2 py-0.5 font-mono text-[10px] font-bold tracking-widest uppercase mb-1">JULI 2026</span>
                  <h4 class="font-bold text-lg text-[#04000D] tracking-tight leading-tight mt-1">NATIONAL COMPETITION</h4>
                  <p class="text-sm text-[#04000D]/80 leading-relaxed mt-2 max-w-sm">
                    National Digital Competition Opening (UI/UX, CP, Business Plan).
                  </p>
                </div>
              </div>

              <!-- Step 3 -->
              <div 
                v-motion
                :initial="{ opacity: 0, scale: 1.1, rotate: 0 }"
                :visible-once="{ 
                  opacity: 1, 
                  scale: 1,
                  rotate: isMobile ? -0.5 : -0.5,
                  transition: { 
                    type: 'spring', 
                    stiffness: 120, 
                    damping: 14, 
                    mass: 1.2,
                    delay: 400 
                  } 
                }"
                class="relative flex lg:flex-col items-start gap-6 lg:gap-4 lg:flex-1 z-10 group origin-center"
              >
                <!-- Bullet Circle -->
                <div class="w-14 h-14 rounded-full bg-[#F5F5F5] border-2 border-[#04000D] flex items-center justify-center font-mono text-sm font-bold text-[#04000D] shrink-0 z-10 transition-transform duration-200">
                  03
                </div>
                <div class="flex-1 lg:mt-2">
                  <span class="inline-block bg-[#04000D] text-[#D6FF00] px-2 py-0.5 font-mono text-[10px] font-bold tracking-widest uppercase mb-1">AGUSTUS 2026</span>
                  <h4 class="font-bold text-lg text-[#04000D] tracking-tight leading-tight mt-1">REGIONAL INCUBATION</h4>
                  <p class="text-sm text-[#04000D]/80 leading-relaxed mt-2 max-w-sm">
                    S-DIH Hackathon Regional Incubation &amp; Java Tech Visitation Planning.
                  </p>
                </div>
              </div>

              <!-- Step 4 -->
              <div 
                v-motion
                :initial="{ opacity: 0, scale: 1.1, rotate: 0 }"
                :visible-once="{ 
                  opacity: 1, 
                  scale: 1,
                  rotate: isMobile ? 0.5 : 2,
                  transition: { 
                    type: 'spring', 
                    stiffness: 120, 
                    damping: 14, 
                    mass: 1.2,
                    delay: 600 
                  } 
                }"
                class="relative flex lg:flex-col items-start gap-6 lg:gap-4 lg:flex-1 z-10 group origin-center"
              >
                <!-- Bullet Circle -->
                <div class="w-14 h-14 rounded-full bg-[#F5F5F5] border-2 border-[#04000D] flex items-center justify-center font-mono text-sm font-bold text-[#04000D] shrink-0 z-10 transition-transform duration-200">
                  04
                </div>
                <div class="flex-1 lg:mt-2">
                  <span class="inline-block bg-[#04000D] text-[#D6FF00] px-2 py-0.5 font-mono text-[10px] font-bold tracking-widest uppercase mb-1">SEPTEMBER 2026</span>
                  <h4 class="font-bold text-lg text-[#04000D] tracking-tight leading-tight mt-1">GRAND PEAK EVENT</h4>
                  <p class="text-sm text-[#04000D]/80 leading-relaxed mt-2 max-w-sm">
                    Expo Inovasi UMKM, TEDx Talks, Grand closing Concert with TULUS.
                  </p>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION N: DETAIL KEGIATAN (The Zine Index) -->
    <section id="detail-kegiatan" class="bg-off-white riso-canvas py-16 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 animate-fade-in relative overflow-hidden" data-reveal>
      <!-- Background Decorative Stamp Shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'ry5 1.png')" 
        alt="Decorative Riso Plate Shard" 
        class="absolute top-36 -right-24 w-48 md:w-80 opacity-20 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />

      <div class="max-w-container-max mx-auto relative z-10">
        
        <!-- Section Header -->
        <div class="mb-12 text-center md:text-left select-none">
          <span class="font-mono text-xs uppercase tracking-widest font-extrabold text-[#04000D]/60">EXPLORE PROPOSAL DETAILS</span>
          <h2 class="font-bold text-3xl md:text-4xl tracking-tighter text-[#04000D] mt-1 riso-bleed">Detail Kegiatan Index</h2>
        </div>

        <!-- Accordion Rows Container -->
        <div class="border border-[#04000D]/20 divide-y divide-[#04000D]/20 bg-white">
          
          <!-- Accordion Row 1: Persiapan Panitia -->
          <div class="transition-all duration-200">
            <button 
              @click="activeZineIndex = activeZineIndex === 0 ? -1 : 0"
              class="w-full text-left py-6 px-6 sm:px-8 flex items-start sm:items-center justify-between font-bold text-[#04000D] hover:bg-[#D6FF00]/5 transition-colors focus:outline-none select-none gap-4"
              :class="activeZineIndex === 0 ? 'bg-[#D6FF00]/5' : ''"
            >
              <div class="flex items-start sm:items-center gap-4 sm:gap-6 flex-1 min-w-0">
                <span class="font-mono text-base text-[#04000D]/60 shrink-0">01/</span>
                <span class="text-lg sm:text-xl tracking-tight uppercase break-words whitespace-normal text-left">PERSIAPAN PANITIA</span>
              </div>
              <span class="shrink-0 font-mono text-xl sm:text-2xl transition-transform duration-200" :class="activeZineIndex === 0 ? 'rotate-45' : ''">+</span>
            </button>
            
            <div 
              v-show="activeZineIndex === 0" 
              class="px-6 sm:px-8 pb-8 pt-2 border-t border-dashed border-[#04000D]/10 bg-off-white/25"
            >
              <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                <div class="md:col-span-4">
                  <span class="font-mono text-[10px] uppercase tracking-widest font-bold text-[#04000D]/60 block mb-2">Milestone Checkpoints</span>
                  <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Pleno General Evaluation Updates
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> 10-Divisional KPI Enforcement
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Top Management Anti-Burnout Schedules
                    </div>
                  </div>
                </div>
                <div class="md:col-span-8 font-body-md text-sm sm:text-base text-[#04000D]/80 leading-relaxed">
                  <p class="mb-4">
                    Memastikan kesiapan internal yang prima melalui evaluasi terstruktur dalam rapat Pleno umum berkala. Seluruh 10 divisi kepanitiaan diawasi ketat lewat metrik KPI yang jelas, didukung dengan pengelolaan jadwal anti-burnout yang dinamis demi menjaga produktivitas dan kesejahteraan mental tim BPH.
                  </p>
                  <p class="font-mono text-xs text-[#04000D]/60 italic">
                    ✦ Penanggung Jawab: Ketua Panitia &amp; BPH Steering Committee ✦
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Accordion Row 2: Roadshow Inklusif -->
          <div class="transition-all duration-200">
            <button 
              @click="activeZineIndex = activeZineIndex === 1 ? -1 : 1"
              class="w-full text-left py-6 px-6 sm:px-8 flex items-start sm:items-center justify-between font-bold text-[#04000D] hover:bg-[#D6FF00]/5 transition-colors focus:outline-none select-none gap-4"
              :class="activeZineIndex === 1 ? 'bg-[#D6FF00]/5' : ''"
            >
              <div class="flex items-start sm:items-center gap-4 sm:gap-6 flex-1 min-w-0">
                <span class="font-mono text-base text-[#04000D]/60 shrink-0">02/</span>
                <span class="text-lg sm:text-xl tracking-tight uppercase break-words whitespace-normal text-left">ROADSHOW INKLUSIF</span>
              </div>
              <span class="shrink-0 font-mono text-xl sm:text-2xl transition-transform duration-200" :class="activeZineIndex === 1 ? 'rotate-45' : ''">+</span>
            </button>
            
            <div 
              v-show="activeZineIndex === 1" 
              class="px-6 sm:px-8 pb-8 pt-2 border-t border-dashed border-[#04000D]/10 bg-off-white/25"
            >
              <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                <div class="md:col-span-4">
                  <span class="font-mono text-[10px] uppercase tracking-widest font-bold text-[#04000D]/60 block mb-2">Regional Expansion</span>
                  <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> 25-Titik Journey: Palu, Sigi &amp; Donggala
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Special Needs Schools (SLB) Collaboration
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> AI &amp; Cyber Literacy Modules with Hannah Asa
                    </div>
                  </div>
                </div>
                <div class="md:col-span-8 font-body-md text-sm sm:text-base text-[#04000D]/80 leading-relaxed">
                  <p class="mb-4">
                    Menjangkau 25 titik sekolah menengah dan Sekolah Luar Biasa (SLB) di wilayah Palu, Sigi, dan Donggala. Kami membagikan modul literasi kecerdasan buatan (AI) dan keamanan siber yang dirancang khusus bersama Hannah Asa Indonesia agar dapat dipelajari secara inklusif oleh semua siswa.
                  </p>
                  <p class="font-mono text-xs text-[#04000D]/60 italic">
                    ✦ Partner Strategis: Hannah Asa Indonesia &amp; Relawan HMTI ✦
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Accordion Row 3: Arena Lomba -->
          <div class="transition-all duration-200">
            <button 
              @click="activeZineIndex = activeZineIndex === 2 ? -1 : 2"
              class="w-full text-left py-6 px-6 sm:px-8 flex items-start sm:items-center justify-between font-bold text-[#04000D] hover:bg-[#D6FF00]/5 transition-colors focus:outline-none select-none gap-4"
              :class="activeZineIndex === 2 ? 'bg-[#D6FF00]/5' : ''"
            >
              <div class="flex items-start sm:items-center gap-4 sm:gap-6 flex-1 min-w-0 flex-wrap sm:flex-nowrap">
                <span class="font-mono text-base text-[#04000D]/60 shrink-0">03/</span>
                <span class="text-lg sm:text-xl tracking-tight uppercase break-words whitespace-normal text-left flex flex-wrap items-center gap-2 sm:gap-3">
                  ARENA KOMPETISI
                  <span class="inline-block bg-[#04000D] text-[#D6FF00] font-mono text-[9px] font-extrabold px-2 py-0.5 rounded-none leading-none select-none tracking-widest border border-[#04000D] shrink-0">COMING SOON</span>
                </span>
              </div>
              <span class="shrink-0 font-mono text-xl sm:text-2xl transition-transform duration-200" :class="activeZineIndex === 2 ? 'rotate-45' : ''">+</span>
            </button>
            
            <div 
              v-show="activeZineIndex === 2" 
              class="px-6 sm:px-8 pb-8 pt-2 border-t border-dashed border-[#04000D]/10 bg-off-white/25"
            >
              <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                <div class="md:col-span-4">
                  <span class="font-mono text-[10px] uppercase tracking-widest font-bold text-[#04000D]/60 block mb-2">Competition Categories</span>
                  <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> National UI/UX Design Competition
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Competitive Programming Arena
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Tech-Driven Business Plan Challenge
                    </div>
                  </div>
                </div>
                <div class="md:col-span-8 font-body-md text-sm sm:text-base text-[#04000D]/80 leading-relaxed">
                  <p class="mb-4">
                    Perlombaan tingkat nasional yang menantang kreativitas dan ketajaman teknis mahasiswa seluruh Indonesia. Menghadirkan tiga kategori utama: desain UI/UX inovatif, kompetisi pemrograman kompetitif yang intensif, serta perancangan proposal rencana bisnis berbasis teknologi pintar.
                  </p>
                  <p class="font-mono text-xs text-[#04000D]/60 italic">
                    ✦ Total Hadiah Ratusan Juta Rupiah &amp; Sertifikat Nasional ✦
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Accordion Row 4: Visitasi Industri -->
          <div class="transition-all duration-200">
            <button 
              @click="activeZineIndex = activeZineIndex === 3 ? -1 : 3"
              class="w-full text-left py-6 px-6 sm:px-8 flex items-start sm:items-center justify-between font-bold text-[#04000D] hover:bg-[#D6FF00]/5 transition-colors focus:outline-none select-none gap-4"
              :class="activeZineIndex === 3 ? 'bg-[#D6FF00]/5' : ''"
            >
              <div class="flex items-start sm:items-center gap-4 sm:gap-6 flex-1 min-w-0">
                <span class="font-mono text-base text-[#04000D]/60 shrink-0">04/</span>
                <span class="text-lg sm:text-xl tracking-tight uppercase break-words whitespace-normal text-left flex flex-wrap items-center gap-2 sm:gap-3">
                  VISITASI INDUSTRI
                  <span class="inline-block bg-[#04000D] text-[#D6FF00] font-mono text-[9px] font-extrabold px-2 py-0.5 rounded-none leading-none select-none tracking-widest border border-[#04000D] shrink-0">COMING SOON</span>
                </span>
              </div>
              <span class="shrink-0 font-mono text-xl sm:text-2xl transition-transform duration-200" :class="activeZineIndex === 3 ? 'rotate-45' : ''">+</span>
            </button>
            
            <div 
              v-show="activeZineIndex === 3" 
              class="px-6 sm:px-8 pb-8 pt-2 border-t border-dashed border-[#04000D]/10 bg-off-white/25"
            >
              <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                <div class="md:col-span-4">
                  <span class="font-mono text-[10px] uppercase tracking-widest font-bold text-[#04000D]/60 block mb-2">Visitation Target</span>
                  <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> 20+ Tech Corporate Hubs in Java
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Google, TikTok &amp; Telkom Visitations
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Committee Performance Reward Program
                    </div>
                  </div>
                </div>
                <div class="md:col-span-8 font-body-md text-sm sm:text-base text-[#04000D]/80 leading-relaxed">
                  <p class="mb-4">
                    Mempersiapkan kunjungan industri eksklusif ke 20+ korporasi teknologi papan atas di Pulau Jawa (seperti Google, TikTok, dan Telkom). Program visitasi ini dirancang khusus sebagai apresiasi prestasi bagi anggota panitia dengan kinerja luar biasa selama persiapan I-FEST 2026.
                  </p>
                  <p class="font-mono text-xs text-[#04000D]/60 italic">
                    ✦ Delegasi Terpilih HMTI Universitas Tadulako ✦
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Accordion Row 5: TEDx & Malam Puncak -->
          <div class="transition-all duration-200">
            <button 
              @click="activeZineIndex = activeZineIndex === 4 ? -1 : 4"
              class="w-full text-left py-6 px-6 sm:px-8 flex items-start sm:items-center justify-between font-bold text-[#04000D] hover:bg-[#D6FF00]/5 transition-colors focus:outline-none select-none gap-4"
              :class="activeZineIndex === 4 ? 'bg-[#D6FF00]/5' : ''"
            >
              <div class="flex items-start sm:items-center gap-4 sm:gap-6 flex-1 min-w-0 flex-wrap sm:flex-nowrap">
                <span class="font-mono text-base text-[#04000D]/60 shrink-0">05/</span>
                <span class="text-lg sm:text-xl tracking-tight uppercase break-words whitespace-normal text-left flex flex-wrap items-center gap-2 sm:gap-3">
                  TEDx &amp; MALAM PUNCAK
                  <span class="inline-block bg-[#04000D] text-[#D6FF00] font-mono text-[9px] font-extrabold px-2 py-0.5 rounded-none leading-none select-none tracking-widest border border-[#04000D] shrink-0">COMING SOON</span>
                </span>
              </div>
              <span class="shrink-0 font-mono text-xl sm:text-2xl transition-transform duration-200" :class="activeZineIndex === 4 ? 'rotate-45' : ''">+</span>
            </button>
            
            <div 
              v-show="activeZineIndex === 4" 
              class="px-6 sm:px-8 pb-8 pt-2 border-t border-dashed border-[#04000D]/10 bg-off-white/25"
            >
              <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                <div class="md:col-span-4">
                  <span class="font-mono text-[10px] uppercase tracking-widest font-bold text-[#04000D]/60 block mb-2">Grand Orchestration</span>
                  <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> 3-Days Innovation Expo: 30+ Booths
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Curated TEDx Stages &amp; Local Speakers
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Grand Closing Concert with TULUS
                    </div>
                  </div>
                </div>
                <div class="md:col-span-8 font-body-md text-sm sm:text-base text-[#04000D]/80 leading-relaxed">
                  <p class="mb-4">
                    Malam puncak termegah yang memadukan pameran inovasi digital UMKM selama tiga hari penuh, konferensi inspiratif berlisensi TEDx, dan konser penutup spektakuler bersama musisi nasional ternama, TULUS, dibalut dengan visual projection mapping bertema Simfoni Digital.
                  </p>
                  <div class="inline-flex items-center border-2 border-dashed border-[#04000D] p-2 bg-transparent gap-3 select-none">
                    <span class="font-mono text-xs text-[#04000D] font-extrabold uppercase tracking-widest">COMING SOON PRINT STAMP: [ TULUS - LIVE CONCERT ]</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </section>

    <!-- SECTION O: GALERI JEJAK LANGKAH (I-FEST 2025 Historical Archive) -->
    <section id="galeri-jejak-langkah" class="bg-[#8839FF] py-16 sm:py-24 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#F5F5F5]/20 relative overflow-hidden select-none" data-reveal>
      <!-- Coarse dots and noise overlays specifically for dark canvas -->
      <div class="absolute inset-0 bg-[radial-gradient(#F5F5F5_1px,transparent_1px)] [background-size:20px_20px] opacity-[0.03] pointer-events-none z-0"></div>
      <div class="absolute inset-0 bg-noise-grain opacity-[0.02] pointer-events-none z-0"></div>

      <!-- Background Decorative Stamp Shards (Yellow on Purple contrast) -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'sy5 1.png')" 
        alt="Decorative Riso Plate Shard" 
        class="absolute -bottom-8 -right-16 w-36 md:w-56 opacity-35 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />

      <div class="max-w-container-max mx-auto relative z-10">
        
        <!-- Section Header -->
        <div class="mb-12 text-center md:text-left select-none">
          <span class="font-mono text-xs uppercase tracking-widest font-extrabold text-[#D6FF00]">HISTORICAL DOSSIER</span>
          <h2 class="font-bold text-3xl md:text-4xl tracking-tighter text-[#F5F5F5] mt-1 riso-bleed">Arsip Resonansi 2025.</h2>
        </div>

        <!-- Grid of Grainy Analog Photocopy Images -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 items-stretch">
          
          <div 
            v-for="(item, index) in galleryImages" 
            :key="index"
            class="bg-transparent border border-[#F5F5F5]/30 p-4 rounded-none flex flex-col justify-between hover:border-[#D6FF00] transition-colors duration-300 select-none"
            :class="[index === 3 ? 'lg:col-start-2' : '']"
          >
            <!-- Photocopy-style high-contrast print filter container -->
            <div class="relative w-full aspect-[4/3] rounded-none overflow-hidden bg-transparent border border-[#F5F5F5]/20 mb-4 group pointer-events-auto">
              <img 
                :src="item.src" 
                :alt="item.alt" 
                class="w-full h-full object-cover grayscale mix-blend-multiply contrast-125 transition-all duration-300 group-hover:scale-105"
              />
            </div>
            
            <div class="flex flex-col gap-1">
              <span class="font-mono text-[9px] uppercase tracking-widest text-[#D6FF00] font-bold">{{ item.title }}</span>
              <h4 class="font-bold text-base text-[#F5F5F5] tracking-tight leading-snug mt-1">{{ item.description }}</h4>
            </div>
          </div>

        </div>

      </div>
    </section>

    <!-- SECTION M: KONTRIBUSI SYMPHONY (QRIS Treasurer & Saweria Donation) -->
    <section class="bg-[#F5F5F5] riso-canvas py-12 md:py-16 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 animate-fade-in relative overflow-hidden" data-reveal>
      <!-- Background Decorative Stamp Shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'sg1 1.png')" 
        alt="Decorative Riso Plate Shard" 
        class="absolute -top-12 -left-16 w-32 md:w-52 opacity-20 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />

      <div class="max-w-container-max mx-auto relative z-10">
        <div class="mb-10 text-center md:text-left">
          <span class="font-mono text-xs uppercase tracking-widest font-bold text-[#04000D]/60">PARTISIPASI &amp; DUKUNGAN</span>
          <h2 class="font-bold text-3xl md:text-4xl tracking-tighter text-[#04000D] mt-1 riso-bleed">Kontribusi Symphony</h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-stretch">
          
          <!-- Left Column: The Stamped QRIS (Even width on large screens) -->
          <div class="flex flex-col justify-between p-6 sm:p-8 bg-white border border-[#04000D] rounded-none select-none">
            <div class="flex flex-col items-center">
              <span class="font-mono text-[9px] uppercase tracking-widest text-[#04000D]/40 mb-4">REGISTRATION STAMP: QR-02</span>
              
              <!-- QR Code Stylized Vector Stamp -->
              <div class="relative w-48 h-48 border border-[#04000D] p-3 bg-white flex items-center justify-center shadow-none mb-6">
                <!-- Stylized QRIS Stamp Overlay in Multiply mode -->
                <div class="absolute inset-0 bg-[#D6FF00]/5 mix-blend-multiply pointer-events-none"></div>
                <svg viewBox="0 0 100 100" class="w-full h-full text-[#04000D]" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                  <!-- QR Patterns -->
                  <rect x="0" y="0" width="25" height="25" />
                  <rect x="3" y="3" width="19" height="19" fill="white" />
                  <rect x="7" y="7" width="11" height="11" />
                  
                  <rect x="75" y="0" width="25" height="25" />
                  <rect x="78" y="3" width="19" height="19" fill="white" />
                  <rect x="82" y="7" width="11" height="11" />
                  
                  <rect x="0" y="75" width="25" height="25" />
                  <rect x="3" y="75" width="19" height="19" fill="white" />
                  <rect x="7" y="79" width="11" height="11" />
                  
                  <!-- Random QR Matrix Blocks -->
                  <rect x="30" y="5" width="5" height="5" />
                  <rect x="40" y="10" width="10" height="5" />
                  <rect x="55" y="0" width="5" height="15" />
                  <rect x="65" y="5" width="5" height="5" />
                  
                  <rect x="30" y="30" width="15" height="5" />
                  <rect x="35" y="40" width="5" height="10" />
                  <rect x="50" y="30" width="5" height="5" />
                  <rect x="60" y="35" width="10" height="5" />
                  <rect x="75" y="30" width="5" height="15" />
                  <rect x="85" y="35" width="10" height="10" />
                  
                  <rect x="5" y="35" width="10" height="5" />
                  <rect x="15" y="45" width="5" height="10" />
                  <rect x="0" y="60" width="10" height="5" />
                  <rect x="20" y="65" width="5" height="5" />
                  
                  <rect x="35" y="60" width="5" height="15" />
                  <rect x="45" y="70" width="10" height="5" />
                  <rect x="60" y="60" width="5" height="5" />
                  <rect x="70" y="65" width="5" height="20" />
                  
                  <rect x="30" y="85" width="15" height="5" />
                  <rect x="40" y="90" width="5" height="5" />
                  <rect x="55" y="80" width="10" height="10" />
                  <rect x="60" y="95" width="15" height="5" />
                  <rect x="85" y="80" width="5" height="15" />
                  
                  <!-- Center Logo Badge -->
                  <rect x="40" y="40" width="20" height="20" fill="white" stroke="#04000D" stroke-width="2" />
                  <text x="50" y="52" font-family="'JetBrains Mono', monospace" font-size="8" font-weight="900" text-anchor="middle" fill="#04000D">IFEST</text>
                </svg>
              </div>
              <p class="font-mono text-xs uppercase tracking-widest text-[#04000D] font-bold text-center mb-2">SCAN QRIS / SAWERIA</p>
            </div>
            
            <div class="text-center mt-4">
              <p class="font-body-md text-base text-[#04000D] font-bold leading-snug">
                Dukung Orkestrasi Inovasi Sulawesi Tengah. Dana taktis difokuskan untuk subsidi akomodasi visitasi panitia ke Jawa.
              </p>
            </div>
          </div>

          <!-- Right Column: Top Donors Leaderboard (Even width on large screens) -->
          <div class="flex flex-col justify-between p-6 sm:p-8 bg-white border border-[#04000D] rounded-none">
            <div>
              <p class="font-mono text-[9px] uppercase tracking-widest text-[#04000D]/40 mb-6">LIVE DONOR LEADERBOARD (REALTIME)</p>
              
              <!-- Leaderboard List -->
              <div class="flex flex-col select-none">
                
                <!-- Row 1 -->
                <div class="flex items-center justify-between py-4 border-b border-[#04000D]/20 opacity-70 hover:opacity-100 transition-opacity duration-200">
                  <div class="flex items-center gap-4">
                    <span class="font-mono text-xs text-[#04000D]/60 font-bold">#01</span>
                    <span class="text-base text-[#04000D]" style="font-weight: 540;">Fauzi &amp; Rian (Sponsorship Team)</span>
                  </div>
                  <span class="font-mono text-base font-bold text-[#04000D]">Rp 5.000.000</span>
                </div>

                <!-- Row 2 -->
                <div class="flex items-center justify-between py-4 border-b border-[#04000D]/20 opacity-70 hover:opacity-100 transition-opacity duration-200">
                  <div class="flex items-center gap-4">
                    <span class="font-mono text-xs text-[#04000D]/60 font-bold">#02</span>
                    <span class="text-base text-[#04000D]" style="font-weight: 540;">HMTI Cabinet 2026</span>
                  </div>
                  <span class="font-mono text-base font-bold text-[#04000D]">Rp 3.500.000</span>
                </div>

                <!-- Row 3 -->
                <div class="flex items-center justify-between py-4 border-b border-[#04000D]/20 opacity-70 hover:opacity-100 transition-opacity duration-200">
                  <div class="flex items-center gap-4">
                    <span class="font-mono text-xs text-[#04000D]/60 font-bold">#03</span>
                    <span class="text-base text-[#04000D]" style="font-weight: 540;">Hannah Asa Dev Team</span>
                  </div>
                  <span class="font-mono text-base font-bold text-[#04000D]">Rp 2.500.000</span>
                </div>

                <!-- Row 4 -->
                <div class="flex items-center justify-between py-4 border-b border-[#04000D]/20 opacity-70 hover:opacity-100 transition-opacity duration-200">
                  <div class="flex items-center gap-4">
                    <span class="font-mono text-xs text-[#04000D]/60 font-bold">#04</span>
                    <span class="text-base text-[#04000D]" style="font-weight: 540;">Alumnus Tadulako 2018</span>
                  </div>
                  <span class="font-mono text-base font-bold text-[#04000D]">Rp 1.500.000</span>
                </div>

                <!-- Row 5 -->
                <div class="flex items-center justify-between py-4 border-b border-[#04000D]/20 opacity-70 hover:opacity-100 transition-opacity duration-200">
                  <div class="flex items-center gap-4">
                    <span class="font-mono text-xs text-[#04000D]/60 font-bold">#05</span>
                    <span class="text-base text-[#04000D]" style="font-weight: 540;">Digital Symphony Supporter</span>
                  </div>
                  <span class="font-mono text-base font-bold text-[#04000D]">Rp 750.000</span>
                </div>

              </div>
            </div>

            <div class="mt-8 pt-4 border-t border-dashed border-[#04000D]/10 flex flex-col sm:flex-row justify-between items-center gap-4 text-center sm:text-left select-none">
              <span class="text-xs text-[#04000D]/60 font-mono">✦ INK PRINTING NOISE SCREEN ACTIVE ✦</span>
              <span class="text-xs font-mono text-[#04000D]/60">Total Terkumpul: <span class="text-[#04000D] font-bold font-mono">Rp 13.250.000</span></span>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- SECTION BPH: Core Management BPH (Command Matrix) -->
    <section id="bph-matrix" class="bg-[#F5F5F5] riso-canvas py-16 md:py-24 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
      <div class="max-w-container-max mx-auto relative z-10">
        
        <!-- Section Header -->
        <div class="mb-16 md:mb-20">
          <p class="font-mono text-[#04000D] text-xs uppercase tracking-widest mb-4 font-bold">INTERNAL COMMAND MATRIX</p>
          <h2 class="font-black text-5xl md:text-7xl tracking-[-0.04em] leading-none text-[#04000D] riso-bleed">Struktur Orkestrasi.</h2>
        </div>

        <!-- Asymmetrical Organizational Grid -->
        <div class="flex flex-col md:grid md:grid-cols-3 gap-8 md:gap-8 pb-16 md:pb-24">
          
          <!-- CELL 1: The Leader Block -->
          <div 
            class="bg-[#D86BFF] border-[3px] border-[#04000D] rounded-none p-6 md:p-8 flex flex-col justify-between transition-opacity duration-150 hover:opacity-95 md:translate-y-0 relative z-30 select-none"
            style="box-shadow: 6px 6px 0px 0px #04000D !important;"
          >
            <div>
              <p class="font-mono text-xs uppercase tracking-wider text-[#04000D] font-bold">✦ PROJECT MANAGER ✦</p>
              <h3 class="font-black text-2xl sm:text-3xl tracking-tighter mt-2 leading-tight text-[#04000D] uppercase">NAKITA SEMESTA</h3>
              <p class="font-mono text-xs text-[#04000D]/80 mt-4 leading-relaxed">
                Mengawal eskalasi strategic partner, lobi eksternal, dan seluruh jalannya 10 divisi operasional.
              </p>
            </div>
          </div>

          <!-- CELL 2: The System Block -->
          <div 
            class="bg-[#D6FF00] border-[3px] border-[#04000D] rounded-none p-6 md:p-8 flex flex-col justify-between transition-opacity duration-150 hover:opacity-95 md:translate-y-6 relative z-20 select-none"
            style="box-shadow: 6px 6px 0px 0px #04000D !important;"
          >
            <div>
              <p class="font-mono text-xs uppercase tracking-wider text-[#04000D] font-bold">✦ SECRETARY ✦</p>
              <h3 class="font-black text-2xl sm:text-3xl tracking-tighter mt-2 leading-tight text-[#04000D] uppercase">RIZKA FILARDI TOLIZ</h3>
              <p class="font-mono text-xs text-[#04000D]/80 mt-4 leading-relaxed">
                Arsitek administrasi, standarisasi birokrasi legal, dan timeline checklist Pleno umum panitia.
              </p>
            </div>
          </div>

          <!-- CELL 3: The Engine Block -->
          <div 
            class="bg-[#8839FF] border-[3px] border-[#04000D] rounded-none p-6 md:p-8 flex flex-col justify-between transition-opacity duration-150 hover:opacity-95 md:translate-y-12 relative z-10 select-none"
            style="box-shadow: 6px 6px 0px 0px #04000D !important;"
          >
            <div>
              <p class="font-mono text-xs uppercase tracking-wider text-[#D6FF00] font-bold">✦ TREASURER ✦</p>
              <h3 class="font-black text-2xl sm:text-3xl tracking-tighter mt-2 leading-tight text-[#D6FF00] riso-text-shadow-magenta uppercase">DAREEAN AHMAD</h3>
              <p class="font-mono text-xs text-[#F5F5F5]/90 mt-4 leading-relaxed">
                Komandan finansial, manajemen dana taktis roadshow, alokasi subsidi visitasi Jawa, dan sistem leaderboard QRIS.
              </p>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- SECTION J: "OUR NETWORK" / SPONSOR HIERARCHY (Placed right above the Footer) -->
    <section id="partners" class="bg-white riso-canvas py-16 sm:py-24 px-4 sm:px-6 md:px-lg border-t border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
      <!-- Background Decorative Stamp Shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'cat3 1.png')" 
        alt="Decorative Riso Plate Shard" 
        class="absolute bottom-16 -right-16 w-36 md:w-56 opacity-20 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />

      <div class="max-w-container-max mx-auto relative z-10">
        
        <!-- Section Header -->
        <div class="mb-16">
          <p class="font-mono text-[#04000D] text-xs md:text-sm uppercase tracking-[0.25em] mb-4 font-bold">PARTNERSHIP HIERARCHY</p>
          <h2 class="font-bold text-2xl sm:text-3xl md:text-5xl tracking-tighter text-[#04000D] riso-text-shadow-magenta riso-bleed">Ekosistem Kolaborasi.</h2>
        </div>

        <!-- Stamped Organizer Logos -->
        <div class="mb-16">
          <p class="font-mono text-xs text-[#04000D]/70 uppercase tracking-widest mb-10 text-center md:text-left font-bold">✦ ORGANIZED BY ✦</p>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-12 items-center justify-items-center">
            <div class="opacity-70 hover:opacity-100 transition-opacity duration-200"><img alt="UNTAD Logo" class="max-h-16 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'logo_untad.png')" /></div>
            <div class="opacity-70 hover:opacity-100 transition-opacity duration-200"><img alt="HMTI Logo" class="max-h-16 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'HMTI LOGO.png')" /></div>
            <div class="opacity-70 hover:opacity-100 transition-opacity duration-200"><img alt="HMTI Cabinet Logo" class="max-h-16 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'all blue.png')" /></div>
            <div class="opacity-70 hover:opacity-100 transition-opacity duration-200"><img alt="I-FEST 2026 Logo" class="max-h-16 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'Logo-IFEST-2026.png')" /></div>
          </div>
        </div>

        <!-- HAIRLINE DIVIDER -->
        <div class="border-b border-[#04000D]/10 mb-16"></div>

        <!-- Stepped Flat Layout Sponsor Hierarchy Container -->
        <div class="border border-[#04000D]/20 divide-y divide-[#04000D]/20 bg-white animate-fade-in">
          
          <!-- TIER 1: MAIN STRATEGIC PARTNER -->
          <div class="p-6 sm:p-8 md:p-12 flex flex-col md:flex-row items-center gap-8 md:gap-16">
            <div class="flex-1">
              <p class="font-mono text-xs uppercase tracking-widest font-bold text-[#D86BFF]">✦ MAIN STRATEGIC PARTNER ✦</p>
              <h3 class="font-bold text-xl sm:text-2xl md:text-3xl text-[#04000D] mt-2 mb-4">{{ mainStrategicPartner.name }}</h3>
              <p class="font-body-md text-base md:text-lg text-[#04000D]/80 leading-relaxed max-w-xl">
                Official partner mengawal eskalasi lobi pendanaan Tier-1 dan kurikulum 25 Titik Roadshow Inklusif.
              </p>
            </div>
            <div class="w-full md:w-1/2 flex justify-center items-center opacity-70 hover:opacity-100 transition-opacity duration-200">
              <img :alt="mainStrategicPartner.name" class="w-full max-w-[360px] h-auto object-contain mix-blend-multiply filter contrast-125" :src="mainStrategicPartner.src" />
            </div>
          </div>

          <!-- TIER 2: STRATEGIC PARTNER -->
          <div class="p-6 sm:p-8 md:p-12">
            <div class="mb-8">
              <p class="font-mono text-xs uppercase tracking-widest font-bold text-[#8839FF]">✦ STRATEGIC PARTNER ✦</p>
              <h3 class="font-bold text-xl sm:text-2xl md:text-3xl text-[#04000D] mt-2">Strategic Alliance</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
              <div
                v-for="partner in strategicPartners"
                :key="partner.name"
                class="border border-[#04000D]/10 bg-off-white/40 p-5 md:p-6 rounded-lg flex flex-col items-center text-center gap-4 opacity-75 hover:opacity-100 transition-opacity duration-200"
              >
                <img
                  :alt="partner.name"
                  :class="partner.logoMaxWidth"
                  class="w-full h-14 md:h-16 object-contain mix-blend-multiply filter contrast-125"
                  :src="partner.src"
                />
                <h4 class="font-bold text-lg md:text-xl text-[#04000D]">{{ partner.name }}</h4>
                <p class="font-body-md text-sm md:text-base text-[#04000D]/75 leading-relaxed">{{ partner.description }}</p>
              </div>
            </div>
          </div>

          <!-- TIER 3: OFFICIAL MEDIA PARTNERS -->
          <div class="p-6 sm:p-8 md:p-12">
            <p class="font-mono text-[#04000D] text-xs uppercase tracking-widest font-bold mb-8">✦ OFFICIAL MEDIA PARTNERS ✦</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4 md:gap-8 items-stretch">
              <div
                v-for="partner in mediaPartners"
                :key="partner.name"
                class="border border-[#04000D]/10 p-3 md:p-4 flex items-center justify-center opacity-70 hover:opacity-100 transition-opacity duration-200 bg-off-white/30 min-h-[72px] md:min-h-[88px] rounded-lg"
              >
                <img :alt="partner.name" class="max-h-10 md:max-h-16 w-auto object-contain mx-auto filter contrast-125 grayscale" :src="partner.src" />
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- SECTION G: Footer Section (Deep Midnight Canvas) -->
    <footer class="w-full bg-[#04000D] text-[#F5F5F5] py-16 px-6 md:px-lg border-t border-dashed border-[#F5F5F5]/25 relative overflow-hidden select-none">
      <div class="max-w-container-max mx-auto relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-start">
          
          <div class="flex flex-col gap-8">
            <div class="flex flex-col gap-2">
              <span class="font-headline-lg text-3xl sm:text-4xl font-bold text-[#D6FF00] riso-text-shadow-double-dark riso-bleed">I-FEST 2026</span>
            </div>
            
            <!-- Bottom-Left Institutional Logo flex block -->
            <div class="flex flex-col gap-3">
              <span class="font-mono text-[10px] md:text-xs font-bold uppercase tracking-wider text-[#F5F5F5]/60">ORGANIZED BY HMTI UNIVERSITAS TADULAKO</span>
              <div class="flex flex-row flex-wrap items-center gap-4 md:gap-6">
                <img alt="UNTAD Logo" class="h-8 md:h-10 w-auto object-contain opacity-80 filter invert grayscale contrast-125 transition-all duration-300 hover:filter-none hover:opacity-100 cursor-pointer" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'logo_untad.png')" />
                <img alt="HMTI Logo" class="h-8 md:h-10 w-auto object-contain opacity-80 filter invert grayscale contrast-125 transition-all duration-300 hover:filter-none hover:opacity-100 cursor-pointer" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'HMTI LOGO.png')" />
                <img alt="HMTI Cabinet Logo" class="h-8 md:h-10 w-auto object-contain opacity-90 filter brightness-0 invert transition-all duration-300 hover:filter-none hover:opacity-100 cursor-pointer" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'all blue.png')" />
                <img alt="I-FEST Logo" class="h-8 md:h-10 w-auto object-contain opacity-90 filter brightness-0 invert transition-all duration-300 hover:filter-none hover:opacity-100 cursor-pointer" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'Logo-IFEST-2026.png')" />
              </div>
            </div>

            <div class="flex gap-6 mt-4">
              <a class="text-xs font-mono uppercase tracking-widest hover:text-[#D6FF00] transition-colors" href="#">Instagram</a>
              <a class="text-xs font-mono uppercase tracking-widest hover:text-[#D6FF00] transition-colors" href="#">RINOYA</a>
              <a class="text-xs font-mono uppercase tracking-widest hover:text-[#D6FF00] transition-colors" href="#">Contact</a>
            </div>
          </div>
          
          <div class="flex flex-col md:items-end md:text-right gap-4">
            <p class="font-body-md text-sm md:text-base opacity-80">Ingin menjadi bagian dari simfoni ini?</p>
            <p class="font-headline-lg text-lg md:text-xl font-medium">Sponsorship &amp; Kemitraan: <br class="md:hidden" /><span class="text-[#D6FF00]">Fauzi (+62 821-9543-2152)</span></p>
            
            <div class="font-mono text-sm tracking-wider mt-1">
              <span class="opacity-75">Instagram:</span>
              <a href="https://www.instagram.com/ifest_untad" target="_blank" rel="noopener noreferrer" class="ml-2 inline-block border border-[#F5F5F5] px-2.5 py-0.5 bg-[#F5F5F5] text-[#04000D] font-bold opacity-80 hover:opacity-100 transition-opacity duration-200">
                @fest_untad
              </a>
            </div>

            <p class="font-caption text-[10px] opacity-40 mt-8">© 2026 I-FEST. All rights reserved. Orchestrated with passion in Central Sulawesi.</p>
          </div>
          
        </div>
      </div>
    </footer>
    
  </div>
</template>