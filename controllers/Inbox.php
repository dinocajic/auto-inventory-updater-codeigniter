<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inbox extends CI_Controller {

	/**
	 * Index Page for this controller.
	 */
	public function index() {
		$this->load->model('inbox_model');
		$data['inbox']     =  $this->inbox_model->getInbox();
		$data['inventory'] = $this->inbox_model->getAttachment();
		$data['from']      = 'Company <sales@company.com>';


		$this->load->model("inventory_model");
		$this->inventory_model->updateInventory($data);

		if ($this->inventory_model->updateInventory($data) < 0) {
		    $to      = "dinocajic@gmail.com";
		    $from    = "sales@company.com";
		    $subject = "Inventory Stopped Working";
		    $body    = "The inventory update on Company 1 and Company 2 is not working";

		    mail($to, $subject, $body, $from);
		}

		$this->inbox_model->deleteMessage();
		$this->inbox_model->close();

		echo "";
	}
}
