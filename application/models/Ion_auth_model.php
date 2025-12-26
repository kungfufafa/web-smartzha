<?php

/*   ________________________________________
    |                 GarudaCBT              |
    |    https://github.com/garudacbt/cbt    |
    |________________________________________|
*/
defined("BASEPATH") or exit("No direct script access allowed");

class Ion_auth_model extends CI_Model
{
    const MAX_COOKIE_LIFETIME = 63072000;
    const MAX_PASSWORD_SIZE_BYTES = 4096;
    public $tables = array();
    public $activation_code;
    public $new_password;
    public $identity;
    public $_ion_where = array();
    public $_ion_select = array();
    public $_ion_like = array();
    public $_ion_limit = NULL;
    public $_ion_offset = NULL;
    public $_ion_order_by = NULL;
    public $_ion_order = NULL;
    protected $_ion_hooks;
    protected $response = NULL;
    protected $messages;
    protected $errors;
    protected $error_start_delimiter;
    protected $error_end_delimiter;
    public $_cache_user_in_group = array();
    protected $_cache_groups = array();
    protected $db;

    public function __construct()
    {
        $this->config->load("ion_auth", TRUE);
        $this->load->helper("cookie");
        $this->load->helper("date");
        $this->lang->load("ion_auth");
        $group_name = $this->config->item("database_group_name", "ion_auth");
        if (empty($group_name)) {
            $CI =& get_instance();
            $this->db = $CI->db;
        } else {
            $this->db = $this->load->database($group_name, TRUE, TRUE);
        }
        $this->tables = $this->config->item("tables", "ion_auth");
        $this->identity_column = $this->config->item("identity", "ion_auth");
        $this->join = $this->config->item("join", "ion_auth");
        $this->hash_method = $this->config->item("hash_method", "ion_auth");
        $this->messages = [];
        $this->errors = [];
        $delimiters_source = $this->config->item("delimiters_source", "ion_auth");
        if ($delimiters_source === "form_validation") {
            $this->load->library("form_validation");
            $form_validation_class = new ReflectionClass("CI_Form_validation");
            $error_prefix = $form_validation_class->getProperty("_error_prefix");
            $error_prefix->setAccessible(TRUE);
            $this->error_start_delimiter = $error_prefix->getValue($this->form_validation);
            $this->message_start_delimiter = $this->error_start_delimiter;
            $error_suffix = $form_validation_class->getProperty("_error_suffix");
            $error_suffix->setAccessible(TRUE);
            $this->error_end_delimiter = $error_suffix->getValue($this->form_validation);
            $this->message_end_delimiter = $this->error_end_delimiter;
        } else {
            $this->message_start_delimiter = $this->config->item("message_start_delimiter", "ion_auth");
            $this->message_end_delimiter = $this->config->item("message_end_delimiter", "ion_auth");
            $this->error_start_delimiter = $this->config->item("error_start_delimiter", "ion_auth");
            $this->error_end_delimiter = $this->config->item("error_end_delimiter", "ion_auth");
        }
        $this->_ion_hooks = new stdClass();
        $this->trigger_events("model_constructor");
    }

    public function db()
    {
        return $this->db;
    }

    public function hash_password($password, $identity = NULL)
    {
        if (empty($password) || strpos($password, "\x00") !== FALSE || strlen($password) > self::MAX_PASSWORD_SIZE_BYTES) {
            return false;
        }
        $algo = $this->_get_hash_algo();
        $params = $this->_get_hash_parameters($identity);
        if ($algo !== FALSE && $params !== FALSE) {
            return password_hash($password, $algo, $params);
        }
        return false;
    }

    public function verify_password($password, $hash_password_db, $identity = NULL)
    {
        if (empty($password) || empty($hash_password_db) || strpos($password, "\x00") !== FALSE || strlen($password) > self::MAX_PASSWORD_SIZE_BYTES) {
            return false;
        }
        if (strpos($hash_password_db, "\$") === 0) {
            return password_verify($password, $hash_password_db);
        }
        return $this->_password_verify_sha1_legacy($identity, $password, $hash_password_db);
    }

    public function rehash_password_if_needed($hash, $identity, $password)
    {
        $algo = $this->_get_hash_algo();
        $params = $this->_get_hash_parameters($identity);
        if ($algo !== FALSE && $params !== FALSE) {
            if (password_needs_rehash($hash, $algo, $params)) {
                if ($this->_set_password_db($identity, $password)) {
                    $this->trigger_events(["rehash_password", "rehash_password_successful"]);
                } else {
                    $this->trigger_events(["rehash_password", "rehash_password_unsuccessful"]);
                }
            }
        }
    }

    public function get_user_by_activation_code($user_code)
    {
        $token = $this->_retrieve_selector_validator_couple($user_code);
        $user = $this->where("activation_selector", $token->selector)->users()->row();
        if ($user && $this->verify_password($token->validator, $user->activation_code)) {
            return $user;
        }
        return false;
    }

    public function activate($id, $code = FALSE)
    {
        $this->trigger_events("pre_activate");
        if ($code !== FALSE) {
            $user = $this->get_user_by_activation_code($code);
        }
        if ($code === FALSE || ($user && $user->id === $id)) {
            $data = ["activation_selector" => NULL, "activation_code" => NULL, "active" => 1];
            $this->trigger_events("extra_where");
            $this->db->update($this->tables["users"], $data, ["id" => $id]);
            if ($this->db->affected_rows() === 1) {
                $this->trigger_events(["post_activate", "post_activate_successful"]);
                $this->set_message("activate_successful");
                return true;
            }
        }
        $this->trigger_events(["post_activate", "post_activate_unsuccessful"]);
        $this->set_error("activate_unsuccessful");
        return false;
    }

