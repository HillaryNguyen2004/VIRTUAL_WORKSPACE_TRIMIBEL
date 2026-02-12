/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/views/**/*.blade.php',
    './resources/**/*.{php,js,ts,vue}',
    './app/**/*.php',
  ],
  theme: {
    extend: {
      backgroundImage: {
        'panel-right-gradient': 'linear-gradient(160deg, #7FBFFF 0%, #766CD6 80%)',
        'panel-left-gradient': 'linear-gradient(160deg, #766CD6 10%, #5FE1E0 120%)',
        'primary-gradient': 'linear-gradient(90deg, #766CD6 0%, #5347CC 100%)'
      },
      colors: {
        primary: {
          DEFAULT: '#5347CC',
          hover: '#4538A8',
          light: '#766CD6',

        },
        secondary: {
          DEFAULT: '#4896FE',
          hover: '#2680F6',
          light: '#7FBFFF',
        },
        accent: {
          DEFAULT: '#17C8C6',
          hover: '#13A6A4',
          light: '#5FE1E0',
        },

        // --- BASE COLORS (Updated) ---
        main: '#070416',      // Your deep navy text (Kept)

        // CHANGED: Switched from Lavender (#F1EFFC) to a Neutral Cool White (#F9FAFB)
        // This makes the UI feel cleaner and less "tinted"
        canvas: '#F9FAFB',

        // --- MUTED / NEUTRALS (Redone) ---
        // I replaced the purple-tinted grays with standard cool-grays (Slate).
        // These look much better on a white background.
        muted: {
          50: '#F9FAFB',  // Matches canvas
          100: '#F3F4F6', // Light gray (for hover backgrounds)
          200: '#E5E7EB', // Neutral Borders
          300: '#D1D5DB', // Disabled states
          400: '#9CA3AF', // Icons
          500: '#6B7280', // Secondary Text
          600: '#4B5563',
          700: '#374151',
          800: '#1F2937',
          900: '#111827',
        },

        // --- STATUS COLORS ---
        success: {
          DEFAULT: '#10B981', // Emerald 500
          hover: '#059669',   // Emerald 600
          light: '#34D399',   // Emerald 400 
        },
        danger: {
          DEFAULT: '#EF4444', // Red 500
          hover: '#DC2626',   // Red 600
          light: '#F87171',   // Red 400
        },
        warning: {
          DEFAULT: '#F59E0B', // Amber 500
          hover: '#D97706',   // Amber 600
          light: '#FBBF24',   // Amber 400
        },
      },
    },
  },
  plugins: [
    require('@tailwindcss/container-queries'),
  ],
}

