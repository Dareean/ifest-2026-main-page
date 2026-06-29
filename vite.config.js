import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { visualizer } from 'rollup-plugin-visualizer'

export default defineConfig({
  assetsInclude: ['**/*.docx', '**/*.xlsx'],
  plugins: [
    vue(),
    visualizer({
      filename: 'stats.html',
      title: 'I-FEST 2026 Bundle Size Visualization',
      open: false,
      gzipSize: true,
      brotliSize: true
    })
  ],
})