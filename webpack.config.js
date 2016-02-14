var path = require('path');
var webpack = require('webpack');

module.exports = {
    module: {
        loaders: [
            {test: /\.coffee$/, loader: "coffee"}
        ]
    },
    plugins: [
        new webpack.ProvidePlugin({
            $: "jquery",
            jQuery: "jquery"
        })
    ],
    entry: './resources/assets/scripts/app/app.coffee',
    output: {
        path: __dirname + '/public',
        filename: './build/scripts/app.js',
        publicPath: 'http://localhost:8080/build/'
    },
    //watch: true,
    //watchDelay: 100,
    devtool: 'source-map',
    resolve: {
        extensions: ["", ".webpack.js", ".web.js", ".js", ".coffee"]
    }
};
