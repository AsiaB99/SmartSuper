import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#f1fbf5',
                    100: '#dcf8e9',
                    200: '#b9efd4',
                    300: '#8fe4b8',
                    400: '#60d594',
                    500: '#2ecc71',
                    600: '#27ae60',
                    700: '#1f8a4d',
                    800: '#1b6f3f',
                    900: '#175b34',
                },
                accent: {
                    50: '#fef8ec',
                    100: '#fdeccb',
                    200: '#fbd997',
                    300: '#f8c563',
                    400: '#f5ae33',
                    500: '#f39c12',
                    600: '#d9870f',
                    700: '#b3710d',
                    800: '#925d0d',
                    900: '#774d0f',
                },
                ink: {
                    50: '#f7f7f7',
                    100: '#ebebeb',
                    200: '#d1d1d1',
                    300: '#adadad',
                    400: '#858585',
                    500: '#666666',
                    600: '#525252',
                    700: '#454545',
                    800: '#3d3d3d',
                    900: '#333333',
                },
                sand: '#f7f3ec',
                mist: '#edf4f2',
                'fondo-claro': '#f4f4f4',
            },
            fontFamily: {
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
                display: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                soft: '0 18px 45px -24px rgba(23, 36, 42, 0.35)',
            },
            backgroundImage: {
                'hero-produce':
                    "linear-gradient(rgba(23, 36, 42, 0.5), rgba(23, 36, 42, 0.72)), url('https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1600&q=80')",
            },
        },
    },

    plugins: [forms],
};
