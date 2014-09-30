<?php

class P2P_BP_Group_Query {

	/**
	 * Query vars, after parsing
	 *
	 * @since 3.5.0
	 * @access public
	 * @var array
	 */
	var $query_vars = array();

	/**
	 * List of found user ids
	 *
	 * @since 3.1.0
	 * @access private
	 * @var array
	 */
	var $results;

	/**
	 * Total number of found users for the current query
	 *
	 * @since 3.1.0
	 * @access private
	 * @var int
	 */
	var $total_bp_groups = 0;

	// SQL clauses
	var $query_fields;
	var $query_from;
	var $query_where;
	var $query_orderby;
	var $query_limit;

	/**
	 * PHP5 constructor.
	 *
	 * @since 3.1.0
	 *
	 * @param string|array $args Optional. The query variables.
	 * @return P2P_BP_Group_Query
	 */
	function __construct( $query = null ) {
		if ( ! empty( $query ) ) {
			$this->prepare_query( $query );
			$this->query();
		}
	}

	/**
	 * Prepare the query variables.
	 *
	 * @since 3.1.0
	 *
	 * @param string|array $args Optional. The query variables.
	 */
	function prepare_query( $query = array() ) {
		global $wpdb,$bp;

		$table = $bp->groups->table_name;

		if ( empty( $this->query_vars ) || ! empty( $query ) ) {
			$this->query_limit = null;
			$this->query_vars = wp_parse_args( $query, array(
				'include' => array(),
				'exclude' => array(),
				'search' => '',
			) );
		}

		$qv =& $this->query_vars;
		
		$this->query_fields = "$table.id, $table.name, $table.status, $table.slug";
		$this->query_from = "FROM $table "; 
		$this->query_where = "WHERE 1=1";
		$this->query_orderby = "ORDER BY name ASC";

		// limit
		if ( isset( $qv['number'] ) && $qv['number'] ) {
			if ( $qv['offset'] )
				$this->query_limit = $wpdb->prepare("LIMIT %d, %d", $qv['offset'], $qv['number']);
			else
				$this->query_limit = $wpdb->prepare("LIMIT %d", $qv['number']);
		}

		$search = '';
		if ( isset( $qv['search'] ) )
			$search = trim( $qv['search'] );
		
		if ( $search ) {
			$leading_wild = ( ltrim($search, '*') != $search );
			$trailing_wild = ( rtrim($search, '*') != $search );
			if ( $leading_wild && $trailing_wild )
				$wild = 'both';
			elseif ( $leading_wild )
				$wild = 'leading';
			elseif ( $trailing_wild )
				$wild = 'trailing';
			else
				$wild = false;
			if ( $wild )
				$search = trim($search, '*');

			$search_columns = array('name');

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}
		
		$query = "SELECT * FROM $wpdb->p2p WHERE p2p_type = '".$qv['connected_type']."'";
        $connected = $wpdb->get_col($query, 2);

		if ( empty($qv['search']) && $connected) {
            $this->query_fields .= ", $wpdb->p2p.*";
            $this->query_from .= " INNER JOIN $wpdb->p2p ON $table.id = $wpdb->p2p.p2p_to AND $wpdb->p2p.p2p_type = '".$qv['connected_type']."'";

            $ids = implode( ',', $connected );
            $this->query_where .= " AND $table.id IN ($ids)";
        } elseif ($connected) {
        	$ids = implode( ',', $connected );
            $this->query_where .= " AND $table.id NOT IN ($ids)";
        } elseif ( empty($qv['search'])){
            $this->query_where .= " AND 1=0 ";
        }

		if(!empty($qv['parent'])) {
            $this->query_where .= " AND $table.parent_id = ".$qv['parent'];
        }
		
	}

	/**
	 * Execute the query, with the current variables.
	 *
	 * @since 3.1.0
	 *
	 * @global wpdb $wpdb WordPress database object for queries.
	 */
	function query() {
		global $wpdb;

		$qv =& $this->query_vars;

		$query = "SELECT $this->query_fields $this->query_from $this->query_where $this->query_orderby $this->query_limit";
		
		$this->results = $wpdb->get_results( $query );

		if ( isset( $qv['count_total'] ) && $qv['count_total'] )
			$this->total_bp_groups = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

		if ( !$this->results )
			return;
	}

	function get( $query_var ) {
		if ( isset( $this->query_vars[$query_var] ) )
			return $this->query_vars[$query_var];

		return null;
	}

	function set( $query_var, $value ) {
		$this->query_vars[$query_var] = $value;
	}

	function get_search_sql( $string, $cols, $wild = false ) {
		$string = esc_sql( $string );

		$searches = array();
		$leading_wild = ( 'leading' == $wild || 'both' == $wild ) ? '%' : '';
		$trailing_wild = ( 'trailing' == $wild || 'both' == $wild ) ? '%' : '';
		foreach ( $cols as $col ) {
			if ( 'id' == $col )
				$searches[] = "$col = '$string'";
			else
				$searches[] = "$col LIKE '$leading_wild" . like_escape($string) . "$trailing_wild'";
		}

		return ' AND (' . implode(' OR ', $searches) . ')';
	}

	function get_results() {
		return $this->results;
	}

	function get_total() {
		return $this->total_bp_groups;
	}
	
	
}
?>