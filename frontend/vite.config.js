import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      '/storage': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
  build: {
    rolldownOptions: {
      output: {
        codeSplitting: {
          groups: [
            {
              name: 'vue',
              test: /[\\/]node_modules[\\/](?:vue|@vue)[\\/]/,
              priority: 40,
            },
            {
              name: 'vue-router',
              test: /[\\/]node_modules[\\/]vue-router[\\/]/,
              priority: 30,
            },
            {
              name: 'pinia',
              test: /[\\/]node_modules[\\/]pinia[\\/]/,
              priority: 20,
            },
            {
              name: 'iconify',
              test: /[\\/]node_modules[\\/]@iconify[\\/]/,
              priority: 10,
            },
          ],
        },
      },
    },
  },
})