import { CreateMediaInput, MediaQuery } from "./media.interface";
export declare const saveMedia: (input: CreateMediaInput) => Promise<{
    id: number;
    url: string;
    publicId: string | null;
    filename: string;
    mimeType: string;
    size: number;
    width: number | null;
    height: number | null;
    provider: "cloudinary" | "local";
    folder: string;
    alt: string | null;
    createdAt: Date;
    uploaderName: string | null;
}>;
export declare const getAll: (query: MediaQuery) => Promise<{
    data: {
        id: number;
        url: string;
        publicId: string | null;
        filename: string;
        mimeType: string;
        size: number;
        width: number | null;
        height: number | null;
        provider: "cloudinary" | "local";
        folder: string;
        alt: string | null;
        createdAt: Date;
        uploaderName: string | null;
    }[];
    pagination: {
        page: number;
        limit: number;
        total: number;
        totalPages: number;
    };
}>;
export declare const getFolders: () => Promise<{
    name: string;
    count: number;
}[]>;
export declare const getById: (id: number) => Promise<{
    id: number;
    url: string;
    publicId: string | null;
    filename: string;
    mimeType: string;
    size: number;
    width: number | null;
    height: number | null;
    provider: "cloudinary" | "local";
    folder: string;
    alt: string | null;
    createdAt: Date;
    uploaderName: string | null;
}>;
export declare const remove: (id: number) => Promise<{
    success: boolean;
}>;
export declare const updateAlt: (id: number, alt: string) => Promise<{
    id: number;
    url: string;
    publicId: string | null;
    filename: string;
    mimeType: string;
    size: number;
    width: number | null;
    height: number | null;
    provider: "cloudinary" | "local";
    folder: string;
    alt: string | null;
    createdAt: Date;
    uploaderName: string | null;
}>;
//# sourceMappingURL=media.service.d.ts.map