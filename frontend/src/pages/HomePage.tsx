import { useState } from 'react'
import HomepageSections from '../features/homepage/HomepageSections'
import QuickViewModal from '../components/common/QuickViewModal'
import { SEO } from '../components/common/SEO'
import type { Product } from '../types'
import { useGetHomepageConfigQuery } from '../store/services/commerceApi'

const HomePage = () => {
  const [quickViewProduct, setQuickViewProduct] = useState<Product | null>(null)
  const { data: config, isLoading, isError, refetch } = useGetHomepageConfigQuery()

  return (
    <main className="relative bg-white">
      <SEO
        title="Home"
        description="Discover premium products at unbeatable prices. Official warranty, free delivery, and 24/7 support."
        url="/"
      />
      <HomepageSections
        config={config}
        hasError={isError}
        isLoading={isLoading}
        onQuickView={setQuickViewProduct}
        onRetry={refetch}
      />
      <QuickViewModal
        key={quickViewProduct?.id ?? 'closed'}
        onClose={() => setQuickViewProduct(null)}
        product={quickViewProduct}
      />
    </main>
  )
}

export default HomePage
