import { useEffect, useRef, useState, type ReactNode } from 'react'

interface LazySectionProps {
  children: ReactNode
  rootMargin?: string
  fallback?: ReactNode
  className?: string
}

const LazySection = ({ children, rootMargin = '200px', fallback = null, className }: LazySectionProps) => {
  const [isVisible, setIsVisible] = useState(false)
  const ref = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setIsVisible(true)
          if (ref.current) observer.unobserve(ref.current)
        }
      },
      { rootMargin, threshold: 0.1 }
    )

    if (ref.current) observer.observe(ref.current)
    return () => observer.disconnect()
  }, [rootMargin])

  return (
    <div ref={ref} className={className}>
      {isVisible ? children : fallback}
    </div>
  )
}

export default LazySection