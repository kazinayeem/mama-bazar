import { useMemo, useState } from 'react'
import { Link2, Plus, Search, X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import FormSection from '../FormSection'
import { useProductForm } from '../ProductFormContext'
import { RELATION_TYPES } from '../../lib/productForm'
import type { RelationFormValue } from '../../lib/productForm'
import { useGetAdminProductsQuery } from '@/store/services/adminProductsApi'
import { currency } from '@/lib/format'

const newRelation = (): RelationFormValue => ({
  key: crypto.randomUUID(),
  type: 'frequently_bought_together',
  relatedProductId: '',
})

const RelatedProductsSection = () => {
  const { form, set } = useProductForm()
  const [searchFor, setSearchFor] = useState<string>('')

  const { data, isFetching } = useGetAdminProductsQuery(
    { search: searchFor, limit: 6, status: 'active' },
    { skip: searchFor.trim().length < 2 },
  )

  const searchResults = useMemo(() => data?.data || [], [data])

  const updateRelation = (key: string, patch: Partial<RelationFormValue>) => {
    set({ relations: form.relations.map((r) => (r.key === key ? { ...r, ...patch } : r)) })
  }

  const removeRelation = (key: string) => {
    set({ relations: form.relations.filter((r) => r.key !== key) })
  }

  const linkedTitles = useMemo(() => {
    const map = new Map<string, string>()
    searchResults.forEach((p) => map.set(String(p.id), p.title))
    return map
  }, [searchResults])

  return (
    <FormSection
      title="Related Products"
      description="Link products to show recommendations on the product page"
      icon={<Link2 className="h-4 w-4" />}
    >
      {form.relations.length === 0 && (
        <p className="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground">
          No related products. Link products to show recommendations on the product page.
        </p>
      )}

      {form.relations.map((relation, index) => (
        <div key={relation.key} className="space-y-3 rounded-lg border p-4">
          <div className="flex items-center justify-between">
            <p className="text-sm font-semibold">Relation {index + 1}</p>
            <Button type="button" variant="ghost" size="sm" className="text-destructive" onClick={() => removeRelation(relation.key)}>
              <X className="mr-1 h-3 w-3" /> Remove
            </Button>
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-2">
              <Label>Relation Type</Label>
              <Select value={relation.type} onValueChange={(v) => updateRelation(relation.key, { type: v as RelationFormValue['type'] })}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {RELATION_TYPES.map((t) => (
                    <SelectItem key={t.value} value={t.value}>
                      {t.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Related Product</Label>
              {relation.relatedProductId ? (
                <div className="flex items-center justify-between rounded-md border px-3 py-2 text-sm">
                  <span className="truncate text-muted-foreground">
                    #{relation.relatedProductId}
                    {linkedTitles.get(relation.relatedProductId) ? ` · ${linkedTitles.get(relation.relatedProductId)}` : ''}
                  </span>
                  <button type="button" onClick={() => updateRelation(relation.key, { relatedProductId: '' })} aria-label="Unlink product">
                    <X className="h-3 w-3" />
                  </button>
                </div>
              ) : (
                <div className="space-y-1.5">
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      className="pl-9"
                      placeholder="Search by name or SKU…"
                      value={searchFor}
                      onChange={(e) => setSearchFor(e.target.value)}
                    />
                  </div>
                  {searchFor.trim().length >= 2 && (
                    <div className="overflow-hidden rounded-md border bg-popover">
                      {isFetching ? (
                        <div className="space-y-1.5 p-2">
                          <Skeleton className="h-8 w-full" />
                          <Skeleton className="h-8 w-full" />
                        </div>
                      ) : searchResults.length === 0 ? (
                        <p className="px-3 py-2 text-xs text-muted-foreground">No products found</p>
                      ) : (
                        searchResults.map((p) => (
                          <button
                            key={p.id}
                            type="button"
                            onClick={() => {
                              updateRelation(relation.key, { relatedProductId: String(p.id) })
                              setSearchFor('')
                            }}
                            className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm transition-colors hover:bg-accent"
                          >
                            {p.images[0] && <img src={p.images[0]} alt="" className="h-7 w-7 rounded object-cover" />}
                            <span className="min-w-0 flex-1 truncate">{p.title}</span>
                            <span className="shrink-0 text-xs text-muted-foreground">{currency(p.price)}</span>
                          </button>
                        ))
                      )}
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>
        </div>
      ))}

      <Button type="button" variant="outline" onClick={() => set({ relations: [...form.relations, newRelation()] })}>
        <Plus className="mr-1 h-4 w-4" /> Add Relation
      </Button>
    </FormSection>
  )
}

export default RelatedProductsSection
