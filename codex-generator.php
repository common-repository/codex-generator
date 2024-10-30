<?php
/*
Plugin Name: Codex Generator
Plugin URI: http://wordpress.org/extend/plugins/codex-generator/
Description: Codex Generator is search, research and documentation generation tool for WordPress functions.
Author: Andrey "Rarst" Savchenko
Author URI: http://www.rarst.net/
Version: 1.2
Text Domain: codex_gen
Domain Path: /lang
License: GPLv2 or later
*/

// TODO encode = in argument description, case wp_filter_object_list()

define( 'CODEX_GENERATOR_FILE', __FILE__ );
define( 'CODEX_GENERATOR_LINK', add_query_arg( 'page', 'codex_gen', admin_url( 'tools.php' ) ) );
define( 'CODEX_GENERATOR_PHP', dirname( __FILE__ ) . '/php/' );

require CODEX_GENERATOR_PHP . 'class-utility.php';
require CODEX_GENERATOR_PHP . 'class.php';
require CODEX_GENERATOR_PHP . 'class-phpdoc-parser.php';
require CODEX_GENERATOR_PHP . 'class-function-query.php';
require CODEX_GENERATOR_PHP . 'class-functions-table.php';

Codex_Generator::on_load();