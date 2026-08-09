import { useState } from 'react'
import { AlertTriangle, Loader2 } from 'lucide-react'
import {
  AlertDialog,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Label } from '@/components/ui/label'

export interface MoveOption {
  value: string
  label: string
}

interface UsageAlertDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  entityName: string
  itemName: string
  usageCount: number
  subCategories?: number
  /** Optional replacement targets for "Move products" */
  moveOptions?: MoveOption[]
  /** When true, "Move products" becomes "Remove references" (targets not supported) */
  moveDisabled?: boolean
  busy?: boolean
  /** Called with the target id (null = remove references / delete anyway) */
  onConfirm: (targetId: number | null) => Promise<void> | void
}

/**
 * Delete-protection dialog. Shown when an entity is referenced by products:
 * the admin can Cancel, Move products to a replacement, or Delete Anyway
 * (references are removed, then the entity is deleted).
 */
const UsageAlertDialog = ({
  open,
  onOpenChange,
  entityName,
  itemName,
  usageCount,
  subCategories = 0,
  moveOptions = [],
  moveDisabled = false,
  busy = false,
  onConfirm,
}: UsageAlertDialogProps) => {
  const [target, setTarget] = useState<string>('__remove__')
  const [confirming, setConfirming] = useState(false)

  const handleConfirm = async () => {
    setConfirming(true)
    try {
      await onConfirm(target === '__remove__' ? null : Number(target))
      onOpenChange(false)
    } catch {
      // error handled by caller
    } finally {
      setConfirming(false)
    }
  }

  return (
    <AlertDialog open={open} onOpenChange={(v) => !v && onOpenChange(false)}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle className="flex items-center gap-2">
            <AlertTriangle className="h-5 w-5 text-amber-500" />
            {entityName.charAt(0).toUpperCase() + entityName.slice(1)} in use
          </AlertDialogTitle>
          <AlertDialogDescription asChild>
            <div className="space-y-3">
              <p>
                <strong className="text-foreground">"{itemName}"</strong> is currently used by{' '}
                <strong className="text-foreground">{usageCount} product{usageCount !== 1 ? 's' : ''}</strong>
                {subCategories > 0 && (
                  <>
                    {' '}and has <strong className="text-foreground">{subCategories} sub-categor{subCategories !== 1 ? 'ies' : 'y'}</strong>
                  </>
                )}
                .
              </p>
              {subCategories > 0 ? (
                <p className="text-sm text-destructive">
                  Sub-categories must be moved or deleted before this {entityName} can be removed.
                </p>
              ) : (
                <p className="text-sm text-muted-foreground">
                  Choose what should happen to the products linked to this {entityName} before deleting it.
                </p>
              )}
              {!moveDisabled && subCategories === 0 && (
                <div className="space-y-2">
                  <Label>Move products to</Label>
                  <Select value={target} onValueChange={setTarget}>
                    <SelectTrigger className="w-full">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="__remove__">Remove {entityName} reference (products stay)</SelectItem>
                      {moveOptions.map((o) => (
                        <SelectItem key={o.value} value={o.value}>
                          {o.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              )}
            </div>
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel disabled={busy}>Cancel</AlertDialogCancel>
          {subCategories === 0 && (
            <Button variant="destructive" onClick={handleConfirm} disabled={busy || confirming}>
              {(busy || confirming) && <Loader2 className="mr-1 h-4 w-4 animate-spin" />}
              {moveDisabled ? 'Delete Anyway' : 'Move & Delete'}
            </Button>
          )}
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  )
}

export default UsageAlertDialog
