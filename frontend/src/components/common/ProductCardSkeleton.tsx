const ProductCardSkeleton = () => {
  return (
    <div className="flex h-full flex-col overflow-hidden rounded-[18px] bg-white shadow-card ring-1 ring-slate-100 dark:bg-slate-900 dark:ring-slate-800">
      <div className="skeleton h-[180px] w-full shrink-0 sm:h-[200px]" />
      <div className="flex flex-1 flex-col gap-3 p-4">
        <div className="skeleton h-3 w-16 rounded-full" />
        <div className="skeleton h-4 w-4/5 rounded-full" />
        <div className="skeleton h-4 w-3/5 rounded-full" />
        <div className="skeleton mt-2 h-5 w-1/2 rounded-full" />
        <div className="skeleton mt-auto h-10 w-full rounded-full" />
      </div>
    </div>
  )
}

export const ProductGridSkeleton = ({ count = 8 }: { count?: number }) => {
  return (
    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      {Array.from({ length: count }).map((_, index) => (
        <ProductCardSkeleton key={index} />
      ))}
    </div>
  )
}

export default ProductCardSkeleton
