import { Loader2, Plus } from 'lucide-react'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectSeparator,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'

export interface ReferenceOption {
  value: string
  label: string
}

const ADD_NEW_VALUE = '__add_new__'

interface ReferenceSelectProps {
  label: string
  value: string
  onChange: (value: string) => void
  options: ReferenceOption[]
  loading?: boolean
  placeholder?: string
  emptyLabel?: string
  required?: boolean
  disabled?: boolean
  disabledLabel?: string
  /** Renders a "+ Add New …" option at the bottom of the dropdown */
  onAddNew?: () => void
}

const ReferenceSelect = ({
  label,
  value,
  onChange,
  options,
  loading,
  placeholder = 'Select…',
  emptyLabel = 'None',
  required,
  disabled,
  disabledLabel,
  onAddNew,
}: ReferenceSelectProps) => (
  <div className="space-y-2">
    <Label>
      {label}
      {required && <span className="ml-0.5 text-destructive">*</span>}
    </Label>
    {loading ? (
      <Skeleton className="h-10 w-full" />
    ) : (
      <Select
        value={value}
        disabled={disabled}
        onValueChange={(v) => {
          if (v === ADD_NEW_VALUE) {
            onAddNew?.()
            return
          }
          onChange(v)
        }}
      >
        <SelectTrigger className="w-full">
          <SelectValue placeholder={placeholder} />
          {disabled && <span className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">Locked</span>}
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="none">{disabled ? disabledLabel || 'Unavailable' : emptyLabel}</SelectItem>
          {options.map((o) => (
            <SelectItem key={o.value} value={o.value}>
              {o.label}
            </SelectItem>
          ))}
          {onAddNew && (
            <>
              <SelectSeparator />
              <SelectItem value={ADD_NEW_VALUE}>
                <span className="flex items-center gap-1.5 text-primary">
                  <Plus className="h-3.5 w-3.5" /> Add New {label.replace(/ \*$/, '')}
                </span>
              </SelectItem>
            </>
          )}
        </SelectContent>
      </Select>
    )}
  </div>
)

export default ReferenceSelect

export const ReferenceSelectLoading = () => (
  <div className="flex items-center gap-2 text-sm text-muted-foreground">
    <Loader2 className="h-4 w-4 animate-spin" /> Loading…
  </div>
)
