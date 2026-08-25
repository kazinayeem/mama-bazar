export default function Demo() {
  return (
    <div className="min-h-screen bg-slate-950 flex items-center justify-center px-6 relative overflow-hidden">
      {/* Background Glow */}
      <div className="absolute top-[-180px] left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-blue-500/10 rounded-full blur-3xl" />

      <div className="relative z-10 w-full max-w-xl text-center">

        {/* Lock Icon */}
        <div className="mx-auto mb-7 flex h-20 w-20 items-center justify-center rounded-2xl bg-white text-slate-950 shadow-2xl text-3xl">
          🔒
        </div>

        {/* Status */}
        <div className="inline-flex items-center gap-2 rounded-full border border-amber-400/20 bg-amber-400/10 px-4 py-2 text-sm text-amber-300 mb-6">
          <span className="h-2 w-2 rounded-full bg-amber-400 animate-pulse" />
          Website Locked
        </div>

        <h1 className="text-4xl sm:text-5xl font-bold tracking-tight text-white mb-5">
          টাকা দিলে ওয়েবসাইট খুলবে! 😭
        </h1>

        <p className="text-xl font-semibold text-slate-300 mb-4">
          Website Under Maintenance
        </p>

        <p className="text-slate-400 text-base sm:text-lg leading-relaxed mb-8">
          This website is currently under maintenance.
          <br />
          Please wait a little while — we’ll be back soon.
        </p>

        <div className="mb-6 rounded-2xl border border-white/10 bg-white/[0.04] p-5">
          <p className="text-slate-300 leading-relaxed">
            কাজ না করলে সমস্যা নাই...
            <br />
            <span className="text-amber-400 font-semibold">
              কিন্তু পেমেন্টটা করে দিয়েন! 😂
            </span>
          </p>
        </div>

        {/* Payment Card */}
        <div className="rounded-2xl border border-white/10 bg-white/[0.04] p-6 text-left">
          <div className="flex items-center justify-between mb-5">
            <div>
              <p className="text-sm text-slate-500">
                Payment Method
              </p>

              <p className="text-xl font-bold text-white mt-1">
                bKash
              </p>
            </div>

            <div className="text-right">
              <p className="text-sm text-slate-500">
                Payment Number
              </p>

              <p className="text-lg font-semibold text-white mt-1">
                01943124216
              </p>
            </div>
          </div>

          <div className="rounded-xl bg-black/20 p-4">
            <p className="text-sm text-slate-400 leading-relaxed">
              Please complete the pending payment to unlock the
              website and restore access.
            </p>
          </div>
        </div>

        {/* Contact */}
        <div className="mt-7">
          <p className="text-sm text-slate-500 mb-2">
            Need assistance?
          </p>

          <a
            href="mailto:contact@bornosoft.bd"
            className="text-white font-medium hover:text-blue-400 transition-colors"
          >
            contact@bornosoft.bd
          </a>
        </div>

        <p className="mt-8 text-xs text-slate-600">
          Thanks for staying with us.
        </p>

      </div>
    </div>
  );
}
