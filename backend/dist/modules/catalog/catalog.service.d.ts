declare const TABLES: {
    readonly colors: import("drizzle-orm/mysql-core").MySqlTableWithColumns<{
        name: "colors";
        schema: undefined;
        columns: {
            id: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "id";
                tableName: "colors";
                dataType: "number";
                columnType: "MySqlInt";
                data: number;
                driverParam: string | number;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
            name: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "name";
                tableName: "colors";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: true;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            displayName: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "display_name";
                tableName: "colors";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            hex: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "hex";
                tableName: "colors";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: true;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            status: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "status";
                tableName: "colors";
                dataType: "string";
                columnType: "MySqlEnumColumn";
                data: "active" | "inactive" | "archived";
                driverParam: string;
                notNull: true;
                hasDefault: true;
                enumValues: ["active", "inactive", "archived"];
                baseColumn: never;
            }, object>;
            sortOrder: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "sort_order";
                tableName: "colors";
                dataType: "number";
                columnType: "MySqlInt";
                data: number;
                driverParam: string | number;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
            createdAt: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "created_at";
                tableName: "colors";
                dataType: "date";
                columnType: "MySqlTimestamp";
                data: Date;
                driverParam: string | number;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
        };
        dialect: "mysql";
    }>;
    readonly sizes: import("drizzle-orm/mysql-core").MySqlTableWithColumns<{
        name: "sizes";
        schema: undefined;
        columns: {
            id: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "id";
                tableName: "sizes";
                dataType: "number";
                columnType: "MySqlInt";
                data: number;
                driverParam: string | number;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
            name: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "name";
                tableName: "sizes";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: true;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            type: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "type";
                tableName: "sizes";
                dataType: "string";
                columnType: "MySqlEnumColumn";
                data: "custom" | "clothing" | "shoes" | "general";
                driverParam: string;
                notNull: true;
                hasDefault: true;
                enumValues: ["clothing", "shoes", "general", "custom"];
                baseColumn: never;
            }, object>;
            status: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "status";
                tableName: "sizes";
                dataType: "string";
                columnType: "MySqlEnumColumn";
                data: "active" | "inactive" | "archived";
                driverParam: string;
                notNull: true;
                hasDefault: true;
                enumValues: ["active", "inactive", "archived"];
                baseColumn: never;
            }, object>;
            sortOrder: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "sort_order";
                tableName: "sizes";
                dataType: "number";
                columnType: "MySqlInt";
                data: number;
                driverParam: string | number;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
            createdAt: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "created_at";
                tableName: "sizes";
                dataType: "date";
                columnType: "MySqlTimestamp";
                data: Date;
                driverParam: string | number;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
        };
        dialect: "mysql";
    }>;
    readonly collections: import("drizzle-orm/mysql-core").MySqlTableWithColumns<{
        name: "collections";
        schema: undefined;
        columns: {
            id: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "id";
                tableName: "collections";
                dataType: "number";
                columnType: "MySqlInt";
                data: number;
                driverParam: string | number;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
            name: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "name";
                tableName: "collections";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: true;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            slug: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "slug";
                tableName: "collections";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: true;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            description: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "description";
                tableName: "collections";
                dataType: "string";
                columnType: "MySqlText";
                data: string;
                driverParam: string;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            image: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "image";
                tableName: "collections";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            banner: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "banner";
                tableName: "collections";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            featured: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "featured";
                tableName: "collections";
                dataType: "boolean";
                columnType: "MySqlBoolean";
                data: boolean;
                driverParam: number | boolean;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
            homepageVisibility: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "homepage_visibility";
                tableName: "collections";
                dataType: "boolean";
                columnType: "MySqlBoolean";
                data: boolean;
                driverParam: number | boolean;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
            sortOrder: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "sort_order";
                tableName: "collections";
                dataType: "number";
                columnType: "MySqlInt";
                data: number;
                driverParam: string | number;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
            startDate: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "start_date";
                tableName: "collections";
                dataType: "date";
                columnType: "MySqlDateTime";
                data: Date;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
            endDate: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "end_date";
                tableName: "collections";
                dataType: "date";
                columnType: "MySqlDateTime";
                data: Date;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
            status: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "status";
                tableName: "collections";
                dataType: "string";
                columnType: "MySqlEnumColumn";
                data: "active" | "inactive" | "archived";
                driverParam: string;
                notNull: true;
                hasDefault: true;
                enumValues: ["active", "inactive", "archived"];
                baseColumn: never;
            }, object>;
            createdAt: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "created_at";
                tableName: "collections";
                dataType: "date";
                columnType: "MySqlTimestamp";
                data: Date;
                driverParam: string | number;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
        };
        dialect: "mysql";
    }>;
    readonly vendors: import("drizzle-orm/mysql-core").MySqlTableWithColumns<{
        name: "vendors";
        schema: undefined;
        columns: {
            id: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "id";
                tableName: "vendors";
                dataType: "number";
                columnType: "MySqlInt";
                data: number;
                driverParam: string | number;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
            name: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "name";
                tableName: "vendors";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: true;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            slug: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "slug";
                tableName: "vendors";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: true;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            logo: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "logo";
                tableName: "vendors";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            description: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "description";
                tableName: "vendors";
                dataType: "string";
                columnType: "MySqlText";
                data: string;
                driverParam: string;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            contact: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "contact";
                tableName: "vendors";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            phone: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "phone";
                tableName: "vendors";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            email: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "email";
                tableName: "vendors";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            address: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "address";
                tableName: "vendors";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            notes: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "notes";
                tableName: "vendors";
                dataType: "string";
                columnType: "MySqlText";
                data: string;
                driverParam: string;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            status: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "status";
                tableName: "vendors";
                dataType: "string";
                columnType: "MySqlEnumColumn";
                data: "active" | "inactive" | "archived";
                driverParam: string;
                notNull: true;
                hasDefault: true;
                enumValues: ["active", "inactive", "archived"];
                baseColumn: never;
            }, object>;
            createdAt: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "created_at";
                tableName: "vendors";
                dataType: "date";
                columnType: "MySqlTimestamp";
                data: Date;
                driverParam: string | number;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
        };
        dialect: "mysql";
    }>;
    readonly suppliers: import("drizzle-orm/mysql-core").MySqlTableWithColumns<{
        name: "suppliers";
        schema: undefined;
        columns: {
            id: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "id";
                tableName: "suppliers";
                dataType: "number";
                columnType: "MySqlInt";
                data: number;
                driverParam: string | number;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
            name: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "name";
                tableName: "suppliers";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: true;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            slug: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "slug";
                tableName: "suppliers";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: true;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            logo: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "logo";
                tableName: "suppliers";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            description: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "description";
                tableName: "suppliers";
                dataType: "string";
                columnType: "MySqlText";
                data: string;
                driverParam: string;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            contact: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "contact";
                tableName: "suppliers";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            phone: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "phone";
                tableName: "suppliers";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            email: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "email";
                tableName: "suppliers";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            address: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "address";
                tableName: "suppliers";
                dataType: "string";
                columnType: "MySqlVarChar";
                data: string;
                driverParam: string | number;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            notes: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "notes";
                tableName: "suppliers";
                dataType: "string";
                columnType: "MySqlText";
                data: string;
                driverParam: string;
                notNull: false;
                hasDefault: false;
                enumValues: [string, ...string[]];
                baseColumn: never;
            }, object>;
            status: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "status";
                tableName: "suppliers";
                dataType: "string";
                columnType: "MySqlEnumColumn";
                data: "active" | "inactive" | "archived";
                driverParam: string;
                notNull: true;
                hasDefault: true;
                enumValues: ["active", "inactive", "archived"];
                baseColumn: never;
            }, object>;
            createdAt: import("drizzle-orm/mysql-core").MySqlColumn<{
                name: "created_at";
                tableName: "suppliers";
                dataType: "date";
                columnType: "MySqlTimestamp";
                data: Date;
                driverParam: string | number;
                notNull: true;
                hasDefault: true;
                enumValues: undefined;
                baseColumn: never;
            }, object>;
        };
        dialect: "mysql";
    }>;
};
export type CatalogTableName = keyof typeof TABLES;
export declare const catalogService: {
    list(name: CatalogTableName): Promise<{
        [x: string]: any;
    }[]>;
    listAdmin(name: CatalogTableName, params: {
        page?: number;
        limit?: number;
        search?: string;
        status?: string;
        sort?: string;
    }): Promise<{
        data: {
            [x: string]: any;
        }[];
        pagination: {
            page: number;
            limit: number;
            total: number;
            totalPages: number;
        };
    }>;
    getById(name: CatalogTableName, id: number): Promise<{
        [x: string]: any;
    }>;
    create(name: CatalogTableName, data: Record<string, unknown>): Promise<{
        [x: string]: any;
    }>;
    update(name: CatalogTableName, id: number, data: Record<string, unknown>): Promise<{
        [x: string]: any;
    }>;
    remove(name: CatalogTableName, id: number): Promise<{
        success: boolean;
    }>;
    getUsage(name: CatalogTableName, id: number, valueName?: string): Promise<number>;
    move(name: CatalogTableName, id: number, targetId: number | null, valueName?: string): Promise<{
        moved: number;
    }>;
};
export {};
//# sourceMappingURL=catalog.service.d.ts.map