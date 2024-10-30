<?php

if ( ! class_exists( 'WP_List_Table' ) )
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

class Codex_Generator_Functions_Table extends WP_List_Table {

	function __construct() {

		parent::__construct( array(
			'singular' => 'function',
			'plural'   => 'functions',
			'ajax'     => false,
		) );
    }

	function prepare_items() {

		$per_page = (int) get_user_option( Codex_Generator::$hook_suffix . '_per_page' );

		if ( empty( $per_page ) )
			$per_page = 20;

		$current_page = $this->get_pagenum();
		$offset = $current_page > 1 ? $per_page * ( $current_page - 1 ) : 0;

		$query = array(
			'number' => $per_page,
			'return' => 'array',
			'offset' => $offset,
		);

		foreach ( array( 's', 'orderby', 'order', 'version', 'version_compare', 'path' ) as $arg ) {
			if ( isset( $_GET[$arg] ) )
				$query[$arg] = esc_attr( $_GET[$arg] );
		}

		$search = new Codex_Generator_Function_Query( $query );
		$data = $search->get_results();
		$total_items = $search->count;
		$this->items = $data;
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}

	function display_tablenav( $which ) {
?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">
<?php
		$this->extra_tablenav( $which );
		$this->pagination( $which );
?>

		<br class="clear" />
	</div>
<?php
	}

	function extra_tablenav( $which ) {

		if ( 'top' == $which ) {
			$this->search_box( __( 'Name:', 'codex_gen' ), 'functions' );
			$this->version_dropdown();
			$this->path_dropdown();
			submit_button( __( 'Filter' ), 'secondary', false, false, array( 'id' => 'function-query-submit' ) );
			$this->reset_button();
		}
		else {
			$this->reset_button();
		}
	}

	function search_box( $text, $input_id ) {

		$input_id = $input_id . '-search-input';

		?>
		<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
		<label for="<?php echo $input_id ?>"><span class="description"><?php echo $text; ?></span></label>
		<input type="text" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
		<?php
	}

	function version_dropdown() {

		$valid_compare = Codex_Generator_Utility::get_compare();

		$version = isset( $_GET['version'] ) ? esc_html( $_GET['version'] ) : false;
		$compare = isset( $_GET['version_compare'] ) ? Codex_Generator_Utility::sanitize_compare( $_GET['version_compare'] ) : '=';

		?>
	<label for="version"><span class="description"><?php _e( 'Version:', 'codex_gen' ); ?></span></label>
	<select name='version_compare'>
		<?php

		foreach ( $valid_compare as $com ) {
			$selected = selected( $compare, $com, false );
			echo "<option value='{$com}' {$selected} >{$com}</option>";
		}

		?>
	</select>

	<select id="version" name='version'>
		<option value=""></option>
		<?php

		foreach ( Codex_Generator_Phpdoc_Parser::get_versions() as $ver ) {
			$selected = selected( $version, $ver, false );
			echo "<option value='{$ver}' {$selected} >{$ver}</option>";
		}
	?></select><?php
	}

	function path_dropdown() {

		$path = isset( $_GET['path'] ) ? esc_html( $_GET['path'] ) : false;

		?>
	<label for="path"><span class="description"><?php _e( 'Path:', 'codex_gen' ); ?></span></label>
	<select id="path" name='path'>
		<option value=""></option>
		<?php

		$tree = $this->explode_paths_to_tree( Codex_Generator_Phpdoc_Parser::get_paths() );
		$this->print_tree( $tree, $path );

		?>
	</select>
		<?php
	}

	function explode_paths_to_tree( $paths ) {

		$tree = array();

		foreach ( $paths as $path ) {
			$parts = explode( '/', $path );

			$target =& $tree;

			foreach( $parts as $part )
				$target =& $target[$part];
		}

		return $tree;
	}

	function print_tree( $tree, $current, $prepend = '', $val = '' ) {

		foreach ( $tree as $key => $leaf ) {
			$value    = trim( "{$val}/{$key}", '/' );
			$selected = selected( $value, $current, false );
			echo "<option value='{$value}' {$selected} >{$prepend}{$key}</option>";

			if ( is_array( $leaf ) )
				$this->print_tree( $leaf, $current, '- ' . $prepend, $val . '/' . $key );
		}
	}

	function reset_button() {

		$link   = CODEX_GENERATOR_LINK;
		$anchor = __( 'Reset', 'codex_gen' );
		echo "<a href='{$link}' class='reset'>{$anchor}</a>";
	}

