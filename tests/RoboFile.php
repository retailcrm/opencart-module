<?php

require_once(__DIR__.'/../vendor/autoload.php');

class RoboFile extends \Robo\Tasks
{
    use \Robo\Task\Development\loadTasks;
    use \Robo\Common\TaskIO;

    /**
     * @var array
     */
    private $opencart_config;

    /**
     * @var int
     */
    private $server_port = 80;

    /**
     * @var string
     */
    private $server_url = 'http://localhost';

    private $root_dir = __DIR__ . '/../';

    public function __construct()
    {
        if ($_ENV) {
            foreach ($_ENV as $option => $value) {
                if (substr($option, 0, 3) === 'OC_') {
                    $option = strtolower(substr($option, 3));
                    $this->opencart_config[$option] = $value;
                } elseif ($option === 'SERVER_PORT') {
                    $this->server_port = (int) $value;
                } elseif ($option === 'SERVER_URL') {
                    $this->server_url = $value;
                }
            }
        } else {
            $this->opencart_config = [
                'db_hostname' => getenv('OC_DB_HOSTNAME'),
                'db_username' => getenv('OC_DB_USERNAME'),
                'db_password' => getenv('OC_DB_PASSWORD'),
                'db_database' => getenv('OC_DB_DATABASE'),
                'db_driver' => getenv('OC_DB_DRIVER'),
                'username' => getenv('OC_USERNAME'),
                'password' => getenv('OC_PASSWORD'),
                'email' => getenv('OC_EMAIL')
            ];
        }

        $this->opencart_config['http_server']  = $this->server_url.':'.$this->server_port.'/';

        $required = array('db_username', 'password', 'email');
        $missing = array();
        foreach ($required as $config) {
            if (empty($this->opencart_config[$config])) {
                $missing[] = 'OC_'.strtoupper($config);
            }
        }

        if (!empty($missing)) {
            $this->printTaskError("<error> Missing ".implode(', ', $missing));
            $this->printTaskError("<error> See .env.sample ");
            die();
        }
    }

    public function opencartSetup()
    {
        $startUp = getenv('TEST_SUITE') === '2.3'
            ? 'catalog/controller/startup/test_startup.php'
            : 'admin/controller/startup/test_startup.php';
        $startUpTo = getenv('TEST_SUITE') === '2.3'
            ? 'catalog/controller/startup/test_startup.php'
            : 'admin/controller/startup/test_startup.php';

        $this->taskDeleteDir($this->root_dir . 'www')->run();
        $this->taskFileSystemStack()
            ->mirror(
                $this->root_dir . 'vendor/opencart/opencart/upload',
                $this->root_dir . 'www'
            )
            ->copy(
                $this->root_dir . 'vendor/beyondit/opencart-test-suite/src/upload/system/config/test-config.php',
                $this->root_dir . 'www/system/config/test-config.php'
            )
            ->copy(
                $this->root_dir . 'vendor/beyondit/opencart-test-suite/src/upload/' . $startUp,
                $this->root_dir . 'www/' . $startUpTo
            )
            ->chmod($this->root_dir . 'www', 0777, 0000, true)
            ->run();

        if (getenv('TEST_SUITE') === '3.0') {
            $this->taskFileSystemStack()->copy(
                $this->root_dir . 'vendor/beyondit/opencart-test-suite/src/upload/system/library/session/test.php',
                $this->root_dir . 'www/system/library/session/test.php'
            )->run();
        }

        // Create new database, drop if exists already
        try {
            $conn = new PDO("mysql:host=".$this->opencart_config['db_hostname'], $this->opencart_config['db_username'], $this->opencart_config['db_password']);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->exec("DROP DATABASE IF EXISTS `" . $this->opencart_config['db_database'] . "`");
            $conn->exec("CREATE DATABASE `" . $this->opencart_config['db_database'] . "`");
        }
        catch(PDOException $e)
        {
            $this->printTaskError("<error> Could not connect ot database...");
        }

        if (version_compare(getenv('OPENCART'), '3.0.2.0', '<=')) {
            $install_code = file_get_contents($this->root_dir . 'www/install/cli_install.php');
            $storage = <<<EOF
define('DIR_MODIFICATION', DIR_SYSTEM . 'modification/');
define('DIR_STORAGE', DIR_SYSTEM . 'storage/');

EOF;

            file_put_contents(
                $this->root_dir . 'www/install/cli_install.php',
                str_replace("define('DIR_MODIFICATION', DIR_SYSTEM . 'modification/');", $storage, $install_code)
            );
        }

        $install = $this->taskExec('php')->arg($this->root_dir . 'www/install/cli_install.php')->arg('install');
        foreach ($this->opencart_config as $option => $value) {
            $install->option($option, $value);
        }
        $install->run();
        $this->taskDeleteDir($this->root_dir . 'www/install')->run();

        $this->restoreSampleData($conn);

        $conn = null;
    }

    public function opencartRun()
    {
        $this->taskServer($this->server_port)
            ->dir($this->root_dir . 'www')
            ->run();
    }

    public function projectDeploy()
    {
        $this->taskFileSystemStack()
            ->mirror($this->root_dir . 'src/upload', $this->root_dir . 'www')
//            ->copy('src/install.xml','www/system/install.ocmod.xml') if exist modification for OCMOD
            ->run();
    }

    public function projectWatch()
    {
        $this->projectDeploy();

        $this->taskWatch()
            ->monitor($this->root_dir . 'composer.json', function () {
                $this->taskComposerUpdate()->run();
                $this->projectDeploy();
            })->monitor($this->root_dir . 'src/', function () {
                $this->projectDeploy();
            })->run();
    }

    public function projectPackage()
    {
        $this->taskDeleteDir('target')->run();
        $this->taskFileSystemStack()->mkdir('target')->run();

        $zip = new ZipArchive();
        $filename = "target/build.ocmod.zip";

        if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
            $this->printTaskError("<error> Could not create ZipArchive");
            exit();
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator("src", \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->isReadable()) {
                $zip->addFile($file->getPathname(),substr($file->getPathname(),4));
            }
        }

        $zip->close();
    }

    private function restoreSampleData($conn)
    {
        if (getenv('TEST_SUITE') === '2.3') {
            $sql = file_get_contents($this->root_dir . 'tests/opencart_sample_data.sql');
        } else {
            $sql = file_get_contents($this->root_dir . 'tests/opencart_sample_data_3.sql');
        }

        $conn->exec("USE " . $this->opencart_config['db_database']);

        foreach (explode(";\n", $sql) as $sql) {
            $sql = trim($sql);

            if ($sql) {
                $conn->exec($sql);
            }
        }
    }
}