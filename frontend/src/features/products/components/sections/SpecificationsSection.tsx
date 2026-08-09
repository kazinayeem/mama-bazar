import { ListChecks, Plus, X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import FormSection from '../FormSection'
import { useProductForm } from '../ProductFormContext'
import type { SpecFormValue } from '../../lib/productForm'

const newSpec = (): SpecFormValue => ({ key: crypto.randomUUID(), label: '', value: '' })

const SpecificationsSection = () => {
  const { form, set } = useProductForm()

  const updateSpec = (key: string, patch: Partial<SpecFormValue>) => {
    set({ specs: form.specs.map((s) => (s.key === key ? { ...s, ...patch } : s)) })
  }

  const removeSpec = (key: string) => {
    set({ specs: form.specs.filter((s) => s.key !== key) })
  }

  return (
    <FormSection
      title="Specifications"
      description="Technical attributes shown as a key/value table on the product page"
      icon={<ListChecks className="h-4 w-4" />}
    >
      {form.specs.length === 0 && (
        <p className="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground">
          No specifications yet. Add specs like Motor power, Voltage, Material, etc.
        </p>
      )}

      <div className="space-y-3">
        {form.specs.map((s, index) => (
          <div key={s.key} className="grid gap-2 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
            <div className="space-y-2">
              <Label>Label</Label>
              <Input placeholder="Motor power" value={s.label} onChange={(e) => updateSpec(s.key, { label: e.target.value })} />
            </div>
            <div className="space-y-2">
              <Label>Value</Label>
              <Input placeholder="500W" value={s.value} onChange={(e) => updateSpec(s.key, { value: e.target.value })} />
            </div>
            <Button type="button" variant="ghost" size="icon" className="text-destructive" title="Remove specification" onClick={() => removeSpec(s.key)}>
              <X className="h-4 w-4" />
            </Button>
            {index > 0 && <span className="hidden text-xs text-muted-foreground sm:block sm:col-span-2" />}
          </div>
        ))}
      </div>

      <Button type="button" variant="outline" onClick={() => set({ specs: [...form.specs, newSpec()] })}>
        <Plus className="mr-1 h-4 w-4" /> Add Specification
      </Button>
    </FormSection>
  )
}

export default SpecificationsSection
