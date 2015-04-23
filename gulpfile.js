var gulp = require("gulp"),
    coffee = require("gulp-coffee"),
    concat = require("gulp-concat"),
    sourcemaps = require("gulp-sourcemaps"),
    cached = require("gulp-cached"),
    plumber = require("gulp-plumber"),
    notify = require("gulp-notify"),
    order = require("gulp-order"),
    argv = require("yargs").argv,
    gulpif = require("gulp-if"),
    uglify = require("gulp-uglify"),
    less = require("gulp-less"),
    minifyCss = require('gulp-minify-css');

var plumberOptions = {
    errorHandler: notify.onError("Error: <%= error.message %>")
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
            .pipe(plumber(plumberOptions))
            .pipe(order([
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
            .pipe(gulpif(/\.coffee/, coffee()))
            .pipe(concat("app.js"))
//            .pipe(uglify())
            .pipe(gulp.dest("public/build/scripts"))
        // Development/watch pipeline
        : gulp.src(paths, {base: "app/scripts"})
            .pipe(plumber(plumberOptions))
            .pipe(cached('scripts'))
            .pipe(sourcemaps.init())
            .pipe(gulpif(/\.coffee/, coffee()))
            .pipe(sourcemaps.write())
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
        .pipe(plumber(plumberOptions))
        .pipe(gulpif(/\.coffee/, coffee()))
        .pipe(order(includedScripts, {base: "."}))
        .pipe(concat("embed.js"))
        .pipe(uglify())
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
    }

    return argv.production
        // Production pipeline
        ? gulp.src(includedStyles, {base: "app/styles"})
            .pipe(plumber(plumberOptions))
            .pipe(gulpif(/\.less/, less()))
            .pipe(concat("app.css"))
            .pipe(minifyCss())
            .pipe(gulp.dest("public/build/styles"))
        // Development pipeline
        : gulp.src(includedStyles, {base: "app/styles"})
            .pipe(plumber(plumberOptions))
            .pipe(sourcemaps.init())
            .pipe(gulpif(/\.less/, less()))
            .pipe(sourcemaps.write())
            .pipe(gulp.dest("public/build/styles"));
});

gulp.task("styles-embed", function() {
    // note that this task should really only ever be invoked for production
    // since development-mode watches and builds include the embed styles
    // already

    return gulp.src(["app/styles/embed.less"], {base: "app/styles"})
        .pipe(less())
        .pipe(concat("embed.css"))
        .pipe(minifyCss())
        .pipe(gulp.dest("public/build/styles"));
});

gulp.task("watch", function() {
    gulp.watch("app/scripts/**/*.{coffee,js}", ["scripts-app"]);
    gulp.watch("app/styles/**/*.{css,less}", ["styles-app"]);
});