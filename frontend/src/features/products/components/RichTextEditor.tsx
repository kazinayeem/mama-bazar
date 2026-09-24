import { useEffect, useMemo, useRef, useState } from 'react'
import { useEditor, EditorContent } from '@tiptap/react'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import TextAlign from '@tiptap/extension-text-align'
import TiptapImage from '@tiptap/extension-image'
import { TableKit } from '@tiptap/extension-table'
import { TextStyle } from '@tiptap/extension-text-style'
import { Color } from '@tiptap/extension-color'
import Highlight from '@tiptap/extension-highlight'
import {
  AlignCenter,
  AlignJustify,
  AlignLeft,
  AlignRight,
  Bold,
  Columns3,
  Eraser,
  Eye,
  Heading1,
  Heading2,
  Heading3,
  Highlighter,
  ImagePlus,
  ListIndentIncrease,
  Italic,
  Link2,
  Link2Off,
  List,
  ListOrdered,
  Loader2,
  Minus,
  ListIndentDecrease,
  Palette,
  PenLine,
  Quote,
  Redo2,
  Rows3,
  Strikethrough,
  Table,
  Table2,
  Underline as UnderlineIcon,
  Undo2,
  X,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { cn } from '@/lib/utils'
import { sanitizeProductHtml } from '@/lib/sanitizeHtml'
import { useUploadMediaMutation } from '@/store/services/adminProductsApi'

interface RichTextEditorProps {
  value?: string
  onChange: (value: string) => void
  placeholder?: string
  minHeight?: number
  maxHeight?: number
}

const TEXT_COLORS = [
  { label: 'Default', value: '' },
  { label: 'Slate', value: '#0f172a' },
  { label: 'Green', value: '#0F4D2C' },
  { label: 'Orange', value: '#F47B20' },
  { label: 'Red', value: '#dc2626' },
  { label: 'Blue', value: '#2563eb' },
]

const HIGHLIGHT_COLORS = [
  { label: 'None', value: '' },
  { label: 'Yellow', value: '#fef08a' },
  { label: 'Green', value: '#bbf7d0' },
  { label: 'Orange', value: '#fed7aa' },
  { label: 'Blue', value: '#bfdbfe' },
]

const isSafeHttpUrl = (url: string) => {
  try {
    const u = new URL(url, window.location.origin)
    return u.protocol === 'http:' || u.protocol === 'https:'
  } catch {
    return false
  }
}

const isLocalStorageImage = (src: string) => {
  if (src.startsWith('/storage/') || src.startsWith('/uploads/')) return true
  try {
    const u = new URL(src, window.location.origin)
    return (
      u.origin === window.location.origin &&
      (u.pathname.startsWith('/storage/') || u.pathname.startsWith('/uploads/'))
    )
  } catch {
    return false
  }
}

const ToolbarButton = ({
  active,
  onClick,
  disabled,
  children,
  title,
}: {
  active?: boolean
  onClick: () => void
  disabled?: boolean
  children: React.ReactNode
  title: string
}) => (
  <Button
    type="button"
    variant="ghost"
    size="icon"
    title={title}
    disabled={disabled}
    onClick={onClick}
    className={cn('h-8 w-8 shrink-0', active && 'bg-accent text-accent-foreground')}
  >
    {children}
  </Button>
)

const RichTextEditor = ({ value, onChange, placeholder, minHeight = 200, maxHeight = 520 }: RichTextEditorProps) => {
  const [uploadMedia, { isLoading: uploadingImage }] = useUploadMediaMutation()
  const [preview, setPreview] = useState(false)
  const fileInputRef = useRef<HTMLInputElement>(null)
  const previewHtml = useMemo(() => sanitizeProductHtml(value || ''), [value])

  const editor = useEditor({
    extensions: [
      StarterKit.configure({ heading: { levels: [1, 2, 3] } }),
      Underline,
      TextStyle,
      Color,
      Highlight.configure({ multicolor: true }),
      TextAlign.configure({ types: ['heading', 'paragraph'] }),
      Link.configure({
        openOnClick: false,
        autolink: true,
        HTMLAttributes: { rel: 'noopener noreferrer', target: '_blank' },
        isAllowedUri: (url) => isSafeHttpUrl(url),
      }),
      Placeholder.configure({ placeholder: placeholder || 'Write a detailed description...' }),
      TiptapImage.configure({ inline: false, allowBase64: false }),
      TableKit,
    ],
    content: value || '',
    onUpdate: ({ editor: e }) => onChange(e.getHTML()),
    editorProps: {
      attributes: {
        class: 'tiptap',
        spellcheck: 'true',
      },
    },
  })

  useEffect(() => {
    if (!editor) return
    const raf = requestAnimationFrame(() => {
      if (value === undefined) return
      const current = editor.isEmpty ? '' : editor.getHTML()
      if (current !== value) editor.commands.setContent(value || '', { emitUpdate: false })
    })
    return () => cancelAnimationFrame(raf)
  }, [value, editor])

  if (!editor) return null

  const setLink = () => {
    const previousUrl = editor.getAttributes('link').href as string | undefined
    const url = window.prompt('Enter URL', previousUrl || 'https://')
    if (url === null) return
    if (url === '') {
      editor.chain().focus().extendMarkRange('link').unsetLink().run()
      return
    }
    if (!isSafeHttpUrl(url)) {
      toast.error('Only http(s) links are allowed')
      return
    }
    editor.chain().focus().extendMarkRange('link').setLink({
      href: url,
      target: '_blank',
      rel: 'noopener noreferrer',
    }).run()
  }

  const handleImageFiles = async (files: FileList | File[] | null) => {
    const list = files
      ? Array.from(files).filter((f) => ['image/jpeg', 'image/png', 'image/webp'].includes(f.type))
      : []
    if (list.length === 0) {
      toast.error('Only JPG, PNG, or WebP images are allowed')
      return
    }
    try {
      const assets = await uploadMedia({ files: list, folder: 'products/descriptions' }).unwrap()
      assets.forEach((asset) => {
        if (!isLocalStorageImage(asset.url)) {
          toast.error('Only local storage images are allowed')
          return
        }
        const alt = window.prompt('Image alt text', '') ?? ''
        editor.chain().focus().setImage({ src: asset.url, alt }).run()
      })
      toast.success(`${assets.length} image(s) inserted`)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Image upload failed')
    }
  }

  const inTable = editor.isActive('table')

  return (
    <div className="overflow-hidden rounded-lg border">
      <div className="sticky top-0 z-10 flex flex-wrap items-center gap-0.5 border-b bg-muted/50 p-1.5 backdrop-blur">
        <ToolbarButton title="Bold" active={editor.isActive('bold')} onClick={() => editor.chain().focus().toggleBold().run()}>
          <Bold className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Italic" active={editor.isActive('italic')} onClick={() => editor.chain().focus().toggleItalic().run()}>
          <Italic className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Underline" active={editor.isActive('underline')} onClick={() => editor.chain().focus().toggleUnderline().run()}>
          <UnderlineIcon className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Strikethrough" active={editor.isActive('strike')} onClick={() => editor.chain().focus().toggleStrike().run()}>
          <Strikethrough className="h-4 w-4" />
        </ToolbarButton>

        <Separator orientation="vertical" className="mx-1 h-6" />

        <ToolbarButton title="Heading 1" active={editor.isActive('heading', { level: 1 })} onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()}>
          <Heading1 className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Heading 2" active={editor.isActive('heading', { level: 2 })} onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}>
          <Heading2 className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Heading 3" active={editor.isActive('heading', { level: 3 })} onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}>
          <Heading3 className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Bullet list" active={editor.isActive('bulletList')} onClick={() => editor.chain().focus().toggleBulletList().run()}>
          <List className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Numbered list" active={editor.isActive('orderedList')} onClick={() => editor.chain().focus().toggleOrderedList().run()}>
          <ListOrdered className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Indent" onClick={() => editor.chain().focus().sinkListItem('listItem').run()}>
          <ListIndentIncrease className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Outdent" onClick={() => editor.chain().focus().liftListItem('listItem').run()}>
          <ListIndentDecrease className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Blockquote" active={editor.isActive('blockquote')} onClick={() => editor.chain().focus().toggleBlockquote().run()}>
          <Quote className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Horizontal rule" onClick={() => editor.chain().focus().setHorizontalRule().run()}>
          <Minus className="h-4 w-4" />
        </ToolbarButton>

        <Separator orientation="vertical" className="mx-1 h-6" />

        <ToolbarButton title="Align left" active={editor.isActive({ textAlign: 'left' })} onClick={() => editor.chain().focus().setTextAlign('left').run()}>
          <AlignLeft className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Align center" active={editor.isActive({ textAlign: 'center' })} onClick={() => editor.chain().focus().setTextAlign('center').run()}>
          <AlignCenter className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Align right" active={editor.isActive({ textAlign: 'right' })} onClick={() => editor.chain().focus().setTextAlign('right').run()}>
          <AlignRight className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Align justify" active={editor.isActive({ textAlign: 'justify' })} onClick={() => editor.chain().focus().setTextAlign('justify').run()}>
          <AlignJustify className="h-4 w-4" />
        </ToolbarButton>

        <Separator orientation="vertical" className="mx-1 h-6" />

        <div className="relative inline-flex">
          <ToolbarButton title="Text color" onClick={() => {}}>
            <Palette className="h-4 w-4" />
          </ToolbarButton>
          <select
            aria-label="Text color"
            className="absolute inset-0 cursor-pointer opacity-0"
            defaultValue=""
            onChange={(e) => {
              const v = e.target.value
              if (!v) editor.chain().focus().unsetColor().run()
              else editor.chain().focus().setColor(v).run()
              e.target.value = ''
            }}
          >
            {TEXT_COLORS.map((c) => (
              <option key={c.label} value={c.value}>{c.label}</option>
            ))}
          </select>
        </div>
        <div className="relative inline-flex">
          <ToolbarButton title="Highlight" onClick={() => {}}>
            <Highlighter className="h-4 w-4" />
          </ToolbarButton>
          <select
            aria-label="Highlight color"
            className="absolute inset-0 cursor-pointer opacity-0"
            defaultValue=""
            onChange={(e) => {
              const v = e.target.value
              if (!v) editor.chain().focus().unsetHighlight().run()
              else editor.chain().focus().toggleHighlight({ color: v }).run()
              e.target.value = ''
            }}
          >
            {HIGHLIGHT_COLORS.map((c) => (
              <option key={c.label} value={c.value}>{c.label}</option>
            ))}
          </select>
        </div>

        <ToolbarButton title="Insert link" active={editor.isActive('link')} onClick={setLink}>
          <Link2 className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Remove link" disabled={!editor.isActive('link')} onClick={() => editor.chain().focus().extendMarkRange('link').unsetLink().run()}>
          <Link2Off className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Upload image" disabled={uploadingImage} onClick={() => fileInputRef.current?.click()}>
          {uploadingImage ? <Loader2 className="h-4 w-4 animate-spin" /> : <ImagePlus className="h-4 w-4" />}
        </ToolbarButton>

        <Separator orientation="vertical" className="mx-1 h-6" />

        <ToolbarButton title="Insert table" active={inTable} onClick={() => (inTable ? editor.chain().focus().deleteTable().run() : editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run())}>
          <Table2 className="h-4 w-4" />
        </ToolbarButton>
        {inTable && (
          <>
            <ToolbarButton title="Add column after" onClick={() => editor.chain().focus().addColumnAfter().run()}>
              <Columns3 className="h-4 w-4" />
            </ToolbarButton>
            <ToolbarButton title="Add row below" onClick={() => editor.chain().focus().addRowAfter().run()}>
              <Rows3 className="h-4 w-4" />
            </ToolbarButton>
            <ToolbarButton title="Delete row" onClick={() => editor.chain().focus().deleteRow().run()}>
              <X className="h-4 w-4" />
            </ToolbarButton>
            <ToolbarButton title="Delete table" onClick={() => editor.chain().focus().deleteTable().run()}>
              <Table className="h-4 w-4" />
            </ToolbarButton>
          </>
        )}

        <Separator orientation="vertical" className="mx-1 h-6" />

        <ToolbarButton title="Clear formatting" onClick={() => editor.chain().focus().clearNodes().unsetAllMarks().run()}>
          <Eraser className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Undo" disabled={!editor.can().undo()} onClick={() => editor.chain().focus().undo().run()}>
          <Undo2 className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Redo" disabled={!editor.can().redo()} onClick={() => editor.chain().focus().redo().run()}>
          <Redo2 className="h-4 w-4" />
        </ToolbarButton>

        <Button
          type="button"
          variant={preview ? 'default' : 'outline'}
          size="sm"
          className="ml-auto h-8 px-3 text-xs"
          onClick={() => setPreview((v) => !v)}
        >
          {preview ? <PenLine className="mr-1 h-3.5 w-3.5" /> : <Eye className="mr-1 h-3.5 w-3.5" />}
          {preview ? 'Edit' : 'Preview'}
        </Button>

        <input
          ref={fileInputRef}
          type="file"
          accept="image/jpeg,image/png,image/webp"
          multiple
          className="hidden"
          onChange={(e) => {
            handleImageFiles(e.target.files)
            e.target.value = ''
          }}
        />
      </div>

      {preview ? (
        <div
          className="product-description rich-text-editor rich-text-preview overflow-x-auto px-3 py-2"
          style={{ minHeight, maxHeight }}
          dangerouslySetInnerHTML={{ __html: previewHtml }}
        />
      ) : (
        <div className="rich-text-editor overflow-y-auto" style={{ minHeight, maxHeight }}>
          <EditorContent editor={editor} />
        </div>
      )}
    </div>
  )
}

export default RichTextEditor
