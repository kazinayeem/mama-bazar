import { BadgeCheck, Quote } from 'lucide-react'
import { Link } from 'react-router-dom'
import StarRating from '../../components/common/StarRating'
import type { ProductReview } from '../../types'

interface ReviewsSectionProps {
  items: ProductReview[]
}

const ReviewsSection = ({ items }: ReviewsSectionProps) => {
  if (items.length === 0) return null
  return (
    <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
      {items.map((review) => (
        <article
          key={review.id}
          className="relative flex flex-col rounded-xl border border-slate-100 bg-slate-50 p-4 transition hover:shadow-sm dark:border-slate-800 dark:bg-slate-800/40"
        >
          <Quote size={22} className="absolute right-4 top-4 text-primary/10" />
          <div className="flex items-center justify-between gap-2">
            <StarRating rating={review.rating} size={12} />
            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">
              <BadgeCheck size={10} /> Verified
            </span>
          </div>
          <p className="mt-3 flex-1 text-xs leading-6 text-slate-600 dark:text-slate-300 line-clamp-4">
            &ldquo;{review.comment}&rdquo;
          </p>
          <div className="mt-3 flex items-center gap-2.5 border-t border-slate-100 pt-3 dark:border-slate-700">
            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary to-brand font-headline text-xs font-bold text-white">
              {(review.customerName || 'G').slice(0, 1).toUpperCase()}
            </span>
            <div className="min-w-0">
              <p className="truncate text-xs font-bold text-slate-800 dark:text-white">{review.customerName || 'Verified customer'}</p>
              {review.productSlug ? (
                <Link className="block truncate text-[10px] text-slate-400 hover:text-primary" to={`/products/${review.productSlug}`}>
                  {review.productTitle?.slice(0, 36)}
                </Link>
              ) : review.productTitle ? (
                <p className="truncate text-[10px] text-slate-400">{review.productTitle.slice(0, 36)}</p>
              ) : null}
            </div>
          </div>
        </article>
      ))}
    </div>
  )
}

export default ReviewsSection
