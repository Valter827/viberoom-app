/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                // Палитра в стиле Discord
                'vr-dark': '#1E1F22',
                'vr-sidebar': '#2B2D31',
                'vr-chat': '#313338',
                'vr-input': '#383A40',
                'vr-hover': '#3a3c42',
                'vr-accent': '#5865F2',
                'vr-accent-hover': '#4752c4',
            },
            fontFamily: {
                sans: ['"gg sans"', 'Whitney', 'Helvetica Neue', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
