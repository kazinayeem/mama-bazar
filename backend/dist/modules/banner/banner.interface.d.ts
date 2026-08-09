export interface CreateBannerInput {
    title?: string;
    subtitle?: string;
    image: string;
    imageMobile?: string;
    imageTablet?: string;
    link?: string;
    position: "hero" | "banner" | "promo" | "sidebar";
    buttonText?: string;
    priority: number;
    status: "active" | "inactive";
}
export interface UpdateBannerInput {
    title?: string;
    subtitle?: string;
    image?: string;
    imageMobile?: string;
    imageTablet?: string;
    link?: string;
    position?: "hero" | "banner" | "promo" | "sidebar";
    buttonText?: string;
    priority?: number;
    status?: "active" | "inactive";
}
//# sourceMappingURL=banner.interface.d.ts.map