<script setup>
import { useToast } from '../composables/useToast'
import { X } from 'lucide-vue-next'

const { toasts, removeToast } = useToast()
</script>

<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-[10000] flex flex-col gap-2 max-w-sm w-full pointer-events-none">
      <TransitionGroup name="toast">
        <div
          v-for="t in toasts"
          :key="t.id"
          :class="[
            'pointer-events-auto bg-white border-2 rounded-2xl px-4 py-3 flex items-start gap-3 shadow-[4px_4px_0px_0px_#04000D] transition-all duration-300',
            t.leaving ? 'opacity-0 translate-x-4 scale-95' : 'opacity-100 translate-x-0 scale-100',
            t.type === 'success' ? 'border-lime-bright' : 'border-accent-magenta',
          ]"
        >
          <span
            class="w-1.5 h-1.5 rounded-full mt-1.5 flex-shrink-0"
            :class="t.type === 'success' ? 'bg-lime-bright' : 'bg-accent-magenta'"
          />
          <p class="flex-1 font-mono text-[11px] font-bold text-on-surface leading-relaxed">
            {{ t.message }}
          </p>
          <button @click="removeToast(t.id)" class="text-on-surface-variant/40 hover:text-on-surface transition-colors flex-shrink-0 mt-0.5">
            <X class="w-3.5 h-3.5" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-enter-active {
  transition: all 0.3s ease-out;
}
.toast-leave-active {
  transition: all 0.3s ease-in;
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(40px) scale(0.95);
}
.toast-move {
  transition: transform 0.3s ease;
}
</style>
