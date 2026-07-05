<script setup>
import { ref, defineAsyncComponent, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import RisoLoader from './components/RisoLoader.vue'
import ToastContainer from './components/ToastContainer.vue'
import ConfirmContainer from './components/ConfirmContainer.vue'
import { useAuthStore } from './stores/auth'
import { Bot } from 'lucide-vue-next'

const AiChatWidget = defineAsyncComponent(() => import('./components/AiChatWidget.vue'))
const isChatActivated = ref(false)

const showContent = ref(false)
const isLoading = ref(true)

const route = useRoute()
const showChatbot = computed(() => {
  const landingPages = ['/', '/kompetisi', '/competitions', '/roadshow']
  return landingPages.includes(route.path)
})

const onSplit = () => {
  showContent.value = true
  document.body.style.overflow = ''
}

const onLoaded = () => {
  isLoading.value = false
}

onMounted(() => {
  const auth = useAuthStore()
  if (auth.isAuthenticated) {
    auth.fetchUser()
  }
})
</script>

<template>
  <!-- Global Initial Loading Screen -->
  <RisoLoader @split="onSplit" @loaded="onLoaded" />

  <!-- Main Routed Application Shell -->
  <div 
    class="riso-canvas bg-off-white min-h-screen text-[#04000D] font-body-md select-text transition-all duration-700 ease-out"
    :class="{ 'opacity-0 scale-[0.98] pointer-events-none h-screen overflow-hidden': !showContent }"
  >
    <router-view v-slot="{ Component }">
      <Suspense>
        <template #default>
          <component :is="Component" />
        </template>
        <template #fallback>
          <div class="w-full h-screen bg-[#f4f4f4] flex items-center justify-center font-mono text-xs text-[#04000D] uppercase tracking-widest font-bold">
            Loading Section...
          </div>
        </template>
      </Suspense>
    </router-view>

    <!-- Global Toast Notifications -->
    <ToastContainer />
    <ConfirmContainer />
  </div>

  <!-- Global AI Assistant Chat Widget -->
  <template v-if="showChatbot">
    <template v-if="isChatActivated">
      <AiChatWidget @close="isChatActivated = false" />
    </template>
    <div v-else class="fixed bottom-3 right-3 sm:bottom-6 sm:right-6 z-[9999] flex flex-col items-end">
      <button 
        @click="isChatActivated = true" 
        class="riso-btn-plate w-12 h-12 sm:w-14 sm:h-14 bg-[#04000D] text-white rounded-full flex items-center justify-center relative active:scale-95 group" 
        style="--plate-color: #FDE047;"
        aria-label="Open Assistant"
      >
        <!-- Pulse Indicator -->
        <span class="absolute -top-0.5 -right-0.5 flex h-3 w-3 sm:h-3.5 sm:w-3.5">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FF3D8B] opacity-75"></span>
          <span class="relative inline-flex rounded-full h-3 w-3 sm:h-3.5 sm:w-3.5 bg-[#FF3D8B]"></span>
        </span>
        
        <!-- Robot Icon -->
        <Bot class="w-5 h-5 sm:w-6 sm:h-6 transition-transform duration-300 group-hover:scale-110" />
      </button>
    </div>
  </template>
</template>

<style>
</style>
