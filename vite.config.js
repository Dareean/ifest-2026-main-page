import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { visualizer } from 'rollup-plugin-visualizer'

export default defineConfig({
  assetsInclude: ['**/*.docx', '**/*.xlsx'],
  plugins: [
    vue(),
    ...(process.env.VITE_VISUALIZER ? [visualizer({
      filename: 'stats.html',
      title: 'I-FEST 2026 Bundle Size Visualization',
      open: false,
      gzipSize: true,
      brotliSize: true
    })] : []),
  ],
  server: {
    proxy: {
      // Proxy /sanctum and /api to the Laravel backend.
      // This makes XSRF-TOKEN cookies same-origin (localhost:5173),
      // so Axios withXSRFToken:true can read them via document.cookie.
      '/sanctum': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('gsap')) return 'vendor-anim'
            if (id.includes('vue') || id.includes('pinia') || id.includes('vue-router')) return 'vendor-vue'
            if (id.includes('supabase')) return 'vendor-supabase'
            if (id.includes('axios')) return 'vendor-http'
            return 'vendor'
          }
          if (id.includes('pages/dashboard/admin')) return 'admin'
          if (id.includes('pages/dashboard')) return 'dashboard'
        }
      }
    }
  }
})