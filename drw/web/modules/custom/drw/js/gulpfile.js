/*
 remove node_modules and clean install
 $ sudo rm -rf node_modules/

 install the latest Gulp 4 CLI tools globally
 $ npm install gulpjs/gulp-cli -g

 install Gulp 4 into your project
 $ npm install 'gulpjs/gulp.git#4.0' --save-dev

 Install the rest:

 $ sudo npm install gulp-minify-css gulp-sass gulp-autoprefixer gulp-sass-glob gulp-imagemin imagemin-pngquant imagemin-jpegtran imagemin-gifsicle imagemin-optipng gulp-merge-media-queries del gulp-uglify gulp-concat browser-sync --save-dev

 and then you can start the gulp watch process in the same folder with: $ gulp watch
 */

var gulp = require('gulp'),
    package = require('./package.json');

/***************
 * COCOMORE CUSTOM TASKS
 **************/

var PATH = '';
function clean() {
    return del(['css']);
}

/*
gulp.task('browserSync', function() {
    browserSync.init({
        open: 'external',
        host: 'msconnect.local',
        proxy: 'msconnect.local', // or project.dev/app/
        port: 3000
    });
});
*/

function images() {
    return gulp.src(PATH + 'images/**/*')
        .pipe(imagemin({
            progressive: true,
            svgoPlugins: [{removeViewBox: false}],
            use: [pngquant(), jpegtran(), optipng(), gifsicle()]
        }))
        .pipe(gulp.dest(PATH + 'images'));
}

function css() {
    return gulp.src(PATH + 'css/**/*.css')
        .pipe(autoprefixer())
        .pipe(uncss())
        .pipe(minifyCss({keepBreaks: false}))
        .pipe(gulp.dest(PATH + 'css/'));
}

function styles() {
    return gulp.src(PATH + 'scss/**/*.scss')
        .pipe(sassGlob())
        .pipe(sass().on('error', sass.logError))
        .pipe(autoprefixer({
            browsers: ['> 1%', 'last 2 versions', 'ie 11', 'iOS >= 9', 'Safari >= 9', 'Android >= 5']
        }))
        .pipe(cmq())
        .pipe(gulp.dest(PATH + 'css/'))
        .pipe(browserSync.stream());
}

/**
 * we have now two js tasks: one to build the new libs file after adding or changing a library
 * and the other one for merging together the custom stuff
 *
 * for javascript run through all js files in package.json "customScripts" and merge together to main.js
 * this will ensure an atomic structure for javascript as well and we can in- or exclude files
 * that we need.
 * Furthermore we can define the order of imported js files (= dependencies)
 *
 * Please be aware that a reload won't happen after adding a new script to package json.
 *
 */

function js() {
    return gulp.src(package.customScripts)
        .pipe(concat('main.js'))
        .pipe(gulp.dest(PATH + 'js'));
}
function build_libs() {
    return gulp.src(package.libScripts)
        .pipe(concat('libs.js'))
        .pipe(gulp.dest(PATH + 'js'));
}

exports.clean = clean;
exports.styles = styles;
exports.images = images;
//exports.watch = watch;
exports.css = css;
exports.js = js;
exports.build_libs = build_libs;

/*gulp.task('styles', buildSCSS);
gulp.task('watch', gulp.series(clean, gulp.parallel(styles, images), watch));
gulp.task('default', gulp.series(clean, gulp.parallel(styles, images)));
gulp.task('js', js);*/
gulp.task('build-libs', build_libs);