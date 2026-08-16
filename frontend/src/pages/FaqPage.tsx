import { AnimatePresence, motion, useReducedMotion } from 'framer-motion'
import { ChevronDown, ChevronRight, Home, MessageCircle, Phone } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { SEO } from '../components/common/SEO'
import { EASE } from '../lib/motion'

interface FaqItem {
  q: string
  a: string
}

const FAQ_ITEMS: FaqItem[] = [
  {
    q: 'কীভাবে আমি অর্ডার করব? — How do I place an order?',
    a: 'যেকোনো পণ্যের পেজে গিয়ে "Add to Cart" চাপুন, তারপর কার্ট থেকে "Checkout"-এ যান। আপনার নাম, মোবাইল নম্বর ও ঠিকানা দিয়ে অর্ডার কনফার্ম করুন। অথবা সরাসরি "Buy Now" চেপেও অর্ডার করতে পারবেন। অর্ডারের সময় আমরা মোবাইল নম্বর যাচাই করে কনফার্ম করি।',
  },
  {
    q: 'অর্ডার কীভাবে ট্র্যাক করব? — How do I track my order?',
    a: 'হোমপেজের "Track Order" অপশনে গিয়ে আপনার অর্ডার আইডি বা মোবাইল নম্বর দিন। অর্ডারের বর্তমান স্ট্যাটাস (Pending → Confirmed → Processing → Shipped → Delivered) সাথে সাথে দেখতে পাবেন। কোনো লগইন লাগবে না।',
  },
  {
    q: 'ডেলিভারি চার্জ কত? — What are the delivery charges?',
    a: 'ডেলিভারি চার্জ আপনার এরিয়ার উপর নির্ভর করে এবং চেকআউটের সময় সঠিক চার্জ দেখানো হয়। কিছু অফার/প্রমোশনে ফ্রি ডেলিভারিও থাকে।',
  },
  {
    q: 'ডেলিভারি হতে কত দিন লাগে? — How long does delivery take?',
    a: 'ঢাকার ভিতরে সাধারণত ১-৩ কার্যদিবস এবং ঢাকার বাইরে ২-৫ কার্যদিবস লাগে। নির্দিষ্ট এরিয়া ও পণ্যের ধরন অনুযায়ী সময় ভিন্ন হতে পারে।',
  },
  {
    q: 'পেমেন্টের কোন কোন উপায় আছে? — What payment methods are available?',
    a: 'ক্যাশ অন ডেলিভারি (COD), বিকাশ (bKash), নগদ (Nagad), রকেট (Rocket) এবং ব্যাংক ট্রান্সফারের মাধ্যমে পেমেন্ট করতে পারবেন। চেকআউট পেজে সব অপশন দেখতে পাবেন।',
  },
  {
    q: 'ক্যাশ অন ডেলিভারি (COD) পাওয়া যায় কি?',
    a: 'হ্যাঁ, সারাদেশে ক্যাশ অন ডেলিভারি সুবিধা রয়েছে। পণ্য হাতে পেয়ে নিশ্চিত হয়ে পেমেন্ট করুন — এটাই সবচেয়ে জনপ্রিয় পেমেন্ট পদ্ধতি।',
  },
  {
    q: 'অর্ডার ক্যান্সেল করা যাবে কি? — Can I cancel my order?',
    a: 'হ্যাঁ। অর্ডার ডিসপ্যাচ না হওয়া পর্যন্ত আপনি ক্যান্সেল করতে পারবেন। আমাদের হটলাইনে কল করুন অথবা "My Orders" থেকে ক্যান্সেল রিকোয়েস্ট করুন।',
  },
  {
    q: 'রিটার্ন ও রিফান্ড পলিসি কী? — What is the return & refund policy?',
    a: 'পণ্য ডেলিভারির ৭ দিনের মধ্যে ভুল পণ্য বা ড্যামেজড পণ্য পেলে রিটার্ন করতে পারবেন। রিফান্ড পেমেন্ট মেথড অনুযায়ী ৫-৭ কার্যদিবসের মধ্যে ফেরত দেওয়া হয়। বিস্তারিত জানতে "রিটার্ন ও রিফান্ড নীতিমালা" পেজ দেখুন।',
  },
  {
    q: 'পণ্যের ওয়ারেন্টি আছে কি? — Is there a warranty?',
    a: 'বেশিরভাগ ইলেকট্রনিক্স পণ্যে ব্র্যান্ড অনুযায়ী ১-২ বছরের ওয়ারেন্টি থাকে। প্রতিটি প্রোডাক্ট পেজে ওয়ারেন্টি তথ্য দেওয়া থাকে।',
  },
  {
    q: 'ওয়ারেন্টি ক্লেইম কীভাবে করব? — How do I claim warranty?',
    a: 'ওয়ারেন্টি ক্লেইম করতে আমাদের হটলাইনে কল করুন বা "যোগাযোগ" পেজে মেসেজ দিন। ওয়ারেন্টি ক্লেইমের সময় পণ্যের রিসিট/ইনভয়েস সাথে রাখুন।',
  },
  {
    q: 'একসাথে একাধিক পণ্য অর্ডার করা যাবে কি?',
    a: 'হ্যাঁ। কার্টে সব পণ্য যোগ করে একসাথে চেকআউট করতে পারবেন। এতে ডেলিভারি চার্জও সাশ্রয় হবে।',
  },
  {
    q: 'প্রোডাক্ট অরিজিনাল কিনা তা নিশ্চিত করব কীভাবে?',
    a: 'আমরা প্রতিটি পণ্য অফিসিয়াল ডিস্ট্রিবিউটর/ইম্পোর্টার থেকে সংগ্রহ করি। পণ্যের সাথে অথেনটিক রিসিট ও ওয়ারেন্টি কার্ড দেওয়া হয়। কোনো সন্দেহ থাকলে আমাদের সাথে যোগাযোগ করুন।',
  },
  {
    q: 'অর্ডার কনফার্ম হওয়ার পর কী হয়? — What happens after I place an order?',
    a: 'অর্ডার কনফার্ম হলে SMS/কলের মাধ্যমে নিশ্চিত করা হয়। এরপর প্রোডাক্ট প্যাক করে ডেলিভারি পার্টনারকে দেওয়া হয় এবং "Track Order" থেকে প্রতিটি ধাপ দেখা যায়।',
  },
  {
    q: 'সাপোর্টের সাথে কীভাবে যোগাযোগ করব? — How do I contact support?',
    a: '"যোগাযোগ" পেজ থেকে ফোন, ইমেইল বা মেসেজ পাঠাতে পারবেন। আমাদের সাপোর্ট টিম প্রতিদিন সকাল ৯টা থেকে রাত ১০টা পর্যন্ত অর্ডার, ডেলিভারি ও রিটার্ন সংক্রান্ত যেকোনো প্রশ্নে সাহায্য করে।',
  },
]

