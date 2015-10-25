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
    plug = require("gulp-load-plugins")(),
    argv = require("yargs").argv,
    header = require("gulp-header");

var plumberOptions = {
    errorHandler: plug.notify.onError("Error: <%= error.message %>")
};

var licenseHeader = [
    "/**",
    "* Pony.fm - A community for pony fan music.",
    "* Copyright (C) 2015 Peter Deltchev and others",
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
].join('\n')

gulp.task("scripts-app", function () {
    var paths = [
        "resources/assets/scripts/app/**/*.{coffee,js}",
        "resources/assets/scripts/base/**/*.{coffee,js}",
        "resources/assets/scripts/shared/**/*.{coffee,js}"
    ];

    if (!argv.production) {
        paths.push("resources/assets/scripts/debug/**/*.{coffee,js}");

        // we also want to add the embed stuff, since we're in development mode
        // we want to watch embed files and re-compile them. However, we want
        // to leave this path out in production so that embed files are not bloating
        // the js file
        paths.push("resources/assets/scripts/embed/**/*.{coffee,js}");
    }

    return argv.production
        // Production pipeline
        ? gulp.src(paths, {base: "resources/assets/scripts"})
        .pipe(plug.plumber(plumberOptions))
        .pipe(plug.order([
            "resources/assets/scripts/base/jquery-2.0.2.js",
            "resources/assets/scripts/base/angular.js",
            "resources/assets/scripts/base/*.{coffee,js}",
            "resources/assets/scripts/shared/*.{coffee,js}",
            "resources/assets/scripts/app/*.{coffee,js}",
            "resources/assets/scripts/app/services/*.{coffee,js}",
            "resources/assets/scripts/app/filters/*.{coffee,js}",
            "resources/assets/scripts/app/directives/*.{coffee,js}",
            "resources/assets/scripts/app/controllers/*.{coffee,js}",
            "resources/assets/scripts/**/*.{coffee,js}"
        ], {base: "."}))
        .pipe(plug.if(/\.coffee/, plug.coffee()))
        .pipe(plug.concat("app.js"))
        .pipe(plug.uglify())
        .pipe(header(licenseHeader))
        .pipe(gulp.dest("public/build/scripts"))
        // Development/watch pipeline
        : gulp.src(paths, {base: "resources/assets/scripts"})
        .pipe(plug.plumber(plumberOptions))
        .pipe(plug.cached('scripts'))
        .pipe(plug.sourcemaps.init())
        .pipe(plug.if(/\.coffee/, plug.coffee()))
        .pipe(plug.sourcemaps.write({
            includeContent: false,
            sourceRoot: "/dev-scripts/"
        }))
        .pipe(header(licenseHeader))
        .pipe(gulp.dest("public/build/scripts"));
});

gulp.task("scripts-embed", function () {
    // note that this task should really only ever be invoked for production
    // since development-mode watches and builds include the embed scripts
    // already

    var includedScripts = [
        "resources/assets/scripts/base/jquery-2.0.2.js",
        "resources/assets/scripts/base/jquery.viewport.js",
        "resources/assets/scripts/base/underscore.js",
        "resources/assets/scripts/base/moment.js",
        "resources/assets/scripts/base/jquery.timeago.js",
        "resources/assets/scripts/base/soundmanager2-nodebug.js",
        "resources/assets/scripts/embed/*.coffee"
    ];

    return gulp.src(includedScripts, {base: "resources/assets/scripts"})
        .pipe(plug.plumber(plumberOptions))
        .pipe(plug.if(/\.coffee/, plug.coffee()))
        .pipe(plug.order(includedScripts, {base: "."}))
        .pipe(plug.concat("embed.js"))
        .pipe(plug.uglify())
        .pipe(header(licenseHeader))
        .pipe(gulp.dest("public/build/scripts"));
});

gulp.task("styles-app", function () {
    var includedStyles = [
        "resources/assets/styles/base/jquery-ui.css",
        "resources/assets/styles/base/colorbox.css",
        "resources/assets/styles/app.less"
    ];

    if (!argv.production) {
        includedStyles.push("resources/assets/styles/profiler.less");
        includedStyles.push("resources/assets/styles/prettify.css");

        // we also want to add the embed stuff, since we're in development mode
        // we want to watch embed files and re-compile them. However, we want
        // to leave this path out in production so that embed files are not bloating
        // the css file
        includedStyles.push("resources/assets/styles/embed.css");

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

    return argv.production
        // Production pipeline
        ? gulp.src(includedStyles, {base: "resources/assets/styles"})
        .pipe(plug.plumber(plumberOptions))
        .pipe(plug.if(/\.less/, plug.less()))
        .pipe(plug.autoprefixer({
            browsers: ["last 2 versions"],
            cascade: false
        }))
        .pipe(plug.concat("app.css"))
        .pipe(plug.minifyCss())
        .pipe(header(licenseHeader))
        .pipe(gulp.dest("public/build/styles"))
        // Development pipeline
        : gulp.src(includedStyles, {base: "resources/assets/styles"})
        .pipe(plug.plumber(plumberOptions))
        .pipe(plug.cached("styles"))
        .pipe(plug.sourcemaps.init())
        .pipe(plug.if(/\.less/, plug.less()))
        .pipe(plug.sourcemaps.write({
            includeContent: false,
            sourceRoot: "/dev-styles/"
        }))
        .pipe(header(licenseHeader))
        .pipe(gulp.dest("public/build/styles"))
        .pipe(plug.livereload());
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
        .pipe(plug.minifyCss())
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
    'scripts-app',
    'styles-app',
    'scripts-embed',
    'styles-embed'
]);

gulp.task("watch", function () {
    plug.livereload.listen();
    gulp.watch("resources/assets/scripts/**/*.{coffee,js}", ["scripts-app"]);
    gulp.watch("resources/assets/styles/**/*.{css,less}", ["styles-app"]);
});

function endsWith(str, suffix) {
    return str.indexOf(suffix, str.length - suffix.length) !== -1;
}
