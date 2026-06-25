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
const activeGalleryIndex = ref(0)
const isAnimating = ref(false)
const rotations = [-3, 2, -1, 4, -2, 3, -4, 1, -2, 3, -1]

const getCardStyle = (index) => {
  const total = galleryImages.length
  const diff = (index - activeGalleryIndex.value + total) % total
  const rot = rotations[index % rotations.length]

  if (index === activeGalleryIndex.value && isAnimating.value) {
    return {
      transform: 'translate(220px, 15px) rotate(12deg)',
      opacity: '0',
      zIndex: '40',
      pointerEvents: 'none'
    }
  }

  if (diff === 0) {
    return {
      transform: `translate(0px, 0px) rotate(${rot}deg)`,
      zIndex: '30',
      opacity: '1'
    }
  } else if (diff === 1) {
    return {
      transform: `translate(8px, 6px) rotate(${rot}deg)`,
      zIndex: '20',
      opacity: '0.92'
    }
  } else if (diff === 2) {
    return {
      transform: `translate(-6px, 12px) rotate(${rot}deg)`,
      zIndex: '10',
      opacity: '0.85'
    }
  } else {
    return {
      transform: `translate(0px, 0px) scale(0.95) rotate(${rot}deg)`,
      zIndex: '0',
      opacity: '0',
      pointerEvents: 'none'
    }
  }
}

const nextCard = () => {
  if (isAnimating.value) return
  isAnimating.value = true
  setTimeout(() => {
    activeGalleryIndex.value = (activeGalleryIndex.value + 1) % galleryImages.length
    isAnimating.value = false
  }, 300)
}

const prevCard = () => {
  if (isAnimating.value) return
  activeGalleryIndex.value = (activeGalleryIndex.value - 1 + galleryImages.length) % galleryImages.length
}

const activeSection = ref('')

const handleScroll = () => {
  const scrollPosition = window.scrollY + 180 // Navbar offset + padding
  const sections = ['roadshow-section', 'competitions-section', 'timeline', 'galeri-jejak-langkah', 'bph-matrix', 'partners']
  
  let currentActive = ''
  for (const id of sections) {
    const el = document.getElementById(id)
    if (el) {
      const top = el.offsetTop
      const height = el.offsetHeight
      if (scrollPosition >= top && scrollPosition < top + height) {
        currentActive = id
        break
      }
    }
  }
  activeSection.value = currentActive
}


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

