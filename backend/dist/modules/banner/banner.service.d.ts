import { CreateBannerInput, UpdateBannerInput } from "./banner.interface";
export declare const getAll: () => Promise<{
    id: number;
    image: string;
    status: "active" | "inactive";
    createdAt: Date;
    link: string | null;
    title: string | null;
    subtitle: string | null;
    imageMobile: string | null;
    imageTablet: string | null;
    position: "banner" | "hero" | "promo" | "sidebar";
    buttonText: string | null;
    priority: number;
    updatedAt: Date;
}[]>;
export declare const getById: (id: number) => Promise<{
    id: number;
    image: string;
    status: "active" | "inactive";
    createdAt: Date;
    link: string | null;
    title: string | null;
    subtitle: string | null;
    imageMobile: string | null;
    imageTablet: string | null;
    position: "banner" | "hero" | "promo" | "sidebar";
    buttonText: string | null;
    priority: number;
    updatedAt: Date;
}>;
export declare const create: (data: CreateBannerInput) => Promise<{
    id: number;
    image: string;
    status: "active" | "inactive";
    createdAt: Date;
    link: string | null;
    title: string | null;
    subtitle: string | null;
    imageMobile: string | null;
    imageTablet: string | null;
    position: "banner" | "hero" | "promo" | "sidebar";
    buttonText: string | null;
    priority: number;
    updatedAt: Date;
}>;
export declare const update: (id: number, data: UpdateBannerInput) => Promise<{
    id: number;
    image: string;
    status: "active" | "inactive";
    createdAt: Date;
    link: string | null;
    title: string | null;
    subtitle: string | null;
    imageMobile: string | null;
    imageTablet: string | null;
    position: "banner" | "hero" | "promo" | "sidebar";
    buttonText: string | null;
    priority: number;
    updatedAt: Date;
}>;
export declare const remove: (id: number) => Promise<{
    success: boolean;
}>;
//# sourceMappingURL=banner.service.d.ts.map