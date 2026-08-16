interface SkeletonRowProps {
  className?: string
}

const SkeletonRow = ({ className = '' }: SkeletonRowProps) => (
  <div className={`animate-pulse rounded-lg bg-slate-100 ${className}`} />
)

export const PageSkeleton = () => (
  <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <SkeletonRow className="h-9 w-56" />
    <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
      {Array.from({ length: 8 }).map((_, index) => (
        <div className="rounded-2xl border border-slate-100 bg-white p-3" key={index}>
          <SkeletonRow className="h-40 w-full" />
          <SkeletonRow className="mt-3 h-3 w-1/3" />
          <SkeletonRow className="mt-2 h-4 w-5/6" />
          <SkeletonRow className="mt-2 h-3 w-1/2" />
          <SkeletonRow className="mt-4 h-9 w-full" />
        </div>
      ))}
    </div>
  </div>
)

export const TableSkeleton = ({ rows = 6, columns = 5 }: { rows?: number; columns?: number }) => (
  <div className="overflow-hidden rounded-2xl border border-slate-100 bg-white">
    <div className="flex gap-4 border-b border-slate-100 p-4">
      {Array.from({ length: columns }).map((_, index) => (
        <SkeletonRow className="h-3 flex-1" key={index} />
      ))}
    </div>
    {Array.from({ length: rows }).map((_, index) => (
      <div className="flex gap-4 border-b border-slate-50 p-4" key={index}>
        {Array.from({ length: columns }).map((_, colIndex) => (
          <SkeletonRow className="h-4 flex-1" key={colIndex} />
        ))}
      </div>
    ))}
  </div>
)

export const DashboardCardSkeleton = ({ rows = 4 }: { rows?: number }) => (
  <div className="rounded-2xl border border-slate-100 bg-white p-5">
    <div className="flex items-center justify-between">
      <SkeletonRow className="h-4 w-28" />
      <SkeletonRow className="h-8 w-8 rounded-full" />
    </div>
    {Array.from({ length: rows }).map((_, index) => (
      <SkeletonRow className="mt-4 h-3 w-full" key={index} />
    ))}
  </div>
)