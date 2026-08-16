/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        /* ── Mama Bazar brand palette ── */
        /* Primary Green — brand identity, nav, header, trust, icons */
        'brand-green': {
          DEFAULT: '#176B3A',
          50:  '#EAF6EF',
          100: '#C6E9D3',
          200: '#8FD4AE',
          300: '#58BF89',
          400: '#2EA665',
          500: '#176B3A',
          600: '#0F4D2C',
          700: '#0A3820',
          dark: '#0F4D2C',
          light: '#EAF6EF',
        },
        /* Brand Orange — CTA, Add to Cart, Sale, Offer, Notifications */
        'brand-orange': {
          DEFAULT: '#F47B20',
          50:  '#FFF1E6',
          100: '#FFE0C2',
          200: '#FFC08A',
          300: '#FF9F52',
          400: '#F88A34',
          500: '#F47B20',
          600: '#D96510',
          700: '#B54E0A',
          light: '#FFF1E6',
        },
        /* Semantic tokens */
        ink: '#17221B',
        'on-primary': '#FFFFFF',
        /* Surfaces */
        'canvas-light': '#FFFFFF',
        'canvas-cream': '#F8FAF8',
        'hairline-light': '#E4E4E7',
        /* Shade ladder */
        'shade-30': '#D4D4D8',
        'shade-40': '#A1A1AA',
        'shade-50': '#71717A',
        'shade-60': '#52525B',
        'shade-70': '#3F3F46',
        /* Legacy compat — mapped to new palette */
        aloe: {
          DEFAULT: '#C6E9D3',
          10: '#EAF6EF',
        },
        brand: {
          DEFAULT: '#176B3A',
          light: '#EAF6EF',
          dark: '#0F4D2C',
        },
        primary: {
          DEFAULT: 'hsl(var(--primary))',
          foreground: 'hsl(var(--primary-foreground))',
          50:  '#EAF6EF',
          100: '#C6E9D3',
          500: '#176B3A',
          600: '#0F4D2C',
          700: '#0A3820',
        },
        accent: {
          DEFAULT: 'hsl(var(--accent))',
          foreground: 'hsl(var(--accent-foreground))',
          50:  '#FFF1E6',
          100: '#FFE0C2',
          500: '#F47B20',
          600: '#D96510',
        },
        success: {
          DEFAULT: '#176B3A',
          foreground: 'hsl(var(--success-foreground))',
          50: '#EAF6EF',
          500: '#176B3A',
        },
        surface: {
          DEFAULT: '#F8FAF8',
          card: '#FFFFFF',
        },
        background: 'hsl(var(--background))',
        foreground: 'hsl(var(--foreground))',
        card: {
          DEFAULT: 'hsl(var(--card))',
          foreground: 'hsl(var(--card-foreground))',
        },
        popover: {
          DEFAULT: 'hsl(var(--popover))',
          foreground: 'hsl(var(--popover-foreground))',
        },
        secondary: {
          DEFAULT: 'hsl(var(--secondary))',
          foreground: 'hsl(var(--secondary-foreground))',
        },
        muted: {
          DEFAULT: 'hsl(var(--muted))',
          foreground: 'hsl(var(--muted-foreground))',
        },
        destructive: {
          DEFAULT: 'hsl(var(--destructive))',
          foreground: 'hsl(var(--destructive-foreground))',
        },
        warning: 'hsl(var(--warning))',
        border: 'hsl(var(--border))',
        input: 'hsl(var(--input))',
        ring: 'hsl(var(--ring))',
      },
      fontFamily: {
        /* Display tier: NHGD -> Helvetica Now Display -> Helvetica -> Arial (thin cuts) */
        headline: [
          'Neue Haas Grotesk Display',
          'Helvetica Now Display',
          'Helvetica Neue',
          'Helvetica',
          'Arial',
          'Inter',
          'sans-serif',
        ],
        display: [
          'Neue Haas Grotesk Display',
          'Helvetica Now Display',
          'Helvetica Neue',
          'Helvetica',
          'Arial',
          'Inter',
          'sans-serif',
        ],
        /* UI tier: Inter Variable, with Noto Sans Bengali as the Bengali glyph provider */
        body: ['Inter', 'Noto Sans Bengali', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Consolas', 'monospace'],
      },
      borderRadius: {
        '4xl': '2rem',
        /* {rounded.pill} — the only button shape */
        pill: '9999px',
      },
      boxShadow: {
        soft: '0 1px 2px rgba(0, 0, 0, 0.05)',
        card: '0 4px 20px rgba(0, 0, 0, 0.07)',
        lift: '0 20px 40px rgba(0, 0, 0, 0.14)',
        glow: '0 0 40px rgba(193, 251, 212, 0.18)',
        /* Level 1: inset top sheen on dark cards */
        'inset-top': 'inset 0 1px 0 rgba(255, 255, 255, 0.04)',
        /* Level 2: dark elevated card */
        'dark-card': '0 0 0 1px rgba(255, 255, 255, 0.08), 0 1px 3px rgba(0, 0, 0, 0.3), 0 5px 10px rgba(0, 0, 0, 0.2)',
        /* Level 3: stacked tiny shadows — the light-track paper halo */
        stacked:
          '0 8px 8px rgba(0, 0, 0, 0.1), 0 4px 4px rgba(0, 0, 0, 0.1), 0 2px 2px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(0, 0, 0, 0.1)',
        /* Level 4: modal / floating panel */
        panel: '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
      },
      keyframes: {
        'gradient-shift': {
          '0%, 100%': { backgroundPosition: '0% 50%' },
          '50%': { backgroundPosition: '100% 50%' },
        },
        float: {
          '0%, 100%': { transform: 'translateY(0px)' },
          '50%': { transform: 'translateY(-14px)' },
        },
        marquee: {
          '0%': { transform: 'translateX(0)' },
          '100%': { transform: 'translateX(-50%)' },
        },
      },
      animation: {
        'gradient-shift': 'gradient-shift 12s ease infinite',
        float: 'float 6s ease-in-out infinite',
        'float-slow': 'float 8s ease-in-out infinite',
        marquee: 'marquee 30s linear infinite',
      },
    },
  },
  plugins: [require('tailwindcss-animate')],
}
