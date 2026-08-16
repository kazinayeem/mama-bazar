import StaticInfoLayout from '@/components/layout/StaticInfoLayout'
import { useGetStoreInfoQuery } from '@/store/services/commerceApi'

const SectionHeading = ({ id, bangla, english }: { id: string; bangla: string; english: string }) => (
  <div id={id} className="scroll-mt-28">
    <h2 className="text-lg font-extrabold text-brand-green-700">{english}</h2>
    <p className="mt-1 text-[11px] font-bold uppercase tracking-[0.15em] text-brand-orange-500">{bangla}</p>
  </div>
)

const PrivacyPolicyPage = () => {
  const { data: storeInfo } = useGetStoreInfoQuery()
  const storeEmail = storeInfo?.email || 'support@mamabazar.com'
  return (
    <StaticInfoLayout
      kicker="Privacy Policy"
      title="Privacy Policy"
      seoTitle="Privacy Policy"
      seoDescription="Read MamaBazar's Privacy Policy — what information we collect, how we use it, data security, cookies, third-party sharing, user rights, and contact information."
      url="/privacy-policy"
      toc={[
        { id: 'information-we-collect', label: 'Information We Collect' },
        { id: 'how-we-use', label: 'How We Use Information' },
        { id: 'data-security', label: 'Data Security' },
        { id: 'third-party', label: 'Third-Party Sharing' },
        { id: 'cookies', label: 'Cookies' },
        { id: 'user-rights', label: 'User Rights' },
        { id: 'contact', label: 'Contact Information' },
      ]}
      relatedLinks={[
        { title: 'Terms & Conditions', to: '/terms-and-conditions' },
        { title: 'Cookie Policy', to: '/cookie-policy' },
        { title: 'About Us', to: '/about' },
        { title: 'Contact Us', to: '/contact' },
      ]}
    >
      <p className="text-[15px] leading-7 text-slate-700">
        Your privacy matters to us. This policy explains what information MamaBazar collects, how we use and
        protect it, and the choices you have.
      </p>
      <p className="mt-2 text-[15px] leading-7 text-slate-700">
        আপনার গোপনীয়তা আমাদের কাছে গুরুত্বপূর্ণ। এই নীতিমালায় MamaBazar কী তথ্য সংগ্রহ করে, কীভাবে তা ব্যবহার ও
        সুরক্ষা করে এবং আপনার কী কী বিকল্প রয়েছে তা বর্ণনা করা হয়েছে।
      </p>

      <section>
        <SectionHeading id="information-we-collect" bangla="আমরা কী তথ্য সংগ্রহ করি" english="Information We Collect" />
        <ul className="mt-3 list-disc space-y-1.5 pl-5 text-[15px] leading-7 text-slate-700">
          <li>Contact details such as your name, phone number, and delivery address</li>
          <li>Order details, payment method, and purchase history</li>
          <li>Device, browser, and usage information to improve our services</li>
          <li>Messages you send us through our contact channels</li>
        </ul>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          যোগাযোগের তথ্য যেমন নাম, মোবাইল নম্বর ও ডেলিভারি ঠিকানা; অর্ডার ও পেমেন্ট তথ্য; ডিভাইস ও ব্যবহার সংক্রান্ত তথ্য; এবং
          আমাদের কাছে পাঠানো বার্তা।
        </p>
      </section>

      <section>
        <SectionHeading id="how-we-use" bangla="তথ্য কীভাবে ব্যবহার করি" english="How We Use Information" />
        <ul className="mt-3 list-disc space-y-1.5 pl-5 text-[15px] leading-7 text-slate-700">
          <li>To process and deliver your orders</li>
          <li>To provide customer support and respond to your inquiries</li>
          <li>To improve our products, services, and website experience</li>
          <li>To keep you informed about your orders and relevant updates</li>
        </ul>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          অর্ডার প্রক্রিয়াকরণ ও ডেলিভারি, গ্রাহক সাপোর্ট, পণ্য ও সেবার মানোন্নয়ন এবং প্রয়োজনীয় আপডেট জানানোর জন্য আমরা
          আপনার তথ্য ব্যবহার করি।
        </p>
      </section>

      <section>
        <SectionHeading id="data-security" bangla="তথ্যের নিরাপত্তা" english="Data Security" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          We take reasonable technical and organisational measures to protect your personal information from
          unauthorised access, loss, or misuse. Access to your data is limited to staff who need it to serve you.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          অননুমোদিত প্রবেশ, ক্ষতি বা অপব্যবহার থেকে আপনার তথ্য রক্ষা করতে আমরা প্রয়োজনীয় প্রযুক্তিগত ও সাংগঠনিক ব্যবস্থা গ্রহণ
          করি।
        </p>
      </section>

      <section>
        <SectionHeading id="third-party" bangla="তৃতীয় পক্ষের সাথে তথ্য শেয়ারিং" english="Third-Party Sharing" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          We only share your information with trusted partners needed to complete your order — such as delivery
          carriers and payment processors. We do not sell your personal information to anyone.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          শুধুমাত্র অর্ডার সম্পন্ন করার জন্য প্রয়োজনীয় বিশ্বস্ত অংশীদারদের (যেমন ডেলিভারি ও পেমেন্ট পরিষেবা) সাথে আমরা তথ্য
          শেয়ার করি। আমরা কখনো আপনার ব্যক্তিগত তথ্য বিক্রি করি না।
        </p>
      </section>

      <section>
        <SectionHeading id="cookies" bangla="Cookies" english="Cookies" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          We use cookies to make the website work properly and to understand how you use it. You can control or
          disable cookies through your browser settings. See our Cookie Policy for details.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          ওয়েবসাইট সঠিকভাবে চালাতে এবং ব্যবহার বুঝতে আমরা Cookies ব্যবহার করি। ব্রাউজার সেটিংস থেকে আপনি Cookies নিয়ন্ত্রণ
          করতে পারেন। বিস্তারিত দেখুন আমাদের Cookie Policy-এ।
        </p>
      </section>

      <section>
        <SectionHeading id="user-rights" bangla="User Rights" english="User Rights" />
        <ul className="mt-3 list-disc space-y-1.5 pl-5 text-[15px] leading-7 text-slate-700">
          <li>Request a copy of the personal information we hold about you</li>
          <li>Request correction of inaccurate information</li>
          <li>Request deletion of your data, subject to legal obligations</li>
          <li>Opt out of marketing communications at any time</li>
        </ul>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          আপনার তথ্যের কপি চাওয়া, ভুল তথ্য সংশোধন, আইনগত বাধ্যবাধকতা সাপেক্ষে তথ্য মুছে ফেলা এবং যেকোনো সময় মার্কেটিং
          বার্তা থেকে বিরত থাকার অধিকার আপনার রয়েছে।
        </p>
      </section>

      <section>
        <SectionHeading id="contact" bangla="যোগাযোগ" english="Contact Information" />
        <p className="mt-3 text-[15px] leading-7 text-slate-700">
          For any privacy-related questions, contact us at{' '}
          <a className="font-medium text-brand-orange-500 underline underline-offset-4" href={`mailto:${storeEmail}`}>
            {storeEmail}
          </a>{' '}
          or via our Contact page.
        </p>
        <p className="mt-3 text-[13px] leading-6 text-slate-500">
          গোপনীয়তা সংক্রান্ত যেকোনো প্রশ্নে আমাদের সাথে যোগাযোগ করুন আমাদের Contact পেজের মাধ্যমে।
        </p>
      </section>
    </StaticInfoLayout>
  )
}

export default PrivacyPolicyPage