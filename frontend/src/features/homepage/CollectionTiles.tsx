import { ArrowRight } from 'lucide-react'
import { Link } from 'react-router-dom'
import { resolveUrl } from '@/lib/apiConfig'
import { getCloudinaryCollectionUrl } from '@/lib/cloudinary'
import type { Collection } from '../../types/admin'

interface CollectionTilesProps {
  items: Collection[]
}

const CollectionTiles = ({ items }: CollectionTilesProps) => {
  if (items.length === 0) return null
  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 lg:gap-4">
      {items.slice(0, 8).map((collection) => (
        <Link
          key={collection.slug}
          className="group relative block aspect-[4/3] overflow-hidden rounded-2xl shadow-soft transition-all duration-200 hover:-translate-y-1 hover:shadow-card"
          to={`/shop?collection=${collection.slug}`}
        >
          {collection.image ? (
            <img
              alt={collection.name}
              className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
              height="450"
              loading="lazy"
              src={getCloudinaryCollectionUrl(resolveUrl(collection.image))}
              width="600"
            />
          ) : (
            <div className="h-full w-full bg-gradient-to-br from-primary/30 to-accent/20" />
          )}
          <div className="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/25 to-transparent" />
          <div className="absolute inset-x-0 bottom-0 p-3.5 sm:p-4">
            <h3 className="font-headline text-sm font-extrabold text-white sm:text-base">{collection.name}</h3>
            <span className="mt-1.5 inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-accent">
              Shop now <ArrowRight size={10} className="transition-transform group-hover:translate-x-0.5" />
            </span>
          </div>
        </Link>
      ))}
    </div>
  )
}

export default CollectionTiles
