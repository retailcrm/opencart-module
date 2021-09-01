<?php

require_once(__DIR__.'/../vendor/autoload.php');

class RoboFile extends \Robo\Tasks
{
    use \Robo\Task\Development\loadTasks;
    use \Robo\Common\TaskIO;

    const OPENCART_DOWNLOAD_URL = [
        '3.0.1.2' => 'https://github.com/opencart/opencart/releases/download/3.0.1.2/3.0.1.2-opencart.zip',
        '3.0.2.0' => 'https://github.com/opencart/opencart/releases/download/3.0.2.0/3.0.2.0-OpenCart.zip',
        '3.0.3.4' => 'https://github.com/opencart/opencart/releases/download/3.0.3.4/opencart-3.0.3.4-core-pre.zip'
    ];

    const OPENCART_ROOT_DIR = [
        '3.0.3.4' => 'opencart-3.0.3.4/upload'
    ];

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

    public function opencartInstall()
    {
        $startUp = 'admin/controller/startup/test_startup.php';
        $startUpTo = 'admin/controller/startup/test_startup.php';
        $version = getenv('OPENCART');
        $ocZip = sprintf('/tmp/opencart-%s.zip', $version);

        $this->taskDeleteDir($this->root_dir . 'www')->run();

        file_put_contents($ocZip, file_get_contents($this->getOpencartDownloadUrl($version)));

        $this->_exec(sprintf('unzip %s -d /tmp/opencart', $ocZip));
        $this->taskFileSystemStack()
            ->mirror(
                $this->getOpencartRootDir($version),
                $this->root_dir . 'www'
            )
            ->copy(
                $this->root_dir . 'vendor/beyondit/opencart-test-suite/src/upload/system/config/test-config.php',
                $this->root_dir . 'www/system/config/test-config.php'
            )
            ->copy(
                $this->root_dir . 'tests/3/admin_config.php',
                $this->root_dir . 'www/admin/config.php'
            )
            ->copy(
                $this->root_dir . 'vendor/beyondit/opencart-test-suite/src/upload/' . $startUp,
                $this->root_dir . 'www/' . $startUpTo
            )
            ->chmod($this->root_dir . 'www', 0777, 0000, true)
            ->run();

        if (getenv('TEST_SUITE') === '3') {
            $this->taskFileSystemStack()->copy(
                $this->root_dir . 'vendor/beyondit/opencart-test-suite/src/upload/system/library/session/test.php',
                $this->root_dir . 'www/system/library/session/test.php'
            )->run();
        }

        // Openbay was removed in 3.0.3.6
        // Unfortunately, those configs from test suite still require it.
        if (
            '3.0.3.4' === getenv('OPENCART') ||
            version_compare(getenv('OPENCART'), '3.0.3.6', '>=')
        ) {
            $testConfigFile = $this->root_dir . 'www/system/config/test-config.php';
            $testStartupFile = $this->root_dir . 'www/' . $startUpTo;
            $testConfig = file_get_contents($testConfigFile);
            $testStartup = file_get_contents($testStartupFile);

            $testConfig = str_ireplace("'openbay'", '', $testConfig);
            $testStartup = str_ireplace('$this->registry->set(\'openbay\', new Openbay($this->registry));', '', $testStartup);

            file_put_contents($testConfigFile, $testConfig);
            file_put_contents($testStartupFile, $testStartup);
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
            $this->printTaskError($e->getMessage());
            $this->printTaskError("<error> Could not connect to database...");
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

    private function getOpencartDownloadUrl(string $version): string
    {
        if (version_compare($version, '3.0.1.1', '<=')) {
            return sprintf('https://github.com/opencart/opencart/releases/download/%s/%s-compiled.zip', $version, $version);
        }

        if (array_key_exists($version, self::OPENCART_DOWNLOAD_URL)) {
            return self::OPENCART_DOWNLOAD_URL[$version];
        }

        return sprintf('https://github.com/opencart/opencart/releases/download/%s/opencart-%s.zip', $version, $version);
    }

    private function getOpencartRootDir(string $version): string
    {
        if (array_key_exists($version, self::OPENCART_ROOT_DIR)) {
            return '/tmp/opencart/' . self::OPENCART_ROOT_DIR[$version];
        }

        return '/tmp/opencart/upload';
    }

    private function restoreSampleData($conn)
    {
        $sql = file_get_contents($this->root_dir . 'tests/opencart_sample_data_3.sql');

        $conn->exec("USE " . $this->opencart_config['db_database']);

        foreach (explode(";\n", $sql) as $sql) {
            $sql = trim($sql);

            if ($sql) {
                $conn->exec($sql);
            }
        }
    }
}
