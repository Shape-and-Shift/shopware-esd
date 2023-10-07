const { join, resolve } = require('path');
module.exports = () => {
    return {
        resolve: {
            alias: {
                '@lodash': resolve(
                    join(__dirname, '..', 'node_modules', 'lodash')
                ),
                '@mime-types': resolve(
                    join(__dirname, '..', 'node_modules', 'mime-types')
                ),
                '@papaparse': resolve(
                    join(__dirname, '..', 'node_modules', 'papaparse')
                ),
            }
        }
    };
}