    public function deactivate($id = NULL)
    {
        $this->trigger_events("deactivate");
        if (!isset($id)) {
            $this->set_error("deactivate_unsuccessful");
            return false;
        }
        if ($this->ion_auth->logged_in() && $this->user()->row()->id == $id) {
            $this->set_error("deactivate_current_user_unsuccessful");
            return false;
        }
        $token = $this->_generate_selector_validator_couple(20, 40);
        $this->activation_code = $token->user_code;
        $data = ["activation_selector" => $token->selector, "activation_code" => $token->validator_hashed, "active" => 0];
        $this->trigger_events("extra_where");
        $this->db->update($this->tables["users"], $data, ["id" => $id]);
        $return = $this->db->affected_rows() == 1;
        if ($return) {
            $this->set_message("deactivate_successful");
        } else {
            $this->set_error("deactivate_unsuccessful");
        }
        return $return;
    }

    public function clear_forgotten_password_code($identity)
    {
        if (!empty($identity)) {
            $data = ["forgotten_password_selector" => NULL, "forgotten_password_code" => NULL, "forgotten_password_time" => NULL];
            $this->db->update($this->tables["users"], $data, [$this->identity_column => $identity]);
            return true;
        }
        return false;
    }

    public function clear_remember_code($identity)
    {
        if (!empty($identity)) {
            $data = ["remember_selector" => NULL, "remember_code" => NULL];
            $this->db->update($this->tables["users"], $data, [$this->identity_column => $identity]);
            return true;
        }
        return false;
    }

    public function reset_password($identity, $new)
    {
        $this->trigger_events("pre_change_password");
        if ($this->identity_check($identity)) {
            $return = $this->_set_password_db($identity, $new);
            if ($return) {
                $this->trigger_events(["post_change_password", "post_change_password_successful"]);
                $this->set_message("password_change_successful");
            } else {
                $this->trigger_events(["post_change_password", "post_change_password_unsuccessful"]);
                $this->set_error("password_change_unsuccessful");
            }
            return $return;
        }
        $this->trigger_events(["post_change_password", "post_change_password_unsuccessful"]);
        return false;
    }

    public function change_password($identity, $old, $new)
    {
        $this->trigger_events("pre_change_password");
        $this->trigger_events("extra_where");
        $query = $this->db->select("id, password")->where($this->identity_column, $identity)->limit(1)->order_by("id", "desc")->get($this->tables["users"]);
        if ($query->num_rows() !== 1) {
            $this->trigger_events(["post_change_password", "post_change_password_unsuccessful"]);
            $this->set_error("password_change_unsuccessful");
            return false;
        }
        $user = $query->row();
        if (!$this->verify_password($old, $user->password, $identity)) {
            $this->set_error("password_change_unsuccessful");
            return false;
        }
        $result = $this->_set_password_db($identity, $new);
        if ($result) {
            $this->trigger_events(["post_change_password", "post_change_password_successful"]);
            $this->set_message("password_change_successful");
        } else {
            $this->trigger_events(["post_change_password", "post_change_password_unsuccessful"]);
            $this->set_error("password_change_unsuccessful");
        }
        return $result;
    }

    public function username_check($username = '')
    {
        $this->trigger_events("username_check");
        if (!empty($username)) {
            $this->trigger_events("extra_where");
            return $this->db->where("username", $username)->limit(1)->count_all_results($this->tables["users"]) > 0;
        }
        return false;
    }

    public function email_check($email = '')
    {
        $this->trigger_events("email_check");
        if (!empty($email)) {
            $this->trigger_events("extra_where");
            return $this->db->where("email", $email)->limit(1)->count_all_results($this->tables["users"]) > 0;
        }
        return false;
    }

    public function identity_check($identity = '')
    {
        $this->trigger_events("identity_check");
        if (!empty($identity)) {
            return $this->db->where($this->identity_column, $identity)->limit(1)->count_all_results($this->tables["users"]) > 0;
        }
        return false;
    }

    public function get_user_id_from_identity($identity = '')
    {
        if (!empty($identity)) {
            $query = $this->db->select("id")->where($this->identity_column, $identity)->limit(1)->get($this->tables["users"]);
            if ($query->num_rows() !== 1) {
                return false;
            }
            $user = $query->row();
            return $user->id;
        }
        return false;
    }

    public function forgotten_password($identity)
    {
        if (!empty($identity)) {
            $token = $this->_generate_selector_validator_couple(20, 80);
            $update = ["forgotten_password_selector" => $token->selector, "forgotten_password_code" => $token->validator_hashed, "forgotten_password_time" => time()];
            $this->trigger_events("extra_where");
            $this->db->update($this->tables["users"], $update, [$this->identity_column => $identity]);
            if ($this->db->affected_rows() === 1) {
                $this->trigger_events(["post_forgotten_password", "post_forgotten_password_successful"]);
                return $token->user_code;
            }
            $this->trigger_events(["post_forgotten_password", "post_forgotten_password_unsuccessful"]);
            return false;
        }
        $this->trigger_events(["post_forgotten_password", "post_forgotten_password_unsuccessful"]);
        return false;
    }

    public function get_user_by_forgotten_password_code($user_code)
    {
        $token = $this->_retrieve_selector_validator_couple($user_code);
        $user = $this->where("forgotten_password_selector", $token->selector)->users()->row();
        if ($user && $this->verify_password($token->validator, $user->forgotten_password_code)) {
            return $user;
        }
        return false;
    }

