export type HomepageSectionType = "hero" | "trust_strip" | "categories" | "category_products" | "promo_banner" | "flash_deals" | "featured" | "best_sellers" | "brands" | "collections" | "trending" | "new_arrivals" | "limited_edition" | "official" | "hot_deals" | "emi_available" | "recommendations" | "why_choose_us" | "reviews" | "newsletter";
export interface HomepageHeroSlide {
    id: string;
    desktopImage: string;
    tabletImage?: string;
    mobileImage?: string;
    badge?: string;
    title?: string;
    subtitle?: string;
    description?: string;
    primaryButtonText?: string;
    primaryButtonUrl?: string;
    secondaryButtonText?: string;
    secondaryButtonUrl?: string;
    backgroundColor?: string;
    textColor?: string;
    overlay?: boolean;
    overlayOpacity?: number;
    alignment?: "left" | "center" | "right";
    status: "active" | "inactive";
    priority: number;
}
export interface HomepageSectionConfig {
    id: string;
    type: HomepageSectionType;
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
}
export interface HomepageContentItem {
    icon?: string;
    title: string;
    text?: string;
}
export interface HomepageConfig {
    announcement: {
        enabled: boolean;
        text: string;
        backgroundColor?: string;
        textColor?: string;
    };
    heroSlides: HomepageHeroSlide[];
    sections: HomepageSectionConfig[];
    trustStrip: HomepageContentItem[];
    whyChooseUs: HomepageContentItem[];
    newsletter: {
        enabled: boolean;
        title?: string;
        subtitle?: string;
        buttonText?: string;
    };
    flashSaleWindow: {
        enabled: boolean;
        start?: string | null;
        end?: string | null;
    };
    popularSearches: string[];
}
//# sourceMappingURL=homepage.interface.d.ts.map