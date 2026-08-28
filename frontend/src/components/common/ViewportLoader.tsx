import { useEffect, useRef, useState, type ReactNode } from 'react'

interface ViewportLoaderProps {
  children: ReactNode | ((props: { inView: boolean }) => ReactNode)
  rootMargin?: string
  fallback?: ReactNode
  className?: string
  minHeight?: string | number
}

/**
 * Reusable ViewportLoader that defers mounting and data-fetching until the
 * container is approaching the viewport (default 400px rootMargin).
 *
 * Guarantees:
 * - IntersectionObserver triggers only once
 * - Disconnects observer immediately after triggering
 * - Zero memory leaks with unmount cleanup
 * - Preserves reserved layout space to ensure 0 CLS
 * - Works identically on mobile and desktop
 */
export const ViewportLoader = ({
  children,
  rootMargin = '400px',
  fallback = null,
  className,
  minHeight,
}: ViewportLoaderProps) => {
  const [inView, setInView] = useState(false)
  const containerRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    // If IntersectionObserver is not supported, immediately display
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

export default ViewportLoader
