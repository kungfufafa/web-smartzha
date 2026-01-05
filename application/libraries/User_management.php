<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User_management
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Generate secure random password
     */
    public function generatePassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    /**
     * Activate user account (uses Ion Auth activation, not registration)
     */
    public function activateUser($user_id)
    {
        return $this->CI->ion_auth->activate($user_id);
    }

    /**
     * Deactivate user account (uses Ion Auth deactivation, not deletion)
     */
    public function deactivateUser($user_id)
    {
        return $this->CI->ion_auth->deactivate($user_id);
    }

    /**
     * Reset login attempts for user
     */
    public function resetLogin($username)
    {
        $this->CI->db->where('login', $username);
        return $this->CI->db->delete('login_attempts');
    }

    /**
     * Standard JSON response
     */
    public function jsonResponse($data, $encode = true)
    {
        if ($encode) {
            $data = json_encode($data);
        }
        $this->CI->output->set_content_type('application/json')->set_output($data);
    }

    /**
     * Validate admin access
     */
    public function requireAdmin()
    {
        if (!$this->CI->ion_auth->logged_in() || !$this->CI->ion_auth->is_admin()) {
            $this->CI->output->set_status_header(403);
            show_error('Hanya Administrator yang diberi hak untuk mengakses halaman ini', 403, 'Akses Terlarang');
        }
    }

    /**
     * Check username availability
     */
    public function isUsernameAvailable($username)
    {
        return !$this->CI->ion_auth->username_check($username);
    }

    /**
     * Check email availability
     */
    public function isEmailAvailable($email)
    {
        return !$this->CI->ion_auth->email_check($email);
    }

    /**
     * Create new user with password (password returned but not logged)
     */
    public function createUser($username, $password, $email, $additional_data, $group_ids = [])
    {
        return $this->CI->ion_auth->register($username, $password, $email, $additional_data, $group_ids);
    }

    /**
     * Parse name into first and last name
     */
    public function parseName($full_name)
    {
        $parts = explode(' ', trim($full_name));
        $first_name = $parts[0] ?? '';
        $last_name = count($parts) > 2 ? $parts[1] : end($parts);

        return [
            'first_name' => $first_name,
            'last_name' => $last_name
        ];
    }

    /**
     * Get reset count for DataTable
     */
    public function getResetCount($username)
    {
        $count = $this->CI->db
            ->where('login', $username)
            ->count_all_results('login_attempts');

        return $count;
    }
}
