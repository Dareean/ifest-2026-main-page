import { createApp } from 'vue'
import { MotionPlugin } from '@vueuse/motion'
import App from './App.vue'
import './style.css'
import AnimatedCounter from './components/AnimatedCounter.vue'

const app = createApp(App)
app.use(MotionPlugin)
app.component('AnimatedCounter', AnimatedCounter)
app.mount('#app')