/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        /* Shopifi semantic tokens */
        ink: '#000000',
        'on-primary': '#FFFFFF',
        /* {colors.aloe-10} — light-track accent (featured tier, tags) */
        aloe: {
          DEFAULT: '#C1FBD4',
          10: '#C1FBD4',
        },
        /* {colors.pistachio-10} — light-track band fill */
        pistachio: {
          DEFAULT: '#D4F9E0',
          10: '#D4F9E0',
        },
        /* Surfaces */
        'canvas-night': {
          DEFAULT: '#000000',
          elevated: '#0A0A0A',
        },
        'surface-elevated-dark': '#1E2C31',
        'canvas-light': '#FFFFFF',
        'canvas-cream': '#FBFBF5',
        'hairline-light': '#E4E4E7',
        'hairline-dark': '#1E2C31',
        /* Shade ladder */
        'shade-30': '#D4D4D8',
        'shade-40': '#A1A1AA',
        'shade-50': '#71717A',
        'shade-60': '#52525B',
        'shade-70': '#3F3F46',
        /* Cool link tones (tertiary links on dark) */
        'link-cool-1': '#9DABAD',
        'link-cool-2': '#9797A2',
        'link-cool-3': '#BDBDCA',
        'link-mint': '#99B3AD',
        /* Legacy aliases (kept for compatibility) */
        brand: {
          DEFAULT: '#C1FBD4',
          light: '#D4F9E0',
          dark: '#9BE8B8',
        },
        primary: {
          DEFAULT: 'hsl(var(--primary))',
          foreground: 'hsl(var(--primary-foreground))',
          50: '#F4F4F5',
          100: '#E4E4E7',
          500: '#52525B',
          600: '#3F3F46',
          700: '#27272A',
        },
        accent: {
          DEFAULT: 'hsl(var(--accent))',
          foreground: 'hsl(var(--accent-foreground))',
          50: '#F2FDF6',
          100: '#E2FBE9',
          500: '#C1FBD4',
          600: '#9BE8B8',
        },
        success: {
          DEFAULT: '#10B981',
          foreground: 'hsl(var(--success-foreground))',
          50: '#ECFDF5',
          500: '#10B981',
        },
        surface: {
          DEFAULT: '#FBFBF5',
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
        /* UI tier: Inter Variable */
        body: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
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
