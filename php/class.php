<?php

class Codex_Generator extends Codex_Generator_Utility {

	/**
	 * @var string $hook_suffix holds plugin page identifier
	 */
	static $hook_suffix;

	/**
	 * @var string $function holds name of function being processed
	 */
	static $function = '';

	/**
	 * @var Codex_Generator_Functions_Table $table
	 */
	static $table;

	/**
	 * Sets up plugin's hooks during initial load.
	 */
	static function on_load() {

		self::add_method( 'admin_menu' );
		self::add_method( 'admin_init' );
		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );
	}

	/**
	 * Registers plugin's admin page in Tools section.
	 */
	static function admin_menu() {

		self::$hook_suffix = add_management_page( __( 'Codex Generator', 'codex_gen' ), __( 'Codex Generator', 'codex_gen' ), 'manage_options', 'codex_gen', array( __CLASS__, 'page' ) );
	}

	/**
	 * Loads plugin text domain, hooks load and Ajax handler for suggest.
	 */
	static function admin_init() {

		load_plugin_textdomain( 'codex_gen', false, dirname( plugin_basename( CODEX_GENERATOR_FILE ) ) . '/lang/' );
		add_action( 'load-' . self::$hook_suffix, array( __CLASS__, 'load' ) );
		add_action( 'wp_ajax_codex_gen_suggest', array( __CLASS__, 'suggest' ) );
		add_action( 'wp_ajax_codex_gen_wiki', array( __CLASS__, 'wiki' ) );
		self::add_method( 'plugin_row_meta', 10, 2 );
	}

	/**
	 * Hooks things only necessary on plugin's page.
	 */
	static function load() {

		self::add_method( 'admin_enqueue_scripts' );
		self::add_method( 'admin_print_styles' );
		self::add_method( 'contextual_help' );
		self::add_method( 'admin_notices' );
		self::add_method( 'admin_print_footer_scripts' );
		add_screen_option( 'per_page', array( 'label' => __( 'functions', 'codex_gen' ), 'default' => 15 ) );
		self::$table = new Codex_Generator_Functions_Table();
		register_column_headers( self::$hook_suffix, self::$table->get_columns() );
	}

	function set_screen_option( $false, $option, $value ) {

		if('tools_page_codex_gen_per_page' == $option)
			return $value;

		return $false;
	}

	/**
	 * Enqueues suggest.
	 */
	static function admin_enqueue_scripts() {

		wp_enqueue_script( 'suggest' );
		add_thickbox();
	}

	/**
	 * Outputs bit of CSS for suggest dropdown.
	 */
	static function admin_print_styles() {

		?><style type="text/css">
		#functions-search-input { width: 200px; }
		.top .reset { margin: 0 5px; }
		.ac_results{ min-width: 197px; }
		.tablenav p.search-box { float: left; }
		.widefat .column-version { width: 10%; }
		.widefat .column-links { text-align: left !important; }
		.widefat .column-get { width: 10%; }
		.bottom .button { float:left; margin: 5px 0; }
	</style><?php
	}

	/**
	 * Sets up suggest.
	 */
	static function admin_print_footer_scripts() {

		?><script type="text/javascript">
		jQuery(document).ready(function($) { $('#functions-search-input').suggest(ajaxurl + '?action=codex_gen_suggest'); });
		</script><?php
	}

	static function plugin_row_meta( $plugin_meta, $plugin_file ) {

		if ( false !== strpos( $plugin_file, basename( CODEX_GENERATOR_FILE ) ) ) {
			$link          = add_query_arg( 'page', 'codex_gen', admin_url( 'tools.php' ) );
			$plugin_meta[] = "<a href='$link'>" . __( 'Tools > Codex Generator', 'codex_gen' ) . '</a>';
		}

		return $plugin_meta;
	}

	/**
	 * Validates function name and generates error notices.
	 */
	static function admin_notices() {

		if ( function_exists( 'eaccelerator_info' ) ) {
			/** @noinspection PhpUndefinedFunctionInspection */
			$info = eaccelerator_info();

			if ( $info['cache'] )
				add_settings_error( 'codex_gen', 'eaccelerator', __( 'eAccelerator caching needs to be disabled to access PHPDoc information.', 'codex_gen' ) );
		}

		$doc = new ReflectionFunction( 'wp' );
		$doc = $doc->getDocComment();

		if( empty($doc['tags']) )
			add_settings_error( 'codex_gen', 'phpdoc', __( 'Could not retrieve PHPDoc information. Check that opcode caching is disabled on server.', 'codex_gen' ) );

		settings_errors( 'codex_gen' );
	}

	/**
	 * Outputs plugin's admin page.
	 */
	static function page() {

		if ( ! current_user_can( 'manage_options' ) )
			wp_die( 'You cannot access this page.' );

		?><div class="wrap"><?php

		screen_icon( 'tools' );
		echo '<h2>' . __( 'Codex Generator', 'codex_gen' ) . '</h2>';

		// TODO move to help
//			echo '<p>'. __('Don\'t know where to start? Try ', 'codex_gen') . '<a href="http://codex.wordpress.org/index.php?title=Special:WantedPages">'.__('Wanted Pages', 'codex_gen').'</a>.</p>';

		self::$table->prepare_items();

			?><form id="functions" method="get" action="">
				<input type="hidden" name="page" value="codex_gen" />
				<?php self::$table->display(); ?>
			</form>
		</div>
	<?php
	}

	/**
	 * Ajax handler for suggest.
	 */
	static function suggest() {

		if ( ! current_user_can( 'manage_options' ) )
			die;

		if( empty($_REQUEST['q']) )
			die;

		$search = new Codex_Generator_Function_Query( array(
			's'       => self::sanitize_function_name( $_REQUEST['q'] ),
			'orderby' => 'match',
			'number'  => 15,
		) );
		$functions = $search->get_names();

		echo implode( "\n", $functions );
		die;
	}

	static function wiki() {

		if ( ! current_user_can( 'manage_options' ) )
			die;

		if( empty($_REQUEST['function']) )
			die;

		$function = self::sanitize_function_name( $_REQUEST['function'] );
		$data     = Codex_Generator_Phpdoc_Parser::parse( $function );
		echo '<br /><textarea style="width:100%;height:90%;" class="code">' . esc_textarea( self::get_wiki( $data ) ) . '</textarea>';
		$link = self::get_codex_link( $function );
		echo '<p>' . __( 'This probably goes here: ', 'codex_gen' ) . "{$link}</p>";

		die;
	}

	/**
	 * Creates full wiki markup.
	 *
	 * @param array $data array of parsed PHPDoc data
	 *
	 * @return string markup for a page
	 */
	static function get_wiki( $data ) {

		// TODO process more tags
		// TODO markup variables in text
		// TODO link to functions in text
		// TODO document vars in PHPDoc

		$output = $short_desc = $long_desc = $name = $path = '';
		$tags = $parameters = $wiki_params = array();
		extract( $data );
		$function    = $name;
		$output     .= self::compile_wiki_section( '== Description ==', $short_desc, $long_desc );
		$text_params = ! empty( $parameters ) ? '$' . implode( ', $', array_keys( $parameters ) ) : '';
		$output     .= self::compile_wiki_section( '== Usage ==', "%%%<?php {$function}( {$text_params} ); ?>%%%" );

		foreach ( $parameters as $param ) {
			$type        = isset( $param['type'] ) ? self::type_to_string( $param['type'], 'wiki' ) : '';
			$description = isset( $param['description'] ) ? $param['description'] : '';
			$optional    = $param['optional'];

			if ( $param['has_default'] )
				$optional .= '|' . self::value_to_string( $param['default'] );

			$wiki_params[] = "{{Parameter|\${$param['name']}|{$type}|{$description}|{$optional}}}";
		}

		$output .= self::compile_wiki_section( '== Parameters ==', $wiki_params );

		if ( ! empty( $tags['return'] ) ) {
			list( $type, $description ) = self::explode( ' ', $tags['return'], 2, '' );
			$type    = self::type_to_string( $type, 'wiki' );
			$output .= self::compile_wiki_section( '== Return Values ==', "{{Return||{$type}|{$description}}}" );
		}

		$since = !empty($tags['since']) ? $tags['since'] : false;

		if ( ! empty( $since ) ) {
			if ( strlen( $since ) > 3 && '.0' === substr( $since, - 2 ) )
				$since = substr( $since, 0, 3 );

			$output .= self::compile_wiki_section( '== Change Log ==', "Since: [[Version {$since}|{$since}]]" );
		}

		$output .= self::compile_wiki_section( '== Source File ==', "<tt>{$function}()</tt> is located in {{Trac|{$path}}}" );
		$output .= "[[Category:Functions]]\n\n[[Category:New_page_created]]";

		return $output;
	}

	/**
	 * Filters out empty arguments (out of any number passed) and joins with linebreaks if more than one left.
	 *
	 * @param string $title section title
	 * @param array|string $content array of content items or multiple string item parameters
	 *
	 * @return string section string or empty string
	 */
	static function compile_wiki_section( $title, $content ) {

		$items = is_array( $content ) ? $content : array_slice( func_get_args(), 1 );
		$items = array_filter( $items );

		if ( empty( $items ) )
			return '';

		array_unshift( $items, $title );

		return implode( "\n\n", $items ) . "\n\n";
	}

	/**
	 * Sanitizes function name, replaces spaces with undescores.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	static function sanitize_function_name( $name ) {

		$name = wp_kses( $name, array() );
		$name = trim( $name, ' ()' );
		$name = str_replace( ' ', '_', $name );

		return $name;
	}

	/**
	 * Checks if on plugin's admin page.
	 *
	 * @return bool
	 */
	static function is_plugin_page() {

		global $hook_suffix;

		return $hook_suffix == self::$hook_suffix;
	}

	/**
	 * @param string $contextual_help
	 * 
	 * @return string
	 */
	static function contextual_help( $contextual_help ) {

		return '<ul>' .
				'<li><a href="http://wordpress.org/extend/plugins/codex-generator/faq/">' . __( 'Frequently Asked Questions', 'codex_gen' ) . '</a></li>' .
				'<li><a href="http://wordpress.org/tags/codex-generator?forum_id=10">' . __( 'Forum', 'codex_gen' ) . '</a></li>' .
				'</ul>';
	}

	/**
	 * Shorthand for adding methods to hooks of same name.
	 *
	 * @param string $method
	 * @param int $priority
	 * @param int $accepted_args
	 *
	 */
	static function add_method( $method, $priority = 10, $accepted_args = 1 ) {

		if ( method_exists( __CLASS__, $method ) ) ;
		add_action( $method, array( __CLASS__, $method ), $priority, $accepted_args );
	}
}