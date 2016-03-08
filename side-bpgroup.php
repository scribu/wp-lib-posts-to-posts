<?php

class P2P_Side_Bpgroup extends P2P_Side {

    protected $item_type = 'P2P_Item_Bpgroup';

    function __construct( $query_vars ) {
        $this->query_vars = $query_vars;
    }

    function get_object_type() {
        return 'bpgroup';
    }

    function get_desc() {
        return __( 'Buddypress Group', P2P_TEXTDOMAIN );
    }

    function get_title() {
        return $this->get_desc();
    }

    function get_labels() {
        return (object) array(
            'singular_name' => __( 'Buddypress Group', P2P_TEXTDOMAIN ),
            'search_items' => __( 'Search Buddypress Groups', P2P_TEXTDOMAIN ),
            'not_found' => __( 'No Buddypress Groups found.', P2P_TEXTDOMAIN ),
        );
    }

    function can_edit_connections() {
        return true;
    }

    function can_create_item() {
        return false;
    }

    function translate_qv( $qv ) {
        if ( isset( $qv['p2p:include'] ) )
            $qv['include'] = _p2p_pluck( $qv, 'p2p:include' );

        if ( isset( $qv['p2p:exclude'] ) )
            $qv['exclude'] = _p2p_pluck( $qv, 'p2p:exclude' );

        if ( isset( $qv['p2p:search'] ) && $qv['p2p:search'] )
            $qv['search'] = '*' . _p2p_pluck( $qv, 'p2p:search' ) . '*';

        if ( isset( $qv['p2p:page'] ) && $qv['p2p:page'] > 0 ) {
            if ( isset( $qv['p2p:per_page'] ) && $qv['p2p:per_page'] > 0 ) {
                $qv['number'] = $qv['p2p:per_page'];
                $qv['offset'] = $qv['p2p:per_page'] * ( $qv['p2p:page'] - 1 );
            }
        }

        return $qv;
    }

    function do_query( $args ) {
		return new P2P_BP_Group_Query($args);
    }

    function capture_query( $args ) {
 
        $args['count_total'] = false;

        $uq = new P2P_BP_Group_Query;
        $uq->_p2p_capture = true; // needed by P2P_URL_Query

		// see http://core.trac.wordpress.org/ticket/21119
		$uq->query_vars = wp_parse_args( $args, array(
			'include' => array(),
			'exclude' => array(),
			'search' => '',
		) );

		$uq->prepare_query();

		return "SELECT $uq->query_fields $uq->query_from $uq->query_where $uq->query_orderby $uq->query_limit";
        //return 'SELECT * FROM '.$wpdb->prefix.'bp_groups bpg JOIN bp_groups_members bpgm ON bpg.id = bpgm.group_id WHERE bpgm.user_id IN ( '. $post->author .')';
    }

    function get_list( $query ) {
        $list = new P2P_List( $query->get_results(), $this->item_type );

        $qv = $query->query_vars;

        if ( isset( $qv['p2p:page'] ) ) {
            $list->current_page = $qv['p2p:page'];
            $list->total_pages = ceil( $query->get_total() / $qv['p2p:per_page'] );
        }

        return $list;
}

	function is_indeterminate( $side ) {
		return true;
	}

	function get_base_qv( $q ) {
		return array_merge( $this->query_vars, $q );
	}

	protected function recognize( $arg ) {
		
		if ( is_object( $arg ))
			return false;

		$group = groups_get_group( array( 'group_id' => $arg) );

		if ( !is_object( $group ) )
			return false;

		return $group;
	}
}
