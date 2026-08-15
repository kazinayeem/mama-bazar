import { Users } from 'lucide-react'
import { cn } from '@/lib/utils'

interface TeamMember {
  name: string
  position: string
  role: string
  image?: string
  initials: string
}

const TEAM_MEMBERS: TeamMember[] = [
  {
    name: 'Al Amin Sarker Dolon',
    position: 'Founder & CEO',
    role: 'Chief Executive Officer',
    image: '/dolon.jpeg',
    initials: 'AD',
  },
  {
    name: 'Mohammad Ali Nayeem',
    position: 'Co-Founder & COO',
    role: 'Chief Operating Officer',
    image: '/nayeem.jpeg',
    initials: 'MN',
  },
  {
    name: 'Riaz Ahmed Roni',
    position: 'Head of GM',
    role: 'General Manager',
    image: '/roni.jpeg',
    initials: 'RR',
  },
  {
    name: 'Shipon Ahmed',
    position: 'Head of Marketing',
    role: 'Head of Marketing Officer',
    image: '/shipon.jpeg',
    initials: 'SA',
  },
    {
    name: 'Abdullah Al Safi',
    position: 'Brand Communication Executive',
    role: 'Brand Communication Executive',
    image: '/safi.jpeg',
    initials: 'BCE',
  },
]

const TeamCard = ({ member }: { member: TeamMember }) => {
  return (
    <div className="flex flex-col items-center rounded-2xl border border-brand-green-100 bg-white p-5 text-center shadow-soft transition-all duration-200 hover:-translate-y-1 hover:border-brand-green-200 hover:shadow-card">
      <div className="relative h-28 w-28 overflow-hidden rounded-full bg-brand-green-50 ring-2 ring-brand-green-100">
        {member.image ? (
          <img
            alt={member.name}
            className="h-full w-full object-cover"
            loading="lazy"
            src={member.image}
          />
        ) : (
          <div className="flex h-full w-full items-center justify-center bg-brand-green-50">
            <span className="font-headline text-2xl font-extrabold text-brand-green-600">{member.initials}</span>
          </div>
        )}
      </div>
      <h3 className="mt-4 font-headline text-base font-extrabold text-brand-green-700">{member.name}</h3>
      <p className="mt-1 text-xs font-bold uppercase tracking-[0.14em] text-brand-orange-500">{member.position}</p>
      <p className={cn('mt-1.5 text-[13px] leading-5 text-slate-500')}>{member.role}</p>
    </div>
  )
}

const TeamSection = () => {
  return (
    <section id="our-team" className="scroll-mt-28">
      <div className="flex items-center gap-2">
        <Users className="h-5 w-5 text-brand-orange-500" />
        <div>
          <h2 className="text-xl font-extrabold text-brand-green-700">Our Team</h2>
          <p className="mt-0.5 text-[11px] font-bold uppercase tracking-[0.15em] text-brand-orange-500">
            আমাদের টিম
          </p>
        </div>
      </div>
      <p className="mt-3 text-[15px] leading-7 text-slate-700">
        The people behind MamaBazar — a team committed to serving you with honesty and care.
      </p>
      <p className="mt-1 text-[13px] leading-6 text-slate-500">
        MamaBazar-এর পেছনের টিম — যারা সততা ও যত্নের সাথে আপনাকে সেবা দিতে প্রতিশ্রুতিবদ্ধ।
      </p>

      <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {TEAM_MEMBERS.map((member) => (
          <TeamCard key={member.name} member={member} />
        ))}
      </div>
    </section>
  )
}

export default TeamSection