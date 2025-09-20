<?php
/**
 * Custom Database Management Tool
 * A comprehensive database administration interface
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
            'version' => '2.1.0',
            'theme' => 'modern'
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
            
            if (stripos(trim($query), 'SELECT') === 0 || stripos(trim($query), 'SHOW') === 0) {
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
    
    public function getTableData($database, $table, $limit = 100, $offset = 0) {
        try {
            $countStmt = $this->connection->query("SELECT COUNT(*) FROM `$database`.`$table`");
            $totalRows = $countStmt->fetchColumn();
            
            $stmt = $this->connection->query("SELECT * FROM `$database`.`$table` LIMIT $limit OFFSET $offset");
            
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
            $sql = "-- Export for table: $table\n\n";
            
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
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }
            
            return $csv;
        } catch (PDOException $e) {
            return false;
        }
    }
}

// Initialize the database manager
$dbManager = new DatabaseManager();

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
                    $exportData = $dbManager->exportTable($_POST['database'], $_POST['table'], $_POST['format']);
                    
                    if ($exportData) {
                        $filename = $_POST['table'] . '.' . $_POST['format'];
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
    <title>DataHub Pro - Database Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            color: #4a5568;
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .header .version {
            color: #718096;
            font-size: 0.9rem;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #4a5568;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #718096;
            color: white;
        }
        
        .btn-danger {
            background: #e53e3e;
            color: white;
        }
        
        .btn-success {
            background: #38a169;
            color: white;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            height: fit-content;
        }
        
        .main-content {
            flex: 1;
            margin-left: 20px;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }
        
        tr:hover {
            background: #f7fafc;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .alert-danger {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .query-editor {
            min-height: 200px;
            font-family: 'Consolas', 'Monaco', monospace;
            resize: vertical;
        }
        
        .db-list {
            list-style: none;
        }
        
        .db-list li {
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .db-list li:hover {
            background: #e2e8f0;
        }
        
        .db-list li.active {
            background: #667eea;
            color: white;
        }
        
        .connection-status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .connection-status.connected {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .connection-status.disconnected {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        .status-dot.connected {
            background: #38a169;
        }
        
        .status-dot.disconnected {
            background: #e53e3e;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                margin-left: 0;
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>DataHub Pro</h1>
            <div class="version">Version 2.1.0 - Advanced Database Management System</div>
        </div>
        
        <div class="connection-status <?php echo $isConnected ? 'connected' : 'disconnected'; ?>">
            <div class="status-dot <?php echo $isConnected ? 'connected' : 'disconnected'; ?>"></div>
            <span><?php echo $isConnected ? 'Connected to database' : 'Not connected'; ?></span>
            <?php if ($isConnected): ?>
                <form method="post" style="margin-left: auto;">
                    <input type="hidden" name="action" value="disconnect">
                    <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Disconnect</button>
                </form>
            <?php endif; ?>
        </div>
        
        <?php if (!$isConnected): ?>
            <div class="card">
                <h2>Database Connection</h2>
                <form method="post">
                    <input type="hidden" name="action" value="connect">
                    <div class="grid">
                        <div class="form-group">
                            <label for="host">Host</label>
                            <input type="text" id="host" name="host" class="form-control" value="localhost" required>
                        </div>
                        <div class="form-group">
                            <label for="port">Port</label>
                            <input type="number" id="port" name="port" class="form-control" value="3306">
                        </div>
                    </div>
                    <div class="grid">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="database">Database (Optional)</label>
                        <input type="text" id="database" name="database" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Connect</button>
                </form>
            </div>
        <?php else: ?>
            <div style="display: flex; gap: 20px;">
                <div class="sidebar">
                    <h3>Databases</h3>
                    <ul class="db-list">
                        <?php
                        $databases = $dbManager->getDatabases();
                        foreach ($databases as $db):
                        ?>
                            <li onclick="selectDatabase('<?php echo htmlspecialchars($db); ?>')"><?php echo htmlspecialchars($db); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div id="tables-section" style="display: none;">
                        <h3>Tables</h3>
                        <ul id="tables-list" class="db-list"></ul>
                    </div>
                </div>
                
                <div class="main-content">
                    <div class="card">
                        <h3>SQL Query Editor</h3>
                        <form method="post">
                            <input type="hidden" name="action" value="query">
                            <div class="form-group">
                                <textarea name="query" class="form-control query-editor" placeholder="Enter your SQL query here..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Execute Query</button>
                        </form>
                    </div>
                    
                    <?php if (isset($queryResult)): ?>
                        <div class="card">
                            <?php if ($queryResult['success']): ?>
                                <div class="alert alert-success">
                                    <?php if (isset($queryResult['data'])): ?>
                                        Query executed successfully. <?php echo count($queryResult['data']); ?> rows returned.
                                    <?php else: ?>
                                        <?php echo $queryResult['message']; ?> (<?php echo $queryResult['affected']; ?> rows affected)
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (isset($queryResult['data']) && !empty($queryResult['data'])): ?>
                                    <div class="table-container">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <?php foreach ($queryResult['columns'] as $column): ?>
                                                        <th><?php echo htmlspecialchars($column); ?></th>
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
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    Error: <?php echo htmlspecialchars($queryResult['error']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div id="table-data" class="card" style="display: none;">
                        <h3>Table Data</h3>
                        <div id="table-content"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function selectDatabase(database) {
            // Highlight selected database
            document.querySelectorAll('.db-list li').forEach(li => li.classList.remove('active'));
            event.target.classList.add('active');
            
            // Load tables for this database
            loadTables(database);
        }
        
        function loadTables(database) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_tables&database=' + encodeURIComponent(database)
            })
            .then(response => response.json())
            .then(tables => {
                const tablesSection = document.getElementById('tables-section');
                const tablesList = document.getElementById('tables-list');
                
                tablesSection.style.display = 'block';
                tablesList.innerHTML = '';
                
                tables.forEach(table => {
                    const li = document.createElement('li');
                    li.textContent = table;
                    li.onclick = () => loadTableData(database, table);
                    tablesList.appendChild(li);
                });
            });
        }
        
        function loadTableData(database, table) {
            const tableDataDiv = document.getElementById('table-data');
            const tableContent = document.getElementById('table-content');
            
            tableDataDiv.style.display = 'block';
            tableContent.innerHTML = '<p>Loading...</p>';
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_table_data&database=' + encodeURIComponent(database) + '&table=' + encodeURIComponent(table)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    let html = `
                        <div style="margin-bottom: 15px;">
                            <button class="btn btn-secondary" onclick="exportTable('${database}', '${table}', 'sql')">Export SQL</button>
                            <button class="btn btn-secondary" onclick="exportTable('${database}', '${table}', 'csv')">Export CSV</button>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>`;
                    
                    result.columns.forEach(column => {
                        html += `<th>${column}</th>`;
                    });
                    
                    html += `</tr></thead><tbody>`;
                    
                    result.data.forEach(row => {
                        html += '<tr>';
                        Object.values(row).forEach(value => {
                            html += `<td>${value || 'NULL'}</td>`;
                        });
                        html += '</tr>';
                    });
                    
                    html += `</tbody></table></div>`;
                    html += `<p>Showing ${result.data.length} of ${result.total} rows</p>`;
                    
                    tableContent.innerHTML = html;
                } else {
                    tableContent.innerHTML = `<div class="alert alert-danger">Error: ${result.error}</div>`;
                }
            });
        }
        
        function exportTable(database, table, format) {
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
    </script>
</body>
</html>

<?php
// Handle AJAX requests
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'get_tables') {
        header('Content-Type: application/json');
        $conn = $_SESSION['db_connection'];
        $dbManager->connect($conn['host'], $conn['username'], $conn['password'], $conn['database'], $conn['port']);
        echo json_encode($dbManager->getTables($_POST['database']));
        exit;
    }
    
    if ($_POST['action'] === 'get_table_data') {
        header('Content-Type: application/json');
        $conn = $_SESSION['db_connection'];
        $dbManager->connect($conn['host'], $conn['username'], $conn['password'], $conn['database'], $conn['port']);
        echo json_encode($dbManager->getTableData($_POST['database'], $_POST['table']));
        exit;
    }
}
?>
