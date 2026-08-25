import React from "react";

export default function Demo() {
  return (
    <div className="min-h-screen bg-slate-950 flex items-center justify-center px-6">
      <div className="text-center max-w-xl">

        {/* Logo / Brand */}
        <div className="mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white text-slate-950 font-bold text-2xl shadow-lg">
            B
          </div>
        </div>

        {/* Status */}
        <div className="inline-flex items-center gap-2 px-4 py-2 mb-6 rounded-full border border-white/10 bg-white/5 text-sm text-slate-300">
          <span className="w-2 h-2 rounded-full bg-amber-400 animate-pulse" />
          Under Maintenance
        </div>

        {/* Heading */}
        <h1 className="text-4xl sm:text-5xl font-bold tracking-tight text-white mb-5">
          We’ll Be Back Soon
        </h1>

        {/* Description */}
        <p className="text-slate-400 text-base sm:text-lg leading-relaxed mb-8">
          This site is currently under maintenance.
          <br />
          Please wait a little while — we’ll be back online soon.
        </p>

        {/* Contact */}
        <div className="border border-white/10 bg-white/[0.03] rounded-2xl p-5">
          <p className="text-sm text-slate-500 mb-2">
            Need to contact us?
          </p>

          <a
            href="mailto:contact@bornosoft.bd"
            className="text-white font-medium hover:text-blue-400 transition-colors"
          >
            contact@bornosoft.bd
          </a>
        </div>

        {/* Footer */}
        <p className="mt-8 text-xs text-slate-600">
          Thanks for your patience. We appreciate you staying with us.
        </p>

      </div>
    </div>
  );
}
