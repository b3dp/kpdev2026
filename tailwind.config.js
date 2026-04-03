/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#162E4B',
                'primary-dark': '#091420',
                accent: '#B27829',
                'accent-bright': '#FF9300',
                teal: '#28484C',
                'teal-muted': '#62868D',
                cream: '#EBDFB5',
                'orange-cta': '#E95925',
                bg: '#FFFFFF',
                'bg-soft': '#F7F5F0',
            },
            fontFamily: {
                baskerville: ['"Libre Baskerville"', 'serif'],
                jakarta: ['"Plus Jakarta Sans"', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
