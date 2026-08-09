import { useEffect, useMemo, useRef, useState } from 'react'
import { useEditor, EditorContent } from '@tiptap/react'
import { Node } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import TextAlign from '@tiptap/extension-text-align'
import TiptapImage from '@tiptap/extension-image'
import { TableKit } from '@tiptap/extension-table'
import {
  AlignCenter,
  AlignJustify,
  AlignLeft,
  AlignRight,
  Bold,
  Code,
  Columns3,
  Eye,
  Heading1,
  Heading2,
  Heading3,
  Image as ImageIcon,
  ImagePlus,
  Italic,
  Link2,
  Link2Off,
  List,
  ListOrdered,
  Loader2,
  Minus,
  PenLine,
  Quote,
  Redo2,
  Rows3,
  Square,
  Strikethrough,
  Table,
  Table2,
  Underline as UnderlineIcon,
  Undo2,
  Video as VideoIcon,
  X,
} from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { cn } from '@/lib/utils'
import { useUploadMediaMutation } from '@/store/services/adminProductsApi'

interface RichTextEditorProps {
  value?: string
  onChange: (value: string) => void
  placeholder?: string
  minHeight?: number
  maxHeight?: number
}

// ---------- Custom iframe video node ----------
const Video = Node.create({
  name: 'video',
  group: 'block',
  atom: true,
  draggable: true,

  addAttributes() {
    return {
      src: { default: null },
    }
  },

  parseHTML() {
    return [{ tag: 'iframe[src]' }]
  },

  renderHTML({ HTMLAttributes }) {
    return ['div', { class: 'video-embed' }, ['iframe', { ...HTMLAttributes, frameborder: '0', allowfullscreen: 'true' }]]
  },

  addCommands() {
    return {
      setVideo:
        (options: { src: string }) =>
        ({ commands }) =>
          commands.insertContent({ type: this.name, attrs: options }),
    }
  },
})

declare module '@tiptap/core' {
  interface Commands<ReturnType> {
    video: {
      setVideo: (options: { src: string }) => ReturnType
    }
  }
}

// ---------- Toolbar ----------
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

const sanitizeHtml = (html: string): string =>
  html
    .replace(/<script[\s\S]*?<\/script>/gi, '')
    .replace(/\son\w+\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi, '')

const RichTextEditor = ({ value, onChange, placeholder, minHeight = 200, maxHeight = 520 }: RichTextEditorProps) => {
  const [uploadMedia, { isLoading: uploadingImage }] = useUploadMediaMutation()
  const [preview, setPreview] = useState(false)
  const fileInputRef = useRef<HTMLInputElement>(null)
  const previewHtml = useMemo(() => sanitizeHtml(value || ''), [value])

  const editor = useEditor({
    extensions: [
      StarterKit,
      Underline,
      TextAlign.configure({ types: ['heading', 'paragraph'] }),
      Link.configure({ openOnClick: false, autolink: true }),
      Placeholder.configure({ placeholder: placeholder || 'Write a detailed description...' }),
      TiptapImage.configure({ inline: false, allowBase64: true }),
      TableKit,
      Video,
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
    editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
  }

  const handleImageFiles = async (files: FileList | File[] | null) => {
    const list = files ? Array.from(files).filter((f) => f.type.startsWith('image/')) : []
    if (list.length === 0) return
    try {
      const assets = await uploadMedia({ files: list, folder: 'products/editor' }).unwrap()
      assets.forEach((asset) => editor.chain().focus().setImage({ src: asset.url }).run())
      toast.success(`${assets.length} image(s) inserted`)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Image upload failed')
    }
  }

  const handleImageUrl = () => {
    const url = window.prompt('Enter image URL')
    if (url) editor.chain().focus().setImage({ src: url }).run()
  }

  const handleVideo = () => {
    const url = window.prompt('Enter video URL (YouTube / Vimeo / direct mp4)')
    if (!url) return
    const match = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w-]+)/)
    const vimeo = url.match(/vimeo\.com\/(\d+)/)
    let embed = url
    if (match) embed = `https://www.youtube.com/embed/${match[1]}`
    else if (vimeo) embed = `https://player.vimeo.com/video/${vimeo[1]}`
    editor.chain().focus().setVideo({ src: embed }).run()
  }

  const inTable = editor.isActive('table')

  return (
    <div className="overflow-hidden rounded-lg border">
      {/* Sticky toolbar */}
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
        <ToolbarButton title="Inline code" active={editor.isActive('code')} onClick={() => editor.chain().focus().toggleCode().run()}>
          <Code className="h-4 w-4" />
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
        <ToolbarButton title="Blockquote" active={editor.isActive('blockquote')} onClick={() => editor.chain().focus().toggleBlockquote().run()}>
          <Quote className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Code block" active={editor.isActive('codeBlock')} onClick={() => editor.chain().focus().toggleCodeBlock().run()}>
          <Square className="h-4 w-4" />
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

        <ToolbarButton title="Insert link" active={editor.isActive('link')} onClick={setLink}>
          <Link2 className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Remove link" disabled={!editor.isActive('link')} onClick={() => editor.chain().focus().extendMarkRange('link').unsetLink().run()}>
          <Link2Off className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Upload image" disabled={uploadingImage} onClick={() => fileInputRef.current?.click()}>
          {uploadingImage ? <Loader2 className="h-4 w-4 animate-spin" /> : <ImagePlus className="h-4 w-4" />}
        </ToolbarButton>
        <ToolbarButton title="Insert image by URL" onClick={handleImageUrl}>
          <ImageIcon className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton title="Embed video" onClick={handleVideo}>
          <VideoIcon className="h-4 w-4" />
        </ToolbarButton>

        <Separator orientation="vertical" className="mx-1 h-6" />

        <ToolbarButton title="Insert table" active={inTable} onClick={() => (inTable ? editor.chain().focus().deleteTable().run() : editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run())}>
          <Table2 className="h-4 w-4" />
        </ToolbarButton>
        {inTable && (
          <>
            <ToolbarButton title="Add column before" onClick={() => editor.chain().focus().addColumnBefore().run()}>
              <Columns3 className="h-4 w-4" />
            </ToolbarButton>
            <ToolbarButton title="Add column after" onClick={() => editor.chain().focus().addColumnAfter().run()}>
              <Columns3 className="h-4 w-4" />
            </ToolbarButton>
            <ToolbarButton title="Add row above" onClick={() => editor.chain().focus().addRowBefore().run()}>
              <Rows3 className="h-4 w-4" />
            </ToolbarButton>
            <ToolbarButton title="Add row below" onClick={() => editor.chain().focus().addRowAfter().run()}>
              <Rows3 className="h-4 w-4" />
            </ToolbarButton>
            <ToolbarButton title="Delete row" onClick={() => editor.chain().focus().deleteRow().run()}>
              <X className="h-4 w-4" />
            </ToolbarButton>
            <ToolbarButton title="Delete column" onClick={() => editor.chain().focus().deleteColumn().run()}>
              <X className="h-4 w-4" />
            </ToolbarButton>
            <ToolbarButton title="Delete table" onClick={() => editor.chain().focus().deleteTable().run()}>
              <Table className="h-4 w-4" />
            </ToolbarButton>
          </>
        )}

        <Separator orientation="vertical" className="mx-1 h-6" />

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
          accept="image/*"
          multiple
          className="hidden"
          onChange={(e) => {
            handleImageFiles(e.target.files)
            e.target.value = ''
          }}
        />
      </div>

      {/* Editable content with internal scroll — no viewport overflow */}
      {preview ? (
        <div
          className="rich-text-editor rich-text-preview"
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
