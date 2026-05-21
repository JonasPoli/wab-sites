/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'selector',
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  theme: {
    fontFamily:{
        'headline':['Outfit','sans-serif'],
        'body':['Inter','sans-serif'],
        'sans':['Inter','sans-serif']
      },
    extend: {
      colors:{
        'primary': {
            DEFAULT:'#0769a1',
            light: '#1679b1',
        },
        'cetec-orange': 'rgba(var(--color-secondary-rgb), <alpha-value>)',
        'cetec-orange-text': 'rgba(var(--color-secondary-rgb), <alpha-value>)',
        'cetec-primary': 'rgba(var(--color-primary-rgb), <alpha-value>)',
        'cetec-secondary': 'rgba(var(--color-secondary-rgb), <alpha-value>)',
        'cetec-dark': '#1a1a1a',
        'cetec-light': '#f8f9fa',
        'cetec': {
            orange: 'rgba(var(--color-secondary-rgb), <alpha-value>)',
            'orange-text': 'rgba(var(--color-secondary-rgb), <alpha-value>)',
            primary: 'rgba(var(--color-primary-rgb), <alpha-value>)',
            secondary: 'rgba(var(--color-secondary-rgb), <alpha-value>)',
            dark: '#1a1a1a',
            light: '#f8f9fa',
        },
        'cetec-footer': {
            top: '#222222',
            mid: '#1a1a1a',
            bottom: '#111111',
        },
      },
        spacing:{
            '55vw':'55vw'
        },
        container:{
            center:true,
            padding:{
                DEFAULT: '1rem',
                sm:'2rem',
            },
            screens: {
                sm: '600px',
                md: '728px',
                lg: '984px',
                xl: '1240px',
                '2xl': '1240px',
            },
        }
    },
  },
  plugins: [
      //require('@tailwindcss/forms'),
      //require('flowbite/plugin')
  ],
}
