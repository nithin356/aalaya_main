<?php
/**
 * Database Connection using PDO
 */

// Define project paths
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_FILE', BASE_PATH . '/config/config.ini');

// Read config.ini
if (!file_exists(CONFIG_FILE)) {
    die("Configuration file not found at " . CONFIG_FILE);
}

$config = parse_ini_file(CONFIG_FILE, true);

if (!$config) {
    die("Error parsing configuration file.");
}

// Database credentials
$host = $config['database']['host'] ?? 'localhost';
$db   = $config['database']['database'] ?? 'aalaya_db';
$user = $config['database']['username'] ?? 'root';
$pass = $config['database']['password'] ?? '';
$port = $config['database']['port'] ?? '3306';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // If database doesn't exist, we might need to create it or show a friendly error
     if ($e->getCode() == 1049) {
         die("Database '$db' does not exist. Please run the schema.sql file in your MySQL manager.");
     }
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Function to get the PDO instance
function getDB() {
    global $pdo;
    return $pdo;
}
?>
