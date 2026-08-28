"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.memoryCache = void 0;
class MemoryCache {
    constructor() {
        this.store = new Map();
    }
    /**
     * Get an item from cache. Returns undefined if expired or not found.
     */
    get(key) {
        const entry = this.store.get(key);
        if (!entry)
            return undefined;
        if (Date.now() > entry.expiresAt) {
            this.store.delete(key);
            return undefined;
        }
        return entry.data;
    }
    /**
     * Set an item with TTL in seconds (default: 300s / 5 minutes).
     */
    set(key, data, ttlSeconds = 300) {
        this.store.set(key, {
            data,
            expiresAt: Date.now() + ttlSeconds * 1000,
        });
    }
    /**
     * Invalidate a specific key or keys starting with a prefix.
     */
    invalidate(keyOrPrefix) {
        for (const key of this.store.keys()) {
            if (key === keyOrPrefix || key.startsWith(`${keyOrPrefix}:`)) {
                this.store.delete(key);
            }
        }
    }
    /**
     * Clear entire cache.
     */
    clear() {
        this.store.clear();
    }
}
exports.memoryCache = new MemoryCache();
//# sourceMappingURL=cache.js.map