    public function register($identity, $password, $email, $additional_data = array(), $groups = array())
    {
        $this->trigger_events("pre_register");
        $manual_activation = $this->config->item("manual_activation", "ion_auth");
        if ($this->identity_check($identity)) {
            $this->set_error("account_creation_duplicate_identity");
            return false;
        }
        if (!$this->config->item("default_group", "ion_auth") && empty($groups)) {
            $this->set_error("account_creation_missing_default_group");
            return false;
        }
        $query = $this->db->get_where($this->tables["groups"], ["name" => $this->config->item("default_group", "ion_auth")], 1)->row();
        if (!isset($query->id) && empty($groups)) {
            $this->set_error("account_creation_invalid_default_group");
            return false;
        }
        $default_group = $query;
        $ip_address = $this->input->ip_address();
        $password = $this->hash_password($password);
        if ($password === FALSE) {
            $this->set_error("account_creation_unsuccessful");
            return false;
        }
        $data = [$this->identity_column => $identity, "username" => $identity, "password" => $password, "email" => $email, "ip_address" => $ip_address, "created_on" => time(), "active" => $manual_activation === FALSE ? 1 : 0];
        $user_data = array_merge($this->_filter_data($this->tables["users"], $additional_data), $data);
        $this->trigger_events("extra_set");
        $this->db->insert($this->tables["users"], $user_data);
        $id = $this->db->insert_id($this->tables["users"] . "_id_seq");
        if (isset($default_group->id) && empty($groups)) {
            $groups[] = $default_group->id;
        }
        if (!empty($groups)) {
            foreach ($groups as $group) {
                $this->add_to_group($group, $id);
            }
        }
        $this->trigger_events("post_register");
        return isset($id) ? $id : FALSE;
    }

    public function login($identity, $password, $remember = FALSE)
    {
        $this->trigger_events("pre_login");
        if (empty($identity) || empty($password)) {
            $this->set_error("login_unsuccessful");
            return false;
        }
        $this->trigger_events("extra_where");
        $query = $this->db->select($this->identity_column . ", email, id, password, active, last_login")->where($this->identity_column, $identity)->limit(1)->order_by("id", "desc")->get($this->tables["users"]);
        if ($this->is_max_login_attempts_exceeded($identity)) {
            $this->hash_password($password);
            $this->trigger_events("post_login_unsuccessful");
            $this->set_error("login_timeout");
            return false;
        }
        if ($query->num_rows() === 1) {
            $user = $query->row();
            if ($this->verify_password($password, $user->password, $identity)) {
                if ($user->active == 0) {
                    $this->trigger_events("post_login_unsuccessful");
                    $this->set_error("login_unsuccessful_not_active");
                    return false;
                }
                $this->set_session($user);
                $this->update_last_login($user->id);
                $this->clear_login_attempts($identity);
                $this->clear_forgotten_password_code($identity);
                if ($this->config->item("remember_users", "ion_auth")) {
                    if ($remember) {
                        $this->remember_user($identity);
                    } else {
                        $this->clear_remember_code($identity);
                    }
                }
                $this->rehash_password_if_needed($user->password, $identity, $password);
                $this->session->sess_regenerate(FALSE);
                $this->trigger_events(["post_login", "post_login_successful"]);
                $this->set_message("login_successful");
                return true;
            }
        }
        $this->hash_password($password);
        $this->increase_login_attempts($identity);
        $this->trigger_events("post_login_unsuccessful");
        $this->set_error("login_unsuccessful");
        return false;
    }

    public function recheck_session()
    {
        $recheck = NULL !== $this->config->item("recheck_timer", "ion_auth") ? $this->config->item("recheck_timer", "ion_auth") : 0;
        if ($recheck !== 0) {
            $last_login = $this->session->userdata("last_check");
            if ($last_login + $recheck < time()) {
                $query = $this->db->select("id")->where([$this->identity_column => $this->session->userdata("identity"), "active" => "1"])->limit(1)->order_by("id", "desc")->get($this->tables["users"]);
                if ($query->num_rows() === 1) {
                    $this->session->set_userdata("last_check", time());
                } else {
                    $this->trigger_events("logout");
                    $identity = $this->config->item("identity", "ion_auth");
                    $this->session->unset_userdata([$identity, "id", "user_id"]);
                    return false;
                }
            }
        }
        return (bool) $this->session->userdata("identity");
    }

    public function is_max_login_attempts_exceeded($identity, $ip_address = NULL)
    {
        if ($this->config->item("track_login_attempts", "ion_auth")) {
            $max_attempts = $this->config->item("maximum_login_attempts", "ion_auth");
            if ($max_attempts > 0) {
                $attempts = $this->get_attempts_num($identity, $ip_address);
                return $attempts >= $max_attempts;
            }
        }
        return false;
    }

    public function get_attempts_num($identity, $ip_address = NULL)
    {
        if (!$this->config->item("track_login_attempts", "ion_auth")) {
            return 0;
        }
        $this->db->select("1", FALSE);
        $this->db->where("login", $identity);
        if ($this->config->item("track_login_ip_address", "ion_auth")) {
            if (!isset($ip_address)) {
                $ip_address = $this->input->ip_address();
            }
            $this->db->where("ip_address", $ip_address);
        }
        $this->db->where("time >", time() - $this->config->item("lockout_time", "ion_auth"), FALSE);
        $qres = $this->db->get($this->tables["login_attempts"]);
        return $qres->num_rows();
    }

