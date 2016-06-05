var path = require('path');
var webpack = require('webpack');

// NOTE: This is a base config; it's not meant to be used directly!

module.exports = {
    module: {
        loaders: [
            {test: /\.coffee$/, loader: "coffee"}
        ],
        noParse: [/pfm-angular-marked\.js/]
    },
    plugins: [
        new webpack.ProvidePlugin({
            $: "jquery",
            jQuery: "jquery"
        }),
        new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/)
    ],
    entry: {
        app: './resources/assets/scripts/app/app.coffee',
        embed: './resources/assets/scripts/embed/embed.coffee'
    },
    output: {
        path: __dirname + '/public',
        filename: './build/scripts/[name].js'
        // publicPath should be defined in the dev config!
    },
    resolve: {
        extensions: ["", ".webpack.js", ".web.js", ".js", ".coffee"]
    }
};
