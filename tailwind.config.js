const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    mode: 'jit',
    purge: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

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
                    900: '#001B19'
                }
            }
        }
    },
    future: {
        removeDeprecatedGapUtilities: true,
    },

    variants: {
        backgroundColor: ['responsive', 'hover', 'focus'],
        borderColor: ['responsive', 'hover', 'focus'],
        borderWidth: ['responsive', 'hover', 'focus']
    },

    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography')],
};
