import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      // Proxy all /api/* requests to PHP backend at port 8000
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      // Proxy upload URLs for plant photos
      '/uploads': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      }
    }
  }
})
