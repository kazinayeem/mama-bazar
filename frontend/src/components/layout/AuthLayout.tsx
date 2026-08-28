import React from 'react'
import { Outlet, Link, useLocation } from 'react-router-dom'
import { motion, AnimatePresence } from 'framer-motion'
import { ArrowLeft, ShieldCheck, HelpCircle } from 'lucide-react'

export const AuthLayout: React.FC = () => {
  const location = useLocation()

  return (
    <div className="flex min-h-screen flex-col bg-[#F9FAF9] text-neutral-900 antialiased selection:bg-brand-green-100 selection:text-brand-green-700">
      {/* ── Top Auth Header ── */}
      <header className="sticky top-0 z-40 w-full border-b border-neutral-200/70 bg-white/90 backdrop-blur-md transition-colors">
        <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
          {/* Brand Logo */}
          <Link
            to="/"
            className="group flex items-center gap-2.5 transition-transform duration-150 active:scale-95"
            aria-label="Mama Bazar Home"
          >
            <img
              src="/brandlogo.png"
              alt="Mama Bazar Logo"
              className="h-8 w-auto object-contain transition-transform duration-200 group-hover:scale-105"
            />
            <span className="font-headline text-lg tracking-tight">
              <span className="font-bold text-brand-green-500">Mama</span>{' '}
              <span className="font-bold text-brand-orange-500">Bazar</span>
            </span>
          </Link>

          {/* Right Header Navigation */}
          <div className="flex items-center gap-3 sm:gap-5">
            <div className="hidden sm:flex items-center gap-1.5 text-xs text-neutral-500">
              <ShieldCheck className="h-4 w-4 text-brand-green-500" />
              <span>Secure Authentication</span>
            </div>

            <span className="hidden sm:inline-block h-3.5 w-px bg-neutral-200" />

            <Link
              to="/contact"
              className="hidden sm:inline-flex items-center gap-1 text-xs font-medium text-neutral-600 hover:text-brand-green-600 transition-colors"
            >
              <HelpCircle className="h-3.5 w-3.5" />
              <span>Help & Support</span>
            </Link>

            <Link
              to="/shop"
              className="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-neutral-700 shadow-sm transition hover:border-brand-green-300 hover:bg-neutral-50 hover:text-brand-green-600 active:scale-95"
            >
              <ArrowLeft className="h-3.5 w-3.5" />
              <span>Return to Shop</span>
            </Link>
          </div>
        </div>
      </header>

      {/* ── Main Auth Form Container with Animated Page Transitions ── */}
      <main className="flex flex-1 items-center justify-center px-4 py-8 sm:py-12 md:py-16">
        <div className="w-full max-w-[440px]">
          <AnimatePresence mode="wait" initial={false}>
            <motion.div
              key={location.pathname}
              initial={{ opacity: 0, y: 12 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -12 }}
              transition={{ duration: 0.2, ease: [0.25, 1, 0.5, 1] }}
              className="w-full"
            >
              <Outlet />
            </motion.div>
          </AnimatePresence>
        </div>
      </main>

      {/* ── Minimal Auth Footer ── */}
      <footer className="w-full border-t border-neutral-200/60 bg-white/60 py-6 text-center text-xs text-neutral-500 backdrop-blur-sm">
        <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 sm:flex-row sm:px-6 lg:px-8">
          <p>© {new Date().getFullYear()} Mama Bazar. All rights reserved.</p>
          <div className="flex items-center gap-4 text-xs font-medium text-neutral-500">
            <Link to="/privacy-policy" className="hover:text-brand-green-600 transition-colors">
              Privacy Policy
            </Link>
            <span>•</span>
            <Link to="/terms-and-conditions" className="hover:text-brand-green-600 transition-colors">
              Terms of Service
            </Link>
            <span>•</span>
            <Link to="/refund-policy" className="hover:text-brand-green-600 transition-colors">
              Return & Refund
            </Link>
          </div>
        </div>
      </footer>
    </div>
  )
}

export default AuthLayout
