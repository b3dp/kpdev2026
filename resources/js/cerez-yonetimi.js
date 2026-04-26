const STORAGE_KEY = 'kp_cerez_tercihleri';

const defaultPreferences = {
    version: 1,
    zorunlu: true,
    analitik: false,
    pazarlama: false,
    updatedAt: null,
};

const parsePreferences = () => {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw);

        return {
            ...defaultPreferences,
            ...parsed,
            zorunlu: true,
        };
    } catch {
        return null;
    }
};

const persistPreferences = (preferences) => {
    const payload = {
        ...preferences,
        zorunlu: true,
        updatedAt: new Date().toISOString(),
    };

    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));

    return payload;
};

const loadGtagScript = (() => {
    let loaded = false;

    return (tagId) => {
        if (!tagId || loaded || document.querySelector('script[data-kp-gtag="1"]')) {
            loaded = true;
            return;
        }

        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(tagId)}`;
        script.dataset.kpGtag = '1';
        document.head.appendChild(script);
        loaded = true;
    };
})();

const ensureDataLayer = () => {
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function gtag() {
        window.dataLayer.push(arguments);
    };
};

const activateTracking = (preferences, config) => {
    ensureDataLayer();

    const measurementId = config.ga4MeasurementId || null;
    const adsTagId = config.googleAdsTagId || null;
    const shouldLoadGoogleTag = Boolean(
        (preferences.analitik && measurementId) || (preferences.pazarlama && adsTagId)
    );

    if (!shouldLoadGoogleTag) {
        return;
    }

    loadGtagScript(measurementId || adsTagId);

    window.gtag('js', new Date());

    if (measurementId && preferences.analitik) {
        window.gtag('config', measurementId, {
            anonymize_ip: true,
            page_path: window.location.pathname + window.location.search,
        });
    }

    if (adsTagId && preferences.pazarlama) {
        window.gtag('config', adsTagId);
    }
};

const olayGonder = (eventName, params = {}, category = 'analitik') => {
    const preferences = parsePreferences() || defaultPreferences;

    if (!preferences[category] || typeof window.gtag !== 'function') {
        return false;
    }

    window.gtag('event', eventName, params);

    return true;
};

const initConsentUi = () => {
    const root = document.querySelector('[data-cerez-root]');

    if (!root) {
        return;
    }

    const banner = root.querySelector('[data-cerez-banner]');
    const panel = root.querySelector('[data-cerez-panel]');
    const backdrop = root.querySelector('[data-cerez-backdrop]');
    const openButtons = root.querySelectorAll('[data-cerez-open]');
    const closeButtons = root.querySelectorAll('[data-cerez-close]');
    const acceptAllButton = root.querySelector('[data-cerez-accept-all]');
    const rejectAllButton = root.querySelector('[data-cerez-reject-all]');
    const saveButton = root.querySelector('[data-cerez-save]');
    const analitikInput = root.querySelector('[data-cerez-analitik]');
    const pazarlamaInput = root.querySelector('[data-cerez-pazarlama]');

    const config = {
        ga4MeasurementId: root.dataset.ga4MeasurementId || '',
        googleAdsTagId: root.dataset.googleAdsTagId || '',
    };

    const setPanelState = (isOpen) => {
        panel?.classList.toggle('is-open', isOpen);
        backdrop?.classList.toggle('is-open', isOpen);
        document.documentElement.classList.toggle('overflow-hidden', isOpen);
    };

    const applyPreferences = (preferences) => {
        if (analitikInput) {
            analitikInput.checked = Boolean(preferences.analitik);
        }

        if (pazarlamaInput) {
            pazarlamaInput.checked = Boolean(preferences.pazarlama);
        }

        activateTracking(preferences, config);
    };

    const savedPreferences = parsePreferences();

    if (savedPreferences) {
        banner?.remove();
        applyPreferences(savedPreferences);
    } else {
        applyPreferences(defaultPreferences);
    }

    openButtons.forEach((button) => {
        button.addEventListener('click', () => setPanelState(true));
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', () => setPanelState(false));
    });

    acceptAllButton?.addEventListener('click', () => {
        const preferences = persistPreferences({
            ...defaultPreferences,
            analitik: true,
            pazarlama: true,
        });

        banner?.remove();
        setPanelState(false);
        applyPreferences(preferences);
    });

    rejectAllButton?.addEventListener('click', () => {
        const preferences = persistPreferences({
            ...defaultPreferences,
            analitik: false,
            pazarlama: false,
        });

        banner?.remove();
        setPanelState(false);
        applyPreferences(preferences);
    });

    saveButton?.addEventListener('click', () => {
        const preferences = persistPreferences({
            ...defaultPreferences,
            analitik: Boolean(analitikInput?.checked),
            pazarlama: Boolean(pazarlamaInput?.checked),
        });

        banner?.remove();
        setPanelState(false);
        applyPreferences(preferences);
    });

    window.kpCerez = {
        getPreferences: () => parsePreferences() || { ...defaultPreferences },
        hasConsent: (category) => Boolean((parsePreferences() || defaultPreferences)[category]),
        trackEvent: (eventName, params = {}, category = 'analitik') => olayGonder(eventName, params, category),
        olayGonder: (eventName, params = {}, category = 'analitik') => olayGonder(eventName, params, category),
    };
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initConsentUi, { once: true });
} else {
    initConsentUi();
}