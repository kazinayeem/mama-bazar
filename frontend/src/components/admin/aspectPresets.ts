export interface AspectPreset {
  label: string
  value: number
}

export const ASPECT_PRESETS: Record<string, AspectPreset> = {
  square: { label: '1:1 — square', value: 1 },
  landscape: { label: '16:9 — wide', value: 16 / 9 },
  hero: { label: '16:9 — hero', value: 16 / 9 },
  banner: { label: '16:9 — banner', value: 16 / 9 },
  mobile: { label: '4:5 — mobile', value: 4 / 5 },
  free: { label: 'Free', value: 0 },
}
