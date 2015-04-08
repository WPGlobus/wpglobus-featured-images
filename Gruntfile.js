/*jslint node: true */
module.exports = function (grunt) {

    'use strict';
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        wp_readme_to_markdown: {
            main: {
                files: {
                    'readme.md': 'readme.txt'
                },
            },
        },
    });

    grunt.loadNpmTasks('grunt-wp-readme-to-markdown');

};
