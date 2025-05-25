/**
 * @package storefront
 */

const fs = require('fs-extra');
const path = require('path');

/**
 * Start time of the process for execution measuring
 * @type {[number, number]}
 */
const startTime = process.hrtime();

/**
 * Output directory
 * @type {String}
 */
const toDirectory = 'vendor/plyr';

/**
 * Directories to copy
 * @type {String[]}
 */
const fromDirectories = [
    'node_modules/plyr/src/sass',
];

/**
 * Iterates over the from directories and starts the copy process.
 *
 * @param {String[]}directories
 * @return {Promise[]}
 */
function iterateFromDirectories(directories) {
    return directories.map((relativeDirectory) => {
        const fullFromPath = path.join(__dirname, relativeDirectory);
        const folderName = relativeDirectory.split('/').pop();
        const fullToPath = path.join(__dirname, toDirectory, folderName);
        return fs.copy(fullFromPath, fullToPath)
            .then(() => {
                console.warn(`- copied "${fullFromPath}" to "${fullToPath}"`);
            });
    });
}

function onCopyProcess(results) {
    // React to the result of the copy process
    Promise.all(results)
        .then(() => {
            console.warn('');
            console.warn('✓ Done, all directories / files copied successfully.');

            const endTime = process.hrtime(startTime);
            console.warn(`Execution time: ${endTime[1] / 1000000}ms`);
        })
        .catch((err) => {
            console.warn('');
            console.error('✖ An error occurred while copying files');

            const endTime = process.hrtime(startTime);
            console.warn(`Execution time: ${endTime[1] / 1000000}ms`);

            throw err;
        })
}

onCopyProcess(iterateFromDirectories(fromDirectories));


