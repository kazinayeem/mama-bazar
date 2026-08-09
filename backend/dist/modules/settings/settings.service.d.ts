export declare const getAll: () => Promise<{
    id: number;
    value: string | null;
    key: string;
}[]>;
export declare const get: (key: string) => Promise<{
    id: number;
    value: string | null;
    key: string;
}>;
export declare const set: (key: string, value: string | null) => Promise<{
    id: number;
    value: string | null;
    key: string;
}>;
export declare const getJSON: <T>(key: string, fallback: T) => Promise<T>;
export declare const setJSON: <T>(key: string, value: T) => Promise<void>;
//# sourceMappingURL=settings.service.d.ts.map