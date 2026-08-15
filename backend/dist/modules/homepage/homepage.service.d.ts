import type { HomepageConfig } from "./homepage.interface";
export declare const getConfig: () => Promise<HomepageConfig>;
export declare const saveConfig: (config: HomepageConfig) => Promise<HomepageConfig>;
export declare const resetConfig: () => Promise<HomepageConfig>;
export declare const getHomepage: (userId: number | null) => Promise<{
    announcement: {
        enabled: boolean;
        text: string;
        backgroundColor?: string;
        textColor?: string;
    };
    heroSlides: import("./homepage.interface").HomepageHeroSlide[];
    flashSaleWindow: {
        isActive: boolean;
        endsAt: string | null;
        enabled: boolean;
        start?: string | null;
        end?: string | null;
    };
    popularSearches: string[];
    sections: {
        data: any;
        id: string;
        type: import("./homepage.interface").HomepageSectionType;
        enabled: boolean;
        title?: string;
        subtitle?: string;
        eyebrow?: string;
        ctaText?: string;
        ctaUrl?: string;
        limit?: number;
        columns?: number;
        background?: "default" | "muted" | "dark";
        categoryId?: number | null;
        categorySlug?: string | null;
    }[];
}>;
export declare const subscribeNewsletter: (email: string, source?: string) => Promise<{
    email: string;
    alreadySubscribed: boolean;
}>;
export declare const getSubscribers: () => Promise<{
    id: number;
    status: "subscribed" | "unsubscribed";
    email: string;
    source: string | null;
    subscribedAt: Date;
}[]>;
//# sourceMappingURL=homepage.service.d.ts.map