<?php

/*   ________________________________________
    |                 GarudaCBT              |
    |    https://github.com/garudacbt/cbt    |
    |________________________________________|
*/
class Install_model extends CI_Model
{
    function install_success()
    {
        return $this->check_installer();
    }

    function check_installer()
    {
        include APPPATH . "config/database.php";
        $database = $db["default"]["database"];
        $this->load->dbutil();
        if ($database == '') {
            return "1";
        }
        if (!$this->dbutil->database_exists($database)) {
            return "5";
        }
        $CI =& get_instance();
        $CI->load->database();
        if ($CI->db->table_exists("users")) {
            if ($CI->db->get("users")->row()) {
                if ($CI->db->get("setting")->row()) {
                    return "0";
                }
                return "4";
            }
            return "3";
        }
        return "2";
    }
}
