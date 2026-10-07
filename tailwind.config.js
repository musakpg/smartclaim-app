/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#f0fdf4',
                    500: '#10b981',
                    600: '#059669',
                    700: '#047857',
                }
            }
        },
    },
    plugins: [],
};
