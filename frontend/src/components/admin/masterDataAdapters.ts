import type { CatalogListResult, CatalogRemoveResult } from './CatalogCrudPage'

type MutationResult = { error?: unknown }

export const mutationError = (res: MutationResult): string => {
  const err = res.error as { data?: { message?: string } } | undefined
  return err?.data?.message || 'Request failed'
}

export const usageFromError = (res: MutationResult): { usageCount?: number; subCategories?: number } | undefined => {
  const data = (res.error as { data?: { data?: { usageCount?: number; subCategories?: number } } })?.data?.data
  if (data && (data.usageCount !== undefined || data.subCategories !== undefined)) return data
  return undefined
}

export const removeResult = (res: MutationResult): CatalogRemoveResult | undefined => {
  const usage = usageFromError(res)
  if (usage) return { usageCount: usage.usageCount, subCategories: usage.subCategories }
  if (res.error) throw new Error(mutationError(res))
  return undefined
}

export const moveResult = (res: MutationResult): void => {
  if (res.error) throw new Error(mutationError(res))
}

export interface AdminListResponse<T> {
  data: T[]
  total: number
  page: number
  limit: number
  totalPages: number
}

export const toListResult = <T>(res: AdminListResponse<T>): CatalogListResult<T> => ({
  data: res.data || [],
  pagination: { page: res.page, limit: res.limit, total: res.total, totalPages: res.totalPages },
})