    public function get_last_attempt_time($identity, $ip_address = NULL)
    {
        if ($this->config->item("track_login_attempts", "ion_auth")) {
            $this->db->select("time");
            $this->db->where("login", $identity);
            if ($this->config->item("track_login_ip_address", "ion_auth")) {
                if (!isset($ip_address)) {
                    $ip_address = $this->input->ip_address();
                }
                $this->db->where("ip_address", $ip_address);
            }
            $this->db->order_by("id", "desc");
            $qres = $this->db->get($this->tables["login_attempts"], 1);
            if ($qres->num_rows() > 0) {
                return $qres->row()->time;
            }
        }
        return 0;
    }

    public function get_last_attempt_ip($identity)
    {
        if ($this->config->item("track_login_attempts", "ion_auth") && $this->config->item("track_login_ip_address", "ion_auth")) {
            $this->db->select("ip_address");
            $this->db->where("login", $identity);
            $this->db->order_by("id", "desc");
            $qres = $this->db->get($this->tables["login_attempts"], 1);
            if ($qres->num_rows() > 0) {
                return $qres->row()->ip_address;
            }
        }
        return "";
    }

    public function increase_login_attempts($identity)
    {
        if (!$this->config->item("track_login_attempts", "ion_auth")) {
            return false;
        }
        $data = ["ip_address" => '', "login" => $identity, "time" => time()];
        if ($this->config->item("track_login_ip_address", "ion_auth")) {
            $data["ip_address"] = $this->input->ip_address();
        }
        return $this->db->insert($this->tables["login_attempts"], $data);
    }

    public function clear_login_attempts($identity, $old_attempts_expire_period = 86400, $ip_address = NULL)
    {
        if (!$this->config->item("track_login_attempts", "ion_auth")) {
            return false;
        }
        $old_attempts_expire_period = max($old_attempts_expire_period, $this->config->item("lockout_time", "ion_auth"));
        $this->db->where("login", $identity);
        if ($this->config->item("track_login_ip_address", "ion_auth")) {
            if (!isset($ip_address)) {
                $ip_address = $this->input->ip_address();
            }
            $this->db->where("ip_address", $ip_address);
        }
        $this->db->or_where("time <", time() - $old_attempts_expire_period, FALSE);
        return $this->db->delete($this->tables["login_attempts"]);
    }

    public function limit($limit)
    {
        $this->trigger_events("limit");
        $this->_ion_limit = $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->trigger_events("offset");
        $this->_ion_offset = $offset;
        return $this;
    }

    public function where($where, $value = NULL)
    {
        $this->trigger_events("where");
        if (!is_array($where)) {
            $where = [$where => $value];
        }
        array_push($this->_ion_where, $where);
        return $this;
    }

    public function like($like, $value = NULL, $position = "both")
    {
        $this->trigger_events("like");
        array_push($this->_ion_like, ["like" => $like, "value" => $value, "position" => $position]);
        return $this;
    }

    public function select($select)
    {
        $this->trigger_events("select");
        $this->_ion_select[] = $select;
        return $this;
    }

    public function order_by($by, $order = "desc")
    {
        $this->trigger_events("order_by");
        $this->_ion_order_by = $by;
        $this->_ion_order = $order;
        return $this;
    }

    public function row()
    {
        $this->trigger_events("row");
        $row = $this->response->row();
        return $row;
    }

    public function row_array()
    {
        $this->trigger_events(["row", "row_array"]);
        $row = $this->response->row_array();
        return $row;
    }

    public function result()
    {
        $this->trigger_events("result");
        $result = $this->response->result();
        return $result;
    }

    public function result_array()
    {
        $this->trigger_events(["result", "result_array"]);
        $result = $this->response->result_array();
        return $result;
    }

    public function num_rows()
    {
        $this->trigger_events(["num_rows"]);
        $result = $this->response->num_rows();
        return $result;
    }

    public function users($groups = NULL)
    {
        $this->trigger_events("users");
        if (isset($this->_ion_select) && !empty($this->_ion_select)) {
            foreach ($this->_ion_select as $select) {
                $this->db->select($select);
            }
            $this->_ion_select = [];
        } else {
            $this->db->select([$this->tables["users"] . ".*", $this->tables["users"] . ".id as id", $this->tables["users"] . ".id as user_id"]);
        }
        if (isset($groups)) {
            if (!is_array($groups)) {
                $groups = [$groups];
            }
            if (isset($groups) && !empty($groups)) {
                $this->db->distinct();
                $this->db->join($this->tables["users_groups"], $this->tables["users_groups"] . "." . $this->join["users"] . "=" . $this->tables["users"] . ".id", "inner");
            }
            $group_ids = [];
            $group_names = [];
            foreach ($groups as $group) {
                if (is_numeric($group)) {
                    $group_ids[] = $group;
                } else {
                    $group_names[] = $group;
                }
            }
            $or_where_in = !empty($group_ids) && !empty($group_names) ? "or_where_in" : "where_in";
            if (!empty($group_names)) {
                $this->db->join($this->tables["groups"], $this->tables["users_groups"] . "." . $this->join["groups"] . " = " . $this->tables["groups"] . ".id", "inner");
                $this->db->where_in($this->tables["groups"] . ".name", $group_names);
            }
            if (!empty($group_ids)) {
                $this->db->{$or_where_in}($this->tables["users_groups"] . "." . $this->join["groups"], $group_ids);
            }
        }
        $this->trigger_events("extra_where");
        if (isset($this->_ion_where) && !empty($this->_ion_where)) {
            foreach ($this->_ion_where as $where) {
                $this->db->where($where);
            }
            $this->_ion_where = [];
        }
        if (isset($this->_ion_like) && !empty($this->_ion_like)) {
            foreach ($this->_ion_like as $like) {
                $this->db->or_like($like["like"], $like["value"], $like["position"]);
            }
            $this->_ion_like = [];
        }
        if (isset($this->_ion_limit) && isset($this->_ion_offset)) {
            $this->db->limit($this->_ion_limit, $this->_ion_offset);
            $this->_ion_limit = NULL;
            $this->_ion_offset = NULL;
        } elseif (isset($this->_ion_limit)) {
            $this->db->limit($this->_ion_limit);
            $this->_ion_limit = NULL;
        }
        if (isset($this->_ion_order_by) && isset($this->_ion_order)) {
            $this->db->order_by($this->_ion_order_by, $this->_ion_order);
            $this->_ion_order = NULL;
            $this->_ion_order_by = NULL;
        }
        $this->response = $this->db->get($this->tables["users"]);
        return $this;
    }

