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

// Handle AJAX requests first
if ($_POST && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if (isset($_SESSION['db_connection'])) {
        $conn = $_SESSION['db_connection'];
        $dbManager->connect($conn['host'], $conn['username'], $conn['password'], $conn['database'], $conn['port']);
        
        switch ($_POST['action']) {
            case 'get_tables':
                echo json_encode($dbManager->getTables($_POST['database']));
                break;
                
            case 'get_table_data':
                echo json_encode($dbManager->getTableData($_POST['database'], $_POST['table']));
                break;
                
            case 'get_table_structure':
                echo json_encode($dbManager->getTableStructure($_POST['database'], $_POST['table']));
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
            margin: 2px;
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
        
        .btn-secondary:hover {
            background: #4a5568;
        }
        
        .btn-danger {
            background: #e53e3e;
            color: white;
        }
        
        .btn-success {
            background: #38a169;
            color: white;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .layout {
            display: flex;
            gap: 20px;
        }
        
        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            height: fit-content;
            min-width: 250px;
        }
        
        .main-content {
            flex: 1;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-height: 500px;
            overflow-y: auto;
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
            word-break: break-word;
            max-width: 300px;
        }
        
        th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
            position: sticky;
            top: 0;
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
            max-height: 300px;
            overflow-y: auto;
        }
        
        .db-list li, .table-list li {
            padding: 10px 12px;
            border-radius: 6px;
            margin-bottom: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .db-list li:hover, .table-list li:hover {
            background: #e2e8f0;
        }
        
        .db-list li.active, .table-list li.active {
            background: #667eea;
            color: white;
            border-color: #4c63d2;
        }
        
        .table-list {
            list-style: none;
            max-height: 400px;
            overflow-y: auto;
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
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #718096;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 5px;
        }
        
        @media (max-width: 768px) {
            .layout {
                flex-direction: column;
            }
            
            .grid {
                grid-template-columns: 1fr;
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
                    <button type="submit" class="btn btn-danger btn-small">Disconnect</button>
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
            <div class="layout">
                <div class="sidebar">
                    <div class="section-title">Databases</div>
                    <ul class="db-list" id="database-list">
                        <?php
                        $databases = $dbManager->getDatabases();
                        foreach ($databases as $db):
                        ?>
                            <li onclick="selectDatabase('<?php echo htmlspecialchars($db); ?>')" data-database="<?php echo htmlspecialchars($db); ?>">
                                <?php echo htmlspecialchars($db); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div id="tables-section" style="display: none;">
                        <div class="section-title">Tables</div>
                        <ul id="tables-list" class="table-list"></ul>
                    </div>
                </div>
                
                <div class="main-content">
                    <div class="card">
                        <h3>SQL Query Editor</h3>
                        <form method="post">
                            <input type="hidden" name="action" value="query">
                            <div class="form-group">
                                <textarea name="query" class="form-control query-editor" placeholder="Enter your SQL query here..." id="query-input"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Execute Query</button>
                            <button type="button" class="btn btn-secondary" onclick="clearQuery()">Clear</button>
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
                        <h3 id="table-title">Table Data</h3>
                        <div id="table-content"></div>
                    </div>
                    
                    <div id="table-structure" class="card" style="display: none;">
                        <h3>Table Structure</h3>
                        <div id="structure-content"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        let currentDatabase = '';
        let currentTable = '';
        
        function selectDatabase(database) {
            currentDatabase = database;
            
            // Highlight selected database
            document.querySelectorAll('.db-list li').forEach(li => li.classList.remove('active'));
            document.querySelector(`[data-database="${database}"]`).classList.add('active');
            
            // Load tables for this database
            loadTables(database);
            
            // Hide table data
            document.getElementById('table-data').style.display = 'none';
            document.getElementById('table-structure').style.display = 'none';
        }
        
        function loadTables(database) {
            const tablesSection = document.getElementById('tables-section');
            const tablesList = document.getElementById('tables-list');
            
            tablesSection.style.display = 'block';
            tablesList.innerHTML = '<li class="loading">Loading tables...</li>';
            
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
                tablesList.innerHTML = '';
                
                if (tables.length > 0) {
                    tables.forEach(table => {
                        const li = document.createElement('li');
                        li.textContent = table;
                        li.setAttribute('data-table', table);
                        li.onclick = () => selectTable(database, table, li);
                        tablesList.appendChild(li);
                    });
                } else {
                    tablesList.innerHTML = '<li style="color: #718096;">No tables found</li>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                tablesList.innerHTML = '<li style="color: #e53e3e;">Error loading tables</li>';
            });
        }
        
        function selectTable(database, table, element) {
            currentTable = table;
            
            // Highlight selected table
            document.querySelectorAll('.table-list li').forEach(li => li.classList.remove('active'));
            element.classList.add('active');
            
            // Load table data
            loadTableData(database, table);
            
            // Update query input with SELECT statement
            document.getElementById('query-input').value = `SELECT * FROM \`${database}\`.\`${table}\` LIMIT 100;`;
        }
        
        function loadTableData(database, table) {
            const tableDataDiv = document.getElementById('table-data');
            const tableContent = document.getElementById('table-content');
            const tableTitle = document.getElementById('table-title');
            
            tableDataDiv.style.display = 'block';
            tableTitle.textContent = `Table: ${table}`;
            tableContent.innerHTML = '<div class="loading">Loading table data...</div>';
            
            // Make AJAX request for table data
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'get_table_data');
            formData.append('database', database);
            formData.append('table', table);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success && result.data) {
                    let html = `
                        <div style="margin-bottom: 15px;">
                            <button class="btn btn-secondary btn-small" onclick="exportTable('${database}', '${table}', 'sql')">Export SQL</button>
                            <button class="btn btn-secondary btn-small" onclick="exportTable('${database}', '${table}', 'csv')">Export CSV</button>
                            <button class="btn btn-secondary btn-small" onclick="showTableStructure('${database}', '${table}')">View Structure</button>
                        </div>`;
                    
                    if (result.data.length > 0) {
                        html += `
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
                                const displayValue = value === null ? '<em>NULL</em>' : (value === '' ? '<em>empty</em>' : String(value));
                                html += `<td>${displayValue}</td>`;
                            });
                            html += '</tr>';
                        });
                        
                        html += `</tbody></table></div>`;
                        html += `<p style="margin-top: 10px; color: #718096;">Showing ${result.data.length} of ${result.total} rows</p>`;
                    } else {
                        html += '<p style="color: #718096;">No data found in this table.</p>';
                    }
                    
                    tableContent.innerHTML = html;
                } else {
                    tableContent.innerHTML = `<div class="alert alert-danger">Error: ${result.error || 'Failed to load table data'}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                tableContent.innerHTML = '<div class="alert alert-danger">Error loading table data</div>';
            });
        }
        
        function showTableStructure(database, table) {
            const structureDiv = document.getElementById('table-structure');
            const structureContent = document.getElementById('structure-content');
            
            structureDiv.style.display = 'block';
            structureContent.innerHTML = '<div class="loading">Loading table structure...</div>';
            
            // Make AJAX request for table structure
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
                                        <th>Field</th>
                                        <th>Type</th>
                                        <th>Null</th>
                                        <th>Key</th>
                                        <th>Default</th>
                                        <th>Extra</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                    
                    structure.forEach(field => {
                        html += `
                            <tr>
                                <td><strong>${field.Field}</strong></td>
                                <td>${field.Type}</td>
                                <td>${field.Null}</td>
                                <td>${field.Key || ''}</td>
                                <td>${field.Default || '<em>NULL</em>'}</td>
                                <td>${field.Extra || ''}</td>
                            </tr>`;
                    });
                    
                    html += `</tbody></table></div>`;
                    structureContent.innerHTML = html;
                } else {
                    structureContent.innerHTML = '<div class="alert alert-danger">Error loading table structure</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                structureContent.innerHTML = '<div class="alert alert-danger">Error loading table structure</div>';
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
        
        function clearQuery() {
            document.getElementById('query-input').value = '';
        }
        
        // Auto-select first database on load
        document.addEventListener('DOMContentLoaded', function() {
            const firstDb = document.querySelector('.db-list li');
            if (firstDb) {
                const dbName = firstDb.getAttribute('data-database');
                if (dbName) {
                    selectDatabase(dbName);
                }
            }
        });
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+Enter to execute query
            if (e.ctrlKey && e.key === 'Enter') {
                const queryForm = document.querySelector('form[method="post"]');
                if (queryForm && document.getElementById('query-input').value.trim()) {
                    queryForm.submit();
                }
            }
            
            // Ctrl+K to clear query
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                clearQuery();
                document.getElementById('query-input').focus();
            }
        });
        
        // Add query templates
        function insertQueryTemplate(type) {
            const queryInput = document.getElementById('query-input');
            let template = '';
            
            switch(type) {
                case 'select':
                    template = currentTable ? 
                        `SELECT * FROM \`${currentDatabase}\`.\`${currentTable}\` WHERE 1 LIMIT 100;` :
                        'SELECT * FROM table_name WHERE condition LIMIT 100;';
                    break;
                case 'insert':
                    template = currentTable ?
                        `INSERT INTO \`${currentDatabase}\`.\`${currentTable}\` (column1, column2) VALUES ('value1', 'value2');` :
                        'INSERT INTO table_name (column1, column2) VALUES (\'value1\', \'value2\');';
                    break;
                case 'update':
                    template = currentTable ?
                        `UPDATE \`${currentDatabase}\`.\`${currentTable}\` SET column1 = 'new_value' WHERE condition;` :
                        'UPDATE table_name SET column1 = \'new_value\' WHERE condition;';
                    break;
                case 'delete':
                    template = currentTable ?
                        `DELETE FROM \`${currentDatabase}\`.\`${currentTable}\` WHERE condition;` :
                        'DELETE FROM table_name WHERE condition;';
                    break;
            }
            
            queryInput.value = template;
            queryInput.focus();
        }
    </script>
</body>
</html>
