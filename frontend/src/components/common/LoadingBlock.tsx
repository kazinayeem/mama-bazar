const LoadingBlock = ({ label = 'Loading...' }: { label?: string }) => {
  return (
    <div className="flex min-h-[260px] items-center justify-center bg-surface-container-low">
      <div className="text-center">
        <div className="mx-auto mb-3 h-10 w-10 animate-spin rounded-full border-2 border-outline-variant border-t-on-surface" />
        <p className="text-xs font-bold uppercase tracking-[0.2em] text-on-surface-variant">{label}</p>
      </div>
    </div>
  )
}

export default LoadingBlock
