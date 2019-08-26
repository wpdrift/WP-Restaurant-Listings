/* jshint node:true */
module.exports = function( grunt ){
	'use strict';

	grunt.initConfig({
		// setting folder templates
		dirs: {
			css: 'assets/css',
			fonts: 'assets/font',
			images: 'assets/images',
			js: 'assets/js',
			build: 'tmp/build',
			svn: 'tmp/release-svn'
		},

		// Compile all .less files.
		less: {
			compile: {
				options: {
					// These paths are searched for @imports
					paths: ['<%= dirs.css %>/']
				},
				files: [{
					expand: true,
					cwd: '<%= dirs.css %>/',
					src: [
						'*.less',
						'!icons.less',
						'!mixins.less'
					],
					dest: '<%= dirs.css %>/',
					ext: '.css'
				}]
			}
		},

		// Minify all .css files.
		cssmin: {
			minify: {
				expand: true,
				cwd: '<%= dirs.css %>/',
				src: ['*.css'],
				dest: '<%= dirs.css %>/',
				ext: '.css'
			}
		},

		// Minify .js files.
		uglify: {
			options: {
				preserveComments: 'some'
			},
			frontend: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.js %>',
					ext: '.min.js'
				}]
			},
		},

		copy: {
			main: {
				src: [
					'**',
					'!*.log', // Log Files
					'!node_modules/**', '!Gruntfile.js', '!package.json','!package-lock.json', // NPM/Grunt
					'!.git/**', '!.github/**', // Git / Github
					'!tests/**', '!bin/**', '!phpunit.xml', '!phpunit.xml.dist', // Unit Tests
					'!vendor/**', '!composer.lock', '!composer.phar', '!composer.json', // Composer
					'!.*', '!**/*~', '!tmp/**', //hidden/tmp files
					'!CONTRIBUTING.md',
					'!readme.md',
					'!phpcs.ruleset.xml',
					'!tools/**'
				],
				dest: '<%= dirs.build %>/'
			}
		},

		// Watch changes for assets
		watch: {
			less: {
				files: ['<%= dirs.css %>/*.less'],
				tasks: ['less', 'cssmin'],
			},
			js: {
				files: [
					'<%= dirs.js %>/*js',
					'!<%= dirs.js %>/*.min.js',
				],
				tasks: ['uglify']
			}
		},

		// Generate POT files.
		makepot: {
			options: {
				type: 'wp-plugin',
				domainPath: '/languages',
				potHeaders: {
					'report-msgid-bugs-to': 'https://github.com/wpdrift/WP-Restaurant-Listings/issues',
					'language-team': 'LANGUAGE <EMAIL@ADDRESS>'
				}
			},
			dist: {
				options: {
					potFilename: 'wp-restaurant-listings.pot',
					exclude: [
						'apigen/.*',
						'tests/.*',
						'tmp/.*',
						'vendor/.*',
						'node_modules/.*'
					]
				}
			}
		},

		// Check textdomain errors.
		checktextdomain: {
			options:{
				text_domain: 'wp-restaurant-listings',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src:  [
					'**/*.php',         // Include all files
					'!apigen/**',       // Exclude apigen/
					'!node_modules/**', // Exclude node_modules/
					'!tests/**',        // Exclude tests/
					'!vendor/**',       // Exclude vendor/
					'!tmp/**'           // Exclude tmp/
				],
				expand: true
			}
		},

		addtextdomain: {
			wprestaurantmanager: {
				options: {
					textdomain: 'wp-restaurant-listings'
				},
				files: {
					src: [
						'*.php',
						'**/*.php',
						'!node_modules/**'
					]
				}
			}
		},

		wp_deploy: {
			deploy: {
				options: {
					plugin_slug: 'wp-restaurant-listings',
					build_dir: '<%= dirs.build %>',
					tmp_dir: '<%= dirs.svn %>/',
					max_buffer: 1024 * 1024
				}
			}
		},

		zip: {
			'main': {
				cwd: '<%= dirs.build %>/',
				src: [ '<%= dirs.build %>/**' ],
				dest: 'tmp/wp-restaurant-listings.zip'
			}
		},

		clean: {
			main: [ 'tmp/' ], //Clean up build folder
		},

		jshint: {
			options: grunt.file.readJSON('.jshintrc'),
			src: [
				'assets/js/**/*.js',
				'!assets/js/**/*.min.js',
				// External Libraries:
				'!assets/js/jquery-deserialize/*.js',
				'!assets/js/jquery-fileupload/*.js',
				'!assets/js/jquery-tiptip/*.js'
			]
		},

		checkrepo: {
			deploy: {
				tagged: true,
				clean: true
			}
		},

		wp_readme_to_markdown: {
			readme: {
				files: {
					'readme.md': 'readme.txt'
				}
			}
		}
	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-contrib-less' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify-es' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-checktextdomain' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-gitinfo' );
	grunt.loadNpmTasks( 'grunt-checkbranch' );
	grunt.loadNpmTasks( 'grunt-wp-deploy' );
	grunt.loadNpmTasks( 'grunt-checkrepo' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown');
	grunt.loadNpmTasks( 'grunt-zip' );

	grunt.registerTask( 'build', [ 'gitinfo', 'test', 'clean', 'copy' ] );

	grunt.registerTask( 'deploy', [ 'checkbranch:master', 'checkrepo', 'build', 'wp_deploy' ] );
	grunt.registerTask( 'deploy-unsafe', [ 'build', 'wp_deploy' ] );

	grunt.registerTask( 'package', [ 'build', 'zip' ] );

	// Register tasks
	grunt.registerTask( 'default', [
		'less',
		'cssmin',
		'uglify',
		'wp_readme_to_markdown'
	] );

	// Just an alias for pot file generation
	grunt.registerTask( 'pot', [
		'makepot'
	] );

	grunt.registerTask( 'test', [
		'phpunit'
	] );

	grunt.registerTask( 'dev', [
		'test',
		'default'
	] );
};
