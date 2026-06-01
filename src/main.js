import { createApp } from 'vue'
import { MotionPlugin } from '@vueuse/motion'
import App from './App.vue'
import './style.css'
import AnimatedCounter from './components/AnimatedCounter.vue'
import router from './router'

const app = createApp(App)
app.use(MotionPlugin)
app.use(router)
app.component('AnimatedCounter', AnimatedCounter)
app.mount('#app')