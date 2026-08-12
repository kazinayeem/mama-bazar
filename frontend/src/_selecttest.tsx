import { useState } from 'react'
import { createRoot } from 'react-dom/client'
import { StrictMode } from 'react'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from './components/ui/select'
import { Dialog, DialogContent, DialogTrigger } from './components/ui/dialog'

function Basic() {
  const [v, setV] = useState<string | undefined>(undefined)
  return (
    <div>
      <p data-testid="basic-out">basic: {v ?? 'NONE'}</p>
      <Select value={v} onValueChange={setV}>
        <SelectTrigger data-testid="basic-trigger">
          <SelectValue placeholder="Pick one" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="a">Option A</SelectItem>
          <SelectItem value="b">Option B</SelectItem>
        </SelectContent>
      </Select>
    </div>
  )
}

function NumberMismatch() {
  const [v, setV] = useState<number | undefined>(undefined)
  return (
    <div>
      <p data-testid="num-out">num: {v ?? 'NONE'}</p>
      <Select value={String(v ?? '')} onValueChange={(x) => setV(Number(x))}>
        <SelectTrigger data-testid="num-trigger">
          <SelectValue placeholder="Pick num" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="1">One</SelectItem>
          <SelectItem value="2">Two</SelectItem>
        </SelectContent>
      </Select>
    </div>
  )
}

function InDialog() {
  const [v, setV] = useState<string | undefined>(undefined)
  return (
    <div>
      <Dialog>
        <DialogTrigger data-testid="open-dialog">Open dialog</DialogTrigger>
        <DialogContent>
          <p data-testid="dialog-out">dialog: {v ?? 'NONE'}</p>
          <Select value={v} onValueChange={setV}>
            <SelectTrigger data-testid="dialog-trigger">
              <SelectValue placeholder="Pick in dialog" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="x">Dialog Option X</SelectItem>
              <SelectItem value="y">Dialog Option Y</SelectItem>
            </SelectContent>
          </Select>
        </DialogContent>
      </Dialog>
    </div>
  )
}

export default function SelectTest() {
  return (
    <div style={{ padding: 40 }}>
      <Basic />
      <NumberMismatch />
      <InDialog />
    </div>
  )
}

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <SelectTest />
  </StrictMode>,
)
