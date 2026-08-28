declare class MemoryCache {
    private store;
    /**
     * Get an item from cache. Returns undefined if expired or not found.
     */
    get<T>(key: string): T | undefined;
    /**
     * Set an item with TTL in seconds (default: 300s / 5 minutes).
     */
    set<T>(key: string, data: T, ttlSeconds?: number): void;
    /**
     * Invalidate a specific key or keys starting with a prefix.
     */
    invalidate(keyOrPrefix: string): void;
    /**
     * Clear entire cache.
     */
    clear(): void;
}
export declare const memoryCache: MemoryCache;
export {};
//# sourceMappingURL=cache.d.ts.map