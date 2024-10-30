<?php

class Codex_Generator_Function_Query extends Codex_Generator_Utility {

	static $user_functions;

	public $args;
	public $count;

	function __construct( $args = array() ) {

		if ( ! isset( self::$user_functions ) ) {
			$functions            = get_defined_functions();
			self::$user_functions = $functions['user'];
			sort( self::$user_functions );
		}

		if( !empty($args['version_compare']) )
			$args['version_compare'] = self::sanitize_compare( $args['version_compare'] );

		$this->args = wp_parse_args( $args, array(
			's'               => false,
			'match'           => 'fuzzy',
			'version'         => false,
			'version_compare' => '=',
			'path'            => false,
			'orderby'         => 'name',
			'order'           => 'asc',
			'number'          => - 1,
			'offset'          => 0,
			'return'          => 'name',
//		'skip_non_wp'     => true,
		) );
	}

	function get_results() {

		$results = self::$user_functions;
		$args    = $this->args;

//		if( $args['skip_non_wp'] )
//			$results = array_filter($results, array(&$this,'filter_non_wp'));

//		if( $this->needs_filter() )
		$results = array_filter( $results, array( &$this, 'array_filter' ) );
//R_Debug::performance(true);
//		if( $this->needs_sort() )
		usort( $results, array( &$this, 'usort' ) );

		$this->count = count( $results );

		if ('desc' == $args['order'])
			$results = array_reverse( $results );

		if ($args['number'] > 0)
			$results = array_slice( $results, $args['offset'], $args['number'] );

		if ('array' == $args['return'])
			$results = array_map( array( &$this, 'get_array' ), $results );

		return $results;
	}

	function get_names() {

		$functions = self::$user_functions;

		foreach( $functions as $key => $name )
			if ( false === strpos( $name, $this->args['s'] ) )
				unset($functions[$key]);

		return array_slice( $functions, 0, $this->args['number'] );
	}

	function needs_filter() {

		return !empty($this->args['s'])
			   || !empty($this->args['version'])
			   || !empty($this->args['path'])
			   || 'version' == $this->args['orderby'];
	}

	function needs_sort() {

		return !empty($this->args['orderby']) && 'name' != $this->args['orderby'];
	}

	function get_array( $function ) {

		return Codex_Generator_Phpdoc_Parser::parse( $function );
	}

	function array_filter( $function ) {

		$array = $this->get_array( $function );

		if( !empty( $this->args['s'] ) )
			if ( 'fuzzy' == $this->args['match'] ) {
				if ( false === strpos( $function, $this->args['s'] ) )
					return false;
			}
			else {
				if ( 0 !== strpos( $function, $this->args['s'] ) ) ;
					return false;
			}

		if( !empty($this->args['version']) )
			if( empty($array['tags']['since']) )
				return false;
			else
				return version_compare( $array['tags']['since'], $this->args['version'], $this->args['version_compare'] );

		if( !empty($this->args['path']) )
			if ( 0 !== strpos( $array['path'], $this->args['path'] ) )
				return false;

		if( 'version' == $this->args['orderby'] && empty($array['tags']['since']) )
			return false;

		return true;
	}

	function filter_non_wp( $function ) {

		$array = $this->get_array( $function );

		return false === strpos( $array['path'], 'wp-content' );
	}

	function usort( $first, $second ) {

		switch ( $this->args['orderby'] ) {
			case 'name':

				return strcmp( $first, $second );

			break;

			case 'match':

				$pos_first  = strpos( $first, $this->args['s'] );
				$pos_second = strpos( $second, $this->args['s'] );

				if ($pos_first != $pos_second)
					return $pos_first > $pos_second ? 1 : - 1;

				return strcmp( $first, $second );

			break;

			case 'version':

				$first  = $this->get_array( $first );
				$second = $this->get_array( $second );

				return version_compare( $first['tags']['since'], $second['tags']['since'] );

			break;

			case 'file':

				$first  = $this->get_array( $first );
				$second = $this->get_array( $second );

				return strcmp( $first['path'], $second['path'] );

			break;
		}

		return 0;
	}
}