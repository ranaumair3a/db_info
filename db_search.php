<?php  
require_once 'config_seci.php';  
date_default_timezone_set('Asia/Karachi');  

// Handle download request
if (isset($_GET['download']) && isset($_GET['table'])) {
    $tableName = mysqli_real_escape_string($con, $_GET['table']);
    $format = $_GET['format'] ?? 'csv';
    
    // Verify table exists
    $checkQuery = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$db}' AND TABLE_NAME = '{$tableName}'";
    $checkResult = mysqli_query($con, $checkQuery);
    $tableExists = mysqli_fetch_assoc($checkResult)['count'] > 0;
    
    if ($tableExists) {
        $dataQuery = "SELECT * FROM `{$tableName}`";
        $dataResult = mysqli_query($con, $dataQuery);
        
        if ($dataResult) {
            $filename = $tableName . '_' . date('Y-m-d_H-i-s');
            
            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                
                $output = fopen('php://output', 'w');
                
                // Get column names
                $fields = mysqli_fetch_fields($dataResult);
                $headers = array_map(function($field) { return $field->name; }, $fields);
                fputcsv($output, $headers);
                
                // Output data
                while ($row = mysqli_fetch_assoc($dataResult)) {
                    fputcsv($output, $row);
                }
                fclose($output);
                exit;
                
            } elseif ($format === 'json') {
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="' . $filename . '.json"');
                
                $data = [];
                while ($row = mysqli_fetch_assoc($dataResult)) {
                    $data[] = $row;
                }
                echo json_encode($data, JSON_PRETTY_PRINT);
                exit;
                
            } elseif ($format === 'sql') {
                header('Content-Type: text/plain');
                header('Content-Disposition: attachment; filename="' . $filename . '.sql"');
                
                echo "-- SQL Export for table: {$tableName}\n";
                echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
                
                // Get CREATE TABLE statement
                $createQuery = "SHOW CREATE TABLE `{$tableName}`";
                $createResult = mysqli_query($con, $createQuery);
                if ($createRow = mysqli_fetch_assoc($createResult)) {
                    echo $createRow['Create Table'] . ";\n\n";
                }
                
                // Get INSERT statements
                mysqli_data_seek($dataResult, 0);
                while ($row = mysqli_fetch_assoc($dataResult)) {
                    $values = array_map(function($val) use ($con) {
                        return $val === null ? 'NULL' : "'" . mysqli_real_escape_string($con, $val) . "'";
                    }, array_values($row));
                    
                    echo "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
                }
                exit;
            }
        }
    }
    exit;
}

// Read limit from GET (default: 25)  
$allowedLimits = ['all', '5', '10', '25', '50', '100'];  
$limitParam = isset($_GET['limit']) ? strtolower(trim($_GET['limit'])) : '25';  
if (!in_array($limitParam, $allowedLimits)) $limitParam = '25';  
  
$limitSql = ($limitParam === 'all') ? '' : 'LIMIT ' . intval($limitParam);  
  
// Fetch tables with additional row details
$query = "  
    SELECT   
        t.TABLE_NAME,  
        t.ENGINE,  
        t.CREATE_TIME,  
        t.UPDATE_TIME,  
        t.TABLE_ROWS,  
        t.DATA_LENGTH,  
        t.INDEX_LENGTH,  
        t.TABLE_COLLATION,
        t.TABLE_COMMENT
    FROM INFORMATION_SCHEMA.TABLES t
    WHERE t.TABLE_SCHEMA = '{$db}'  
    ORDER BY t.CREATE_TIME DESC, t.TABLE_NAME ASC  
    {$limitSql}  
";  
  
$result = mysqli_query($con, $query);  
$tables = [];  
$error = '';  
  
