# AGENTS.md

## Project Overview

SMARTZHA is a Computer-Based Testing (CBT) and e-learning platform built with **CodeIgniter 3.x** (PHP MVC framework). Forked from GarudaCBT (MIT license). Uses pocketarc/codeigniter fork for PHP 8.4+ compatibility.

## Build/Test Commands

```bash
composer install
cp .env.example .env
# Configure DB_HOST, DB_USER, DB_PASS, DB_NAME in .env

./vendor/bin/phpunit                    # All tests
./vendor/bin/phpunit path/to/test.php    # Single test
php -l file.php  # Check syntax
# Database ops via UI: Settings → Database → Backup/Restore/Update
```

## CodeIgniter 3.x Core Architecture

### Initialization Flow
1. **index.php** → Sets ENVIRONMENT, defines paths (BASEPATH, APPPATH, VIEWPATH), loads .env, checks DB, requires CodeIgniter.php
2. **CodeIgniter.php** → Loads constants, error handlers, core classes: Benchmark → Hooks → Config → UTF8 → URI → Router → Output → Security → Input → Language
3. **Router** → Parses URI, matches routes, determines controller/method
4. **Controller** → Instantiates class, executes method, captures output
5. **Output** → Sends response to browser

### Routing & URL Structure
- **Default**: `/controller/method/param1/param2` (e.g., `auth/login`, `dashboard/index`)
- **Controller**: First segment → `application/controllers/Name.php` → `class Name extends CI_Controller`
- **Method**: Second segment → method name (defaults to `index`)
- **Routes**: Custom routes in `application/config/routes.php`

### MVC Pattern

**Controllers**: Handle requests, load models/libs, render views. Use `$this->load` for loading.
**Models**: Database ops only. Extend `CI_Model`, access `$this->db`.
**Views**: HTML in `application/views/`. Variables extracted to local scope.

**Loading**: Models via `$this->load->model('Model_name', 'alias')`. Libraries via `$this->load->library()`. Helpers via `$this->load->helper()`. Views via `$this->load->view()`.

**Autoloading**: Configure in `application/config/autoload.php`. Database, ion_auth, session autoloaded by default. Models generally NOT autoloaded.

## Code Style Guidelines

**PHP Style (CodeIgniter Standard)**: Allman indentation, snake_case methods/variables, PascalCase classes.

```php
class MyClass
{
    public function myMethod()
    {
        if ($condition)
        {
            // code
        }
        else
        {
            // code
        }
    }
}
```

**Naming**: Controllers: `Auth.php` → `class Auth`. Models: `Master_model.php` → `class Master_model`. Tables: `snake_case` with prefix (e.g., `master_siswa`).

### Security Requirements

**SQL Injection**: ALWAYS use Query Builder or parameter binding.
```php
$this->db->where('id', $id)->get('table');  // GOOD
$this->db->query("SELECT * FROM table WHERE id = $id");  // BAD
```

**XSS**: Use TRUE for xss_clean on POST data: `$name = $this->input->post('name', TRUE);`

**File Access**: `defined('BASEPATH') OR exit('No direct script access allowed');` at file top.

**CSRF**: Enabled via `$config['csrf_protection'] = TRUE`.

### Database Queries (Query Builder)

```php
$this->db->insert('table', $data);
$this->db->where('id', $id)->update('table', $data);
$this->db->where_in('id', $ids)->delete('table');
$this->db->select('a.*, b.name')->from('table_a a')->join('table_b b', 'a.id = b.id', 'left')->get()->result();
$offset = ($page - 1) * $limit;
$result = $this->db->limit($limit, $offset)->get('table')->result();
```

### Error Handling

**Form Validation**: `$this->form_validation->set_rules('field', 'Label', 'rules');`
```php
if ($this->form_validation->run() === FALSE) {
    $this->data['message'] = validation_errors();
    $this->load->view('form', $this->data);
}
```

**Database Errors**: Check success, log errors.
```php
$insert = $this->db->insert('table', $data);
if (!$insert) {
    log_message('error', 'DB insert failed: ' . $this->db->error()['message']);
    return FALSE;
}
```

### JSON API & Auth

**JSON**: `$this->output->set_content_type('application/json')->set_output(json_encode($data));`

**Ion Auth**: `$this->ion_auth->logged_in()`, `$this->ion_auth->is_admin()`, `$this->ion_auth->in_group('guru')`, `$this->ion_auth->user()->row()->id`

### Environment (.env)

```bash
DB_HOST=localhost
DB_USER=root
DB_PASS=password
DB_NAME=smartzha
BASE_URL=http://localhost/smartzha
ENCRYPTION_KEY=random_32_char_string
SESS_SAVE_PATH=cache/session
```

Access: `$baseUrl = $_ENV['BASE_URL'] ?? getenv('BASE_URL');`

## Common Patterns

**Batch Ops**: `$this->db->insert_batch()`, `$this->db->update_batch()`

**Foreign Keys**: `$this->db->query('SET FOREIGN_KEY_CHECKS=0');` ... `$this->db->query('SET FOREIGN_KEY_CHECKS=1');`

**Frontend**: jQuery + Bootstrap, module pattern in `assets/app/js/`

**Notes**: Default controller: `auth`. Timezone: Asia/Jakarta. Language: Indonesian UI, English comments. Session: `cache/session/`. Logs: `application/logs/`.

**When contributing**: Follow Allman indent, use Query Builder, sanitize inputs, check auth, write clean code, test thoroughly.
