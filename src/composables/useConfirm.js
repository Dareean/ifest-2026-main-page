import { reactive } from 'vue'

const state = reactive({
  isOpen: false,
  title: '',
  message: '',
  resolve: null,
  type: 'confirm', // 'confirm' | 'alert' | 'prompt'
  confirmText: 'Ya',
  cancelText: 'Batal',
  inputValue: '',
  placeholder: '',
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

  function prompt(message, title = 'Input', options = {}) {
    state.title = title
    state.message = message
    state.isOpen = true
    state.type = 'prompt'
    state.confirmText = options.confirmText || 'Kirim'
    state.cancelText = options.cancelText || 'Batal'
    state.inputValue = options.defaultValue || ''
    state.placeholder = options.placeholder || 'Tulis di sini...'

    return new Promise((resolve) => {
      state.resolve = resolve
    })
  }

  function handleConfirm() {
    state.isOpen = false
    if (state.resolve) {
      if (state.type === 'prompt') {
        state.resolve(state.inputValue)
      } else {
        state.resolve(true)
      }
    }
  }

  function handleCancel() {
    state.isOpen = false
    if (state.resolve) {
      if (state.type === 'prompt') {
        state.resolve(null)
      } else {
        state.resolve(false)
      }
    }
  }

  return {
    state,
    confirm,
    alert,
    prompt,
    handleConfirm,
    handleCancel,
  }
}