if ($result) {  
    while ($row = mysqli_fetch_assoc($result)) {
        // Get sample of recent rows to check creation patterns
        $tableName = $row['TABLE_NAME'];
        $sampleQuery = "SELECT COUNT(*) as total_rows FROM `{$tableName}`";
        $sampleResult = mysqli_query($con, $sampleQuery);
        $totalRows = mysqli_fetch_assoc($sampleResult)['total_rows'] ?? 0;
        
        // Try to get creation date from primary key or timestamp columns
        $columnsQuery = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_SCHEMA = '{$db}' AND TABLE_NAME = '{$tableName}' 
                        AND (DATA_TYPE LIKE '%timestamp%' OR DATA_TYPE LIKE '%datetime%' OR COLUMN_KEY = 'PRI')
                        ORDER BY ORDINAL_POSITION LIMIT 5";
        $columnsResult = mysqli_query($con, $columnsQuery);
        $timeColumns = [];
        while ($col = mysqli_fetch_assoc($columnsResult)) {
            $timeColumns[] = $col;
        }
        
        $row['ACTUAL_ROWS'] = $totalRows;
        $row['TIME_COLUMNS'] = $timeColumns;
        $tables[] = $row;  
    }  
} else {  
    $error = "Database Error: " . htmlspecialchars(mysqli_error($con));  
}  
  
// Helper for size formatting  
function formatBytes($bytes) {  
    if ($bytes === null) return '—';  
    $bytes = (float)$bytes;  
    $units = ['B','KB','MB','GB','TB'];  
    $i = 0;  
    while ($bytes >= 1024 && $i < count($units)-1) {  
        $bytes /= 1024;  
        $i++;  
    }  
    return number_format($bytes, 2) . ' ' . $units[$i];  
}  
?><!DOCTYPE html>  
<html lang="en">  
<head>  
<meta charset="UTF-8" />  
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>  
<title>Database Tables • Download & Analytics</title>  
<script src="https://cdn.tailwindcss.com"></script>  
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">  
<style>  
  .gradient-bg{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);}  
  .glass{backdrop-filter:blur(10px);background:rgba(255,255,255,.92);border:1px solid rgba(255,255,255,.25)}  
  .table-row:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.08)}  
  .sticky-th{position:sticky;top:0;z-index:10}
  .download-dropdown {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    z-index: 20;
    min-width: 160px;
  }
  .download-btn:hover .download-dropdown {
    display: block;
  }