const FaqPage = () => {
  const reduceMotion = useReducedMotion()
  const [openIndex, setOpenIndex] = useState<number | null>(0)

  const toggle = (index: number) => setOpenIndex((current) => (current === index ? null : index))
  const transition = { duration: reduceMotion ? 0 : 0.28, ease: EASE }

  return (
    <div className="min-h-[60vh] bg-[#f5f7f5] pb-16">
      <SEO
        title="সাধারণ জিজ্ঞাসা (FAQ) - MamaBazar"
        description="অর্ডার, ডেলিভারি, পেমেন্ট, রিটার্ন ও ওয়ারেন্টি সম্পর্কিত সাধারণ জিজ্ঞাসার উত্তর — Frequently asked questions about ordering, delivery and more."
      />
      <div className="mx-auto max-w-7xl px-4 pt-5 sm:px-6 lg:px-8">
        <nav className="flex items-center gap-1.5 text-xs text-slate-500">
          <Link to="/" className="flex items-center gap-1 transition hover:text-emerald-900">
            <Home className="h-3.5 w-3.5" /> হোম
          </Link>
          <ChevronRight className="h-3.5 w-3.5" />
          <span className="font-medium text-emerald-950">সাধারণ জিজ্ঞাসা (FAQ)</span>
        </nav>

        <div className="mt-6 grid gap-8 lg:grid-cols-[260px_minmax(0,1fr)]">
          <aside className="hidden lg:block">
            <div className="sticky top-24 rounded-md border border-slate-200 bg-white p-4 shadow-sm">
              <p className="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">অন্যান্য পেজ</p>
              <ul className="mt-3 space-y-1">
                <li>
                  <Link className="block rounded-md px-2 py-1.5 text-[13px] text-slate-600 transition hover:bg-emerald-50 hover:text-emerald-900" to="/refund-policy">
                    রিটার্ন ও রিফান্ড নীতিমালা
                  </Link>
                </li>
                <li>
                  <Link className="block rounded-md px-2 py-1.5 text-[13px] text-slate-600 transition hover:bg-emerald-50 hover:text-emerald-900" to="/shipping-policy">
                    ডেলিভারি নীতিমালা
                  </Link>
                </li>
                <li>
                  <Link className="block rounded-md px-2 py-1.5 text-[13px] text-slate-600 transition hover:bg-emerald-50 hover:text-emerald-900" to="/payment-policy">
                    পেমেন্ট নীতিমালা
                  </Link>
                </li>
                <li>
                  <Link className="block rounded-md px-2 py-1.5 text-[13px] text-slate-600 transition hover:bg-emerald-50 hover:text-emerald-900" to="/warranty-policy">
                    ওয়ারেন্টি নীতিমালা
                  </Link>
                </li>
                <li>
                  <Link className="block rounded-md px-2 py-1.5 text-[13px] text-slate-600 transition hover:bg-emerald-50 hover:text-emerald-900" to="/contact">
                    যোগাযোগ করুন
                  </Link>
                </li>
              </ul>
            </div>
          </aside>

          <article className="min-w-0">
            <div className="rounded-md border border-slate-200 bg-white px-5 py-8 shadow-sm sm:px-10">
              <p className="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-600">Help Center</p>
              <h1 className="mt-2 text-2xl font-extrabold leading-snug text-emerald-950 sm:text-3xl">
                সাধারণ জিজ্ঞাসা (FAQ)
              </h1>
              <p className="mt-3 text-sm leading-6 text-slate-500">
                অর্ডার, ডেলিভারি, পেমেন্ট ও রিটার্ন সম্পর্কিত সবচেয়ে সাধারণ প্রশ্নগুলোর উত্তর এখানে দেওয়া হলো।
                প্রশ্নে ক্লিক করলে উত্তর দেখতে পাবেন।
              </p>

              <div className="mt-8 divide-y divide-slate-100 border-t border-slate-100">
                {FAQ_ITEMS.map((item, index) => {
                  const isOpen = openIndex === index
                  return (
                    <div key={index}>
                      <button
                        aria-expanded={isOpen}
                        className="flex w-full items-center justify-between gap-4 py-5 text-left"
                        onClick={() => toggle(index)}
                        type="button"
                      >
                        <span className="text-[15px] font-bold leading-6 text-emerald-950">{item.q}</span>
                        <motion.span
                          animate={{ rotate: isOpen ? 180 : 0 }}
                          className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-900"
                          transition={transition}
                        >
                          <ChevronDown className="h-4 w-4" />
                        </motion.span>
                      </button>
                      <AnimatePresence initial={false}>
                        {isOpen && (
                          <motion.div
                            animate={{ height: 'auto', opacity: 1 }}
                            className="overflow-hidden"
                            exit={{ height: 0, opacity: 0 }}
                            initial={{ height: 0, opacity: 0 }}
                            transition={transition}
                          >
                            <p className="pb-5 pr-12 text-sm leading-7 text-slate-600">{item.a}</p>
                          </motion.div>
                        )}
                      </AnimatePresence>
                    </div>
                  )
                })}
              </div>
            </div>

            <section className="mt-6 rounded-md border border-emerald-900/20 bg-emerald-950 px-5 py-6 text-white shadow-sm sm:px-8">
              <h2 className="flex items-center gap-2 text-lg font-bold">
                <MessageCircle className="h-5 w-5 text-orange-400" />
                আপনার প্রশ্নের উত্তর পাননি?
              </h2>
              <p className="mt-2 text-sm leading-6 text-emerald-100/90">
                আমাদের সাপোর্ট টিম অর্ডার, ডেলিভারি এবং রিটার্ন সংক্রান্ত যেকোনো প্রশ্নে আপনাকে সাহায্য করতে প্রস্তুত।
              </p>
              <div className="mt-4 flex flex-wrap gap-3">
                <Link
                  className="inline-flex items-center gap-2 rounded-md bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-600"
                  to="/contact"
                >
                  <Phone className="h-4 w-4" /> যোগাযোগ করুন
                </Link>
              </div>
            </section>
          </article>
        </div>
      </div>
    </div>
  )
}

export default FaqPage