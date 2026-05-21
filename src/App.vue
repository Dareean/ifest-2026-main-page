<script setup>
import { onBeforeUnmount, onMounted } from 'vue'

let observer = null

const visualAssetModules = import.meta.glob('./assets/visual_assets/*', {
  eager: true,
  import: 'default',
})

const mediaPartnerAssetModules = import.meta.glob('./assets/medpart/*', {
  eager: true,
  import: 'default',
})

const getAsset = (assetModules, folder, fileName) => assetModules[`./assets/${folder}/${fileName}`] ?? ''

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

onMounted(() => {
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
  if (observer) {
    observer.disconnect()
    observer = null
  }
})
</script>

<template>
  <div class="riso-canvas bg-off-white min-h-screen text-[#04000D] font-body-md select-text pb-12">
    
    <!-- SECTION A: Top Navigation Chrome -->
    <header class="fixed inset-x-0 top-0 z-[60] w-full border-b border-dashed border-[#04000D]/30 bg-off-white/95 backdrop-blur-sm">
      <div class="max-w-container-max mx-auto flex justify-between items-center px-6 md:px-lg py-sm">
        
        <!-- Logo Flex Container with UNTAD -> HMTI -> I-FEST -->
        <div class="flex items-center gap-3 md:gap-4 select-none">
          <div class="flex items-center gap-2 md:gap-3">
            <img alt="UNTAD Logo" class="h-8 md:h-10 w-auto object-contain" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBd9JaJvIDLTJQdFS5d5yoFAZhSrpqKx63XEw7xCSKOm74lLtdU7kFK8ry0-Ffl5DbiBue6L4B4xtlUckPSdKjoZyuI7OR1TDF8IBHoZ22Alpy3qSoDMOU4jBsLRryl0OpL6y7dkWz0jqk2e81gapQ2adZ2CA3wQpHL7HM_1GKVHFoQjipIh7lrKUqAnSS95Z7EDvmvaaqbECsPMA-t8NlAdCGaZAYC1BwZfMj6SqvKCFYVX7TSL-GVeaQhcVzHHag4WrtBwMhMl8Y" />
            <img alt="HMTI Logo" class="h-8 md:h-10 w-auto object-contain" src="https://lh3.googleusercontent.com/aida-public/AB6AXuABJQXQNNm9vUDNgESijKQHQ4U9jj90CMt1VQQNobIUggoIV7qe3RWu9pl7xaUtM30XL3nlxN9LXj2m6ZdK3YqXhVubUo-eFJYaN9m5nMPGqVVmq9qytOgHyfsPZrxl3YKRMlCp4pJ1cAFuzOPsMTry9CbeeZonCM9VQZ_xlnfRkZjtdnAUfoQez_mjYwOJUDiolihm56ECyziYVTYkfAeAQLh7wP0b3owa1QqIcYZaporMHHIYGa_lDzufxsDHWj0Z1ZxT-e42LZk" />
            <img alt="I-FEST Logo" class="h-8 md:h-10 w-auto object-contain" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDhnw57bnP_MNSx7W9XMJ7qaxRXVmnex8RLUI13x_Rpl1xN4M7pXCy5pN_ZdOPnIHJTNN4kZSjT9DqzG_ee4l1CDfv44v8r4YlgZSciIPTAE-bXMwsc7jgnuUtS7p16r6Sh2cX5JKDsmjgzh6kEC0nC-x7DpfKeaJ7IZRE5nmVnbATSzj2k_2KAH4AIVzmjl6oj_exmAveMvGzDaZm9qPAKFAmAXmp2B8CsY_frqmD4LafhTT9wt45x5dHwqMVf9bWGHPMYK-whYig" />
          </div>
          <span class="font-mono text-base md:text-lg font-bold tracking-widest text-[#04000D] border-l border-[#04000D]/20 pl-3 md:pl-4 riso-bleed">I-FEST 2026</span>
        </div>

        <nav class="hidden md:flex items-center space-x-xl select-none">
          <a class="font-body-md text-body-md text-[#04000D] font-bold border-b border-[#04000D] pb-0.5 hover:text-accent-magenta transition-colors duration-200" href="#">Roadshow</a>
          <a class="font-body-md text-body-md text-[#04000D]/75 hover:text-accent-magenta transition-colors duration-200" href="#">Kompetisi</a>
          <a class="font-body-md text-body-md text-[#04000D]/75 hover:text-accent-magenta transition-colors duration-200" href="#">Konser</a>
          <a class="font-body-md text-body-md text-[#04000D]/75 hover:text-accent-magenta transition-colors duration-200" href="#">FAQ</a>
        </nav>

        <div class="flex items-center">
          <button class="riso-btn-plate bg-[#04000D] text-white px-6 md:px-xl py-2 md:py-xs rounded-full font-button text-sm md:text-button select-none font-bold" style="--plate-color: #FF3D8B;">
            AMANKAN TIKET
          </button>
        </div>
      </div>
    </header>

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
          <p class="font-mono text-[#FF3D8B] text-xs md:text-base tracking-[0.22em] uppercase mb-md font-bold select-none riso-bleed">
            THE BIGGEST IT FESTIVAL IN EASTERN INDONESIA
          </p>
          
          <!-- Massive Condensed Typography with Magenta Misregistration Plate -->
          <h1 class="font-bold text-[#04000D] text-5xl md:text-8xl lg:text-[120px] tracking-tighter leading-[0.85] mb-lg max-w-5xl riso-text-shadow-magenta riso-bleed">
            DIGITAL SYMPHONY
          </h1>
          
          <p class="font-body-md text-base md:text-xl text-[#04000D]/80 max-w-2xl mb-xl leading-relaxed mx-auto">
            Mengorkestrasi Inovasi Global untuk Masa Depan Berkelanjutan. HMTI Universitas Tadulako memanggil 8.000+ inovator untuk bergabung dalam revolusi digital terbesar di Sulawesi Tengah.
          </p>
          
          <button class="riso-btn-plate bg-[#04000D] text-white px-8 md:px-xl py-3 md:py-md rounded-full font-button text-button select-none font-bold" style="--plate-color: #D6FF00;">
            EXPLORE THE SYMPHONY ↓
          </button>
        </div>
      </div>
      
      <!-- Stamped Strategic Partners Ribbon -->
      <div class="absolute bottom-0 left-0 w-full bg-[#04000D] border-t border-dashed border-white/20 py-4 md:py-6 overflow-hidden z-20 select-none">
        <div class="flex whitespace-nowrap animate-marquee items-center">
          <div class="flex items-center space-x-12 px-6">
            <img alt="Hannah Asa" class="h-8 w-auto object-contain filter brightness-0 invert" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCNkv2nd4oC9evrxjVmiOkoGsdrMgyx1WzIFR8RNd8t-yWV9HI-nei0WOalBBUTc1hiC7pKH0O_obesU7kLqOHztF15mrQzt-8n3miZeyqKA_Epnwd-rG2WJ00QhfmTZlK7tkjPKiXZjnzziuBZQvuRAYcrqSmYeWGt_O9XQb-TWSdQ3WcPJBJai5H3175kPrG0C4kdSc6Xr9yt3_LVKxFPm2se5kZEHmncC9eT_wh-otjtzmHlcre2bU3WiBdW4nX4QjQQqWcqsV0" />
            <span class="font-mono text-white text-sm md:text-lg uppercase tracking-widest font-bold">MAIN STRATEGIC PARTNER: HANNAH ASA INDONESIA</span>
            <img alt="Sultan Music" class="h-8 w-auto object-contain filter brightness-0 invert" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAUrrspETTLMK_VWe8VMHGBKJ6bqIsQJ3FkWj5f15jvn5c0yhB9ha8XFj6SDveCWLAAn7dJWPKtWDsFtKkoOwkQRaGLLT_LJDlgAyiX0aiqV8p4JGKTJJkXM7nZKcts1sDujhb-4pqv_Lp1yFsSoQRtIKp-smllKQITUBNB886zrZMdL0MEpRWbEuE56u6RsEeeA65EY0PV3cxhHo_wM_QZ-c6WvZYnZjtSaeTAoJlZKq-DpKLj4fnbLb0gZKC_mMq2DiGIQdc8cKg" />
            <span class="font-mono text-white text-sm md:text-lg uppercase tracking-widest font-bold">STRATEGIC PARTNER: SULTAN MUSIC</span>
          </div>
          <div class="flex items-center space-x-12 px-6">
            <img alt="Hannah Asa" class="h-8 w-auto object-contain filter brightness-0 invert" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCNkv2nd4oC9evrxjVmiOkoGsdrMgyx1WzIFR8RNd8t-yWV9HI-nei0WOalBBUTc1hiC7pKH0O_obesU7kLqOHztF15mrQzt-8n3miZeyqKA_Epnwd-rG2WJ00QhfmTZlK7tkjPKiXZjnzziuBZQvuRAYcrqSmYeWGt_O9XQb-TWSdQ3WcPJBJai5H3175kPrG0C4kdSc6Xr9yt3_LVKxFPm2se5kZEHmncC9eT_wh-otjtzmHlcre2bU3WiBdW4nX4QjQQqWcqsV0" />
            <span class="font-mono text-white text-sm md:text-lg uppercase tracking-widest font-bold">MAIN STRATEGIC PARTNER: HANNAH ASA INDONESIA</span>
            <img alt="Sultan Music" class="h-8 w-auto object-contain filter brightness-0 invert" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAUrrspETTLMK_VWe8VMHGBKJ6bqIsQJ3FkWj5f15jvn5c0yhB9ha8XFj6SDveCWLAAn7dJWPKtWDsFtKkoOwkQRaGLLT_LJDlgAyiX0aiqV8p4JGKTJJkXM7nZKcts1sDujhb-4pqv_Lp1yFsSoQRtIKp-smllKQITUBNB886zrZMdL0MEpRWbEuE56u6RsEeeA65EY0PV3cxhHo_wM_QZ-c6WvZYnZjtSaeTAoJlZKq-DpKLj4fnbLb0gZKC_mMq2DiGIQdc8cKg" />
            <span class="font-mono text-white text-sm md:text-lg uppercase tracking-widest font-bold">STRATEGIC PARTNER: SULTAN MUSIC</span>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION C: Intro (Tactile Text Section) -->
    <section class="bg-off-white riso-canvas py-[80px] md:py-[110px] px-6 md:px-lg border-b border-dashed border-[#04000D]/20" data-reveal>
      <div class="max-w-container-max mx-auto text-center">
        <h2 class="font-bold text-3xl md:text-5xl text-[#04000D] tracking-tighter mb-lg riso-text-shadow-magenta riso-bleed">
          Bukan Sekadar Festival. Ini Adalah Episentrum Perubahan.
        </h2>
        <p class="font-body-lg text-base md:text-xl text-[#04000D]/85 max-w-3xl mx-auto leading-relaxed">
          Disrupsi kecerdasan buatan dan teknologi bergerak lebih cepat dari sebelumnya. I-FEST 2026 hadir untuk menjembatani digital divide, mempertemukan talenta akar rumput dengan standar industri nasional. Dari ruang kelas pedalaman hingga panggung konser raksasa, mari ciptakan resonansi yang nyata.
        </p>
      </div>
    </section>

    <!-- SECTION D: Phase 01: Inclusivity (Interactive Magenta Misregistration Card Plate) -->
    <section class="bg-off-white riso-canvas py-12 md:py-[80px] px-4 md:px-lg" data-reveal>
      <div class="max-w-container-max mx-auto">
        <div class="riso-card-plate bg-[#DCEEB1] border-riso-sketchy-lg p-8 md:p-[64px] text-[#04000D]" style="--plate-color: #FF3D8B;">
          <p class="font-mono text-xs md:text-sm uppercase tracking-widest font-bold text-[#FF3D8B]">PHASE 01: INCLUSIVITY</p>
          <h2 class="font-bold text-2xl md:text-4xl tracking-tight mt-4 riso-bleed">Inklusi Tanpa Batas. Menyentuh yang Tak Tersentuh.</h2>
          <p class="text-base md:text-lg mt-6 max-w-2xl leading-relaxed text-[#04000D]/90">
            Berkolaborasi dengan Hannah Asa Indonesia, Digital Symphony Tour bergerak menuju 25 titik. Kami membawa literasi siber, AI, dan aksesibilitas teknologi ke sekolah umum, SLB, dan desa-desa terpencil di Palu, Sigi, dan Donggala. Karena teknologi adalah hak semua orang.
          </p>
          <div class="mt-12 pt-6 border-t border-[#04000D]/10 font-mono text-xs md:text-sm uppercase font-bold text-[#04000D]/70">
            25 TITIK ROADSHOW | 2.500+ PELAJAR
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION E: Phase 02: Incubation (Interactive Lime Misregistration Card Plate) -->
    <section class="bg-off-white riso-canvas py-12 md:py-[80px] px-4 md:px-lg" data-reveal>
      <div class="max-w-container-max mx-auto">
        <div class="riso-card-plate bg-[#D86BFF] border-riso-sketchy-lg p-8 md:p-[64px] text-[#04000D]" style="--plate-color: #D6FF00;">
          <p class="font-mono text-xs md:text-sm uppercase tracking-widest font-bold text-[#04000D]/80">PHASE 02: INCUBATION</p>
          <h2 class="font-bold text-2xl md:text-4xl tracking-tight mt-4 riso-bleed">Arena Pembuktian Talenta Digital.</h2>
          <p class="text-base md:text-lg mt-6 max-w-2xl leading-relaxed text-[#04000D]/90">
            Waktunya membawa ide Anda ke permukaan. Berkompetisilah di tingkat nasional dalam UI/UX Design, Competitive Programming, dan Business Plan. Khusus untuk inovator lokal, Sulteng Digital Innovation Hub (S-DIH) Hackathon menanti solusi nyata Anda untuk masalah Agri-Tech dan Fin-Tech daerah.
          </p>
          <div class="mt-12">
            <button class="riso-btn-plate bg-[#04000D] text-white px-8 md:px-xl py-3 md:py-md rounded-full font-button text-button font-bold" style="--plate-color: #ffffff;">
              DOWNLOAD RULEBOOK LOMBA
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION F: Phase 03: Harmony (Interactive Lilac Misregistration Card Plate) -->
    <section class="bg-off-white riso-canvas py-12 md:py-[80px] px-4 md:px-lg" data-reveal>
      <div class="max-w-container-max mx-auto">
        <div class="riso-card-plate bg-[#8839FF] border-riso-sketchy-lg p-8 md:p-[64px] text-white" style="--plate-color: #FF3D8B;">
          <p class="font-mono text-xs md:text-sm uppercase tracking-widest font-bold text-[#D6FF00] riso-bleed">PHASE 03: HARMONY</p>
          <h2 class="font-bold text-3xl md:text-5xl tracking-tight mt-4 riso-bleed">Akhir Sebuah Simfoni. Bersama TULUS.</h2>
          <p class="text-base md:text-lg mt-6 max-w-2xl leading-relaxed opacity-95">
            Tiga hari puncak yang tak akan terlupakan. Jelajahi etalase inovasi di Expo UMKM, serap inspirasi dari panggung TEDx, dan rayakan puncak orkestrasi digital ini bersama 8.000 suara lainnya dalam Grand Closing Concert bersama solois terbaik tanah air, TULUS.
          </p>
          <div class="mt-12">
            <button class="riso-btn-plate bg-[#D6FF00] text-[#04000D] px-8 md:px-xl py-3 md:py-md rounded-full font-button text-button font-bold" style="--plate-color: #ffffff;">
              BELI TIKET KONSER (SOON)
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION H: Main Grid Sections -->
    <main class="py-[64px] md:py-section-gap min-h-screen px-6 md:px-lg max-w-container-max mx-auto relative z-10">
      <section class="w-full flex flex-col items-center justify-center text-center py-xl" data-reveal>
        <span class="font-eyebrow text-sm md:text-eyebrow text-accent-magenta mb-sm uppercase tracking-widest font-bold">Digital Arts &amp; Future Music</span>
        
        <!-- Double Shift Riso text shadow -->
        <h2 class="font-display-xl text-4xl md:text-display-xl text-[#04000D] max-w-4xl mb-lg leading-tight riso-text-shadow-double riso-bleed">
          Vibrant. Intellectual. <br class="hidden md:block" /> Celebratory.
        </h2>
        <p class="font-body-lg text-base md:text-body-lg text-[#04000D]/80 max-w-2xl mb-xl">
          A digital tribute to the raw energy of Risograph print culture. Join the intellectual movement at I-FEST 2026.
        </p>
        
        <!-- Tactile 3D-effect Card Grid with snapy hover interactions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-lg w-full mt-12 md:mt-section-gap select-none">
          <div class="riso-card-plate bg-block-lilac border-riso-sketchy-md aspect-square p-8 md:p-xl flex flex-col justify-end group cursor-pointer text-[#04000D] font-bold" style="--plate-color: #FF3D8B;">
            <span class="material-symbols-outlined text-4xl mb-md">brush</span>
            <h3 class="font-headline-lg text-xl md:text-headline-lg">MAIN STRATEGIC PARTNER</h3>
          </div>
          <div class="riso-card-plate bg-block-mint border-riso-sketchy-md aspect-square p-8 md:p-xl flex flex-col justify-end group cursor-pointer text-[#04000D] font-bold" style="--plate-color: #D6FF00;">
            <span class="material-symbols-outlined text-4xl mb-md">stadium</span>
            <h3 class="font-headline-lg text-xl md:text-headline-lg">STRATEGIC PARTNER</h3>
          </div>
          <div class="riso-card-plate bg-block-coral border-riso-sketchy-md aspect-square p-8 md:p-xl flex flex-col justify-end group cursor-pointer text-[#04000D] font-bold" style="--plate-color: #8839FF;">
            <span class="material-symbols-outlined text-4xl mb-md">auto_awesome</span>
            <h3 class="font-headline-lg text-xl md:text-headline-lg">Exhibition</h3>
          </div>
        </div>
      </section>

      <div class="h-16 md:h-section-gap"></div>

      <!-- SECTION I: Info Overlay Card -->
      <section class="riso-card-plate bg-surface-container border-riso-sketchy-lg p-8 md:p-xl mb-12 md:mb-section-gap" style="--plate-color: #D6FF00;" data-reveal>
        <div class="flex flex-col md:flex-row gap-8 md:gap-xl items-center">
          <div class="flex-1">
            <h2 class="font-display-lg text-4xl md:text-display-lg text-[#04000D] mb-md leading-tight riso-bleed">
              Curating the <span class="text-accent-magenta italic">Unexpected</span>
            </h2>
            <p class="font-body-md text-base md:text-body-md text-[#04000D]/80">
              Our 2026 theme explores the intersection of algorithmic precision and human error, mimicking the tactile imperfections of ink on paper.
            </p>
          </div>
          
          <!-- Inner card styled with sketchy dashed lines -->
          <div class="w-full md:w-1/3 bg-white border-riso-sketchy-md p-6 md:p-lg">
            <div class="flex flex-col gap-sm">
              <div class="flex justify-between items-center border-b border-dashed border-[#04000D]/10 pb-sm">
                <span class="font-caption text-caption text-[#04000D]/60">DATE</span>
                <span class="font-body-md text-body-md font-bold text-[#04000D]">12-14 OCT</span>
              </div>
              <div class="flex justify-between items-center border-b border-dashed border-[#04000D]/10 pb-sm">
                <span class="font-caption text-caption text-[#04000D]/60">VENUE</span>
                <span class="font-body-md text-body-md font-bold text-[#04000D]">PALU, SULTENG</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="font-caption text-caption text-[#04000D]/60">STATUS</span>
                <span class="inline-flex items-center px-xs py-[2px] bg-block-lime border border-[#04000D]/20 rounded-full font-eyebrow text-[10px] text-[#04000D] font-bold">EARLY BIRD</span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- SECTION J: "OUR NETWORK" / SPONSOR HIERARCHY (Placed right above the Footer) -->
    <section id="partners" class="bg-white riso-canvas py-24 px-6 md:px-lg border-t border-dashed border-[#04000D]/20" data-reveal>
      <div class="max-w-container-max mx-auto">
        
        <!-- Section Header -->
        <div class="mb-16">
          <p class="font-mono text-[#04000D] text-xs md:text-sm uppercase tracking-[0.25em] mb-4 font-bold">PARTNERSHIP HIERARCHY</p>
          <h2 class="font-bold text-4xl md:text-5xl tracking-tighter text-[#04000D] riso-text-shadow-magenta riso-bleed">Ekosistem Kolaborasi.</h2>
        </div>

        <!-- Stamped Organizer Logos -->
        <div class="mb-16">
          <p class="font-mono text-xs text-[#04000D]/70 uppercase tracking-widest mb-10 text-center md:text-left font-bold">✦ ORGANIZED BY ✦</p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-12 items-center justify-items-center">
            <div class="opacity-70 hover:opacity-100 transition-opacity duration-200"><img alt="UNTAD Logo" class="max-h-16 w-auto object-contain" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBd9JaJvIDLTJQdFS5d5yoFAZhSrpqKx63XEw7xCSKOm74lLtdU7kFK8ry0-Ffl5DbiBue6L4B4xtlUckPSdKjoZyuI7OR1TDF8IBHoZ22Alpy3qSoDMOU4jBsLRryl0OpL6y7dkWz0jqk2e81gapQ2adZ2CA3wQpHL7HM_1GKVHFoQjipIh7lrKUqAnSS95Z7EDvmvaaqbECsPMA-t8NlAdCGaZAYC1BwZfMj6SqvKCFYVX7TSL-GVeaQhcVzHHag4WrtBwMhMl8Y" /></div>
            <div class="opacity-70 hover:opacity-100 transition-opacity duration-200"><img alt="HMTI Logo" class="max-h-16 w-auto object-contain" src="https://lh3.googleusercontent.com/aida-public/AB6AXuABJQXQNNm9vUDNgESijKQHQ4U9jj90CMt1VQQNobIUggoIV7qe3RWu9pl7xaUtM30XL3nlxN9LXj2m6ZdK3YqXhVubUo-eFJYaN9m5nMPGqVVmq9qytOgHyfsPZrxl3YKRMlCp4pJ1cAFuzOPsMTry9CbeeZonCM9VQZ_xlnfRkZjtdnAUfoQez_mjYwOJUDiolihm56ECyziYVTYkfAeAQLh7wP0b3owa1QqIcYZaporMHHIYGa_lDzufxsDHWj0Z1ZxT-e42LZk" /></div>
            <div class="opacity-70 hover:opacity-100 transition-opacity duration-200"><img alt="I-FEST 2026 Logo" class="max-h-16 w-auto object-contain" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDhnw57bnP_MNSx7W9XMJ7qaxRXVmnex8RLUI13x_Rpl1xN4M7pXCy5pN_ZdOPnIHJTNN4kZSjT9DqzG_ee4l1CDfv44v8r4YlgZSciIPTAE-bXMwsc7jgnuUtS7p16r6Sh2cX5JKDsmjgzh6kEC0nC-x7DpfKeaJ7IZRE5nmVnbATSzj2k_2KAH4AIVzmjl6oj_exmAveMvGzDaZm9qPAKFAmAXmp2B8CsY_frqmD4LafhTT9wt45x5dHwqMVf9bWGHPMYK-whYig" /></div>
          </div>
        </div>

        <!-- HAIRLINE DIVIDER -->
        <div class="border-b border-[#04000D]/10 mb-16"></div>

        <!-- Stepped Flat Layout Sponsor Hierarchy Container -->
        <div class="border border-[#04000D]/20 divide-y divide-[#04000D]/20 bg-white animate-fade-in">
          
          <!-- TIER 1: MAIN STRATEGIC PARTNER -->
          <div class="p-8 md:p-12 flex flex-col md:flex-row items-center gap-8 md:gap-16">
            <div class="flex-1">
              <p class="font-mono text-xs uppercase tracking-widest font-bold text-[#D86BFF]">✦ MAIN STRATEGIC PARTNER ✦</p>
              <h3 class="font-bold text-2xl md:text-3xl text-[#04000D] mt-2 mb-4">Hannah Asa Indonesia</h3>
              <p class="font-body-md text-base md:text-lg text-[#04000D]/80 leading-relaxed max-w-xl">
                Official partner mengawal eskalasi lobi pendanaan Tier-1 dan kurikulum 25 Titik Roadshow Inklusif.
              </p>
            </div>
            <div class="w-full md:w-1/2 flex justify-center items-center opacity-70 hover:opacity-100 transition-opacity duration-200">
              <img alt="Hannah Asa Indonesia" class="w-full max-w-[360px] h-auto object-contain mix-blend-multiply filter contrast-125" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCNkv2nd4oC9evrxjVmiOkoGsdrMgyx1WzIFR8RNd8t-yWV9HI-nei0WOalBBUTc1hiC7pKH0O_obesU7kLqOHztF15mrQzt-8n3miZeyqKA_Epnwd-rG2WJ00QhfmTZlK7tkjPKiXZjnzziuBZQvuRAYcrqSmYeWGt_O9XQb-TWSdQ3WcPJBJai5H3175kPrG0C4kdSc6Xr9yt3_LVKxFPm2se5kZEHmncC9eT_wh-otjtzmHlcre2bU3WiBdW4nX4QjQQqWcqsV0" />
            </div>
          </div>

          <!-- TIER 2: STRATEGIC PARTNER -->
          <div class="p-8 md:p-12 flex flex-col md:flex-row items-center gap-8 md:gap-16">
            <div class="flex-1">
              <p class="font-mono text-xs uppercase tracking-widest font-bold text-[#8839FF]">✦ STRATEGIC PARTNER ✦</p>
              <h3 class="font-bold text-2xl md:text-3xl text-[#04000D] mt-2 mb-4">Sultan Music</h3>
              <p class="font-body-md text-base md:text-lg text-[#04000D]/80 leading-relaxed max-w-xl">
                Official Production & Vendor partner menjamin mutu infrastruktur panggung dan malam puncak acara.
              </p>
            </div>
            <div class="w-full md:w-1/3 flex justify-center items-center opacity-70 hover:opacity-100 transition-opacity duration-200">
              <img alt="Sultan Music" class="w-full max-w-[200px] h-auto object-contain mix-blend-multiply filter contrast-125" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAUrrspETTLMK_VWe8VMHGBKJ6bqIsQJ3FkWj5f15jvn5c0yhB9ha8XFj6SDveCWLAAn7dJWPKtWDsFtKkoOwkQRaGLLT_LJDlgAyiX0aiqV8p4JGKTJJkXM7nZKcts1sDujhb-4pqv_Lp1yFsSoQRtIKp-smllKQITUBNB886zrZMdL0MEpRWbEuE56u6RsEeeA65EY0PV3cxhHo_wM_QZ-c6WvZYnZjtSaeTAoJlZKq-DpKLj4fnbLb0gZKC_mMq2DiGIQdc8cKg" />
            </div>
          </div>

          <!-- TIER 3: OFFICIAL MEDIA PARTNERS -->
          <div class="p-8 md:p-12">
            <p class="font-mono text-[#04000D] text-xs uppercase tracking-widest font-bold mb-8">✦ OFFICIAL MEDIA PARTNERS ✦</p>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-6 md:gap-8 items-stretch">
              <div
                v-for="partner in mediaPartners"
                :key="partner.name"
                class="border border-[#04000D]/10 p-4 flex items-center justify-center opacity-70 hover:opacity-100 transition-opacity duration-200 bg-off-white/30 min-h-[88px]"
              >
                <img :alt="partner.name" class="max-h-16 w-auto object-contain filter contrast-125 grayscale" :src="partner.src" />
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
              <span class="font-headline-lg text-4xl font-bold text-[#D6FF00] riso-text-shadow-double-dark riso-bleed">I-FEST 2026</span>
            </div>
            
            <!-- Bottom-Left Institutional Logo flex block -->
            <div class="flex flex-col gap-3">
              <span class="font-mono text-[10px] md:text-xs font-bold uppercase tracking-wider text-[#F5F5F5]/60">ORGANIZED BY HMTI UNIVERSITAS TADULAKO</span>
              <div class="flex gap-6 items-center">
                <img alt="UNTAD Logo" class="h-10 md:h-12 w-auto opacity-95 filter brightness-0 invert object-contain" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBd9JaJvIDLTJQdFS5d5yoFAZhSrpqKx63XEw7xCSKOm74lLtdU7kFK8ry0-Ffl5DbiBue6L4B4xtlUckPSdKjoZyuI7OR1TDF8IBHoZ22Alpy3qSoDMOU4jBsLRryl0OpL6y7dkWz0jqk2e81gapQ2adZ2CA3wQpHL7HM_1GKVHFoQjipIh7lrKUqAnSS95Z7EDvmvaaqbECsPMA-t8NlAdCGaZAYC1BwZfMj6SqvKCFYVX7TSL-GVeaQhcVzHHag4WrtBwMhMl8Y" />
                <img alt="HMTI Logo" class="h-10 md:h-12 w-auto opacity-95 filter brightness-0 invert object-contain" src="https://lh3.googleusercontent.com/aida-public/AB6AXuABJQXQNNm9vUDNgESijKQHQ4U9jj90CMt1VQQNobIUggoIV7qe3RWu9pl7xaUtM30XL3nlxN9LXj2m6ZdK3YqXhVubUo-eFJYaN9m5nMPGqVVmq9qytOgHyfsPZrxl3YKRMlCp4pJ1cAFuzOPsMTry9CbeeZonCM9VQZ_xlnfRkZjtdnAUfoQez_mjYwOJUDiolihm56ECyziYVTYkfAeAQLh7wP0b3owa1QqIcYZaporMHHIYGa_lDzufxsDHWj0Z1ZxT-e42LZk" />
                <img alt="HMTI Cabinet Logo" class="h-10 md:h-12 w-auto opacity-95 filter brightness-0 invert object-contain" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCGOO9Q0mjYc0H7aYPw3OmVq2D6_4YW65cNu_CCvDESeqbTs_coMrgaJ_KCfhxzjtQ3Bvqa67E6HCknZa8iPNcMb9zQZH8l81TIhJcDJmpYc9d6pFBMBjPdzUyZmbOIed-ho30XBdUc47mRF4whIXg3N5BE68gC8dUsANghJXqFpHgF9-hbiorFCmClyn3Y4O4y0NWEqm6DXHkapQitfCm0XzLijjz0M5OD_LaS-thSzF_i4Klvhxgf3Q3DdZGBO8pEOOE-yADwxNg" />
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
            <p class="font-caption text-[10px] opacity-40 mt-8">© 2026 I-FEST. All rights reserved. Orchestrated with passion in Central Sulawesi.</p>
          </div>
          
        </div>
      </div>
    </footer>
    
  </div>
</template>