<script setup>
import { ref, watch, nextTick } from 'vue'
import { useConfirm } from '../composables/useConfirm'
import { HelpCircle, AlertTriangle, MessageSquare } from 'lucide-vue-next'

const { state, handleConfirm, handleCancel } = useConfirm()
const promptInput = ref(null)

watch(() => state.isOpen, (open) => {
  if (open && state.type === 'prompt') {
    nextTick(() => {
      promptInput.value?.focus()
    })
  }
})
</script>

<template>
  <Transition name="confirm">
    <div 
      v-if="state.isOpen" 
      class="fixed inset-0 z-[99999] flex items-center justify-center p-4 backdrop-blur-sm bg-[#04000D]/60"
      @click.self="handleCancel"
    >
      <div 
        class="w-full max-w-sm bg-white border-3 border-[#04000D] rounded-2xl shadow-[8px_8px_0px_0px_#04000D] overflow-hidden transform transition-all duration-300 select-none"
      >
        <!-- Top accent banner -->
        <div 
          class="h-3 w-full border-b-3 border-[#04000D]"
          :class="state.type === 'confirm' ? 'bg-[#DCEEB1]' : state.type === 'prompt' ? 'bg-[#FDE047]' : 'bg-[#FF3D8B]'"
        ></div>

        <!-- Content body -->
        <div class="p-6">
          <div class="flex items-start gap-4">
            <!-- Icon -->
            <div 
              class="w-12 h-12 rounded-xl border-2 border-[#04000D] flex items-center justify-center flex-shrink-0 shadow-[3px_3px_0px_0px_#04000D]"
              :class="state.type === 'confirm' ? 'bg-[#DCEEB1]/10' : state.type === 'prompt' ? 'bg-[#FDE047]/10' : 'bg-[#FF3D8B]/10'"
            >
              <HelpCircle 
                v-if="state.type === 'confirm'" 
                class="w-6 h-6 text-[#04000D]" 
              />
              <MessageSquare 
                v-else-if="state.type === 'prompt'" 
                class="w-6 h-6 text-[#04000D]" 
              />
              <AlertTriangle 
                v-else 
                class="w-6 h-6 text-[#FF3D8B]" 
              />
            </div>

            <!-- Text Content -->
            <div class="min-w-0 flex-1">
              <h3 class="font-extrabold text-base text-[#04000D] tracking-tight leading-snug">
                {{ state.title }}
              </h3>
              <p class="text-xs text-on-surface-variant/80 mt-1.5 leading-relaxed font-semibold">
                {{ state.message }}
              </p>

              <!-- Prompt Input Field -->
              <div v-if="state.type === 'prompt'" class="mt-3.5">
                <input
                  v-model="state.inputValue"
                  type="text"
                  :placeholder="state.placeholder"
                  class="w-full bg-slate-50 border-2 border-[#04000D] focus:border-accent-magenta rounded-xl px-3 py-2 text-xs font-semibold text-on-surface placeholder:text-on-surface-variant/35 focus:outline-none transition-all shadow-[2px_2px_0px_0px_#04000D]"
                  @keyup.enter="handleConfirm"
                  ref="promptInput"
                />
              </div>
            </div>
          </div>

          <!-- Buttons -->
          <div class="mt-6 flex items-center justify-end gap-3">
            <button
              v-if="state.type === 'confirm' || state.type === 'prompt'"
              @click="handleCancel"
              class="border-2 border-[#04000D] hover:bg-slate-50 text-[#04000D] px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-[3px_3px_0px_0px_#04000D] active:translate-x-[2px] active:translate-y-[2px] active:shadow-[1px_1px_0px_0px_#04000D] select-none"
            >
              {{ state.cancelText }}
            </button>
            <button
              @click="handleConfirm"
              class="border-2 border-[#04000D] text-[#04000D] px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-[3px_3px_0px_0px_#04000D] active:translate-x-[2px] active:translate-y-[2px] active:shadow-[1px_1px_0px_0px_#04000D] select-none"
              :class="state.type === 'confirm' ? 'bg-[#DCEEB1] hover:bg-[#DCEEB1]/90' : state.type === 'prompt' ? 'bg-[#FDE047] hover:bg-[#FDE047]/90' : 'bg-[#FF3D8B]/20 hover:bg-[#FF3D8B]/30'"
            >
              {{ state.confirmText }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.confirm-enter-active, .confirm-leave-active {
  transition: opacity 0.25s ease;
}
.confirm-enter-active > div {
  animation: confirm-pop 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.confirm-leave-active > div {
  animation: confirm-pop 0.2s cubic-bezier(0.36, 0.07, 0.19, 0.97) reverse;
}
.confirm-enter-from, .confirm-leave-to {
  opacity: 0;
}

@keyframes confirm-pop {
  0% {
    transform: scale(0.92);
  }
  100% {
    transform: scale(1);
  }
}
</style>
