import { storage } from "./storage.js";

const STORAGE_KEY = "site_settings";

const DEFAULT_SETTINGS = {
    siteName: "AlleyNote",
    heroTitle: "現代化公布欄系統",
    siteDescription: "現代化公布欄系統",
    footerCopyright: "© 2024 AlleyNote. All rights reserved.",
    footerDescription: "基於 Domain-Driven Design 的企業級公布欄系統",
    timezone: "Asia/Taipei",
    allowedFileTypes: [],
};

function extractValue(raw, fallback = undefined) {
    if (raw && typeof raw === "object" && "value" in raw) {
        return raw.value ?? fallback;
    }

    return raw ?? fallback;
}

export function getDefaultSiteSettings() {
    return { ...DEFAULT_SETTINGS };
}

export function loadCachedSiteSettings() {
    const cached = storage.get(STORAGE_KEY);
    if (!cached || typeof cached !== "object") {
        return getDefaultSiteSettings();
    }

    return {
        ...getDefaultSiteSettings(),
        ...cached,
    };
}

export function cacheSiteSettings(settings) {
    storage.set(STORAGE_KEY, settings);
}

export function normalizeSettingsFromApi(apiPayload = {}) {
    const normalized = getDefaultSiteSettings();

    normalized.siteName = extractValue(
        apiPayload.site_name,
        normalized.siteName
    );
    normalized.heroTitle = extractValue(
        apiPayload.hero_title,
        extractValue(apiPayload.site_tagline, normalized.heroTitle)
    );
    normalized.siteDescription = extractValue(
        apiPayload.site_description,
        normalized.siteDescription
    );
    normalized.footerCopyright = extractValue(
        apiPayload.footer_copyright,
        normalized.footerCopyright
    );
    normalized.footerDescription = extractValue(
        apiPayload.footer_description,
        normalized.footerDescription
    );
    normalized.timezone = extractValue(
        apiPayload.site_timezone,
        normalized.timezone
    );

    const allowedTypesRaw = extractValue(
        apiPayload.allowed_file_types,
        normalized.allowedFileTypes
    );
    if (Array.isArray(allowedTypesRaw)) {
        normalized.allowedFileTypes = allowedTypesRaw;
    } else if (typeof allowedTypesRaw === "string" && allowedTypesRaw.length) {
        try {
            const parsed = JSON.parse(allowedTypesRaw);
            if (Array.isArray(parsed)) {
                normalized.allowedFileTypes = parsed;
            }
        } catch (error) {
            console.warn("解析 allowed_file_types 失敗:", error);
        }
    }

    return normalized;
}

export function normalizeSettingsFromForm(
    formPayload = {},
    richTextPayload = {}
) {
    const normalized = getDefaultSiteSettings();

    if (typeof formPayload.site_name === "string") {
        normalized.siteName = formPayload.site_name;
    }

    const heroTitle =
        typeof formPayload.site_tagline === "string"
            ? formPayload.site_tagline
            : undefined;
    normalized.heroTitle = heroTitle ?? normalized.heroTitle;

    if (typeof formPayload.site_description === "string") {
        normalized.siteDescription = formPayload.site_description;
        if (!heroTitle) {
            normalized.heroTitle = formPayload.site_description;
        }
    }

    if (typeof formPayload.footer_copyright === "string") {
        normalized.footerCopyright = formPayload.footer_copyright;
    }

    const footerDescription =
        richTextPayload.footerDescription ?? formPayload.footer_description;
    if (typeof footerDescription === "string") {
        normalized.footerDescription = footerDescription;
    }

    if (typeof formPayload.site_timezone === "string") {
        normalized.timezone = formPayload.site_timezone;
    }

    const fromForm = formPayload.allowed_file_types;
    if (Array.isArray(fromForm)) {
        normalized.allowedFileTypes = fromForm;
    } else if (typeof fromForm === "string" && fromForm.length) {
        try {
            const parsed = JSON.parse(fromForm);
            if (Array.isArray(parsed)) {
                normalized.allowedFileTypes = parsed;
            }
        } catch (error) {
            console.warn("解析 allowed_file_types 失敗:", error);
        }
    }

    return normalized;
}

export function applySiteBranding(settings) {
    if (!settings) {
        return;
    }

    requestAnimationFrame(() => {
        document
            .querySelectorAll("[data-site-name]")
            .forEach((el) => (el.textContent = settings.siteName));

        document
            .querySelectorAll("[data-site-tagline]")
            .forEach((el) => (el.textContent = settings.heroTitle));

        document
            .querySelectorAll("[data-site-description]")
            .forEach((el) => (el.textContent = settings.siteDescription));

        document
            .querySelectorAll("[data-footer-copyright]")
            .forEach((el) => (el.textContent = settings.footerCopyright));

        document.querySelectorAll("[data-footer-description]").forEach((el) => {
            el.innerHTML = settings.footerDescription;
        });
    });
}
