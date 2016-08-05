/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

var gulp = require("gulp"),
    gutil = require("gulp-util"),
    plug = require("gulp-load-plugins")(),
    argv = require("yargs").argv,
    header = require("gulp-header"),
    webpack = require("webpack"),
    WebpackDevServer = require("webpack-dev-server"),
    webpackDevConfig = require("./webpack.dev.config.js"),
    webpackProductionConfig = require("./webpack.production.config.js"),
    webpackStream = require('webpack-stream'),
    _ = require("underscore");

var plumberOptions = {
    errorHandler: plug.notify.onError("Error: <%= error.message %>")
};

var licenseHeader = [
    "/**",
    "* Pony.fm - A community for pony fan music.",
    "* Copyright (C) 2016 Peter Deltchev and others",
    "*",
    "* This program is free software: you can redistribute it and/or modify",
    "* it under the terms of the GNU Affero General Public License as published by",
    "* the Free Software Foundation, either version 3 of the License, or",
    "* (at your option) any later version.",
    "*",
    "* This program is distributed in the hope that it will be useful,",
    "* but WITHOUT ANY WARRANTY; without even the implied warranty of",
    "* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the",
    "* GNU Affero General Public License for more details.",
    "*",
    "* You should have received a copy of the GNU Affero General Public License",
    "* along with this program.  If not, see <http://www.gnu.org/licenses/>.",
    "*/",
    "",
    ""
].join('\n');


gulp.task("webpack-build", function() {
    return gulp.src(_.values(webpackProductionConfig.entry))
        .pipe(webpackStream(webpackProductionConfig))
        .pipe(header(licenseHeader))
        .pipe(gulp.dest('public'));
});


gulp.task("webpack-dev-server", function () {
    // Starts a webpack-dev-server
    var compiler = webpack(webpackDevConfig);

    new WebpackDevServer(compiler, {
        // server and middleware options, currently blank
    }).listen(61999, "localhost", function (err) {
        if (err)
            throw new gutil.PluginError("webpack-dev-server", err);

        // Server listening
        gutil.log("[webpack-dev-server]", "http://localhost:61999/webpack-dev-server/index.html");
    });
});


gulp.task("styles-app", function () {
    var includedStyles = [
        "node_modules/angular-material/angular-material.css",
        "resources/assets/styles/app.less",
    ];

    if (!argv.production) {
        // we also want to add the embed stuff, since we're in development mode
        // we want to watch embed files and re-compile them. However, we want
        // to leave this path out in production so that embed files are not bloating
        // the css file
        includedStyles.push("resources/assets/styles/embed.less");

        // Remove app.less from the cache so that it gets recompiled
        var styleCache = plug.cached.caches.styles;
        for (var file in styleCache) {
            if (!styleCache.hasOwnProperty(file))
                continue;

            if (!endsWith(file, "app.less"))
                continue;

            delete styleCache[file];
        }
    }

    // note that we're not doing autoprefixer on dev builds for now to shave off roughly 600-700 milliseconds per
    // build. It's already taking forever to recompile the less

    if (argv.production) {
        // Production pipeline
        return gulp.src(includedStyles)
            .pipe(plug.plumber(plumberOptions))
            .pipe(plug.if(/\.less/, plug.less()))
            .pipe(plug.autoprefixer({
                browsers: ["last 2 versions"],
                cascade: false
            }))
            .pipe(plug.concat("app.css"))
            .pipe(plug.cleanCss())
            .pipe(header(licenseHeader))
            .pipe(gulp.dest("public/build/styles"));
    } else {
        // Development pipeline
        return gulp.src(includedStyles)
            .pipe(plug.plumber(plumberOptions))
            .pipe(plug.cached("styles"))
            .pipe(plug.sourcemaps.init())
            .pipe(plug.if(/\.less/, plug.less()))
            .pipe(plug.concat("app.css"))
            .pipe(header(licenseHeader))
            .pipe(plug.sourcemaps.write())
            .pipe(gulp.dest("public/build/styles"))
            .pipe(plug.livereload());
    }
});

gulp.task("styles-embed", function () {
    // note that this task should really only ever be invoked for production
    // since development-mode watches and builds include the embed styles
    // already

    return gulp.src(["resources/assets/styles/embed.less"], {base: "resources/assets/styles"})
        .pipe(plug.less())
        .pipe(plug.autoprefixer({
            browsers: ["last 2 versions"],
            cascade: false
        }))
        .pipe(plug.concat("embed.css"))
        .pipe(plug.cleanCss())
        .pipe(header(licenseHeader))
        .pipe(gulp.dest("public/build/styles"));
});

gulp.task('copy:templates', function () {
    gulp.src([
        'public/templates/**/*.html'
    ])
        .pipe(plug.angularTemplatecache({
            module: "ponyfm",
            root: "/templates"
        }))
        .pipe(header(licenseHeader))
        .pipe(gulp.dest('public/build/scripts'));
});

gulp.task('build', [
    'webpack-build',
    'copy:templates',
    'styles-app',
    'styles-embed'
]);

gulp.task("watch-legacy", ["build"], function () {
    gulp.watch("resources/assets/styles/**/*.{css,less}", ["styles-app"]);
});

gulp.task("watch", ["webpack-dev-server", "watch-legacy"], function () {});


function endsWith(str, suffix) {
    return str.indexOf(suffix, str.length - suffix.length) !== -1;
}
