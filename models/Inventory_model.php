<?php
/**
 * @author: Dino Cajic
 */

class Inventory_model extends CI_Model {

    /**
     * Updates the inventory
     *
     * @param $data
     *
     * @return int
     */
    public function updateInventory($data) {
        if ($data["inbox"][0]["header"]->fromaddress != $data["from"]) {
            return -1;
        }

        $this->db->truncate('quantity');
        $insert = $this->db->insert_batch('quantity', $data["inventory"]);
        return $insert;
    }
}