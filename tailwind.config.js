import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './node_modules/preline/dist/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Mundo del contrato: marino institucional, papel, tinta de tóner, saldo (verde) y alerta (ámbar).
            colors: {
                marino: { DEFAULT: '#0d2149', 700: '#123a7a', 600: '#1d4ed8', 50: '#eef2fb' },
                papel: '#f5f6f8',
                tinta: { DEFAULT: '#0f172a', 2: '#334155', 3: '#64748b', 4: '#94a3b8' },
                linea: 'rgba(15, 23, 42, 0.08)',
                saldo: { DEFAULT: '#15803d', 50: '#ecfdf3' },
            },
            boxShadow: {
                hoja: '0 0 0 1px rgba(15,23,42,.06), 0 1px 2px -1px rgba(15,23,42,.06), 0 2px 4px rgba(15,23,42,.04)',
            },
        },
    },

    plugins: [forms],
};
