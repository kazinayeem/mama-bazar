import { z } from "zod";
export declare const saveConfigSchema: z.ZodObject<{
    body: z.ZodObject<{
        announcement: z.ZodOptional<z.ZodObject<{
            enabled: z.ZodBoolean;
            text: z.ZodString;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            enabled: z.ZodBoolean;
            text: z.ZodString;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            enabled: z.ZodBoolean;
            text: z.ZodString;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">>>;
        heroSlides: z.ZodOptional<z.ZodArray<z.ZodObject<{
            id: z.ZodString;
            desktopImage: z.ZodString;
            tabletImage: z.ZodOptional<z.ZodString>;
            mobileImage: z.ZodOptional<z.ZodString>;
            badge: z.ZodOptional<z.ZodString>;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            description: z.ZodOptional<z.ZodString>;
            primaryButtonText: z.ZodOptional<z.ZodString>;
            primaryButtonUrl: z.ZodOptional<z.ZodString>;
            secondaryButtonText: z.ZodOptional<z.ZodString>;
            secondaryButtonUrl: z.ZodOptional<z.ZodString>;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
            overlay: z.ZodOptional<z.ZodBoolean>;
            overlayOpacity: z.ZodOptional<z.ZodNumber>;
            alignment: z.ZodOptional<z.ZodEnum<["left", "center", "right"]>>;
            status: z.ZodEnum<["active", "inactive"]>;
            priority: z.ZodNumber;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            id: z.ZodString;
            desktopImage: z.ZodString;
            tabletImage: z.ZodOptional<z.ZodString>;
            mobileImage: z.ZodOptional<z.ZodString>;
            badge: z.ZodOptional<z.ZodString>;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            description: z.ZodOptional<z.ZodString>;
            primaryButtonText: z.ZodOptional<z.ZodString>;
            primaryButtonUrl: z.ZodOptional<z.ZodString>;
            secondaryButtonText: z.ZodOptional<z.ZodString>;
            secondaryButtonUrl: z.ZodOptional<z.ZodString>;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
            overlay: z.ZodOptional<z.ZodBoolean>;
            overlayOpacity: z.ZodOptional<z.ZodNumber>;
            alignment: z.ZodOptional<z.ZodEnum<["left", "center", "right"]>>;
            status: z.ZodEnum<["active", "inactive"]>;
            priority: z.ZodNumber;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            id: z.ZodString;
            desktopImage: z.ZodString;
            tabletImage: z.ZodOptional<z.ZodString>;
            mobileImage: z.ZodOptional<z.ZodString>;
            badge: z.ZodOptional<z.ZodString>;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            description: z.ZodOptional<z.ZodString>;
            primaryButtonText: z.ZodOptional<z.ZodString>;
            primaryButtonUrl: z.ZodOptional<z.ZodString>;
            secondaryButtonText: z.ZodOptional<z.ZodString>;
            secondaryButtonUrl: z.ZodOptional<z.ZodString>;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
            overlay: z.ZodOptional<z.ZodBoolean>;
            overlayOpacity: z.ZodOptional<z.ZodNumber>;
            alignment: z.ZodOptional<z.ZodEnum<["left", "center", "right"]>>;
            status: z.ZodEnum<["active", "inactive"]>;
            priority: z.ZodNumber;
        }, z.ZodTypeAny, "passthrough">>, "many">>;
        sections: z.ZodOptional<z.ZodArray<z.ZodObject<{
            id: z.ZodString;
            type: z.ZodString;
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            eyebrow: z.ZodOptional<z.ZodString>;
            ctaText: z.ZodOptional<z.ZodString>;
            ctaUrl: z.ZodOptional<z.ZodString>;
            limit: z.ZodOptional<z.ZodNumber>;
            columns: z.ZodOptional<z.ZodNumber>;
            background: z.ZodOptional<z.ZodEnum<["default", "muted", "dark"]>>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            id: z.ZodString;
            type: z.ZodString;
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            eyebrow: z.ZodOptional<z.ZodString>;
            ctaText: z.ZodOptional<z.ZodString>;
            ctaUrl: z.ZodOptional<z.ZodString>;
            limit: z.ZodOptional<z.ZodNumber>;
            columns: z.ZodOptional<z.ZodNumber>;
            background: z.ZodOptional<z.ZodEnum<["default", "muted", "dark"]>>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            id: z.ZodString;
            type: z.ZodString;
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            eyebrow: z.ZodOptional<z.ZodString>;
            ctaText: z.ZodOptional<z.ZodString>;
            ctaUrl: z.ZodOptional<z.ZodString>;
            limit: z.ZodOptional<z.ZodNumber>;
            columns: z.ZodOptional<z.ZodNumber>;
            background: z.ZodOptional<z.ZodEnum<["default", "muted", "dark"]>>;
        }, z.ZodTypeAny, "passthrough">>, "many">>;
        trustStrip: z.ZodOptional<z.ZodArray<z.ZodObject<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">>, "many">>;
        whyChooseUs: z.ZodOptional<z.ZodArray<z.ZodObject<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">>, "many">>;
        newsletter: z.ZodOptional<z.ZodObject<{
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            buttonText: z.ZodOptional<z.ZodString>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            buttonText: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            buttonText: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">>>;
        flashSaleWindow: z.ZodOptional<z.ZodObject<{
            enabled: z.ZodBoolean;
            start: z.ZodOptional<z.ZodNullable<z.ZodString>>;
            end: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            enabled: z.ZodBoolean;
            start: z.ZodOptional<z.ZodNullable<z.ZodString>>;
            end: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            enabled: z.ZodBoolean;
            start: z.ZodOptional<z.ZodNullable<z.ZodString>>;
            end: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        }, z.ZodTypeAny, "passthrough">>>;
    }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
        announcement: z.ZodOptional<z.ZodObject<{
            enabled: z.ZodBoolean;
            text: z.ZodString;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            enabled: z.ZodBoolean;
            text: z.ZodString;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            enabled: z.ZodBoolean;
            text: z.ZodString;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">>>;
        heroSlides: z.ZodOptional<z.ZodArray<z.ZodObject<{
            id: z.ZodString;
            desktopImage: z.ZodString;
            tabletImage: z.ZodOptional<z.ZodString>;
            mobileImage: z.ZodOptional<z.ZodString>;
            badge: z.ZodOptional<z.ZodString>;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            description: z.ZodOptional<z.ZodString>;
            primaryButtonText: z.ZodOptional<z.ZodString>;
            primaryButtonUrl: z.ZodOptional<z.ZodString>;
            secondaryButtonText: z.ZodOptional<z.ZodString>;
            secondaryButtonUrl: z.ZodOptional<z.ZodString>;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
            overlay: z.ZodOptional<z.ZodBoolean>;
            overlayOpacity: z.ZodOptional<z.ZodNumber>;
            alignment: z.ZodOptional<z.ZodEnum<["left", "center", "right"]>>;
            status: z.ZodEnum<["active", "inactive"]>;
            priority: z.ZodNumber;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            id: z.ZodString;
            desktopImage: z.ZodString;
            tabletImage: z.ZodOptional<z.ZodString>;
            mobileImage: z.ZodOptional<z.ZodString>;
            badge: z.ZodOptional<z.ZodString>;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            description: z.ZodOptional<z.ZodString>;
            primaryButtonText: z.ZodOptional<z.ZodString>;
            primaryButtonUrl: z.ZodOptional<z.ZodString>;
            secondaryButtonText: z.ZodOptional<z.ZodString>;
            secondaryButtonUrl: z.ZodOptional<z.ZodString>;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
            overlay: z.ZodOptional<z.ZodBoolean>;
            overlayOpacity: z.ZodOptional<z.ZodNumber>;
            alignment: z.ZodOptional<z.ZodEnum<["left", "center", "right"]>>;
            status: z.ZodEnum<["active", "inactive"]>;
            priority: z.ZodNumber;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            id: z.ZodString;
            desktopImage: z.ZodString;
            tabletImage: z.ZodOptional<z.ZodString>;
            mobileImage: z.ZodOptional<z.ZodString>;
            badge: z.ZodOptional<z.ZodString>;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            description: z.ZodOptional<z.ZodString>;
            primaryButtonText: z.ZodOptional<z.ZodString>;
            primaryButtonUrl: z.ZodOptional<z.ZodString>;
            secondaryButtonText: z.ZodOptional<z.ZodString>;
            secondaryButtonUrl: z.ZodOptional<z.ZodString>;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
            overlay: z.ZodOptional<z.ZodBoolean>;
            overlayOpacity: z.ZodOptional<z.ZodNumber>;
            alignment: z.ZodOptional<z.ZodEnum<["left", "center", "right"]>>;
            status: z.ZodEnum<["active", "inactive"]>;
            priority: z.ZodNumber;
        }, z.ZodTypeAny, "passthrough">>, "many">>;
        sections: z.ZodOptional<z.ZodArray<z.ZodObject<{
            id: z.ZodString;
            type: z.ZodString;
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            eyebrow: z.ZodOptional<z.ZodString>;
            ctaText: z.ZodOptional<z.ZodString>;
            ctaUrl: z.ZodOptional<z.ZodString>;
            limit: z.ZodOptional<z.ZodNumber>;
            columns: z.ZodOptional<z.ZodNumber>;
            background: z.ZodOptional<z.ZodEnum<["default", "muted", "dark"]>>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            id: z.ZodString;
            type: z.ZodString;
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            eyebrow: z.ZodOptional<z.ZodString>;
            ctaText: z.ZodOptional<z.ZodString>;
            ctaUrl: z.ZodOptional<z.ZodString>;
            limit: z.ZodOptional<z.ZodNumber>;
            columns: z.ZodOptional<z.ZodNumber>;
            background: z.ZodOptional<z.ZodEnum<["default", "muted", "dark"]>>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            id: z.ZodString;
            type: z.ZodString;
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            eyebrow: z.ZodOptional<z.ZodString>;
            ctaText: z.ZodOptional<z.ZodString>;
            ctaUrl: z.ZodOptional<z.ZodString>;
            limit: z.ZodOptional<z.ZodNumber>;
            columns: z.ZodOptional<z.ZodNumber>;
            background: z.ZodOptional<z.ZodEnum<["default", "muted", "dark"]>>;
        }, z.ZodTypeAny, "passthrough">>, "many">>;
        trustStrip: z.ZodOptional<z.ZodArray<z.ZodObject<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">>, "many">>;
        whyChooseUs: z.ZodOptional<z.ZodArray<z.ZodObject<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">>, "many">>;
        newsletter: z.ZodOptional<z.ZodObject<{
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            buttonText: z.ZodOptional<z.ZodString>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            buttonText: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            buttonText: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">>>;
        flashSaleWindow: z.ZodOptional<z.ZodObject<{
            enabled: z.ZodBoolean;
            start: z.ZodOptional<z.ZodNullable<z.ZodString>>;
            end: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            enabled: z.ZodBoolean;
            start: z.ZodOptional<z.ZodNullable<z.ZodString>>;
            end: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            enabled: z.ZodBoolean;
            start: z.ZodOptional<z.ZodNullable<z.ZodString>>;
            end: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        }, z.ZodTypeAny, "passthrough">>>;
    }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
        announcement: z.ZodOptional<z.ZodObject<{
            enabled: z.ZodBoolean;
            text: z.ZodString;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            enabled: z.ZodBoolean;
            text: z.ZodString;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            enabled: z.ZodBoolean;
            text: z.ZodString;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">>>;
        heroSlides: z.ZodOptional<z.ZodArray<z.ZodObject<{
            id: z.ZodString;
            desktopImage: z.ZodString;
            tabletImage: z.ZodOptional<z.ZodString>;
            mobileImage: z.ZodOptional<z.ZodString>;
            badge: z.ZodOptional<z.ZodString>;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            description: z.ZodOptional<z.ZodString>;
            primaryButtonText: z.ZodOptional<z.ZodString>;
            primaryButtonUrl: z.ZodOptional<z.ZodString>;
            secondaryButtonText: z.ZodOptional<z.ZodString>;
            secondaryButtonUrl: z.ZodOptional<z.ZodString>;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
            overlay: z.ZodOptional<z.ZodBoolean>;
            overlayOpacity: z.ZodOptional<z.ZodNumber>;
            alignment: z.ZodOptional<z.ZodEnum<["left", "center", "right"]>>;
            status: z.ZodEnum<["active", "inactive"]>;
            priority: z.ZodNumber;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            id: z.ZodString;
            desktopImage: z.ZodString;
            tabletImage: z.ZodOptional<z.ZodString>;
            mobileImage: z.ZodOptional<z.ZodString>;
            badge: z.ZodOptional<z.ZodString>;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            description: z.ZodOptional<z.ZodString>;
            primaryButtonText: z.ZodOptional<z.ZodString>;
            primaryButtonUrl: z.ZodOptional<z.ZodString>;
            secondaryButtonText: z.ZodOptional<z.ZodString>;
            secondaryButtonUrl: z.ZodOptional<z.ZodString>;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
            overlay: z.ZodOptional<z.ZodBoolean>;
            overlayOpacity: z.ZodOptional<z.ZodNumber>;
            alignment: z.ZodOptional<z.ZodEnum<["left", "center", "right"]>>;
            status: z.ZodEnum<["active", "inactive"]>;
            priority: z.ZodNumber;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            id: z.ZodString;
            desktopImage: z.ZodString;
            tabletImage: z.ZodOptional<z.ZodString>;
            mobileImage: z.ZodOptional<z.ZodString>;
            badge: z.ZodOptional<z.ZodString>;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            description: z.ZodOptional<z.ZodString>;
            primaryButtonText: z.ZodOptional<z.ZodString>;
            primaryButtonUrl: z.ZodOptional<z.ZodString>;
            secondaryButtonText: z.ZodOptional<z.ZodString>;
            secondaryButtonUrl: z.ZodOptional<z.ZodString>;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
            overlay: z.ZodOptional<z.ZodBoolean>;
            overlayOpacity: z.ZodOptional<z.ZodNumber>;
            alignment: z.ZodOptional<z.ZodEnum<["left", "center", "right"]>>;
            status: z.ZodEnum<["active", "inactive"]>;
            priority: z.ZodNumber;
        }, z.ZodTypeAny, "passthrough">>, "many">>;
        sections: z.ZodOptional<z.ZodArray<z.ZodObject<{
            id: z.ZodString;
            type: z.ZodString;
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            eyebrow: z.ZodOptional<z.ZodString>;
            ctaText: z.ZodOptional<z.ZodString>;
            ctaUrl: z.ZodOptional<z.ZodString>;
            limit: z.ZodOptional<z.ZodNumber>;
            columns: z.ZodOptional<z.ZodNumber>;
            background: z.ZodOptional<z.ZodEnum<["default", "muted", "dark"]>>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            id: z.ZodString;
            type: z.ZodString;
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            eyebrow: z.ZodOptional<z.ZodString>;
            ctaText: z.ZodOptional<z.ZodString>;
            ctaUrl: z.ZodOptional<z.ZodString>;
            limit: z.ZodOptional<z.ZodNumber>;
            columns: z.ZodOptional<z.ZodNumber>;
            background: z.ZodOptional<z.ZodEnum<["default", "muted", "dark"]>>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            id: z.ZodString;
            type: z.ZodString;
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            eyebrow: z.ZodOptional<z.ZodString>;
            ctaText: z.ZodOptional<z.ZodString>;
            ctaUrl: z.ZodOptional<z.ZodString>;
            limit: z.ZodOptional<z.ZodNumber>;
            columns: z.ZodOptional<z.ZodNumber>;
            background: z.ZodOptional<z.ZodEnum<["default", "muted", "dark"]>>;
        }, z.ZodTypeAny, "passthrough">>, "many">>;
        trustStrip: z.ZodOptional<z.ZodArray<z.ZodObject<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">>, "many">>;
        whyChooseUs: z.ZodOptional<z.ZodArray<z.ZodObject<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">>, "many">>;
        newsletter: z.ZodOptional<z.ZodObject<{
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            buttonText: z.ZodOptional<z.ZodString>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            buttonText: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            buttonText: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">>>;
        flashSaleWindow: z.ZodOptional<z.ZodObject<{
            enabled: z.ZodBoolean;
            start: z.ZodOptional<z.ZodNullable<z.ZodString>>;
            end: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        }, "passthrough", z.ZodTypeAny, z.objectOutputType<{
            enabled: z.ZodBoolean;
            start: z.ZodOptional<z.ZodNullable<z.ZodString>>;
            end: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        }, z.ZodTypeAny, "passthrough">, z.objectInputType<{
            enabled: z.ZodBoolean;
            start: z.ZodOptional<z.ZodNullable<z.ZodString>>;
            end: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        }, z.ZodTypeAny, "passthrough">>>;
    }, z.ZodTypeAny, "passthrough">>;
}, "strip", z.ZodTypeAny, {
    body: {
        newsletter?: z.objectOutputType<{
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            buttonText: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough"> | undefined;
        announcement?: z.objectOutputType<{
            enabled: z.ZodBoolean;
            text: z.ZodString;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough"> | undefined;
        heroSlides?: z.objectOutputType<{
            id: z.ZodString;
            desktopImage: z.ZodString;
            tabletImage: z.ZodOptional<z.ZodString>;
            mobileImage: z.ZodOptional<z.ZodString>;
            badge: z.ZodOptional<z.ZodString>;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            description: z.ZodOptional<z.ZodString>;
            primaryButtonText: z.ZodOptional<z.ZodString>;
            primaryButtonUrl: z.ZodOptional<z.ZodString>;
            secondaryButtonText: z.ZodOptional<z.ZodString>;
            secondaryButtonUrl: z.ZodOptional<z.ZodString>;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
            overlay: z.ZodOptional<z.ZodBoolean>;
            overlayOpacity: z.ZodOptional<z.ZodNumber>;
            alignment: z.ZodOptional<z.ZodEnum<["left", "center", "right"]>>;
            status: z.ZodEnum<["active", "inactive"]>;
            priority: z.ZodNumber;
        }, z.ZodTypeAny, "passthrough">[] | undefined;
        sections?: z.objectOutputType<{
            id: z.ZodString;
            type: z.ZodString;
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            eyebrow: z.ZodOptional<z.ZodString>;
            ctaText: z.ZodOptional<z.ZodString>;
            ctaUrl: z.ZodOptional<z.ZodString>;
            limit: z.ZodOptional<z.ZodNumber>;
            columns: z.ZodOptional<z.ZodNumber>;
            background: z.ZodOptional<z.ZodEnum<["default", "muted", "dark"]>>;
        }, z.ZodTypeAny, "passthrough">[] | undefined;
        trustStrip?: z.objectOutputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">[] | undefined;
        whyChooseUs?: z.objectOutputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">[] | undefined;
        flashSaleWindow?: z.objectOutputType<{
            enabled: z.ZodBoolean;
            start: z.ZodOptional<z.ZodNullable<z.ZodString>>;
            end: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        }, z.ZodTypeAny, "passthrough"> | undefined;
    } & {
        [k: string]: unknown;
    };
}, {
    body: {
        newsletter?: z.objectInputType<{
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            buttonText: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough"> | undefined;
        announcement?: z.objectInputType<{
            enabled: z.ZodBoolean;
            text: z.ZodString;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough"> | undefined;
        heroSlides?: z.objectInputType<{
            id: z.ZodString;
            desktopImage: z.ZodString;
            tabletImage: z.ZodOptional<z.ZodString>;
            mobileImage: z.ZodOptional<z.ZodString>;
            badge: z.ZodOptional<z.ZodString>;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            description: z.ZodOptional<z.ZodString>;
            primaryButtonText: z.ZodOptional<z.ZodString>;
            primaryButtonUrl: z.ZodOptional<z.ZodString>;
            secondaryButtonText: z.ZodOptional<z.ZodString>;
            secondaryButtonUrl: z.ZodOptional<z.ZodString>;
            backgroundColor: z.ZodOptional<z.ZodString>;
            textColor: z.ZodOptional<z.ZodString>;
            overlay: z.ZodOptional<z.ZodBoolean>;
            overlayOpacity: z.ZodOptional<z.ZodNumber>;
            alignment: z.ZodOptional<z.ZodEnum<["left", "center", "right"]>>;
            status: z.ZodEnum<["active", "inactive"]>;
            priority: z.ZodNumber;
        }, z.ZodTypeAny, "passthrough">[] | undefined;
        sections?: z.objectInputType<{
            id: z.ZodString;
            type: z.ZodString;
            enabled: z.ZodBoolean;
            title: z.ZodOptional<z.ZodString>;
            subtitle: z.ZodOptional<z.ZodString>;
            eyebrow: z.ZodOptional<z.ZodString>;
            ctaText: z.ZodOptional<z.ZodString>;
            ctaUrl: z.ZodOptional<z.ZodString>;
            limit: z.ZodOptional<z.ZodNumber>;
            columns: z.ZodOptional<z.ZodNumber>;
            background: z.ZodOptional<z.ZodEnum<["default", "muted", "dark"]>>;
        }, z.ZodTypeAny, "passthrough">[] | undefined;
        trustStrip?: z.objectInputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">[] | undefined;
        whyChooseUs?: z.objectInputType<{
            icon: z.ZodOptional<z.ZodString>;
            title: z.ZodString;
            text: z.ZodOptional<z.ZodString>;
        }, z.ZodTypeAny, "passthrough">[] | undefined;
        flashSaleWindow?: z.objectInputType<{
            enabled: z.ZodBoolean;
            start: z.ZodOptional<z.ZodNullable<z.ZodString>>;
            end: z.ZodOptional<z.ZodNullable<z.ZodString>>;
        }, z.ZodTypeAny, "passthrough"> | undefined;
    } & {
        [k: string]: unknown;
    };
}>;
export declare const subscribeNewsletterSchema: z.ZodObject<{
    body: z.ZodObject<{
        email: z.ZodString;
        source: z.ZodOptional<z.ZodString>;
    }, "strip", z.ZodTypeAny, {
        email: string;
        source?: string | undefined;
    }, {
        email: string;
        source?: string | undefined;
    }>;
}, "strip", z.ZodTypeAny, {
    body: {
        email: string;
        source?: string | undefined;
    };
}, {
    body: {
        email: string;
        source?: string | undefined;
    };
}>;
//# sourceMappingURL=homepage.schema.d.ts.map