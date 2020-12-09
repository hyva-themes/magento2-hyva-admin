const colors = require('tailwindcss/colors');

module.exports = {
    purge: [
        '../../../../../../hyva*/**/adminhtml/templates/**/*.phtml',
    ],
  darkMode: false,
  theme: {
    extend: {
      colors: {
        orange: {...colors.orange, 950: '#373330'},
        gray: colors.warmGray,
      }
    },
  },
  variants: {
    extend: {},
  },
  plugins: [require('@tailwindcss/forms')],
}
