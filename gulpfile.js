var gulp = require("gulp"),
    plug = require("gulp-load-plugins")(),
    argv = require("yargs").argv;

var plumberOptions = {
    errorHandler: plug.notify.onError("Error: <%= error.message %>")
};

gulp.task("scripts-app", function() {
    var paths = [
        "app/scripts/app/**/*.{coffee,js}",
        "app/scripts/base/**/*.{coffee,js}",
        "app/scripts/shared/**/*.{coffee,js}"
    ];

    if (!argv.production) {
        paths.push("app/scripts/debug/**/*.{coffee,js}");

        // we also want to add the embed stuff, since we're in development mode
        // we want to watch embed files and re-compile them. However, we want
        // to leave this path out in production so that embed files are not bloating
        // the js file
        paths.push("app/scripts/embed/**/*.{coffee,js}");
    }

    return argv.production
        // Production pipeline
        ? gulp.src(paths, {base: "app/scripts"})
            .pipe(plug.plumber(plumberOptions))
            .pipe(plug.order([
                "app/scripts/base/jquery-2.0.2.js",
                "app/scripts/base/angular.js",
                "app/scripts/base/*.{coffee,js}",
                "app/scripts/shared/*.{coffee,js}",
                "app/scripts/app/*.{coffee,js}",
                "app/scripts/app/services/*.{coffee,js}",
                "app/scripts/app/filters/*.{coffee,js}",
                "app/scripts/app/directives/*.{coffee,js}",
                "app/scripts/app/controllers/*.{coffee,js}",
                "app/scripts/**/*.{coffee,js}"
            ], {base: "."}))
            .pipe(plug.if(/\.coffee/, plug.coffee()))
            .pipe(plug.concat("app.js"))
            .pipe(plug.uglify())
            .pipe(gulp.dest("public/build/scripts"))
        // Development/watch pipeline
        : gulp.src(paths, {base: "app/scripts"})
            .pipe(plug.plumber(plumberOptions))
            .pipe(plug.cached('scripts'))
            .pipe(plug.sourcemaps.init())
            .pipe(plug.if(/\.coffee/, plug.coffee()))
            .pipe(plug.sourcemaps.write({
                includeContent: false,
                sourceRoot: "/dev-scripts/"
            }))
            .pipe(gulp.dest("public/build/scripts"));
});

gulp.task("scripts-embed", function() {
    // note that this task should really only ever be invoked for production
    // since development-mode watches and builds include the embed scripts
    // already

    var includedScripts = [
        "app/scripts/base/jquery-2.0.2.js",
        "app/scripts/base/jquery.viewport.js",
        "app/scripts/base/underscore.js",
        "app/scripts/base/moment.js",
        "app/scripts/base/jquery.timeago.js",
        "app/scripts/base/soundmanager2-nodebug.js",
        "app/scripts/embed/*.coffee"
    ];
    
    return gulp.src(includedScripts, {base: "app/scripts"})
        .pipe(plug.plumber(plumberOptions))
        .pipe(plug.if(/\.coffee/, plug.coffee()))
        .pipe(plug.order(includedScripts, {base: "."}))
        .pipe(plug.concat("embed.js"))
        .pipe(plug.uglify())
        .pipe(gulp.dest("public/build/scripts"));
});

gulp.task("styles-app", function() {
    var includedStyles = [
        "app/styles/base/jquery-ui.css",
        "app/styles/base/colorbox.css",
        "app/styles/app.less"
    ];

    if (!argv.production) {
        includedStyles.push("app/styles/profiler.less");
        includedStyles.push("app/styles/prettify.css");

        // we also want to add the embed stuff, since we're in development mode
        // we want to watch embed files and re-compile them. However, we want
        // to leave this path out in production so that embed files are not bloating
        // the css file
        includedStyles.push("app/styles/embed.css");

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
        ? gulp.src(includedStyles, {base: "app/styles"})
            .pipe(plug.plumber(plumberOptions))
            .pipe(plug.if(/\.less/, plug.less()))
            .pipe(plug.autoprefixer({browsers: ["last 2 versions"], cascade: false}))
            .pipe(plug.concat("app.css"))
            .pipe(plug.minifyCss())
            .pipe(gulp.dest("public/build/styles"))
        // Development pipeline
        : gulp.src(includedStyles, {base: "app/styles"})
            .pipe(plug.plumber(plumberOptions))
            .pipe(plug.cached("styles"))
            .pipe(plug.sourcemaps.init())
            .pipe(plug.if(/\.less/, plug.less()))
            .pipe(plug.sourcemaps.write({
                includeContent: false,
                sourceRoot: "/dev-styles/"
            }))
            .pipe(gulp.dest("public/build/styles"))
            .pipe(plug.livereload());
});

gulp.task("styles-embed", function() {
    // note that this task should really only ever be invoked for production
    // since development-mode watches and builds include the embed styles
    // already

    return gulp.src(["app/styles/embed.less"], {base: "app/styles"})
        .pipe(plug.less())
        .pipe(plug.autoprefixer({browsers: ["last 2 versions"], cascade: false}))
        .pipe(plug.concat("embed.css"))
        .pipe(plug.minifyCss())
        .pipe(gulp.dest("public/build/styles"));
});

gulp.task('copy:templates', function() {
    gulp.src([
        'public/templates/**/*.html'
    ])
            .pipe(plug.angularTemplatecache({
            module: "ponyfm",
            root: "/templates"
        }))
        .pipe(gulp.dest('public/build/scripts'));
});

gulp.task('build', [
    'scripts-app',
    'styles-app',
    'scripts-embed',
    'styles-embed'
]);

gulp.task("watch", function() {
    plug.livereload.listen();
    gulp.watch("app/scripts/**/*.{coffee,js}", ["scripts-app"]);
    gulp.watch("app/styles/**/*.{css,less}", ["styles-app"]);
});

function endsWith(str, suffix) {
    return str.indexOf(suffix, str.length - suffix.length) !== -1;
}