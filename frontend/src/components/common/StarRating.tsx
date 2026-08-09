import { Star, StarHalf } from 'lucide-react'

interface StarRatingProps {
  rating: number
  size?: number
  className?: string
  showValue?: boolean
}

const StarRating = ({ rating, size = 14, className = '', showValue = false }: StarRatingProps) => {
  const fullStars = Math.floor(rating)
  const hasHalf = rating - fullStars >= 0.4

  return (
    <span className={`inline-flex items-center gap-1 ${className}`}>
      <span className="inline-flex items-center" aria-hidden="true">
        {Array.from({ length: 5 }).map((_, index) => {
          if (index < fullStars) {
            return <Star key={index} size={size} className="fill-amber-400 text-amber-400" />
          }
          if (index === fullStars && hasHalf) {
            return <StarHalf key={index} size={size} className="fill-amber-400 text-amber-400" />
          }
          return <Star key={index} size={size} className="text-slate-300" />
        })}
      </span>
      {showValue && <span className="text-xs font-semibold text-slate-600">{rating.toFixed(1)}</span>}
    </span>
  )
}

export default StarRating
