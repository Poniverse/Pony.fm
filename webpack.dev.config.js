var path = require('path');
var webpack = require('webpack');
var _ = require('underscore');

var webpackBaseConfig = require('./webpack.base.config.js');
var config = _.clone(webpackBaseConfig);

config.devtool = 'eval-source-map';

config.devHost = 'localhost';
config.devPort = '6199';
config.devUrl  = 'http://' + config.devHost + ':' + (config.devPort == '80' ? '' : config.devPort);

config.output.publicPath = config.devUrl + '/build/';


module.exports = config;
