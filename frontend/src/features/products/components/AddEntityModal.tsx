import { useEffect, useState } from 'react'
import { Loader2, Plus } from 'lucide-react'
import { toast } from 'sonner'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
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
import CropImageField from '@/components/admin/CropImageField'
import { useGetCategoriesQuery } from '@/store/services/commerceApi'
import {
  useCreateCategoryMutation,
  useCreateBrandMutation,
  useCreateCollectionMutation,
  useCreateVendorMutation,
  useCreateSupplierMutation,
  useCreateColorMutation,
  useCreateSizeMutation,
} from '@/store/services/adminProductsApi'

export type MasterEntity = 'category' | 'brand' | 'collection' | 'vendor' | 'supplier' | 'color' | 'size'

export interface AddEntityResult {
  id: number
  name: string
}

interface AddEntityModalProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  entity: MasterEntity
  /** Parent for new sub/child categories */
  defaultParentId?: number | null
  onCreated: (result: AddEntityResult) => void
}

const ENTITY_LABELS: Record<MasterEntity, string> = {
  category: 'Category',
  brand: 'Brand',
  collection: 'Collection',
  vendor: 'Vendor',
  supplier: 'Supplier',
  color: 'Color',
  size: 'Size',
}

const SIZE_TYPES = [
  { value: 'clothing', label: 'Clothing' },
  { value: 'shoes', label: 'Shoes' },
  { value: 'general', label: 'General' },
  { value: 'custom', label: 'Custom' },
]

/**
 * Small, focused modal that creates a single master-data record right from the
 * product form. On success the RTK cache is invalidated, so the dropdown
 * refreshes automatically, and the newly created option gets selected.
 */
const AddEntityModal = ({
  open,
  onOpenChange,
  entity,
  defaultParentId,
  onCreated,
}: AddEntityModalProps) => {
  const { data: allCategories = [] } = useGetCategoriesQuery()
  const [createCategory] = useCreateCategoryMutation()
  const [createBrand] = useCreateBrandMutation()
  const [createCollection] = useCreateCollectionMutation()
  const [createVendor] = useCreateVendorMutation()
  const [createSupplier] = useCreateSupplierMutation()
  const [createColor] = useCreateColorMutation()
  const [createSize] = useCreateSizeMutation()

  const [name, setName] = useState('')
  const [parentId, setParentId] = useState<string>(defaultParentId ? String(defaultParentId) : 'none')
  const [image, setImage] = useState('')
  const [contact, setContact] = useState('')
  const [hex, setHex] = useState('#000000')
  const [sizeType, setSizeType] = useState('general')
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    if (open) {
      setName('')
      setParentId(defaultParentId ? String(defaultParentId) : 'none')
      setImage('')
      setContact('')
      setHex('#000000')
      setSizeType('general')
    }
  }, [open, defaultParentId])

  const close = () => {
    if (!saving) onOpenChange(false)
  }

  const handleSave = async () => {
    if (!name.trim()) {
      toast.error(`${ENTITY_LABELS[entity]} name is required`)
      return
    }
    setSaving(true)
    try {
      let res: { error?: unknown; data?: { id?: number; name?: string } }
      switch (entity) {
        case 'category':
          res = await createCategory({
            name: name.trim(),
            parentId: parentId === 'none' ? null : Number(parentId),
            image: image || undefined,
            status: 'active',
          })
          break
        case 'brand':
          res = await createBrand({ name: name.trim(), logo: image || undefined, status: 'active' })
          break
        case 'collection':
          res = await createCollection({ name: name.trim(), image: image || undefined, status: 'active' })
          break
        case 'vendor':
          res = await createVendor({ name: name.trim(), contact: contact.trim() || undefined, status: 'active' })
          break
        case 'supplier':
          res = await createSupplier({ name: name.trim(), contact: contact.trim() || undefined, status: 'active' })
          break
        case 'color':
          res = await createColor({ name: name.trim().toLowerCase(), displayName: name.trim(), hex, status: 'active' })
          break
        case 'size':
          res = await createSize({ name: name.trim(), type: sizeType, status: 'active' })
          break
      }
      if (res.error) {
        const msg = (res.error as { data?: { message?: string } }).data?.message
        throw new Error(msg || `Could not create ${ENTITY_LABELS[entity].toLowerCase()}`)
      }
      toast.success(`${ENTITY_LABELS[entity]} created`)
      onOpenChange(false)
      onCreated({ id: res.data?.id || 0, name: name.trim() })
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  const categoryOptions = allCategories.filter((c) => c.status !== 'archived')

  return (
    <Dialog open={open} onOpenChange={close}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Plus className="h-4 w-4" /> Add New {ENTITY_LABELS[entity]}
          </DialogTitle>
        </DialogHeader>
        <div className="space-y-4">
          <div>
            <Label>{ENTITY_LABELS[entity]} Name *</Label>
            <Input value={name} onChange={(e) => setName(e.target.value)} placeholder={`e.g. ${entity === 'category' ? 'Smart Home' : entity === 'brand' ? 'Xiaomi' : entity === 'color' ? 'Black' : 'M'}`} />
          </div>

          {entity === 'category' && (
            <div>
              <Label>Parent Category</Label>
              <Select value={parentId} onValueChange={setParentId}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Top-level (no parent)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">Top-level (no parent)</SelectItem>
                  {categoryOptions.map((c) => (
                    <SelectItem key={c.id} value={String(c.id)}>
                      {c.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          )}

          {(entity === 'category' || entity === 'brand' || entity === 'collection') && (
            <CropImageField
              label={entity === 'brand' ? 'Logo (optional)' : 'Image (optional)'}
              value={image}
              onChange={setImage}
              aspectKey="square"
              folder={entity === 'brand' ? 'brands' : entity === 'collection' ? 'collections' : 'categories'}
            />
          )}

          {(entity === 'vendor' || entity === 'supplier') && (
            <div>
              <Label>Contact Person</Label>
              <Input value={contact} onChange={(e) => setContact(e.target.value)} placeholder="Optional" />
            </div>
          )}

          {entity === 'color' && (
            <div>
              <Label>Hex Color</Label>
              <div className="flex items-center gap-2">
                <input
                  type="color"
                  value={/^#[0-9a-fA-F]{6}$/.test(hex) ? hex : '#000000'}
                  onChange={(e) => setHex(e.target.value)}
                  className="h-9 w-10 cursor-pointer rounded border bg-transparent p-1"
                />
                <Input value={hex} onChange={(e) => setHex(e.target.value)} placeholder="#000000" />
              </div>
            </div>
          )}

          {entity === 'size' && (
            <div>
              <Label>Type</Label>
              <Select value={sizeType} onValueChange={setSizeType}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {SIZE_TYPES.map((t) => (
                    <SelectItem key={t.value} value={t.value}>
                      {t.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          )}
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={close} disabled={saving}>
            Cancel
          </Button>
          <Button onClick={handleSave} disabled={saving}>
            {saving && <Loader2 className="mr-1 h-4 w-4 animate-spin" />}
            Create {ENTITY_LABELS[entity]}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

export default AddEntityModal