const scrollToHero = () => {
  const heroSection = document.getElementById('hero')
  if (heroSection) {
    heroSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
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

const doc2025AssetModules = import.meta.glob('../assets/dokumentasi_ifest2025/*', {
  eager: true,
  import: 'default',
})

const getAsset = (assetModules, folder, fileName) => assetModules[`../assets/${folder}/${fileName}`] ?? ''

const panitiaAssetModules = import.meta.glob('../assets/Fotage Panitia Ifest 2026/**/*', {
  eager: true,
  import: 'default',
})

// Parser for committee photos
const parseCommitteePhotos = () => {
  const allFiles = Object.keys(panitiaAssetModules)
  const divisions = {
    'Kreativitas': { name: 'Kreativitas', coordinators: [], members: [], groupPhoto: null, color: '#E11D48', textColor: '#ffffff', fruit: 'Apel', emoji: '🍎' },
    'Koor Inti': { name: 'Koor Inti', coordinators: [], members: [], groupPhoto: null, color: '#DC2626', textColor: '#ffffff', fruit: 'Cabe', emoji: '🌶️🌶️' },
    'Sponsor': { name: 'Sponsor', coordinators: [], members: [], groupPhoto: null, color: '#10B981', textColor: '#ffffff', fruit: 'Semangka', emoji: '🍉' },
    'Ekonomi Kreatif': { name: 'Ekonomi Kreatif', coordinators: [], members: [], groupPhoto: null, color: '#FF2E93', textColor: '#ffffff', fruit: 'Strawberry', emoji: '🍓' },
    'Konsumsi': { name: 'Konsumsi', coordinators: [], members: [], groupPhoto: null, color: '#FF8A65', textColor: '#04000D', fruit: 'Peach', emoji: '🍑' },
    'Acara': { name: 'Acara', coordinators: [], members: [], groupPhoto: null, color: '#BE123C', textColor: '#ffffff', fruit: 'Cherry', emoji: '🍒' },
    'Logistik': { name: 'Logistik', coordinators: [], members: [], groupPhoto: null, color: '#65A30D', textColor: '#ffffff', fruit: 'Alpukat', emoji: '🥑' },
    'Korlap': { name: 'Korlap', coordinators: [], members: [], groupPhoto: null, color: '#FDE047', textColor: '#04000D', fruit: 'Lemon', emoji: '🍋' },
    'Humas': { name: 'Humas', coordinators: [], members: [], groupPhoto: null, color: '#F97316', textColor: '#ffffff', fruit: 'Jeruk', emoji: '🍊' },
    'Keamanan': { name: 'Keamanan', coordinators: [], members: [], groupPhoto: null, color: '#2563EB', textColor: '#ffffff', fruit: 'Blueberry', emoji: '🫐' }
  }

  const isGroupPhoto = (name) => {
    const n = name.toLowerCase()
    if (
      n.includes('rame') || n.includes('ramai') || n.includes('boyband') || n.includes('porenjes') || 
      n.includes('sekretaris i dan ii') || n.includes('ketua dan wakil') || 
      n.includes('koor inti 3') || n.includes('koor inti 2') || 
      n.includes('sekertaris') || n.includes('divisi lapangan') || 
      n.includes('foto pertama ya') || n.includes('request pake foto') || 
      n.includes('not this') || n.includes('anggota humas') ||
      n.includes('sponsor 1')
    ) {
      return true
    }

    const dotIdx = n.lastIndexOf('.')
    const nameWithoutExt = dotIdx === -1 ? n : n.substring(0, dotIdx)

    return nameWithoutExt === 'img_8492' || nameWithoutExt === 'img_8813' || nameWithoutExt === 'img_8815' || nameWithoutExt === 'img_8818' ||
           nameWithoutExt === 'img_8770' || nameWithoutExt === 'img_8782' || nameWithoutExt === 'img_8789' ||
           nameWithoutExt === 'img_8808' || nameWithoutExt === 'img_8810' || nameWithoutExt === 'img_8803' ||
           nameWithoutExt === 'img_8804' || nameWithoutExt === 'img_8805' || nameWithoutExt === 'img_8806' ||
           nameWithoutExt === 'img_8500' || nameWithoutExt === 'img_8501' || nameWithoutExt === 'img_8518' ||
           nameWithoutExt === 'img_8519' || nameWithoutExt === 'img_8540' || nameWithoutExt === 'img_8650' ||
           nameWithoutExt === 'img_8651' || nameWithoutExt === 'img_8472' || nameWithoutExt === 'img_8473' ||
           nameWithoutExt === 'img_8479' || nameWithoutExt === 'img_8592' || nameWithoutExt === 'img_8412' ||
           nameWithoutExt === 'img_8413' || nameWithoutExt === 'img_8689' || nameWithoutExt === 'img_8696' ||
           nameWithoutExt === 'img_8543' || nameWithoutExt === 'img_8546' || nameWithoutExt === 'img_8772' ||
           nameWithoutExt === 'img_8790' || nameWithoutExt === 'img_8791' || nameWithoutExt === 'img_8703' ||
           nameWithoutExt === 'img_8704' || nameWithoutExt === 'img_8733' || nameWithoutExt === 'img_8736' ||
           nameWithoutExt === 'img_8737' || nameWithoutExt === 'img_8738' || nameWithoutExt === 'img_8740' ||
           nameWithoutExt === 'img_8755' || nameWithoutExt === 'img_8756' || nameWithoutExt === 'img_8642' ||
           nameWithoutExt === 'img_8648' || nameWithoutExt === 'img' || nameWithoutExt.includes('51882e3c') ||
           nameWithoutExt.includes('866eecd6') || nameWithoutExt.includes('f8944255') || nameWithoutExt.includes('571c6e37') ||
           nameWithoutExt.includes('a5a56752') || nameWithoutExt.includes('d9ff2b37')
  }

  const peopleMap = new Map()

  allFiles.forEach(path => {
    const parts = path.split('/')
    const index = parts.indexOf('Fotage Panitia Ifest 2026')
    if (index === -1 || index === parts.length - 1) return

    let division = parts[index + 1]
    if (division === 'Lapangan') {
      division = 'Korlap'
    }

    let filename = parts[parts.length - 1]
    const extIndex = filename.lastIndexOf('.')
    if (extIndex === -1) return
    const nameWithoutExt = filename.substring(0, extIndex)
    const ext = filename.substring(extIndex).toLowerCase()

    if (!divisions[division]) {
      if (parts[index + 1] === 'Koor Inti' && parts[index + 2]) {
        division = 'Koor Inti'
      } else {
        return
      }
    }

    const fileUrl = panitiaAssetModules[path]

    if (isGroupPhoto(filename)) {
      if (!divisions[division].groupPhoto || ext === '.png') {
        divisions[division].groupPhoto = fileUrl
      }
      return
    }

    let cleanName = nameWithoutExt
      .replace(/\(\d+\)/g, '')
      .replace(/_\(\d+\)/g, '')
      .replace(/_/g, ' ')
      .replace(/\s+/g, ' ')
      .trim()

    const uniqueKey = cleanName.toLowerCase()
    const hasPrevious = peopleMap.has(uniqueKey)
    const isNewBetter = !hasPrevious || (ext === '.png' && !filename.includes('(1)') && !filename.includes('(2)'))

    if (!hasPrevious || isNewBetter) {
      let classYear = ''
      const yearMatch = cleanName.match(/\b(23|24|25)\b/)
      if (yearMatch) {
        classYear = yearMatch[0]
      }

      let subfolder = ''
      if (parts[index + 1] === 'Koor Inti' && parts[index + 2]) {
        subfolder = parts[index + 2]
      }

      let role = 'Anggota'
      let isCoordinator = false

      if (subfolder === 'Project Manager') {
        isCoordinator = true
        role = 'Project Manager'
      } else if (subfolder === 'PIC') {
        isCoordinator = true
        role = 'PIC'
      } else if (subfolder === 'Ketua & Wakil Ketua Panitia') {
        isCoordinator = true
        if (cleanName.toLowerCase().includes('wakil')) {
          role = 'Wakil Ketua Panitia'
        } else {
          role = 'Ketua Panitia'
        }
      } else if (subfolder === 'Bendahara') {
        isCoordinator = true
        role = 'Bendahara'
      } else if (subfolder === 'Sekretaris') {
        isCoordinator = true
        if (cleanName.toLowerCase().includes('ii') || cleanName.toLowerCase().includes('2')) {
          role = 'Sekretaris II'
        } else {
          role = 'Sekretaris I'
        }
      } else {
        const nameLower = cleanName.toLowerCase()
        if (
          nameLower.includes('koordinator') || 
          nameLower.includes('koord') || 
          nameLower.includes('ketua') || 
          nameLower.includes('wakil') || 
          nameLower.includes('project manager') || 
          nameLower.includes('sekretaris') || 
          nameLower.includes('bendahara') || 
          nameLower.includes('pic')
        ) {
          isCoordinator = true
          if (nameLower.includes('project manager')) role = 'Project Manager'
          else if (nameLower.includes('pic')) role = 'PIC'
          else if (nameLower.includes('ketua panitia')) role = 'Ketua Panitia'
          else if (nameLower.includes('wakil ketua')) role = 'Wakil Ketua Panitia'
          else if (nameLower.includes('sekretaris i')) role = 'Sekretaris I'
          else if (nameLower.includes('sekretaris ii')) role = 'Sekretaris II'
          else if (nameLower.includes('sekretaris')) role = 'Sekretaris'
          else if (nameLower.includes('bendahara')) role = 'Bendahara'
          else if (nameLower.includes('wakil koord') || nameLower.includes('wakil kordinator')) role = 'Wakil Koordinator'
          else if (nameLower.includes('ketua koord') || nameLower.includes('koordinator')) role = 'Koordinator'
        }
      }

      let displayName = cleanName
      const lowerName = cleanName.toLowerCase()
      if (lowerName.startsWith('dareean')) displayName = 'Dareean A. Raffi'
      else if (lowerName.startsWith('gabriel')) displayName = 'Gabriel Kristofan Supari'
      else if (lowerName.startsWith('reyqal')) displayName = 'Reyqal Syawalano'
      else if (lowerName.startsWith('nakita')) displayName = 'Nakita Semesta'
      else if (lowerName.startsWith('nur ainun')) displayName = 'Nur Ainun'
      else if (lowerName.startsWith('yulianingsih')) displayName = 'Yulianingsih'
      else if (lowerName.startsWith('lara')) displayName = 'Lara Fauzia'
      else {
        if (classYear) {
          const yearIdx = cleanName.indexOf(classYear)
          if (yearIdx !== -1) {
            displayName = cleanName.substring(0, yearIdx).trim()
          }
        } else {
          const roleKeywords = ['anggota', 'koordinator', 'koord', 'ketua', 'wakil', 'project manager', 'sekretaris', 'bendahara', 'pic']
          for (const kw of roleKeywords) {
            const idx = displayName.toLowerCase().indexOf(kw)
            if (idx !== -1) {
              displayName = displayName.substring(0, idx).trim()
              break
            }
          }
        }
      }

      displayName = displayName.replace(/[-_,\s]+$/, '').trim()

      peopleMap.set(uniqueKey, {
        name: displayName,
        classYear: classYear ? `'${classYear}` : '',
        role: role,
        isCoordinator,
        imgSrc: fileUrl,
        division
      })
    }
  })

  peopleMap.forEach(person => {
    const div = divisions[person.division]
    if (person.isCoordinator) {
      div.coordinators.push(person)
    } else {
      div.members.push(person)
    }
  })

  Object.keys(divisions).forEach(key => {
    const div = divisions[key]
    div.coordinators.sort((a, b) => {
      const getPriority = (role) => {
        const r = role.toLowerCase()
        if (r.includes('project manager')) return 1
        if (r.includes('pic')) return 2
        if (r.includes('ketua panitia')) return 3
        if (r.includes('wakil ketua')) return 4
        if (r.includes('sekretaris i')) return 5
        if (r.includes('sekretaris ii')) return 6
        if (r.includes('sekretaris')) return 7
        if (r.includes('bendahara')) return 8
        if (r.includes('koordinator')) return 9
        if (r.includes('wakil koordinator')) return 10
        return 100
      }
      return getPriority(a.role) - getPriority(b.role)
    })
    div.members.sort((a, b) => a.name.localeCompare(b.name))
  })

  return divisions
}

const panitiaData = parseCommitteePhotos()
const activeDivisionTab = ref('Koor Inti')
const fotoPanitiaKeseluruhan = computed(() => {
  return panitiaAssetModules['../assets/Fotage Panitia Ifest 2026/Panitia Keseluruhan/Fotooo.jpg'] || ''
})

const galleryImages = [
  {
    src: getAsset(doc2025AssetModules, 'dokumentasi_ifest2025', 'DSCF2050.webp'),
    alt: 'Suasana Hackathon Pasigala',
    title: 'Hackathon Pasigala',
    description: 'Kolaborasi intensif 24 jam inovator muda memecahkan tantangan riil di Sulawesi Tengah.'
  },
  {
    src: getAsset(doc2025AssetModules, 'dokumentasi_ifest2025', 'DSCF2385.webp'),
    alt: 'Awarding Night Pemenang',
    title: 'Awarding & Penghargaan',
    description: 'Apresiasi karya teknologi terbaik kepada para pemenang kompetisi nasional.'
  },
  {
    src: getAsset(doc2025AssetModules, 'dokumentasi_ifest2025', 'DSC_0404.webp'),
    alt: 'Pameran Karya Inovatif',
    title: 'Showcase Expo Teknologi',
    description: 'Demo produk digital inovatif hasil karya talenta lokal kepada ribuan pengunjung.'
  },
  {
    src: getAsset(doc2025AssetModules, 'dokumentasi_ifest2025', 'DSC_0406.webp'),
    alt: 'Demo IoT Smart System',
    title: 'Prototype IoT Showcase',
    description: 'Pengunjung mencoba prototype sistem pintar berbasis Internet of Things.'
  },
  {
    src: getAsset(doc2025AssetModules, 'dokumentasi_ifest2025', 'DSC_0417.webp'),
    alt: 'Sesi Coding & Mentoring',
    title: 'Developer Mentorship',
    description: 'Sesi bimbingan langsung bersama praktisi industri teknologi nasional.'
  },
  {
    src: getAsset(doc2025AssetModules, 'dokumentasi_ifest2025', 'IMG_0170.webp'),
    alt: 'Sambutan Puncak Acara',
    title: 'Opening Ceremony',
    description: 'Pembukaan resmi I-FEST oleh Rektor Universitas Tadulako dan Ketua Jurusan.'
  },
  {
    src: getAsset(doc2025AssetModules, 'dokumentasi_ifest2025', 'IMG_5734.webp'),
    alt: 'Auditorium Dipadati Peserta',
    title: 'Seminar Nasional Tech',
    description: 'Lebih dari 1.000 peserta memadati auditorium utama untuk mengikuti seminar teknologi.'
  },
  {
    src: getAsset(doc2025AssetModules, 'dokumentasi_ifest2025', 'IMG_5819.webp'),
    alt: 'Peserta Bertanya Sesi Diskusi',
    title: 'Sesi Diskusi Interaktif',
    description: 'Tanya jawab kritis antara mahasiswa dan pembicara seputar masa depan AI.'
  },
  {
    src: getAsset(doc2025AssetModules, 'dokumentasi_ifest2025', '_DSC6425.webp'),
    alt: 'Penampilan Band & Guest Star',
    title: 'Digital Symphony Concert',
    description: 'Konser penutup yang menyatukan ribuan penonton dalam harmoni digital.'
  },
  {
    src: getAsset(doc2025AssetModules, 'dokumentasi_ifest2025', '_DSC6476.webp'),
    alt: 'Keceriaan Panitia & Crew',
    title: 'IFEST 2025 Organizer Team',
    description: 'Dedikasi penuh 80+ panitia HMTI UNTAD menyukseskan festival IT terbesar.'
  },
  {
    src: getAsset(doc2025AssetModules, 'dokumentasi_ifest2025', '_DSC6481.webp'),
    alt: 'Closing Ceremony Foto Bersama',
    title: 'Fase Akhir & Evaluasi',
    description: 'Kebersamaan seluruh jajaran panitia, mentor, dan pengisi acara di malam puncak.'
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
  window.addEventListener('scroll', handleScroll)
  // Run once to set initial active section
  handleScroll()

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

  // Handle autoscroll persist state logic
  const targetSectionId = window.history.state?.scrollToSection
  if (targetSectionId) {
    setTimeout(() => {
      const element = document.getElementById(targetSectionId)
      if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' })
        // Clear state
        window.history.replaceState({ ...window.history.state, scrollToSection: null }, document.title)
      }
    }, 150)
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateViewport)
  window.removeEventListener('scroll', handleScroll)
  ScrollTrigger.getAll().forEach((trigger) => trigger.kill())
})
</script>

<template>
  <div 
    class="riso-canvas bg-off-white min-h-screen text-[#04000D] font-body-md select-text pb-12 transition-all duration-700 ease-out"
  >

    <!-- SECTION B: Hero Section (Tactile Colored Paper Canvas) -->
    <section id="hero" class="bg-off-white riso-canvas min-h-[85vh] pt-[100px] md:pt-[120px] pb-[70px] relative overflow-hidden flex flex-col justify-center border-b border-dashed border-[#04000D]/30">
      
      <!-- Layered, Floating 3D Assets (Stamps Bleeding Into The Colored Paper Fibers) -->
      <img
        v-for="asset in heroDecorations"
        :key="asset.src"
        :alt="asset.alt"
        :class="asset.className"
        :src="asset.src"
        :style="{ animationDelay: asset.delay }"
      />
      
      <div class="max-w-container-max mx-auto px-6 md:px-lg w-full py-lg relative z-10 flex flex-col items-center text-center">
        <div data-reveal id="hero-content" class="flex flex-col items-center">
          <p class="font-mono text-[#FF3D8B] text-xs md:text-base tracking-[0.22em] uppercase mb-md font-bold select-none riso-bleed px-4">
            THE BIGGEST IT FESTIVAL IN EASTERN INDONESIA
          </p>
          
          <h1 class="font-black text-[#04000D] text-4xl sm:text-6xl md:text-8xl lg:text-[110px] tracking-[-0.04em] leading-none mb-lg max-w-5xl px-4 riso-bleed text-center select-none pt-2 pb-4">
            <span class="riso-text-shadow-magenta inline-block">DIGITAL SYMPHONY</span>
          </h1>
          
          <p class="font-body-md text-base md:text-xl text-[#04000D]/80 max-w-2xl mb-lg leading-relaxed mx-auto px-4">
            Mengorkestrasi Inovasi Global untuk Masa Depan Berkelanjutan. HMTI Universitas Tadulako memanggil 8.000+ inovator untuk bergabung dalam revolusi digital terbesar di Sulawesi Tengah.
          </p>
          
          <button class="riso-btn-plate bg-black text-white px-8 md:px-xl py-3 md:py-md rounded-full font-button text-button select-none font-bold active:scale-95" style="--plate-color: #FF3D8B;">
            EXPLORE THE SYMPHONY ↓
          </button>
        </div>
      </div>
      
      <!-- Ticker Tape Ribbon (Infinite Logo Marquee) -->
      <div class="absolute bottom-0 left-0 w-full bg-[#FDE047] border-y-2 border-[#04000D] py-3 md:py-4 overflow-hidden z-20 select-none shadow-[0_-4px_0_0_rgba(4,0,13,0.05)]">
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
    <section class="bg-off-white riso-canvas py-[32px] md:py-[48px] px-6 md:px-lg border-b border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
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
        <div class="bg-solid-white border-2 border-[#04000D] shadow-[6px_6px_0px_0px_rgba(4,0,13,1)] p-6 md:p-8 relative overflow-hidden text-left">
          
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
    <section id="pillars" class="bg-[#F5F5F5] riso-canvas py-12 md:py-16 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
      <!-- Background Decorative Stamp Shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'ry6 1.webp')" 
        alt="Decorative Riso Plate Shard" 
        class="absolute top-10 right-10 w-36 md:w-56 opacity-20 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />
      
      <div class="max-w-container-max mx-auto relative z-10">
        
        <!-- Section Header -->
        <div class="mb-10 md:mb-14 text-left">
          <span class="font-mono text-xs uppercase tracking-widest font-bold text-[#04000D] block mb-2">
            THE TRILOGY ENGINE
          </span>
          <h2 class="font-black text-4xl sm:text-5xl md:text-7xl tracking-[-0.04em] leading-none text-[#04000D] riso-bleed flex flex-wrap items-center gap-y-2 select-none pt-2 pb-2">
            <span class="bg-[#D86BFF] text-[#04000D] px-3.5 py-1.5 rounded-none inline-block transform translate-x-[2px] translate-y-[1px] shadow-[5px_5px_0px_0px_#04000D] mr-2">TIGA PILAR</span> SIMFONI.
          </h2>
        </div>

        <!-- The Brutalist Trilogy Grid System -->
        <div id="trilogy-grid" class="grid grid-cols-1 md:grid-cols-[1.2fr_1fr_1.1fr] gap-6 md:gap-8 rounded-none select-none">
          
          <!-- CELL 1: RESONANCE -->
          <div class="bg-[#FDE047] p-5 sm:p-6 md:p-8 flex flex-col justify-between min-h-[260px] md:min-h-[280px] text-[#04000D] transition-all duration-200 hover:bg-[#d9ff1a] border-3 border-[#04000D]" style="box-shadow: 6px 6px 0px 0px #04000D;">
            <div>
              <div class="flex justify-between items-start mb-6">
                <span class="font-mono text-[10px] font-extrabold uppercase bg-[#04000D] text-[#FDE047] px-2 py-0.5">
                  DAMPAK NYATA
                </span>
                <span class="font-mono text-[10px] font-bold text-[#04000D]/60">[PILAR-01]</span>
              </div>
              <h3 class="font-sans font-black uppercase text-2xl sm:text-3xl tracking-tight leading-none mb-4 text-[#04000D]" style="text-shadow: 2px 2px 0px #FF3D8B;">
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
          <div class="bg-[#D86BFF] p-5 sm:p-6 md:p-8 flex flex-col justify-between min-h-[260px] md:min-h-[280px] text-[#04000D] transition-all duration-200 hover:bg-[#df85ff] border-3 border-[#04000D]" style="box-shadow: 6px 6px 0px 0px #04000D;">
            <div>
              <div class="flex justify-between items-start mb-6">
                <span class="font-mono text-[10px] font-extrabold uppercase bg-[#04000D] text-[#D86BFF] px-2 py-0.5">
                  KOLABORASI
                </span>
                <span class="font-mono text-[10px] font-bold text-[#04000D]/60">[PILAR-02]</span>
              </div>
              <h3 class="font-sans font-black uppercase text-2xl sm:text-3xl tracking-tight leading-none mb-4 text-[#04000D]" style="text-shadow: 2px 2px 0px #FDE047;">
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
          <div class="bg-[#8839FF] p-5 sm:p-6 md:p-8 flex flex-col justify-between min-h-[260px] md:min-h-[280px] text-white transition-all duration-200 hover:bg-[#9752ff] border-3 border-[#04000D]" style="box-shadow: 6px 6px 0px 0px #04000D;">
            <div>
              <div class="flex justify-between items-start mb-6">
                <span class="font-mono text-[10px] font-extrabold uppercase bg-[#FDE047] text-[#04000D] px-2 py-0.5">
                  INKLUSIFITAS
                </span>
                <span class="font-mono text-[10px] font-bold text-white/60">[PILAR-03]</span>
              </div>
              <h3 class="font-sans font-black uppercase text-2xl sm:text-3xl tracking-tight leading-none mb-4 text-[#FDE047]" style="text-shadow: 2px 2px 0px #04000D;">
                03 / INCLUSIVITY
              </h3>
              <p class="font-body text-sm md:text-base leading-relaxed text-[#F5F5F5]/90">
                Mendorong pemerataan akses dan literasi digital agar seluruh lapisan masyarakat siap menghadapi tantangan disrupsi tanpa terkecuali.
              </p>
            </div>
            <div class="mt-8 border-t border-white/10 pt-4 flex justify-between items-center font-mono text-[9px] font-bold uppercase tracking-widest text-[#FDE047]/95">
              <span>EQUAL ACCESS</span>
              <span>DIGITAL LITERACY</span>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- SECTION COMP: ROADSHOW INKLUSIF & SOCIAL MOVEMENT -->
    <section id="roadshow-section" class="bg-off-white riso-canvas py-12 md:py-16 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
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
              <span class="bg-[#FDE047] text-[#04000D] px-3.5 py-1.5 rounded-none inline-block transform translate-x-[2px] translate-y-[1px] shadow-[5px_5px_0px_0px_#04000D] mr-2">ROADSHOW INKLUSIF</span> &amp; SOCIAL MOVEMENT.
            </h2>
            <p class="font-body-md text-base md:text-xl text-[#04000D]/80 mt-6 leading-relaxed">
              Aksi nyata pengabdian masyarakat untuk mengorkestrasi inovasi dan menghadirkan akses literasi digital langsung ke sekolah, desa, dan komunitas disabilitas yang paling membutuhkan di wilayah Palu, Sigi, dan Donggala (Pasigala).
            </p>
          </div>
          
          <div class="flex-shrink-0 select-none">
            <router-link :to="{ path: '/roadshow', state: { fromSection: 'roadshow-section' } }" class="riso-btn-plate bg-[#04000D] text-white px-8 py-4 rounded-full font-button text-xs font-bold text-center inline-block" style="--plate-color: #FF3D8B;">
              Eksplorasi Rute Roadshow →
            </router-link>
          </div>
        </div>

      </div>
    </section>

    <!-- SECTION COMP: COMPETITION BENTO GRID -->
    <section id="competitions-section" class="bg-off-white riso-canvas py-12 md:py-16 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
      
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
        <div class="mb-8 md:mb-10 text-center lg:text-left">
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
        <div class="mb-10">
          <div class="flex items-center gap-3 mb-5 select-none">
            <span class="font-mono text-xs font-bold uppercase tracking-widest bg-[#04000D] text-white px-2.5 py-0.5">TIER 01</span>
            <span class="font-mono text-xs font-bold uppercase tracking-widest text-[#04000D]/60">KATEGORI NASIONAL</span>
          </div>

          <div id="kompetisi-grid-tier1" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div 
              v-for="comp in competitionsData.slice(0, 3)" 
              :key="comp.id"
              class="border-2 md:border-3 border-[#04000D] p-5 sm:p-6 flex flex-col justify-between min-h-[240px] relative transition-transform duration-200 hover:-rotate-1"
              :style="{ backgroundColor: comp.cardBg, boxShadow: '6px 6px 0px 0px #04000D' }"
            >
              <div>
                <div class="flex justify-between items-start mb-4">
                  <span class="font-mono text-[9px] font-extrabold uppercase bg-[#04000D] text-white px-2 py-0.5">{{ comp.tagline }}</span>
                  <span class="font-mono text-[9px] font-bold text-[#04000D]/60">[{{ comp.id }}]</span>
                </div>
                <h3 class="font-black uppercase text-lg sm:text-xl tracking-tight leading-none mb-3 text-[#04000D] riso-bleed">
                  {{ comp.title.split(' ')[0] }} <span class="text-accent-magenta">{{ comp.title.split(' ').slice(1).join(' ') }}</span>
                </h3>
                <div class="border-t border-dashed border-[#04000D]/20 pt-3 flex flex-col gap-2 font-mono text-[11px] text-[#04000D]/80 font-medium tracking-wide">
                  <div class="flex justify-between"><span>Skala:</span><span class="font-bold">{{ comp.scale }}</span></div>
                  <div class="flex justify-between"><span>Biaya Registrasi:</span><span class="font-bold font-mono">{{ comp.fee }}</span></div>
                </div>
              </div>

              <div class="mt-5 select-none">
                <router-link :to="{ path: '/kompetisi', query: { id: comp.id }, state: { fromSection: 'competitions-section' } }" class="riso-btn-plate w-full block bg-[#04000D] text-white py-2.5 rounded-full font-button text-xs text-center font-bold" :style="{ '--plate-color': comp.accentColor }">
                  Lihat Detail Lomba →
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- TIER 2: KATEGORI REGIONAL (2-Column Bento Grid) -->
        <div class="mb-10">
          <div class="flex items-center gap-3 mb-5 select-none">
            <span class="font-mono text-xs font-bold uppercase tracking-widest bg-[#04000D] text-white px-2.5 py-0.5">TIER 02</span>
            <span class="font-mono text-xs font-bold uppercase tracking-widest text-[#04000D]/60">KATEGORI REGIONAL (SULTENG &amp; SEKITARNYA)</span>
          </div>

          <div id="kompetisi-grid-tier2" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div 
              v-for="comp in competitionsData.slice(3, 5)" 
              :key="comp.id"
              class="bg-white border-2 md:border-3 border-[#04000D] p-5 sm:p-6 flex flex-col justify-between min-h-[240px] relative transition-transform duration-200 hover:rotate-1"
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
                <router-link :to="{ path: '/kompetisi', query: { id: comp.id }, state: { fromSection: 'competitions-section' } }" class="riso-btn-plate w-full block bg-[#04000D] text-white py-2.5 rounded-full font-button text-xs text-center font-bold" :style="{ '--plate-color': comp.accentColor }">
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
          <div class="bg-[#04000D] border-2 md:border-3 border-[#04000D] p-5 sm:p-6 md:p-8 relative overflow-hidden select-none" style="box-shadow: 6px 6px 0px 0px #FF3D8B;">
            <div class="absolute inset-0 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:20px_20px] opacity-[0.03] pointer-events-none z-0"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row gap-6 justify-between items-center">
              <div class="text-white text-left flex-1">
                <div class="flex items-center gap-2 mb-3">
                  <span class="font-mono text-[9px] font-extrabold uppercase bg-[#FDE047] text-[#04000D] px-2 py-0.5">HACKATHON + SHOWCASE</span>
                  <span class="font-mono text-xs font-bold text-white/50">[REG-03]</span>
                </div>
                <h3 class="font-black text-2xl sm:text-4xl uppercase tracking-tighter leading-none mb-4 text-[#FDE047] riso-bleed">
                  SULTENG DIGITAL INNOVATION HUB (S-DIH)
                </h3>
                <div class="flex gap-6 font-mono text-xs text-white/85 border-t border-white/10 pt-4 mt-2">
                  <div>Skala: <span class="font-bold text-white">Regional</span></div>
                  <div>Biaya Registrasi: <span class="font-bold text-[#FDE047]">Gratis (Free)</span></div>
                </div>
              </div>
              <div class="w-full md:w-auto">
                <router-link :to="{ path: '/kompetisi', query: { id: 'REG-03' }, state: { fromSection: 'competitions-section' } }" class="riso-btn-plate bg-[#FDE047] text-[#04000D] px-8 py-3 rounded-full font-button text-xs text-center font-extrabold block" style="--plate-color: #FF3D8B;">
                  Lihat Detail Lomba →
                </router-link>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- SECTION K: KEY NUMBERS (The Impact Dashboard) -->
    <section id="impact-dashboard" class="bg-[#F5F5F5] riso-canvas py-8 md:py-12 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 animate-fade-in relative overflow-hidden" data-reveal>
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
            <div class="relative bg-[#F5F5F5] border border-dashed border-[#04000D] p-3 sm:p-4 z-10 flex flex-col justify-between min-h-[110px]">
              <span class="font-mono text-[8px] sm:text-[9px] uppercase tracking-wider text-[#04000D]/60 font-bold">ESTIMASI TARGET PARTISIPAN</span>
              <div>
                <h3 class="text-2xl sm:text-4xl font-bold tracking-tighter text-[#04000D] leading-none mb-1 font-headline-lg riso-bleed tabular-nums">{{ formattedPartisipan }}</h3>
                <p class="font-mono text-[9px] sm:text-[10px] tracking-widest text-[#04000D] font-bold uppercase leading-none mt-2 select-none">
                  <span class="bg-[#FDE047] text-[#04000D] px-2 py-0.5 rounded-none inline-block transform translate-x-[2px] translate-y-[1px] shadow-[2px_2px_0px_0px_#04000D]">8.000+ PARTICIPANTS</span>
                </p>
              </div>
            </div>
          </div>

          <!-- Stat 2 -->
          <div class="relative group">
            <div class="absolute inset-0 bg-[#FDE047] translate-x-[2px] translate-y-[1.5px] border border-[#04000D] z-0"></div>
            <div class="relative bg-[#F5F5F5] border border-dashed border-[#04000D] p-3 sm:p-4 z-10 flex flex-col justify-between min-h-[110px]">
              <span class="font-mono text-[8px] sm:text-[9px] uppercase tracking-wider text-[#04000D]/60 font-bold">TITIK ROADSHOW INKLUSIF</span>
              <div>
                <h3 class="text-2xl sm:text-4xl font-bold tracking-tighter text-[#04000D] leading-none mb-1 font-headline-lg riso-bleed tabular-nums">{{ formattedRoadshow }}</h3>
                <p class="font-mono text-[9px] sm:text-[10px] tracking-widest text-[#04000D] font-bold uppercase leading-none mt-2 select-none">
                  <span class="bg-[#D86BFF] text-[#04000D] px-2 py-0.5 rounded-none inline-block transform translate-x-[-2px] translate-y-[1px] shadow-[2px_2px_0px_0px_#04000D]">25 REGIONAL ROADSHOWS</span>
                </p>
              </div>
            </div>
          </div>

          <!-- Stat 3 -->
          <div class="relative group">
            <div class="absolute inset-0 bg-[#04000D] translate-x-[2px] translate-y-[1.5px] border border-[#04000D] z-0"></div>
            <div class="relative bg-[#F5F5F5] border border-dashed border-[#04000D] p-3 sm:p-4 z-10 flex flex-col justify-between min-h-[110px]">
              <span class="font-mono text-[8px] sm:text-[9px] uppercase tracking-wider text-[#04000D]/60 font-bold">ESTIMASI TALENTA DIGITAL</span>
              <div>
                <h3 class="text-2xl sm:text-4xl font-bold tracking-tighter text-[#04000D] leading-none mb-1 font-headline-lg riso-bleed tabular-nums">{{ formattedTalent }}</h3>
                <p class="font-mono text-[9px] sm:text-[10px] tracking-widest text-[#04000D] font-bold uppercase leading-none mt-2 select-none">
                  <span class="bg-[#FDE047] text-[#04000D] px-2 py-0.5 rounded-none inline-block transform translate-x-[1px] translate-y-[-1px] shadow-[2px_2px_0px_0px_#04000D]">500+ DIGITAL TALENTS</span>
                </p>
              </div>
            </div>
          </div>

          <!-- Stat 4 -->
          <div class="relative group">
            <div class="absolute inset-0 bg-[#FDE047] translate-x-[2px] translate-y-[1.5px] border border-[#04000D] z-0"></div>
            <div class="relative bg-[#F5F5F5] border border-dashed border-[#04000D] p-3 sm:p-4 z-10 flex flex-col justify-between min-h-[110px]">
              <span class="font-mono text-[8px] sm:text-[9px] uppercase tracking-wider text-[#04000D]/60 font-bold">ESTIMASI MEDIA EXPOSURE</span>
              <div>
                <h3 class="text-2xl sm:text-4xl font-bold tracking-tighter text-[#04000D] leading-none mb-1 font-headline-lg riso-bleed tabular-nums">{{ formattedExposure }}</h3>
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
    <section id="timeline" class="bg-off-white riso-canvas py-12 md:py-16 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 animate-fade-in relative overflow-hidden" data-reveal>
      <div class="absolute inset-0 bg-[radial-gradient(#04000D_1px,transparent_1px)] [background-size:24px_24px] opacity-[0.02] pointer-events-none z-0"></div>
      <div class="absolute inset-0 bg-noise-grain opacity-[0.015] pointer-events-none z-0"></div>

      <div class="max-w-container-max mx-auto relative z-10">
        
        <!-- HEADER BLOCK (Matching image_f0d77e.png) -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10 select-none border-b-2 border-[#04000D] pb-6">
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
        <div class="relative mt-12 select-none">
          
          <!-- Central Connecting Spine -->
          <!-- Desktop: Perfect Center -->
          <div class="hidden lg:block absolute left-1/2 -translate-x-1/2 top-4 bottom-4 w-1 border-l-4 border-dashed border-slate-300/80 z-0"></div>
          <!-- Mobile: Left Align -->
          <div class="lg:hidden absolute left-8 top-4 bottom-4 w-1 border-l-4 border-dashed border-slate-300/80 z-0"></div>

          <div class="flex flex-col gap-10 md:gap-12">
            
            <!-- PHASE 01 -->
            <div class="relative grid grid-cols-1 lg:grid-cols-[1fr_auto_1fr] items-start gap-8 lg:gap-12 w-full">
              <!-- Left Card -->
              <div 
                v-motion
                :initial="{ opacity: 0, x: -40 }"
                :visible-once="{ opacity: 1, x: 0, transition: { duration: 500 } }"
                class="lg:text-right flex flex-col items-start lg:items-end w-full lg:order-1 order-3 pl-16 lg:pl-0"
              >
                <div class="w-full max-w-xl text-left bg-white border-3 border-[#8B5CF6] rounded-2xl p-5 transition-all duration-300 hover:scale-[1.01]" style="box-shadow: 6px 6px 0px 0px #8B5CF6;">
                  <!-- Status: Terlaksana -->
                  <div class="flex items-center gap-1.5 mb-3">
                    <span class="bg-emerald-100 text-emerald-700 px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                      Terlaksana
                    </span>
                  </div>
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
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-[#8B5CF6] border-4 border-[#8B5CF6] flex items-center justify-center shadow-[2px_2px_0px_0px_#04000D]">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-white"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
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
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-white border-4 border-[#10B981] flex items-center justify-center font-mono text-sm md:text-base font-extrabold text-[#10B981] shadow-[2px_2px_0px_0px_#04000D] ring-4 ring-[#10B981]/30 animate-pulse">
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
                <div class="w-full max-w-xl text-left bg-white border-3 border-[#10B981] rounded-2xl p-5 transition-all duration-300 hover:scale-[1.01]" style="box-shadow: 6px 6px 0px 0px #10B981;">
                  <!-- Status: Segera -->
                  <div class="flex items-center gap-1.5 mb-3">
                    <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 animate-pulse">
                      ◉ Segera — Juli
                    </span>
                  </div>
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
                <div class="w-full max-w-xl text-left bg-white border-3 border-[#3B82F6] rounded-2xl p-5 transition-all duration-300 hover:scale-[1.01]" style="box-shadow: 6px 6px 0px 0px #3B82F6;">
                  <!-- Status: Segera -->
                  <div class="flex items-center gap-1.5 mb-3">
                    <span class="bg-blue-50 text-blue-600 border border-blue-200 px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 animate-pulse">
                      ◉ Segera — Juli
                    </span>
                  </div>
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
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-white border-4 border-[#3B82F6] flex items-center justify-center font-mono text-sm md:text-base font-extrabold text-[#3B82F6] shadow-[2px_2px_0px_0px_#04000D] ring-4 ring-[#3B82F6]/30 animate-pulse">
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
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-white border-4 border-dashed border-[#F59E0B]/50 flex items-center justify-center font-mono text-sm md:text-base font-extrabold text-[#F59E0B]/40 shadow-none">
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
                <div class="w-full max-w-xl text-left bg-white/80 border-2 border-dashed border-[#F59E0B]/40 rounded-2xl p-5 transition-all duration-300 opacity-60">
                  <!-- Status: Mendatang -->
                  <div class="flex items-center gap-1.5 mb-3">
                    <span class="bg-slate-100 text-slate-400 px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold uppercase tracking-wider">
                      Mendatang
                    </span>
                  </div>
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
                <div class="w-full max-w-xl text-left bg-white/80 border-2 border-dashed border-[#EF4444]/40 rounded-2xl p-6 transition-all duration-300 opacity-60">
                  <!-- Status: Mendatang -->
                  <div class="flex items-center gap-1.5 mb-3">
                    <span class="bg-slate-100 text-slate-400 px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold uppercase tracking-wider">
                      Mendatang
                    </span>
                  </div>
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
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-white border-4 border-dashed border-[#EF4444]/50 flex items-center justify-center font-mono text-sm md:text-base font-extrabold text-[#EF4444]/40 shadow-none">
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
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-[#0F172A] border-4 border-dashed border-[#F59E0B]/50 flex items-center justify-center shadow-none opacity-60">
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
                <div class="w-full max-w-xl text-left bg-[#0F172A]/80 border-2 border-dashed border-[#F59E0B]/40 rounded-2xl p-6 transition-all duration-300 opacity-60">
                  <!-- Status: Mendatang -->
                  <div class="flex items-center gap-1.5 mb-3">
                    <span class="bg-slate-700 text-slate-400 px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold uppercase tracking-wider">
                      Mendatang
                    </span>
                  </div>
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
    <section id="detail-kegiatan" class="bg-off-white riso-canvas py-10 md:py-14 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 animate-fade-in relative overflow-hidden" data-reveal>
      <!-- Background Decorative Stamp Shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'ry5 1.webp')" 
        alt="Decorative Riso Plate Shard" 
        class="absolute top-36 -right-24 w-48 md:w-80 opacity-20 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />

      <div class="max-w-container-max mx-auto relative z-10">
        
        <!-- Section Header -->
        <div class="mb-8 text-center md:text-left select-none">
          <span class="font-mono text-xs uppercase tracking-widest font-extrabold text-[#04000D]/60">EXPLORE PROPOSAL DETAILS</span>
          <h2 class="font-bold text-2xl md:text-3xl tracking-tighter text-[#04000D] mt-1 riso-bleed">Detail Kegiatan Index</h2>
        </div>

        <!-- Accordion Rows Container -->
        <div class="border border-[#04000D]/20 divide-y divide-[#04000D]/20 bg-white">
          
          <!-- Accordion Row 1: Identity & Foundation -->
          <div class="transition-all duration-200">
            <button 
              @click="activeZineIndex = activeZineIndex === 0 ? -1 : 0"
              class="w-full text-left py-4 px-5 sm:px-6 flex items-start sm:items-center justify-between font-bold text-[#04000D] hover:bg-[#FDE047]/5 transition-colors focus:outline-none select-none gap-4"
              :class="activeZineIndex === 0 ? 'bg-[#FDE047]/5' : ''"
            >
              <div class="flex items-start sm:items-center gap-4 sm:gap-6 flex-1 min-w-0">
                <span class="font-mono text-base text-[#04000D]/60 shrink-0">01/</span>
                <span class="text-lg sm:text-xl tracking-tight uppercase break-words whitespace-normal text-left">IDENTITY &amp; FOUNDATION</span>
              </div>
              <span class="shrink-0 font-mono text-xl sm:text-2xl transition-transform duration-200" :class="activeZineIndex === 0 ? 'rotate-45' : ''">+</span>
            </button>
            
            <div 
              v-show="activeZineIndex === 0" 
              class="px-5 sm:px-6 pb-6 pt-2 border-t border-dashed border-[#04000D]/10 bg-off-white/25"
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

          <!-- Row 2: Roadshow Inklusif (Digital Symphony Tour) [LINK ROUTING] -->
          <div class="transition-all duration-200">
            <router-link 
              :to="{ path: '/roadshow', state: { fromSection: 'roadshow-section' } }"
              class="w-full text-left py-6 px-6 sm:px-8 flex items-start sm:items-center justify-between font-bold text-[#04000D] hover:bg-[#FDE047]/5 transition-colors focus:outline-none select-none gap-4 block"
            >
              <div class="flex items-start sm:items-center gap-4 sm:gap-6 flex-1 min-w-0 flex-wrap sm:flex-nowrap">
                <span class="font-mono text-base text-[#04000D]/60 shrink-0">02/</span>
                <span class="text-lg sm:text-xl tracking-tight uppercase break-words whitespace-normal text-left flex flex-wrap items-center gap-2 sm:gap-3">
                  ROADSHOW INKLUSIF (Digital Symphony Tour)
                  <span class="inline-block bg-white text-[#FF3D8B] font-mono text-[9px] font-extrabold px-2 py-0.5 rounded-none leading-none select-none tracking-widest border-2 border-[#FF3D8B] shrink-0">LIVE</span>
                </span>
              </div>
              <span class="shrink-0 font-mono text-xl sm:text-2xl transition-transform duration-200">↗</span>
            </router-link>
          </div>

          <!-- Row 3: Arena Kompetisi (Digital Competitions) [LINK ROUTING] -->
          <div class="transition-all duration-200">
            <router-link 
              :to="{ path: '/kompetisi', state: { fromSection: 'competitions-section' } }"
              class="w-full text-left py-6 px-6 sm:px-8 flex items-start sm:items-center justify-between font-bold text-[#04000D] hover:bg-[#FDE047]/5 transition-colors focus:outline-none select-none gap-4 block"
            >
              <div class="flex items-start sm:items-center gap-4 sm:gap-6 flex-1 min-w-0 flex-wrap sm:flex-nowrap">
                <span class="font-mono text-base text-[#04000D]/60 shrink-0">03/</span>
                <span class="text-lg sm:text-xl tracking-tight uppercase break-words whitespace-normal text-left flex flex-wrap items-center gap-2 sm:gap-3">
                  ARENA KOMPETISI (Digital Competitions)
                  <span class="inline-block bg-white text-[#10B981] font-mono text-[9px] font-extrabold px-2 py-0.5 rounded-none leading-none select-none tracking-widest border-2 border-[#10B981] shrink-0">OPEN</span>
                </span>
              </div>
              <span class="shrink-0 font-mono text-xl sm:text-2xl transition-transform duration-200">↗</span>
            </router-link>
          </div>

          <!-- Accordion Row 4: Benchmark & Exploration (Visitasi Industri) -->
          <div class="transition-all duration-200">
            <button 
              @click="activeZineIndex = activeZineIndex === 3 ? -1 : 3"
              class="w-full text-left py-4 px-5 sm:px-6 flex items-start sm:items-center justify-between font-bold text-[#04000D] hover:bg-[#FDE047]/5 transition-colors focus:outline-none select-none gap-4"
              :class="activeZineIndex === 3 ? 'bg-[#FDE047]/5' : ''"
            >
              <div class="flex items-start sm:items-center gap-4 sm:gap-6 flex-1 min-w-0 flex-wrap sm:flex-nowrap">
                <span class="font-mono text-base text-[#04000D]/60 shrink-0">04/</span>
                <span class="text-lg sm:text-xl tracking-tight uppercase break-words whitespace-normal text-left flex flex-wrap items-center gap-2 sm:gap-3">
                  BENCHMARK &amp; EXPLORATION (Visitasi Industri)
                  <span class="inline-block bg-[#04000D] text-[#FDE047] font-mono text-[9px] font-extrabold px-2 py-0.5 rounded-none leading-none select-none tracking-widest border border-[#04000D] shrink-0">COMING SOON</span>
                </span>
              </div>
              <span class="shrink-0 font-mono text-xl sm:text-2xl transition-transform duration-200" :class="activeZineIndex === 3 ? 'rotate-45' : ''">+</span>
            </button>
            
            <div 
              v-show="activeZineIndex === 3" 
              class="px-5 sm:px-6 pb-6 pt-2 border-t border-dashed border-[#04000D]/10 bg-off-white/25"
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

          <!-- Accordion Row 5: Local Intellectual Series (TEDxUNTAD) -->
          <div class="transition-all duration-200">
            <button 
              @click="activeZineIndex = activeZineIndex === 4 ? -1 : 4"
              class="w-full text-left py-4 px-5 sm:px-6 flex items-start sm:items-center justify-between font-bold text-[#04000D] hover:bg-[#FDE047]/5 transition-colors focus:outline-none select-none gap-4"
              :class="activeZineIndex === 4 ? 'bg-[#FDE047]/5' : ''"
            >
              <div class="flex items-start sm:items-center gap-4 sm:gap-6 flex-1 min-w-0 flex-wrap sm:flex-nowrap">
                <span class="font-mono text-base text-[#04000D]/60 shrink-0">05/</span>
                <span class="text-lg sm:text-xl tracking-tight uppercase break-words whitespace-normal text-left flex flex-wrap items-center gap-2 sm:gap-3">
                  LOCAL INTELLECTUAL SERIES (TEDxUNTAD)
                  <span class="inline-block bg-[#04000D] text-[#FDE047] font-mono text-[9px] font-extrabold px-2 py-0.5 rounded-none leading-none select-none tracking-widest border border-[#04000D] shrink-0">COMING SOON</span>
                </span>
              </div>
              <span class="shrink-0 font-mono text-xl sm:text-2xl transition-transform duration-200" :class="activeZineIndex === 4 ? 'rotate-45' : ''">+</span>
            </button>
            
            <div 
              v-show="activeZineIndex === 4" 
              class="px-5 sm:px-6 pb-6 pt-2 border-t border-dashed border-[#04000D]/10 bg-off-white/25"
            >
              <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                <div class="md:col-span-4">
                  <span class="font-mono text-[10px] uppercase tracking-widest font-bold text-[#04000D]/60 block mb-2">Intellectual Checkpoints</span>
                  <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Curated TEDx Stages &amp; Local Speakers
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Regional Resonance Panels
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Interdisciplinary Knowledge Sharing
                    </div>
                  </div>
                </div>
                <div class="md:col-span-8 font-body-md text-sm sm:text-base text-[#04000D]/80 leading-relaxed">
                  <p class="mb-4">
                    TEDxUNTAD menghadirkan serangkaian pembicara lokal dan nasional terkurasi untuk membahas isu-isu krusial seputar disrupsi teknologi, kebudayaan, dan inovasi inklusif guna memantik gagasan besar di Sulawesi Tengah.
                  </p>
                  <p class="font-mono text-xs text-[#04000D]/60 italic">
                    ✦ Lisensi Resmi TEDx &amp; Divisi Intelektual HMTI ✦
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Accordion Row 6: The Grand Symphony (Malam Puncak) -->
          <div class="transition-all duration-200">
            <button 
              @click="activeZineIndex = activeZineIndex === 5 ? -1 : 5"
              class="w-full text-left py-4 px-5 sm:px-6 flex items-start sm:items-center justify-between font-bold text-[#04000D] hover:bg-[#FDE047]/5 transition-colors focus:outline-none select-none gap-4"
              :class="activeZineIndex === 5 ? 'bg-[#FDE047]/5' : ''"
            >
              <div class="flex items-start sm:items-center gap-4 sm:gap-6 flex-1 min-w-0 flex-wrap sm:flex-nowrap">
                <span class="font-mono text-base text-[#04000D]/60 shrink-0">06/</span>
                <span class="text-lg sm:text-xl tracking-tight uppercase break-words whitespace-normal text-left flex flex-wrap items-center gap-2 sm:gap-3">
                  THE GRAND SYMPHONY (Malam Puncak)
                  <span class="inline-block bg-[#04000D] text-[#FDE047] font-mono text-[9px] font-extrabold px-2 py-0.5 rounded-none leading-none select-none tracking-widest border border-[#04000D] shrink-0">COMING SOON</span>
                </span>
              </div>
              <span class="shrink-0 font-mono text-xl sm:text-2xl transition-transform duration-200" :class="activeZineIndex === 5 ? 'rotate-45' : ''">+</span>
            </button>
            
            <div 
              v-show="activeZineIndex === 5" 
              class="px-5 sm:px-6 pb-6 pt-2 border-t border-dashed border-[#04000D]/10 bg-off-white/25"
            >
              <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                <div class="md:col-span-4">
                  <span class="font-mono text-[10px] uppercase tracking-widest font-bold text-[#04000D]/60 block mb-2">Grand Orchestration</span>
                  <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> 3-Days Innovation Expo: 30+ Booths
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Visual Projection Mapping
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-[#04000D]">
                      <span class="w-1.5 h-1.5 bg-[#04000D] rounded-full"></span> Grand Closing Concert
                    </div>
                  </div>
                </div>
                <div class="md:col-span-8 font-body-md text-sm sm:text-base text-[#04000D]/80 leading-relaxed">
                  <p class="mb-4">
                    Malam puncak termegah yang memadukan pameran inovasi digital UMKM selama tiga hari penuh, awarding night bagi pemenang kompetisi nasional, dan konser penutup spektakuler bersama Guest Star, dibalut dengan visual projection mapping bertema Simfoni Digital.
                  </p>
                  <div class="inline-flex items-center border-2 border-dashed border-[#04000D] p-2 bg-transparent gap-3 select-none mb-4">
                    <span class="font-mono text-xs text-[#04000D] font-extrabold uppercase tracking-widest">COMING SOON PRINT STAMP: [ SECRET GUEST STAR - TBA ]</span>
                  </div>
                  <p class="font-mono text-xs text-[#04000D]/60 italic block mt-2">
                    ✦ BPH &amp; Seluruh Divisi Kerja I-FEST 2026 ✦
                  </p>
                </div>
              </div>
            </div>
          </div>        
        </div>
      </div>
    </section>

    <!-- SECTION O: GALERI JEJAK LANGKAH (I-FEST 2025 Historical Archive) -->
    <section id="galeri-jejak-langkah" class="bg-[#8839FF] py-12 sm:py-16 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#F5F5F5]/20 relative overflow-hidden select-none" data-reveal>
      <!-- Coarse dots and noise overlays specifically for dark canvas -->
      <div class="absolute inset-0 bg-[radial-gradient(#F5F5F5_1px,transparent_1px)] [background-size:20px_20px] opacity-[0.03] pointer-events-none z-0"></div>
      <div class="absolute inset-0 bg-noise-grain opacity-[0.02] pointer-events-none z-0"></div>

      <!-- Background Decorative Stamp Shards (Yellow on Purple contrast) -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'sy5 1.webp')" 
        alt="Decorative Riso Plate Shard Right" 
        class="absolute -bottom-8 -right-16 w-36 md:w-56 opacity-35 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'ry2 1.webp')" 
        alt="Decorative Riso Plate Shard Left" 
        class="absolute -top-12 -left-12 w-36 md:w-56 opacity-25 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block rotate-45" 
      />

      <!-- Left Margin Technical Calibration Strip -->
      <div class="hidden lg:flex flex-col items-center justify-between absolute left-3 xl:left-6 top-0 bottom-0 py-8 w-10 z-20 pointer-events-none border-r border-dashed border-[#F5F5F5]/10 pr-3">
        <!-- Top Registration Mark -->
        <svg viewBox="0 0 24 24" class="w-5 h-5 text-[#FDE047]/60" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="12" cy="12" r="8" />
          <line x1="12" y1="2" x2="12" y2="22" />
          <line x1="2" y1="12" x2="22" y2="12" />
        </svg>
        
        <!-- Center Group: Color Calibration Bar & Info Text -->
        <div class="flex flex-col items-center gap-6 my-auto">
          <!-- Color Calibration Bar -->
          <div class="flex flex-col border border-[#F5F5F5]/20 p-1 bg-[#0D0B14] gap-1">
            <div class="w-3 h-3 bg-[#8839FF]" title="Primary Purple"></div>
            <div class="w-3 h-3 bg-[#FF3D8B]" title="Accent Pink"></div>
            <div class="w-3 h-3 bg-[#FDE047]" title="Soft Yellow"></div>
            <div class="w-3 h-3 bg-[#F5F5F5]" title="Off White"></div>
            <div class="w-3 h-3 bg-[#0D0B14]" title="Dark Charcoal"></div>
          </div>
          
          <!-- Rotated Monospace Text -->
          <div class="font-mono text-[9px] font-bold tracking-[0.25em] text-[#F5F5F5]/30 uppercase select-none whitespace-nowrap -rotate-90 origin-center my-12">
            I-FEST 2025 // HISTORICAL DOSSIER // SYS: REG-01
          </div>
        </div>

        <!-- Bottom Registration Mark -->
        <svg viewBox="0 0 24 24" class="w-5 h-5 text-[#FDE047]/60" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="12" cy="12" r="8" />
          <line x1="12" y1="2" x2="12" y2="22" />
          <line x1="2" y1="12" x2="22" y2="12" />
        </svg>
      </div>

      <!-- Right Margin Technical Stamp & Barcode -->
      <div class="hidden lg:flex flex-col items-center justify-between absolute right-3 xl:right-6 top-0 bottom-0 py-8 w-10 z-20 pointer-events-none border-l border-dashed border-[#F5F5F5]/10 pl-3">
        <!-- Top Registration Mark -->
        <svg viewBox="0 0 24 24" class="w-5 h-5 text-[#FDE047]/60" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="12" cy="12" r="8" />
          <line x1="12" y1="2" x2="12" y2="22" />
          <line x1="2" y1="12" x2="22" y2="12" />
        </svg>

        <!-- Center Group: Barcode & Status Text -->
        <div class="flex flex-col items-center gap-6 my-auto">
          <!-- Rotated Monospace Text -->
          <div class="font-mono text-[9px] font-bold tracking-[0.25em] text-[#F5F5F5]/30 uppercase select-none whitespace-nowrap rotate-90 origin-center my-12">
            INK MULTIPLY: TRUE // DENSITY: 85% // REGISTERED
          </div>

          <!-- Vertical Barcode Block -->
          <div class="flex flex-col items-center justify-center p-1.5 bg-[#0D0B14] border border-[#F5F5F5]/20 rotate-90 my-2">
            <svg viewBox="0 0 40 12" class="w-12 h-4 text-[#FDE047]">
              <!-- Simple barcode lines -->
              <rect x="0" y="0" width="2" height="12" fill="currentColor" />
              <rect x="3" y="0" width="1" height="12" fill="currentColor" />
              <rect x="5" y="0" width="3" height="12" fill="currentColor" />
              <rect x="9" y="0" width="1" height="12" fill="currentColor" />
              <rect x="11" y="0" width="2" height="12" fill="currentColor" />
              <rect x="14" y="0" width="1" height="12" fill="currentColor" />
              <rect x="16" y="0" width="4" height="12" fill="currentColor" />
              <rect x="21" y="0" width="2" height="12" fill="currentColor" />
              <rect x="24" y="0" width="1" height="12" fill="currentColor" />
              <rect x="26" y="0" width="3" height="12" fill="currentColor" />
              <rect x="30" y="0" width="2" height="12" fill="currentColor" />
              <rect x="33" y="0" width="1" height="12" fill="currentColor" />
              <rect x="35" y="0" width="2" height="12" fill="currentColor" />
              <rect x="38" y="0" width="2" height="12" fill="currentColor" />
            </svg>
          </div>
        </div>

        <!-- Bottom Registration Mark -->
        <svg viewBox="0 0 24 24" class="w-5 h-5 text-[#FDE047]/60" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="12" cy="12" r="8" />
          <line x1="12" y1="2" x2="12" y2="22" />
          <line x1="2" y1="12" x2="22" y2="12" />
        </svg>
      </div>

      <div class="max-w-container-max mx-auto relative z-10">
        
        <!-- Section Header -->
        <div class="mb-12 text-center lg:text-left select-none">
          <span class="font-mono text-xs uppercase tracking-widest font-extrabold text-[#FDE047]">HISTORICAL DOSSIER</span>
          <h2 class="font-bold text-2xl md:text-3xl tracking-tighter text-[#F5F5F5] mt-1 riso-bleed">Arsip Resonansi 2025.</h2>
        </div>

        <!-- Two-column grid on desktop (lg:grid-cols-2), single column on mobile -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
          
          <!-- Left Column: The Polaroid Stack -->
          <div class="flex flex-col items-center justify-center w-full">
            <!-- Polaroid Card Stack Container -->
            <div class="relative w-[280px] h-[320px] sm:w-[340px] sm:h-[390px] md:w-[380px] md:h-[420px] select-none flex items-center justify-center">
              <div
                v-for="(item, index) in galleryImages"
                :key="index"
                class="absolute inset-0 bg-white border-2 md:border-3 border-[#04000D] p-3 pb-8 rounded-none transition-all duration-300 ease-out select-none cursor-pointer flex flex-col justify-between"
                :style="getCardStyle(index)"
                @click="index === activeGalleryIndex ? nextCard() : null"
                style="box-shadow: 6px 6px 0px 0px rgba(4,0,13,0.3);"
              >
                <!-- Photo frame -->
                <div class="relative w-full aspect-[4/3] rounded-none overflow-hidden bg-[#FAF9F6] border-2 border-[#04000D]">
                  <img 
                    :src="item.src" 
                    :alt="item.alt" 
                    class="w-full h-full object-cover grayscale mix-blend-multiply contrast-125 pointer-events-none select-none"
                  />
                  <div class="absolute inset-0 bg-[#04000D]/5 mix-blend-multiply pointer-events-none"></div>
                </div>
                
                <div class="mt-4 text-center">
                  <span class="font-mono text-[9px] sm:text-[10px] font-black uppercase tracking-wider text-[#04000D]">
                    ✦ {{ item.title }} ✦
                  </span>
                </div>
              </div>
            </div>
            
            <p class="text-[10px] text-[#F5F5F5]/50 font-mono mt-6 uppercase tracking-widest hidden lg:block">
              * Klik foto teratas untuk berpindah ke arsip berikutnya
            </p>
          </div>

          <!-- Right Column: Controls, Index Panel & Metadata -->
          <div class="flex flex-col gap-6 w-full max-w-lg mx-auto lg:mx-0">
            
            <!-- Index Control Panel -->
            <div class="bg-[#0D0B14] border border-[#F5F5F5]/15 p-5 text-left">
              <div class="flex justify-between items-baseline mb-4 font-mono text-[9px] uppercase tracking-widest text-[#F5F5F5]/40 font-bold">
                <span>DOCUMENT DIRECTORY</span>
                <span>{{ galleryImages.length }} FILES DETECTED</span>
              </div>
              
              <!-- Grid of buttons -->
              <div class="grid grid-cols-6 sm:grid-cols-11 lg:grid-cols-6 gap-2">
                <button
                  v-for="(_, index) in galleryImages"
                  :key="index"
                  @click="activeGalleryIndex = index"
                  class="font-mono text-xs font-bold p-2 text-center border transition-all duration-150 active:scale-95"
                  :class="activeGalleryIndex === index 
                    ? 'bg-[#FDE047] text-[#04000D] border-[#FDE047] shadow-[2px_2px_0px_0px_rgba(255,61,139,0.9)]' 
                    : 'text-[#F5F5F5]/70 border-[#F5F5F5]/15 hover:bg-white/5 hover:text-white'"
                >
                  {{ String(index + 1).padStart(2, '0') }}
                </button>
              </div>
            </div>

            <!-- Controls and File Navigation -->
            <div class="flex items-center justify-between bg-[#0D0B14] border border-[#F5F5F5]/15 p-4 select-none">
              <button 
                @click="prevCard" 
                class="riso-btn-plate bg-[#0D0B14] text-white border border-[#F5F5F5]/20 w-10 h-10 rounded-full flex items-center justify-center font-bold active:scale-95 transition-transform"
                style="--plate-color: #FF3D8B;"
              >
                ←
              </button>
              
              <span class="font-mono text-xs text-[#FDE047] font-extrabold uppercase tracking-widest">
                FILE_INDEX: [ {{ String(activeGalleryIndex + 1).padStart(2, '0') }} // {{ String(galleryImages.length).padStart(2, '0') }} ]
              </span>
              
              <button 
                @click="nextCard" 
                class="riso-btn-plate bg-[#0D0B14] text-white border border-[#F5F5F5]/20 w-10 h-10 rounded-full flex items-center justify-center font-bold active:scale-95 transition-transform"
                style="--plate-color: #FF3D8B;"
              >
                →
              </button>
            </div>

            <!-- Active File Metadata Card -->
            <div class="bg-[#0D0B14] border border-[#F5F5F5]/15 p-6 text-left select-none w-full min-h-[140px] transition-all duration-300 shadow-[6px_6px_0px_0px_rgba(253,224,71,0.1)]">
              <div class="flex justify-between items-baseline mb-3 font-mono text-[9px] uppercase tracking-widest text-[#FDE047] font-bold">
                <span>METADATA DOSSIER</span>
                <span>IFEST_RESONANCE_2025</span>
              </div>
              <h4 class="font-mono text-sm sm:text-base text-[#F5F5F5]/90 tracking-wide font-black uppercase">
                {{ galleryImages[activeGalleryIndex]?.title }}
              </h4>
              <p class="font-mono text-xs text-[#F5F5F5]/60 mt-3.5 leading-relaxed">
                {{ galleryImages[activeGalleryIndex]?.description }}
              </p>
            </div>

          </div>

        </div>

      </div>
    </section>

    <!-- SECTION M: KONTRIBUSI SYMPHONY (QRIS Treasurer & Saweria Donation) -->
    <section class="bg-[#F5F5F5] riso-canvas py-8 md:py-12 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 animate-fade-in relative overflow-hidden" data-reveal>
      <!-- Background Decorative Stamp Shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'sg1 1.webp')" 
        alt="Decorative Riso Plate Shard" 
        class="absolute -top-12 -left-16 w-32 md:w-52 opacity-20 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />

      <div class="max-w-container-max mx-auto relative z-10">
        <div class="mb-6 text-center md:text-left">
          <span class="font-mono text-xs uppercase tracking-widest font-bold text-[#04000D]/60">PARTISIPASI &amp; DUKUNGAN</span>
          <h2 class="font-bold text-2xl md:text-3xl tracking-tighter text-[#04000D] mt-1 riso-bleed">Kontribusi Symphony</h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-stretch">
          
          <!-- Left Column: The Stamped QRIS (Even width on large screens) -->
          <div class="flex flex-col justify-between p-5 sm:p-6 bg-white border border-[#04000D] rounded-none select-none">
            <div class="flex flex-col items-center">
              <span class="font-mono text-[9px] uppercase tracking-widest text-[#04000D]/40 mb-4">REGISTRATION STAMP: QR-02</span>
              
              <!-- QR Code Stylized Vector Stamp -->
              <div class="relative w-48 h-48 border border-[#04000D] p-3 bg-white flex items-center justify-center shadow-none mb-6">
                <!-- Stylized QRIS Stamp Overlay in Multiply mode -->
                <div class="absolute inset-0 bg-[#FDE047]/5 mix-blend-multiply pointer-events-none"></div>
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
          <div class="flex flex-col justify-between p-5 sm:p-6 bg-white border border-[#04000D] rounded-none">
            <div>
              <p class="font-mono text-[9px] uppercase tracking-widest text-[#04000D]/40 mb-6">LIVE DONOR LEADERBOARD (REALTIME)</p>
              
              <!-- Leaderboard List -->
              <div class="flex flex-col select-none">
                
                <!-- Row 1 -->
                <div class="flex items-center justify-between py-3 border-b border-[#04000D]/20 opacity-70 hover:opacity-100 transition-opacity duration-200">
                  <div class="flex items-center gap-4">
                    <span class="font-mono text-xs text-[#04000D]/60 font-bold">#01</span>
                    <span class="text-base text-[#04000D]" style="font-weight: 540;">Fauzi &amp; Rian (Sponsorship Team)</span>
                  </div>
                  <span class="font-mono text-base font-bold text-[#04000D]">Rp 5.000.000</span>
                </div>

                <!-- Row 2 -->
                <div class="flex items-center justify-between py-3 border-b border-[#04000D]/20 opacity-70 hover:opacity-100 transition-opacity duration-200">
                  <div class="flex items-center gap-4">
                    <span class="font-mono text-xs text-[#04000D]/60 font-bold">#02</span>
                    <span class="text-base text-[#04000D]" style="font-weight: 540;">HMTI Cabinet 2026</span>
                  </div>
                  <span class="font-mono text-base font-bold text-[#04000D]">Rp 3.500.000</span>
                </div>

                <!-- Row 3 -->
                <div class="flex items-center justify-between py-3 border-b border-[#04000D]/20 opacity-70 hover:opacity-100 transition-opacity duration-200">
                  <div class="flex items-center gap-4">
                    <span class="font-mono text-xs text-[#04000D]/60 font-bold">#03</span>
                    <span class="text-base text-[#04000D]" style="font-weight: 540;">Hannah Asa Dev Team</span>
                  </div>
                  <span class="font-mono text-base font-bold text-[#04000D]">Rp 2.500.000</span>
                </div>

                <!-- Row 4 -->
                <div class="flex items-center justify-between py-3 border-b border-[#04000D]/20 opacity-70 hover:opacity-100 transition-opacity duration-200">
                  <div class="flex items-center gap-4">
                    <span class="font-mono text-xs text-[#04000D]/60 font-bold">#04</span>
                    <span class="text-base text-[#04000D]" style="font-weight: 540;">Alumnus Tadulako 2018</span>
                  </div>
                  <span class="font-mono text-base font-bold text-[#04000D]">Rp 1.500.000</span>
                </div>

                <!-- Row 5 -->
                <div class="flex items-center justify-between py-3 border-b border-[#04000D]/20 opacity-70 hover:opacity-100 transition-opacity duration-200">
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
    <section id="bph-matrix" class="bg-[#F5F5F5] riso-canvas py-12 md:py-16 px-4 sm:px-6 md:px-lg border-b border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
      <div class="max-w-container-max mx-auto relative z-10">
        
        <!-- Section Header -->
        <div class="mb-10 md:mb-14">
          <p class="font-mono text-[#04000D] text-xs uppercase tracking-widest mb-3 font-bold">INTERNAL COMMAND MATRIX</p>
          <h2 class="font-black text-4xl md:text-6xl tracking-[-0.04em] leading-none text-[#04000D] riso-bleed">Struktur Orkestrasi.</h2>
        </div>

        <!-- Asymmetrical Organizational Blueprint Rows -->
        <div class="border-t-3 border-x-3 border-[#04000D] bg-white pb-0 mb-10 select-none">
          
          <!-- ROW 1: NAKITA SEMESTA (Project Manager) -->
          <div class="grid grid-cols-1 md:grid-cols-[1.2fr_2.8fr] border-b-3 border-[#04000D] overflow-hidden group">
            <!-- Left Side Column (Color Ink Block) -->
            <div class="bg-[#D86BFF] p-5 md:p-6 flex flex-col justify-center items-center text-center border-b-3 md:border-b-0 border-[#04000D] transition-opacity duration-150 group-hover:opacity-95">
              <span class="font-mono text-xs tracking-widest border-b-2 border-[#04000D] pb-1 block w-full mb-6 font-bold text-[#04000D]">✦ PROJECT MANAGER ✦</span>
              <div class="w-20 h-20 md:w-24 md:h-24 bg-white border-[3px] border-[#04000D] shadow-[4px_4px_0px_0px_#04000D] overflow-hidden flex items-center justify-center rotate-[-2deg] transition-all duration-200 group-hover:scale-105 select-none relative">
                <img 
                  v-if="panitiaData['Koor Inti']?.coordinators.find(p => p.role === 'Project Manager')?.imgSrc"
                  :src="panitiaData['Koor Inti']?.coordinators.find(p => p.role === 'Project Manager')?.imgSrc" 
                  alt="Nakita Semesta" 
                  class="w-full h-full object-cover object-top contrast-110"
                />
                <span v-else class="font-mono font-black text-xl md:text-2xl text-[#04000D] uppercase">NS</span>
              </div>
            </div>
            <!-- Right Side Column (The Dossier Block) -->
            <div class="bg-[#F5F5F5] p-5 md:p-6 flex flex-col justify-center md:border-l-3 border-[#04000D] transition-colors duration-150 group-hover:bg-white">
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
            <div class="order-1 md:order-2 bg-[#FDE047] p-5 md:p-6 flex flex-col justify-center items-center text-center border-b-3 md:border-b-0 border-[#04000D] transition-opacity duration-150 group-hover:opacity-95">
              <span class="font-mono text-xs tracking-widest border-b-2 border-[#04000D] pb-1 block w-full mb-6 font-bold text-[#04000D]">✦ PIC I-FEST 2026 ✦</span>
              <div class="w-20 h-20 md:w-24 md:h-24 bg-white border-[3px] border-[#04000D] shadow-[4px_4px_0px_0px_#04000D] overflow-hidden flex items-center justify-center rotate-[3deg] transition-all duration-200 group-hover:scale-105 select-none relative">
                <img 
                  v-if="panitiaData['Koor Inti']?.coordinators.find(p => p.role === 'PIC')?.imgSrc"
                  :src="panitiaData['Koor Inti']?.coordinators.find(p => p.role === 'PIC')?.imgSrc" 
                  alt="Dareean A. Raffi" 
                  class="w-full h-full object-cover object-top contrast-110"
                />
                <span v-else class="font-mono font-black text-xl md:text-2xl text-[#04000D] uppercase">DR</span>
              </div>
            </div>
            <!-- Right Side Column (The Dossier Block) - Positioned on the Left on Desktop -->
            <div class="order-2 md:order-1 bg-[#F5F5F5] p-5 md:p-6 flex flex-col justify-center md:border-r-3 border-[#04000D] transition-colors duration-150 group-hover:bg-white">
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
            <div class="bg-[#8839FF] p-5 md:p-6 flex flex-col justify-center items-center text-center border-b-3 md:border-b-0 border-[#04000D] transition-opacity duration-150 group-hover:opacity-95">
              <span class="font-mono text-xs tracking-widest border-b-2 border-[#FDE047] pb-1 block w-full mb-6 font-bold text-[#FDE047]">✦ KETUA PANITIA ✦</span>
              <div class="w-20 h-20 md:w-24 md:h-24 bg-white border-[3px] border-[#04000D] shadow-[4px_4px_0px_0px_#04000D] overflow-hidden flex items-center justify-center rotate-[-1deg] transition-all duration-200 group-hover:scale-105 select-none relative">
                <img 
                  v-if="panitiaData['Koor Inti']?.coordinators.find(p => p.role === 'Ketua Panitia')?.imgSrc"
                  :src="panitiaData['Koor Inti']?.coordinators.find(p => p.role === 'Ketua Panitia')?.imgSrc" 
                  alt="Gabriel Kristofan" 
                  class="w-full h-full object-cover object-top contrast-110"
                />
                <span v-else class="font-mono font-black text-xl md:text-2xl text-[#04000D] uppercase">GK</span>
              </div>
            </div>
            <!-- Right Side Column (The Dossier Block) -->
            <div class="bg-[#F5F5F5] p-5 md:p-6 flex flex-col justify-center md:border-l-3 border-[#04000D] transition-colors duration-150 group-hover:bg-white">
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
            <div class="order-1 md:order-2 bg-[#D86BFF] p-5 md:p-6 flex flex-col justify-center items-center text-center border-b-3 md:border-b-0 border-[#04000D] transition-opacity duration-150 group-hover:opacity-95">
              <span class="font-mono text-xs tracking-widest border-b-2 border-[#04000D] pb-1 block w-full mb-6 font-bold text-[#04000D]">✦ WAKIL KETUA PANITIA ✦</span>
              <div class="w-20 h-20 md:w-24 md:h-24 bg-white border-[3px] border-[#04000D] shadow-[4px_4px_0px_0px_#04000D] overflow-hidden flex items-center justify-center rotate-[2deg] transition-all duration-200 group-hover:scale-105 select-none relative">
                <img 
                  v-if="panitiaData['Koor Inti']?.coordinators.find(p => p.role === 'Wakil Ketua Panitia')?.imgSrc"
                  :src="panitiaData['Koor Inti']?.coordinators.find(p => p.role === 'Wakil Ketua Panitia')?.imgSrc" 
                  alt="Reyqal Syawalano" 
                  class="w-full h-full object-cover object-top contrast-110"
                />
                <span v-else class="font-mono font-black text-xl md:text-2xl text-[#04000D] uppercase">RS</span>
              </div>
            </div>
            <!-- Right Side Column (The Dossier Block) - Positioned on the Left on Desktop -->
            <div class="order-2 md:order-1 bg-[#F5F5F5] p-5 md:p-6 flex flex-col justify-center md:border-r-3 border-[#04000D] transition-colors duration-150 group-hover:bg-white">
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

        <!-- Division Command Board (Grid selection cards + panel below) -->
        <div class="mt-16 select-none animate-fade-in" data-reveal>
          <div class="mb-6 flex flex-col md:flex-row md:items-baseline md:justify-between gap-2 border-b-3 border-[#04000D] pb-3">
            <h3 class="font-black text-2xl md:text-4xl tracking-[-0.04em] uppercase text-[#04000D] riso-bleed">✦ DIVISI OPERASIONAL</h3>
            <span class="font-mono text-xs text-[#04000D]/60 uppercase tracking-wider font-bold">SELECT A DIVISION TO VIEW INDIVIDUAL ROSTER</span>
          </div>

          <!-- Polaroid Hero Banner (Foto Bersama Panitia Keseluruhan) -->
          <div v-if="fotoPanitiaKeseluruhan" class="mb-10 max-w-4xl mx-auto select-none">
            <div class="border-3 border-[#04000D] bg-white p-3 sm:p-4 shadow-[6px_6px_0px_0px_#04000D] rotate-[-0.5deg] hover:rotate-0 transition-transform duration-350 ease-out">
              <div class="relative aspect-[16/9] md:aspect-[21/9] overflow-hidden border border-[#04000D]/20 bg-[#04000D]/5">
                <img 
                  :src="fotoPanitiaKeseluruhan" 
                  alt="Foto Bersama Seluruh Panitia I-FEST 2026" 
                  class="w-full h-full object-cover grayscale contrast-125 brightness-95 hover:grayscale-0 transition-all duration-500 ease-in-out"
                />
              </div>
              <div class="mt-4 text-center">
                <span class="font-headline font-black text-xs md:text-sm tracking-[0.2em] text-[#04000D] uppercase font-mono">
                  ✦ ORCHESTRA OF INNOVATION // I-FEST 2026 FULL CREW ⚡ ✦
                </span>
              </div>
            </div>
          </div>

          <!-- Division Selection Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mb-8 select-none">
            <button
              v-for="(div, key) in panitiaData"
              :key="key"
              @click="activeDivisionTab = key"
              class="font-mono text-xs font-bold p-3 border-2 border-[#04000D] flex flex-col items-center justify-center gap-1 active:scale-95 transition-all duration-150 rounded-none shadow-[2px_2px_0px_0px_#04000D] cursor-pointer"
              :style="activeDivisionTab === key ? {
                backgroundColor: div.color,
                color: div.textColor,
                transform: 'translate(-2px, -2px)',
                boxShadow: '4px 4px 0px 0px #04000D'
              } : {
                backgroundColor: '#ffffff',
                color: '#04000D'
              }"
            >
              <span class="text-2xl mb-1">{{ div.emoji }}</span>
              <span class="font-black tracking-tight text-[10px] md:text-xs text-center leading-tight">{{ div.name.toUpperCase() }}</span>
              <span class="text-[9px] opacity-75 font-mono">({{ div.coordinators.length + div.members.length }} P)</span>
            </button>
          </div>

          <!-- Active Division Panel (Only selected division shown here) -->
          <div 
            v-if="panitiaData[activeDivisionTab]"
            class="border-3 border-[#04000D] bg-white relative overflow-hidden shadow-[4px_4px_0px_0px_#04000D] transition-all duration-300"
          >
            <!-- Division Header Strip -->
            <div 
              class="border-b-3 border-[#04000D] px-4 py-3 flex flex-wrap items-center justify-between gap-2 select-none"
              :style="{ backgroundColor: panitiaData[activeDivisionTab]?.color, color: panitiaData[activeDivisionTab]?.textColor }"
            >
              <div class="flex items-center gap-2">
                <span class="font-mono text-base">{{ panitiaData[activeDivisionTab]?.emoji }}</span>
                <h4 class="font-black text-lg md:text-xl uppercase tracking-tight">{{ panitiaData[activeDivisionTab]?.name.toUpperCase() }}</h4>
              </div>
              <span class="font-mono text-xs font-bold uppercase">
                {{ panitiaData[activeDivisionTab]?.coordinators.length + panitiaData[activeDivisionTab]?.members.length }} Personil
              </span>
            </div>

            <!-- Division Body -->
            <div class="p-5 md:p-6 bg-white relative z-10">
              <!-- Decorative Background Stamp Overlay -->
              <div class="absolute -right-6 -bottom-6 w-48 h-48 opacity-[0.03] pointer-events-none font-mono font-black text-[96px] uppercase tracking-tighter select-none rotate-12 flex items-center justify-center">
                {{ panitiaData[activeDivisionTab]?.emoji }}
              </div>

              <!-- Division Grid Layout -->
              <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 md:gap-8 relative z-10">
                
                <!-- Left Column: Concept, Coordinators, and Group Photo -->
                <div class="col-span-1 lg:col-span-4 flex flex-col gap-5">
                  <!-- Fruit Concept Badge -->
                  <div 
                    class="inline-flex items-center self-start gap-2 px-3 py-1.5 border-2 border-[#04000D] font-mono text-[10px] font-black uppercase tracking-wider shadow-[2px_2px_0px_0px_#04000D] select-none"
                    :style="{ backgroundColor: panitiaData[activeDivisionTab]?.color, color: panitiaData[activeDivisionTab]?.textColor }"
                  >
                    <span>KONSEP BUAH: {{ panitiaData[activeDivisionTab]?.fruit.toUpperCase() }} {{ panitiaData[activeDivisionTab]?.emoji }}</span>
                  </div>

                  <!-- Coordinator Section -->
                  <div>
                    <span class="font-mono text-[9px] tracking-widest text-[#04000D]/50 uppercase font-bold block mb-3">✦ KOORDINATOR ✦</span>
                    
                    <div class="flex flex-col gap-3">
                      <div 
                        v-for="coor in panitiaData[activeDivisionTab]?.coordinators" 
                        :key="coor.name"
                        class="flex items-center gap-3 bg-[#F5F5F5] border-2 border-[#04000D] p-2.5 shadow-[2px_2px_0px_0px_#04000D] hover:translate-y-[-1px] hover:shadow-[3px_3px_0px_0px_#04000D] transition-all duration-150"
                      >
                        <!-- Photo Block -->
                        <div 
                          class="w-12 h-12 border-2 border-[#04000D] flex-shrink-0 overflow-hidden flex items-center justify-center relative select-none"
                          :style="{ backgroundColor: panitiaData[activeDivisionTab]?.color }"
                        >
                          <img 
                            v-if="coor.imgSrc" 
                            :src="coor.imgSrc" 
                            :alt="coor.name" 
                            class="w-full h-full object-cover object-top contrast-110"
                          />
                          <span v-else class="font-mono font-black text-xs text-[#04000D]">{{ coor.name.substring(0, 2).toUpperCase() }}</span>
                        </div>
                        
                        <!-- Details -->
                        <div class="flex flex-col justify-center min-w-0 select-text">
                          <h5 class="font-black text-xs md:text-sm uppercase tracking-tight text-[#04000D] leading-tight truncate">{{ coor.name }}</h5>
                          <span class="font-mono text-[9px] text-[#04000D]/70 font-bold uppercase mt-0.5 truncate">{{ coor.role }} {{ coor.classYear }}</span>
                        </div>
                      </div>

                      <div v-if="!panitiaData[activeDivisionTab]?.coordinators.length" class="font-mono text-[10px] text-[#04000D]/60 italic p-3 border border-dashed border-[#04000D]/20 bg-[#F5F5F5]/40">
                        Tidak ada data koordinator khusus.
                      </div>
                    </div>
                  </div>

                  <!-- Division Group Photo -->
                  <div v-if="panitiaData[activeDivisionTab]?.groupPhoto" class="select-none">
                    <span class="font-mono text-[9px] tracking-widest text-[#04000D]/50 uppercase font-bold block mb-2.5">✦ DOKUMENTASI UNIT ✦</span>
                    <div class="border-2 border-[#04000D] bg-white p-2 shadow-[3px_3px_0px_0px_#04000D] rotate-[0.5deg] hover:rotate-0 transition-transform duration-200">
                      <div class="relative aspect-[3/2] overflow-hidden border border-[#04000D]/20 bg-[#04000D]/5">
                        <img 
                          :src="panitiaData[activeDivisionTab]?.groupPhoto" 
                          alt="Foto Bersama Divisi" 
                          class="w-full h-full object-cover grayscale contrast-110 brightness-95"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Right Column: Members Grid -->
                <div class="col-span-1 lg:col-span-8 flex flex-col">
                  <span class="font-mono text-[9px] tracking-widest text-[#04000D]/50 uppercase font-bold block mb-3">✦ ANGGOTA UNIT ✦</span>
                  
                  <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 xl:grid-cols-6 gap-3">
                    <div 
                      v-for="member in panitiaData[activeDivisionTab]?.members" 
                      :key="member.name"
                      class="group/member flex flex-col items-center"
                    >
                      <!-- Avatar frame -->
                      <div 
                        class="w-full aspect-square border-2 border-[#04000D] overflow-hidden flex items-center justify-center shadow-[2px_2px_0px_0px_#04000D] group-hover/member:shadow-[3px_3px_0px_0px_#04000D] group-hover/member:translate-y-[-1px] transition-all duration-150 relative cursor-pointer"
                        :style="{ backgroundColor: panitiaData[activeDivisionTab]?.color }"
                      >
                        <img 
                          v-if="member.imgSrc" 
                          :src="member.imgSrc" 
                          :alt="member.name" 
                          class="w-full h-full object-cover object-top contrast-110 transition-transform duration-200 group-hover/member:scale-105"
                        />
                        <span v-else class="font-mono font-black text-sm text-[#04000D]">{{ member.name.substring(0, 2).toUpperCase() }}</span>
                      </div>

                      <!-- Label -->
                      <div class="w-full mt-1.5 text-center select-text min-w-0">
                        <p class="font-black text-[10px] leading-tight uppercase tracking-tight text-[#04000D] truncate" :title="member.name">
                          {{ member.name.split(' ')[0] }}<span v-if="member.name.split(' ')[1]" class="hidden sm:inline"> {{ member.name.split(' ')[1] }}</span>
                        </p>
                        <span class="font-mono text-[8px] text-[#04000D]/60 mt-0.5 block font-bold leading-none">{{ member.classYear }}</span>
                      </div>
                    </div>
                  </div>

                  <div v-if="!panitiaData[activeDivisionTab]?.members.length" class="font-mono text-[10px] text-[#04000D]/60 italic p-6 border border-dashed border-[#04000D]/20 bg-[#F5F5F5]/40 text-center mt-3">
                    Tidak ada data anggota unit.
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION J: "OUR NETWORK" / SPONSOR HIERARCHY (Placed right above the Footer) -->
    <section id="partners" class="bg-white riso-canvas py-12 sm:py-16 px-4 sm:px-6 md:px-lg border-t border-dashed border-[#04000D]/20 relative overflow-hidden" data-reveal>
      <!-- Background Decorative Stamp Shards -->
      <img 
        :src="getAsset(visualAssetModules, 'visual_assets', 'cat3 1.webp')" 
        alt="Decorative Riso Plate Shard" 
        class="absolute bottom-16 -right-16 w-36 md:w-56 opacity-20 mix-blend-multiply contrast-125 pointer-events-none z-0 hidden md:block" 
      />

      <div class="max-w-container-max mx-auto relative z-10">
        
        <!-- Section Header -->
        <div class="mb-10">
          <p class="font-mono text-[#04000D] text-xs md:text-sm uppercase tracking-[0.25em] mb-4 font-bold">PARTNERSHIP HIERARCHY</p>
          <h2 class="font-bold text-2xl sm:text-3xl md:text-5xl tracking-tighter text-[#04000D] riso-text-shadow-magenta riso-bleed">Ekosistem Kolaborasi.</h2>
        </div>

        <!-- Stamped Organizer Logos -->
        <div class="mb-10">
          <p class="font-mono text-xs text-[#04000D]/70 uppercase tracking-widest mb-6 text-center md:text-left font-bold">✦ ORGANIZED BY ✦</p>
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
                <div class="bg-[#04000D] text-[#FDE047] border-2 border-[#04000D] px-3.5 py-1.5 text-[10px] md:text-xs font-mono font-bold uppercase tracking-wider rounded-none whitespace-nowrap shadow-[3px_3px_0px_0px_#FF3D8B]">
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
                <div class="bg-[#04000D] text-[#FDE047] border-2 border-[#04000D] px-3.5 py-1.5 text-[10px] md:text-xs font-mono font-bold uppercase tracking-wider rounded-none whitespace-nowrap shadow-[3px_3px_0px_0px_#FF3D8B]">
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
                <div class="bg-[#04000D] text-[#FDE047] border-2 border-[#04000D] px-3.5 py-1.5 text-[10px] md:text-xs font-mono font-bold uppercase tracking-wider rounded-none whitespace-nowrap shadow-[3px_3px_0px_0px_#FF3D8B]">
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
                <div class="bg-[#04000D] text-[#FDE047] border-2 border-[#04000D] px-3.5 py-1.5 text-[10px] md:text-xs font-mono font-bold uppercase tracking-wider rounded-none whitespace-nowrap shadow-[3px_3px_0px_0px_#FF3D8B]">
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
                  <div class="bg-[#04000D] text-[#FDE047] border-2 border-[#04000D] px-3.5 py-1.5 text-[10px] md:text-xs font-mono font-bold uppercase tracking-wider rounded-none text-center max-w-[180px] md:max-w-[240px] break-words shadow-[3px_3px_0px_0px_#FF3D8B]">
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
    <footer class="w-full bg-[#04000D] text-[#F5F5F5] py-10 md:py-14 px-6 md:px-lg border-t border-dashed border-[#F5F5F5]/25 relative overflow-hidden select-none">
      <div class="max-w-container-max mx-auto relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 items-start">
          
            <div class="relative flex flex-col gap-6 group">
              <button
                type="button"
                class="absolute inset-0 z-20 rounded-none bg-transparent cursor-pointer"
                aria-label="Kembali ke hero section"
                @click="scrollToHero"
              ></button>
            <div class="flex flex-col gap-2">
              <span class="font-headline-lg text-3xl sm:text-4xl font-bold text-[#FDE047] riso-text-shadow-double-dark riso-bleed">I-FEST 2026</span>
            </div>
            
            <!-- Bottom-Left Institutional Logo flex block -->
            <div class="relative z-10 flex flex-col gap-3">
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
            <p class="font-headline-lg text-lg md:text-xl font-medium">Sponsorship &amp; Kemitraan: <br class="md:hidden" /><a href="https://wa.me/6282195432152?text=Halo%20Fauzi%2C%20saya%20%5BNama%20Anda%5D%20dari%20%5BNama%20Perusahaan%2FInstansi%5D.%20Kami%20tertarik%20untuk%20mengetahui%20lebih%20lanjut%20mengenai%20peluang%20kerja%20sama%20dan%20paket%20sponsorship%20di%20I-FEST%202026.%20Boleh%20kami%20meminta%20berkas%20Proposal%20Umum%20terbaru%3F%20Terima%20kasih%21" target="_blank" rel="noopener noreferrer" class="text-[#FDE047] hover:underline transition-all duration-200">Fauzi (+62 821-9543-2152)</a></p>
            
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
        <a @click="toggleMenu" class="font-mono text-2xl font-bold border-b-2 border-dashed pb-1 transition-colors duration-200" :class="activeSection === 'roadshow-section' ? 'text-[#FF3D8B] border-[#FF3D8B]' : 'text-[#04000D] border-[#FF3D8B]/30 hover:text-accent-magenta'" href="#roadshow-section">Roadshow</a>
        <a @click="toggleMenu" class="font-mono text-2xl font-bold border-b-2 border-dashed pb-1 transition-colors duration-200" :class="activeSection === 'competitions-section' ? 'text-[#FF3D8B] border-[#FF3D8B]' : 'text-[#04000D] border-[#FDE047]/30 hover:text-accent-magenta'" href="#competitions-section">Kompetisi</a>
        <a @click="toggleMenu" class="font-mono text-2xl font-bold border-b-2 border-dashed pb-1 transition-colors duration-200" :class="activeSection === 'timeline' ? 'text-[#FF3D8B] border-[#FF3D8B]' : 'text-[#04000D] border-[#8839FF]/30 hover:text-accent-magenta'" href="#timeline">Timeline</a>
        <a @click="toggleMenu" class="font-mono text-2xl font-bold border-b-2 border-dashed pb-1 transition-colors duration-200" :class="activeSection === 'galeri-jejak-langkah' ? 'text-[#FF3D8B] border-[#FF3D8B]' : 'text-[#04000D] border-[#D86BFF]/30 hover:text-accent-magenta'" href="#galeri-jejak-langkah">Arsip 2025</a>
        <a @click="toggleMenu" class="font-mono text-2xl font-bold border-b-2 border-dashed pb-1 transition-colors duration-200" :class="activeSection === 'bph-matrix' ? 'text-[#FF3D8B] border-[#FF3D8B]' : 'text-[#04000D] border-[#D86BFF]/30 hover:text-accent-magenta'" href="#bph-matrix">Orkestrasi</a>
        <a @click="toggleMenu" class="font-mono text-2xl font-bold border-b-2 border-dashed pb-1 transition-colors duration-200" :class="activeSection === 'partners' ? 'text-[#FF3D8B] border-[#FF3D8B]' : 'text-[#04000D] border-[#04000D]/30 hover:text-accent-magenta'" href="#partners">Network</a>
      </nav>
    </div>
  </div>

  <!-- SECTION A: Top Navigation Chrome -->
  <header class="fixed inset-x-0 top-0 z-[90] w-full border-b border-dashed border-[#04000D]/30 bg-off-white/95 backdrop-blur-sm">
    <div class="max-w-container-max mx-auto flex items-center px-3 sm:px-4 md:px-lg py-3 md:py-sm gap-4 lg:gap-8">
      
      <!-- Logo Flex Container with UNTAD -> HMTI -> I-FEST -->
      <div class="flex items-center gap-2 md:gap-4 select-none">
        <div class="flex items-center gap-1.5 md:gap-3">
          <img alt="UNTAD Logo" class="h-7 md:h-10 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'logo_untad.webp')" />
          <img alt="HMTI Logo" class="h-7 md:h-10 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'HMTI LOGO.webp')" />
          <img alt="I-FEST Logo" class="h-7 md:h-10 w-auto object-contain" :src="getAsset(mainLogoAssetModules, 'logo_utama', 'Logo-IFEST-2026.webp')" />
        </div>
        <span class="hidden sm:inline-block font-mono text-base md:text-lg font-bold tracking-widest text-[#04000D] border-l border-[#04000D]/20 pl-3 md:pl-4 riso-bleed">I-FEST 2026</span>
      </div>

      <nav class="hidden md:flex items-center gap-3 lg:gap-5 xl:gap-6 select-none ml-auto mr-2 lg:mr-4 justify-end">
        <a class="font-mono text-xs lg:text-[13px] xl:text-sm font-bold uppercase tracking-wider pb-1 border-b-2 transition-all duration-200" :class="activeSection === 'roadshow-section' ? 'text-[#FF3D8B] border-[#FF3D8B]' : 'text-[#04000D]/70 border-transparent hover:text-accent-magenta'" href="#roadshow-section">Roadshow</a>
        <a class="font-mono text-xs lg:text-[13px] xl:text-sm font-bold uppercase tracking-wider pb-1 border-b-2 transition-all duration-200" :class="activeSection === 'competitions-section' ? 'text-[#FF3D8B] border-[#FF3D8B]' : 'text-[#04000D]/70 border-transparent hover:text-accent-magenta'" href="#competitions-section">Kompetisi</a>
        <a class="font-mono text-xs lg:text-[13px] xl:text-sm font-bold uppercase tracking-wider pb-1 border-b-2 transition-all duration-200" :class="activeSection === 'timeline' ? 'text-[#FF3D8B] border-[#FF3D8B]' : 'text-[#04000D]/70 border-transparent hover:text-accent-magenta'" href="#timeline">Timeline</a>
        <a class="font-mono text-xs lg:text-[13px] xl:text-sm font-bold uppercase tracking-wider pb-1 border-b-2 transition-all duration-200" :class="activeSection === 'galeri-jejak-langkah' ? 'text-[#FF3D8B] border-[#FF3D8B]' : 'text-[#04000D]/70 border-transparent hover:text-accent-magenta'" href="#galeri-jejak-langkah">Arsip 2025</a>
        <a class="font-mono text-xs lg:text-[13px] xl:text-sm font-bold uppercase tracking-wider pb-1 border-b-2 transition-all duration-200" :class="activeSection === 'bph-matrix' ? 'text-[#FF3D8B] border-[#FF3D8B]' : 'text-[#04000D]/70 border-transparent hover:text-accent-magenta'" href="#bph-matrix">Orkestrasi</a>
        <a class="font-mono text-xs lg:text-[13px] xl:text-sm font-bold uppercase tracking-wider pb-1 border-b-2 transition-all duration-200" :class="activeSection === 'partners' ? 'text-[#FF3D8B] border-[#FF3D8B]' : 'text-[#04000D]/70 border-transparent hover:text-accent-magenta'" href="#partners">Network</a>
      </nav>

      <div class="flex items-center gap-2 select-none md:hidden ml-auto">
        <button @click="toggleMenu" class="p-1.5 flex items-center justify-center border border-[#04000D] rounded bg-white hover:bg-off-white transition-colors" aria-label="Toggle menu">
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
      class="riso-btn-plate w-14 h-14 bg-[#04000D] text-white rounded-full flex items-center justify-center relative active:scale-95 group" 
      style="--plate-color: #FDE047;"
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

<style scoped>
.bg-solid-white {
  background-color: #ffffff !important;
  position: relative !important;
  z-index: 10 !important;
}
.bg-solid-white::before {
  display: none !important;
  content: none !important;
  background-image: none !important;
}
</style>

