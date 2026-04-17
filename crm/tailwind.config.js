/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './app/**/*.php',
    './public/**/*.php',
    './public/assets/js/**/*.js',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      colors: {
        ink: '#0b1220',
        brand: {
          50:  '#eef4ff',
          100: '#dbe6ff',
          200: '#b8cdff',
          300: '#8ca9ff',
          400: '#5d80ff',
          500: '#3864ff',
          600: '#1e4aff',
          700: '#143cd6',
          800: '#1233a8',
          900: '#0e2a7a',
          950: '#0a1e58',
        },
      },
      boxShadow: {
        soft: '0 8px 24px -8px rgba(0,0,0,0.4)',
      },
    },
  },
  plugins: [],
};
