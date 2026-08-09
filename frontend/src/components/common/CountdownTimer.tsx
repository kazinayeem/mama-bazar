import { useEffect, useState } from 'react'

const pad = (value: number) => String(value).padStart(2, '0')

const CountdownTimer = ({ targetHours = 9 }: { targetHours?: number }) => {
  const [secondsLeft, setSecondsLeft] = useState(targetHours * 3600)

  useEffect(() => {
    const timer = setInterval(() => {
      setSecondsLeft((current) => (current > 0 ? current - 1 : targetHours * 3600))
    }, 1000)
    return () => clearInterval(timer)
  }, [targetHours])

  const hours = Math.floor(secondsLeft / 3600)
  const minutes = Math.floor((secondsLeft % 3600) / 60)
  const seconds = secondsLeft % 60

  const cells = [
    { label: 'Hours', value: pad(hours) },
    { label: 'Minutes', value: pad(minutes) },
    { label: 'Seconds', value: pad(seconds) },
  ]

  return (
    <div className="flex items-center gap-2" role="timer" aria-label="Deal countdown">
      {cells.map((cell, index) => (
        <div className="flex items-center gap-2" key={cell.label}>
          <div className="flex min-w-[52px] flex-col items-center rounded-xl bg-slate-950 px-2 py-2 text-white shadow-soft dark:bg-slate-800">
            <span className="font-mono text-xl font-extrabold leading-none sm:text-2xl">{cell.value}</span>
            <span className="mt-1 text-[9px] font-semibold uppercase tracking-[0.15em] text-slate-400">{cell.label}</span>
          </div>
          {index < cells.length - 1 && <span className="pb-4 font-mono text-xl font-extrabold text-slate-400">:</span>}
        </div>
      ))}
    </div>
  )
}

export default CountdownTimer
