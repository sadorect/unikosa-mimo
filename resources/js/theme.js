import { ref } from 'vue';

const STORAGE_KEY = 'theme_pref'; // member-level override: 'light' | 'dark' | 'auto'

// Reactive current preference, consumed by ThemeToggle.
export const themePref = ref(getStoredPref());

const GOOGLE_FONTS = {
    Inter: 'Inter:wght@400;500;600;700',
    Poppins: 'Poppins:wght@400;500;600;700',
    Roboto: 'Roboto:wght@400;500;700',
    Lora: 'Lora:wght@400;500;600;700',
    Merriweather: 'Merriweather:wght@400;700',
    Nunito: 'Nunito:wght@400;500;600;700',
};

function getStoredPref() {
    if (typeof localStorage === 'undefined') return null;
    return localStorage.getItem(STORAGE_KEY);
}

/* --- accent palette ------------------------------------------------------ */

function hexToRgb(hex) {
    const h = hex.replace('#', '');
    const full = h.length === 3 ? h.split('').map((c) => c + c).join('') : h;
    const int = parseInt(full, 16);
    return [(int >> 16) & 255, (int >> 8) & 255, int & 255];
}

function rgbToHex([r, g, b]) {
    return '#' + [r, g, b].map((v) => Math.round(v).toString(16).padStart(2, '0')).join('');
}

function mix(rgb, target, amount) {
    return rgb.map((c, i) => c + (target[i] - c) * amount);
}

// Derive a Tailwind-like 50..900 ramp from a single accent hex.
function buildRamp(hex) {
    const base = hexToRgb(hex);
    const white = [255, 255, 255];
    const black = [0, 0, 0];
    return {
        50: rgbToHex(mix(base, white, 0.9)),
        100: rgbToHex(mix(base, white, 0.75)),
        200: rgbToHex(mix(base, white, 0.55)),
        300: rgbToHex(mix(base, white, 0.35)),
        400: rgbToHex(mix(base, white, 0.15)),
        500: rgbToHex(base),
        600: rgbToHex(mix(base, black, 0.1)),
        700: rgbToHex(mix(base, black, 0.25)),
        800: rgbToHex(mix(base, black, 0.4)),
        900: rgbToHex(mix(base, black, 0.55)),
    };
}

function applyAccent(hex) {
    if (!hex || !/^#?[0-9a-fA-F]{3,6}$/.test(hex)) return;
    const ramp = buildRamp(hex.startsWith('#') ? hex : `#${hex}`);
    const root = document.documentElement;
    Object.entries(ramp).forEach(([shade, value]) => {
        root.style.setProperty(`--accent-${shade}`, value);
    });
}

/* --- font ---------------------------------------------------------------- */

function applyFont(family) {
    if (!family) return;
    const stack = `'${family}', ui-sans-serif, system-ui, sans-serif`;
    document.documentElement.style.setProperty('--font-family', stack);

    const spec = GOOGLE_FONTS[family];
    if (spec && !document.getElementById('app-google-font')) {
        const link = document.createElement('link');
        link.id = 'app-google-font';
        link.rel = 'stylesheet';
        link.href = `https://fonts.googleapis.com/css2?family=${spec}&display=swap`;
        document.head.appendChild(link);
    }
}

/* --- light / dark / auto ------------------------------------------------- */

function prefersDark() {
    return typeof matchMedia !== 'undefined' && matchMedia('(prefers-color-scheme: dark)').matches;
}

function resolveMode(adminDefault) {
    const pref = getStoredPref() || adminDefault || 'light';
    if (pref === 'auto') return prefersDark() ? 'dark' : 'light';
    return pref;
}

function applyMode(mode) {
    document.documentElement.classList.toggle('dark', mode === 'dark');
}

let adminThemeMode = 'light';
let mediaListenerBound = false;

/**
 * Apply the full theme from Inertia-shared admin settings, honouring any
 * member-level override stored in localStorage. Called on boot and whenever
 * settings change across Inertia navigations.
 */
export function applyTheme(settings = {}) {
    adminThemeMode = settings.theme_mode || 'light';
    applyAccent(settings.accent_color);
    applyFont(settings.font_family);
    applyMode(resolveMode(adminThemeMode));

    // React to OS scheme changes while in "auto".
    if (!mediaListenerBound && typeof matchMedia !== 'undefined') {
        matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if ((getStoredPref() || adminThemeMode) === 'auto') {
                applyMode(resolveMode(adminThemeMode));
            }
        });
        mediaListenerBound = true;
    }
}

/** Member-level toggle: persists choice and re-applies immediately. */
export function setThemePref(pref) {
    themePref.value = pref;
    if (pref) localStorage.setItem(STORAGE_KEY, pref);
    else localStorage.removeItem(STORAGE_KEY);
    applyMode(resolveMode(adminThemeMode));
}
