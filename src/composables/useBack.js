import { useRouter } from 'vue-router'

export function useBack() {
  const router = useRouter()

  function goBack(fallback = '/') {
    if (window.history.length > 2) {
      router.back()
    } else {
      router.push(fallback)
    }
  }

  return { goBack }
}
