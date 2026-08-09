import { ArrowRight } from 'lucide-react'
import { Link } from 'react-router-dom'
import type { Collection } from '../../types/admin'

interface CollectionTilesProps {
  items: Collection[]
}

const CollectionTiles = ({ items }: CollectionTilesProps) => {
  if (items.length === 0) return null
  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
      {items.slice(0, 8).map((collection) => (
        <Link
          key={collection.slug}
          className="group relative block aspect-[4/3] overflow-hidden rounded-xl shadow-sm transition-transform duration-200 hover:-translate-y-0.5 hover:shadow-md"
          to={`/shop?collection=${collection.slug}`}
        >
          {collection.image ? (
            <img
              alt={collection.name}
              className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
              loading="lazy"
              src={collection.image}
            />
          ) : (
            <div className="h-full w-full bg-gradient-to-br from-primary/30 to-accent/20" />
          )}
          <div className="absolute inset-0 bg-gradient-to-t from-slate-950/75 via-slate-950/20 to-transparent" />
          <div className="absolute inset-x-0 bottom-0 p-3">
            <h3 className="font-headline text-sm font-bold text-white">{collection.name}</h3>
            <span className="mt-1 inline-flex items-center gap-1 text-[10px] font-semibold text-white/80">
              Shop <ArrowRight size={10} className="transition-transform group-hover:translate-x-0.5" />
            </span>
          </div>
        </Link>
      ))}
    </div>
  )
}

export default CollectionTiles
