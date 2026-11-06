/**
 * "Royal Rose" design system — raspberry primary, gold accent, aubergine ink.
 * Every view reads `primary-*` / `accent-*` / `ink-*`, so retuning a value
 * here restyles the whole site and admin panel at once.
 */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#FFF0F7',
          100: '#FFE4F2',
          200: '#FBCFE8',
          300: '#F9A8D4',
          400: '#F472B6',
          500: '#EC4899',
          600: '#E11D62',
          700: '#BE185D',
          800: '#9D174D',
          900: '#831843',
        },
        accent: {
          50: '#FFFAEB',
          100: '#FEF0C7',
          200: '#FEDF89',
          300: '#F5CB66',
          400: '#EDB53F',
          500: '#D49B27',
          600: '#B87A16',
          700: '#94590F',
        },
        ink: {
          50: '#FFF5FA',
          100: '#FCE7F3',
          200: '#F5D0E6',
          300: '#E9B8D4',
          400: '#B886A3',
          500: '#7E526D',
          600: '#58354C',
          700: '#3F2236',
          800: '#2B1424',
          900: '#1C0B17',
        },
        success: '#10B981',
        warning: '#F59E0B',
        danger: '#E11D48',
      },
      borderRadius: { sm: '8px', md: '12px', lg: '16px', xl: '22px', '2xl': '28px', '3xl': '36px' },
      boxShadow: {
        sm: '0 1px 2px rgba(190,24,93,.06), 0 1px 3px rgba(190,24,93,.04)',
        md: '0 6px 16px -4px rgba(225,29,98,.14), 0 2px 6px -2px rgba(190,24,93,.06)',
        lg: '0 18px 40px -12px rgba(225,29,98,.22), 0 6px 14px -6px rgba(190,24,93,.10)',
        xl: '0 28px 60px -18px rgba(190,24,93,.30)',
        glow: '0 10px 28px -6px rgba(236,72,153,.55), 0 4px 14px -4px rgba(225,29,98,.40)',
        'glow-lg': '0 18px 42px -8px rgba(236,72,153,.65), 0 8px 22px -6px rgba(225,29,98,.50)',
        card: '0 2px 6px rgba(190,24,93,.05), 0 12px 28px -12px rgba(225,29,98,.14)',
      },
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', 'sans-serif'],
        serif: ['Fraunces', 'Georgia', 'serif'],
      },
      backgroundImage: {
        'brand-gradient': 'linear-gradient(135deg, #FF2E7E 0%, #EC4899 50%, #D61F69 100%)',
        'brand-soft': 'linear-gradient(135deg, #FFE4F2 0%, #F8D7FF 50%, #FCE7F3 100%)',
        'ink-gradient': 'linear-gradient(135deg, #E11D62 0%, #EC4899 48%, #9D174D 100%)',
        'gold-gradient': 'linear-gradient(135deg, #F5CB66 0%, #D49B27 100%)',
      },
      keyframes: {
        'fade-up': {
          '0%': { opacity: '0', transform: 'translateY(16px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        'fade-in': { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
        shimmer: {
          '0%': { backgroundPosition: '-200% 0' },
          '100%': { backgroundPosition: '200% 0' },
        },
      },
      animation: {
        'fade-up': 'fade-up .6s cubic-bezier(.16,1,.3,1) both',
        'fade-in': 'fade-in .4s ease-out both',
        shimmer: 'shimmer 3s linear infinite',
      },
    },
  },
};