	function get_columns(){

		$columns = array(
			'name'        => __( 'Name', 'codex_gen' ),
			'arguments'   => __( 'Arguments', 'codex_gen' ),
			'return'      => __( 'Return', 'codex_gen' ),
			'description' => __( 'Description', 'codex_gen' ),
			'version'     => __( 'Version', 'codex_gen' ),
			'file'        => __( 'File', 'codex_gen' ),
			'links'       => __( 'Links', 'codex_gen' ),
			'get'         => __( 'Get', 'codex_gen' ),
		);

        return $columns;
    }

	function get_sortable_columns() {
        $sortable_columns = array(
					'name'       => array( 'name', empty( $_GET['orderby'] ) ),
					'version'    => array( 'version', false ),
					'file'       => array( 'file', false ),
        );
        return $sortable_columns;
    }

	function column_name( $item ) {

		$name = esc_html( $item['name'] );

		return "<span class='code'>{$name}()</span>";
	}

	function column_description( $item ) {

		if ( ! empty( $item['short_desc'] ) )
			$description = $item['short_desc'];
		elseif ( ! empty( $item['long_desc'] ) )
			list( $description ) = explode( "\n", $item['long_desc'], 1 );
		else
			$description = '';

		return esc_html( $description );
	}

	function column_arguments( $item ) {

		$output = '';

		foreach ( $item['parameters'] as $param ) {
			$type    = isset( $param['type'] ) ? "<em>({$param['type']})</em>" : '';
			$name    = "<strong>\${$param['name']}</strong>";
			$default = $param['has_default'] ? ' = ' . Codex_Generator_Utility::value_to_string( $param['default'] ) : '';

			$output .= "{$type} {$name} {$default}<br />";
		}

		$output = "<span class='code'>{$output}</span>";

		return $output;
	}

	function column_return( $item ) {

		if ( ! empty( $item['tags']['return'] ) ) {
			list( $type, $description ) = Codex_Generator_Utility::explode( ' ', $item['tags']['return'], 2, '' );
			$type        = esc_html( Codex_Generator_Utility::type_to_string( $type ) );
			$description = esc_html( $description );
			return "<span class='code'><em>({$type})</em></span> {$description}";
		}

		return '';
	}

	function column_version( $item ) {

		$version = '';

		if ( ! empty( $item['tags']['since'] ) ) {
			$version = Codex_Generator_Utility::sanitize_version( $item['tags']['since'] );
			$version = esc_html( Codex_Generator_Utility::trim_version( $version ) );
			$link    = esc_url( add_query_arg( 'version', $version, CODEX_GENERATOR_LINK ) );
			$title   = esc_attr( sprintf( __( 'Filter by %s version', 'codex_gen' ), $version ) );
			$version = "<a href='{$link}' title='{$title}'>{$version}</a>";
		}

		return $version;
	}

	function column_file( $item ) {

		$file     = Codex_Generator_Utility::sanitize_path( $item['path'] );
		$segments = explode( '/', $file );
		$links    = array();
		$pos      = 0;

		foreach ( $segments as $segment ) {
			$pos ++;
			$anchor  = $segment;
			$path    = implode( '/', array_slice( $segments, 0, $pos ) );
			$link    = add_query_arg( 'path', $path, CODEX_GENERATOR_LINK );
			$links[] = "<a href='{$link}'>{$anchor}</a>";
		}

		return implode( ' / ', $links );
	}

	function column_links( $item ) {

		global $wp_version;

		$name    = esc_html( $item['name'] );
		$output  = Codex_Generator_Utility::get_codex_link( $name, __( 'Codex', 'codex_gen' ) );
		$output .= ' | ';
		$file    = esc_html( Codex_Generator_Utility::sanitize_path( $item['path'] ) );

		// TODO offset line by PHPDoc
		$link    = esc_url( 'http://core.trac.wordpress.org/browser/tags/' . $wp_version . '/' . $file . '#L' . $item['line'] );
		$title   = esc_attr( sprintf( __( 'Browse %s on Trac', 'codex_gen' ), $file ) );
		$anchor  = __( 'Trac', 'codex_gen' );
		$output .= "<a href='{$link}' title='{$title}'>{$anchor}</a>";

		return $output;
	}

	function column_get( $item ) {

		$name = $item['name'];
		$href = admin_url( 'admin-ajax.php' );

		$query = array(
			'action'   => 'codex_gen_wiki',
			'function' => esc_attr( $name ),
			'width'    => 800,
			'height'   => 700,
		);

		$href   = esc_url( add_query_arg( $query, $href ) );
		$anchor = esc_html( __( 'Wiki', 'codex_gen' ) );
		$title  = esc_attr( sprintf( __( 'Wiki page markup for %s()', '' ), $name ) );

		return "<a href='{$href}' class='thickbox' title='{$title}'>{$anchor}</a>";
	}
}