    public function user($id = NULL)
    {
        $this->trigger_events("user");
        $id = isset($id) ? $id : $this->session->userdata("user_id");
        $this->limit(1);
        $this->order_by($this->tables["users"] . ".id", "desc");
        $this->where($this->tables["users"] . ".id", $id);
        $this->users();
        return $this;
    }

    public function get_users_groups($id = FALSE)
    {
        $this->trigger_events("get_users_group");
        $id || ($id = $this->session->userdata("user_id"));
        return $this->db->select($this->tables["users_groups"] . "." . $this->join["groups"] . " as id, " . $this->tables["groups"] . ".name, " . $this->tables["groups"] . ".description")->where($this->tables["users_groups"] . "." . $this->join["users"], $id)->join($this->tables["groups"], $this->tables["users_groups"] . "." . $this->join["groups"] . "=" . $this->tables["groups"] . ".id")->get($this->tables["users_groups"]);
    }

    public function in_group($check_group, $id = FALSE, $check_all = FALSE)
    {
        $this->trigger_events("in_group");
        $id || ($id = $this->session->userdata("user_id"));
        if (!is_array($check_group)) {
            $check_group = [$check_group];
        }
        if (isset($this->_cache_user_in_group[$id])) {
            $groups_array = $this->_cache_user_in_group[$id];
        } else {
            $users_groups = $this->get_users_groups($id)->result();
            $groups_array = [];
            foreach ($users_groups as $group) {
                $groups_array[$group->id] = $group->name;
            }
            $this->_cache_user_in_group[$id] = $groups_array;
        }
        foreach ($check_group as $key => $value) {
            $groups = is_numeric($value) ? array_keys($groups_array) : $groups_array;
            if (in_array($value, $groups) xor $check_all) {
                return !$check_all;
            }
        }
        return $check_all;
    }

    public function add_to_group($group_ids, $user_id = FALSE)
    {
        $this->trigger_events("add_to_group");
        $user_id || ($user_id = $this->session->userdata("user_id"));
        if (!is_array($group_ids)) {
            $group_ids = [$group_ids];
        }
        $return = 0;
        foreach ($group_ids as $group_id) {
            if ($this->db->insert($this->tables["users_groups"], [$this->join["groups"] => (float) $group_id, $this->join["users"] => (float) $user_id])) {
                if (isset($this->_cache_groups[$group_id])) {
                    $group_name = $this->_cache_groups[$group_id];
                } else {
                    $group = $this->group($group_id)->result();
                    $group_name = $group[0]->name;
                    $this->_cache_groups[$group_id] = $group_name;
                }
                $this->_cache_user_in_group[$user_id][$group_id] = $group_name;
                $return++;
            }
        }
        return $return;
    }

    public function remove_from_group($group_ids = FALSE, $user_id = FALSE)
    {
        $this->trigger_events("remove_from_group");
        if (empty($user_id)) {
            return false;
        }
        if (!empty($group_ids)) {
            if (!is_array($group_ids)) {
                $group_ids = [$group_ids];
            }
            foreach ($group_ids as $group_id) {
                $this->db->delete($this->tables["users_groups"], [$this->join["groups"] => (float) $group_id, $this->join["users"] => (float) $user_id]);
                if (isset($this->_cache_user_in_group[$user_id]) && isset($this->_cache_user_in_group[$user_id][$group_id])) {
                    unset($this->_cache_user_in_group[$user_id][$group_id]);
                }
            }
            $return = TRUE;
        } else {
            $return = $this->db->delete($this->tables["users_groups"], [$this->join["users"] => (float) $user_id]);
            if ($return) {
                $this->_cache_user_in_group[$user_id] = [];
            }
        }
        return $return;
    }

    public function groups()
    {
        $this->trigger_events("groups");
        if (isset($this->_ion_where) && !empty($this->_ion_where)) {
            foreach ($this->_ion_where as $where) {
                $this->db->where($where);
            }
            $this->_ion_where = [];
        }
        if (isset($this->_ion_limit) && isset($this->_ion_offset)) {
            $this->db->limit($this->_ion_limit, $this->_ion_offset);
            $this->_ion_limit = NULL;
            $this->_ion_offset = NULL;
        } elseif (isset($this->_ion_limit)) {
            $this->db->limit($this->_ion_limit);
            $this->_ion_limit = NULL;
        }
        if (isset($this->_ion_order_by) && isset($this->_ion_order)) {
            $this->db->order_by($this->_ion_order_by, $this->_ion_order);
        }
        $this->response = $this->db->get($this->tables["groups"]);
        return $this;
    }

    public function group($id = NULL)
    {
        $this->trigger_events("group");
        if (isset($id)) {
            $this->where($this->tables["groups"] . ".id", $id);
        }
        $this->limit(1);
        $this->order_by("id", "desc");
        return $this->groups();
    }

