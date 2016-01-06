var gulp = require('gulp'),
    jshint = require('gulp-jshint'),
    uglify = require('gulp-uglify'),
    sourcemaps = require('gulp-sourcemaps'),
    concat = require('gulp-concat'),
    ngAnnotate = require('gulp-ng-annotate'),
    mainBowerFiles = require('main-bower-files'),
    browserSync = require('browser-sync');

var bower_js_files = ["bower_components/jquery/dist/jquery.min.js",
    "bower_components/angular/angular.min.js",
    "bower_components/angular-sanitize/angular-sanitize.min.js",
    "bower_components/angular-ui-router/release/angular-ui-router.min.js",
    "bower_components/angular-messages/angular-messages.min.js",
    "bower_components/lodash/lodash.min.js",
    "bower_components/angular-facebook/lib/angular-facebook.js",
    "bower_components/angular-animate/angular-animate.min.js",
    "bower_components/restangular/dist/restangular.min.js",
    "bower_components/ngstorage/ngStorage.min.js",
    "bower_components/angular-aria/angular-aria.min.js",
    "bower_components/angular-material/angular-material.min.js",
    "bower_components/angular-loading-bar/build/loading-bar.min.js",
    "bower_components/moment/min/moment.min.js",
    "bower_components/angular-moment/angular-moment.min.js",
    "plugins/ng-text-truncate/ng-text-truncate.js",
    "bower_components/ngEmbed/dist/ng-embed.min.js",
    "bower_components/ng-file-upload/ng-file-upload.min.js",
    "bower_components/angular-emoji-popup/dist/js/config.js",
    "bower_components/angular-emoji-popup/dist/js/emoji.min.js",
    "bower_components/angular-elastic/elastic.js"
];


gulp.task('js', function() {
    return gulp.src(['js/*.js', 'controllers/*.js', 'directives/*.js', 'filters/*.js', 'services/*.js'])
        .pipe(sourcemaps.init({
            loadMaps: true
        }))
        .pipe(ngAnnotate())
        .pipe(jshint())
        .pipe(jshint.reporter('default'))
        .pipe(uglify())
        .pipe(concat('app.js'))
        .pipe(sourcemaps.write('maps', {
            sourceRoot: '/'
        }))
        .pipe(gulp.dest('dist'));
});

gulp.task('js_libs', function() {
    return gulp.src(bower_js_files)
        .pipe(uglify())
        .pipe(concat('libs.js'))
        .pipe(gulp.dest('dist'));
});

gulp.task('watch', function() {
    gulp.watch(['js/*.js', 'controllers/*.js', 'directives/*.js', 'filters/*.js', 'services/*.js'], ['js']);

});



gulp.task('browser-sync', function() {
    var files = [
        'app/**/*.html',
        'app/assets/css/**/*.css',
        'app/assets/imgs/**/*.png',
        'app/assets/js/**/*.js'
    ];

    browserSync.init(files, {
        server: {
            baseDir: './app'
        }
    });
});
