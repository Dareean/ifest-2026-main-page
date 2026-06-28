<script setup>
import { ref, defineAsyncComponent } from 'vue'
import RisoLoader from './components/RisoLoader.vue'
import { Bot } from 'lucide-vue-next'

const AiChatWidget = defineAsyncComponent(() => import('./components/AiChatWidget.vue'))
const isChatActivated = ref(false)

const showContent = ref(false)
const isLoading = ref(true)

const onSplit = () => {
  showContent.value = true
  document.body.style.overflow = ''
}

const onLoaded = () => {
  isLoading.value = false
}
</script>

<template>
  <!-- Global Initial Loading Screen -->
  <RisoLoader @split="onSplit" @loaded="onLoaded" />

  <!-- Main Routed Application Shell -->
  <div 
    class="riso-canvas bg-off-white min-h-screen text-[#04000D] font-body-md select-text transition-all duration-700 ease-out"
    :class="{ 'opacity-0 scale-[0.98] pointer-events-none h-screen overflow-hidden': !showContent }"
  >
    <router-view v-slot="{ Component, route }">
      <transition name="fade" mode="out-in">
        <Suspense>
          <template #default>
            <div :key="route.fullPath">
              <component :is="Component" />
            </div>
          </template>
          <template #fallback>
            <div class="w-full h-screen bg-[#f4f4f4] flex items-center justify-center font-mono text-xs text-[#04000D] uppercase tracking-widest font-bold">
              Loading Section...
            </div>
          </template>
        </Suspense>
      </transition>
    </router-view>
  </div>

  <!-- Global AI Assistant Chat Widget -->
  <template v-if="isChatActivated">
    <AiChatWidget @close="isChatActivated = false" />
  </template>
  <div v-else class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 flex flex-col items-end">
    <button 
      @click="isChatActivated = true" 
      class="riso-btn-plate w-14 h-14 bg-[#04000D] text-white rounded-full flex items-center justify-center relative active:scale-95 group" 
      style="--plate-color: #FDE047;"
      aria-label="Open Assistant"
    >
      <!-- Pulse Indicator -->
      <span class="absolute -top-0.5 -right-0.5 flex h-3.5 w-3.5">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FF3D8B] opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-[#FF3D8B]"></span>
      </span>
      
      <!-- Robot Icon -->
      <Bot class="w-6 h-6 transition-transform duration-300 group-hover:scale-110" />
    </button>
  </div>
</template>

<style>
/* Smooth global fade transition for page routes */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(6px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
