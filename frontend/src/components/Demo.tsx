export default function Demo() {
  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 flex items-center justify-center px-4 sm:px-6">
      <div className="relative w-full max-w-lg">
        {/* Background glow */}
        <div className="absolute -top-20 left-1/2 -translate-x-1/2 w-80 h-80 bg-amber-500/10 rounded-full blur-3xl" />

        <div className="relative z-10 bg-white/[0.05] backdrop-blur-xl border border-white/10 rounded-3xl p-8 sm:p-10 text-center shadow-2xl">
          {/* Status Icon */}
          <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/15 border border-amber-500/20">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-8 w-8 text-amber-400"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              strokeWidth={2}
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M12 9v2m0 4h.01M5.07 19H19a2 2 0 001.75-2.97L13.76 4.12a2 2 0 00-3.52 0L3.32 16.03A2 2 0 005.07 19z"
              />
            </svg>
          </div>

          {/* Status badge */}
          <div className="inline-flex items-center gap-2 rounded-full border border-amber-400/20 bg-amber-400/10 px-4 py-1.5 text-xs font-medium text-amber-300 uppercase tracking-wider mb-5">
            <span className="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse" />
            Server Unavailable
          </div>

          {/* Heading */}
          <h1 className="text-2xl sm:text-3xl font-bold text-white mb-3 tracking-tight">
            Sub-Server Unavailable
          </h1>

          {/* Message */}
          <p className="text-slate-400 text-sm sm:text-base leading-relaxed mb-8">
            Due to high load on the main server, the sub-server is currently
            unavailable.
          </p>

          <div className="h-px bg-white/10 mb-8" />

          {/* Contact */}
          <div className="space-y-2">
            <p className="text-slate-500 text-sm">Please contact</p>
            <a
              href="mailto:contact@bornosoft.bd"
              className="inline-flex items-center gap-2 text-white font-semibold text-sm sm:text-base hover:text-amber-400 transition-colors"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                className="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2}
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
              </svg>
              contact@bornosoft.bd
            </a>
            <p className="text-slate-500 text-xs sm:text-sm">
              or wait until the main server becomes available again.
            </p>
          </div>
        </div>

        <p className="text-center text-slate-700 text-xs mt-6">
          Bornosoft &mdash; We appreciate your patience.
        </p>
      </div>
    </div>
  );
}
