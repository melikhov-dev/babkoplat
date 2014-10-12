// This is the main application configuration file.  It is a Grunt
// configuration file, which you can learn more about here:
// https://github.com/cowboy/grunt/blob/master/docs/configuring.md
'use strict';

module.exports = function (grunt) {
    var pkg, jsFiles, components;

    pkg        = grunt.file.readJSON('package.json');
    components = [
        'config/header.js',
        'config/router.js',
        'src/**/*.js'
    ];

    jsFiles = function (env) {
        return Array.prototype.concat.call(pkg.vendors, components, ['config/app.config.'+ env +'.js', 'config/footer.js']);
    };

    grunt.initConfig({
        pkg: pkg,
        webDir: '../web',
        buildDir: 'build',
        templateDir : '../app/templates',
        projectDir: '.',
        resourcesDir: 'resources/public',
        version: grunt.template.today("yyyymmddhhMM"),




        less: {
            all: {
                options: {
                    paths: ['<%= resourcesDir %>/less'],
                    compress: true
                },
                files: {
                    '<%= buildDir %>/css/main.<%= version %>.css': '<%= resourcesDir %>/less/global.less'
                }
            }
        },

        concat: {
            styles: {
                src: [
                    '<%= buildDir %>/css/main.<%= version %>.css'
                ],
                dest: '<%= buildDir %>/css/styles.<%= version %>.css'
            },
            'scripts.prod': {
                expand: false,
                src: jsFiles('prod'),
                dest: '<%= buildDir %>/js/all.<%= version %>.build.js'
            },
            'scripts.test': {
                expand: false,
                src: jsFiles('test'),
                dest: '<%= buildDir %>/js/all.<%= version %>.build.js'
            },
            'scripts.stage': {
                expand: false,
                src: jsFiles('stage'),
                dest: '<%= buildDir %>/js/all.<%= version %>.build.js'
            },
            'scripts.dev': {
                expand: false,
                src: jsFiles('dev'),
                dest: '<%= buildDir %>/js/all.<%= version %>.js'
            }
        },

        copy: {
            resources: {
                files: [
                    {
                        expand: true,
                        cwd: '<%= resourcesDir %>',
                        src: 'images/**',
                        dest: '<%= buildDir %>'
                    },
                    {
                        expand: true,
                        cwd: '<%= resourcesDir %>',
                        src: 'fonts/**',
                        dest: '<%= buildDir %>'
                    },
                    {
                        src: '<%= resourcesDir %>/favicon.ico',
                        dest: '<%= buildDir %>/favicon.ico'
                    },
                    {
                        src: '<%= resourcesDir %>/.htaccess',
                        dest: '<%= buildDir %>/.htaccess'
                    },
                    {
                        src: '<%= resourcesDir %>/robots.txt',
                        dest: '<%= webDir %>/robots.txt'
                    }
                ]
            },
            build: {
                files: [
                    {
                        expand: true,
                        cwd: '<%= buildDir %>',
                        src: ['*', '*/**', '.*'],
                        dest: '<%= webDir %>'
                    }
                ]
            }
        },

        preprocess: {
            html: {
                files: {
                    '<%= templateDir %>/index.jade': '<%= resourcesDir %>/index.jade.html',
                },
                options: {
                    context: {
                        NAME: '<%= pkg.name %>',
                        VERSION: '<%= version %>',
                        DEBUG: false,
                        ENV: 'prod'
                    }
                }
            },

            'html.dev': {
                files: {
                    '<%= buildDir %>/index.html': '<%= resourcesDir %>/index.html',
                },
                options: {
                    context: {
                        NAME: '<%= pkg.name %>',
                        VERSION: '<%= version %>',
                        DEBUG: false,
                        ENV: 'dev'
                    }
                }
            },
        },

        watch: {
            dev: {
                files: ['Gruntfile.js', '<%= projectDir %>/**/*.js', '<%= projectDir %>/src/**/*.jade', '<%= resourcesDir %>/less/*.less'],
                tasks: ['dev', 'livereload', 'notify:build']
            }
        },

        clean: {
            dist: {
                options: {
                    force: true
                },
                src: [
                    '<%= webDir %>/src',
                    '<%= webDir %>/js',
                    '<%= webDir %>/css',
                    '<%= webDir %>/images',
                    '<%= webDir %>/fonts',
                    '<%= webDir %>/favicon.ico',
                    '<%= webDir %>/index.html'
                ]
            },
            build: {
                options: {
                    force: true
                },
                src: [
                    '<%= buildDir %>'
                ]
            },
            temp: {
                options: {
                    force: true
                },
                src: [
                    '<%= buildDir %>/css/main.<%= version %>.css',
                    '<%= buildDir %>/js/all.<%= version %>.build.js'
                ]
            }
        },

        jshint: {
            all: ['<%= projectDir %>/src/**/*.js'],
            options: {
                jshintrc : '.jshintrc'
            }
        },

        notify: {
            build: {
                options: {
                    title: 'Grunt: <%= pkg.name %>.<%= version %>',  // optional
                    message: 'Build complete', //required
                    subtitle: '' // optional, kinda a lot for a message
                }
            }
        },

        connect: {
            server: {
                port: 8001,
                baseDir: '<%= projectDir %>'
            }
        },
        uglify: {
            compressed: {
                files: [{
                    src: '<%= buildDir %>/js/all.<%= version %>.build.js',
                    dest: '<%= buildDir %>/js/all.<%= version %>.js'
                }]
            },
            debug: {
                options: {
                    sourceMap: '<%= buildDir %>/js/all.<%= version %>.sourcemap.js'
                },
                files: [{
                    src: '<%= buildDir %>/js/all.<%= version %>.build.js',
                    dest: '<%= buildDir %>/js/all.<%= version %>.js'
                }]
            }
        }
    });

    grunt.loadNpmTasks('grunt-preprocess');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-notify');
    grunt.loadNpmTasks('grunt-contrib-connect');
    grunt.loadNpmTasks('grunt-contrib-livereload');

    grunt.registerTask('prepare', ['clean:build', 'copy:resources', 'less:all', 'concat:styles', 'preprocess:html']);
    grunt.registerTask('install', ['clean:temp', 'clean:dist', 'copy:build', 'clean:build']);

    grunt.registerTask('prod', ['prepare', 'concat:scripts.prod', 'uglify:compressed', 'install']);
    grunt.registerTask('test', ['prepare', 'concat:scripts.test', 'uglify:compressed', 'install']);
    grunt.registerTask('stage', ['prepare', 'concat:scripts.stage', 'uglify:compressed', 'install']);
    grunt.registerTask('dev', ['prepare', 'concat:scripts.dev', 'install']);

    grunt.registerTask('default', ['dev', 'connect:server', 'watch:dev']);
};
