var path = require('path');
var webpack = require('webpack');
var _ = require('underscore');

var webpackBaseConfig = require('./webpack.base.config.js');
var config = _.clone(webpackBaseConfig);

config.devtool = 'eval-source-map';
config.output.publicPath = 'http://localhost:61999/build/';


module.exports = config;
