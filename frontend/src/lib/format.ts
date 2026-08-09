export const currency = (value: number | string, unit = 'BDT') => {
  const amount = Number(value)
  if (Number.isNaN(amount)) return `${value}`
  const formatter = new Intl.NumberFormat('en-BD', {
    maximumFractionDigits: 0,
  })
  if (unit === 'BDT') return `Tk ${formatter.format(amount)}`
  return `${unit} ${formatter.format(amount)}`
}

export const salePrice = (price: string | number, discount?: string | number | null) => {
  const p = Number(price)
  const d = Number(discount || 0)
  if (!d) return p
  return Math.round(p - (p * d) / 100)
}

export const formatPrice = (value: number, unit = 'BDT') => {
  const formatter = new Intl.NumberFormat('en-BD', {
    maximumFractionDigits: 0,
  })
  if (unit === 'BDT') return `Tk ${formatter.format(value)}`
  return `${unit} ${formatter.format(value)}`
}

export const formatNumber = (value: number) => {
  if (value >= 1000) return `${(value / 1000).toFixed(1).replace(/\.0$/, '')}k`
  return String(value)
}
