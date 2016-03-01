/* Copyright 2016 Francis Meyvis */

// npm install ; gulp

var gulp   = require('gulp'),
    uglify = require('gulp-uglify'),
    rename = require('gulp-rename');


gulp.task('minify', function() {
    return gulp.src('assets/js/googlemaps.js')
        .pipe(uglify({
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
