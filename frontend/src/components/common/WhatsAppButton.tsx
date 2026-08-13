import { MessageCircle, MessageSquareMore, Phone } from 'lucide-react'
import { useState } from 'react'
import { buildWhatsAppUrl } from '../../lib/whatsapp'

const MESSENGER_URL = 'https://www.facebook.com/profile.php?id=61593199792337'

const WhatsAppButton = () => {
  const [contactOpen, setContactOpen] = useState(false)

  return (
    <div className="fixed bottom-5 right-5 z-40 flex flex-col items-end gap-3">
      {contactOpen && (
        <div className="flex flex-col items-end gap-2">
          <a
            className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-lg shadow-slate-900/5 transition hover:border-emerald-500 hover:text-emerald-600"
            href={MESSENGER_URL}
            rel="noopener noreferrer"
            target="_blank"
          >
            <MessageCircle size={16} className="text-blue-600" />
            Messenger
          </a>
          <a
            className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-lg shadow-slate-900/5 transition hover:border-emerald-500 hover:text-emerald-600"
            href={buildWhatsAppUrl()}
            rel="noopener noreferrer"
            target="_blank"
          >
            <Phone size={16} className="text-emerald-500" />
            WhatsApp
          </a>
        </div>
      )}

      <button
        aria-expanded={contactOpen}
        aria-label="Open contact options"
        className="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg shadow-primary/25 transition hover:bg-primary-700 active:scale-95"
        onClick={() => setContactOpen((prev) => !prev)}
        type="button"
      >
        {contactOpen ? <MessageSquareMore size={20} /> : <MessageCircle size={20} />}
      </button>
    </div>
  )
}

export default WhatsAppButton