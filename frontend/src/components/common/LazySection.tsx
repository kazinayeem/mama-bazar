import { useEffect, useRef, useState, type ReactNode } from 'react'

export interface LazySectionProps {
  children: ReactNode | ((props: { inView: boolean }) => ReactNode)
  rootMargin?: string
  fallback?: ReactNode
  className?: string
  minHeight?: string | number
}

/**
 * High-performance Viewport / LazySection loader.
 * Defers rendering and data fetching until the section approaches the viewport.
 */
export const LazySection = ({
  children,
  rootMargin = '400px',
  fallback = null,
  className,
  minHeight,
}: LazySectionProps) => {
  const [inView, setInView] = useState(false)
  const containerRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (typeof window === 'undefined' || !('IntersectionObserver' in window)) {
      setInView(true)
      return
    }

    const element = containerRef.current
    if (!element) return

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setInView(true)
          observer.disconnect()
        }
      },
      {
        rootMargin,
        threshold: 0,
      },
    )

    observer.observe(element)

    return () => {
      observer.disconnect()
    }
  }, [rootMargin])

  return (
    <div ref={containerRef} className={className}>
      {inView
        ? typeof children === 'function'
          ? children({ inView: true })
          : children
        : fallback || (minHeight ? <div style={{ minHeight }} /> : null)}
    </div>
  )
}

export default LazySection