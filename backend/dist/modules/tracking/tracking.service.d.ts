import { CreateIntegrationInput, UpdateIntegrationInput, TrackingConfig } from "./tracking.interface";
export declare const getAll: () => Promise<{
    id: number;
    name: string;
    status: "active" | "inactive";
    createdAt: Date;
    type: "google_tag_manager" | "google_analytics" | "facebook_pixel" | "facebook_conversion_api" | "tiktok_pixel" | "custom_script";
    updatedAt: Date;
    pixelId: string | null;
    scriptCode: string | null;
    accessToken: string | null;
    testEventCode: string | null;
}[]>;
export declare const getActive: () => Promise<{
    id: number;
    name: string;
    status: "active" | "inactive";
    createdAt: Date;
    type: "google_tag_manager" | "google_analytics" | "facebook_pixel" | "facebook_conversion_api" | "tiktok_pixel" | "custom_script";
    updatedAt: Date;
    pixelId: string | null;
    scriptCode: string | null;
    accessToken: string | null;
    testEventCode: string | null;
}[]>;
export declare const getById: (id: number) => Promise<{
    id: number;
    name: string;
    status: "active" | "inactive";
    createdAt: Date;
    type: "google_tag_manager" | "google_analytics" | "facebook_pixel" | "facebook_conversion_api" | "tiktok_pixel" | "custom_script";
    updatedAt: Date;
    pixelId: string | null;
    scriptCode: string | null;
    accessToken: string | null;
    testEventCode: string | null;
}>;
export declare const getTrackingConfig: () => Promise<TrackingConfig>;
export declare const create: (data: CreateIntegrationInput) => Promise<{
    id: number;
    name: string;
    status: "active" | "inactive";
    createdAt: Date;
    type: "google_tag_manager" | "google_analytics" | "facebook_pixel" | "facebook_conversion_api" | "tiktok_pixel" | "custom_script";
    updatedAt: Date;
    pixelId: string | null;
    scriptCode: string | null;
    accessToken: string | null;
    testEventCode: string | null;
}>;
export declare const update: (id: number, data: UpdateIntegrationInput) => Promise<{
    id: number;
    name: string;
    status: "active" | "inactive";
    createdAt: Date;
    type: "google_tag_manager" | "google_analytics" | "facebook_pixel" | "facebook_conversion_api" | "tiktok_pixel" | "custom_script";
    updatedAt: Date;
    pixelId: string | null;
    scriptCode: string | null;
    accessToken: string | null;
    testEventCode: string | null;
}>;
export declare const remove: (id: number) => Promise<{
    success: boolean;
}>;
export declare const logEvent: (eventName: string, platform: string, payload: any, status?: "success" | "failed", errorMessage?: string) => Promise<void>;
export declare const getRecentLogs: (limit?: number) => Promise<{
    id: number;
    status: "success" | "failed";
    createdAt: Date;
    eventName: string;
    platform: string;
    payload: Record<string, any> | null;
    errorMessage: string | null;
}[]>;
//# sourceMappingURL=tracking.service.d.ts.map