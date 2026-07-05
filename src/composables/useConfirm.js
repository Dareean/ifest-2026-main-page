import { reactive } from 'vue'

const state = reactive({
  isOpen: false,
  title: '',
  message: '',
  resolve: null,
  type: 'confirm', // 'confirm' | 'alert'
  confirmText: 'Ya',
  cancelText: 'Batal',
})

export function useConfirm() {
  function confirm(message, title = 'Konfirmasi', options = {}) {
    state.title = title
    state.message = message
    state.isOpen = true
    state.type = 'confirm'
    state.confirmText = options.confirmText || 'Ya'
    state.cancelText = options.cancelText || 'Batal'

    return new Promise((resolve) => {
      state.resolve = resolve
    })
  }

  function alert(message, title = 'Informasi', options = {}) {
    state.title = title
    state.message = message
    state.isOpen = true
    state.type = 'alert'
    state.confirmText = options.confirmText || 'OK'

    return new Promise((resolve) => {
      state.resolve = resolve
    })
  }

  function handleConfirm() {
    state.isOpen = false
    if (state.resolve) state.resolve(true)
  }

  function handleCancel() {
    state.isOpen = false
    if (state.resolve) state.resolve(false)
  }

  return {
    state,
    confirm,
    alert,
    handleConfirm,
    handleCancel,
  }
}
