/**
 * MamaBazar product description rich text editor (Tiptap + Alpine).
 * Toolbar styled to match admin slate design system.
 */
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import TextAlign from '@tiptap/extension-text-align';
import Placeholder from '@tiptap/extension-placeholder';
import { TextStyle } from '@tiptap/extension-text-style';
import { Color } from '@tiptap/extension-color';
import Highlight from '@tiptap/extension-highlight';
import { TableKit } from '@tiptap/extension-table';

const COLORS = [
    { label: 'Default', value: '' },
    { label: 'Slate', value: '#0f172a' },
    { label: 'Green', value: '#0F4D2C' },
    { label: 'Orange', value: '#F47B20' },
    { label: 'Red', value: '#dc2626' },
    { label: 'Blue', value: '#2563eb' },
];

const HIGHLIGHTS = [
    { label: 'None', value: '' },
    { label: 'Yellow', value: '#fef08a' },
    { label: 'Green', value: '#bbf7d0' },
    { label: 'Orange', value: '#fed7aa' },
    { label: 'Blue', value: '#bfdbfe' },
];

const UPLOAD_FOLDER = 'products/descriptions';

function isSafeHttpUrl(url) {
    if (!url) return false;
    try {
        const u = new URL(url, window.location.origin);
        return u.protocol === 'http:' || u.protocol === 'https:';
    } catch {
        return false;
    }
}

function isLocalStorageImage(src) {
    if (!src) return false;
    if (src.startsWith('/storage/') || src.startsWith('/uploads/')) return true;
    try {
        const u = new URL(src, window.location.origin);
        return u.origin === window.location.origin
            && (u.pathname.startsWith('/storage/') || u.pathname.startsWith('/uploads/'));
    } catch {
        return false;
    }
}

function normalizeHtml(html) {
    if (!html || html === '<p></p>') return '';
    return html;
}

/**
 * Nested Alpine scopes: `this.form` is undefined on the child.
 * Walk the DOM for the parent productForm's `form` object.
 */
function resolveParentForm(el) {
    if (!el || !window.Alpine) return null;
    let node = el.parentElement;
    while (node) {
        try {
            if (node._x_dataStack) {
                const data = window.Alpine.$data(node);
                if (data?.form && Object.prototype.hasOwnProperty.call(data.form, 'description')) {
                    return data.form;
                }
            }
        } catch {
            // continue walking
        }
        node = node.parentElement;
    }
    return null;
}

