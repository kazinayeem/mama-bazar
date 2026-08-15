import { Link } from 'react-router-dom'
import { HeartHandshake, Leaf, PackageCheck, Phone, ShoppingBag, ShieldCheck, Star, Truck } from 'lucide-react'
import StaticInfoLayout from '@/components/layout/StaticInfoLayout'
import TeamSection from '@/features/homepage/TeamSection'

const SectionHeading = ({ id, bangla, english }: { id: string; bangla: string; english: string }) => (
  <div id={id} className="scroll-mt-28">
    <h2 className="text-xl font-extrabold text-brand-green-700">{english}</h2>
    <p className="mt-1 text-[11px] font-bold uppercase tracking-[0.15em] text-brand-orange-500">{bangla}</p>
  </div>
)

const features = [
  { icon: Truck, title: 'Fast Delivery', bangla: 'দ্রুত ডেলিভারি' },
  { icon: ShieldCheck, title: 'Official Warranty', bangla: 'অফিসিয়াল ওয়ারেন্টি' },
  { icon: PackageCheck, title: 'Quality Products', bangla: 'মানসম্মত পণ্য' },
  { icon: Phone, title: '24/7 Support', bangla: '২৪/৭ সাপোর্ট' },
]

const AboutPage = () => {
  return (
    <StaticInfoLayout
      kicker="Our Story"
      title="About MamaBazar"
      seoTitle="About Us"
      seoDescription="Learn about MamaBazar — our mission, services, and the promise we make to every customer. Trusted online store in Bangladesh for quality products at the best prices."
      url="/about"
      relatedLinks={[
        { title: 'Contact Us', to: '/contact' },
        { title: 'Privacy Policy', to: '/privacy-policy' },
        { title: 'Terms & Conditions', to: '/terms-and-conditions' },
        { title: 'Cookie Policy', to: '/cookie-policy' },
      ]}
    >
      {/* About MamaBazar + Our Mission — balanced two-column desktop layout */}
      <div className="grid gap-8 lg:grid-cols-2 lg:items-start lg:gap-12">
        <div className="space-y-8">
          <section>
            <SectionHeading id="about" bangla="MamaBazar সম্পর্কে সংক্ষিপ্ত পরিচিতি" english="About MamaBazar" />
            <p className="mt-3 text-[15px] leading-7 text-slate-700">
              <strong className="font-semibold text-brand-green-700">MamaBazar</strong> is a trusted online store
              dedicated to bringing quality products to customers across Bangladesh. We combine a carefully curated
              catalogue with fast, reliable delivery and friendly customer support — so shopping from home is easy,
              safe, and enjoyable.
            </p>
            <p className="mt-4 text-[15px] leading-7 text-slate-700">
              <strong className="font-semibold text-brand-green-700">MamaBazar</strong> একটি বিশ্বস্ত অনলাইন শপ, যেখানে আমরা
              বাংলাদেশের সর্বত্র গ্রাহকদের কাছে মানসম্মত পণ্য পৌঁছে দিতে প্রতিশ্রুতিবদ্ধ। আমরা যত্ন করে বাছাই করা ক্যাটালগ,
              দ্রুত ও নির্ভরযোগ্য ডেলিভারি এবং বন্ধুত্বপূর্ণ গ্রাহক সাপোর্টের মাধ্যমে ঘরে বসে কেনাকাটাকে সহজ, নিরাপদ এবং
              আনন্দময় করে তুলি।
            </p>
          </section>
        </div>

        <div className="space-y-8">
          <section>
            <SectionHeading id="mission" bangla="আমাদের লক্ষ্য" english="Our Mission" />
            <p className="mt-3 text-[15px] leading-7 text-slate-700">
              Our mission is simple: to make quality products accessible to every household in Bangladesh at fair
              prices. We work with trusted suppliers, verify every product, and continuously improve our delivery
              network so you always get the best value for your money.
            </p>
            <p className="mt-4 text-[15px] leading-7 text-slate-700">
              আমাদের লক্ষ্য খুবই সহজ — বাংলাদেশের প্রতিটি পরিবারে সঠিক মূল্যে মানসম্মত পণ্য পৌঁছে দেওয়া। আমরা নির্ভরযোগ্য
              সরবরাহকারীদের সাথে কাজ করি, প্রতিটি পণ্য যাচাই করি এবং ডেলিভারি নেটওয়ার্ককে প্রতিনিয়ত উন্নত করি, যাতে আপনি
              সর্বদা সঠিক মূল্যে সেরা পণ্য পান।
            </p>
          </section>
        </div>
      </div>

      {/* Our Services */}
      <section>
        <SectionHeading id="services" bangla="আমাদের সেবা" english="Our Services" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          From household essentials to lifestyle products, we offer a wide range of services designed around your
          convenience:
        </p>
        <ul className="mt-3 grid gap-3 sm:grid-cols-2">
          {features.map(({ icon: Icon, title, bangla }) => (
            <li
              className="flex items-start gap-3 rounded-md border border-slate-200 bg-slate-50/60 px-4 py-3.5"
              key={title}
            >
              <span className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-brand-green-50 text-brand-orange-500">
                <Icon className="h-4 w-4" />
              </span>
              <div>
                <p className="text-sm font-bold text-brand-green-700">{title}</p>
                <p className="mt-0.5 text-xs text-slate-500">{bangla}</p>
              </div>
            </li>
          ))}
        </ul>
        <ul className="mt-4 space-y-2 text-[15px] leading-7 text-slate-700">
          <li>🛍️ Wide range of quality products — মানসম্মত পণ্যের বিশাল কালেকশন</li>
          <li>🚚 Fast and reliable delivery across the country — সারাদেশে দ্রুত ও নির্ভরযোগ্য ডেলিভারি</li>
          <li>💬 Dedicated customer support — নিবেদিতপ্রাণ গ্রাহক সাপোর্ট</li>
          <li>🔁 Easy returns and refunds — সহজ রিটার্ন ও রিফান্ড</li>
        </ul>
      </section>

      {/* Our Customer Promise */}
      <section>
        <SectionHeading id="promise" bangla="গ্রাহকের জন্য আমাদের প্রতিশ্রুতি" english="Our Customer Promise" />
        <div className="mt-3 grid gap-3 sm:grid-cols-2">
          <div className="rounded-md border border-brand-green-100 bg-brand-green-50/50 p-4">
            <Star className="h-5 w-5 text-brand-orange-500" />
            <p className="mt-2 text-sm font-bold text-brand-green-700">Authentic Products</p>
            <p className="mt-1 text-[13px] leading-6 text-slate-600">প্রতিটি পণ্য শতভাগ খাঁটি ও যাচাইকৃত।</p>
          </div>
          <div className="rounded-md border border-brand-green-100 bg-brand-green-50/50 p-4">
            <ShoppingBag className="h-5 w-5 text-brand-orange-500" />
            <p className="mt-2 text-sm font-bold text-brand-green-700">Fair Prices</p>
            <p className="mt-1 text-[13px] leading-6 text-slate-600">সবসময় ন্যায্য মূল্যে সেরা ডিল।</p>
          </div>
          <div className="rounded-md border border-brand-green-100 bg-brand-green-50/50 p-4">
            <Truck className="h-5 w-5 text-brand-orange-500" />
            <p className="mt-2 text-sm font-bold text-brand-green-700">On-Time Delivery</p>
            <p className="mt-1 text-[13px] leading-6 text-slate-600">প্রতিশ্রুত সময়ে ডেলিভারি।</p>
          </div>
          <div className="rounded-md border border-brand-green-100 bg-brand-green-50/50 p-4">
            <HeartHandshake className="h-5 w-5 text-brand-orange-500" />
            <p className="mt-2 text-sm font-bold text-brand-green-700">Customer First</p>
            <p className="mt-1 text-[13px] leading-6 text-slate-600">গ্রাহকের সুখ-সন্তুষ্টিই আমাদের প্রথম অগ্রাধিকার।</p>
          </div>
        </div>
        <p className="mt-4 text-[15px] leading-7 text-slate-700">
          <Leaf className="mr-1 inline h-4 w-4 text-brand-green-500" />
          We believe every customer deserves a smooth, honest, and worry-free shopping experience. That promise
          guides everything we do.
        </p>
        <p className="mt-4">
          <Link
            to="/contact"
            className="inline-flex items-center gap-2 rounded-md bg-brand-orange-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-orange-600"
          >
            <Phone className="h-4 w-4" /> Get in Touch
          </Link>
        </p>
      </section>

      {/* Our Team */}
      <TeamSection />
    </StaticInfoLayout>
  )
}

export default AboutPage