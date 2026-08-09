import { useMemo, useState } from 'react'
import type { Product } from '../../types'

type QuickAddModalProps = {
  open: boolean
  product: Product | null
  onClose: () => void
  onConfirm: (selection: { size?: string; color?: string; image?: string }) => void
}

const QuickAddModal = ({ open, product, onClose, onConfirm }: QuickAddModalProps) => {
  const [size, setSize] = useState('')
  const [color, setColor] = useState('')
  const [lastSelection, setLastSelection] = useState<{ open: boolean; product: Product | null }>({
    open: false,
    product: null,
  })

  const sizeOptions = useMemo(() => product?.sizeOptions || [], [product?.sizeOptions])
  const colorOptions = useMemo(() => product?.colorOptions || [], [product?.colorOptions])

  if (lastSelection.open !== open || lastSelection.product !== product) {
    setLastSelection({ open, product })
    if (open && product) {
      setSize(sizeOptions[0] || '')
      setColor(colorOptions[0]?.name || '')
    }
  }

  if (!open || !product) return null

  const selectedColorImage = colorOptions.find((item) => item.name === color)?.image
  const image = selectedColorImage || product.images[0]

  return (
    <div className="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 px-4">
      <div className="w-full max-w-md rounded-xl bg-white p-6">
        <h3 className="text-xl font-bold text-gray-900">Quick Add</h3>
        <p className="mt-1 text-sm text-gray-600">{product.title}</p>

        {sizeOptions.length > 0 && (
          <div className="mt-5">
            <p className="mb-2 text-xs font-semibold uppercase tracking-[0.15em] text-gray-600">Choose Size</p>
            <div className="grid grid-cols-5 gap-2">
              {sizeOptions.map((option) => (
                <button
                  className={`rounded border px-2 py-2 text-sm ${size === option ? 'border-black bg-black text-white' : 'border-gray-300 text-gray-800'}`}
                  key={option}
                  onClick={() => setSize(option)}
                  type="button"
                >
                  {option}
                </button>
              ))}
            </div>
          </div>
        )}

        {colorOptions.length > 0 && (
          <div className="mt-5">
            <p className="mb-2 text-xs font-semibold uppercase tracking-[0.15em] text-gray-600">Choose Color</p>
            <div className="flex flex-wrap gap-2">
              {colorOptions.map((option) => (
                <button
                  className={`inline-flex items-center gap-2 rounded border px-3 py-2 text-xs font-semibold uppercase tracking-[0.12em] ${
                    color === option.name ? 'border-black' : 'border-gray-300'
                  }`}
                  key={option.name}
                  onClick={() => setColor(option.name)}
                  type="button"
                >
                  <span
                    className="h-3 w-3 rounded-full border border-black/20"
                    style={{ backgroundColor: option.value || '#d1d5db' }}
                  />
                  {option.name}
                </button>
              ))}
            </div>
          </div>
        )}

        <div className="mt-6 flex gap-3">
          <button
            className="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
            onClick={onClose}
            type="button"
          >
            Cancel
          </button>
          <button
            className="flex-1 rounded-lg bg-black px-4 py-2 text-sm font-medium text-white"
            onClick={() => onConfirm({ size: size || undefined, color: color || undefined, image })}
            type="button"
          >
            Add To Bag
          </button>
        </div>
      </div>
    </div>
  )
}

export default QuickAddModal
