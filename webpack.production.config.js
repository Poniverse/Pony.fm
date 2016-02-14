var path = require('path');
var webpack = require('webpack');
var _ = require('underscore');

var webpackBaseConfig = require('./webpack.base.config.js');
var config = _.clone(webpackBaseConfig);

config.plugins.push(
    new webpack.optimize.UglifyJsPlugin({
        compress: {
            warnings: false
        }
    })
);

//config.output.publicPath = 'https://pony.fm/build/';


module.exports = config;
