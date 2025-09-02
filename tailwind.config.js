/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/views/**/*.blade.php',
    './resources/**/*.{js,ts,vue}',
  ],
  theme: {
    extend: {
      backgroundImage: {
        'panel-right-gradient': 'linear-gradient(160deg, #5AE194 0%, #C4B5FD 100%)',
        'panel-left-gradient': 'linear-gradient(160deg, #C4B5FD 0%, #5AE194 100%)',
        'btn-login': 'linear-gradient(90deg, #8F7AE1 0%, #5D3FD3 100%)'
      },
    },
  },
  plugins: [],
}

