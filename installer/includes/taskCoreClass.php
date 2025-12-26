<?php

class Core {
    function checkEmpty($data) {
        if (!empty($data['hostname']) && !empty($data['username']) && !empty($data['database'])) {
            return true;
        } else {
            return false;
        }
    }

    function show_message($type, $message) {
        return $message;
    }

    function getAllData($data) {
        return $data;
    }

    function write_db_config($data) {
        $output_path = '../.env';

        $scheme = (isset($_SERVER['HTTPS']) && @$_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $basePath = preg_replace('#/installer/?$#', '', $basePath);
        $baseUrl = isset($_SERVER['HTTP_HOST']) ? rtrim($scheme . '://' . $_SERVER['HTTP_HOST'] . ($basePath ? $basePath : ''), '/') : '';

        try {
            $encryptionKey = bin2hex(random_bytes(16));
        } catch (Exception $e) {
            $encryptionKey = 'change-me';
        }

        $env_content = "BASE_URL=" . $baseUrl . "\n";
        $env_content .= "DB_HOST=" . $data['hostname'] . "\n";
        $env_content .= "DB_USER=" . $data['username'] . "\n";
        $env_content .= "DB_PASS=" . $data['password'] . "\n";
        $env_content .= "DB_NAME=" . $data['database'] . "\n";
        $env_content .= "ENCRYPTION_KEY=" . $encryptionKey . "\n";
        $env_content .= "SESS_SAVE_PATH=cache/session\n";

        $handle = fopen($output_path, 'w+');
        @chmod($output_path, 0644);

        if (fwrite($handle, $env_content)) {
            fclose($handle);
            return true;
        } else {
            fclose($handle);
            return false;
        }
    }

    function checkFile() {
        return true;
    }
}
