import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd())
  const apiTarget = (env.VITE_API_URL || env.VITE_API_BASE_URL || 'http://localhost:5000').trim()

  return {
    plugins: [
      react(),
      VitePWA({
        registerType: 'autoUpdate',
        includeAssets: ['brandlogo.png', 'favicon.svg', 'icons.svg', 'robots.txt', 'sitemap.xml', 'fonts/*.woff2'],
        manifest: {
          name: 'Mama Bazar - Premium Ecommerce',
          short_name: 'Mama Bazar',
          description: 'Discover premium products and gadgets with official warranty and fast delivery.',
          theme_color: '#0f172a',
          background_color: '#ffffff',
          display: 'standalone',
          orientation: 'portrait-primary',
          start_url: '/',
          scope: '/',
          id: '/',
          categories: ['shopping', 'ecommerce', 'lifestyle'],
          icons: [
            {
              src: '/pwa-192x192.png',
              sizes: '192x192',
              type: 'image/png',
            },
            {
              src: '/pwa-512x512.png',
              sizes: '512x512',
              type: 'image/png',
            },
            {
              src: '/maskable-icon-512x512.png',
              sizes: '512x512',
              type: 'image/png',
              purpose: 'maskable',
            },
          ],
        },
        workbox: {
          globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
          navigateFallback: '/index.html',
          navigateFallbackDenylist: [/^\/api\//, /^\/uploads\//],
          runtimeCaching: [
            {
              urlPattern: /^https:\/\/res\.cloudinary\.com\/.*/i,
              handler: 'StaleWhileRevalidate',
              options: {
                cacheName: 'cloudinary-images',
                expiration: {
                  maxEntries: 120,
                  maxAgeSeconds: 30 * 24 * 60 * 60,
                },
                cacheableResponse: {
                  statuses: [0, 200],
                },
              },
            },
            {
              urlPattern: /\/api\/(categories|brands|collections|colors|sizes|banners|homepage\/config|settings\/public|policies)/i,
              handler: 'StaleWhileRevalidate',
              options: {
                cacheName: 'public-reference-api',
                expiration: {
                  maxEntries: 50,
                  maxAgeSeconds: 24 * 60 * 60,
                },
                cacheableResponse: {
                  statuses: [0, 200],
                },
              },
            },
            {
              urlPattern: /\/api\/products/i,
              handler: 'NetworkFirst',
              options: {
                cacheName: 'products-api',
                networkTimeoutSeconds: 3,
                expiration: {
                  maxEntries: 60,
                  maxAgeSeconds: 6 * 60 * 60,
                },
                cacheableResponse: {
                  statuses: [0, 200],
                },
              },
            },
          ],
        },
      }),
    ],
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
