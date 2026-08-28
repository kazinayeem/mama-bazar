import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd())
  const apiTarget = (env.VITE_API_URL || env.VITE_API_BASE_URL || 'http://localhost:5000').trim()

  return {
    plugins: [react()],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './src'),
      },
    },
    server: {
      proxy: apiTarget
        ? {
            '/api': {
              target: apiTarget,
              changeOrigin: true,
            },
            '/uploads': {
              target: apiTarget,
              changeOrigin: true,
            },
          }
        : undefined,
    },
    build: {
      cssCodeSplit: true,
      rollupOptions: {
        output: {
          /**
           * Manual chunk splitting — keeps large vendor libraries in separate
           * cacheable files so that a code deployment only invalidates app
           * chunks (not vendor chunks the browser has already cached).
           */
          manualChunks(id: string) {
            // Heavy admin-only libraries
            if (id.includes('node_modules/@tiptap')) return 'vendor-admin-tiptap'
            if (id.includes('node_modules/recharts')) return 'vendor-admin-charts'
            if (id.includes('node_modules/jspdf') || id.includes('node_modules/html2canvas')) return 'vendor-pdf'
            if (id.includes('node_modules/@tanstack/react-table')) return 'vendor-table'
            if (id.includes('node_modules/react-easy-crop') || id.includes('node_modules/react-dropzone')) return 'vendor-media'

            // Shared frontend libraries
            if (id.includes('node_modules/framer-motion')) return 'vendor-motion'
            if (
              id.includes('node_modules/@reduxjs/toolkit') ||
              id.includes('node_modules/react-redux') ||
              id.includes('node_modules/redux')
            )
              return 'vendor-state'
            if (
              id.includes('node_modules/react-router') ||
              id.includes('node_modules/@remix-run')
            )
              return 'vendor-router'
            if (id.includes('node_modules/lucide-react')) return 'vendor-icons'
            if (id.includes('node_modules/@radix-ui')) return 'vendor-radix'
          },
        },
      },
    },
  }
})
