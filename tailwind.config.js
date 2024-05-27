const defaultTheme = require('tailwindcss/defaultTheme')
const colors = require('tailwindcss/colors')

/** @type {import('tailwindcss').Config} */
export default {
    mode: 'jit',
    purge: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            colors: {
                primary: {
                    100: '#E6EFEE',
                    200: '#CBDEDD',
                    300: '#AEC6C4',
                    400: '#69A3A2',
                    500: '#005953',
                    600: '#00504B',
                    700: '#003532',
                    800: '#002825',
                    900: '#001B19',
                },
                surface: {
                    0: '#FFFFFF',
                    50: '#F9FAFB',
                    100: '#F9FAFB',
                    200: '#F4F6F8',
                    300: '#E5E7EB',
                    400: '#D2D6DC',
                    500: '#9FA6B2',
                    600: '#6B7280',
                    700: '#4B5563',
                    800: '#374151',
                    900: '#1F2937',
                    950: '#111827',
                },
                danger: colors.rose,
                success: colors.green,
                warning: colors.yellow,
            },
        },
    },

    variants: {
        borderColor: ['responsive', 'hover', 'focus'],
        borderWidth: ['responsive', 'hover', 'focus'],
    },

    plugins: [
        require('@tailwindcss/forms')({
            strategy: 'class',
        }),
        require('@tailwindcss/typography'),
    ],
};
