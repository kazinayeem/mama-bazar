import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  // The dev proxy target reads the same env var the app uses at runtime so that
  // development, staging and production backends can all be selected from env.
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
      rollupOptions: {
        output: {
          /**
           * Manual chunk splitting — keeps large vendor libraries in separate
           * cacheable files so that a code deployment only invalidates app
           * chunks (not vendor chunks the browser has already cached).
           *
           * framer-motion → separate chunk so only pages/components that
           *   import it incur the parse cost (HeroCarousel, CartDrawer, etc.)
           * state → RTK + Redux in one chunk (stable, rarely changes)
           * router → React Router in its own chunk
           * icons → lucide-react (large icon set, stable)
           */
          manualChunks(id: string) {
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
          },
        },
      },
    },
  }
})

