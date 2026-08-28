import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { Provider } from 'react-redux'
import { BrowserRouter } from 'react-router-dom'
import { HelmetProvider } from 'react-helmet-async'
import { Toaster } from 'sonner'
import './index.css'
import App from './App.tsx'
import ScrollToTop from './components/common/ScrollToTop'
import { ToastProvider } from './components/common/ToastProvider'
import { store } from './store'
import { registerSW } from 'virtual:pwa-register'

// Register PWA Service Worker for offline capabilities & instant caching
if ('serviceWorker' in navigator && import.meta.env.PROD) {
  registerSW({ immediate: true })
}

// Light-only: ensure no dark class can ever be applied at boot.
document.documentElement.classList.remove('dark')

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <Provider store={store}>
      <HelmetProvider>
        <ToastProvider>
          <BrowserRouter>
            <ScrollToTop />
            <App />
          </BrowserRouter>
          <Toaster position="top-right" richColors />
        </ToastProvider>
      </HelmetProvider>
    </Provider>
  </StrictMode>,
)
