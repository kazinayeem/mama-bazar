interface CacheEntry<T> {
  data: T
  expiresAt: number
}

class MemoryCache {
  private store = new Map<string, CacheEntry<unknown>>()

  /**
   * Get an item from cache. Returns undefined if expired or not found.
   */
  get<T>(key: string): T | undefined {
    const entry = this.store.get(key)
    if (!entry) return undefined

    if (Date.now() > entry.expiresAt) {
      this.store.delete(key)
      return undefined
    }

    return entry.data as T
  }

  /**
   * Set an item with TTL in seconds (default: 300s / 5 minutes).
   */
  set<T>(key: string, data: T, ttlSeconds = 300): void {
    this.store.set(key, {
      data,
      expiresAt: Date.now() + ttlSeconds * 1000,
    })
  }

  /**
   * Invalidate a specific key or keys starting with a prefix.
   */
  invalidate(keyOrPrefix: string): void {
    for (const key of this.store.keys()) {
      if (key === keyOrPrefix || key.startsWith(`${keyOrPrefix}:`)) {
        this.store.delete(key)
      }
    }
  }

  /**
   * Clear entire cache.
   */
  clear(): void {
    this.store.clear()
  }
}

export const memoryCache = new MemoryCache()
