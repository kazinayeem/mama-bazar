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
  const { data: homepage, isLoading, isError, refetch } = useGetHomepageQuery()

  return (
    <main className="relative overflow-x-hidden bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.08),_transparent_34%),linear-gradient(180deg,#f8fafc_0%,#ffffff_16%,#ffffff_100%)] dark:bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.12),_transparent_28%),linear-gradient(180deg,#020617_0%,#020617_100%)]">
      <SEO
        title="Home"
        description="Discover premium products at unbeatable prices. Official warranty, free delivery, and 24/7 support."
        url="/"
      />
      <div className="pointer-events-none absolute inset-x-0 top-0 z-0 h-64 bg-gradient-to-b from-primary/10 via-transparent to-transparent dark:from-primary/20" />
      <HomepageSections
        data={homepage}
        hasError={isError}
        loading={isLoading}
        onQuickView={setQuickViewProduct}
        onRetry={refetch}
      />

      {/* Single floating chat button — fixed bottom-right only */}
      <button
        aria-label="Chat with support"
        className="fixed bottom-5 right-5 z-40 flex h-12 w-12 items-center justify-center rounded-full bg-primary text-white shadow-lg shadow-primary/25 transition hover:bg-primary-700 active:scale-95"
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
