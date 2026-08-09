import { Loader2, X } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

interface TagInputProps {
  label: string
  value: string[]
  onChange: (tags: string[]) => void
  placeholder?: string
  hint?: string
}

const TagInput = ({ label, value, onChange, placeholder, hint }: TagInputProps) => {
  const add = (raw: string) => {
    const item = raw.trim()
    if (!item) return
    if (!value.includes(item)) onChange([...value, item])
  }

  return (
    <div className="space-y-2">
      <Label>{label}</Label>
      <div className="flex flex-wrap items-center gap-2">
        {value.map((item) => (
          <Badge key={item} variant="secondary" className="gap-1 pr-1">
            {item}
            <button type="button" onClick={() => onChange(value.filter((x) => x !== item))} aria-label={`Remove ${item}`}>
              <X className="h-3 w-3" />
            </button>
          </Badge>
        ))}
        <Input
          className="h-7 w-44"
          placeholder={placeholder || 'Type and press Enter'}
          onKeyDown={(e) => {
            if (e.key === 'Enter') {
              e.preventDefault()
              add((e.target as HTMLInputElement).value)
              ;(e.target as HTMLInputElement).value = ''
            }
          }}
          onBlur={(e) => {
            if (e.target.value.trim()) {
              add(e.target.value)
              e.target.value = ''
            }
          }}
        />
      </div>
      {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
    </div>
  )
}

export const TagInputLoading = () => (
  <div className="flex items-center gap-2 text-sm text-muted-foreground">
    <Loader2 className="h-4 w-4 animate-spin" /> Loading…
  </div>
)

export default TagInput
