<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * @author: Dino Cajic
 */

class Inbox_model extends CI_Model {

    /**
     * @var $conn          - IMAP server connection
     * @var array $inbox   - stores the message
     * @var int $msg_cnt   - stores the message count
     * @var string $server - your mail server
     * @var string $user   - your email address
     * @var string $pass   - your email password
     * @var string $port   - email port: adjust according to server settings
     */
    public  $conn;
    private $inbox;
    private $msg_cnt;
    private $server = 'mail.company.com';
    private $user   = 'inventory@company.com';
    private $pass   = 'password';
    private $port   = 143;

    /**
     * Inbox_model constructor.
     * Connect to the server and get the inbox emails
     */
    function __construct() {
        parent::__construct();

        $this->connect();
        $this->inbox();
    }

    /**
     * Close the server connection
     */
    function close() {
        $this->inbox = array();
        $this->msg_cnt = 0;

        imap_close($this->conn);
    }

    /**
     * Open the server connection
     */
    function connect() {
        $this->conn = imap_open('{'.$this->server.'/notls}', $this->user, $this->pass);
    }

    /**
     * Move the message to a new folder
     *
     * @param int    $msg_index
     * @param string $folder
     */
    function move($msg_index, $folder = 'INBOX.Processed') {
        // move on server
        imap_mail_move($this->conn, $msg_index, $folder);
        imap_expunge($this->conn);

        // re-read the inbox
        $this->inbox();
    }

    /**
     * Get a specific message (1 = first email, 2 = second email, etc.)
     *
     * @param null $msg_index
     *
     * @return array
     */
    function get($msg_index=NULL) {
        if (count($this->inbox) <= 0) {
            return array();
        } elseif ( ! is_null($msg_index) && isset($this->inbox[$msg_index])) {
            return $this->inbox[$msg_index];
        }

        return $this->inbox[0];
    }

    /**
     * Read the inbox
     */
    function inbox() {
        $this->msg_cnt = imap_num_msg($this->conn);
        $i = $this->msg_cnt; // Get the last message

        $in = array();

        $in[] = array(
            'index'     => $i,
            'header'    => imap_headerinfo($this->conn, $i),
            'body'      => imap_body($this->conn, $i),
            'structure' => imap_fetchstructure($this->conn, $i)
        );

        $this->inbox = $in;
    }

    /**
     * Return the messages
     *
     * @return array
     */
    function getInbox() {
        return $this->inbox;
    }

    function getAttachment() {
        $connection = $this->conn;
        $message_number = $this->msg_cnt;

        $structure = imap_fetchstructure($this->conn, $message_number);

        $attachments = array();
        if(isset($structure->parts) && count($structure->parts)) {

            for($i = 0; $i < count($structure->parts); $i++) {

                $attachments[$i] = array(
                    'is_attachment' => false,
                    'filename' => '',
                    'name' => '',
                    'attachment' => ''
                );

                if($structure->parts[$i]->ifdparameters) {
                    foreach($structure->parts[$i]->dparameters as $object) {
                        if(strtolower($object->attribute) == 'filename') {
                            $attachments[$i]['is_attachment'] = true;
                            $attachments[$i]['filename'] = $object->value;
                        }
                    }
                }

                if($structure->parts[$i]->ifparameters) {
                    foreach($structure->parts[$i]->parameters as $object) {
                        if(strtolower($object->attribute) == 'name') {
                            $attachments[$i]['is_attachment'] = true;
                            $attachments[$i]['name'] = $object->value;
                        }
                    }
                }

                if($attachments[$i]['is_attachment']) {
                    $attachments[$i]['attachment'] = imap_fetchbody($connection, $message_number, $i+1);
                    if($structure->parts[$i]->encoding == 3) { // 3 = BASE64
                        $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                    }
                    elseif($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                        $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                    }
                }
            }
        }

        $inventory_file = explode("\n", $attachments[1]['attachment']);
        $inventory = array();

        for ($i = 0; $i < sizeof($inventory_file); $i++) {
            $temp = explode("=", $inventory_file[$i]);

            if (isset($temp[1])) {
                $inventory[$i]['location'] = str_replace("$", "", $temp[0]);
                $inventory[$i]['amount']       = str_replace('"', '', $temp[1]);
                $inventory[$i]['amount']       = str_replace(';', '', $inventory[$i]['amount']);
            }
        }

        return $inventory;
    }

    public function deleteMessage() {
        imap_delete($this->conn, $this->msg_cnt);
    }

}