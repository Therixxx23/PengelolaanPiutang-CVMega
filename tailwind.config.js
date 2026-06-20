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
                ink: '#1B2027',
                'ink-muted': '#5B6470',
                paper: '#F6F7F6',
                surface: '#FFFFFF',
                line: '#DCE2E0',
                action: '#0E6E66',
                status: {
                    lancar: '#6B7CA3',
                    watch30: '#C8862A',
                    watch60: '#B8612A',
                    critical: '#B33A2E',
                    paid: '#3E7C58',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.monospace],
            },
            borderRadius: {
                DEFAULT: '6px',
            },
            spacing: {
                18: '4.5rem',
                22: '5.5rem',
                30: '7.5rem',
            },
        },
    },

    plugins: [forms],
};
