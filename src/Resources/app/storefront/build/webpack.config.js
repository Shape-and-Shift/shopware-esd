const { join, resolve } = require('path');
module.exports = () => {
    return {
        resolve: {
            alias: {
                '@plyr': resolve(
                    join(__dirname, '..', 'node_modules', 'plyr')
                )
            }
        }
    };
}
