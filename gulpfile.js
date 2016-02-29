/* Copyright 2026 Francis Meyvis */

// npm install ; gulp

var gulp   = require('gulp'),
    uglify = require('gulp-uglify'),
    rename = require('gulp-rename');


gulp.task('minify', function() {
    return gulp.src('assets/js/googlemaps.js')
        .pipe(uglify({
            "mangle": false,
            "preserveComments": "license"
        }))
        .pipe(rename({
            'suffix': '.min'
        }))
        .pipe(gulp.dest('assets/js'));
});


gulp.task('watch', function() {
    gulp.watch(['assets/js/googlemaps.js'], ['minify'])
});


gulp.task('default', ['minify', 'watch']);
