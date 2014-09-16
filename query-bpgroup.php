<?php

class P2P_Query_Bpgroup {

    static function init() {
        add_action( 'pre_bpgroup_query', array( __CLASS__, 'pre_bpgroup_query' ), 20 );
    }

    static function pre_bpgroup_query( $query ) {
        global $wpdb,$bp;

        $r = P2P_Query::create_from_qv( $query->query_vars, 'bpgroup' );

        if ( is_wp_error( $r ) ) {
            $query->_p2p_error = $r;

            $query->query_where = " AND 1=0";
            return;
        }

        if ( null === $r )
            return;

        list( $p2p_q, $query->query_vars ) = $r;

        $map = array(
            'fields' => 'query_fields',
            'join' => 'query_from',
            'where' => 'query_where',
            'orderby' => 'query_orderby',
        );

        $clauses = array();

        foreach ( $map as $clause => $key )
            $clauses[$clause] = $query->$key;

		$table = $bp->groups->table_name;
        $clauses = $p2p_q->alter_clauses( $clauses, "$table.id" );

        if ( 0 !== strpos( $clauses['orderby'], 'ORDER BY ' ) )
            $clauses['orderby'] = 'ORDER BY ' . $clauses['orderby'];

        foreach ( $map as $clause => $key )
            $query->$key = $clauses[ $clause ];
    }

	/**
	 * Pre-populates the p2p meta cache to decrease the number of queries.
	 */
	static function cache_p2p_meta( $the_posts, $wp_query ) {
		if ( isset( $wp_query->_p2p_query ) && !empty( $the_posts ) )
			update_meta_cache( 'p2p', wp_list_pluck( $the_posts, 'p2p_id' ) );

		return $the_posts;
	}
	
}

?>
