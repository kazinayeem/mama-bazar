import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const UPLOAD_FOLDER = 'products/descriptions';

function normalizeHtml(html) {
    if (!html || html === '<p><br></p>' || html === '<p></p>') return '';
    return html;
}

function isLocalStorageImage(src) {
    if (!src) return false;
    try {
        const url = new URL(src, window.location.origin);
        return url.origin === window.location.origin
            && (url.pathname.startsWith('/storage/') || url.pathname.startsWith('/uploads/'));
    } catch {
        return false;
    }
}

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
            // Continue through nested Alpine scopes.
        }
        node = node.parentElement;
    }
    return null;
}

export function registerRichEditor(Alpine) {
    Alpine.data('productRichEditor', (config = {}) => ({
        editor: null,
        sourceMode: false,
        uploading: false,
        colors: [],
        highlights: [],
        descriptionHtml: normalizeHtml(config.initial || ''),
        uploadUrl: config.uploadUrl || '',
        csrfToken: config.csrfToken || '',
        _parentForm: null,

        init() {
            this._parentForm = resolveParentForm(this.$el);
            if (this._parentForm?.description && !this.descriptionHtml) {
                this.descriptionHtml = normalizeHtml(this._parentForm.description);
            }
            this.$nextTick(() => this.mountEditor());
            return () => this.destroy();
        },

        mountEditor() {
            if (!this.$refs.editorMount) return;

            const toolbarOptions = [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ color: [] }, { background: [] }],
                [{ align: [] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ indent: '-1' }, { indent: '+1' }],
                ['blockquote', 'link', 'image', 'video'],
                ['clean'],
            ];

            this.editor = new Quill(this.$refs.editorMount, {
                theme: 'snow',
                placeholder: 'Write a detailed product description...',
                modules: {
                    toolbar: {
                        container: toolbarOptions,
                        handlers: {
                            image: () => this.selectImage(),
                        },
                    },
                },
                formats: [
                    'header', 'bold', 'italic', 'underline', 'strike', 'color', 'background',
                    'align', 'list', 'indent', 'blockquote', 'link', 'image', 'video',
                ],
            });

            if (this.descriptionHtml) {
                this.editor.clipboard.dangerouslyPasteHTML(this.descriptionHtml, 'silent');
            }
            this.syncToForm();
            this.editor.on('text-change', () => this.syncToForm());
        },

        syncToForm() {
            if (!this.editor) return;
            const value = normalizeHtml(this.editor.root.innerHTML);
            this.descriptionHtml = value;
            if (this.$refs.descriptionInput) {
                this.$refs.descriptionInput.value = value;
            }
            if (!this._parentForm) {
                this._parentForm = resolveParentForm(this.$el);
            }
            if (this._parentForm) {
                this._parentForm.description = value;
            }
        },

        selectImage() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = IMAGE_TYPES.join(',');
            input.addEventListener('change', () => {
                const file = input.files?.[0];
                if (file) this.uploadImage(file);
            }, { once: true });
            input.click();
        },

        async uploadImage(file) {
            if (!this.uploadUrl) {
                alert('Upload URL is not configured.');
                return;
            }
            if (!IMAGE_TYPES.includes(file.type)) {
                alert('Only JPG, PNG, or WebP images are allowed.');
                return;
            }

            this.uploading = true;
            try {
                const formData = new FormData();
                formData.append('file', file, file.name);
                formData.append('alt', file.name.replace(/\.[^.]+$/, ''));
                formData.append('folder', UPLOAD_FOLDER);
                formData.append('_token', this.csrfToken);

                const response = await fetch(this.uploadUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.url || !isLocalStorageImage(data.url)) {
                    throw new Error(data.message || 'Image upload failed.');
                }

                const range = this.editor.getSelection(true);
                this.editor.insertEmbed(range?.index || 0, 'image', data.url, 'user');
                this.editor.setSelection((range?.index || 0) + 1, 0, 'silent');
            } catch (error) {
                alert(error instanceof Error ? error.message : 'Image upload failed.');
            } finally {
                this.uploading = false;
            }
        },

        destroy() {
            if (this.editor) {
                this.editor.off('text-change');
                this.editor = null;
            }
        },

        isActive() {
            return false;
        },

        run() {},
        setLink() {},
        setColor() {},
        setHighlight() {},
        clearFormat() {},
        uploadImages() {},
        toggleSource() {},
        onSourceInput() {},
    }));
}