</style>  
</head>  
<body class="gradient-bg min-h-screen">  
  <div class="container mx-auto px-4 py-8">  
    <!-- Header -->  
    <div class="text-center mb-8">  
      <h1 class="text-4xl font-bold text-white mb-2">  
        <i class="fa-solid fa-database mr-3"></i>Database Tables Manager
      </h1>  
      <p class="text-blue-100 text-lg">View, analyze & download tables from <span class="font-semibold"><?= htmlspecialchars($db) ?></span></p>  
    </div>  

    <!-- Controls -->  
    <div class="max-w-6xl mx-auto mb-6">  
      <div class="glass rounded-xl p-5 shadow-xl">  
        <form method="GET" class="flex flex-col md:flex-row md:items-center gap-4">  
          <div class="flex-1 relative">  
            <input id="filterInput" type="text" placeholder="Filter tables by name..."  
              class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-300">  
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-gray-400"></i>  
          </div>  
          <div class="flex items-center gap-3">  
            <label class="text-gray-700 font-medium"><i class="fa-regular fa-square-check mr-2"></i>Show</label>  
            <select name="limit" class="px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-blue-500">  
              <?php foreach($allowedLimits as $opt): ?>  
                <option value="<?= $opt ?>" <?= $opt===$limitParam?'selected':'' ?>>  
                  <?= strtoupper($opt)==='ALL'?'All Tables':$opt.' Tables' ?>  
                </option>  
              <?php endforeach; ?>  
            </select>  
            <button class="px-5 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold rounded-lg hover:from-blue-600 hover:to-purple-700 transform hover:scale-105 transition-all duration-300 shadow-lg">  
              <i class="fa-solid fa-rotate mr-2"></i>Apply  
            </button>  
          </div>  
        </form>  
      </div>  
    </div>  

    <!-- Error -->  
    <?php if($error): ?>  
      <div class="max-w-6xl mx-auto mb-6">  
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-r-lg shadow-md">  
          <div class="flex items-center">  
            <i class="fas fa-exclamation-triangle mr-3 text-xl"></i>  
            <strong>Error:</strong>&nbsp;<?= $error ?>  
          </div>  
        </div>  
      </div>  
    <?php endif; ?>  

    <!-- Stats -->  
    <div class="max-w-6xl mx-auto mb-4">  
      <div class="glass rounded-xl p-4 flex items-center justify-between text-sm md:text-base">  
        <div class="flex flex-wrap items-center gap-3">  
          <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-semibold">  
            <?= count($tables) ?> table<?= count($tables)==1?'':'s' ?>  
          </span>  
          <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full font-semibold">  
            Showing: <?= strtoupper($limitParam)==='ALL'?'All':intval($limitParam) ?>  
          </span>
          <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full font-semibold">
            <i class="fa-solid fa-download mr-1"></i>Download Available
          </span>
        </div>  
        <div class="text-gray-500">  
          <i class="fa-regular fa-clock mr-2"></i><?= date('Y-m-d H:i:s') ?>  
        </div>  
      </div>  
    </div>  

    <!-- Results -->  
    <div class="max-w-7xl mx-auto glass rounded-xl shadow-xl overflow-hidden">  
      <div class="max-h-[75vh] overflow-y-auto">  
        <table class="min-w-full">  
          <thead class="bg-gradient-to-r from-gray-50 to-gray-100 sticky-th">  
            <tr>  
              <th class="py-4 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-b">Table Name</th>  
              <th class="py-4 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-b">Created</th>  
              <th class="py-4 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-b">Updated</th>  
              <th class="py-4 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-b">Rows</th>  
              <th class="py-4 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-b">Engine</th>  
              <th class="py-4 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-b">Size</th>  
              <th class="py-4 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-b">Actions</th>  
            </tr>  
          </thead>  
          <tbody id="tableBody" class="divide-y divide-gray-200">  
            <?php if(empty($tables)): ?>  
              <tr>  
                <td colspan="7" class="py-8 px-6 text-center text-gray-500">  
                  <i class="fa-regular fa-face-frown mr-2"></i>No tables found.  
                </td>  
              </tr>  
            <?php else: ?>  
              <?php foreach($tables as $i => $t):   
                $size = (float)($t['DATA_LENGTH'] ?? 0) + (float)($t['INDEX_LENGTH'] ?? 0);  
                $created = $t['CREATE_TIME'] ? date('M j, Y H:i', strtotime($t['CREATE_TIME'])) : '—';  
                $updated = $t['UPDATE_TIME'] ? date('M j, Y H:i', strtotime($t['UPDATE_TIME'])) : '—';  
                $estimatedRows = isset($t['TABLE_ROWS']) ? (int)$t['TABLE_ROWS'] : null;
                $actualRows = $t['ACTUAL_ROWS'] ?? 0;
                $tableName = $t['TABLE_NAME'];
              ?>  
              <tr class="table-row transition-all duration-300 <?= $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?>">  
                <td class="py-4 px-4 border-b">  
                  <div class="flex flex-col gap-1">
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium inline-block w-fit">
                      <i class="fa-solid fa-table mr-1"></i><?= htmlspecialchars($tableName) ?>
                    </span>
                    <?php if($t['TABLE_COMMENT']): ?>
                      <span class="text-xs text-gray-500 italic"><?= htmlspecialchars($t['TABLE_COMMENT']) ?></span>
                    <?php endif; ?>
                  </div>
                </td>  
                <td class="py-4 px-4 border-b">  
                  <span class="bg-emerald-50 text-emerald-700 px-2 py-1 rounded text-xs border border-emerald-200">  
                    <i class="fa-regular fa-calendar-plus mr-1"></i><?= htmlspecialchars($created) ?>  
                  </span>  
                </td>  
                <td class="py-4 px-4 border-b">  
                  <span class="bg-amber-50 text-amber-700 px-2 py-1 rounded text-xs border border-amber-200">  
                    <i class="fa-regular fa-pen-to-square mr-1"></i><?= htmlspecialchars($updated) ?>  
                  </span>  
                </td>  
                <td class="py-4 px-4 border-b">  
                  <div class="flex flex-col gap-1">
                    <span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded text-xs border border-indigo-200">  
                      <i class="fa-solid fa-list-ol mr-1"></i><?= number_format($actualRows) ?> rows
                    </span>
                    <?php if($estimatedRows && $estimatedRows != $actualRows): ?>
                      <span class="text-xs text-gray-500">Est: <?= number_format($estimatedRows) ?></span>
                    <?php endif; ?>
                  </div>
                </td>  
                <td class="py-4 px-4 border-b">  
                  <span class="bg-purple-50 text-purple-700 px-2 py-1 rounded text-xs border border-purple-200">  
                    <i class="fa-solid fa-cog mr-1"></i><?= htmlspecialchars($t['ENGINE'] ?: '—') ?>  
                  </span>  
                </td>  
                <td class="py-4 px-4 border-b">  
                  <span class="bg-teal-50 text-teal-700 px-2 py-1 rounded text-xs border border-teal-200">  
                    <i class="fa-solid fa-hard-drive mr-1"></i><?= formatBytes($size) ?>  
                  </span>  
                </td>  
                <td class="py-4 px-4 border-b">
                  <div class="flex gap-2">
                    <!-- Copy Button -->
                    <button class="copyBtn px-2 py-1 text-xs bg-gray-600 hover:bg-gray-700 text-white rounded shadow transition-colors"  
                            data-name="<?= htmlspecialchars($tableName) ?>">  
                      <i class="fa-regular fa-copy mr-1"></i>Copy  
                    </button>
                    
                    <!-- Download Dropdown -->
                    <div class="download-btn relative">
                      <button class="px-2 py-1 text-xs bg-green-600 hover:bg-green-700 text-white rounded shadow transition-colors">
                        <i class="fa-solid fa-download mr-1"></i>Download <i class="fa-solid fa-chevron-down ml-1"></i>
                      </button>
                      <div class="download-dropdown">
                        <a href="?download=1&table=<?= urlencode($tableName) ?>&format=csv" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                          <i class="fa-solid fa-file-csv mr-2"></i>CSV Format
                        </a>
                        <a href="?download=1&table=<?= urlencode($tableName) ?>&format=json" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                          <i class="fa-solid fa-file-code mr-2"></i>JSON Format
                        </a>
                        <a href="?download=1&table=<?= urlencode($tableName) ?>&format=sql" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors border-t">
                          <i class="fa-solid fa-database mr-2"></i>SQL Dump
                        </a>
                      </div>
                    </div>
                    
                    <!-- View Details Button -->
                    <button class="detailsBtn px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded shadow transition-colors"  
                            data-table="<?= htmlspecialchars($tableName) ?>">  
                      <i class="fa-solid fa-info-circle mr-1"></i>Info  
                    </button>
                  </div>
                </td>  
              </tr>  
              <?php endforeach; ?>  
            <?php endif; ?>  
          </tbody>  
        </table>  
      </div>  
    </div>  

    <!-- Footer -->  
    <div class="text-center mt-8 text-blue-100">  
      <p class="mb-2"><i class="fas fa-info-circle mr-2"></i>Tables ordered by <strong>CREATE_TIME DESC</strong> (newest first)</p>
      <p class="text-sm opacity-75">
        <i class="fa-solid fa-download mr-1"></i>Click download to export complete table data in CSV, JSON, or SQL format
      </p>
    </div>

  </div>    

  <!-- Modal for table details -->
  <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Table Details</h3>
          <button id="closeModal" class="text-gray-500 hover:text-gray-700">
            <i class="fa-solid fa-times text-xl"></i>
          </button>
        </div>
        <div id="modalContent" class="space-y-4">
          <!-- Content will be loaded here -->
        </div>
      </div>
    </div>
  </div>

  <script>  
    // Client-side filter  
    const filterInput = document.getElementById('filterInput');  
    const tableBody = document.getElementById('tableBody');  
  
    function normalize(s){ return (s || '').toLowerCase().trim(); }  
  
    filterInput?.addEventListener('input', function(){  
      const q = normalize(this.value);  
      const rows = tableBody.querySelectorAll('tr');  
      rows.forEach(r => {  
        const nameBadge = r.querySelector('td:nth-child(1) span');  
        if(!nameBadge){ r.style.display = ''; return; }  
        const name = normalize(nameBadge.textContent);  
        r.style.display = name.includes(q) ? '' : 'none';  
      });  
    });  
  
    // Copy table name  
    document.querySelectorAll('.copyBtn').forEach(btn => {  
      btn.addEventListener('click', async () => {  
        const name = btn.getAttribute('data-name');  
        try {  
          await navigator.clipboard.writeText(name);  
          const prev = btn.innerHTML;  
          btn.innerHTML = '<i class="fa-solid fa-check mr-1"></i>Copied';  
          btn.classList.remove('bg-gray-600', 'hover:bg-gray-700');
          btn.classList.add('bg-green-600');
          setTimeout(()=> {
            btn.innerHTML = prev;
            btn.classList.remove('bg-green-600');
            btn.classList.add('bg-gray-600', 'hover:bg-gray-700');
          }, 1200);  
        } catch (e) {  
          alert('Copy failed: ' + e);  
        }  
      });  
    });

    // Table details modal
    const modal = document.getElementById('detailsModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    const closeModal = document.getElementById('closeModal');

    document.querySelectorAll('.detailsBtn').forEach(btn => {
      btn.addEventListener('click', async () => {
        const tableName = btn.getAttribute('data-table');
        modalTitle.textContent = `Table: ${tableName}`;
        modalContent.innerHTML = '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading...</div>';
        modal.classList.remove('hidden');

        try {
          // This would typically fetch from a separate endpoint
          // For now, showing static info
          modalContent.innerHTML = `
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-semibold mb-2"><i class="fa-solid fa-table mr-2"></i>Table: ${tableName}</h4>
              <p class="text-sm text-gray-600 mb-4">Detailed information about this table structure and data.</p>
              
              <div class="grid md:grid-cols-2 gap-4 text-sm">
                <div>
                  <strong>Actions Available:</strong>
                  <ul class="mt-2 space-y-1">
                    <li><i class="fa-solid fa-download mr-2 text-green-600"></i>Download as CSV</li>
                    <li><i class="fa-solid fa-download mr-2 text-blue-600"></i>Download as JSON</li>
                    <li><i class="fa-solid fa-download mr-2 text-purple-600"></i>Download as SQL</li>
                  </ul>
                </div>
                <div>
                  <strong>Export Options:</strong>
                  <ul class="mt-2 space-y-1">
                    <li><i class="fa-solid fa-check mr-2 text-green-500"></i>Complete data export</li>
                    <li><i class="fa-solid fa-check mr-2 text-green-500"></i>All columns included</li>
                    <li><i class="fa-solid fa-check mr-2 text-green-500"></i>Proper formatting</li>
                  </ul>
                </div>
              </div>
              
              <div class="mt-4 p-3 bg-blue-50 rounded border border-blue-200">
                <p class="text-blue-800 text-sm">
                  <i class="fa-solid fa-info-circle mr-2"></i>
                  Use the download dropdown to export complete table data. SQL format includes both structure and data.
                </p>
              </div>
            </div>
          `;
        } catch (error) {
          modalContent.innerHTML = `<div class="text-red-600">Error loading details: ${error.message}</div>`;
        }
      });
    });

    closeModal.addEventListener('click', () => {
      modal.classList.add('hidden');
    });

    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.classList.add('hidden');
      }
    });

    // Download progress feedback
    document.querySelectorAll('a[href*="download=1"]').forEach(link => {
      link.addEventListener('click', (e) => {
        const format = new URL(link.href).searchParams.get('format').toUpperCase();
        const btn = link.closest('.download-btn').querySelector('button');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1"></i>Downloading ${format}...`;
        btn.classList.add('bg-blue-600');
        
        setTimeout(() => {
          btn.innerHTML = originalText;
          btn.classList.remove('bg-blue-600');
        }, 3000);
      });
    });
  </script>  
</body>  
</html>
