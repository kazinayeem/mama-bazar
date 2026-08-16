export interface GeoNode {
  name: string
  bn_name: string
  lat?: string
  long?: string
  districts?: GeoNode[]
  upazilas?: GeoNode[]
  unions?: GeoNode[]
  pourashavas?: GeoNode[]
}

// Preferred English spellings for the 8 divisions (dataset uses legacy spellings).
const DIVISION_ALIASES: Record<string, string> = {
  Chattagram: 'Chattogram',
  Barisal: 'Barishal',
}

let cache: GeoNode[] | null = null
let promise: Promise<GeoNode[]> | null = null

// Loads the complete Bangladesh administrative dataset once and caches it.
// The dataset is code-split into its own chunk via the dynamic JSON import.
export const loadLocations = (): Promise<GeoNode[]> => {
  if (cache) return Promise.resolve(cache)
  if (!promise) {
    promise = import('../data/bangladesh-geo.json').then((mod) => {
      const raw = ((mod.default ?? []) as GeoNode[]) || []
      cache = raw.map((division) => ({ ...division, name: DIVISION_ALIASES[division.name] ?? division.name }))
      return cache
    })
  }
  return promise
}

export const getDivisions = (data: GeoNode[]): string[] => data.map((division) => division.name)

export const getDistricts = (data: GeoNode[], division: string): string[] => {
  const match = data.find((item) => item.name === division)
  return match?.districts?.map((item) => item.name) ?? []
}

export const getUpazilas = (data: GeoNode[], division: string, district: string): string[] => {
  const divisionNode = data.find((item) => item.name === division)
  const match = divisionNode?.districts?.find((item) => item.name === district)
  return match?.upazilas?.map((item) => item.name) ?? []
}

// Union / Municipality / City Corporation / Ward level, sourced from unions + pourashavas.
export const getAreas = (data: GeoNode[], division: string, district: string, upazila: string): string[] => {
  const divisionNode = data.find((item) => item.name === division)
  const districtNode = divisionNode?.districts?.find((item) => item.name === district)
  const match = districtNode?.upazilas?.find((item) => item.name === upazila)
  const unions = match?.unions?.map((item) => item.name) ?? []
  const pourashavas = match?.pourashavas?.map((item) => item.name) ?? []
  return Array.from(new Set([...unions, ...pourashavas]))
}