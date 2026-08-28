import { Bot, MessageCircle, MessageSquareMore, Phone, RotateCcw, Send, Sparkles, X } from 'lucide-react'
import { type FormEvent, type KeyboardEvent, useEffect, useRef, useState } from 'react'
import { buildWhatsAppUrl } from '../../lib/whatsapp'

const MESSENGER_URL = 'https://www.facebook.com/profile.php?id=61593199792337'

const AI_GREETING = 'আসসালামু আলাইকুম! Mama Bazar-এ আপনাকে স্বাগতম। কীভাবে সাহায্য করতে পারি?'

const STARTER_OPTIONS = [
  { id: '1', label: 'কী কী পণ্য আছে?', action: 'send' },
  { id: '2', label: 'আমার জন্য একটি পণ্য খুঁজে দিন', action: 'send' },
  { id: '3', label: 'চ্যাট শুরু করুন', action: 'greet' },
] as const

interface ChatMessage {
  id: string
  sender: 'user' | 'ai'
  text: string
  timestamp: string
  isError?: boolean
}

const WhatsAppButton = () => {
  const [chatOpen, setChatOpen] = useState(false)
  const [messages, setMessages] = useState<ChatMessage[]>([])
  const [inputValue, setInputValue] = useState('')
  const [isLoading, setIsLoading] = useState(false)
  const [hasStartedChat, setHasStartedChat] = useState(false)

  const messagesEndRef = useRef<HTMLDivElement>(null)
  const inputRef = useRef<HTMLInputElement>(null)

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
  }

  useEffect(() => {
    if (chatOpen) {
      scrollToBottom()
      if (hasStartedChat) {
        inputRef.current?.focus()
      }
    }
  }, [chatOpen, messages, isLoading, hasStartedChat])

  const formatTime = () => {
    return new Date().toLocaleTimeString('bn-BD', {
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  const sendToApi = async (userText: string) => {
    setIsLoading(true)

    try {
      const response = await fetch('/api/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ message: userText }),
      })

      if (!response.ok) {
        throw new Error(`Server returned ${response.status}`)
      }

      const data = await response.json()
      const aiReply = data.reply || 'দুঃখিত, কোনো উত্তর পাওয়া যায়নি।'

      const aiMessage: ChatMessage = {
        id: `ai-${Date.now()}`,
        sender: 'ai',
        text: aiReply,
        timestamp: formatTime(),
      }

      setMessages((prev) => [...prev, aiMessage])
    } catch {
      const errorMessage: ChatMessage = {
        id: `err-${Date.now()}`,
        sender: 'ai',
        text: 'দুঃখিত, বর্তমানে এআই সার্ভারের সাথে সংযোগ স্থাপন করা যাচ্ছে না। অনুগ্রহ করে কিছুক্ষণ পর আবার চেষ্টা করুন।',
        timestamp: formatTime(),
        isError: true,
      }
      setMessages((prev) => [...prev, errorMessage])
    } finally {
      setIsLoading(false)
    }
  }

  const handleSendMessage = async (textToSend?: string) => {
    const text = (textToSend ?? inputValue).trim()
    if (!text || isLoading) return

    setHasStartedChat(true)

    const userMessage: ChatMessage = {
      id: `user-${Date.now()}`,
      sender: 'user',
      text,
      timestamp: formatTime(),
    }

    setMessages((prev) => [...prev, userMessage])
    if (!textToSend) {
      setInputValue('')
    }

    await sendToApi(text)
  }

  const handleStarterClick = async (option: typeof STARTER_OPTIONS[number]) => {
    setHasStartedChat(true)

    if (option.action === 'greet') {
      const greetingMessage: ChatMessage = {
        id: `greeting-${Date.now()}`,
        sender: 'ai',
        text: AI_GREETING,
        timestamp: formatTime(),
      }
      setMessages((prev) => [...prev, greetingMessage])
      setTimeout(() => inputRef.current?.focus(), 100)
    } else {
      await handleSendMessage(option.label)
    }
  }

  const handleKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault()
      handleSendMessage()
    }
  }

  const handleFormSubmit = (e: FormEvent) => {
    e.preventDefault()
    handleSendMessage()
  }

  const handleResetChat = () => {
    setMessages([])
    setHasStartedChat(false)
    setInputValue('')
  }

  return (
    <div className="fixed bottom-[calc(env(safe-area-inset-bottom)+80px)] right-4 z-40 flex flex-col items-end gap-3 md:bottom-5 md:right-5">
      {chatOpen && (
        <div
          role="dialog"
          aria-label="Mama Bazar Chat Window"
          className="flex h-[500px] max-h-[75vh] w-[calc(100vw-2rem)] max-w-[360px] sm:w-[380px] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl transition-all duration-200 ease-out"
        >
          {/* Header */}
          <div className="flex items-center justify-between bg-brand-green-500 px-4 py-3 text-white shadow-sm">
            <div className="flex items-center gap-2.5">
              <div className="relative flex h-9 w-9 items-center justify-center rounded-full bg-white/15 backdrop-blur-sm">
                <Bot size={20} className="text-white" />
                <span className="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-brand-green-500" />
              </div>
              <div className="flex flex-col">
                <div className="flex items-center gap-1.5">
                  <span className="text-sm font-bold tracking-tight">Mama Bazar Support</span>
                  <span className="inline-flex items-center gap-0.5 rounded-full bg-white/20 px-1.5 py-0.2 text-[10px] font-medium text-emerald-100">
                    <Sparkles size={10} /> AI
                  </span>
                </div>
                <span className="text-[11px] text-emerald-100">অনলাইন • তাৎক্ষণিক উত্তর</span>
              </div>
            </div>

            <div className="flex items-center gap-1">
              {/* Quick direct contact links */}
              <a
                href={MESSENGER_URL}
                target="_blank"
                rel="noopener noreferrer"
                title="Open Messenger"
                className="flex h-8 w-8 items-center justify-center rounded-full text-white/80 transition hover:bg-white/15 hover:text-white"
              >
                <MessageCircle size={16} />
              </a>
              <a
                href={buildWhatsAppUrl()}
                target="_blank"
                rel="noopener noreferrer"
                title="Open WhatsApp"
                className="flex h-8 w-8 items-center justify-center rounded-full text-white/80 transition hover:bg-white/15 hover:text-white"
              >
                <Phone size={16} />
              </a>
              {hasStartedChat && (
                <button
                  type="button"
                  onClick={handleResetChat}
                  title="Reset conversation"
                  aria-label="Reset conversation"
                  className="flex h-8 w-8 items-center justify-center rounded-full text-white/80 transition hover:bg-white/15 hover:text-white"
                >
                  <RotateCcw size={15} />
                </button>
              )}
              <button
                type="button"
                onClick={() => setChatOpen(false)}
                title="Close chat"
                aria-label="Close chat"
                className="flex h-8 w-8 items-center justify-center rounded-full text-white/80 transition hover:bg-white/15 hover:text-white"
              >
                <X size={18} />
              </button>
            </div>
          </div>

          {/* Chat Body */}
          <div className="flex-1 overflow-y-auto bg-slate-50/70 p-3.5 space-y-3">
            {/* Initial Starter Screen if conversation not yet started */}
            {!hasStartedChat && messages.length === 0 ? (
              <div className="flex flex-col h-full justify-between py-2">
                <div className="space-y-3">
                  <div className="rounded-xl border border-brand-green-100 bg-brand-green-50/60 p-3.5 text-center">
                    <div className="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-brand-green-100 text-brand-green-600">
                      <Sparkles size={20} />
                    </div>
                    <h4 className="text-sm font-semibold text-slate-800">
                      স্বাগতম! Mama Bazar-এ আপনাকে সাহায্য করতে আমরা প্রস্তুত।
                    </h4>
                    <p className="mt-1 text-xs text-slate-600">
                      নিচের যেকোনো একটি অপশনে ক্লিক করুন অথবা সরাসরি চ্যাট শুরু করুন:
                    </p>
                  </div>

                  <div className="space-y-2 pt-1">
                    {STARTER_OPTIONS.map((opt, idx) => (
                      <button
                        key={opt.id}
                        type="button"
                        onClick={() => handleStarterClick(opt)}
                        className="group flex w-full items-center justify-between rounded-xl border border-slate-200/90 bg-white p-3 text-left shadow-xs transition hover:border-brand-green-500 hover:bg-brand-green-50/40 hover:shadow-sm active:scale-[0.99]"
                      >
                        <div className="flex items-center gap-2.5">
                          <span className="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600 group-hover:bg-brand-green-500 group-hover:text-white transition">
                            {idx + 1}
                          </span>
                          <span className="text-xs sm:text-sm font-medium text-slate-800 group-hover:text-brand-green-700">
                            {opt.label}
                          </span>
                        </div>
                        <Sparkles size={14} className="text-slate-400 group-hover:text-brand-green-600 transition" />
                      </button>
                    ))}
                  </div>
                </div>

                <div className="mt-4 rounded-lg bg-white p-2.5 text-center border border-slate-200/60">
                  <p className="text-[11px] text-slate-500">
                    দ্রুত যোগাযোগের জন্য আমাদের{' '}
                    <a
                      href={buildWhatsAppUrl()}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="font-medium text-brand-green-600 hover:underline"
                    >
                      WhatsApp
                    </a>{' '}
                    অথবা{' '}
                    <a
                      href={MESSENGER_URL}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="font-medium text-blue-600 hover:underline"
                    >
                      Messenger
                    </a>
                    -এ নক করতে পারেন।
                  </p>
                </div>
              </div>
            ) : (
              /* Conversation Messages */
              <>
                {messages.map((msg) => (
                  <div
                    key={msg.id}
                    className={`flex flex-col ${
                      msg.sender === 'user' ? 'items-end' : 'items-start'
                    }`}
                  >
                    <div
                      className={`max-w-[85%] rounded-2xl px-3.5 py-2.5 text-xs sm:text-sm shadow-xs break-words ${
                        msg.sender === 'user'
                          ? 'bg-brand-green-500 text-white rounded-tr-xs'
                          : msg.isError
                          ? 'bg-red-50 text-red-700 border border-red-200 rounded-tl-xs'
                          : 'bg-white text-slate-800 border border-slate-200/80 rounded-tl-xs'
                      }`}
                    >
                      <p className="whitespace-pre-wrap leading-relaxed">{msg.text}</p>
                    </div>
                    <span className="mt-1 px-1 text-[10px] text-slate-400">
                      {msg.timestamp}
                    </span>
                  </div>
                ))}

                {/* Loading / Typing Indicator */}
                {isLoading && (
                  <div className="flex flex-col items-start">
                    <div className="flex items-center gap-1.5 rounded-2xl rounded-tl-xs border border-slate-200/80 bg-white px-3.5 py-2.5 shadow-xs">
                      <span className="h-2 w-2 animate-bounce rounded-full bg-brand-green-500 [animation-delay:-0.3s]" />
                      <span className="h-2 w-2 animate-bounce rounded-full bg-brand-green-500 [animation-delay:-0.15s]" />
                      <span className="h-2 w-2 animate-bounce rounded-full bg-brand-green-500" />
                      <span className="ml-1 text-[11px] text-slate-500">উত্তর তৈরি হচ্ছে...</span>
                    </div>
                  </div>
                )}

                <div ref={messagesEndRef} />
              </>
            )}
          </div>

          {/* Input Area */}
          <form
            onSubmit={handleFormSubmit}
            className="flex items-center gap-2 border-t border-slate-200 bg-white p-2.5"
          >
            <input
              ref={inputRef}
              type="text"
              value={inputValue}
              onChange={(e) => setInputValue(e.target.value)}
              onKeyDown={handleKeyDown}
              placeholder="মেসেজ লিখুন..."
              disabled={isLoading}
              className="flex-1 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs sm:text-sm text-slate-800 placeholder-slate-400 transition focus:border-brand-green-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-green-500/20 disabled:bg-slate-100 disabled:opacity-60"
            />
            <button
              type="submit"
              disabled={!inputValue.trim() || isLoading}
              aria-label="Send message"
              className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-green-500 text-white shadow-sm transition hover:bg-brand-green-600 active:scale-95 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
            >
              <Send size={15} />
            </button>
          </form>
        </div>
      )}

      {/* Floating Trigger Button */}
      <button
        aria-expanded={chatOpen}
        aria-label="Open chat"
        className="flex h-12 w-12 items-center justify-center rounded-full bg-brand-green-500 text-white shadow-lg shadow-brand-green-500/25 transition hover:bg-brand-green-600 active:scale-95"
        onClick={() => setChatOpen((prev) => !prev)}
        type="button"
      >
        {chatOpen ? <MessageSquareMore size={22} /> : <MessageCircle size={22} />}
      </button>
    </div>
  )
}

export default WhatsAppButton