import { Mail, Globe } from 'lucide-react'
import { motion } from 'framer-motion'
import { useState, useEffect } from 'react'
import { SEO } from '../components/common/SEO'

const HomePage = () => {
  const [progressValue, setProgressValue] = useState(72)
  const [messageIndex, setMessageIndex] = useState(0)
  const [statusIndex, setStatusIndex] = useState(0)

  const funMessages = [
    'Building something awesome...',
    'Still working...',
    'Almost there... maybe 😅',
    'Coffee break... ☕',
    'Compiling the impossible...',
    'Debugging the debugger...',
    'Teaching AI to code... 🤖'
  ]

  const statusMessages = [
    'Our developers are arguing with the code...',
    'Convincing the servers to cooperate...',
    'Reticulating splines...',
    'Swapping time and space...',
    'Spinning violently around the y-axis...',
    'Tokenizing real life...',
    'Bending the spoon...'
  ]

  // Rotate loading messages
  useEffect(() => {
    const interval = setInterval(() => {
      setMessageIndex((prev) => (prev + 1) % funMessages.length)
    }, 3000)
    return () => clearInterval(interval)
  }, [])

  // Rotate status messages
  useEffect(() => {
    const interval = setInterval(() => {
      setStatusIndex((prev) => (prev + 1) % statusMessages.length)
    }, 4500)
    return () => clearInterval(interval)
  }, [])

  // Animate progress bar
  useEffect(() => {
    const interval = setInterval(() => {
      setProgressValue((prev) => {
        const increment = Math.random() * 8 + 2
        const newValue = prev + increment
        return newValue > 99 ? 99 : newValue
      })
    }, 1500)
    return () => clearInterval(interval)
  }, [])

  // Check for reduced motion preference
  const prefersReducedMotion =
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches

  const floatingElements = [
    { icon: '⚠️', delay: 0, duration: 6 },
    { icon: '🔧', delay: 1, duration: 7 },
    { icon: '🛠️', delay: 2, duration: 8 },
    { icon: '💻', delay: 3, duration: 6.5 }
  ]

  return (
    <>
      <SEO
        title="Home"
        description="Our website is currently under construction. Please come back later."
        url="/"
      />
      <div className="fixed inset-0 bg-gradient-to-b from-slate-50 via-blue-50 to-slate-100 flex items-center justify-center px-4 py-12 w-screen h-screen overflow-hidden">
        {/* Floating background elements */}
        {!prefersReducedMotion && (
          <div className="absolute inset-0 pointer-events-none">
            {floatingElements.map((element, idx) => (
              <motion.div
                key={idx}
                className="absolute text-3xl md:text-4xl opacity-10"
                initial={{
                  x: Math.random() * window.innerWidth,
                  y: Math.random() * window.innerHeight
                }}
                animate={{
                  x: [
                    Math.random() * window.innerWidth,
                    Math.random() * window.innerWidth,
                    Math.random() * window.innerWidth
                  ],
                  y: [
                    Math.random() * window.innerHeight,
                    Math.random() * window.innerHeight,
                    Math.random() * window.innerHeight
                  ]
                }}
                transition={{
                  duration: element.duration,
                  delay: element.delay,
                  repeat: Infinity,
                  ease: 'easeInOut'
                }}
              >
                {element.icon}
              </motion.div>
            ))}
          </div>
        )}

        <div className="max-w-2xl w-full text-center relative z-10">
          {/* Logo with entrance and float animation */}
          <motion.div
            className="mb-8 flex justify-center"
            initial={prefersReducedMotion ? { opacity: 1 } : { opacity: 0, y: -30 }}
            animate={prefersReducedMotion ? { opacity: 1 } : { opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
          >
            <motion.img
              src="https://bornosoft.bd/images/logo.jpeg"
              alt="Bornosoft Logo"
              className="h-24 w-auto object-contain"
              animate={prefersReducedMotion ? {} : { y: [0, -8, 0] }}
              transition={{
                duration: 3,
                repeat: Infinity,
                ease: 'easeInOut'
              }}
            />
          </motion.div>

          {/* Heading */}
          <motion.h1
            className="text-4xl md:text-5xl font-bold text-slate-900 mb-4"
            initial={prefersReducedMotion ? { opacity: 1 } : { opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.8, delay: 0.2 }}
          >
            Website Under Construction
          </motion.h1>

          {/* Message */}
          <motion.p
            className="text-lg md:text-xl text-slate-600 mb-8 leading-relaxed"
            initial={prefersReducedMotion ? { opacity: 1 } : { opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.8, delay: 0.4 }}
          >
            Our website is currently under construction. Please come back later.
          </motion.p>

          {/* Construction section with animations */}
          <motion.div
            className="bg-white rounded-lg shadow-md p-8 mb-8 border-2 border-slate-100"
            initial={prefersReducedMotion ? { opacity: 1 } : { opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ duration: 0.8, delay: 0.6 }}
          >
            {/* Fun loading message with fade effect */}
            <motion.div
              key={messageIndex}
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.5 }}
              className="text-slate-700 font-semibold mb-4 text-base md:text-lg h-6"
            >
              {funMessages[messageIndex]}
            </motion.div>

            {/* Animated progress bar */}
            <div className="w-full bg-slate-200 rounded-full h-2 mb-4 overflow-hidden">
              <motion.div
                className="h-full bg-gradient-to-r from-blue-500 via-blue-600 to-blue-500 rounded-full"
                initial={{ width: '72%' }}
                animate={{ width: `${progressValue}%` }}
                transition={{ duration: 0.5 }}
              />
            </div>

            <p className="text-sm text-slate-500 mb-6 h-5">
              {Math.floor(progressValue)}% complete (probably)
            </p>

            {/* Funny status message with fade effect */}
            <motion.div
              key={statusIndex}
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.5 }}
              className="text-slate-600 text-sm md:text-base italic mb-6 h-6 text-center"
            >
              📝 {statusMessages[statusIndex]}
            </motion.div>

            {/* Contact buttons */}
            <p className="text-slate-700 font-semibold mb-6 text-lg">
              Please contact with Bornosoft
            </p>

            <div className="space-y-4 md:space-y-0 md:flex md:items-center md:justify-center md:gap-8">
              {/* Website Link */}
              <motion.a
                href="https://bornosoft.bd"
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-3 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200"
                whileHover={prefersReducedMotion ? {} : { scale: 1.05 }}
                whileTap={prefersReducedMotion ? {} : { scale: 0.98 }}
              >
                <Globe size={20} />
                <span>bornosoft.bd</span>
              </motion.a>

              {/* Email Link */}
              <motion.a
                href="mailto:contact@bornosoft.bd"
                className="inline-flex items-center gap-3 px-6 py-3 bg-slate-200 hover:bg-slate-300 text-slate-900 rounded-lg font-medium transition-colors duration-200"
                whileHover={prefersReducedMotion ? {} : { scale: 1.05 }}
                whileTap={prefersReducedMotion ? {} : { scale: 0.98 }}
              >
                <Mail size={20} />
                <span>contact@bornosoft.bd</span>
              </motion.a>
            </div>
          </motion.div>

          {/* Footer Note with subtle animation */}
          <motion.p
            className="text-sm text-slate-500"
            initial={prefersReducedMotion ? { opacity: 1 } : { opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.8, delay: 1 }}
          >
            Thank you for your patience. We're working hard to bring you an amazing experience.
          </motion.p>

          {/* Animated construction worker/hammer icon */}
          {!prefersReducedMotion && (
            <motion.div
              className="mt-6 flex justify-center text-4xl"
              animate={{ rotate: [0, 15, -15, 0] }}
              transition={{
                duration: 1.5,
                repeat: Infinity,
                ease: 'easeInOut'
              }}
            >
              🔨
            </motion.div>
          )}
        </div>
      </div>
    </>
  )
}

export default HomePage
