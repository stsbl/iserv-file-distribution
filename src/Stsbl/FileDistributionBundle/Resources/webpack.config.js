// src/Stsbl/EximStatsBundle/Resources/webpack.config.js
let merge = require('webpack-merge');
let path = require('path');
let baseConfig = require(path.join(process.env.WEBPACK_BASE_PATH, 'webpack.config.base.js'));

let webpackConfig = {
    entry: {
        'js/file-distribution': './assets/js/file-distribution.js',
        'js/file-distribution-autocomplete': './assets/js/file-distribution-autocomplete.js',
        'js/file-distribution-highlight': './assets/js/file-distribution-highlight.js',
        'css/file-distribution': './assets/less/file-distribution.less',
    },
};

module.exports = merge(baseConfig.get(__dirname), webpackConfig);