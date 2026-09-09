import { Mail, Globe } from 'lucide-react'

export const UnderConstruction = () => {
  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100 flex items-center justify-center px-4 py-12">
      <div className="max-w-2xl w-full text-center">
        {/* Logo */}
        <div className="mb-8 flex justify-center">
          <img
            src="https://bornosoft.bd/images/logo.jpeg"
            alt="Bornosoft Logo"
            className="h-24 w-auto object-contain"
          />
        </div>

        {/* Heading */}
        <h1 className="text-4xl md:text-5xl font-bold text-slate-900 mb-4">
          Website Under Construction
        </h1>

        {/* Message */}
        <p className="text-lg md:text-xl text-slate-600 mb-8 leading-relaxed">
          Our website is currently under construction. Please come back later.
        </p>

        {/* Contact Section */}
        <div className="bg-white rounded-lg shadow-md p-8 mb-8">
          <p className="text-slate-700 font-semibold mb-6 text-lg">
            Please contact with Bornosoft
          </p>

          <div className="space-y-4 md:space-y-0 md:flex md:items-center md:justify-center md:gap-8">
            {/* Website Link */}
            <a
              href="https://bornosoft.bd"
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-3 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200"
            >
              <Globe size={20} />
              <span>bornosoft.bd</span>
            </a>

            {/* Email Link */}
            <a
              href="mailto:contact@bornosoft.bd"
              className="inline-flex items-center gap-3 px-6 py-3 bg-slate-200 hover:bg-slate-300 text-slate-900 rounded-lg font-medium transition-colors duration-200"
            >
              <Mail size={20} />
              <span>contact@bornosoft.bd</span>
            </a>
          </div>
        </div>

        {/* Footer Note */}
        <p className="text-sm text-slate-500">
          Thank you for your patience. We're working hard to bring you an amazing experience.
        </p>
      </div>
    </div>
  )
}

export default UnderConstruction
