import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { Provider } from 'react-redux'
import { BrowserRouter } from 'react-router-dom'
import { HelmetProvider } from 'react-helmet-async'
import { Toaster } from 'sonner'
import './index.css'
import App from './App.tsx'
import { ToastProvider } from './components/common/ToastProvider'
import { store } from './store'

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <Provider store={store}>
      <HelmetProvider>
        <ToastProvider>
          <BrowserRouter>
            <App />
          </BrowserRouter>
          <Toaster position="top-right" richColors />
        </ToastProvider>
      </HelmetProvider>
    </Provider>
  </StrictMode>,
)
