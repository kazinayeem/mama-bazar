import { useState } from 'react'
import HomepageSections from '../features/homepage/HomepageSections'
import QuickViewModal from '../components/common/QuickViewModal'
import { SEO } from '../components/common/SEO'
import type { Product } from '../types'

const HomePage = () => {
  const [quickViewProduct, setQuickViewProduct] = useState<Product | null>(null)

  return (
    <main className="relative overflow-x-hidden bg-white">
      <SEO
        title="Home"
        description="Discover premium products at unbeatable prices. Official warranty, free delivery, and 24/7 support."
        url="/"
      />
      <HomepageSections onQuickView={setQuickViewProduct} />
      <QuickViewModal
        key={quickViewProduct?.id ?? 'closed'}
        onClose={() => setQuickViewProduct(null)}
        product={quickViewProduct}
      />
    </main>
  )
}

export default HomePage