export function registerRichEditor(Alpine) {
    Alpine.data('productRichEditor', (config = {}) => ({
        editor: null,
        sourceMode: false,
        sourceHtml: '',
        /** Local mirror so the hidden input always posts the latest HTML. */
        descriptionHtml: normalizeHtml(config.initial || ''),
        uploadUrl: config.uploadUrl || '',
        csrfToken: config.csrfToken || '',
        uploading: false,
        colors: COLORS,
        highlights: HIGHLIGHTS,
        _parentForm: null,

        init() {
            this._parentForm = resolveParentForm(this.$el);
            if (this._parentForm && !this.descriptionHtml && this._parentForm.description) {
                this.descriptionHtml = normalizeHtml(this._parentForm.description);
            }

            this.$nextTick(() => this.mountEditor(this.descriptionHtml));

            this.$watch(
                () => this._parentForm?.description,
                (val) => {
                    if (!this.editor || this.sourceMode) return;
                    const incoming = normalizeHtml(val || '');
                    const current = normalizeHtml(this.editor.isEmpty ? '' : this.editor.getHTML());
                    if (incoming !== current) {
                        this.descriptionHtml = incoming;
                        this.editor.commands.setContent(incoming || '', { emitUpdate: false });
                    }
                }
            );

            // Alpine cleanup when the component is removed
            return () => this.destroy();
        },

        mountEditor(content) {
            if (this.editor) {
                this.editor.destroy();
                this.editor = null;
            }
            if (!this.$refs.editorMount) return;

            this.editor = new Editor({
                element: this.$refs.editorMount,
                extensions: [
                    StarterKit.configure({
                        heading: { levels: [1, 2, 3] },
                    }),
                    Underline,
                    TextStyle,
                    Color,
                    Highlight.configure({ multicolor: true }),
                    TextAlign.configure({ types: ['heading', 'paragraph'] }),
                    Link.configure({
                        openOnClick: false,
                        autolink: true,
                        HTMLAttributes: {
                            rel: 'noopener noreferrer',
                            target: '_blank',
                        },
                        isAllowedUri: (url) => isSafeHttpUrl(url),
                    }),
                    Placeholder.configure({
                        placeholder: 'Write a detailed product description…',
                    }),
                    Image.configure({
                        inline: false,
                        allowBase64: false,
                        HTMLAttributes: { class: 'product-desc-img' },
                    }),
                    TableKit.configure({
                        table: { resizable: false },
                    }),
                ],
                content: content || '',
                editorProps: {
                    attributes: {
                        class: 'mb-rte-content prose prose-sm max-w-none focus:outline-none min-h-[180px] px-3 py-2 text-xs text-slate-800',
                        spellcheck: 'true',
                    },
                },
                onUpdate: ({ editor }) => {
                    this.syncToForm(editor.getHTML());
                },
            });
        },

        syncToForm(html) {
            const value = normalizeHtml(html);
            this.descriptionHtml = value;

            if (!this._parentForm) {
                this._parentForm = resolveParentForm(this.$el);
            }
            if (this._parentForm) {
                this._parentForm.description = value;
            }
        },

        run(fn) {
            if (!this.editor || this.sourceMode) return;
            fn(this.editor);
        },

        isActive(name, attrs = {}) {
            return this.editor ? this.editor.isActive(name, attrs) : false;
        },

        setLink() {
            this.run((editor) => {
                const prev = editor.getAttributes('link').href || '';
                const url = window.prompt('Enter URL (https://…)', prev || 'https://');
                if (url === null) return;
                if (url === '') {
                    editor.chain().focus().extendMarkRange('link').unsetLink().run();
                    return;
                }
                if (!isSafeHttpUrl(url)) {
                    alert('Only http(s) links are allowed.');
                    return;
                }
                editor.chain().focus().extendMarkRange('link').setLink({
                    href: url,
                    target: '_blank',
                    rel: 'noopener noreferrer',
                }).run();
            });
        },

        setColor(value) {
            this.run((editor) => {
                if (!value) {
                    editor.chain().focus().unsetColor().run();
                } else {
                    editor.chain().focus().setColor(value).run();
                }
            });
        },

        setHighlight(value) {
            this.run((editor) => {
                if (!value) {
                    editor.chain().focus().unsetHighlight().run();
                } else {
                    editor.chain().focus().toggleHighlight({ color: value }).run();
                }
            });
        },

        clearFormat() {
            this.run((editor) => {
                editor.chain().focus().clearNodes().unsetAllMarks().run();
            });
        },

        async uploadImages(files) {
            const list = Array.from(files || []).filter((f) =>
                ['image/jpeg', 'image/png', 'image/webp'].includes(f.type)
            );
            if (!list.length) {
                alert('Only JPG, PNG, or WebP images are allowed.');
                return;
            }
            if (!this.uploadUrl) {
                alert('Upload URL is not configured.');
                return;
            }

            this.uploading = true;
            try {
                for (const file of list) {
                    const alt = window.prompt('Image alt text (accessibility)', file.name.replace(/\.[^.]+$/, '')) || '';
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('alt', alt);
                    formData.append('folder', UPLOAD_FOLDER);
                    formData.append('_token', this.csrfToken);

                    const res = await fetch(this.uploadUrl, {
                        method: 'POST',
                        body: formData,
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });
                    const data = await res.json();
                    if (!res.ok || !data.url) {
                        throw new Error(data.message || 'Upload failed');
                    }
                    if (!isLocalStorageImage(data.url)) {
                        throw new Error('Only local storage images are allowed');
                    }
                    this.run((editor) => {
                        editor.chain().focus().setImage({ src: data.url, alt: data.alt || alt }).run();
                    });
                }
            } catch (err) {
                alert(err instanceof Error ? err.message : 'Image upload failed');
            } finally {
                this.uploading = false;
                if (this.$refs.imageInput) this.$refs.imageInput.value = '';
            }
        },

        toggleSource() {
            if (!this.editor) return;
            if (!this.sourceMode) {
                this.sourceHtml = this.editor.getHTML();
                this.sourceMode = true;
                this.editor.setEditable(false);
            } else {
                this.sourceMode = false;
                this.editor.setEditable(true);
                this.editor.commands.setContent(this.sourceHtml || '', { emitUpdate: true });
                this.syncToForm(this.editor.getHTML());
            }
        },

        onSourceInput() {
            this.syncToForm(this.sourceHtml);
        },

        destroy() {
            if (this.editor) {
                this.editor.destroy();
                this.editor = null;
            }
        },
    }));
}
