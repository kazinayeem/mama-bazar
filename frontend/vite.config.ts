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
  }
})
