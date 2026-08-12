import { MessageCircle, MessageSquareMore, Phone } from 'lucide-react'
import { useState } from 'react'
import HomepageSections from '../features/homepage/HomepageSections'
import QuickViewModal from '../components/common/QuickViewModal'
import { SEO } from '../components/common/SEO'
import { useGetHomepageQuery } from '../store/services/commerceApi'
import type { Product } from '../types'

const MESSENGER_URL = 'https://www.facebook.com/profile.php?id=61593199792337'
const WHATSAPP_URL = 'https://wa.me/8801778841408'

const HomePage = () => {
  const [quickViewProduct, setQuickViewProduct] = useState<Product | null>(null)
  const [contactOpen, setContactOpen] = useState(false)
  const { data: homepage, isLoading, isError, refetch } = useGetHomepageQuery()

  return (
    <main className="relative overflow-x-hidden bg-white dark:bg-[#020617]">
      <SEO
        title="Home"
        description="Discover premium products at unbeatable prices. Official warranty, free delivery, and 24/7 support."
        url="/"
      />
      <HomepageSections
        data={homepage}
        hasError={isError}
        loading={isLoading}
        onQuickView={setQuickViewProduct}
        onRetry={refetch}
      />

      <div className="fixed bottom-5 right-5 z-40 flex flex-col items-end gap-3">
        {contactOpen && (
          <div className="flex flex-col items-end gap-2">
            <a
              className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-lg shadow-slate-900/5 transition hover:border-primary hover:text-primary-foreground dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
              href={MESSENGER_URL}
              rel="noopener noreferrer"
              target="_blank"
            >
              <MessageCircle size={16} className="text-blue-600" />
              Messenger
            </a>
            <a
              className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-lg shadow-slate-900/5 transition hover:border-emerald-500 hover:text-emerald-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
              href={WHATSAPP_URL}
              rel="noopener noreferrer"
              target="_blank"
            >
              <Phone size={16} className="text-emerald-500" />
              WhatsApp
            </a>
          </div>
        )}

        <button
          aria-expanded={contactOpen}
          aria-label="Open contact options"
          className="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg shadow-primary/25 transition hover:bg-primary-700 active:scale-95"
          onClick={() => setContactOpen((prev) => !prev)}
          type="button"
        >
          {contactOpen ? <MessageSquareMore size={20} /> : <MessageCircle size={20} />}
        </button>
      </div>

      <QuickViewModal key={quickViewProduct?.id ?? 'closed'} onClose={() => setQuickViewProduct(null)} product={quickViewProduct} />
    </main>
  )
}

export default HomePage
