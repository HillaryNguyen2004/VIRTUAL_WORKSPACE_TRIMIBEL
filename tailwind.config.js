/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/views/**/*.blade.php',
    './resources/**/*.{php,js,ts,vue}',
  ],
  theme: {
    extend: {
      backgroundImage: {
        'panel-right-gradient': 'linear-gradient(160deg, #7FBFFF 10%, #766CD6 100%)',
        'panel-left-gradient': 'linear-gradient(160deg, #766CD6 -10%, #5FE1E0 120%)',
        'btn-login': 'linear-gradient(90deg, #766CD6 0%, #5347CC 100%)'
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
        success: '#10B981', 
        danger: '#EF4444',  
        warning: '#F59E0B', 
      },
    },
  },
  plugins: [
    require('@tailwindcss/container-queries'),
  ],
}

