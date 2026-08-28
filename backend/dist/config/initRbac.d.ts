export interface PermissionDefinition {
    code: string;
    module: string;
    label: string;
    description: string;
}
export declare const ALL_PERMISSIONS: PermissionDefinition[];
export declare const ROLE_PRESETS: Record<string, {
    displayName: string;
    description: string;
    permissions: string[];
}>;
export declare function initializeRbac(): Promise<void>;
//# sourceMappingURL=initRbac.d.ts.map