import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { MotionPlugin } from '@vueuse/motion'
import App from './App.vue'
import './style.css'
import AnimatedCounter from './components/AnimatedCounter.vue'
import router from './router'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(MotionPlugin)
app.use(router)
app.component('AnimatedCounter', AnimatedCounter)
app.mount('#app')

// Set I-FEST favicon dynamically so Vite resolves the correct asset path
import ifestFavicon from './assets/logo_utama/Logo-IFEST-2026.webp'

// Apply favicon after mount (or immediately if head link exists)
try {
	const head = document.getElementsByTagName('head')[0]
	let icon = head.querySelector("link[rel~='icon']")
	if (!icon) {
		icon = document.createElement('link')
		icon.setAttribute('rel', 'icon')
		head.appendChild(icon)
	}
	icon.setAttribute('href', ifestFavicon)
} catch (e) {
	// silent fail if document/head not available (e.g., SSR)
}
