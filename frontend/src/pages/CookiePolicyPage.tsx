import StaticInfoLayout from '@/components/layout/StaticInfoLayout'

const SectionHeading = ({ id, bangla, english }: { id: string; bangla: string; english: string }) => (
  <div id={id} className="scroll-mt-28">
    <h2 className="text-lg font-extrabold text-brand-green-700">{english}</h2>
    <p className="mt-1 text-[11px] font-bold uppercase tracking-[0.15em] text-brand-orange-500">{bangla}</p>
  </div>
)

const CookiePolicyPage = () => {
  return (
    <StaticInfoLayout
      kicker="Cookie Policy"
      title="Cookie Policy"
      seoTitle="Cookie Policy"
      seoDescription="Read MamaBazar's Cookie Policy — what cookies are, why we use them, the types of cookies we use, and how you can control them."
      url="/cookie-policy"
      toc={[
        { id: 'what-are-cookies', label: 'What Are Cookies' },
        { id: 'why-cookies', label: 'Why We Use Cookies' },
        { id: 'types-of-cookies', label: 'Types of Cookies' },
        { id: 'control-cookies', label: 'How to Control Cookies' },
      ]}
      relatedLinks={[
        { title: 'Privacy Policy', to: '/privacy-policy' },
        { title: 'Terms & Conditions', to: '/terms-and-conditions' },
        { title: 'About Us', to: '/about' },
        { title: 'Contact Us', to: '/contact' },
      ]}
    >
      <section>
        <SectionHeading id="what-are-cookies" bangla="Cookies কী" english="What Are Cookies" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          Cookies are small text files stored on your device when you visit a website. They help the website
          remember your preferences and understand how you use it, making your experience smoother and more
          personal.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          Cookies হলো ছোট টেক্সট ফাইল, যা ওয়েবসাইট দেখার সময় আপনার ডিভাইসে সংরক্ষিত হয়। এটি ওয়েবসাইটকে আপনার পছন্দ মনে
          রাখতে এবং ব্যবহার বুঝতে সাহায্য করে।
        </p>
      </section>

      <section>
        <SectionHeading id="why-cookies" bangla="আমরা কেন Cookies ব্যবহার করি" english="Why We Use Cookies" />
        <ul className="mt-3 list-disc space-y-1.5 pl-5 text-[15px] leading-7 text-slate-700">
          <li>To keep the website working correctly</li>
          <li>To remember your cart and login session</li>
          <li>To understand how visitors use the site so we can improve it</li>
          <li>To show relevant offers and measure marketing campaigns</li>
        </ul>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          ওয়েবসাইট সঠিকভাবে চালানো, আপনার কার্ট ও লগইন সেশন মনে রাখা, ভিজিটরদের ব্যবহার বুঝে সাইট উন্নত করা এবং প্রাসঙ্গিক
          অফার দেখানোর জন্য আমরা Cookies ব্যবহার করি।
        </p>
      </section>

      <section>
        <SectionHeading id="types-of-cookies" bangla="কোন ধরনের Cookies ব্যবহার করা হয়" english="Types of Cookies" />
        <div className="mt-3 space-y-3">
          <div className="rounded-md border border-slate-200 bg-slate-50/60 p-4">
            <p className="text-sm font-bold text-brand-green-700">Essential Cookies</p>
            <p className="mt-1 text-[13px] leading-6 text-slate-600">
              Required for the website to function — such as keeping you logged in and your cart updated.
            </p>
          </div>
          <div className="rounded-md border border-slate-200 bg-slate-50/60 p-4">
            <p className="text-sm font-bold text-brand-green-700">Analytics Cookies</p>
            <p className="mt-1 text-[13px] leading-6 text-slate-600">
              Help us understand how visitors use the site so we can improve performance and content.
            </p>
          </div>
          <div className="rounded-md border border-slate-200 bg-slate-50/60 p-4">
            <p className="text-sm font-bold text-brand-green-700">Marketing Cookies</p>
            <p className="mt-1 text-[13px] leading-6 text-slate-600">
              Used to show you relevant offers and measure the effectiveness of our campaigns.
            </p>
          </div>
          <p className="text-[13px] leading-6 text-slate-500">
            প্রয়োজনীয় Cookies ওয়েবসাইট চালানোর জন্য, বিশ্লেষণ Cookies ব্যবহার বুঝতে এবং মার্কেটিং Cookies প্রাসঙ্গিক অফার
            দেখানোর জন্য ব্যবহৃত হয়।
          </p>
        </div>
      </section>

      <section>
        <SectionHeading id="control-cookies" bangla="Cookies কীভাবে নিয়ন্ত্রণ করবেন" english="How to Control Cookies" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          You can control and delete cookies through your browser settings. You may also block cookies on this
          site, but some features — such as keeping items in your cart — may not work properly without them.
        </p>
        <ul className="mt-3 list-disc space-y-1.5 pl-5 text-[15px] leading-7 text-slate-700">
          <li>Clear cookies from your browser after each session</li>
          <li>Set your browser to block or alert you before accepting cookies</li>
          <li>Use private or incognito mode to limit stored data</li>
        </ul>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          ব্রাউজার সেটিংস থেকে আপনি Cookies নিয়ন্ত্রণ ও মুছে ফেলতে পারেন। Cookies ব্লক করলে কার্ট সংরক্ষণের মতো কিছু
          সুবিধা সঠিকভাবে কাজ নাও করতে পারে।
        </p>
      </section>
    </StaticInfoLayout>
  )
}

export default CookiePolicyPage