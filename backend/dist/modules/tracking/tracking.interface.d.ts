export type IntegrationType = "google_tag_manager" | "google_analytics" | "facebook_pixel" | "facebook_conversion_api" | "tiktok_pixel" | "custom_script";
export interface IMarketingIntegration {
    id: number;
    name: string;
    type: IntegrationType;
    pixelId?: string | null;
    scriptCode?: string | null;
    accessToken?: string | null;
    testEventCode?: string | null;
    status: "active" | "inactive";
    createdAt: Date;
    updatedAt: Date;
}
export interface CreateIntegrationInput {
    name: string;
    type: IntegrationType;
    pixelId?: string;
    scriptCode?: string;
    accessToken?: string;
    testEventCode?: string;
    status?: "active" | "inactive";
}
export interface UpdateIntegrationInput {
    name?: string;
    type?: IntegrationType;
    pixelId?: string;
    scriptCode?: string;
    accessToken?: string;
    testEventCode?: string;
    status?: "active" | "inactive";
}
export interface TrackingConfig {
    gtmId?: string;
    gaMeasurementId?: string;
    facebookPixelId?: string;
    facebookAccessToken?: string;
    facebookTestEventCode?: string;
    tiktokPixelId?: string;
    customHeadScripts: string[];
    customBodyScripts: string[];
}
//# sourceMappingURL=tracking.interface.d.ts.map