<script setup>
import { ref, onMounted, computed } from 'vue'

const props = defineProps({
  duration: {
    type: Number,
    default: 2000 // duration of loading in ms
  }
})

const emit = defineEmits(['loaded', 'split'])

const progress = ref(0)
const isLoaded = ref(false)
const isSplitting = ref(false)

// Determine when each layer stamps down
const showBase = computed(() => progress.value > 5)
const showLime = computed(() => progress.value >= 35)
const showMagenta = computed(() => progress.value >= 70)

onMounted(() => {
  const startTime = performance.now()
  
  const updateProgress = (now) => {
    const elapsed = now - startTime
    const percent = Math.min((elapsed / props.duration) * 100, 100)
    progress.value = Math.floor(percent)
    
    if (percent < 100) {
      requestAnimationFrame(updateProgress)
    } else {
      // Hold at 100% briefly, then split, then emit loaded
      setTimeout(() => {
        isSplitting.value = true
        emit('split')
        setTimeout(() => {
          isLoaded.value = true
          emit('loaded')
        }, 800) // matches split transition time
      }, 500)
    }
  }
  
  requestAnimationFrame(updateProgress)
})
</script>

<template>
  <div v-if="!isLoaded" class="fixed inset-0 z-[999] overflow-hidden select-none pointer-events-none">
    
    <!-- Top Sliding Panel -->
    <div 
      class="absolute inset-x-0 top-0 h-1/2 bg-off-white flex flex-col justify-end items-center border-b border-dashed border-[#04000D]/30 transition-transform duration-700 cubic-bezier(0.85, 0, 0.15, 1) pointer-events-auto"
      :class="{ '-translate-y-full': isSplitting }"
    >
      <!-- Dot grain overlay on top panel -->
      <div class="absolute inset-0 bg-[radial-gradient(#04000D_1px,transparent_1px)] [background-size:16px_16px] opacity-[0.04] pointer-events-none z-0"></div>
      <div class="absolute inset-0 bg-noise-grain opacity-[0.04] pointer-events-none z-0"></div>
      
      <!-- Stamp Top Container -->
      <div class="translate-y-1/2 z-10 flex flex-col items-center">
        <!-- Mechanical Riso Stamp Wrapper -->
        <div class="relative w-72 h-72 md:w-96 md:h-96 flex items-center justify-center">
          
          <!-- Layer 1: Base Midnight Outline (Mechanical Vibration) -->
          <div 
            v-if="showBase"
            v-motion
            :initial="{ opacity: 0, scale: 3, rotate: -8 }"
            :enter="{ 
              opacity: 1, 
              scale: 1, 
              rotate: 0,
              transition: { type: 'spring', stiffness: 400, damping: 14 } 
            }"
            class="absolute inset-0 flex items-center justify-center z-10 animate-mechanical-rattle"
          >
            <div class="w-64 h-64 md:w-80 md:h-80 border-4 border-[#04000D] rounded-3xl flex flex-col items-center justify-center bg-transparent p-6 shadow-none">
              <!-- Grid crosshairs -->
              <div class="absolute top-2 left-1/2 -translate-x-1/2 font-mono text-[9px] text-[#04000D]/40">REGISTRATION MARK: A-01</div>
              <span class="text-6xl md:text-7xl text-[#04000D] mb-2">✦</span>
              <h2 class="font-mono text-3xl md:text-4xl font-extrabold tracking-widest text-[#04000D]">I-FEST</h2>
              <p class="font-mono text-[10px] md:text-xs tracking-[0.25em] text-[#04000D] uppercase mt-2 font-bold">DIGITAL SYMPHONY</p>
              <div class="absolute bottom-2 left-1/2 -translate-x-1/2 font-mono text-[8px] text-[#04000D]/40">HMTI UNTAD © 2026</div>
            </div>
          </div>

          <!-- Layer 2: Misaligned Lime Green Plate (mix-blend-multiply) -->
          <div 
            v-if="showLime"
            v-motion
            :initial="{ opacity: 0, scale: 2.2, rotate: 6 }"
            :enter="{ 
              opacity: 0.85, 
              scale: 1, 
              rotate: -1.5,
              transition: { type: 'spring', stiffness: 350, damping: 12 } 
            }"
            class="absolute inset-0 flex items-center justify-center z-20 mix-blend-multiply -translate-x-[3px] -translate-y-[2px]"
          >
            <div class="w-64 h-64 md:w-80 md:h-80 border-4 border-[#FDE047] rounded-3xl flex flex-col items-center justify-center bg-[#FDE047]/10 p-6">
              <span class="text-6xl md:text-7xl text-[#FDE047] mb-2">✦</span>
              <h2 class="font-mono text-3xl md:text-4xl font-extrabold tracking-widest text-[#FDE047]">I-FEST</h2>
              <p class="font-mono text-[10px] md:text-xs tracking-[0.25em] text-[#FDE047] uppercase mt-2 font-bold">DIGITAL SYMPHONY</p>
            </div>
          </div>

          <!-- Layer 3: Misaligned Vivid Magenta Plate (mix-blend-multiply) -->
          <div 
            v-if="showMagenta"
            v-motion
            :initial="{ opacity: 0, scale: 2.5, rotate: -12 }"
            :enter="{ 
              opacity: 0.85, 
              scale: 1, 
              rotate: 2,
              transition: { type: 'spring', stiffness: 300, damping: 10 } 
            }"
            class="absolute inset-0 flex items-center justify-center z-30 mix-blend-multiply translate-x-[4px] translate-y-[3px]"
          >
            <div class="w-64 h-64 md:w-80 md:h-80 border-4 border-[#FF3D8B] rounded-3xl flex flex-col items-center justify-center bg-[#FF3D8B]/10 p-6">
              <span class="text-6xl md:text-7xl text-[#FF3D8B] mb-2">✦</span>
              <h2 class="font-mono text-3xl md:text-4xl font-extrabold tracking-widest text-[#FF3D8B]">I-FEST</h2>
              <p class="font-mono text-[10px] md:text-xs tracking-[0.25em] text-[#FF3D8B] uppercase mt-2 font-bold">DIGITAL SYMPHONY</p>
            </div>
          </div>

        </div>
      </div>
    </div>
    
    <!-- Bottom Sliding Panel -->
    <div 
      class="absolute inset-x-0 bottom-0 h-1/2 bg-off-white flex flex-col justify-start items-center border-t border-dashed border-[#04000D]/30 transition-transform duration-700 cubic-bezier(0.85, 0, 0.15, 1) pointer-events-auto"
      :class="{ 'translate-y-full': isSplitting }"
    >
      <!-- Dot grain overlay on bottom panel -->
      <div class="absolute inset-0 bg-[radial-gradient(#04000D_1px,transparent_1px)] [background-size:16px_16px] opacity-[0.04] pointer-events-none z-0"></div>
      <div class="absolute inset-0 bg-noise-grain opacity-[0.04] pointer-events-none z-0"></div>
      
      <!-- Offset bottom spacer so the stamp doesn't clip badly and looks aligned -->
      <div class="-translate-y-1/2 h-72 md:h-96"></div>

      <!-- Loading Details Block -->
      <div class="mt-8 flex flex-col items-center gap-4 z-20">
        <!-- Progress Counter -->
        <div class="flex items-baseline gap-1 font-mono text-5xl md:text-6xl font-bold tracking-tight text-[#04000D] riso-text-shadow-magenta riso-bleed">
          <span>{{ progress }}</span>
          <span class="text-2xl md:text-3xl text-[#FF3D8B]">%</span>
        </div>
        
        <!-- Status indicator (Tactile text style) -->
        <div class="flex flex-col items-center gap-1">
          <span class="font-mono text-[10px] md:text-xs tracking-[0.3em] text-[#04000D] uppercase font-bold animate-pulse">
            {{ progress === 100 ? 'SATURATION COMPLETE' : 'INJECTING RISOGRAPH INK...' }}
          </span>
          <span class="font-mono text-[8px] md:text-[9px] text-[#04000D]/50 uppercase tracking-widest">
            PLATE REGISTERING • MULTIPLY OVERLAYS ACTIVE
          </span>
        </div>
        
        <!-- Coarse Progress Bar Indicator -->
        <div class="w-64 md:w-80 h-3 border-2 border-[#04000D] p-[2px] bg-white rounded-full overflow-hidden">
          <div 
            class="h-full bg-[#FDE047] border border-[#04000D] rounded-full transition-all duration-100 ease-out" 
            :style="{ width: `${progress}%` }"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Mechanical shake keyframes to simulate physical printing presses */
@keyframes mechanical-rattle {
  0% { transform: translate(0, 0) rotate(0deg); }
  2% { transform: translate(1px, -1px) rotate(-0.5deg); }
  4% { transform: translate(-1.5px, 1px) rotate(0.5deg); }
  6% { transform: translate(1px, 1.5px) rotate(0deg); }
  8% { transform: translate(-1px, -1px) rotate(0.5deg); }
  10% { transform: translate(1.5px, -1px) rotate(-0.5deg); }
  12% { transform: translate(0, 0) rotate(0deg); }
}

.animate-mechanical-rattle {
  animation: mechanical-rattle 1.8s infinite ease-in-out;
}

/* Background raw paper texture */
.bg-noise-grain {
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='1' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
}
</style>
