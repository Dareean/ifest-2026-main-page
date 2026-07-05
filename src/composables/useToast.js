import { reactive } from 'vue'

const state = reactive({
  toasts: [],
})

let nextId = 0

export function useToast() {
  function showToast(message, type = 'success') {
    const id = ++nextId
    state.toasts.push({ id, message, type, leaving: false })
    setTimeout(() => removeToast(id), 3500)
  }

  function removeToast(id) {
    const toast = state.toasts.find(t => t.id === id)
    if (toast) toast.leaving = true
    setTimeout(() => {
      state.toasts = state.toasts.filter(t => t.id !== id)
    }, 300)
  }

  return {
    toasts: state.toasts,
    showToast,
    removeToast,
  }
}
