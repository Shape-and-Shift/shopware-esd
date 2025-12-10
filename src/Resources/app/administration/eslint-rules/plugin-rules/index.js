/**
 * @package admin
 */

const path = require('path');

module.exports = {
    rules: {
         
        'no-src-imports': require(path.resolve(__dirname, 'no-src-imports.js')),
    },
};
