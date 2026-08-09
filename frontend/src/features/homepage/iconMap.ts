import {
  BadgeCheck,
  Boxes,
  CreditCard,
  Headphones,
  HeartHandshake,
  Lock,
  Package,
  RefreshCcw,
  RotateCcw,
  ShieldCheck,
  Sparkles,
  ThumbsUp,
  Truck,
  Wallet,
  Zap,
  type LucideIcon,
} from 'lucide-react'

const ICONS: Record<string, LucideIcon> = {
  Truck,
  Zap,
  ShieldCheck,
  BadgeCheck,
  RefreshCcw,
  RotateCcw,
  Headphones,
  CreditCard,
  Wallet,
  Lock,
  Boxes,
  Package,
  Sparkles,
  ThumbsUp,
  HeartHandshake,
}

export const iconByName = (name?: string, fallback: LucideIcon = BadgeCheck): LucideIcon => {
  if (!name) return fallback
  return ICONS[name] || fallback
}
