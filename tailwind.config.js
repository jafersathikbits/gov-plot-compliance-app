import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#eef4ff',
                    100: '#dae6ff',
                    200: '#bdd2ff',
                    300: '#90b5fe',
                    400: '#5c8cfb',
                    500: '#3865f6',
                    600: '#2246eb',
                    700: '#1a33d0',
                    800: '#1c2ca8',
                    900: '#1c2b84',
                    950: '#121a4f',
                },
            },
        },
    },

    plugins: [forms],
};
