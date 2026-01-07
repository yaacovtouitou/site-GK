/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  theme: {
    extend: {
      colors: {
        'royal-blue': 'var(--royal-blue)',
        'gold': 'var(--gold)',
        'cyber-white': 'var(--cyber-white)',
        'silver': 'var(--silver)',
        'vibrant-orange': 'var(--vibrant-orange)',
        'background': 'var(--background)',
        'foreground': 'var(--foreground)',
      },
      fontFamily: {
        sans: ['Poppins', 'sans-serif'],
        heading: ['Orbitron', 'sans-serif'],
      },
      borderRadius: {
        'xl': '1rem',
        '2xl': '1.5rem',
        '3xl': '2rem',
      },
      boxShadow: {
        'soft': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
        'clay': '8px 8px 16px #d1d9e6, -8px -8px 16px #ffffff',
      }
    },
  },
  plugins: [],
}
