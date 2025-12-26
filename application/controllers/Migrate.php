<?php

/*   ________________________________________
    |                 GarudaCBT              |
    |    https://github.com/garudacbt/cbt    |
    |________________________________________|
*/
defined("BASEPATH") or exit("No direct script access allowed");
class Migrate extends CI_Controller
{
    public function index()
    {
        echo "Controller file index method run.";
    }
    public function CreateMigration($version = "20230902000000")
    {
        $this->load->library("migration");
        if ($this->migration->current() === FALSE) {
            show_error($this->migration->error_string());
        } else {
            echo "The migration file has executed successfully.";
        }
    }
    public function undoMigration($version = NULL)
    {
        $this->load->library("migration");
        $migrations = $this->migration->find_migrations();
        $migrationKeys = array();
        foreach ($migrations as $key => $migration) {
            $migrationKeys[] = $key;
        }
        if (isset($version) && array_key_exists($version, $migrations) && $this->migration->version($version)) {
            echo "The migration was undo";
            exit;
        }
        if (isset($version) && !array_key_exists($version, $migrations)) {
            echo "The migration with selected version doesn\xe2\x80\x99t exist.";
            yfnP1:
            // [PHPDeobfuscator] Implied return
            return;
        }
        $penultimate = sizeof($migrationKeys) == 1 ? 0 : $migrationKeys[sizeof($migrationKeys) - 2];
        if ($this->migration->version($penultimate)) {
            echo "The migration has been reverted successfully.";
            exit;
        }
        echo "Couldn\\\xe2\x80\x99t roll back the migration.";
        exit;
    }
    public function resetMigration()
    {
        $this->load->library("migration");
        if ($this->migration->current() !== FALSE) {
            echo "The migration was revert to the version set in the config file.";
            return true;
        }
        echo "Couldn\\\xe2\x80\x99t reset migration.";
        show_error($this->migration->error_string());
        exit;
    }
    function make_base()
    {
        $this->load->library("ci_migrations_generator/Sqltoci");
        $this->sqltoci->generate("kelas_jadwal_kbm");
    }
}
