<script setup>
import { ref, computed, onBeforeUnmount, onMounted, defineAsyncComponent } from 'vue'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import { competitionsData } from '../data/competitionsData'

const AiChatWidget = defineAsyncComponent(() => import('../components/AiChatWidget.vue'))
const isChatActivated = ref(false)

const showContent = ref(true)
const isLoading = ref(false)
const isMenuOpen = ref(false)
const activeZineIndex = ref(0)
const activeTimelinePhase = ref(-1)


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

const visualAssetModules = import.meta.glob('../assets/visual_assets/*', {
  eager: true,
  import: 'default',
})

const mediaPartnerAssetModules = import.meta.glob('../assets/medpart/*', {
  eager: true,
  import: 'default',
})

const strategicPartnerAssetModules = import.meta.glob('../assets/sponsor-strategic_partner/*', {
  eager: true,
  import: 'default',
})

const mainLogoAssetModules = import.meta.glob('../assets/logo_utama/*', {
  eager: true,
  import: 'default',
})

const getAsset = (assetModules, folder, fileName) => assetModules[`../assets/${folder}/${fileName}`] ?? ''

const galleryImages = [
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'academic_workshop.webp'),
    alt: 'Tema Utama: Resonansi Digital',
    title: 'Tema Utama: Resonansi Digital',
    description: 'Capturing the 2025 spirit of youth collaboration impacting Central Sulawesi technology growth.'
  },
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'palu_dev_day.webp'),
    alt: '2.525 Penerima Manfaat',
    title: '2.525 Penerima Manfaat',
    description: 'Direct impact tracking over 13 High Schools visited and 1,082 participants crowding the Tadulako Main Auditorium.'
  },
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'smart_helmet.webp'),
    alt: '30 Produk Teknologi Lokal',
    title: '30 Produk Teknologi Lokal',
    description: 'Showcasing 30 native inventions across Mobile Apps, VR Megalith Showcases, IoT, and Web Development (Ternary AI).'
  },
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'tech_meetup.webp'),
    alt: '390.1K Instagram Reach',
    title: '390.1K Instagram Reach',
    description: 'Capturing our massive digital exposure footprint under the official @ifest_untad network during the campaign.'
  }
]

const heroDecorations = [
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'Component 1.webp'),
    alt: 'Abstract visual asset',
    className: 'absolute top-24 -left-12 md:-left-4 lg:left-6 w-48 md:w-80 opacity-75 md:opacity-85 mix-blend-multiply contrast-125 animate-float pointer-events-none z-0 filter',
    delay: '0s',
  },
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'rb1 1.webp'),
    alt: 'Abstract visual asset',
    className: 'absolute bottom-40 -right-12 md:right-6 w-56 md:w-96 opacity-75 md:opacity-85 mix-blend-multiply contrast-125 animate-float pointer-events-none z-0 filter',
    delay: '-2s',
  },
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'ry1 1.webp'),
    alt: 'Abstract visual asset',
    className: 'absolute bottom-32 -left-12 md:left-[5%] lg:left-[10%] w-40 md:w-64 opacity-70 md:opacity-80 mix-blend-multiply contrast-125 animate-float pointer-events-none z-0 filter',
    delay: '-4s',
  },
  {
    src: getAsset(visualAssetModules, 'visual_assets', 'cat1 1.webp'),
    alt: 'Abstract visual asset',
    className: 'absolute top-24 -right-12 md:right-[10%] lg:right-[16%] w-32 md:w-56 opacity-75 md:opacity-85 mix-blend-multiply contrast-125 animate-float pointer-events-none z-0 filter',
    delay: '-1s',
  },
]

const mediaPartners = [
  { name: 'INFOCAMABA', src: getAsset(mediaPartnerAssetModules, 'medpart', '(1) INFOCAMABA.webp'), instagram: 'https://www.instagram.com/infocamaba_/' },
  { name: 'HMPTI UNISA PALU', src: getAsset(mediaPartnerAssetModules, 'medpart', '(2) HMPTI UNISA PALU.webp'), instagram: 'https://www.instagram.com/hmpti_unisa/' },
  { name: 'LPM HITAM PUTIH', src: getAsset(mediaPartnerAssetModules, 'medpart', '(3) LPM HITAM PUTIH.webp'), instagram: 'https://www.instagram.com/lpm.hitamputih/' },
  { name: 'LPM NASEHA', src: getAsset(mediaPartnerAssetModules, 'medpart', '(4) LPM NASEHA.webp'), instagram: 'https://www.instagram.com/lpmnaseha/' },
  { name: 'HIMA - SI UIN', src: getAsset(mediaPartnerAssetModules, 'medpart', '(5) HIMA - SI UIN.webp'), instagram: 'https://www.instagram.com/himasi.uindkpalu/' },
  { name: 'PROGRAMMING TADULAKO', src: getAsset(mediaPartnerAssetModules, 'medpart', '(6) programmig_tad.webp'), instagram: 'https://www.instagram.com/programming.tadulako/' },
  { name: 'HMPSSI STMIK ADHI GUNA PALU', src: getAsset(mediaPartnerAssetModules, 'medpart', '(7) HMPSI STMIK Adhi Guna Palu (1) (1).webp'), instagram: 'https://www.instagram.com/hmpssi_adhiguna/' },
  { name: 'ANIMEDIA TADULAKO', src: getAsset(mediaPartnerAssetModules, 'medpart', '(8) Animedia Tadulako.webp'), instagram: 'https://www.instagram.com/animediatadulako/' },
  { name: 'PERMIKOMNAS WILAYAH X', src: getAsset(mediaPartnerAssetModules, 'medpart', '(9) Permikomnas Wilayah X.webp'), instagram: 'https://www.instagram.com/permikomnaswilayahx/' },
  { name: 'HIMATIF UIN', src: getAsset(mediaPartnerAssetModules, 'medpart', '(10) HIMATIF UIN.webp'), instagram: 'https://www.instagram.com/himatif.uindkpalu/' },
]

const mainStrategicPartner = {
  name: 'Hannah Asa Indonesia',
  shortName: 'HANNAH ASA INDONESIA',
  src: getAsset(strategicPartnerAssetModules, 'sponsor-strategic_partner', 'Hannah Asa.webp'),
  instagram: 'https://www.instagram.com/hannahasaindonesia/',
}

const strategicPartners = [
  {
    name: 'Sultan Music',
    shortName: 'SULTAN MUSIC',
    src: getAsset(strategicPartnerAssetModules, 'sponsor-strategic_partner', 'Sultan Music.webp'),
    description: 'Official Production & Vendor partner menjamin mutu infrastruktur panggung dan malam puncak acara.',
    logoMaxWidth: 'max-w-[200px]',
    instagram: 'https://www.instagram.com/sultan_musik.id/',
  },
  {
    name: 'Google Student Ambasador',
    shortName: 'GSA',
    src: getAsset(strategicPartnerAssetModules, 'sponsor-strategic_partner', 'gsa.webp'),
    description: 'Strategic execution partner yang mendukung operasional kolaborasi dan aktivasi lintas program.',
    logoMaxWidth: 'max-w-[180px]',
    instagram: '',
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

const marqueeLogos = [
  { name: 'Hannah Asa Indonesia', src: mainStrategicPartner.src, isMedia: false },
  { name: 'INFOCAMABA', src: getAsset(mediaPartnerAssetModules, 'medpart', '(1) INFOCAMABA.webp'), isMedia: true },
  { name: 'Sultan Music', src: strategicPartners[0].src, isMedia: false },
  { name: 'HMPTI UNISA PALU', src: getAsset(mediaPartnerAssetModules, 'medpart', '(2) HMPTI UNISA PALU.webp'), isMedia: true },
  { name: 'UNTAD', src: getAsset(mainLogoAssetModules, 'logo_utama', 'logo_untad.webp'), isMedia: false },
  { name: 'LPM HITAM PUTIH', src: getAsset(mediaPartnerAssetModules, 'medpart', '(3) LPM HITAM PUTIH.webp'), isMedia: true },
  { name: 'HMTI', src: getAsset(mainLogoAssetModules, 'logo_utama', 'HMTI LOGO.webp'), isMedia: false },
  { name: 'LPM NASEHA', src: getAsset(mediaPartnerAssetModules, 'medpart', '(4) LPM NASEHA.webp'), isMedia: true },
  { name: 'Google Student Ambasador', src: strategicPartners[1].src, isMedia: false },
  { name: 'HIMA - SI UIN', src: getAsset(mediaPartnerAssetModules, 'medpart', '(5) HIMA - SI UIN.webp'), isMedia: true },
  { name: 'PROGRAMMING TADULAKO', src: getAsset(mediaPartnerAssetModules, 'medpart', '(6) programmig_tad.webp'), isMedia: true },
]

onMounted(() => {
  updateViewport()
  window.addEventListener('resize', updateViewport)
  if (isLoading.value) {
    document.body.style.overflow = 'hidden'
  }
  // GSAP ScrollTrigger integration
  gsap.registerPlugin(ScrollTrigger)

  // 1. Core Section Reveal Animations
  const revealElements = document.querySelectorAll('[data-reveal]')
  revealElements.forEach((el) => {
    if (el.id === 'hero-content') {
      // Hero content fades in smoothly on load
      gsap.fromTo(el, 
        { opacity: 0, y: 30 },
        { opacity: 1, y: 0, duration: 0.8, ease: 'power2.out', delay: 0.15 }
      )
    } else {
      // General section reveal triggers
      gsap.fromTo(el,
        { opacity: 0, y: 30 },
        {
          opacity: 1,
          y: 0,
          duration: 0.7,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: el,
            start: 'top 85%',
            toggleActions: 'play none none none'
          }
        }
      )
    }
  })

  // 2. Trilogy Grid Staggered Card Entry
  gsap.fromTo('#trilogy-grid > div',
    { opacity: 0, y: 40 },
    {
      opacity: 1,
      y: 0,
      duration: 0.6,
      stagger: 0.12,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: '#trilogy-grid',
        start: 'top 85%',
        toggleActions: 'play none none none'
      }
    }
  )

  // 3. Competition Cards (Tier 1 & Tier 2) Staggered Entry
  gsap.fromTo('#kompetisi-grid-tier1 > div',
    { opacity: 0, y: 40 },
    {
      opacity: 1,
      y: 0,
      duration: 0.6,
      stagger: 0.12,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: '#kompetisi-grid-tier1',
        start: 'top 85%',
        toggleActions: 'play none none none'
      }
    }
  )

  gsap.fromTo('#kompetisi-grid-tier2 > div',
    { opacity: 0, y: 40 },
    {
      opacity: 1,
      y: 0,
      duration: 0.6,
      stagger: 0.12,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: '#kompetisi-grid-tier2',
        start: 'top 85%',
        toggleActions: 'play none none none'
      }
    }
  )

  // 5. Impact Dashboard Counters Trigger
  const keyNumbersSection = document.querySelector('#impact-dashboard')
  if (keyNumbersSection) {
    ScrollTrigger.create({
      trigger: keyNumbersSection,
      start: 'top 85%',
      onEnter: () => animateCounters(),
      once: true
    })
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
  ScrollTrigger.getAll().forEach((trigger) => trigger.kill())
})
</script>

