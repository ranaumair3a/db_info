<?php
/**
 * Advanced Database Management Tool - DataHub Pro
 * Comprehensive database administration interface with advanced controls
 */

// Security configurations
ini_set('display_errors', 0);
error_reporting(0);

// Session management
session_start();

class DatabaseManager {
    private $connection = null;
    private $config = [];
    
    public function __construct() {
        $this->config = [
            'title' => 'DataHub Pro',
            'version' => '3.0.0',
            'theme' => 'advanced'
        ];
    }
    
    public function connect($host, $username, $password, $database = '', $port = 3306) {
        try {
            $dsn = "mysql:host=$host;port=$port";
            if ($database) {
                $dsn .= ";dbname=$database";
            }
            $dsn .= ";charset=utf8mb4";
            
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            
            $_SESSION['db_connection'] = [
                'host' => $host,
                'username' => $username,
                'password' => $password,
                'database' => $database,
                'port' => $port
            ];
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getDatabases() {
        try {
            $stmt = $this->connection->query("SHOW DATABASES");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getTables($database) {
        try {
            $stmt = $this->connection->query("SHOW TABLES FROM `$database`");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getTableInfo($database, $table) {
        try {
            $info = [];
            
            // Get basic table info
            $stmt = $this->connection->query("SHOW TABLE STATUS FROM `$database` WHERE Name = '$table'");
            $tableStatus = $stmt->fetch();
            
            // Get column count
            $stmt = $this->connection->query("SELECT COUNT(*) as column_count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = '$table'");
            $columnInfo = $stmt->fetch();
            
            // Get row count
            $stmt = $this->connection->query("SELECT COUNT(*) as row_count FROM `$database`.`$table`");
            $rowInfo = $stmt->fetch();
            
            $info = [
                'name' => $table,
                'engine' => $tableStatus['Engine'] ?? 'Unknown',
                'rows' => $rowInfo['row_count'] ?? 0,
                'columns' => $columnInfo['column_count'] ?? 0,
                'size' => $this->formatBytes(($tableStatus['Data_length'] ?? 0) + ($tableStatus['Index_length'] ?? 0)),
                'created' => $tableStatus['Create_time'] ?? 'Unknown',
                'updated' => $tableStatus['Update_time'] ?? 'Unknown',
                'collation' => $tableStatus['Collation'] ?? 'Unknown'
            ];
            
            return $info;
        } catch (PDOException $e) {
            return [
                'name' => $table,
                'engine' => 'Unknown',
                'rows' => 0,
                'columns' => 0,
                'size' => '0 B',
                'created' => 'Unknown',
                'updated' => 'Unknown',
                'collation' => 'Unknown'
            ];
        }
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    public function getTableStructure($database, $table) {
        try {
            $stmt = $this->connection->query("DESCRIBE `$database`.`$table`");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function executeQuery($query) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            
            if (stripos(trim($query), 'SELECT') === 0 || stripos(trim($query), 'SHOW') === 0 || stripos(trim($query), 'DESCRIBE') === 0) {
                return [
                    'success' => true,
                    'data' => $stmt->fetchAll(),
                    'columns' => $this->getColumnNames($stmt),
                    'rows' => $stmt->rowCount()
                ];
            } else {
                return [
                    'success' => true,
                    'affected' => $stmt->rowCount(),
                    'message' => 'Query executed successfully'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function getColumnNames($stmt) {
        $columns = [];
        for ($i = 0; $i < $stmt->columnCount(); $i++) {
            $meta = $stmt->getColumnMeta($i);
            $columns[] = $meta['name'];
        }
        return $columns;
    }
    
    public function getTableData($database, $table, $limit = 100, $offset = 0, $orderBy = '', $orderDir = 'ASC') {
        try {
            $countStmt = $this->connection->query("SELECT COUNT(*) FROM `$database`.`$table`");
            $totalRows = $countStmt->fetchColumn();
            
            $query = "SELECT * FROM `$database`.`$table`";
            if ($orderBy) {
                $query .= " ORDER BY `$orderBy` $orderDir";
            }
            $query .= " LIMIT $limit OFFSET $offset";
            
            $stmt = $this->connection->query($query);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(),
                'columns' => $this->getColumnNames($stmt),
                'total' => $totalRows,
                'limit' => $limit,
                'offset' => $offset
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function exportMultipleTables($database, $tables, $format = 'sql') {
        $output = '';
        foreach ($tables as $table) {
            if ($format === 'sql') {
                $output .= $this->exportTableSQL($database, $table) . "\n\n";
            } elseif ($format === 'csv') {
                $output .= "-- Table: $table --\n" . $this->exportTableCSV($database, $table) . "\n\n";
            }
        }
        return $output;
    }
    
    public function exportTable($database, $table, $format = 'sql') {
        if ($format === 'sql') {
            return $this->exportTableSQL($database, $table);
        } elseif ($format === 'csv') {
            return $this->exportTableCSV($database, $table);
        }
        return false;
    }
    
    private function exportTableSQL($database, $table) {
        try {
            $sql = "-- Export for table: $table\n";
            $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
            
            // Get CREATE TABLE statement
            $stmt = $this->connection->query("SHOW CREATE TABLE `$database`.`$table`");
            $createTable = $stmt->fetch();
            $sql .= $createTable['Create Table'] . ";\n\n";
            
            // Get data
            $stmt = $this->connection->query("SELECT * FROM `$database`.`$table`");
            while ($row = $stmt->fetch()) {
                $values = array_map(function($value) {
                    return $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                }, $row);
                $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
            
            return $sql;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function exportTableCSV($database, $table) {
        try {
            $stmt = $this->connection->query("SELECT * FROM `$database`.`$table`");
            $columns = $this->getColumnNames($stmt);
            
            $csv = implode(',', $columns) . "\n";
            while ($row = $stmt->fetch()) {
                $csv .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value ?? '') . '"';
                }, $row)) . "\n";
            }
            
            return $csv;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function searchTables($database, $searchTerm) {
        try {
            $stmt = $this->connection->prepare("SHOW TABLES FROM `$database` LIKE ?");
            $stmt->execute(['%' . $searchTerm . '%']);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }
}

// Initialize the database manager
$dbManager = new DatabaseManager();

// Handle AJAX requests first
if ($_POST && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if (isset($_SESSION['db_connection'])) {
        $conn = $_SESSION['db_connection'];
        $dbManager->connect($conn['host'], $conn['username'], $conn['password'], $conn['database'], $conn['port']);
        
        switch ($_POST['action']) {
            case 'get_tables':
                $tables = $dbManager->getTables($_POST['database']);
                $tablesWithInfo = [];
                foreach ($tables as $table) {
                    $tablesWithInfo[] = $dbManager->getTableInfo($_POST['database'], $table);
                }
                echo json_encode($tablesWithInfo);
                break;
                
            case 'get_table_data':
                $limit = $_POST['limit'] ?? 100;
                $offset = $_POST['offset'] ?? 0;
                $orderBy = $_POST['orderBy'] ?? '';
                $orderDir = $_POST['orderDir'] ?? 'ASC';
                echo json_encode($dbManager->getTableData($_POST['database'], $_POST['table'], $limit, $offset, $orderBy, $orderDir));
                break;
                
            case 'get_table_structure':
                echo json_encode($dbManager->getTableStructure($_POST['database'], $_POST['table']));
                break;
                
            case 'search_tables':
                echo json_encode($dbManager->searchTables($_POST['database'], $_POST['search']));
                break;
        }
    } else {
        echo json_encode(['error' => 'Not connected']);
    }
    exit;
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'connect':
                $connected = $dbManager->connect(
                    $_POST['host'],
                    $_POST['username'],
                    $_POST['password'],
                    $_POST['database'] ?? '',
                    $_POST['port'] ?? 3306
                );
                break;
                
            case 'query':
                if (isset($_SESSION['db_connection'])) {
                    $conn = $_SESSION['db_connection'];
                    $dbManager->connect($conn['host'], $conn['username'], $conn['password'], $conn['database'], $conn['port']);
                    $queryResult = $dbManager->executeQuery($_POST['query']);
                }
                break;
                
            case 'export':
                if (isset($_SESSION['db_connection'])) {
                    $conn = $_SESSION['db_connection'];
                    $dbManager->connect($conn['host'], $conn['username'], $conn['password'], $conn['database'], $conn['port']);
                    
                    if (isset($_POST['tables']) && is_array($_POST['tables'])) {
                        $exportData = $dbManager->exportMultipleTables($_POST['database'], $_POST['tables'], $_POST['format']);
                        $filename = $_POST['database'] . '_export.' . $_POST['format'];
                    } else {
                        $exportData = $dbManager->exportTable($_POST['database'], $_POST['table'], $_POST['format']);
                        $filename = $_POST['table'] . '.' . $_POST['format'];
                    }
                    
                    if ($exportData) {
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="' . $filename . '"');
                        echo $exportData;
                        exit;
                    }
                }
                break;
                
            case 'disconnect':
                session_destroy();
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
                break;
        }
    }
}

// Check if we have an active connection
$isConnected = false;
if (isset($_SESSION['db_connection'])) {
    $conn = $_SESSION['db_connection'];
    $isConnected = $dbManager->connect($conn['host'], $conn['username'], $conn['password'], $conn['database'], $conn['port']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DataHub Pro - Advanced Database Management</title>
    
    <!-- External Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-sql.min.js"></script>
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #48bb78;
            --danger-color: #f56565;
            --warning-color: #ed8936;
            --info-color: #4299e1;
            --dark-color: #2d3748;
            --light-color: #f7fafc;
            --border-color: #e2e8f0;
            --hover-color: #edf2f7;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            color: #2d3748;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header h1 {
            color: var(--dark-color);
            font-size: 2.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header .version {
            color: #718096;
            font-size: 1rem;
            margin-top: 5px;
        }
        
        .header-controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1.1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
            background: rgba(255, 255, 255, 1);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-align: center;
            margin: 3px;
            position: relative;
            overflow: hidden;
        }
        
        .btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover:before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #718096;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4a5568;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #38a169;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #e53e3e;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: white;
        }
        
        .btn-info {
            background: var(--info-color);
            color: white;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .btn-large {
            padding: 16px 32px;
            font-size: 18px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .layout {
            display: flex;
            gap: 25px;
        }
        
        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 25px;
            height: fit-content;
            min-width: 350px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .main-content {
            flex: 1;
        }
        
        .controls-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
        }
        
        .view-modes {
            display: flex;
            gap: 10px;
        }
        
        .view-mode {
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .view-mode.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .view-mode:hover {
            border-color: var(--primary-color);
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            max-height: 600px;
            overflow-y: auto;
            background: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            word-break: break-word;
            max-width: 300px;
            position: relative;
        }
        
        th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 700;
            color: var(--dark-color);
            position: sticky;
            top: 0;
            z-index: 10;
            cursor: pointer;
            user-select: none;
        }
        
        th:hover {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        }
        
        th .sort-icon {
            margin-left: 8px;
            opacity: 0.5;
            transition: opacity 0.3s ease;
        }
        
        th.sorted .sort-icon {
            opacity: 1;
            color: var(--primary-color);
        }
        
        tr:nth-child(even) {
            background: rgba(248, 249, 250, 0.5);
        }
        
        tr:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        
        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 5px solid;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .alert-success {
            background: rgba(72, 187, 120, 0.1);
            color: #22543d;
            border-left-color: var(--success-color);
        }
        
        .alert-danger {
            background: rgba(245, 101, 101, 0.1);
            color: #742a2a;
            border-left-color: var(--danger-color);
        }
        
        .alert-info {
            background: rgba(66, 153, 225, 0.1);
            color: #2c5282;
            border-left-color: var(--info-color);
        }
        
        .alert-warning {
            background: rgba(237, 137, 54, 0.1);
            color: #7b341e;
            border-left-color: var(--warning-color);
        }
        
        .query-editor {
            min-height: 250px;
            font-family: 'Fira Code', 'Consolas', 'Monaco', monospace;
            resize: vertical;
            line-height: 1.5;
            background: #2d3748;
            color: #e2e8f0;
            border: none;
        }
        
        .db-list, .table-list {
            list-style: none;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .db-item, .table-item {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .db-item:hover, .table-item:hover {
            background: var(--hover-color);
            border-color: var(--border-color);
            transform: translateX(5px);
        }
        
        .db-item.active, .table-item.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .table-info {
            font-size: 0.85rem;
            color: #718096;
            margin-top: 4px;
        }
        
        .table-item.active .table-info {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .connection-status {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
        }
        
        .connection-status.connected {
            background: rgba(72, 187, 120, 0.1);
            color: #22543d;
            border: 1px solid rgba(72, 187, 120, 0.3);
        }
        
        .connection-status.disconnected {
            background: rgba(245, 101, 101, 0.1);
            color: #742a2a;
            border: 1px solid rgba(245, 101, 101, 0.3);
        }
        
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .status-dot.connected {
            background: var(--success-color);
        }
        
        .status-dot.disconnected {
            background: var(--danger-color);
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .loading {
            text-align: center;
            padding: 30px;
            color: #718096;
            font-size: 1.1rem;
        }
        
        .loading i {
            animation: spin 1s linear infinite;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 20px;
            border-bottom: 3px solid var(--primary-color);
            padding-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }
        
        .search-box .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .checkbox-item:hover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }
        
        .checkbox-item.selected {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }
        
        .checkbox-item input[type="checkbox"] {
            margin: 0;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .pagination .btn {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 249, 250, 0.9) 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #718096;
            font-weight: 500;
        }
        
        .table-grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .table-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .table-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-color);
        }
        
        .table-card.selected {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }
        
        .table-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .table-card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .table-card-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 0.9rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #718096;
        }
        
        .query-templates {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }
        
        .template-btn {
            padding: 10px;
            text-align: center;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            font-size: 14px;
        }
        
        .template-btn:hover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.1);
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 90%;
            max-height: 90%;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #718096;
        }
        
        .modal-close:hover {
            color: var(--danger-color);
        }
        
        .export-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .export-card {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .export-card:hover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }
        
        .export-card.selected {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }
        
        .floating-controls {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 100;
        }
        
        .floating-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }
        
        .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }
        
        @media (max-width: 1200px) {
            .layout {
                flex-direction: column;
            }
            
            .sidebar {
                min-width: auto;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
            
            .controls-panel {
                flex-direction: column;
                align-items: stretch;
            }
            
            .floating-controls {
                bottom: 15px;
                right: 15px;
            }
            
            .floating-btn {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div>
                    <h1><i class="fas fa-database"></i> DataHub Pro</h1>
                    <div class="version">Version 3.0.0 - Advanced Database Management System</div>
                </div>
                <div class="header-controls">
                    <button class="btn btn-info" onclick="showHelp()">
                        <i class="fas fa-question-circle"></i> Help
                    </button>
                    <button class="btn btn-warning" onclick="showSettings()">
                        <i class="fas fa-cog"></i> Settings
                    </button>
                </div>
            </div>
        </div>
        
        <div class="connection-status <?php echo $isConnected ? 'connected' : 'disconnected'; ?>">
            <div class="status-dot <?php echo $isConnected ? 'connected' : 'disconnected'; ?>"></div>
            <span>
                <i class="fas fa-<?php echo $isConnected ? 'check-circle' : 'times-circle'; ?>"></i>
                <?php echo $isConnected ? 'Connected to database' : 'Not connected to database'; ?>
            </span>
            <?php if ($isConnected): ?>
                <form method="post" style="margin-left: auto;">
                    <input type="hidden" name="action" value="disconnect">
                    <button type="submit" class="btn btn-danger btn-small">
                        <i class="fas fa-sign-out-alt"></i> Disconnect
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <?php if (!$isConnected): ?>
            <div class="card">
                <h2><i class="fas fa-plug"></i> Database Connection</h2>
                <form method="post">
                    <input type="hidden" name="action" value="connect">
                    <div class="grid">
                        <div class="form-group">
                            <label for="host"><i class="fas fa-server"></i> Host</label>
                            <input type="text" id="host" name="host" class="form-control" value="localhost" required>
                        </div>
                        <div class="form-group">
                            <label for="port"><i class="fas fa-door-open"></i> Port</label>
                            <input type="number" id="port" name="port" class="form-control" value="3306">
                        </div>
                    </div>
                    <div class="grid">
                        <div class="form-group">
                            <label for="username"><i class="fas fa-user"></i> Username</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock"></i> Password</label>
                            <input type="password" id="password" name="password" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="database"><i class="fas fa-database"></i> Database (Optional)</label>
                        <input type="text" id="database" name="database" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary btn-large">
                        <i class="fas fa-plug"></i> Connect to Database
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="layout">
                <div class="sidebar">
                    <div class="section-title">
                        <i class="fas fa-database"></i> Databases
                    </div>
                    
                    <div class="search-box">
                        <input type="text" placeholder="Search databases..." id="db-search">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    
                    <ul class="db-list" id="database-list">
                        <?php
                        $databases = $dbManager->getDatabases();
                        foreach ($databases as $db):
                        ?>
                            <li class="db-item" onclick="selectDatabase('<?php echo htmlspecialchars($db); ?>')" data-database="<?php echo htmlspecialchars($db); ?>">
                                <i class="fas fa-database"></i>
                                <span><?php echo htmlspecialchars($db); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div id="tables-section" style="display: none;">
                        <div class="section-title">
                            <i class="fas fa-table"></i> Tables
                            <span class="btn btn-small btn-success" onclick="selectAllTables()">
                                <i class="fas fa-check-square"></i>
                            </span>
                        </div>
                        
                        <div class="search-box">
                            <input type="text" placeholder="Search tables..." id="table-search" onkeyup="searchTables()">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        
                        <div class="view-modes">
                            <div class="view-mode active" onclick="setTableView('list')" data-view="list">
                                <i class="fas fa-list"></i>
                            </div>
                            <div class="view-mode" onclick="setTableView('grid')" data-view="grid">
                                <i class="fas fa-th"></i>
                            </div>
                        </div>
                        
                        <div id="tables-list-view">
                            <ul id="tables-list" class="table-list"></ul>
                        </div>
                        
                        <div id="tables-grid-view" class="table-grid-view" style="display: none;">
                        </div>
                        
                        <div id="selected-tables-info" style="display: none; margin-top: 15px;">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <span id="selected-count">0 tables selected</span>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-success btn-small" onclick="exportSelectedTables('sql')">
                                    <i class="fas fa-download"></i> Export SQL
                                </button>
                                <button class="btn btn-success btn-small" onclick="exportSelectedTables('csv')">
                                    <i class="fas fa-download"></i> Export CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="main-content">
                    <div class="controls-panel">
                        <div class="view-modes">
                            <div class="view-mode active" onclick="setDataView('table')" data-view="table">
                                <i class="fas fa-table"></i> Table
                            </div>
                            <div class="view-mode" onclick="setDataView('cards')" data-view="cards">
                                <i class="fas fa-th-large"></i> Cards
                            </div>
                            <div class="view-mode" onclick="setDataView('json')" data-view="json">
                                <i class="fas fa-code"></i> JSON
                            </div>
                        </div>
                        
                        <div class="controls-group">
                            <select id="rows-per-page" class="form-control" style="width: auto;" onchange="changeRowsPerPage()">
                                <option value="25">25 rows</option>
                                <option value="50">50 rows</option>
                                <option value="100" selected>100 rows</option>
                                <option value="500">500 rows</option>
                                <option value="1000">1000 rows</option>
                            </select>
                            
                            <button class="btn btn-secondary" onclick="refreshData()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            
                            <button class="btn btn-info" onclick="showQueryBuilder()">
                                <i class="fas fa-tools"></i> Query Builder
                            </button>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h3><i class="fas fa-code"></i> SQL Query Editor</h3>
                        <div class="query-templates">
                            <div class="template-btn" onclick="insertTemplate('select')">
                                <i class="fas fa-search"></i> SELECT
                            </div>
                            <div class="template-btn" onclick="insertTemplate('insert')">
                                <i class="fas fa-plus"></i> INSERT
                            </div>
                            <div class="template-btn" onclick="insertTemplate('update')">
                                <i class="fas fa-edit"></i> UPDATE
                            </div>
                            <div class="template-btn" onclick="insertTemplate('delete')">
                                <i class="fas fa-trash"></i> DELETE
                            </div>
                            <div class="template-btn" onclick="insertTemplate('create')">
                                <i class="fas fa-hammer"></i> CREATE
                            </div>
                            <div class="template-btn" onclick="insertTemplate('alter')">
                                <i class="fas fa-wrench"></i> ALTER
                            </div>
                        </div>
                        
                        <form method="post">
                            <input type="hidden" name="action" value="query">
                            <div class="form-group">
                                <textarea name="query" class="form-control query-editor" placeholder="Enter your SQL query here..." id="query-input"></textarea>
                            </div>
                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Execute Query
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="clearQuery()">
                                    <i class="fas fa-eraser"></i> Clear
                                </button>
                                <button type="button" class="btn btn-info" onclick="formatQuery()">
                                    <i class="fas fa-indent"></i> Format
                                </button>
                                <button type="button" class="btn btn-warning" onclick="saveQuery()">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <?php if (isset($queryResult)): ?>
                        <div class="card">
                            <?php if ($queryResult['success']): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <?php if (isset($queryResult['data'])): ?>
                                            Query executed successfully. <?php echo count($queryResult['data']); ?> rows returned.
                                        <?php else: ?>
                                            <?php echo $queryResult['message']; ?> (<?php echo $queryResult['affected']; ?> rows affected)
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (isset($queryResult['data']) && !empty($queryResult['data'])): ?>
                                    <div id="query-results">
                                        <div class="controls-panel">
                                            <button class="btn btn-success btn-small" onclick="exportQueryResults('csv')">
                                                <i class="fas fa-download"></i> Export CSV
                                            </button>
                                            <button class="btn btn-success btn-small" onclick="exportQueryResults('json')">
                                                <i class="fas fa-download"></i> Export JSON
                                            </button>
                                        </div>
                                        
                                        <div class="table-container">
                                            <table id="results-table">
                                                <thead>
                                                    <tr>
                                                        <?php foreach ($queryResult['columns'] as $column): ?>
                                                            <th onclick="sortTable('results-table', <?php echo array_search($column, $queryResult['columns']); ?>)">
                                                                <?php echo htmlspecialchars($column); ?>
                                                                <i class="fas fa-sort sort-icon"></i>
                                                            </th>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($queryResult['data'] as $row): ?>
                                                        <tr>
                                                            <?php foreach ($row as $value): ?>
                                                                <td><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <div>Error: <?php echo htmlspecialchars($queryResult['error']); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div id="table-data" class="card" style="display: none;">
                        <h3 id="table-title"><i class="fas fa-table"></i> Table Data</h3>
                        <div id="table-content"></div>
                    </div>
                    
                    <div id="table-structure" class="card" style="display: none;">
                        <h3><i class="fas fa-sitemap"></i> Table Structure</h3>
                        <div id="structure-content"></div>
                    </div>
                </div>
            </div>
            
            <!-- Floating Action Buttons -->
            <div class="floating-controls">
                <button class="floating-btn btn-primary" onclick="showExportModal()" title="Export Data">
                    <i class="fas fa-download"></i>
                </button>
                <button class="floating-btn btn-success" onclick="showImportModal()" title="Import Data">
                    <i class="fas fa-upload"></i>
                </button>
                <button class="floating-btn btn-info" onclick="showQueryHistory()" title="Query History">
                    <i class="fas fa-history"></i>
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Export Modal -->
    <div id="export-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-download"></i> Export Data</h3>
                <button class="modal-close" onclick="closeModal('export-modal')">&times;</button>
            </div>
            <div class="export-options">
                <div class="export-card" data-format="sql">
                    <i class="fas fa-database" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 15px;"></i>
                    <h4>SQL Export</h4>
                    <p>Export as SQL dump with CREATE and INSERT statements</p>
                </div>
                <div class="export-card" data-format="csv">
                    <i class="fas fa-file-csv" style="font-size: 3rem; color: var(--success-color); margin-bottom: 15px;"></i>
                    <h4>CSV Export</h4>
                    <p>Export as comma-separated values</p>
                </div>
                <div class="export-card" data-format="json">
                    <i class="fas fa-file-code" style="font-size: 3rem; color: var(--info-color); margin-bottom: 15px;"></i>
                    <h4>JSON Export</h4>
                    <p>Export as JSON format</p>
                </div>
            </div>
            <div id="export-tables-list"></div>
            <div class="modal-footer" style="margin-top: 20px; text-align: right;">
                <button class="btn btn-secondary" onclick="closeModal('export-modal')">Cancel</button>
                <button class="btn btn-primary" onclick="performExport()">Export</button>
            </div>
        </div>
    </div>

    <script>
        let currentDatabase = '';
        let currentTable = '';
        let selectedTables = [];
        let currentPage = 0;
        let rowsPerPage = 100;
        let currentOrderBy = '';
        let currentOrderDir = 'ASC';
        let currentView = 'table';
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            const firstDb = document.querySelector('.db-item');
            if (firstDb) {
                const dbName = firstDb.getAttribute('data-database');
                if (dbName) {
                    selectDatabase(dbName);
                }
            }
            
            // Initialize export modal handlers
            initializeExportModal();
        });
        
        function selectDatabase(database) {
            currentDatabase = database;
            
            // Highlight selected database
            document.querySelectorAll('.db-item').forEach(li => li.classList.remove('active'));
            document.querySelector(`[data-database="${database}"]`).classList.add('active');
            
            // Load tables for this database
            loadTables(database);
            
            // Hide table data
            document.getElementById('table-data').style.display = 'none';
            document.getElementById('table-structure').style.display = 'none';
            
            // Clear selected tables
            selectedTables = [];
            updateSelectedTablesInfo();
        }
        
        function loadTables(database) {
            const tablesSection = document.getElementById('tables-section');
            const tablesList = document.getElementById('tables-list');
            const tablesGrid = document.getElementById('tables-grid-view');
            
            tablesSection.style.display = 'block';
            tablesList.innerHTML = '<li class="loading"><i class="fas fa-spinner"></i><br>Loading tables...</li>';
            tablesGrid.innerHTML = '';
            
            // Make AJAX request
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'get_tables');
            formData.append('database', database);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(tables => {
                displayTables(tables);
            })
            .catch(error => {
                console.error('Error:', error);
                tablesList.innerHTML = '<li style="color: var(--danger-color);"><i class="fas fa-exclamation-triangle"></i> Error loading tables</li>';
            });
        }
        
        function displayTables(tables) {
            const tablesList = document.getElementById('tables-list');
            const tablesGrid = document.getElementById('tables-grid-view');
            
            tablesList.innerHTML = '';
            tablesGrid.innerHTML = '';
            
            if (tables.length > 0) {
                tables.forEach(tableInfo => {
                    // List view
                    const li = document.createElement('li');
                    li.className = 'table-item';
                    li.setAttribute('data-table', tableInfo.name);
                    li.innerHTML = `
                        <input type="checkbox" onchange="toggleTableSelection('${tableInfo.name}', this)" style="margin-right: 10px;">
                        <i class="fas fa-table"></i>
                        <div>
                            <div>${tableInfo.name}</div>
                            <div class="table-info">${tableInfo.rows} rows • ${tableInfo.columns} cols • ${tableInfo.size}</div>
                        </div>
                    `;
                    li.onclick = (e) => {
                        if (e.target.type !== 'checkbox') {
                            selectTable(currentDatabase, tableInfo.name, li);
                        }
                    };
                    tablesList.appendChild(li);
                    
                    // Grid view
                    const card = document.createElement('div');
                    card.className = 'table-card';
                    card.setAttribute('data-table', tableInfo.name);
                    card.innerHTML = `
                        <div class="table-card-header">
                            <div class="table-card-title">${tableInfo.name}</div>
                            <input type="checkbox" onchange="toggleTableSelection('${tableInfo.name}', this)">
                        </div>
                        <div class="table-card-meta">
                            <div class="meta-item">
                                <i class="fas fa-list-ol"></i>
                                <span>${tableInfo.rows} rows</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-columns"></i>
                                <span>${tableInfo.columns} cols</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-hdd"></i>
                                <span>${tableInfo.size}</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>${tableInfo.created ? new Date(tableInfo.created).toLocaleDateString() : 'Unknown'}</span>
                            </div>
                        </div>
                    `;
                    card.onclick = (e) => {
                        if (e.target.type !== 'checkbox') {
                            selectTable(currentDatabase, tableInfo.name, card);
                        }
                    };
                    tablesGrid.appendChild(card);
                });
            } else {
                tablesList.innerHTML = '<li style="color: #718096;"><i class="fas fa-info-circle"></i> No tables found</li>';
                tablesGrid.innerHTML = '<div style="color: #718096; text-align: center; padding: 40px;"><i class="fas fa-info-circle"></i><br>No tables found</div>';
            }
        }
        
        function setTableView(view) {
            document.querySelectorAll('.view-mode').forEach(el => el.classList.remove('active'));
            document.querySelector(`[data-view="${view}"]`).classList.add('active');
            
            const listView = document.getElementById('tables-list-view');
            const gridView = document.getElementById('tables-grid-view');
            
            if (view === 'list') {
                listView.style.display = 'block';
                gridView.style.display = 'none';
            } else {
                listView.style.display = 'none';
                gridView.style.display = 'block';
            }
        }
        
        function toggleTableSelection(tableName, checkbox) {
            if (checkbox.checked) {
                if (!selectedTables.includes(tableName)) {
                    selectedTables.push(tableName);
                }
            } else {
                selectedTables = selectedTables.filter(t => t !== tableName);
            }
            updateSelectedTablesInfo();
        }
        
        function selectAllTables() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
                const tableName = checkbox.closest('[data-table]').getAttribute('data-table');
                if (tableName) {
                    toggleTableSelection(tableName, checkbox);
                }
            });
        }
        
        function updateSelectedTablesInfo() {
            const info = document.getElementById('selected-tables-info');
            const count = document.getElementById('selected-count');
            
            if (selectedTables.length > 0) {
                info.style.display = 'block';
                count.textContent = `${selectedTables.length} table${selectedTables.length === 1 ? '' : 's'} selected`;
            } else {
                info.style.display = 'none';
            }
        }
        
        function selectTable(database, table, element) {
            currentTable = table;
            
            // Highlight selected table
            document.querySelectorAll('.table-item, .table-card').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            
            // Load table data
            loadTableData(database, table);
            
            // Update query input
            document.getElementById('query-input').value = `SELECT * FROM \`${database}\`.\`${table}\` LIMIT ${rowsPerPage};`;
        }
        
        function loadTableData(database, table) {
            const tableDataDiv = document.getElementById('table-data');
            const tableContent = document.getElementById('table-content');
            const tableTitle = document.getElementById('table-title');
            
            tableDataDiv.style.display = 'block';
            tableTitle.innerHTML = `<i class="fas fa-table"></i> Table: ${table}`;
            tableContent.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i><br>Loading table data...</div>';
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'get_table_data');
            formData.append('database', database);
            formData.append('table', table);
            formData.append('limit', rowsPerPage);
            formData.append('offset', currentPage * rowsPerPage);
            formData.append('orderBy', currentOrderBy);
            formData.append('orderDir', currentOrderDir);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                displayTableData(result, database, table);
            })
            .catch(error => {
                console.error('Error:', error);
                tableContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error loading table data</div>';
            });
        }
        
        function displayTableData(result, database, table) {
            const tableContent = document.getElementById('table-content');
            
            if (result.success && result.data) {
                let html = `
                    <div class="controls-panel">
                        <div class="btn-group">
                            <button class="btn btn-success btn-small" onclick="exportSingleTable('${database}', '${table}', 'sql')">
                                <i class="fas fa-download"></i> Export SQL
                            </button>
                            <button class="btn btn-success btn-small" onclick="exportSingleTable('${database}', '${table}', 'csv')">
                                <i class="fas fa-download"></i> Export CSV
                            </button>
                            <button class="btn btn-info btn-small" onclick="showTableStructure('${database}', '${table}')">
                                <i class="fas fa-sitemap"></i> Structure
                            </button>
                            <button class="btn btn-warning btn-small" onclick="addNewRecord('${database}', '${table}')">
                                <i class="fas fa-plus"></i> Add Record
                            </button>
                        </div>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number">${result.total}</div>
                                <div class="stat-label">Total Rows</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">${result.columns.length}</div>
                                <div class="stat-label">Columns</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">${currentPage + 1}</div>
                                <div class="stat-label">Page</div>
                            </div>
                        </div>
                    </div>`;
                
                if (result.data.length > 0) {
                    if (currentView === 'table') {
                        html += `
                            <div class="table-container">
                                <table id="data-table">
                                    <thead>
                                        <tr>`;
                        
                        result.columns.forEach(column => {
                            html += `<th onclick="sortTableData('${column}')" class="${currentOrderBy === column ? 'sorted' : ''}">
                                ${column}
                                <i class="fas fa-sort${currentOrderBy === column ? (currentOrderDir === 'ASC' ? '-up' : '-down') : ''} sort-icon"></i>
                            </th>`;
                        });
                        
                        html += `</tr></thead><tbody>`;
                        
                        result.data.forEach((row, index) => {
                            html += '<tr>';
                            Object.values(row).forEach(value => {
                                const displayValue = value === null ? '<em style="color: #718096;">NULL</em>' : 
                                                  (value === '' ? '<em style="color: #718096;">empty</em>' : 
                                                  String(value).length > 100 ? String(value).substring(0, 100) + '...' : 
                                                  String(value));
                                html += `<td>${displayValue}</td>`;
                            });
                            html += '</tr>';
                        });
                        
                        html += `</tbody></table></div>`;
                    } else if (currentView === 'cards') {
                        html += '<div class="table-grid-view">';
                        result.data.forEach((row, index) => {
                            html += `<div class="table-card">
                                <div class="table-card-header">
                                    <div class="table-card-title">Record #${(currentPage * rowsPerPage) + index + 1}</div>
                                </div>
                                <div style="display: grid; gap: 10px;">`;
                            
                            result.columns.forEach(column => {
                                const value = row[column];
                                const displayValue = value === null ? 'NULL' : (value === '' ? 'empty' : String(value));
                                html += `<div style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid var(--border-color);">
                                    <strong>${column}:</strong>
                                    <span style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;">${displayValue}</span>
                                </div>`;
                            });
                            
                            html += `</div></div>`;
                        });
                        html += '</div>';
                    } else if (currentView === 'json') {
                        html += `<div style="background: #2d3748; color: #e2e8f0; padding: 20px; border-radius: 8px; overflow-x: auto;">
                            <pre><code class="language-json">${JSON.stringify(result.data, null, 2)}</code></pre>
                        </div>`;
                    }
                    
                    // Pagination
                    const totalPages = Math.ceil(result.total / rowsPerPage);
                    if (totalPages > 1) {
                        html += `<div class="pagination">
                            <button class="btn btn-secondary" onclick="goToPage(0)" ${currentPage === 0 ? 'disabled' : ''}>
                                <i class="fas fa-angle-double-left"></i>
                            </button>
                            <button class="btn btn-secondary" onclick="goToPage(${currentPage - 1})" ${currentPage === 0 ? 'disabled' : ''}>
                                <i class="fas fa-angle-left"></i>
                            </button>`;
                        
                        for (let i = Math.max(0, currentPage - 2); i <= Math.min(totalPages - 1, currentPage + 2); i++) {
                            html += `<button class="btn ${i === currentPage ? 'btn-primary' : 'btn-secondary'}" onclick="goToPage(${i})">
                                ${i + 1}
                            </button>`;
                        }
                        
                        html += `<button class="btn btn-secondary" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages - 1 ? 'disabled' : ''}>
                                <i class="fas fa-angle-right"></i>
                            </button>
                            <button class="btn btn-secondary" onclick="goToPage(${totalPages - 1})" ${currentPage === totalPages - 1 ? 'disabled' : ''}>
                                <i class="fas fa-angle-double-right"></i>
                            </button>
                        </div>`;
                    }
                    
                    html += `<p style="margin-top: 15px; color: #718096; text-align: center;">
                        Showing ${result.data.length} of ${result.total} rows
                        ${totalPages > 1 ? `(Page ${currentPage + 1} of ${totalPages})` : ''}
                    </p>`;
                } else {
                    html += '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No data found in this table.</div>';
                }
                
                tableContent.innerHTML = html;
                
                // Highlight syntax if JSON view
                if (currentView === 'json' && window.Prism) {
                    Prism.highlightAll();
                }
            } else {
                tableContent.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error: ${result.error || 'Failed to load table data'}</div>`;
            }
        }
        
        function sortTableData(column) {
            if (currentOrderBy === column) {
                currentOrderDir = currentOrderDir === 'ASC' ? 'DESC' : 'ASC';
            } else {
                currentOrderBy = column;
                currentOrderDir = 'ASC';
            }
            currentPage = 0;
            loadTableData(currentDatabase, currentTable);
        }
        
        function goToPage(page) {
            currentPage = page;
            loadTableData(currentDatabase, currentTable);
        }
        
        function changeRowsPerPage() {
            rowsPerPage = parseInt(document.getElementById('rows-per-page').value);
            currentPage = 0;
            if (currentTable) {
                loadTableData(currentDatabase, currentTable);
            }
        }
        
        function setDataView(view) {
            currentView = view;
            document.querySelectorAll('.controls-panel .view-mode').forEach(el => el.classList.remove('active'));
            document.querySelector(`.controls-panel [data-view="${view}"]`).classList.add('active');
            
            if (currentTable) {
                loadTableData(currentDatabase, currentTable);
            }
        }
        
        function showTableStructure(database, table) {
            const structureDiv = document.getElementById('table-structure');
            const structureContent = document.getElementById('structure-content');
            
            structureDiv.style.display = 'block';
            structureContent.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i><br>Loading table structure...</div>';
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'get_table_structure');
            formData.append('database', database);
            formData.append('table', table);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(structure => {
                if (structure && structure.length > 0) {
                    let html = `
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-tag"></i> Field</th>
                                        <th><i class="fas fa-cog"></i> Type</th>
                                        <th><i class="fas fa-question-circle"></i> Null</th>
                                        <th><i class="fas fa-key"></i> Key</th>
                                        <th><i class="fas fa-edit"></i> Default</th>
                                        <th><i class="fas fa-plus-circle"></i> Extra</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                    
                    structure.forEach(field => {
                        html += `
                            <tr>
                                <td><strong><i class="fas fa-column"></i> ${field.Field}</strong></td>
                                <td><span class="badge" style="background: var(--info-color); color: white; padding: 4px 8px; border-radius: 4px;">${field.Type}</span></td>
                                <td>${field.Null === 'YES' ? '<i class="fas fa-check" style="color: var(--success-color);"></i>' : '<i class="fas fa-times" style="color: var(--danger-color);"></i>'}</td>
                                <td>${field.Key ? '<i class="fas fa-key" style="color: var(--warning-color);"></i> ' + field.Key : ''}</td>
                                <td>${field.Default ? field.Default : '<em style="color: #718096;">NULL</em>'}</td>
                                <td>${field.Extra ? '<span class="badge" style="background: var(--secondary-color); color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">' + field.Extra + '</span>' : ''}</td>
                            </tr>`;
                    });
                    
                    html += `</tbody></table></div>`;
                    structureContent.innerHTML = html;
                } else {
                    structureContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error loading table structure</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                structureContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error loading table structure</div>';
            });
        }
        
        function searchTables() {
            const searchTerm = document.getElementById('table-search').value.toLowerCase();
            const tableItems = document.querySelectorAll('.table-item, .table-card');
            
            tableItems.forEach(item => {
                const tableName = item.getAttribute('data-table').toLowerCase();
                if (tableName.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function exportSingleTable(database, table, format) {
            const form = document.createElement('form');
            form.method = 'post';
            form.innerHTML = `
                <input type="hidden" name="action" value="export">
                <input type="hidden" name="database" value="${database}">
                <input type="hidden" name="table" value="${table}">
                <input type="hidden" name="format" value="${format}">
            `;
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
        
        function exportSelectedTables(format) {
            if (selectedTables.length === 0) {
                alert('Please select at least one table to export.');
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'post';
            
            let html = `
                <input type="hidden" name="action" value="export">
                <input type="hidden" name="database" value="${currentDatabase}">
                <input type="hidden" name="format" value="${format}">
            `;
            
            selectedTables.forEach(table => {
                html += `<input type="hidden" name="tables[]" value="${table}">`;
            });
            
            form.innerHTML = html;
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
        
        function insertTemplate(type) {
            const queryInput = document.getElementById('query-input');
            let template = '';
            
            switch(type) {
                case 'select':
                    template = currentTable ? 
                        `SELECT * FROM \`${currentDatabase}\`.\`${currentTable}\` WHERE 1 ORDER BY id LIMIT ${rowsPerPage};` :
                        'SELECT * FROM database.table_name WHERE condition ORDER BY column LIMIT 100;';
                    break;
                case 'insert':
                    template = currentTable ?
                        `INSERT INTO \`${currentDatabase}\`.\`${currentTable}\` (column1, column2, column3) VALUES ('value1', 'value2', 'value3');` :
                        'INSERT INTO database.table_name (column1, column2) VALUES (\'value1\', \'value2\');';
                    break;
                case 'update':
                    template = currentTable ?
                        `UPDATE \`${currentDatabase}\`.\`${currentTable}\` SET column1 = 'new_value' WHERE id = 1;` :
                        'UPDATE database.table_name SET column1 = \'new_value\' WHERE condition;';
                    break;
                case 'delete':
                    template = currentTable ?
                        `DELETE FROM \`${currentDatabase}\`.\`${currentTable}\` WHERE id = 1;` :
                        'DELETE FROM database.table_name WHERE condition;';
                    break;
                case 'create':
                    template = `CREATE TABLE \`${currentDatabase}\`.new_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);`;
                    break;
                case 'alter':
                    template = currentTable ?
                        `ALTER TABLE \`${currentDatabase}\`.\`${currentTable}\` ADD COLUMN new_column VARCHAR(255) AFTER existing_column;` :
                        'ALTER TABLE database.table_name ADD COLUMN new_column VARCHAR(255);';
                    break;
            }
            
            queryInput.value = template;
            queryInput.focus();
        }
        
        function clearQuery() {
            document.getElementById('query-input').value = '';
            document.getElementById('query-input').focus();
        }
        
        function formatQuery() {
            const queryInput = document.getElementById('query-input');
            let query = queryInput.value;
            
            // Basic SQL formatting
            query = query.replace(/\bSELECT\b/gi, '\nSELECT\n  ');
            query = query.replace(/\bFROM\b/gi, '\nFROM\n  ');
            query = query.replace(/\bWHERE\b/gi, '\nWHERE\n  ');
            query = query.replace(/\bORDER BY\b/gi, '\nORDER BY\n  ');
            query = query.replace(/\bGROUP BY\b/gi, '\nGROUP BY\n  ');
            query = query.replace(/\bHAVING\b/gi, '\nHAVING\n  ');
            query = query.replace(/\bLIMIT\b/gi, '\nLIMIT ');
            query = query.replace(/,/g, ',\n  ');
            
            queryInput.value = query.trim();
        }
        
        function refreshData() {
            if (currentTable) {
                loadTableData(currentDatabase, currentTable);
            }
        }
        
        // Modal functions
        function showExportModal() {
            document.getElementById('export-modal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function initializeExportModal() {
            const exportCards = document.querySelectorAll('.export-card');
            exportCards.forEach(card => {
                card.addEventListener('click', function() {
                    exportCards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                });
            });
        }
        
        function performExport() {
            const selectedCard = document.querySelector('.export-card.selected');
            if (!selectedCard) {
                alert('Please select an export format.');
                return;
            }
            
            const format = selectedCard.getAttribute('data-format');
            
            if (selectedTables.length > 0) {
                exportSelectedTables(format);
            } else if (currentTable) {
                exportSingleTable(currentDatabase, currentTable, format);
            } else {
                alert('Please select a table to export.');
                return;
            }
            
            closeModal('export-modal');
        }
        
        // Additional utility functions
        function showHelp() {
            alert('DataHub Pro Help:\n\n' +
                  'Keyboard Shortcuts:\n' +
                  '• Ctrl+Enter: Execute query\n' +
                  '• Ctrl+K: Clear query\n' +
                  '• F5: Refresh data\n\n' +
                  'Features:\n' +
                  '• Multiple table selection\n' +
                  '• Export in SQL/CSV/JSON formats\n' +
                  '• Table and card views\n' +
                  '• Advanced search and filtering\n' +
                  '• Query templates and formatting');
        }
        
        function showSettings() {
            alert('Settings panel will be implemented in future versions.');
        }
        
        function showQueryBuilder() {
            alert('Visual query builder will be implemented in future versions.');
        }
        
        function showImportModal() {
            alert('Import functionality will be implemented in future versions.');
        }
        
        function showQueryHistory() {
            alert('Query history will be implemented in future versions.');
        }
        
        function saveQuery() {
            const query = document.getElementById('query-input').value;
            if (query.trim()) {
                const name = prompt('Enter a name for this query:');
                if (name) {
                    // Save to localStorage (in a real app, this would be saved to server)
                    localStorage.setItem('saved_query_' + name, query);
                    alert('Query saved successfully!');
                }
            }
        }
        
        function addNewRecord(database, table) {
            alert('Add new record functionality will be implemented in future versions.');
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                const queryForm = document.querySelector('form[method="post"]');
                if (queryForm && document.getElementById('query-input').value.trim()) {
                    queryForm.submit();
                }
            }
            
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                clearQuery();
            }
            
            if (e.key === 'F5') {
                e.preventDefault();
                refreshData();
            }
        });
        
        // Auto-save query in localStorage
        document.getElementById('query-input').addEventListener('input', function() {
            localStorage.setItem('current_query', this.value);
        });
        
        // Restore query from localStorage
        window.addEventListener('load', function() {
            const savedQuery = localStorage.getItem('current_query');
            if (savedQuery) {
                document.getElementById('query-input').value = savedQuery;
            }
        });
    </script>
</body>
</html>
