<template>
  <span>{{ display }}</span>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'

const props = defineProps({
  end: { type: Number, required: true },
  duration: { type: Number, default: 1500 },
  suffix: { type: String, default: '' },
  format: { type: Function, default: null }
})

const display = ref((0).toLocaleString() + props.suffix)

function easeOutQuad(t){ return t*(2-t) }

function animateTo(target){
  let start = null
  const startVal = 0
  const step = (ts) => {
    if (!start) start = ts
    const elapsed = ts - start
    const progress = Math.min(elapsed / props.duration, 1)
    const eased = easeOutQuad(progress)
    const current = Math.round(startVal + (target - startVal) * eased)
    display.value = (props.format ? props.format(current) : current.toLocaleString()) + props.suffix
    if (progress < 1) requestAnimationFrame(step)
  }
  requestAnimationFrame(step)
}

onMounted(()=> {
  animateTo(props.end)
})

watch(()=>props.end, (v)=> animateTo(v))
</script>

<style scoped>
/* inherit sizing and weight from parent element, no styles needed */
</style>