<template>
  <div 
    class="riso-canvas bg-off-white min-h-screen text-[#04000D] font-body-md select-text pb-12 transition-all duration-700 ease-out"
  >

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
          
          <h1 class="font-black text-[#04000D] text-4xl sm:text-6xl md:text-8xl lg:text-[110px] tracking-[-0.04em] leading-none mb-lg max-w-5xl px-4 riso-bleed text-center select-none pt-2 pb-4">
            <span class="riso-text-shadow-magenta inline-block">DIGITAL SYMPHONY</span>
          </h1>
          
          <p class="font-body-md text-base md:text-xl text-[#04000D]/80 max-w-2xl mb-xl leading-relaxed mx-auto px-4">
            Mengorkestrasi Inovasi Global untuk Masa Depan Berkelanjutan. HMTI Universitas Tadulako memanggil 8.000+ inovator untuk bergabung dalam revolusi digital terbesar di Sulawesi Tengah.
          </p>
          
          <button class="riso-btn-plate bg-[#04000D] text-white px-8 md:px-xl py-3 md:py-md rounded-full font-button text-button select-none font-bold" style="--plate-color: #D6FF00;">
            EXPLORE THE SYMPHONY ↓
          </button>
        </div>
      </div>
      
      <!-- Ticker Tape Ribbon (Infinite Logo Marquee) -->
      <div class="absolute bottom-0 left-0 w-full bg-[#D6FF00] border-y-2 border-[#04000D] py-4 md:py-6 overflow-hidden z-20 select-none shadow-[0_-4px_0_0_rgba(4,0,13,0.05)]">
        <div class="flex whitespace-nowrap animate-marquee items-center will-change-transform">
          <!-- We repeat the set of logos twice (using group in 2) to ensure perfect 100% seamless infinite looping -->
          <div v-for="group in 2" :key="group" class="flex items-center flex-shrink-0">
            <div 
              v-for="logo in marqueeLogos.filter(logo => logo.src)" 
              :key="`${group}-${logo.name}`" 
              class="flex items-center flex-shrink-0"
            >
              <!-- Separator element: a bold solid diamond character (✦) -->
              <span class="font-mono text-xs md:text-sm font-bold text-[#04000D] mx-4 md:mx-8 select-none flex-shrink-0">✦</span>
              <!-- Logo image, locked for stability -->
              <img 
                :alt="logo.name" 
                class="h-7 md:h-12 w-auto object-contain mix-blend-multiply filter grayscale contrast-200 transition-opacity duration-150 opacity-85 hover:opacity-100 flex-shrink-0 will-change-transform" 
                :src="logo.src" 
              />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION C: Intro (Tactile Text Section) -->
    <section class="bg-off-white riso-canvas py-[48px] md:py-[64px] px-6 md:px-lg border-b border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
      <!-- Subtle Decorative Background Assets (Literal Riso stamped look) -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'ry2 1.webp')" 
        alt="Decorative Risograph Star Shard" 
        class="absolute top-12 left-4 md:left-12 lg:left-24 w-16 md:w-28 opacity-25 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'rb4 1.webp')" 
        alt="Decorative Risograph Star Shard" 
        class="absolute bottom-12 right-4 md:right-12 lg:right-24 w-20 md:w-32 opacity-25 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />

      <div class="max-w-container-max mx-auto relative z-10">
        <div class="bg-[#F5F5F5] border-2 border-[#04000D] shadow-[6px_6px_0px_0px_rgba(4,0,13,1)] p-6 md:p-8 relative overflow-hidden text-left">
          
          <!-- Giant Background Number Overlay -->
          <img 
            :src="getAsset(visualAssetModules, 'visual_assets', 'cat1 1.webp')" 
            alt="IFEST 01 Risograph Stamp" 
            class="absolute -bottom-6 -right-6 w-32 sm:w-48 md:w-64 opacity-[0.06] pointer-events-none select-none z-0 mix-blend-multiply" 
          />
          
          <div class="relative z-10 max-w-4xl">
            <!-- Monospace indicator tag -->
            <span class="font-mono text-xs uppercase tracking-widest text-[#3B82F6] block mb-3.5 font-black">// CORE MANIFESTO</span>
            
            <!-- Headline -->
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-black tracking-tight leading-none uppercase text-[#04000D] riso-bleed mb-4">
              Mengorkestrasi Inovasi, <br class="hidden sm:inline" /><span class="text-[#8839FF]">Mengalirkan Inklusi</span>.
            </h2>
            
            <!-- Paragraph -->
            <p class="text-base md:text-lg text-gray-700 leading-relaxed max-w-2xl">
              Disrupsi kecerdasan buatan dan teknologi bergerak lebih cepat dari sebelumnya. I-FEST 2026 hadir untuk menjembatani digital divide, mempertemukan talenta akar rumput dengan standar industri nasional. Dari ruang kelas pedalaman hingga panggung konser raksasa, mari ciptakan resonansi yang nyata.
            </p>
          </div>
          
        </div>
      </div>
    </section>

    <!-- SECTION P: 3 Core Pillars (Brutalist Trilogy Engine) -->
    <section id="pillars" class="bg-[#F5F5F5] riso-canvas py-20 md:py-24 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
      <!-- Background Decorative Stamp Shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'ry6 1.webp')" 
        alt="Decorative Riso Plate Shard" 
        class="absolute top-10 right-10 w-36 md:w-56 opacity-20 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />
      
      <div class="max-w-container-max mx-auto relative z-10">
        
        <!-- Section Header -->
        <div class="mb-12 md:mb-16 text-left">
          <span class="font-mono text-xs uppercase tracking-widest font-bold text-[#04000D] block mb-2">
            THE TRILOGY ENGINE
          </span>
          <h2 class="font-black text-4xl sm:text-5xl md:text-7xl tracking-[-0.04em] leading-none text-[#04000D] riso-bleed flex flex-wrap items-center gap-y-2 select-none pt-2 pb-2">
            <span class="bg-[#D86BFF] text-[#04000D] px-3.5 py-1.5 rounded-none inline-block transform translate-x-[2px] translate-y-[1px] shadow-[5px_5px_0px_0px_#04000D] mr-2">TIGA PILAR</span> SIMFONI.
          </h2>
        </div>

        <!-- The Brutalist Trilogy Grid System -->
        <div id="trilogy-grid" class="flex flex-col md:grid md:grid-cols-[1.2fr_1fr_1.1fr] border-2 md:border-3 border-[#04000D] bg-[#04000D] gap-[2px] md:gap-[3px] rounded-none overflow-hidden select-none" style="box-shadow: 6px 6px 0px 0px #04000D;">
          
          <!-- CELL 1: RESONANCE -->
          <div class="bg-[#D6FF00] p-6 sm:p-8 md:p-10 flex flex-col justify-between min-h-[320px] md:min-h-[360px] text-[#04000D] transition-all duration-200 hover:bg-[#d9ff1a]">
            <div>
              <div class="flex justify-between items-start mb-6">
                <span class="font-mono text-[10px] font-extrabold uppercase bg-[#04000D] text-[#D6FF00] px-2 py-0.5">
                  DAMPAK NYATA
                </span>
                <span class="font-mono text-[10px] font-bold text-[#04000D]/60">[PILAR-01]</span>
              </div>
              <h3 class="font-black uppercase text-xl sm:text-2xl md:text-3xl tracking-tight leading-none mb-4 text-[#04000D] riso-text-shadow-magenta riso-bleed">
                01 / RESONANCE
              </h3>
              <p class="font-body text-sm md:text-base leading-relaxed opacity-95">
                Menciptakan inovasi digital mutakhir yang memberikan solusi berkelanjutan dan manfaat nyata bagi masyarakat luas.
              </p>
            </div>
            <div class="mt-8 border-t border-[#04000D]/10 pt-4 flex justify-between items-center font-mono text-[9px] font-bold uppercase tracking-widest text-[#04000D]/75">
              <span>REAL IMPACT</span>
              <span>SUSTAINABLE INNOVATION</span>
            </div>
          </div>

          <!-- CELL 2: SYNERGY -->
          <div class="bg-[#D86BFF] p-6 sm:p-8 md:p-10 flex flex-col justify-between min-h-[320px] md:min-h-[360px] text-[#04000D] transition-all duration-200 hover:bg-[#df85ff]">
            <div>
              <div class="flex justify-between items-start mb-6">
                <span class="font-mono text-[10px] font-extrabold uppercase bg-[#04000D] text-[#D86BFF] px-2 py-0.5">
                  KOLABORASI
                </span>
                <span class="font-mono text-[10px] font-bold text-[#04000D]/60">[PILAR-02]</span>
              </div>
              <h3 class="font-black uppercase text-xl sm:text-2xl md:text-3xl tracking-tight leading-none mb-4 text-[#04000D] riso-text-shadow-lime riso-bleed">
                02 / SYNERGY
              </h3>
              <p class="font-body text-sm md:text-base leading-relaxed opacity-95">
                Mengorkestrasi kolaborasi strategis lintas sektor akademisi, industri, dan pemerintah untuk membangun ekosistem terintegrasi.
              </p>
            </div>
            <div class="mt-8 border-t border-[#04000D]/10 pt-4 flex justify-between items-center font-mono text-[9px] font-bold uppercase tracking-widest text-[#04000D]/75">
              <span>STRATEGIC COLLABORATION</span>
              <span>INTEGRATED ECOSYSTEM</span>
            </div>
          </div>

          <!-- CELL 3: INCLUSIVITY -->
          <div class="bg-[#8839FF] p-6 sm:p-8 md:p-10 flex flex-col justify-between min-h-[320px] md:min-h-[360px] text-white transition-all duration-200 hover:bg-[#9752ff]">
            <div>
              <div class="flex justify-between items-start mb-6">
                <span class="font-mono text-[10px] font-extrabold uppercase bg-[#D6FF00] text-[#04000D] px-2 py-0.5">
                  INKLUSIFITAS
                </span>
                <span class="font-mono text-[10px] font-bold text-white/60">[PILAR-03]</span>
              </div>
              <h3 class="font-black uppercase text-xl sm:text-2xl md:text-3xl tracking-tight leading-none mb-4 text-[#D6FF00] riso-text-shadow-magenta riso-bleed">
                03 / INCLUSIVITY
              </h3>
              <p class="font-body text-sm md:text-base leading-relaxed text-[#F5F5F5]/90">
                Mendorong pemerataan akses dan literasi digital agar seluruh lapisan masyarakat siap menghadapi tantangan disrupsi tanpa terkecuali.
              </p>
            </div>
            <div class="mt-8 border-t border-white/10 pt-4 flex justify-between items-center font-mono text-[9px] font-bold uppercase tracking-widest text-[#D6FF00]/95">
              <span>EQUAL ACCESS</span>
              <span>DIGITAL LITERACY</span>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- SECTION COMP: ROADSHOW INKLUSIF & SOCIAL MOVEMENT -->
    <section id="roadshow" class="bg-off-white riso-canvas py-20 md:py-24 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
      <!-- Background decorative riso shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'rg1 1.webp')" 
        alt="Decorative Riso Stamp Shard" 
        class="absolute -top-12 -left-16 w-36 md:w-56 opacity-15 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'ry2 1.webp')" 
        alt="Decorative Riso Stamp Shard" 
        class="absolute bottom-12 -right-12 w-28 md:w-44 opacity-20 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />

      <div class="max-w-container-max mx-auto relative z-10 text-center lg:text-left">
        
        <!-- Section Header Teaser -->
        <div class="flex flex-col lg:flex-row items-center lg:items-end justify-between gap-8">
          <div class="max-w-3xl">
            <span class="font-mono text-xs uppercase tracking-widest font-bold text-[#04000D] block mb-2">
              DIGITAL SYMPHONY TOUR
            </span>
            <h2 class="font-black text-4xl sm:text-5xl md:text-6xl tracking-[-0.04em] leading-none text-[#04000D] riso-bleed flex flex-wrap justify-center lg:justify-start gap-y-2 select-none pt-2 pb-2">
              <span class="bg-[#D6FF00] text-[#04000D] px-3.5 py-1.5 rounded-none inline-block transform translate-x-[2px] translate-y-[1px] shadow-[5px_5px_0px_0px_#04000D] mr-2">ROADSHOW INKLUSIF</span> &amp; SOCIAL MOVEMENT.
            </h2>
            <p class="font-body-md text-base md:text-xl text-[#04000D]/80 mt-6 leading-relaxed">
              Aksi nyata pengabdian masyarakat untuk mengorkestrasi inovasi dan menghadirkan akses literasi digital langsung ke sekolah, desa, dan komunitas disabilitas yang paling membutuhkan di wilayah Palu, Sigi, dan Donggala (Pasigala).
            </p>
          </div>
          
          <div class="flex-shrink-0 select-none">
            <router-link to="/roadshow" class="riso-btn-plate bg-[#04000D] text-white px-8 py-4 rounded-full font-button text-xs font-bold text-center inline-block" style="--plate-color: #FF3D8B;">
              Eksplorasi Rute Roadshow →
            </router-link>
          </div>
        </div>

      </div>
    </section>

    <!-- SECTION COMP: COMPETITION BENTO GRID -->
    <section id="kompetisi" class="bg-off-white riso-canvas py-20 md:py-24 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
      
      <!-- Background decorative riso shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'ry5 1.webp')" 
        alt="Decorative Riso Stamp Shard" 
        class="absolute -top-12 -right-16 w-36 md:w-56 opacity-20 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'sb3 1.webp')" 
        alt="Decorative Riso Stamp Shard" 
        class="absolute bottom-12 -left-12 w-28 md:w-44 opacity-15 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />

      <div class="max-w-container-max mx-auto relative z-10">
        
        <!-- Section Header -->
        <div class="mb-12 md:mb-16 text-center lg:text-left">
          <span class="font-mono text-xs uppercase tracking-widest font-bold text-[#04000D] block mb-2">
            IFEST 2026 CHALLENGE
          </span>
          <h2 class="font-black text-4xl sm:text-5xl md:text-7xl tracking-[-0.04em] leading-none text-[#04000D] riso-bleed flex flex-wrap justify-center lg:justify-start gap-y-2 select-none pt-2 pb-2">
            <span class="bg-[#FF3D8B] text-white px-3.5 py-1.5 rounded-none inline-block transform translate-x-[2px] translate-y-[1px] shadow-[5px_5px_0px_0px_#04000D] mr-2">ARENA KOMPETISI</span> DIGITAL.
          </h2>
          <p class="font-body-md text-base md:text-xl text-[#04000D]/80 max-w-3xl mt-6 leading-relaxed">
            Panggung bagi talenta muda Indonesia untuk bersaing, berkarya, dan berinovasi dari algoritma hingga desain, dari video hingga ide bisnis yang mengubah dunia nyata.
          </p>
        </div>

        <!-- TIER 1: KATEGORI NASIONAL (3-Column Bento Grid) -->
        <div class="mb-16">
          <div class="flex items-center gap-3 mb-8 select-none">
            <span class="font-mono text-xs font-bold uppercase tracking-widest bg-[#04000D] text-white px-2.5 py-0.5">TIER 01</span>
            <span class="font-mono text-xs font-bold uppercase tracking-widest text-[#04000D]/60">KATEGORI NASIONAL</span>
          </div>

          <div id="kompetisi-grid-tier1" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div 
              v-for="comp in competitionsData.slice(0, 3)" 
              :key="comp.id"
              class="border-2 md:border-3 border-[#04000D] p-6 sm:p-8 flex flex-col justify-between min-h-[300px] relative transition-transform duration-200 hover:-rotate-1"
              :style="{ backgroundColor: comp.cardBg, boxShadow: '6px 6px 0px 0px #04000D' }"
            >
              <div>
                <div class="flex justify-between items-start mb-6">
                  <span class="font-mono text-[9px] font-extrabold uppercase bg-[#04000D] text-white px-2 py-0.5">{{ comp.tagline }}</span>
                  <span class="font-mono text-[9px] font-bold text-[#04000D]/60">[{{ comp.id }}]</span>
                </div>
                <h3 class="font-black uppercase text-xl sm:text-2xl tracking-tight leading-none mb-4 text-[#04000D] riso-bleed">
                  {{ comp.title.split(' ')[0] }} <span class="text-accent-magenta">{{ comp.title.split(' ').slice(1).join(' ') }}</span>
                </h3>
                <div class="border-t border-dashed border-[#04000D]/20 pt-4 flex flex-col gap-2 font-mono text-[11px] text-[#04000D]/80 font-medium tracking-wide">
                  <div class="flex justify-between"><span>Skala:</span><span class="font-bold">{{ comp.scale }}</span></div>
                  <div class="flex justify-between"><span>Biaya Registrasi:</span><span class="font-bold font-mono">{{ comp.fee }}</span></div>
                </div>
              </div>
              
              <div class="mt-8 select-none">
                <router-link :to="{ path: '/kompetisi', query: { id: comp.id } }" class="riso-btn-plate w-full block bg-[#04000D] text-white py-2.5 rounded-full font-button text-xs text-center font-bold" :style="{ '--plate-color': comp.accentColor }">
                  Lihat Detail Lomba →
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- TIER 2: KATEGORI REGIONAL (2-Column Bento Grid) -->
        <div class="mb-16">
          <div class="flex items-center gap-3 mb-8 select-none">
            <span class="font-mono text-xs font-bold uppercase tracking-widest bg-[#04000D] text-white px-2.5 py-0.5">TIER 02</span>
            <span class="font-mono text-xs font-bold uppercase tracking-widest text-[#04000D]/60">KATEGORI REGIONAL (SULTENG &amp; SEKITARNYA)</span>
          </div>

          <div id="kompetisi-grid-tier2" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div 
              v-for="comp in competitionsData.slice(3, 5)" 
              :key="comp.id"
              class="bg-white border-2 md:border-3 border-[#04000D] p-6 sm:p-8 flex flex-col justify-between min-h-[300px] relative transition-transform duration-200 hover:rotate-1"
              style="box-shadow: 6px 6px 0px 0px #04000D;"
            >
              <!-- Stamp accent plate -->
              <div class="absolute inset-0 bg-[#04000D]/5 mix-blend-multiply pointer-events-none rounded-none"></div>
              <div>
                <div class="flex justify-between items-start mb-6">
                  <span class="font-mono text-[9px] font-extrabold uppercase bg-[#04000D] text-white px-2 py-0.5">{{ comp.tagline }}</span>
                  <span class="font-mono text-[9px] font-bold text-[#04000D]/60">[{{ comp.id }}]</span>
                </div>
                <h3 class="font-black uppercase text-xl sm:text-2xl tracking-tight leading-none mb-4 text-[#04000D] riso-bleed">
                  {{ comp.title.split(' ').slice(0, -1).join(' ') }} <span class="text-accent-magenta">{{ comp.title.split(' ').slice(-1)[0] }}</span>
                </h3>
                <div class="border-t border-dashed border-[#04000D]/20 pt-4 flex flex-col gap-2 font-mono text-[11px] text-[#04000D]/80 font-medium tracking-wide">
                  <div class="flex justify-between"><span>Skala:</span><span class="font-bold">{{ comp.scale }}</span></div>
                  <div class="flex justify-between"><span>Biaya Registrasi:</span><span class="font-bold font-mono">Gratis</span></div>
                </div>
              </div>
              
              <div class="mt-8 select-none">
                <router-link :to="{ path: '/kompetisi', query: { id: comp.id } }" class="riso-btn-plate w-full block bg-[#04000D] text-white py-2.5 rounded-full font-button text-xs text-center font-bold" :style="{ '--plate-color': comp.accentColor }">
                  Lihat Detail Lomba →
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- TIER 3: EXPO INOVASI DIGITAL (Hackathon + Showcase) -->
        <div>
          <div class="flex items-center gap-3 mb-8 select-none">
            <span class="font-mono text-xs font-bold uppercase tracking-widest bg-[#04000D] text-white px-2.5 py-0.5">TIER 03</span>
            <span class="font-mono text-xs font-bold uppercase tracking-widest text-[#04000D]/60">SULTENG INNOVATION ENGINE</span>
          </div>

          <!-- Massive dark contrast bento card for S-DIH -->
          <div class="bg-[#04000D] border-2 md:border-3 border-[#04000D] p-6 sm:p-8 md:p-10 relative overflow-hidden select-none" style="box-shadow: 6px 6px 0px 0px #FF3D8B;">
            <div class="absolute inset-0 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:20px_20px] opacity-[0.03] pointer-events-none z-0"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row gap-6 justify-between items-center">
              <div class="text-white text-left flex-1">
                <div class="flex items-center gap-2 mb-3">
                  <span class="font-mono text-[9px] font-extrabold uppercase bg-[#D6FF00] text-[#04000D] px-2 py-0.5">HACKATHON + SHOWCASE</span>
                  <span class="font-mono text-xs font-bold text-white/50">[REG-03]</span>
                </div>
                <h3 class="font-black text-2xl sm:text-4xl uppercase tracking-tighter leading-none mb-4 text-[#D6FF00] riso-bleed">
                  SULTENG DIGITAL INNOVATION HUB (S-DIH)
                </h3>
                <div class="flex gap-6 font-mono text-xs text-white/85 border-t border-white/10 pt-4 mt-2">
                  <div>Skala: <span class="font-bold text-white">Regional</span></div>
                  <div>Biaya Registrasi: <span class="font-bold text-[#D6FF00]">Gratis (Free)</span></div>
                </div>
              </div>
              <div class="w-full md:w-auto">
                <router-link :to="{ path: '/kompetisi', query: { id: 'REG-03' } }" class="riso-btn-plate bg-[#D6FF00] text-[#04000D] px-8 py-3 rounded-full font-button text-xs text-center font-extrabold block" style="--plate-color: #FF3D8B;">
                  Lihat Detail Lomba →
                </router-link>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- SECTION K: KEY NUMBERS (The Impact Dashboard) -->
    <section id="impact-dashboard" class="bg-[#F5F5F5] riso-canvas py-12 md:py-16 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 animate-fade-in relative overflow-hidden" data-reveal>
      <!-- Background Decorative Stamp Shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'ry6 1.webp')" 
        alt="Decorative Riso Plate Shard" 
        class="absolute -top-6 -left-12 w-28 md:w-44 opacity-20 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />

      <div class="max-w-container-max mx-auto relative z-10">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8 select-none">
          
          <!-- Stat 1 -->
          <div class="relative group">
            <div class="absolute inset-0 bg-[#04000D] translate-x-[2px] translate-y-[1.5px] border border-[#04000D] z-0"></div>
            <div class="relative bg-[#F5F5F5] border border-dashed border-[#04000D] p-5 sm:p-6 z-10 flex flex-col justify-between min-h-[145px]">
              <span class="font-mono text-[9px] sm:text-[10px] uppercase tracking-wider text-[#04000D]/60 font-bold">ESTIMASI TARGET PARTISIPAN</span>
              <div>
                <h3 class="text-3xl sm:text-5xl font-bold tracking-tighter text-[#04000D] leading-none mb-2 font-headline-lg riso-bleed tabular-nums">{{ formattedPartisipan }}</h3>
                <p class="font-mono text-[9px] sm:text-[10px] tracking-widest text-[#04000D] font-bold uppercase leading-none mt-2 select-none">
                  <span class="bg-[#D6FF00] text-[#04000D] px-2 py-0.5 rounded-none inline-block transform translate-x-[2px] translate-y-[1px] shadow-[2px_2px_0px_0px_#04000D]">8.000+ PARTICIPANTS</span>
                </p>
              </div>
            </div>
          </div>

          <!-- Stat 2 -->
          <div class="relative group">
            <div class="absolute inset-0 bg-[#D6FF00] translate-x-[2px] translate-y-[1.5px] border border-[#04000D] z-0"></div>
            <div class="relative bg-[#F5F5F5] border border-dashed border-[#04000D] p-5 sm:p-6 z-10 flex flex-col justify-between min-h-[145px]">
              <span class="font-mono text-[9px] sm:text-[10px] uppercase tracking-wider text-[#04000D]/60 font-bold">TITIK ROADSHOW INKLUSIF</span>
              <div>
                <h3 class="text-3xl sm:text-5xl font-bold tracking-tighter text-[#04000D] leading-none mb-2 font-headline-lg riso-bleed tabular-nums">{{ formattedRoadshow }}</h3>
                <p class="font-mono text-[9px] sm:text-[10px] tracking-widest text-[#04000D] font-bold uppercase leading-none mt-2 select-none">
                  <span class="bg-[#D86BFF] text-[#04000D] px-2 py-0.5 rounded-none inline-block transform translate-x-[-2px] translate-y-[1px] shadow-[2px_2px_0px_0px_#04000D]">25 REGIONAL ROADSHOWS</span>
                </p>
              </div>
            </div>
          </div>

          <!-- Stat 3 -->
          <div class="relative group">
            <div class="absolute inset-0 bg-[#04000D] translate-x-[2px] translate-y-[1.5px] border border-[#04000D] z-0"></div>
            <div class="relative bg-[#F5F5F5] border border-dashed border-[#04000D] p-5 sm:p-6 z-10 flex flex-col justify-between min-h-[145px]">
              <span class="font-mono text-[9px] sm:text-[10px] uppercase tracking-wider text-[#04000D]/60 font-bold">ESTIMASI TALENTA DIGITAL</span>
              <div>
                <h3 class="text-3xl sm:text-5xl font-bold tracking-tighter text-[#04000D] leading-none mb-2 font-headline-lg riso-bleed tabular-nums">{{ formattedTalent }}</h3>
                <p class="font-mono text-[9px] sm:text-[10px] tracking-widest text-[#04000D] font-bold uppercase leading-none mt-2 select-none">
                  <span class="bg-[#D6FF00] text-[#04000D] px-2 py-0.5 rounded-none inline-block transform translate-x-[1px] translate-y-[-1px] shadow-[2px_2px_0px_0px_#04000D]">500+ DIGITAL TALENTS</span>
                </p>
              </div>
            </div>
          </div>

          <!-- Stat 4 -->
          <div class="relative group">
            <div class="absolute inset-0 bg-[#D6FF00] translate-x-[2px] translate-y-[1.5px] border border-[#04000D] z-0"></div>
            <div class="relative bg-[#F5F5F5] border border-dashed border-[#04000D] p-5 sm:p-6 z-10 flex flex-col justify-between min-h-[145px]">
              <span class="font-mono text-[9px] sm:text-[10px] uppercase tracking-wider text-[#04000D]/60 font-bold">ESTIMASI MEDIA EXPOSURE</span>
              <div>
                <h3 class="text-3xl sm:text-5xl font-bold tracking-tighter text-[#04000D] leading-none mb-2 font-headline-lg riso-bleed tabular-nums">{{ formattedExposure }}</h3>
                <p class="font-mono text-[9px] sm:text-[10px] tracking-widest text-[#04000D] font-bold uppercase leading-none mt-2 select-none">
                  <span class="bg-[#D86BFF] text-[#04000D] px-2 py-0.5 rounded-none inline-block transform translate-x-[-1px] translate-y-[2px] shadow-[2px_2px_0px_0px_#04000D]">800K+ MEDIA EXPOSURE</span>
                </p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- SECTION L: TIMELINE KEGIATAN (The Roadmap Series) -->
    <section id="timeline" class="bg-off-white riso-canvas py-16 md:py-24 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 animate-fade-in relative overflow-hidden" data-reveal>
      <div class="absolute inset-0 bg-[radial-gradient(#04000D_1px,transparent_1px)] [background-size:24px_24px] opacity-[0.02] pointer-events-none z-0"></div>
      <div class="absolute inset-0 bg-noise-grain opacity-[0.015] pointer-events-none z-0"></div>

      <div class="max-w-container-max mx-auto relative z-10">
        
        <!-- HEADER BLOCK (Matching image_f0d77e.png) -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-16 select-none border-b-2 border-[#04000D] pb-8">
          <div class="text-left">
            <div class="flex items-center gap-3 mb-3">
              <span class="w-12 h-1 bg-[#3B82F6] inline-block"></span>
              <span class="font-mono text-sm uppercase tracking-widest font-black text-[#3B82F6]">TIMELINE</span>
              <span class="bg-[#3B82F6] text-white px-3 py-1 text-[10px] font-mono font-bold rounded-full tracking-wider uppercase ml-2">I-FEST 2026 – Digital Symphony</span>
            </div>
            <h2 class="font-black text-3xl sm:text-4xl md:text-5xl leading-none text-[#04000D] tracking-tight uppercase">
              Timeline &amp; <span class="text-[#8B5CF6] italic font-serif lowercase capitalize">Rangkaian</span> <br class="hidden sm:inline"/>I-FEST 2026
            </h2>
            <p class="font-mono text-sm font-bold text-[#3B82F6] uppercase tracking-wider mt-4">
              Roadmap Menuju Puncak
            </p>
          </div>
          
          <!-- Big 6 Rangkaian Badge -->
          <div class="bg-[#F3F0FF] border-2 border-[#8B5CF6] p-6 rounded-2xl flex items-center gap-4 self-start md:self-auto shadow-[4px_4px_0px_0px_#8B5CF6] min-w-[240px]">
            <span class="font-black text-5xl md:text-6xl text-[#8B5CF6] leading-none">6</span>
            <div class="text-left font-mono">
              <span class="text-xs uppercase tracking-widest text-[#8B5CF6]/80 font-bold block">RANGKAIAN</span>
              <span class="text-xs uppercase tracking-widest text-[#8B5CF6] font-black block">KEGIATAN</span>
            </div>
          </div>
        </div>

        <!-- TIMELINE GRID / ROADMAP FLOW -->
        <div class="relative mt-20 select-none">
          
          <!-- Central Connecting Spine -->
          <!-- Desktop: Perfect Center -->
          <div class="hidden lg:block absolute left-1/2 -translate-x-1/2 top-4 bottom-4 w-1 border-l-4 border-dashed border-slate-300/80 z-0"></div>
          <!-- Mobile: Left Align -->
          <div class="lg:hidden absolute left-8 top-4 bottom-4 w-1 border-l-4 border-dashed border-slate-300/80 z-0"></div>

          <div class="flex flex-col gap-16 md:gap-20">
            
            <!-- PHASE 01 -->
            <div class="relative grid grid-cols-1 lg:grid-cols-[1fr_auto_1fr] items-start gap-8 lg:gap-12 w-full">
              <!-- Left Card -->
              <div 
                v-motion
                :initial="{ opacity: 0, x: -40 }"
                :visible-once="{ opacity: 1, x: 0, transition: { duration: 500 } }"
                class="lg:text-right flex flex-col items-start lg:items-end w-full lg:order-1 order-3 pl-16 lg:pl-0"
              >
                <div class="w-full max-w-xl text-left bg-white border-3 border-[#8B5CF6] rounded-2xl p-6 transition-all duration-300 hover:scale-[1.01]" style="box-shadow: 6px 6px 0px 0px #8B5CF6;">
                  <div 
                    @click="activeTimelinePhase = activeTimelinePhase === 0 ? -1 : 0"
                    class="flex items-center justify-between border-b border-purple-100 pb-3 cursor-pointer select-none"
                  >
                    <h4 class="font-black text-lg md:text-xl text-[#04000D] tracking-tight uppercase">PHASE 01: Identity &amp; Foundation</h4>
                    <div class="flex items-center gap-3">
                      <span class="flex items-center gap-1.5 font-mono text-xs font-bold text-[#8B5CF6] uppercase">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        Januari - Maret
                      </span>
                      <!-- Chevron icon -->
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" class="w-4 h-4 text-[#8B5CF6] transition-transform duration-300" :class="activeTimelinePhase === 0 ? 'rotate-180' : ''"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    </div>
                  </div>
                  
                  <div 
                    class="overflow-hidden transition-all duration-300 ease-in-out"
                    :class="activeTimelinePhase === 0 ? 'max-h-96 mt-4 opacity-100' : 'max-h-0 opacity-0 pointer-events-none'"
                  >
                    <div class="bg-[#FAF5FF] border border-purple-100 rounded-xl p-4 md:p-5">
                      <table class="w-full font-mono text-xs md:text-sm text-[#04000D]">
                        <tr class="align-top">
                          <td class="w-[80px] font-black text-[#8B5CF6] py-1">Januari:</td>
                          <td class="py-1 text-[#04000D]/90">Pembentukan Tim Inti &amp; Penyusunan Konsep Kasar.</td>
                        </tr>
                        <tr class="align-top border-t border-purple-100/50">
                          <td class="font-black text-[#8B5CF6] py-2">Februari:</td>
                          <td class="py-2 text-[#04000D]/90">Penyusunan Proposal Kegiatan.</td>
                        </tr>
                        <tr class="align-top border-t border-purple-100/50">
                          <td class="font-black text-[#8B5CF6] py-2">Maret:</td>
                          <td class="py-2 text-[#04000D]/90">Finalisasi struktur kepanitiaan (80+ personil).</td>
                        </tr>
                        <tr class="align-top border-t border-purple-100/50">
                          <td class="font-black text-[#8B5CF6] py-2">Maret:</td>
                          <td class="py-2 text-[#04000D]/90">Audiensi Mitra Strategis &amp; Pencarian Dana.</td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Spine Node -->
              <div class="absolute left-4 lg:left-auto lg:relative lg:order-2 order-1 z-10">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-white border-4 border-[#8B5CF6] flex items-center justify-center font-mono text-sm md:text-base font-extrabold text-[#8B5CF6] shadow-[2px_2px_0px_0px_#04000D]">
                  01
                </div>
              </div>
              
              <!-- Spacer for desktop -->
              <div class="hidden lg:block lg:order-3"></div>
            </div>

            <!-- PHASE 02 -->
            <div class="relative grid grid-cols-1 lg:grid-cols-[1fr_auto_1fr] items-start gap-8 lg:gap-12 w-full">
              <!-- Spacer for desktop -->
              <div class="hidden lg:block lg:order-1"></div>
              
              <!-- Spine Node -->
              <div class="absolute left-4 lg:left-auto lg:relative lg:order-2 order-1 z-10">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-white border-4 border-[#10B981] flex items-center justify-center font-mono text-sm md:text-base font-extrabold text-[#10B981] shadow-[2px_2px_0px_0px_#04000D]">
                  02
                </div>
              </div>

              <!-- Right Card -->
              <div 
                v-motion
                :initial="{ opacity: 0, x: 40 }"
                :visible-once="{ opacity: 1, x: 0, transition: { duration: 500 } }"
                class="flex flex-col items-start w-full lg:order-3 order-3 pl-16 lg:pl-0"
              >
                <div class="w-full max-w-xl text-left bg-white border-3 border-[#10B981] rounded-2xl p-6 transition-all duration-300 hover:scale-[1.01]" style="box-shadow: 6px 6px 0px 0px #10B981;">
                  <div 
                    @click="activeTimelinePhase = activeTimelinePhase === 1 ? -1 : 1"
                    class="flex items-center justify-between border-b border-emerald-100 pb-3 cursor-pointer select-none"
                  >
                    <h4 class="font-black text-lg md:text-xl text-[#04000D] tracking-tight uppercase">PHASE 02: Inklusif Roadshow</h4>
                    <div class="flex items-center gap-3">
                      <span class="flex items-center gap-1.5 font-mono text-xs font-bold text-[#10B981] uppercase">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        Mei - Agustus
                      </span>
                      <!-- Chevron icon -->
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" class="w-4 h-4 text-[#10B981] transition-transform duration-300" :class="activeTimelinePhase === 1 ? 'rotate-180' : ''"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    </div>
                  </div>
                  
                  <div 
                    class="overflow-hidden transition-all duration-300 ease-in-out"
                    :class="activeTimelinePhase === 1 ? 'max-h-96 mt-4 opacity-100' : 'max-h-0 opacity-0 pointer-events-none'"
                  >
                    <div class="bg-[#ECFDF5] border border-emerald-100 rounded-xl p-4 md:p-5">
                      <table class="w-full font-mono text-xs md:text-sm text-[#04000D]">
                        <tr class="align-top">
                          <td class="w-[95px] font-black text-[#10B981] py-1">Mei - Jun:</td>
                          <td class="py-1 text-[#04000D]/90">Awal pergerakan menyasar sekolah umum, sekolah alam/alternatif, dan komunitas disabilitas.</td>
                        </tr>
                        <tr class="align-top border-t border-emerald-100/50">
                          <td class="font-black text-[#10B981] py-2">Jul - Agust:</td>
                          <td class="py-2 text-[#04000D]/90">Ekspansi roadshow ke 800+ pelajar &amp; masyarakat marginal.</td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- PHASE 03 -->
            <div class="relative grid grid-cols-1 lg:grid-cols-[1fr_auto_1fr] items-start gap-8 lg:gap-12 w-full">
              <!-- Left Card -->
              <div 
                v-motion
                :initial="{ opacity: 0, x: -40 }"
                :visible-once="{ opacity: 1, x: 0, transition: { duration: 500 } }"
                class="lg:text-right flex flex-col items-start lg:items-end w-full lg:order-1 order-3 pl-16 lg:pl-0"
              >
                <div class="w-full max-w-xl text-left bg-white border-3 border-[#3B82F6] rounded-2xl p-6 transition-all duration-300 hover:scale-[1.01]" style="box-shadow: 6px 6px 0px 0px #3B82F6;">
                  <div 
                    @click="activeTimelinePhase = activeTimelinePhase === 2 ? -1 : 2"
                    class="flex items-center justify-between border-b border-blue-100 pb-3 cursor-pointer select-none"
                  >
                    <h4 class="font-black text-lg md:text-xl text-[#04000D] tracking-tight uppercase">PHASE 03: Awareness &amp; Reg</h4>
                    <div class="flex items-center gap-3">
                      <span class="flex items-center gap-1.5 font-mono text-xs font-bold text-[#3B82F6] uppercase">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        Juli - Agustus
                      </span>
                      <!-- Chevron icon -->
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" class="w-4 h-4 text-[#3B82F6] transition-transform duration-300" :class="activeTimelinePhase === 2 ? 'rotate-180' : ''"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    </div>
                  </div>
                  
                  <div 
                    class="overflow-hidden transition-all duration-300 ease-in-out"
                    :class="activeTimelinePhase === 2 ? 'max-h-96 mt-4 opacity-100' : 'max-h-0 opacity-0 pointer-events-none'"
                  >
                    <div class="bg-[#EFF6FF] border border-blue-100 rounded-xl p-4 md:p-5">
                      <table class="w-full font-mono text-xs md:text-sm text-[#04000D]">
                        <tr class="align-top">
                          <td class="w-[80px] font-black text-[#3B82F6] py-1">Juli:</td>
                          <td class="py-1 text-[#04000D]/90">Pembukaan pendaftaran Arena Kompetisi di sela-sela roadshow lapangan.</td>
                        </tr>
                        <tr class="align-top border-t border-blue-100/50">
                          <td class="font-black text-[#3B82F6] py-2">Agustus:</td>
                          <td class="py-2 text-[#04000D]/90">Kampanye masif registrasi 6 Lomba menargetkan 500+ kompetitor elite.</td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Spine Node -->
              <div class="absolute left-4 lg:left-auto lg:relative lg:order-2 order-1 z-10">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-white border-4 border-[#3B82F6] flex items-center justify-center font-mono text-sm md:text-base font-extrabold text-[#3B82F6] shadow-[2px_2px_0px_0px_#04000D]">
                  03
                </div>
              </div>
              
              <!-- Spacer for desktop -->
              <div class="hidden lg:block lg:order-3"></div>
            </div>

            <!-- PHASE 04 -->
            <div class="relative grid grid-cols-1 lg:grid-cols-[1fr_auto_1fr] items-start gap-8 lg:gap-12 w-full">
              <!-- Spacer for desktop -->
              <div class="hidden lg:block lg:order-1"></div>
              
              <!-- Spine Node -->
              <div class="absolute left-4 lg:left-auto lg:relative lg:order-2 order-1 z-10">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-white border-4 border-[#F59E0B] flex items-center justify-center font-mono text-sm md:text-base font-extrabold text-[#F59E0B] shadow-[2px_2px_0px_0px_#04000D]">
                  04
                </div>
              </div>

              <!-- Right Card -->
              <div 
                v-motion
                :initial="{ opacity: 0, x: 40 }"
                :visible-once="{ opacity: 1, x: 0, transition: { duration: 500 } }"
                class="flex flex-col items-start w-full lg:order-3 order-3 pl-16 lg:pl-0"
              >
                <div class="w-full max-w-xl text-left bg-white border-3 border-[#F59E0B] rounded-2xl p-6 transition-all duration-300 hover:scale-[1.01]" style="box-shadow: 6px 6px 0px 0px #F59E0B;">
                  <div 
                    @click="activeTimelinePhase = activeTimelinePhase === 3 ? -1 : 3"
                    class="flex items-center justify-between border-b border-amber-100 pb-3 cursor-pointer select-none"
                  >
                    <h4 class="font-black text-lg md:text-xl text-[#04000D] tracking-tight uppercase">PHASE 04: Benchmark &amp; Exploration</h4>
                    <div class="flex items-center gap-3">
                      <span class="flex items-center gap-1.5 font-mono text-xs font-bold text-[#F59E0B] uppercase">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        Agustus - Sept
                      </span>
                      <!-- Chevron icon -->
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" class="w-4 h-4 text-[#F59E0B] transition-transform duration-300" :class="activeTimelinePhase === 3 ? 'rotate-180' : ''"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    </div>
                  </div>
                  
                  <div 
                    class="overflow-hidden transition-all duration-300 ease-in-out"
                    :class="activeTimelinePhase === 3 ? 'max-h-96 mt-4 opacity-100' : 'max-h-0 opacity-0 pointer-events-none'"
                  >
                    <div class="bg-[#FFFBEB] border border-amber-100 rounded-xl p-4 md:p-5">
                      <table class="w-full font-mono text-xs md:text-sm text-[#04000D]">
                        <tr class="align-top">
                          <td class="w-[90px] font-black text-[#F59E0B] py-1">Agustus:</td>
                          <td class="py-1 text-[#04000D]/90">Studi banding akademik ke laboratorium teknologi universitas target di Pulau Jawa.</td>
                        </tr>
                        <tr class="align-top border-t border-amber-100/50">
                          <td class="font-black text-[#F59E0B] py-2">September:</td>
                          <td class="py-2 text-[#04000D]/90">Industrial Visitation ke raksasa teknologi untuk sinkronisasi praktik industri.</td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- PHASE 05 -->
            <div class="relative grid grid-cols-1 lg:grid-cols-[1fr_auto_1fr] items-start gap-8 lg:gap-12 w-full">
              <!-- Left Card -->
              <div 
                v-motion
                :initial="{ opacity: 0, x: -40 }"
                :visible-once="{ opacity: 1, x: 0, transition: { duration: 500 } }"
                class="lg:text-right flex flex-col items-start lg:items-end w-full lg:order-1 order-3 pl-16 lg:pl-0"
              >
                <div class="w-full max-w-xl text-left bg-white border-3 border-[#EF4444] rounded-2xl p-6 transition-all duration-300 hover:scale-[1.01]" style="box-shadow: 6px 6px 0px 0px #EF4444;">
                  <div 
                    @click="activeTimelinePhase = activeTimelinePhase === 4 ? -1 : 4"
                    class="flex items-center justify-between border-b border-red-100 pb-3 cursor-pointer select-none"
                  >
                    <h4 class="font-black text-lg md:text-xl text-[#04000D] tracking-tight uppercase">PHASE 05: Local Intellectual Series</h4>
                    <div class="flex items-center gap-3">
                      <span class="flex items-center gap-1.5 font-mono text-xs font-bold text-[#EF4444] uppercase">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        September
                      </span>
                      <!-- Chevron icon -->
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" class="w-4 h-4 text-[#EF4444] transition-transform duration-300" :class="activeTimelinePhase === 4 ? 'rotate-180' : ''"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    </div>
                  </div>
                  
                  <div 
                    class="overflow-hidden transition-all duration-300 ease-in-out"
                    :class="activeTimelinePhase === 4 ? 'max-h-96 mt-4 opacity-100' : 'max-h-0 opacity-0 pointer-events-none'"
                  >
                    <div class="bg-[#FEF2F2] border border-red-100 rounded-xl p-4 md:p-5">
                      <table class="w-full font-mono text-xs md:text-sm text-[#04000D]">
                        <tr class="align-top">
                          <td class="w-[90px] font-black text-[#EF4444] py-1">Pekan 3-4:</td>
                          <td class="py-1 text-[#04000D]/90">TEDxUNTAD: Regional Resonance membedah tantangan disrupsi digital.</td>
                        </tr>
                        <tr class="align-top border-t border-red-100/50">
                          <td class="font-black text-[#EF4444] py-2">Output:</td>
                          <td class="py-2 text-[#04000D]/90">Dokumentasi pemikiran strategis untuk Expo Inovasi November.</td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Spine Node -->
              <div class="absolute left-4 lg:left-auto lg:relative lg:order-2 order-1 z-10">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-white border-4 border-[#EF4444] flex items-center justify-center font-mono text-sm md:text-base font-extrabold text-[#EF4444] shadow-[2px_2px_0px_0px_#04000D]">
                  05
                </div>
              </div>
              
              <!-- Spacer for desktop -->
              <div class="hidden lg:block lg:order-3"></div>
            </div>

            <!-- PHASE 06 -->
            <div class="relative grid grid-cols-1 lg:grid-cols-[1fr_auto_1fr] items-start gap-8 lg:gap-12 w-full">
              <!-- Spacer for desktop -->
              <div class="hidden lg:block lg:order-1"></div>
              
              <!-- Spine Node -->
              <div class="absolute left-4 lg:left-auto lg:relative lg:order-2 order-1 z-10">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-[#0F172A] border-4 border-[#F59E0B] flex items-center justify-center shadow-[2px_2px_0px_0px_#04000D]">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-[#F59E0B]"><path fill-rule="evenodd" d="M12 2.25a.75.75 0 0 1 .75.75v2.25a.75.75 0 0 1-1.5 0V3a.75.75 0 0 1 .75-.75ZM6.16 5.1a.75.75 0 0 1 1.06 0l1.59 1.59a.75.75 0 1 1-1.06 1.06L6.16 6.16a.75.75 0 0 1 0-1.06Zm10.62 0a.75.75 0 0 1 0 1.06l-1.59 1.59a.75.75 0 1 1-1.06-1.06l1.59-1.59a.75.75 0 0 1 1.06 0ZM2.25 12a.75.75 0 0 1 .75-.75h2.25a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1-.75-.75Zm15 0a.75.75 0 0 1 .75-.75H21a.75.75 0 0 1 0 1.5h-2.25a.75.75 0 0 1-.75-.75Zm-10.5 5.34a.75.75 0 0 1 1.06 0l1.59 1.59a.75.75 0 1 1-1.06 1.06l-1.59-1.59a.75.75 0 0 1 0-1.06Zm7.06 0a.75.75 0 0 1 0 1.06l-1.59 1.59a.75.75 0 1 1-1.06-1.06l1.59-1.59a.75.75 0 0 1 1.06 0ZM12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5ZM12 18.75a.75.75 0 0 1 .75.75V21a.75.75 0 0 1-1.5 0v-1.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" /></svg>
                </div>
              </div>

              <!-- Right Card (Dark Navy Theme) -->
              <div 
                v-motion
                :initial="{ opacity: 0, x: 40 }"
                :visible-once="{ opacity: 1, x: 0, transition: { duration: 500 } }"
                class="flex flex-col items-start w-full lg:order-3 order-3 pl-16 lg:pl-0"
              >
                <div class="w-full max-w-xl text-left bg-[#0F172A] border-3 border-[#F59E0B] rounded-2xl p-6 transition-all duration-300 hover:scale-[1.01]" style="box-shadow: 6px 6px 0px 0px #F59E0B;">
                  <div 
                    @click="activeTimelinePhase = activeTimelinePhase === 5 ? -1 : 5"
                    class="flex items-center justify-between border-b border-slate-700 pb-3 cursor-pointer select-none"
                  >
                    <h4 class="font-black text-lg md:text-xl text-white tracking-tight uppercase">PHASE 06: Grand Symphony &amp; Legacy</h4>
                    <div class="flex items-center gap-3">
                      <span class="flex items-center gap-1.5 font-mono text-xs font-bold text-[#F59E0B] uppercase">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        Nov - Des
                      </span>
                      <!-- Chevron icon -->
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3.5" stroke="currentColor" class="w-4 h-4 text-[#F59E0B] transition-transform duration-300" :class="activeTimelinePhase === 5 ? 'rotate-180' : ''"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    </div>
                  </div>
                  
                  <div 
                    class="overflow-hidden transition-all duration-300 ease-in-out"
                    :class="activeTimelinePhase === 5 ? 'max-h-96 mt-4 opacity-100' : 'max-h-0 opacity-0 pointer-events-none'"
                  >
                    <div class="bg-[#1E293B] border border-slate-700 rounded-xl p-4 md:p-5">
                      <table class="w-full font-mono text-xs md:text-sm text-white">
                        <tr class="align-top">
                          <td class="w-[95px] font-black text-[#F59E0B] py-1">November:</td>
                          <td class="py-1 text-white/90"><span class="font-bold text-[#F59E0B]">3 HARI PUNCAK I-FEST 2026</span> (Expo Inovasi, Seminar Internasional, Awarding Night, Grand Closing Concert).</td>
                        </tr>
                        <tr class="align-top border-t border-slate-700/50">
                          <td class="font-black text-[#F59E0B] py-2">Desember:</td>
                          <td class="py-2 text-white/90">Perilisan Official Aftermovie &amp; Penyerahan Impact Report kepada mitra untuk transparansi ROI.</td>
                        </tr>
                      </table>
                    </div>
                  </div>
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
        :src="getAsset(visualAssetModules, 'visual_assets', 'ry5 1.webp')" 
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
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Grand Closing Concert.
                    </div>
                  </div>
                </div>
                <div class="md:col-span-8 font-body-md text-sm sm:text-base text-[#04000D]/80 leading-relaxed">
                  <p class="mb-4">
                    Malam puncak termegah yang memadukan pameran inovasi digital UMKM selama tiga hari penuh, konferensi inspiratif berlisensi TEDx, dan konser penutup spektakuler bersama SECRET GUEST STAR, dibalut dengan visual projection mapping bertema Simfoni Digital.
                  </p>
                  <div class="inline-flex items-center border-2 border-dashed border-[#04000D] p-2 bg-transparent gap-3 select-none">
                    <span class="font-mono text-xs text-[#04000D] font-extrabold uppercase tracking-widest">COMING SOON PRINT STAMP: [ SECRET GUEST STAR - TBA ]</span>
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
        :src="getAsset(visualAssetModules, 'visual_assets', 'sy5 1.webp')" 
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
        :src="getAsset(visualAssetModules, 'visual_assets', 'sg1 1.webp')" 
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

        <!-- Asymmetrical Organizational Blueprint Rows -->
        <div class="border-t-3 border-x-3 border-[#04000D] bg-white pb-0 mb-16 md:mb-24 select-none">
          
          <!-- ROW 1: NAKITA SEMESTA (Project Manager) -->
          <div class="grid grid-cols-1 md:grid-cols-[1.2fr_2.8fr] border-b-3 border-[#04000D] overflow-hidden group">
            <!-- Left Side Column (Color Ink Block) -->
            <div class="bg-[#D86BFF] p-6 md:p-8 flex flex-col justify-center items-center text-center border-b-3 md:border-b-0 border-[#04000D] transition-opacity duration-150 group-hover:opacity-95">
              <span class="font-mono text-xs tracking-widest border-b-2 border-[#04000D] pb-1 block w-full mb-6 font-bold text-[#04000D]">✦ PROJECT MANAGER ✦</span>
              <div class="w-16 h-16 md:w-20 md:h-20 bg-white border-[3px] border-[#04000D] shadow-[4px_4px_0px_0px_#04000D] flex items-center justify-center font-mono font-black text-xl md:text-2xl text-[#04000D] select-none uppercase tracking-wider rotate-[-2deg]">
                NS
              </div>
            </div>
            <!-- Right Side Column (The Dossier Block) -->
            <div class="bg-[#F5F5F5] p-6 md:p-8 flex flex-col justify-center md:border-l-3 border-[#04000D] transition-colors duration-150 group-hover:bg-white">
              <h3 class="font-black text-3xl md:text-5xl lg:text-6xl tracking-[-0.04em] uppercase leading-none text-[#04000D] riso-bleed">NAKITA SEMESTA</h3>
              <div class="mt-2 font-mono text-xs text-[#04000D]/60 flex flex-wrap items-center gap-1 select-none">
                <span>Instagram:</span>
                <a href="https://www.instagram.com/semestaaaa.__/" target="_blank" rel="noopener noreferrer" class="font-bold text-[#04000D] hover:text-[#D86BFF] transition-colors underline decoration-dashed pointer-events-auto">
                  @semestaaaa.__
                </a>
              </div>
              <div class="mt-4 border border-[#04000D]/20 bg-white/60 p-4 font-mono text-xs md:text-sm text-[#04000D]/80 leading-relaxed shadow-[3px_3px_0px_0px_rgba(4,0,13,0.05)] max-w-2xl">
                Mengawal eskalasi strategic partner, lobi eksternal, dan seluruh jalannya 10 divisi operasional.
              </div>
            </div>
          </div>

          <!-- ROW 2: DAREEAN A. RAFFI (PIC I-FEST 2026) - Reversed Layout -->
          <div class="grid grid-cols-1 md:grid-cols-[2.8fr_1.2fr] border-b-3 border-[#04000D] overflow-hidden group">
            <!-- Left Side Column (Color Ink Block) - Positioned on the Right on Desktop -->
            <div class="order-1 md:order-2 bg-[#D6FF00] p-6 md:p-8 flex flex-col justify-center items-center text-center border-b-3 md:border-b-0 border-[#04000D] transition-opacity duration-150 group-hover:opacity-95">
              <span class="font-mono text-xs tracking-widest border-b-2 border-[#04000D] pb-1 block w-full mb-6 font-bold text-[#04000D]">✦ PIC I-FEST 2026 ✦</span>
              <div class="w-16 h-16 md:w-20 md:h-20 bg-white border-[3px] border-[#04000D] shadow-[4px_4px_0px_0px_#04000D] flex items-center justify-center font-mono font-black text-xl md:text-2xl text-[#04000D] select-none uppercase tracking-wider rotate-[3deg]">
                DR
              </div>
            </div>
            <!-- Right Side Column (The Dossier Block) - Positioned on the Left on Desktop -->
            <div class="order-2 md:order-1 bg-[#F5F5F5] p-6 md:p-8 flex flex-col justify-center md:border-r-3 border-[#04000D] transition-colors duration-150 group-hover:bg-white">
              <h3 class="font-black text-3xl md:text-5xl lg:text-6xl tracking-[-0.04em] uppercase leading-none text-[#04000D] riso-bleed">DAREEAN A. RAFFI</h3>
              <div class="mt-2 font-mono text-xs text-[#04000D]/60 flex flex-wrap items-center gap-1 select-none">
                <span>Instagram:</span>
                <a href="https://www.instagram.com/darenrafi/" target="_blank" rel="noopener noreferrer" class="font-bold text-[#04000D] hover:text-[#8839FF] transition-colors underline decoration-dashed pointer-events-auto">
                  @darenrafi
                </a>
              </div>
              <div class="mt-4 border border-[#04000D]/20 bg-white/60 p-4 font-mono text-xs md:text-sm text-[#04000D]/80 leading-relaxed shadow-[3px_3px_0px_0px_rgba(4,0,13,0.05)] max-w-2xl">
                Arsitek administrasi, standarisasi birokrasi legal, dan timeline checklist Pleno umum panitia.
              </div>
            </div>
          </div>

          <!-- ROW 3: GABRIEL KRISTOFAN (Ketua Panitia) -->
          <div class="grid grid-cols-1 md:grid-cols-[1.2fr_2.8fr] border-b-3 border-[#04000D] overflow-hidden group">
            <!-- Left Side Column (Color Ink Block) -->
            <div class="bg-[#8839FF] p-6 md:p-8 flex flex-col justify-center items-center text-center border-b-3 md:border-b-0 border-[#04000D] transition-opacity duration-150 group-hover:opacity-95">
              <span class="font-mono text-xs tracking-widest border-b-2 border-[#D6FF00] pb-1 block w-full mb-6 font-bold text-[#D6FF00]">✦ KETUA PANITIA ✦</span>
              <div class="w-16 h-16 md:w-20 md:h-20 bg-white border-[3px] border-[#04000D] shadow-[4px_4px_0px_0px_#04000D] flex items-center justify-center font-mono font-black text-xl md:text-2xl text-[#04000D] select-none uppercase tracking-wider rotate-[-1deg]">
                GK
              </div>
            </div>
            <!-- Right Side Column (The Dossier Block) -->
            <div class="bg-[#F5F5F5] p-6 md:p-8 flex flex-col justify-center md:border-l-3 border-[#04000D] transition-colors duration-150 group-hover:bg-white">
              <h3 class="font-black text-3xl md:text-5xl lg:text-6xl tracking-[-0.04em] uppercase leading-none text-[#04000D] riso-bleed">GABRIEL KRISTOFAN</h3>
              <div class="mt-2 font-mono text-xs text-[#04000D]/60 flex flex-wrap items-center gap-1 select-none">
                <span>Instagram:</span>
                <a href="https://www.instagram.com/gabrielkristofansupari/" target="_blank" rel="noopener noreferrer" class="font-bold text-[#04000D] hover:text-[#D86BFF] transition-colors underline decoration-dashed pointer-events-auto">
                  @gabrielkristofansupari
                </a>
              </div>
              <div class="mt-4 border border-[#04000D]/20 bg-white/60 p-4 font-mono text-xs md:text-sm text-[#04000D]/80 leading-relaxed shadow-[3px_3px_0px_0px_rgba(4,0,13,0.05)] max-w-2xl">
                Memimpin eksekusi teknis lapangan, mengoordinasikan seluruh divisi kepanitiaan, dan memastikan kelancaran alur acara.
              </div>
            </div>
          </div>

          <!-- ROW 4: REYQAL SYAWALANO (Wakil Ketua Panitia) - Reversed Layout -->
          <div class="grid grid-cols-1 md:grid-cols-[2.8fr_1.2fr] border-b-3 border-[#04000D] overflow-hidden group">
            <!-- Left Side Column (Color Ink Block) - Positioned on the Right on Desktop -->
            <div class="order-1 md:order-2 bg-[#D86BFF] p-6 md:p-8 flex flex-col justify-center items-center text-center border-b-3 md:border-b-0 border-[#04000D] transition-opacity duration-150 group-hover:opacity-95">
              <span class="font-mono text-xs tracking-widest border-b-2 border-[#04000D] pb-1 block w-full mb-6 font-bold text-[#04000D]">✦ WAKIL KETUA PANITIA ✦</span>
              <div class="w-16 h-16 md:w-20 md:h-20 bg-white border-[3px] border-[#04000D] shadow-[4px_4px_0px_0px_#04000D] flex items-center justify-center font-mono font-black text-xl md:text-2xl text-[#04000D] select-none uppercase tracking-wider rotate-[2deg]">
                RS
              </div>
            </div>
            <!-- Right Side Column (The Dossier Block) - Positioned on the Left on Desktop -->
            <div class="order-2 md:order-1 bg-[#F5F5F5] p-6 md:p-8 flex flex-col justify-center md:border-r-3 border-[#04000D] transition-colors duration-150 group-hover:bg-white">
              <h3 class="font-black text-3xl md:text-5xl lg:text-6xl tracking-[-0.04em] uppercase leading-none text-[#04000D] riso-bleed">REYQAL SYAWALANO</h3>
              <div class="mt-2 font-mono text-xs text-[#04000D]/60 flex flex-wrap items-center gap-1 select-none">
                <span>Instagram:</span>
                <a href="https://www.instagram.com/reyqalsew/" target="_blank" rel="noopener noreferrer" class="font-bold text-[#04000D] hover:text-[#8839FF] transition-colors underline decoration-dashed pointer-events-auto">
                  @reyqalsew
                </a>
              </div>
              <div class="mt-4 border border-[#04000D]/20 bg-white/60 p-4 font-mono text-xs md:text-sm text-[#04000D]/80 leading-relaxed shadow-[3px_3px_0px_0px_rgba(4,0,13,0.05)] max-w-2xl">
                Mendampingi Ketua Panitia dalam pengawasan operasional harian, kontrol kualitas teknis divisi, dan manajemen mitigasi risiko.
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- SECTION J: "OUR NETWORK" / SPONSOR HIERARCHY (Placed right above the Footer) -->
    <section id="partners" class="bg-white riso-canvas py-16 sm:py-24 px-4 sm:px-6 md:px-lg border-t border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
      <!-- Background Decorative Stamp Shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'cat3 1.webp')" 
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
            
            <!-- UNTAD LOGO -->
            <div class="relative group flex flex-col items-center">
              <a 
                href="https://www.instagram.com/humasuntad/" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="opacity-70 group-hover:opacity-100 transition-opacity duration-200 cursor-pointer"
              >
                <img alt="UNTAD Logo" class="max-h-16 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'logo_untad.webp')" />
              </a>
              <!-- Brutalist Tooltip -->
              <div class="absolute bottom-full mb-4 left-1/2 -translate-x-1/2 flex flex-col items-center opacity-0 scale-90 pointer-events-none group-hover:opacity-100 group-hover:scale-100 transition-all duration-150 origin-bottom z-30">
                <div class="bg-[#04000D] text-[#D6FF00] border-2 border-[#04000D] px-3.5 py-1.5 text-[10px] md:text-xs font-mono font-bold uppercase tracking-wider rounded-none whitespace-nowrap shadow-[3px_3px_0px_0px_#FF3D8B]">
                  UNIVERSITAS TADULAKO
                </div>
                <div class="w-2.5 h-2.5 bg-[#04000D] rotate-45 -mt-1.5"></div>
              </div>
            </div>

            <!-- HMTI LOGO -->
            <div class="relative group flex flex-col items-center">
              <a 
                href="https://www.instagram.com/hmtiuntad/" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="opacity-70 group-hover:opacity-100 transition-opacity duration-200 cursor-pointer"
              >
                <img alt="HMTI Logo" class="max-h-16 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'HMTI LOGO.webp')" />
              </a>
              <!-- Brutalist Tooltip -->
              <div class="absolute bottom-full mb-4 left-1/2 -translate-x-1/2 flex flex-col items-center opacity-0 scale-90 pointer-events-none group-hover:opacity-100 group-hover:scale-100 transition-all duration-150 origin-bottom z-30">
                <div class="bg-[#04000D] text-[#D6FF00] border-2 border-[#04000D] px-3.5 py-1.5 text-[10px] md:text-xs font-mono font-bold uppercase tracking-wider rounded-none whitespace-nowrap shadow-[3px_3px_0px_0px_#FF3D8B]">
                  HIMPUNAN MAHASISWA TEKNIK INFORMATIKA - UNTAD
                </div>
                <div class="w-2.5 h-2.5 bg-[#04000D] rotate-45 -mt-1.5"></div>
              </div>
            </div>

            <!-- HMTI CABINET LOGO -->
            <div class="relative group flex flex-col items-center">
              <a 
                href="https://www.instagram.com/hmtiuntad/" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="opacity-70 group-hover:opacity-100 transition-opacity duration-200 cursor-pointer"
              >
                <img alt="HMTI Cabinet Logo" class="max-h-16 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'all blue.webp')" />
              </a>
              <!-- Brutalist Tooltip -->
              <div class="absolute bottom-full mb-4 left-1/2 -translate-x-1/2 flex flex-col items-center opacity-0 scale-90 pointer-events-none group-hover:opacity-100 group-hover:scale-100 transition-all duration-150 origin-bottom z-30">
                <div class="bg-[#04000D] text-[#D6FF00] border-2 border-[#04000D] px-3.5 py-1.5 text-[10px] md:text-xs font-mono font-bold uppercase tracking-wider rounded-none whitespace-nowrap shadow-[3px_3px_0px_0px_#FF3D8B]">
                   KABINET ALLBLUE - HMTI UNTAD
                </div>
                <div class="w-2.5 h-2.5 bg-[#04000D] rotate-45 -mt-1.5"></div>
              </div>
            </div>

            <!-- RINOYA LOGO -->
            <div class="relative group flex flex-col items-center">
              <a 
                href="https://www.instagram.com/rinoya.hmtiuntad/" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="opacity-70 group-hover:opacity-100 transition-opacity duration-200 cursor-pointer"
              >
                <img alt="RINOYA Logo" class="max-h-16 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'Logo_Inovasi_dan_Karya__RINOYA(removebg).webp')" />
              </a>
              <!-- Brutalist Tooltip -->
              <div class="absolute bottom-full mb-4 left-1/2 -translate-x-1/2 flex flex-col items-center opacity-0 scale-90 pointer-events-none group-hover:opacity-100 group-hover:scale-100 transition-all duration-150 origin-bottom z-30">
                <div class="bg-[#04000D] text-[#D6FF00] border-2 border-[#04000D] px-3.5 py-1.5 text-[10px] md:text-xs font-mono font-bold uppercase tracking-wider rounded-none whitespace-nowrap shadow-[3px_3px_0px_0px_#FF3D8B]">
                  DEPARTEMEN RINOYA - HMTI UNTAD
                </div>
                <div class="w-2.5 h-2.5 bg-[#04000D] rotate-45 -mt-1.5"></div>
              </div>
            </div>

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
              <a 
                :href="mainStrategicPartner.instagram" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="cursor-pointer flex justify-center items-center"
              >
                <img :alt="mainStrategicPartner.name" class="w-full max-w-[360px] h-auto object-contain mix-blend-multiply filter contrast-125" :src="mainStrategicPartner.src" />
              </a>
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
                <!-- Render image with instagram link if present, otherwise render normally -->
                <a 
                  v-if="partner.instagram"
                  :href="partner.instagram"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="cursor-pointer w-full flex justify-center items-center"
                >
                  <img
                    :alt="partner.name"
                    :class="partner.logoMaxWidth"
                    class="w-full h-14 md:h-16 object-contain mix-blend-multiply filter contrast-125 transition-all duration-300 hover:opacity-85"
                    :src="partner.src"
                  />
                </a>
                <img
                  v-else
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
              <a
                v-for="partner in mediaPartners"
                :key="partner.name"
                :href="partner.instagram || '#'"
                :target="partner.instagram ? '_blank' : '_self'"
                rel="noopener noreferrer"
                class="relative group border border-[#04000D]/10 p-3 md:p-4 flex items-center justify-center opacity-70 group-hover:opacity-100 transition-opacity duration-200 bg-off-white/30 min-h-[72px] md:min-h-[88px] rounded-lg cursor-pointer"
              >
                <!-- Render image if partner.src is present, otherwise show fallback text -->
                <img 
                  v-if="partner.src"
                  :alt="partner.name" 
                  class="max-h-10 md:max-h-16 w-auto object-contain mx-auto filter contrast-125 grayscale transition-all duration-300 group-hover:filter-none" 
                  :src="partner.src" 
                />
                <span v-else class="font-mono text-[10px] md:text-xs font-bold text-[#04000D]/60 tracking-wider text-center px-1">{{ partner.name }}</span>

                <!-- Brutalist Tooltip -->
                <div class="absolute bottom-full mb-4 left-1/2 -translate-x-1/2 flex flex-col items-center opacity-0 scale-90 pointer-events-none group-hover:opacity-100 group-hover:scale-100 transition-all duration-150 origin-bottom z-30">
                  <div class="bg-[#04000D] text-[#D6FF00] border-2 border-[#04000D] px-3.5 py-1.5 text-[10px] md:text-xs font-mono font-bold uppercase tracking-wider rounded-none text-center max-w-[180px] md:max-w-[240px] break-words shadow-[3px_3px_0px_0px_#FF3D8B]">
                    {{ partner.name }}
                  </div>
                  <div class="w-2.5 h-2.5 bg-[#04000D] rotate-45 -mt-1.5"></div>
                </div>
              </a>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- SECTION G: Footer Section (Deep Midnight Canvas) -->
    <footer class="w-full bg-[#04000D] text-[#F5F5F5] py-12 md:py-16 px-6 md:px-lg border-t border-dashed border-[#F5F5F5]/25 relative overflow-hidden select-none">
      <div class="max-w-container-max mx-auto relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 items-start">
          
          <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-2">
              <span class="font-headline-lg text-3xl sm:text-4xl font-bold text-[#D6FF00] riso-text-shadow-double-dark riso-bleed">I-FEST 2026</span>
            </div>
            
            <!-- Bottom-Left Institutional Logo flex block -->
            <div class="flex flex-col gap-3">
              <span class="font-mono text-[10px] md:text-xs font-bold uppercase tracking-wider text-[#F5F5F5]/60">ORGANIZED BY HMTI UNIVERSITAS TADULAKO</span>
              <div class="flex flex-row flex-wrap items-center gap-4 md:gap-6">
                <a href="https://www.instagram.com/humasuntad/" target="_blank" rel="noopener noreferrer">
                  <img alt="UNTAD Logo" class="h-8 md:h-10 w-auto object-contain opacity-80 filter invert grayscale contrast-125 transition-all duration-300 hover:filter-none hover:opacity-100 cursor-pointer" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'logo_untad.webp')" />
                </a>
                <a href="https://www.instagram.com/hmtiuntad/" target="_blank" rel="noopener noreferrer">
                  <img alt="HMTI Logo" class="h-8 md:h-10 w-auto object-contain opacity-80 filter invert grayscale contrast-125 transition-all duration-300 hover:filter-none hover:opacity-100 cursor-pointer" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'HMTI LOGO.webp')" />
                </a>
                <a href="https://www.instagram.com/hmtiuntad/" target="_blank" rel="noopener noreferrer">
                  <img alt="HMTI Cabinet Logo" class="h-8 md:h-10 w-auto object-contain opacity-90 filter brightness-0 invert transition-all duration-300 hover:filter-none hover:opacity-100 cursor-pointer" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'all blue.webp')" />
                </a>
                <a href="https://www.instagram.com/rinoya.hmtiuntad/" target="_blank" rel="noopener noreferrer">
                  <img alt="RINOYA Logo" class="h-8 md:h-10 w-auto object-contain opacity-90 filter brightness-0 invert transition-all duration-300 hover:filter-none hover:opacity-100 cursor-pointer" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'Logo_Inovasi_dan_Karya__RINOYA(removebg).webp')" />
                </a>
              </div>
            </div>
          </div>
          
          <div class="flex flex-col md:items-end md:text-right gap-4">
            <p class="font-body-md text-sm md:text-base opacity-80">Ingin menjadi bagian dari simfoni ini?</p>
            <p class="font-headline-lg text-lg md:text-xl font-medium">Sponsorship &amp; Kemitraan: <br class="md:hidden" /><a href="https://wa.me/6282195432152?text=Halo%20Fauzi%2C%20saya%20%5BNama%20Anda%5D%20dari%20%5BNama%20Perusahaan%2FInstansi%5D.%20Kami%20tertarik%20untuk%20mengetahui%20lebih%20lanjut%20mengenai%20peluang%20kerja%20sama%20dan%20paket%20sponsorship%20di%20I-FEST%202026.%20Boleh%20kami%20meminta%20berkas%20Proposal%20Umum%20terbaru%3F%20Terima%20kasih%21" target="_blank" rel="noopener noreferrer" class="text-[#D6FF00] hover:underline transition-all duration-200">Fauzi (+62 821-9543-2152)</a></p>
            
            <div class="font-mono text-sm tracking-wider mt-1">
              <span class="opacity-75">Instagram:</span>
              <a href="https://www.instagram.com/ifest_untad" target="_blank" rel="noopener noreferrer" class="ml-2 inline-block border border-[#F5F5F5] px-2.5 py-0.5 bg-[#F5F5F5] text-[#04000D] font-bold opacity-80 hover:opacity-100 transition-opacity duration-200">
                @fest_untad
              </a>
            </div>
          </div>
          
        </div>

        <!-- Divider line & copyright -->
        <div class="border-t border-[#F5F5F5]/10 mt-8 md:mt-12 pt-6 md:pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
          <p class="font-caption text-[10px] opacity-40">© 2026 I-FEST. All rights reserved. Orchestrated with passion in Central Sulawesi.</p>
        </div>
      </div>
    </footer>
  </div>

  <!-- Mobile Drawer Menu Overlay -->
  <div v-if="isMenuOpen" class="fixed inset-0 z-[80] w-full h-screen bg-[#F5F5F5] overflow-y-auto p-8 pt-24 md:hidden">
    <div class="absolute inset-0 bg-[radial-gradient(#04000D_1px,transparent_1px)] [background-size:16px_16px] opacity-[0.04] pointer-events-none z-0"></div>
    <div class="absolute inset-0 bg-noise-grain opacity-[0.03] pointer-events-none z-0"></div>
    
    <div class="flex flex-col items-center justify-center min-h-[calc(100vh-96px)] gap-y-6 relative z-10">
      <nav class="flex flex-col items-center gap-8 text-center">
        <a @click="toggleMenu" class="font-mono text-2xl font-bold text-[#04000D] border-b-2 border-dashed border-[#FF3D8B] pb-1 hover:text-accent-magenta" href="#roadshow">Roadshow</a>
        <a @click="toggleMenu" class="font-mono text-2xl font-bold text-[#04000D] border-b-2 border-dashed border-[#D6FF00] pb-1 hover:text-accent-magenta" href="#kompetisi">Kompetisi</a>
        <a @click="toggleMenu" class="font-mono text-2xl font-bold text-[#04000D] border-b-2 border-dashed border-[#8839FF] pb-1 hover:text-accent-magenta" href="#timeline">Timeline</a>
        <a @click="toggleMenu" class="font-mono text-2xl font-bold text-[#04000D] border-b-2 border-dashed border-[#D86BFF] pb-1 hover:text-accent-magenta" href="#galeri-jejak-langkah">Arsip 2025</a>
        <a @click="toggleMenu" class="font-mono text-2xl font-bold text-[#04000D] border-b-2 border-dashed border-[#04000D]/30 pb-1 hover:text-accent-magenta" href="#partners">Network</a>
        
        <a @click="toggleMenu" href="#kompetisi" class="riso-btn-plate bg-[#04000D] text-white px-8 py-3 rounded-full font-button text-base font-bold mt-8 text-center inline-block" style="--plate-color: #FF3D8B;">
          DAFTAR KOMPETISI
        </a>
      </nav>
    </div>
  </div>

  <!-- SECTION A: Top Navigation Chrome -->
  <header class="fixed inset-x-0 top-0 z-[90] w-full border-b border-dashed border-[#04000D]/30 bg-off-white/95 backdrop-blur-sm">
    <div class="max-w-container-max mx-auto flex justify-between items-center px-3 sm:px-4 md:px-lg py-3 md:py-sm">
      
      <!-- Logo Flex Container with UNTAD -> HMTI -> I-FEST -->
      <div class="flex items-center gap-2 md:gap-4 select-none">
        <div class="flex items-center gap-1.5 md:gap-3">
          <img alt="UNTAD Logo" class="h-7 md:h-10 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'logo_untad.webp')" />
          <img alt="HMTI Logo" class="h-7 md:h-10 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'HMTI LOGO.webp')" />
          <img alt="I-FEST Logo" class="h-7 md:h-10 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'Logo-IFEST-2026.webp')" />
        </div>
        <span class="hidden sm:inline-block font-mono text-base md:text-lg font-bold tracking-widest text-[#04000D] border-l border-[#04000D]/20 pl-3 md:pl-4 riso-bleed">I-FEST 2026</span>
      </div>

      <nav class="hidden md:flex items-center gap-3 lg:gap-6 xl:gap-8 select-none">
        <a class="font-mono text-xs lg:text-[13px] xl:text-sm font-bold uppercase tracking-wider text-[#04000D]/70 hover:text-accent-magenta transition-colors duration-200" href="#roadshow">Roadshow</a>
        <a class="font-mono text-xs lg:text-[13px] xl:text-sm font-bold uppercase tracking-wider text-[#04000D]/70 hover:text-accent-magenta transition-colors duration-200" href="#kompetisi">Kompetisi</a>
        <a class="font-mono text-xs lg:text-[13px] xl:text-sm font-bold uppercase tracking-wider text-[#04000D]/70 hover:text-accent-magenta transition-colors duration-200" href="#timeline">Timeline</a>
        <a class="font-mono text-xs lg:text-[13px] xl:text-sm font-bold uppercase tracking-wider text-[#04000D]/70 hover:text-accent-magenta transition-colors duration-200" href="#galeri-jejak-langkah">Arsip 2025</a>
        <a class="font-mono text-xs lg:text-[13px] xl:text-sm font-bold uppercase tracking-wider text-[#04000D]/70 hover:text-accent-magenta transition-colors duration-200" href="#partners">Network</a>
      </nav>

      <div class="flex items-center gap-2 select-none">
        <a href="#kompetisi" class="riso-btn-plate bg-[#04000D] text-white px-4 lg:px-lg py-2 rounded-full font-button text-xs lg:text-sm select-none font-bold text-center inline-block" style="--plate-color: #FF3D8B;">
          DAFTAR KOMPETISI
        </a>
        <button @click="toggleMenu" class="p-1.5 flex items-center justify-center border border-[#04000D] rounded bg-white hover:bg-off-white md:hidden transition-colors" aria-label="Toggle menu">
          <span class="material-symbols-outlined text-xl text-[#04000D] font-bold">
            {{ isMenuOpen ? 'close' : 'menu' }}
          </span>
        </button>
      </div>
    </div>
  </header>

  <!-- AI CHAT ASSISTANT FLOATING WIDGET -->
  <template v-if="isChatActivated">
    <AiChatWidget @close="isChatActivated = false" />
  </template>
  <div v-else class="fixed bottom-6 right-6 z-50 flex flex-col items-end">
    <!-- The Trigger Floating Button -->
    <button 
      @click="isChatActivated = true" 
      class="riso-btn-plate w-14 h-14 bg-[#04000D] text-white rounded-full flex items-center justify-center relative transition-transform duration-200 active:scale-95 group" 
      style="--plate-color: #D6FF00;"
      aria-label="Open Assistant"
    >
      <!-- Pulse Indicator Overlay -->
      <span class="absolute -top-0.5 -right-0.5 flex h-3.5 w-3.5">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FF3D8B] opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-[#FF3D8B]"></span>
      </span>
      
      <!-- SVG Monochromatic Robot Icon -->
      <svg 
        xmlns="http://www.w3.org/2000/svg" 
        viewBox="0 0 24 24" 
        fill="none" 
        stroke="currentColor" 
        stroke-width="2" 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        class="w-6 h-6 transition-transform duration-300 group-hover:scale-110"
      >
        <path d="M12 8V4H8"/>
        <rect width="16" height="12" x="4" y="8" rx="2"/>
        <path d="M2 14h2"/>
        <path d="M20 14h2"/>
        <path d="M15 13v2"/>
        <path d="M9 13v2"/>
      </svg>
    </button>
  </div>
</template>
