<?php

class P2P_Item_Bpgroup extends P2P_Item {

	function get_id() {
		return $this->item->id;
	}
	
    function get_title() {
        return $this->item->name;
    }

    function get_permalink() {
        return bp_get_group_permalink($this->item);
    }

    function get_editlink() {
        return bp_get_group_permalink($this->item);
    }
}
?>