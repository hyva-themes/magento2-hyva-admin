const colors = require("tailwindcss/colors");
const magento = {
    header: '#5f564f',
    color: '#514943',
    background: {
        DEFAULT: '#f5f5f5',
        hover: '#dbdbdb'
    }
}
module.exports = {
    purge: ["../../../../../../Hyva*/**/adminhtml/templates/**/*.phtml"],
    darkMode: false,
    theme: {
        fontSize: {
            xs: ['0.75em', { lineHeight: '1em' }],
            sm: ['0.875em', { lineHeight: '1.25em' }],
            base: ['1em', { lineHeight: '1.5em' }],
            lg: ['1.125em', { lineHeight: '1.75em' }],
            xl: ['1.25em', { lineHeight: '1.75em' }],
            '2xl': ['1.5em', { lineHeight: '2em' }],
            '3xl': ['1.875em', { lineHeight: '2.25em' }],
            '4xl': ['2.25em', { lineHeight: '2.5em' }],
            '5xl': ['3em', { lineHeight: '1' }],
            '6xl': ['3.75em', { lineHeight: '1' }],
            '7xl': ['4.5em', { lineHeight: '1' }],
            '8xl': ['6em', { lineHeight: '1' }],
            '9xl': ['8em', { lineHeight: '1' }],
        },
        colors: {
            gray: colors.warmGray,
        },
        textColor: {
            white: colors.white,
            black: colors.black,
            gray: colors.gray,
            primary: {
                lighter: colors.gray["700"],
                DEFAULT: magento.color,
                darker: colors.gray["900"],
            },
            secondary: {
                lighter: colors.gray["400"],
                DEFAULT: colors.gray["500"],
                darker: colors.gray["600"],
            },
        },
        backgroundColor: {
            white: colors.white,
            gray: colors.gray,
            primary: {
                lighter: colors.gray["200"],
                DEFAULT: magento.background.DEFAULT,
                darker: magento.background.hover,
            },
            secondary: {
                lighter: colors.gray["100"],
                DEFAULT: colors.gray["200"],
                darker: colors.gray["300"],
            },
            container: {
                lighter: colors.white,
                DEFAULT: colors.gray["100"],
                darker: colors.gray["200"],
                header: magento.color,
                hover: magento.header
            },
        },
        borderColor: {
            gray: colors.gray,
            transparent: colors.transparent,
            primary: {
                lighter: colors.gray["300"],
                DEFAULT: colors.gray["500"],
                darker: magento.header,
            },
            secondary: {
                lighter: colors.gray["100"],
                DEFAULT: colors.gray["200"],
                darker: colors.gray["300"],
            },
            container: {
                lighter: colors.gray["100"],
                DEFAULT: colors.gray["200"],
                darker: colors.gray["300"],
            },
        },
    },
    variants: {
        extend: {},
    },
    plugins: [
        require("@tailwindcss/forms")({strategy: 'class'})
    ],
};
