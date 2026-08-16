import { motion, useReducedMotion } from 'framer-motion'
import type { ReactNode } from 'react'
import { PAGE_TRANSITION } from '../../lib/motion'

interface PageTransitionProps {
  children: ReactNode
}

const PageTransition = ({ children }: PageTransitionProps) => {
  const reduceMotion = useReducedMotion()

  if (reduceMotion) {
    return <>{children}</>
  }

  return (
    <motion.div
      animate={{ opacity: 1, y: 0 }}
      initial={{ opacity: 0, y: 10 }}
      transition={PAGE_TRANSITION}
    >
      {children}
    </motion.div>
  )
}

export default PageTransition