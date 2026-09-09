import { Mail, Globe } from 'lucide-react'
import { motion } from 'framer-motion'
import { useState, useEffect } from 'react'
import { SEO } from '../components/common/SEO'

const HomePage = () => {
  const [messageIndex, setMessageIndex] = useState(0)
  const [terminalLines, setTerminalLines] = useState<string[]>([])
  const [showCursor, setShowCursor] = useState(true)

  const buildMessages = [
    'Compiling something awesome...',
    'React is thinking...',
    'Next.js is doing its thing...',
    'Removing bugs...',
    'Adding more bugs...',
    'Developer is searching Stack Overflow...',
    'One tiny change broke everything...',
    'Almost ready... probably.',
    'Making it work by Thursday...',
    'Code is compiling...'
  ]

  const terminalOutput = [
    '> initializing project...',
    '> loading components...',
    '> compiling React...',
    '> optimizing Next.js...',
    '> fixing one more bug...',
    '> ERROR: developer needs coffee',
    '> coffee found ✓',
    '> deployment: pending...'
  ]

  // Rotate build messages
  useEffect(() => {
    const interval = setInterval(() => {
      setMessageIndex((prev) => (prev + 1) % buildMessages.length)
    }, 3000)
    return () => clearInterval(interval)
  }, [])

  // Terminal typing animation
  useEffect(() => {
    let lineIndex = 0
    const addLine = () => {
      if (lineIndex < terminalOutput.length) {
        setTerminalLines((prev) => [...prev, terminalOutput[lineIndex]])
        lineIndex++
        setTimeout(addLine, 500)
      }
    }
    addLine()
  }, [])

  // Cursor blink
  useEffect(() => {
    const interval = setInterval(() => {
      setShowCursor((prev) => !prev)
    }, 530)
    return () => clearInterval(interval)
  }, [])

  const prefersReducedMotion =
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches

  // Developer icons that orbit
  const orbitingIcons = [
    { icon: '⚛️', label: 'React', delay: 0, radius: 120, duration: 12 },
    { icon: '▲', label: 'Next.js', delay: 1, radius: 120, duration: 14 },
    { icon: '</>', label: 'Code', delay: 2, radius: 100, duration: 16 },
    { icon: '⌨️', label: 'Terminal', delay: 3, radius: 100, duration: 18 },
    { icon: '{}', label: 'Brackets', delay: 0.5, radius: 85, duration: 15 },
    { icon: '�', label: 'Package', delay: 1.5, radius: 85, duration: 17 },
    { icon: '🌿', label: 'Git', delay: 2.5, radius: 95, duration: 20 },
    { icon: '💻', label: 'Dev', delay: 3.5, radius: 110, duration: 13 }
  ]

  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: { staggerChildren: 0.1, delayChildren: 0.2 }
    }
  }

  const itemVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.6 } }
  }

  return (
    <>
      <SEO
        title="Home"
        description="Our website is currently under construction. We're building something awesome!"
        url="/"
      />

      {/* Dark background with animated grid */}
      <div className="fixed inset-0 bg-black overflow-hidden w-screen h-screen">
        {/* Animated grid background */}
        {!prefersReducedMotion && (
          <div className="absolute inset-0 opacity-10">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
              <defs>
                <pattern
                  id="grid"
                  width="40"
                  height="40"
                  patternUnits="userSpaceOnUse"
                >
                  <path
                    d="M 40 0 L 0 0 0 40"
                    fill="none"
                    stroke="#00ff88"
                    strokeWidth="0.5"
                  />
                </pattern>
                <linearGradient id="gridGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" stopColor="#00ff88" stopOpacity="0.1" />
                  <stop offset="100%" stopColor="#00ff88" stopOpacity="0" />
                </linearGradient>
              </defs>
              <rect width="100%" height="100%" fill="url(#grid)" />
              <rect width="100%" height="100%" fill="url(#gridGrad)" />
            </svg>
          </div>
        )}

        {/* Floating particles */}
        {!prefersReducedMotion && (
          <div className="absolute inset-0">
            {Array.from({ length: 15 }).map((_, i) => (
              <motion.div
                key={`particle-${i}`}
                className="absolute w-1 h-1 bg-cyan-400 rounded-full opacity-30"
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
                  duration: 15 + Math.random() * 10,
                  repeat: Infinity,
                  ease: 'linear'
                }}
              />
            ))}
          </div>
        )}

        {/* Main content */}
        <div className="relative z-10 min-h-screen flex items-center justify-center px-4 py-12">
          <motion.div
            className="max-w-3xl w-full"
            variants={containerVariants}
            initial="hidden"
            animate="visible"
          >
            {/* Orbiting icons background */}
            {!prefersReducedMotion && (
              <div className="absolute inset-0 -z-10">
                {orbitingIcons.map((item, idx) => {
                  const angle = (idx * 360) / orbitingIcons.length
                  return (
                    <motion.div
                      key={idx}
                      className="absolute text-2xl md:text-3xl"
                      style={{
                        left: '50%',
                        top: '50%',
                        transformOrigin: `${item.radius}px 0px`
                      }}
                      animate={{ rotate: 360 }}
                      transition={{
                        duration: item.duration,
                        repeat: Infinity,
                        ease: 'linear',
                        delay: item.delay
                      }}
                    >
                      <motion.div
                        className="filter drop-shadow-lg"
                        style={{
                          transform: `translate(-50%, -50%) rotate(${angle}deg)`
                        }}
                        animate={
                          item.icon === '⚛️'
                            ? { scale: [1, 1.2, 1] }
                            : item.icon === '▲'
                              ? { scale: [1.2, 1, 1.2] }
                              : {}
                        }
                        transition={{
                          duration: 2,
                          repeat: Infinity,
                          ease: 'easeInOut'
                        }}
                      >
                        {item.icon}
                      </motion.div>
                    </motion.div>
                  )
                })}
              </div>
            )}

            {/* Logo */}
            <motion.div
              className="mb-8 flex justify-center"
              variants={itemVariants}
            >
              <motion.img
                src="https://bornosoft.bd/images/logo.jpeg"
                alt="Bornosoft Logo"
                className="h-20 w-auto object-contain filter drop-shadow-2xl"
                animate={
                  prefersReducedMotion
                    ? {}
                    : { y: [0, -10, 0], scale: [1, 1.05, 1] }
                }
                transition={{
                  duration: 4,
                  repeat: Infinity,
                  ease: 'easeInOut'
                }}
              />
            </motion.div>

            {/* Glitch heading */}
            <motion.div variants={itemVariants} className="relative mb-6">
              <div className="text-center">
                <motion.h1
                  className="text-4xl md:text-6xl font-bold text-center relative inline-block w-full"
                  style={{
                    textShadow:
                      '0 0 20px rgba(0, 255, 136, 0.5), 0 0 40px rgba(0, 255, 200, 0.3)',
                    color: '#00ff88'
                  }}
                >
                  {!prefersReducedMotion && (
                    <motion.div
                      className="absolute inset-0"
                      animate={{ opacity: [0, 0.5, 0] }}
                      transition={{
                        duration: 0.1,
                        repeat: Infinity,
                        repeatDelay: 4
                      }}
                      style={{
                        textShadow:
                          '3px 3px 0px rgba(255, 0, 200, 0.7), -3px -3px 0px rgba(0, 255, 255, 0.7)',
                        color: '#ff00c8'
                      }}
                    >
                      Website Under Construction
                    </motion.div>
                  )}
                  Website Under Construction
                </motion.h1>
              </div>
            </motion.div>

            {/* Supporting text */}
            <motion.p
              variants={itemVariants}
              className="text-center text-lg md:text-xl text-cyan-300 mb-8 font-light tracking-wide"
              style={{ textShadow: '0 0 10px rgba(0, 255, 200, 0.3)' }}
            >
              We're currently compiling something awesome. Please check back later.
            </motion.p>

            {/* Terminal panel */}
            <motion.div
              variants={itemVariants}
              className="mb-8 p-6 rounded border border-green-500 bg-black/50 backdrop-blur font-mono text-sm md:text-base"
              style={{
                boxShadow:
                  '0 0 20px rgba(0, 255, 136, 0.3), inset 0 0 10px rgba(0, 255, 136, 0.1)'
              }}
            >
              <div className="space-y-2 text-green-400">
                {terminalLines.map((line, idx) => (
                  <motion.div
                    key={idx}
                    initial={prefersReducedMotion ? {} : { opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ duration: 0.3 }}
                  >
                    {line}
                  </motion.div>
                ))}
                {terminalLines.length < terminalOutput.length && (
                  <div className="text-green-400">
                    {showCursor ? '▌' : ' '}
                  </div>
                )}
              </div>
            </motion.div>

            {/* Build status card */}
            <motion.div
              variants={itemVariants}
              className="mb-8 p-8 rounded border border-cyan-500 bg-gradient-to-br from-blue-900/20 to-purple-900/20 backdrop-blur"
              style={{
                boxShadow:
                  '0 0 30px rgba(0, 255, 255, 0.2), inset 0 0 20px rgba(0, 255, 255, 0.05)'
              }}
            >
              {/* Build status */}
              <div className="text-center mb-6">
                <p className="text-cyan-300 font-mono text-sm uppercase tracking-widest mb-3">
                  Build Status
                </p>
                <motion.div
                  className="text-3xl md:text-4xl font-bold text-green-400"
                  animate={prefersReducedMotion ? {} : { scale: [1, 1.05, 1] }}
                  transition={{ duration: 1.5, repeat: Infinity }}
                  style={{
                    textShadow: '0 0 10px rgba(0, 255, 136, 0.6)'
                  }}
                >
                  99%
                </motion.div>
              </div>

              {/* Rotating messages */}
              <motion.div
                key={messageIndex}
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -10 }}
                transition={{ duration: 0.5 }}
                className="text-center text-cyan-300 text-lg md:text-xl mb-6 h-8"
                style={{
                  textShadow: '0 0 10px rgba(0, 255, 200, 0.4)'
                }}
              >
                {buildMessages[messageIndex]}
              </motion.div>

              {/* Animated progress bar */}
              <div className="w-full bg-gray-900 rounded-full h-2 overflow-hidden border border-cyan-500/30">
                <motion.div
                  className="h-full bg-gradient-to-r from-cyan-500 via-green-400 to-cyan-500"
                  initial={{ width: '90%' }}
                  animate={{ width: ['90%', '99%', '90%'] }}
                  transition={{ duration: 3, repeat: Infinity }}
                  style={{
                    filter: 'drop-shadow(0 0 8px rgba(0, 255, 200, 0.8))'
                  }}
                />
              </div>
            </motion.div>

            {/* Contact section */}
            <motion.div variants={itemVariants} className="space-y-6">
              <p
                className="text-center text-cyan-300 font-mono text-sm uppercase tracking-widest"
                style={{
                  textShadow: '0 0 10px rgba(0, 255, 200, 0.3)'
                }}
              >
                Please contact with Bornosoft
              </p>

              <div className="flex flex-col md:flex-row gap-4 justify-center">
                {/* Website button */}
                <motion.a
                  href="https://bornosoft.bd"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center justify-center gap-3 px-8 py-3 rounded border-2 border-cyan-500 bg-black/50 text-cyan-300 font-semibold transition-all"
                  whileHover={
                    prefersReducedMotion
                      ? {}
                      : {
                          scale: 1.05,
                          boxShadow:
                            '0 0 20px rgba(0, 255, 200, 0.6), inset 0 0 10px rgba(0, 255, 200, 0.1)'
                        }
                  }
                  whileTap={prefersReducedMotion ? {} : { scale: 0.98 }}
                >
                  <Globe size={20} />
                  <span>bornosoft.bd</span>
                </motion.a>

                {/* Email button */}
                <motion.a
                  href="mailto:contact@bornosoft.bd"
                  className="inline-flex items-center justify-center gap-3 px-8 py-3 rounded border-2 border-purple-500 bg-black/50 text-purple-300 font-semibold transition-all"
                  whileHover={
                    prefersReducedMotion
                      ? {}
                      : {
                          scale: 1.05,
                          boxShadow:
                            '0 0 20px rgba(200, 0, 255, 0.6), inset 0 0 10px rgba(200, 0, 255, 0.1)'
                        }
                  }
                  whileTap={prefersReducedMotion ? {} : { scale: 0.98 }}
                >
                  <Mail size={20} />
                  <span>contact@bornosoft.bd</span>
                </motion.a>
              </div>
            </motion.div>

            {/* Footer note */}
            <motion.p
              variants={itemVariants}
              className="text-center text-sm text-green-500/70 mt-8 font-mono"
              style={{
                textShadow: '0 0 5px rgba(0, 255, 136, 0.2)'
              }}
            >
              // checking back soon will be worth your while
            </motion.p>
          </motion.div>
        </div>
      </div>
    </>
  )
}

export default HomePage
