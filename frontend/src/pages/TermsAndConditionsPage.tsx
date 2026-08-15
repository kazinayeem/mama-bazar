import StaticInfoLayout from '@/components/layout/StaticInfoLayout'

const SectionHeading = ({ id, bangla, english }: { id: string; bangla: string; english: string }) => (
  <div id={id} className="scroll-mt-28">
    <h2 className="text-lg font-extrabold text-brand-green-700">{english}</h2>
    <p className="mt-1 text-[11px] font-bold uppercase tracking-[0.15em] text-brand-orange-500">{bangla}</p>
  </div>
)

const TermsAndConditionsPage = () => {
  return (
    <StaticInfoLayout
      kicker="Terms & Conditions"
      title="Terms & Conditions"
      seoTitle="Terms & Conditions"
      seoDescription="Read MamaBazar's Terms & Conditions covering website usage, accounts, product information, pricing, orders, payments, shipping, returns, and user responsibilities."
      url="/terms-and-conditions"
      toc={[
        { id: 'website-usage', label: 'Website Usage' },
        { id: 'account', label: 'Account Responsibility' },
        { id: 'product-info', label: 'Product Information' },
        { id: 'pricing', label: 'Pricing' },
        { id: 'orders', label: 'Orders' },
        { id: 'payments', label: 'Payments' },
        { id: 'shipping', label: 'Shipping' },
        { id: 'returns', label: 'Returns & Refunds' },
        { id: 'responsibilities', label: 'User Responsibilities' },
        { id: 'changes', label: 'Changes to Terms' },
        { id: 'contact', label: 'Contact' },
      ]}
      relatedLinks={[
        { title: 'Privacy Policy', to: '/privacy-policy' },
        { title: 'Cookie Policy', to: '/cookie-policy' },
        { title: 'About Us', to: '/about' },
        { title: 'Contact Us', to: '/contact' },
      ]}
    >
      <p className="text-[15px] leading-7 text-slate-700">
        By using MamaBazar, you agree to these terms. Please read them carefully before placing an order.
      </p>
      <p className="mt-2 text-[15px] leading-7 text-slate-700">
        MamaBazar ব্যবহারের মাধ্যমে আপনি এই শর্তাবলীতে সম্মত হচ্ছেন। অর্ডার করার আগে দয়া করে মনোযোগ দিয়ে পড়ুন।
      </p>

      <section>
        <SectionHeading id="website-usage" bangla="ওয়েবসাইট ব্যবহার" english="Website Usage" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          You may use this website for lawful purposes only. You agree not to misuse the site, attempt to gain
          unauthorised access, or interfere with its normal operation.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          আপনি শুধুমাত্র আইনসম্মত উদ্দেশ্যে এই ওয়েবসাইট ব্যবহার করতে পারবেন। ওয়েবসাইটের স্বাভাবিক কার্যক্রমে বাধা দেওয়া বা
          অননুমোদিত প্রবেশের চেষ্টা নিষিদ্ধ।
        </p>
      </section>

      <section>
        <SectionHeading id="account" bangla="অ্যাকাউন্টের দায়িত্ব" english="Account Responsibility" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          You are responsible for keeping your account credentials confidential and for all activity under your
          account. Please contact us immediately if you suspect unauthorised use.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          আপনার অ্যাকাউন্টের তথ্য গোপন রাখা এবং অ্যাকাউন্টের মাধ্যমে হওয়া সকল কার্যক্রমের দায়িত্ব আপনার। অননুমোদিত ব্যবহার
          সন্দেহ হলে অবিলম্বে আমাদের জানান।
        </p>
      </section>

      <section>
        <SectionHeading id="product-info" bangla="পণ্যের তথ্য" english="Product Information" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          We make every effort to display product images, descriptions, and prices accurately. However, minor
          differences in colour or appearance may occur, and specifications may change without notice.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          আমরা পণ্যের ছবি, বিবরণ ও মূল্য যথাসম্ভব সঠিকভাবে দেখানোর চেষ্টা করি। তবে রঙ বা চেহারায় সামান্য পার্থক্য থাকতে পারে
          এবং স্পেসিফিকেশন নোটিশ ছাড়াই পরিবর্তন হতে পারে।
        </p>
      </section>

      <section>
        <SectionHeading id="pricing" bangla="মূল্য নির্ধারণ" english="Pricing" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          All prices are shown in Bangladeshi Taka (৳) and may include applicable offers or discounts. Prices are
          subject to change without prior notice, but the price confirmed at checkout will be honoured.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          সকল মূল্য বাংলাদেশি টাকায় (৳) প্রদর্শিত হয়। চেকআউটের সময় নিশ্চিত হওয়া মূল্যই প্রযোজ্য হবে।
        </p>
      </section>

      <section>
        <SectionHeading id="orders" bangla="অর্ডার" english="Orders" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          Once you place an order, we will confirm availability and process it. We may cancel orders that cannot be
          fulfilled due to stock or pricing errors, and we will refund any payment received.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          অর্ডার নিশ্চিত হওয়ার পর আমরা তা প্রক্রিয়াজাত করি। স্টক বা মূল্যজনিত কারণে কোনো অর্ডার বাতিল হলে পেমেন্ট ফেরত
          দেওয়া হবে।
        </p>
      </section>

      <section>
        <SectionHeading id="payments" bangla="পেমেন্ট" english="Payments" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          We accept the payment methods shown at checkout, including Cash on Delivery and online payment options.
          Payments are processed securely through trusted providers.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          চেকআউটে প্রদর্শিত পেমেন্ট পদ্ধতি আমরা গ্রহণ করি — যার মধ্যে রয়েছে ক্যাশ অন ডেলিভারি এবং অনলাইন পেমেন্ট।
        </p>
      </section>

      <section>
        <SectionHeading id="shipping" bangla="ডেলিভারি" english="Shipping" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          Delivery times and charges vary by location and are shown during checkout. While we aim to deliver on
          time, delays caused by couriers or circumstances beyond our control are not covered by this policy.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          ডেলিভারির সময় ও চার্জ এলাকা অনুযায়ী ভিন্ন হয় এবং চেকআউটের সময় দেখানো হয়।
        </p>
      </section>

      <section>
        <SectionHeading id="returns" bangla="রিটার্ন ও রিফান্ড" english="Returns & Refunds" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          If a product arrives damaged, defective, or not as described, contact us within the return window and we
          will arrange a replacement or refund as applicable.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          পণ্য ক্ষতিগ্রস্ত, ত্রুটিপূর্ণ বা বর্ণনার সাথে মিল না থাকলে নির্ধারিত সময়ের মধ্যে আমাদের জানান — আমরা প্রতিস্থাপন বা
          রিফান্ডের ব্যবস্থা করব।
        </p>
      </section>

      <section>
        <SectionHeading id="responsibilities" bangla="ব্যবহারকারীর দায়িত্ব" english="User Responsibilities" />
        <ul className="mt-3 list-disc space-y-1.5 pl-5 text-[15px] leading-7 text-slate-700">
          <li>Provide accurate delivery and contact information</li>
          <li>Receive and inspect your order upon delivery</li>
          <li>Use products in accordance with the provided instructions</li>
          <li>Comply with all applicable laws while using this website</li>
        </ul>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          সঠিক ডেলিভারি ও যোগাযোগের তথ্য প্রদান, ডেলিভারির সময় পণ্য গ্রহণ ও যাচাই এবং নির্দেশনা অনুযায়ী পণ্য ব্যবহার আপনার
          দায়িত্ব।
        </p>
      </section>

      <section>
        <SectionHeading id="changes" bangla="শর্তাবলীর পরিবর্তন" english="Changes to Terms" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          We may update these terms from time to time. Continued use of the website after changes are posted means
          you accept the updated terms.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          এই শর্তাবলী সময়ে সময়ে আপডেট করা হতে পারে। আপডেটের পর ওয়েবসাইট ব্যবহার করার অর্থ আপনি নতুন শর্ত মেনে নিচ্ছেন।
        </p>
      </section>

      <section>
        <SectionHeading id="contact" bangla="যোগাযোগ" english="Contact" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          Questions about these terms? Reach us at{' '}
          <a className="font-medium text-brand-orange-500 underline underline-offset-4" href="mailto:support@mamabazar.com">
            support@mamabazar.com
          </a>{' '}
          or visit our Contact page.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          এই শর্তাবলী সম্পর্কে প্রশ্ন থাকলে আমাদের Contact পেজের মাধ্যমে যোগাযোগ করুন।
        </p>
      </section>
    </StaticInfoLayout>
  )
}

export default TermsAndConditionsPage