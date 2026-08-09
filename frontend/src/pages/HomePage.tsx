import { MessageCircle } from 'lucide-react'
import { useState } from 'react'
import HomepageSections from '../features/homepage/HomepageSections'
import QuickViewModal from '../components/common/QuickViewModal'
import { SEO } from '../components/common/SEO'
import { useToast } from '../components/common/ToastProvider'
import { useGetHomepageQuery } from '../store/services/commerceApi'
import type { Product } from '../types'

const HomePage = () => {
  const toast = useToast()
  const [quickViewProduct, setQuickViewProduct] = useState<Product | null>(null)
  const { data: homepage, isLoading } = useGetHomepageQuery()

  return (
    <main className="overflow-x-hidden bg-white dark:bg-slate-950">
      <SEO
        title="Home"
        description="Discover premium products at unbeatable prices. Official warranty, free delivery, and 24/7 support."
        url="/"
      />
      <HomepageSections data={homepage} loading={isLoading} onQuickView={setQuickViewProduct} />

      {/* Single floating chat button — fixed bottom-right only */}
      <button
        aria-label="Chat with support"
        className="fixed bottom-5 right-5 z-40 flex h-12 w-12 items-center justify-center rounded-full bg-primary text-white shadow-lg transition hover:bg-primary-700 active:scale-95"
        onClick={() => toast.success('Chat coming soon!')}
        type="button"
      >
        <MessageCircle size={20} />
      </button>

      <QuickViewModal key={quickViewProduct?.id ?? 'closed'} onClose={() => setQuickViewProduct(null)} product={quickViewProduct} />
    </main>
  )
}

export default HomePage
