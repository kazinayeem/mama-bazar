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
    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      {items.map((review) => (
        <article
          key={review.id}
          className="relative flex flex-col rounded-2xl border border-slate-100 bg-white p-5 shadow-soft transition-all duration-200 hover:-translate-y-0.5 hover:shadow-card dark:border-slate-800 dark:bg-slate-900"
        >
          <Quote size={26} className="absolute right-5 top-5 text-primary/10" />
          <div className="flex items-center justify-between gap-2">
            <StarRating rating={review.rating} size={14} />
            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">
              <BadgeCheck size={10} /> Verified
            </span>
          </div>
          <p className="mt-4 flex-1 text-[13px] leading-6 text-slate-600 line-clamp-4 dark:text-slate-300">
            &ldquo;{review.comment}&rdquo;
          </p>
          <div className="mt-4 flex items-center gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary to-brand font-headline text-xs font-bold text-white">
              {(review.customerName || 'G').slice(0, 1).toUpperCase()}
            </span>
            <div className="min-w-0">
              <p className="truncate text-[13px] font-bold text-slate-800 dark:text-white">
                {review.customerName || 'Verified customer'}
              </p>
              {review.productSlug ? (
                <Link className="block truncate text-[11px] text-slate-400 hover:text-primary" to={`/products/${review.productSlug}`}>
                  {review.productTitle?.slice(0, 40)}
                </Link>
              ) : review.productTitle ? (
                <p className="truncate text-[11px] text-slate-400">{review.productTitle.slice(0, 40)}</p>
              ) : null}
            </div>
          </div>
        </article>
      ))}
    </div>
  )
}

export default ReviewsSection