    public function update($id, array $data)
    {
        $this->trigger_events("pre_update_user");
        $user = $this->user($id)->row();
        $this->db->trans_begin();
        if (array_key_exists($this->identity_column, $data) && $this->identity_check($data[$this->identity_column]) && $user->{$this->identity_column} !== $data[$this->identity_column]) {
            $this->db->trans_rollback();
            $this->set_error("account_creation_duplicate_identity");
            $this->trigger_events(["post_update_user", "post_update_user_unsuccessful"]);
            $this->set_error("update_unsuccessful");
            return false;
        }
        $data = $this->_filter_data($this->tables["users"], $data);
        if (array_key_exists($this->identity_column, $data) || array_key_exists("password", $data) || array_key_exists("email", $data)) {
            if (array_key_exists("password", $data)) {
                if (!empty($data["password"])) {
                    $data["password"] = $this->hash_password($data["password"], $user->{$this->identity_column});
                    if ($data["password"] === FALSE) {
                        $this->db->trans_rollback();
                        $this->trigger_events(["post_update_user", "post_update_user_unsuccessful"]);
                        $this->set_error("update_unsuccessful");
                        return false;
                    }
                } else {
                    unset($data["password"]);
                }
            }
        }
        $this->trigger_events("extra_where");
        $this->db->update($this->tables["users"], $data, ["id" => $user->id]);
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->trigger_events(["post_update_user", "post_update_user_unsuccessful"]);
            $this->set_error("update_unsuccessful");
            return false;
        }
        $this->db->trans_commit();
        $this->trigger_events(["post_update_user", "post_update_user_successful"]);
        $this->set_message("update_successful");
        return true;
    }

    public function delete_user($id)
    {
        $this->trigger_events("pre_delete_user");
        $this->db->trans_begin();
        $this->remove_from_group(NULL, $id);
        $this->db->delete($this->tables["users"], ["id" => $id]);
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->trigger_events(["post_delete_user", "post_delete_user_unsuccessful"]);
            $this->set_error("delete_unsuccessful");
            return false;
        }
        $this->db->trans_commit();
        $this->trigger_events(["post_delete_user", "post_delete_user_successful"]);
        $this->set_message("delete_successful");
        return true;
    }

    public function update_last_login($id)
    {
        $this->trigger_events("update_last_login");
        $this->load->helper("date");
        $this->trigger_events("extra_where");
        $this->db->update($this->tables["users"], ["last_login" => time()], ["id" => $id]);
        return $this->db->affected_rows() == 1;
    }

    public function set_lang($lang = "en")
    {
        $this->trigger_events("set_lang");
        if ($this->config->item("user_expire", "ion_auth") === 0) {
            $expire = self::MAX_COOKIE_LIFETIME;
        } else {
            $expire = $this->config->item("user_expire", "ion_auth");
        }
        set_cookie(["name" => "lang_code", "value" => $lang, "expire" => $expire]);
        return true;
    }

    public function set_session($user)
    {
        $this->trigger_events("pre_set_session");
        $session_data = ["identity" => $user->{$this->identity_column}, $this->identity_column => $user->{$this->identity_column}, "email" => $user->email, "user_id" => $user->id, "old_last_login" => $user->last_login, "last_check" => time()];
        $this->session->set_userdata($session_data);
        $this->trigger_events("post_set_session");
        return true;
    }

    public function remember_user($identity)
    {
        $this->trigger_events("pre_remember_user");
        if ($identity) {
            $token = $this->_generate_selector_validator_couple();
            if ($token->validator_hashed) {
                $this->db->update($this->tables["users"], ["remember_selector" => $token->selector, "remember_code" => $token->validator_hashed], [$this->identity_column => $identity]);
                if ($this->db->affected_rows() > -1) {
                    if ($this->config->item("user_expire", "ion_auth") === 0) {
                        $expire = self::MAX_COOKIE_LIFETIME;
                    } else {
                        $expire = $this->config->item("user_expire", "ion_auth");
                    }
                    set_cookie(["name" => $this->config->item("remember_cookie_name", "ion_auth"), "value" => $token->user_code, "expire" => $expire]);
                    $this->trigger_events(["post_remember_user", "remember_user_successful"]);
                    return true;
                }
            }
            $this->trigger_events(["post_remember_user", "remember_user_unsuccessful"]);
            return false;
        }
        return false;
    }

    public function login_remembered_user()
    {
        $this->trigger_events("pre_login_remembered_user");
        $remember_cookie = get_cookie($this->config->item("remember_cookie_name", "ion_auth"));
        $token = $this->_retrieve_selector_validator_couple($remember_cookie);
        if ($token === FALSE) {
            $this->trigger_events(["post_login_remembered_user", "post_login_remembered_user_unsuccessful"]);
            return false;
        }
        $this->trigger_events("extra_where");
        $query = $this->db->select($this->identity_column . ", id, email, remember_code, last_login")->where("remember_selector", $token->selector)->where("active", 1)->limit(1)->get($this->tables["users"]);
        if ($query->num_rows() === 1) {
            $user = $query->row();
            $identity = $user->{$this->identity_column};
            if ($this->verify_password($token->validator, $user->remember_code, $identity)) {
                $this->update_last_login($user->id);
                $this->set_session($user);
                $this->clear_forgotten_password_code($identity);
                if ($this->config->item("user_extend_on_login", "ion_auth")) {
                    $this->remember_user($identity);
                }
                $this->session->sess_regenerate(FALSE);
                $this->trigger_events(["post_login_remembered_user", "post_login_remembered_user_successful"]);
                return true;
            }
        }
        delete_cookie($this->config->item("remember_cookie_name", "ion_auth"));
        $this->trigger_events(["post_login_remembered_user", "post_login_remembered_user_unsuccessful"]);
        return false;
    }

    public function create_group($group_name = FALSE, $group_description = '', $additional_data = array())
    {
        if (!$group_name) {
            $this->set_error("group_name_required");
            return false;
        }
        $existing_group = $this->db->get_where($this->tables["groups"], ["name" => $group_name])->num_rows();
        if ($existing_group !== 0) {
            $this->set_error("group_already_exists");
            return false;
        }
        $data = ["name" => $group_name, "description" => $group_description];
        if (!empty($additional_data)) {
            $data = array_merge($this->_filter_data($this->tables["groups"], $additional_data), $data);
        }
        $this->trigger_events("extra_group_set");
        $this->db->insert($this->tables["groups"], $data);
        $group_id = $this->db->insert_id($this->tables["groups"] . "_id_seq");
        $this->set_message("group_creation_successful");
        return $group_id;
    }

    public function update_group($group_id = FALSE, $group_name = FALSE, $additional_data = array())
    {
        if (empty($group_id)) {
            return false;
        }
        $data = [];
        if (!empty($group_name)) {
            $existing_group = $this->db->get_where($this->tables["groups"], ["name" => $group_name])->row();
            if (isset($existing_group->id) && $existing_group->id != $group_id) {
                $this->set_error("group_already_exists");
                return false;
            }
            $data["name"] = $group_name;
        }
        $group = $this->db->get_where($this->tables["groups"], ["id" => $group_id])->row();
        if ($this->config->item("admin_group", "ion_auth") === $group->name && $group_name !== $group->name) {
            $this->set_error("group_name_admin_not_alter");
            return false;
        }
        if (!empty($additional_data)) {
            $data = array_merge($this->_filter_data($this->tables["groups"], $additional_data), $data);
        }
        $this->db->update($this->tables["groups"], $data, ["id" => $group_id]);
        $this->set_message("group_update_successful");
        return true;
    }

    public function delete_group($group_id = FALSE)
    {
        if (!$group_id || empty($group_id)) {
            return false;
        }
        $group = $this->group($group_id)->row();
        if ($group->name == $this->config->item("admin_group", "ion_auth")) {
            $this->trigger_events(["post_delete_group", "post_delete_group_notallowed"]);
            $this->set_error("group_delete_notallowed");
            return false;
        }
        $this->trigger_events("pre_delete_group");
        $this->db->trans_begin();
        $this->db->delete($this->tables["users_groups"], [$this->join["groups"] => $group_id]);
        $this->db->delete($this->tables["groups"], ["id" => $group_id]);
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->trigger_events(["post_delete_group", "post_delete_group_unsuccessful"]);
            $this->set_error("group_delete_unsuccessful");
            return false;
        }
        $this->db->trans_commit();
        $this->trigger_events(["post_delete_group", "post_delete_group_successful"]);
        $this->set_message("group_delete_successful");
        return true;
    }

    public function set_hook($event, $name, $class, $method, $arguments)
    {
        $this->_ion_hooks->{$event}[$name] = new stdClass();
        $this->_ion_hooks->{$event}[$name]->class = $class;
        $this->_ion_hooks->{$event}[$name]->method = $method;
        $this->_ion_hooks->{$event}[$name]->arguments = $arguments;
    }

    public function remove_hook($event, $name)
    {
        if (isset($this->_ion_hooks->{$event}[$name])) {
            unset($this->_ion_hooks->{$event}[$name]);
        }
    }

    public function remove_hooks($event)
    {
        if (isset($this->_ion_hooks->{$event})) {
            unset($this->_ion_hooks->{$event});
        }
    }

    protected function _call_hook($event, $name)
    {
        if (isset($this->_ion_hooks->{$event}[$name]) && method_exists($this->_ion_hooks->{$event}[$name]->class, $this->_ion_hooks->{$event}[$name]->method)) {
            $hook = $this->_ion_hooks->{$event}[$name];
            return call_user_func_array([$hook->class, $hook->method], $hook->arguments);
        }
        return false;
    }

    public function trigger_events($events)
    {
        if (is_array($events) && !empty($events)) {
            foreach ($events as $event) {
                $this->trigger_events($event);
            }
        } elseif (isset($this->_ion_hooks->{$events}) && !empty($this->_ion_hooks->{$events})) {
            foreach ($this->_ion_hooks->{$events} as $name => $hook) {
                $this->_call_hook($events, $name);
            }
        }
    }

    public function set_message_delimiters($start_delimiter, $end_delimiter)
    {
        $this->message_start_delimiter = $start_delimiter;
        $this->message_end_delimiter = $end_delimiter;
        return true;
    }

    public function set_error_delimiters($start_delimiter, $end_delimiter)
    {
        $this->error_start_delimiter = $start_delimiter;
        $this->error_end_delimiter = $end_delimiter;
        return true;
    }

    public function set_message($message)
    {
        $this->messages[] = $message;
        return $message;
    }

    public function messages()
    {
        $_output = '';
        foreach ($this->messages as $message) {
            $messageLang = $this->lang->line($message) ? $this->lang->line($message) : "##" . $message . "##";
            $_output .= $this->message_start_delimiter . $messageLang . $this->message_end_delimiter;
        }
        return $_output;
    }

    public function messages_array($langify = TRUE)
    {
        if ($langify) {
            $_output = [];
            foreach ($this->messages as $message) {
                $messageLang = $this->lang->line($message) ? $this->lang->line($message) : "##" . $message . "##";
                $_output[] = $this->message_start_delimiter . $messageLang . $this->message_end_delimiter;
            }
            return $_output;
        }
        return $this->messages;
    }

    public function clear_messages()
    {
        $this->messages = [];
        return true;
    }

    public function set_error($error)
    {
        $this->errors[] = $error;
        return $error;
    }

    public function errors()
    {
        $_output = '';
        foreach ($this->errors as $error) {
            $errorLang = $this->lang->line($error) ? $this->lang->line($error) : "##" . $error . "##";
            $_output .= $this->error_start_delimiter . $errorLang . $this->error_end_delimiter;
        }
        return $_output;
    }

    public function errors_array($langify = TRUE)
    {
        if ($langify) {
            $_output = [];
            foreach ($this->errors as $error) {
                $errorLang = $this->lang->line($error) ? $this->lang->line($error) : "##" . $error . "##";
                $_output[] = $this->error_start_delimiter . $errorLang . $this->error_end_delimiter;
            }
            return $_output;
        }
        return $this->errors;
    }

    public function clear_errors()
    {
        $this->errors = [];
        return true;
    }

    protected function _set_password_db($identity, $password)
    {
        $hash = $this->hash_password($password, $identity);
        if ($hash === FALSE) {
            return false;
        }
        $data = ["password" => $hash, "remember_code" => NULL, "forgotten_password_code" => NULL, "forgotten_password_time" => NULL];
        $this->trigger_events("extra_where");
        $this->db->update($this->tables["users"], $data, [$this->identity_column => $identity]);
        return $this->db->affected_rows() == 1;
    }

    protected function _filter_data($table, $data)
    {
        $filtered_data = [];
        $columns = $this->db->list_fields($table);
        if (is_array($data)) {
            foreach ($columns as $column) {
                if (array_key_exists($column, $data)) {
                    $filtered_data[$column] = $data[$column];
                }
            }
        }
        return $filtered_data;
    }

    protected function _random_token($result_length = 32)
    {
        if (!isset($result_length) || intval($result_length) <= 8) {
            $result_length = 32;
        }
        if (function_exists("random_bytes")) {
            return bin2hex(random_bytes($result_length / 2));
        }
        if (function_exists("mcrypt_create_iv")) {
            return bin2hex(mcrypt_create_iv($result_length / 2, MCRYPT_DEV_URANDOM));
        }
        if (function_exists("openssl_random_pseudo_bytes")) {
            return bin2hex(openssl_random_pseudo_bytes($result_length / 2));
        }
        return false;
    }

    protected function _get_hash_parameters($identity = NULL)
    {
        $is_admin = FALSE;
        if ($identity) {
            $user_id = $this->get_user_id_from_identity($identity);
            if ($user_id && $this->in_group($this->config->item("admin_group", "ion_auth"), $user_id)) {
                $is_admin = TRUE;
            }
        }
        $params = FALSE;
        switch ($this->hash_method) {
            case "bcrypt":
                $params = ["cost" => $is_admin ? $this->config->item("bcrypt_admin_cost", "ion_auth") : $this->config->item("bcrypt_default_cost", "ion_auth")];
                break;
            case "argon2":
                $params = $is_admin ? $this->config->item("argon2_admin_params", "ion_auth") : $this->config->item("argon2_default_params", "ion_auth");
                break;
            default:
        }
        return $params;
    }

    protected function _get_hash_algo()
    {
        $algo = FALSE;
        switch ($this->hash_method) {
            case "bcrypt":
                $algo = PASSWORD_BCRYPT;
                break;
            case "argon2":
                $algo = PASSWORD_ARGON2I;
                break;
            default:
        }
        return $algo;
    }

    protected function _generate_selector_validator_couple($selector_size = 40, $validator_size = 128)
    {
        $selector = $this->_random_token($selector_size);
        $validator = $this->_random_token($validator_size);
        $validator_hashed = $this->hash_password($validator);
        $user_code = "{$selector}.{$validator}";
        return (object) ["selector" => $selector, "validator_hashed" => $validator_hashed, "user_code" => $user_code];
    }

    protected function _retrieve_selector_validator_couple($user_code)
    {
        if ($user_code) {
            $tokens = explode(".", $user_code);
            if (count($tokens) === 2) {
                return (object) ["selector" => $tokens[0], "validator" => $tokens[1]];
            }
        }
        return false;
    }

    protected function _password_verify_sha1_legacy($identity, $password, $hashed_password_db)
    {
        $this->trigger_events("pre_sha1_password_migration");
        if ($this->config->item("store_salt", "ion_auth")) {
            $query = $this->db->select("salt")->where($this->identity_column, $identity)->limit(1)->get($this->tables["users"]);
            $salt_db = $query->row();
            if ($query->num_rows() !== 1) {
                $this->trigger_events(["post_sha1_password_migration", "post_sha1_password_migration_unsuccessful"]);
                return false;
            }
            $hashed_password = sha1($password . $salt_db->salt);
        } else {
            $salt_length = $this->config->item("salt_length", "ion_auth");
            if (!$salt_length) {
                $this->trigger_events(["post_sha1_password_migration", "post_sha1_password_migration_unsuccessful"]);
                return false;
            }
            $salt = substr($hashed_password_db, 0, $salt_length);
            $hashed_password = $salt . substr(sha1($salt . $password), 0, -$salt_length);
        }
        if ($hashed_password === $hashed_password_db) {
            $result = $this->_set_password_db($identity, $password);
            if ($result) {
                $this->trigger_events(["post_sha1_password_migration", "post_sha1_password_migration_successful"]);
            } else {
                $this->trigger_events(["post_sha1_password_migration", "post_sha1_password_migration_unsuccessful"]);
            }
            return $result;
        }
        $this->trigger_events(["post_sha1_password_migration", "post_sha1_password_migration_unsuccessful"]);
        return false;
    }
}
