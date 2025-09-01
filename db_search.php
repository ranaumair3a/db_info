<?php
require_once 'config_seci.php';
date_default_timezone_set('Asia/Karachi');

// Read limit from GET (default: 25)
$allowedLimits = ['all', '5', '10', '25', '50', '100'];
$limitParam = isset($_GET['limit']) ? strtolower(trim($_GET['limit'])) : '25';
if (!in_array($limitParam, $allowedLimits)) $limitParam = '25';

$limitSql = ($limitParam === 'all') ? '' : 'LIMIT ' . intval($limitParam);

// Handle CSV download
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="tables_export_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, ['Table', 'Created', 'Day', 'Updated', 'Rows', 'Engine', 'Collation', 'Size']);
    
    // Fetch all tables for CSV
    $query = "
        SELECT 
            TABLE_NAME, 
            ENGINE, 
            CREATE_TIME, 
            UPDATE_TIME, 
            TABLE_ROWS, 
            DATA_LENGTH, 
            INDEX_LENGTH, 
            TABLE_COLLATION 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = '{$db}' 
        ORDER BY CREATE_TIME DESC, TABLE_NAME ASC
    ";
    $result = mysqli_query($con, $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $size = (float)($row['DATA_LENGTH'] ?? 0) + (float)($row['INDEX_LENGTH'] ?? 0);
        $created = $row['CREATE_TIME'] ? date('Y-m-d H:i:s', strtotime($row['CREATE_TIME'])) : '—';
        $day = $row['CREATE_TIME'] ? date('l', strtotime($row['CREATE_TIME'])) : '—';
        $updated = $row['UPDATE_TIME'] ? date('Y-m-d H:i:s', strtotime($row['UPDATE_TIME'])) : '—';
        $rows = isset($row['TABLE_ROWS']) ? (int)$row['TABLE_ROWS'] : null;
        
        fputcsv($output, [
            $row['TABLE_NAME'],
            $created,
            $day,
            $updated,
            $rows === null ? '—' : number_format($rows),
            $row['ENGINE'] ?: '—',
            $row['TABLE_COLLATION'] ?: '—',
            formatBytes($size)
        ]);
    }
    
    fclose($output);
    exit;
}

// Fetch tables ordered by CREATE_TIME DESC (newest first)
$query = "
    SELECT 
        TABLE_NAME, 
        ENGINE, 
        CREATE_TIME, 
        UPDATE_TIME, 
        TABLE_ROWS, 
        DATA_LENGTH, 
        INDEX_LENGTH, 
        TABLE_COLLATION 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = '{$db}' 
    ORDER BY CREATE_TIME DESC, TABLE_NAME ASC 
    {$limitSql}
";

$result = mysqli_query($con, $query);
$tables = [];
$error = '';
$groupedTables = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tables[] = $row;
        // Group by creation date (Y-m-d)
        $createDate = $row['CREATE_TIME'] ? date('Y-m-d', strtotime($row['CREATE_TIME'])) : 'Unknown';
        $groupedTables[$createDate][] = $row;
    }
} else {
    $error = "Database Error: " . htmlspecialchars(mysqli_error($con));
}

// Helper for size formatting
function formatBytes($bytes) {
    if ($bytes === null) return '—';
    $bytes = (float)$bytes;
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return number_format($bytes, 2) . ' ' . $units[$i];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Newest Tables • Database Overview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .glass { backdrop-filter: blur(10px); background: rgba(255, 255, 255, .92); border: 1px solid rgba(255, 255, 255, .25); }
        .table-row:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, .08); }
        .sticky-th { position: sticky; top: 0; z-index: 10; }
        .group-header { background: #f1f5f9; font-weight: bold; }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">
                <i class="fa-solid fa-database mr-3"></i>Newest Tables
            </h1>
            <p class="text-blue-100 text-lg">Recently created tables in <span class="font-semibold"><?= htmlspecialchars($db) ?></span> (new → old)</p>
        </div>

        <!-- Controls -->
        <div class="max-w-5xl mx-auto mb-6">
            <div class="glass rounded-xl p-5 shadow-xl">
                <form method="GET" class="flex flex-col md:flex-row md:items-center gap-4">
                    <div class="flex-1 relative">
                        <input id="filterInput" type="text" placeholder="Filter tables…" 
                            class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-300">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-gray-400"></i>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="text-gray-700 font-medium"><i class="fa-regular fa-square-check mr-2"></i>Limit</label>
                        <select name="limit" class="px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-blue-500">
                            <?php foreach ($allowedLimits as $opt): ?>
                                <option value="<?= $opt ?>" <?= $opt === $limitParam ? 'selected' : '' ?>>
                                    <?= strtoupper($opt) === 'ALL' ? 'All' : $opt ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="px-5 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold rounded-lg hover:from-blue-600 hover:to-purple-700 transform hover:scale-105 transition-all duration-300 shadow-lg">
                            <i class="fa-solid fa-rotate mr-2"></i>Apply
                        </button>
                        <a href="?download=csv&limit=<?= $limitParam ?>" class="px-5 py-2.5 bg-gradient-to-r from-green-500 to-teal-600 text-white font-semibold rounded-lg hover:from-green-600 hover:to-teal-700 transform hover:scale-105 transition-all duration-300 shadow-lg">
                            <i class="fa-solid fa-download mr-2"></i>Download CSV
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Error -->
        <?php if ($error): ?>
            <div class="max-w-5xl mx-auto mb-6">
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-r-lg shadow-md">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-3 text-xl"></i>
                        <strong>Error:</strong>&nbsp;<?= $error ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="max-w-5xl mx-auto mb-4">
            <div class="glass rounded-xl p-4 flex items-center justify-between text-sm md:text-base">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-semibold">
                        <?= count($tables) ?> table<?= count($tables) == 1 ? '' : 's' ?>
                    </span>
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full font-semibold">
                        Limit: <?= strtoupper($limitParam) === 'ALL' ? 'All' : intval($limitParam) ?>
                    </span>
                </div>
                <div class="text-gray-500">
                    <i class="fa-regular fa-clock mr-2"></i>Generated at <?= date('Y-m-d H:i:s') ?>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div class="max-w-6xl mx-auto glass rounded-xl shadow-xl overflow-hidden">
            <div class="max-h-[70vh] overflow-y-auto">
                <table class="min-w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 sticky-th">
                        <tr>
                            <th class="py-4 px-6 text-left text-xs md:text-sm font-bold text-gray-700 uppercase tracking-wider border-b">Table</th>
                            <th class="py-4 px-6 text-left text-xs md:text-sm font-bold text-gray-700 uppercase tracking-wider border-b">Created</th>
                            <th class="py-4 px-6 text-left text-xs md:text-sm font-bold text-gray-700 uppercase tracking-wider border-b">Day</th>
                            <th class="py-4 px-6 text-left text-xs md:text-sm font-bold text-gray-700 uppercase tracking-wider border-b">Updated</th>
                            <th class="py-4 px-6 text-left text-xs md:text-sm font-bold text-gray-700 uppercase tracking-wider border-b">Rows</th>
                            <th class="py-4 px-6 text-left text-xs md:text-sm font-bold text-gray-700 uppercase tracking-wider border-b">Engine</th>
                            <th class="py-4 px-6 text-left text-xs md:text-sm font-bold text-gray-700 uppercase tracking-wider border-b">Collation</th>
                            <th class="py-4 px-6 text-left text-xs md:text-sm font-bold text-gray-700 uppercase tracking-wider border-b">Size</th>
                            <th class="py-4 px-6 text-left text-xs md:text-sm font-bold text-gray-700 uppercase tracking-wider border-b"></th>
                        </tr>
                    </thead>
                    <tbody id="tableBody" class="divide-y divide-gray-200">
                        <?php if (empty($tables)): ?>
                            <tr>
                                <td colspan="9" class="py-8 px-6 text-center text-gray-500">
                                    <i class="fa-regular fa-face-frown mr-2"></i>No tables found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($groupedTables as $createDate => $group): ?>
                                <?php if ($createDate !== 'Unknown'): ?>
                                    <tr class="group-header">
                                        <td colspan="9" class="py-3 px-6 text-sm font-semibold text-gray-700">
                                            <i class="fa-regular fa-calendar mr-2"></i>Created on <?= htmlspecialchars($createDate) ?> (<?= count($group) ?> table<?= count($group) == 1 ? '' : 's' ?>)
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($group as $i => $t): 
                                    $size = (float)($t['DATA_LENGTH'] ?? 0) + (float)($t['INDEX_LENGTH'] ?? 0);
                                    $created = $t['CREATE_TIME'] ? date('Y-m-d H:i:s', strtotime($t['CREATE_TIME'])) : '—';
                                    $day = $t['CREATE_TIME'] ? date('l', strtotime($t['CREATE_TIME'])) : '—';
                                    $updated = $t['UPDATE_TIME'] ? date('Y-m-d H:i:s', strtotime($t['UPDATE_TIME'])) : '—';
                                    $rows = isset($t['TABLE_ROWS']) ? (int)$t['TABLE_ROWS'] : null;
                                ?>
                                    <tr class="table-row transition-all duration-300 <?= $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?>">
                                        <td class="py-4 px-6 border-b">
                                            <div class="flex items-center gap-2">
                                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium"><?= htmlspecialchars($t['TABLE_NAME']) ?></span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 border-b">
                                            <span class="bg-emerald-50 text-emerald-700 px-3 py-1 rounded text-sm border border-emerald-200">
                                                <i class="fa-regular fa-calendar-plus mr-1"></i><?= htmlspecialchars($created) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 border-b">
                                            <span class="bg-cyan-50 text-cyan-700 px-3 py-1 rounded text-sm border border-cyan-200">
                                                <i class="fa-regular fa-calendar-day mr-1"></i><?= htmlspecialchars($day) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 border-b">
                                            <span class="bg-amber-50 text-amber-700 px-3 py-1 rounded text-sm border border-amber-200">
                                                <i class="fa-regular fa-pen-to-square mr-1"></i><?= htmlspecialchars($updated) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 border-b">
                                            <span class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded text-sm border border-indigo-200">
                                                <i class="fa-solid fa-list-ol mr-1"></i><?= $rows === null ? '—' : number_format($rows) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 border-b">
                                            <span class="bg-purple-50 text-purple-700 px-3 py-1 rounded text-sm border border-purple-200">
                                                <?= htmlspecialchars($t['ENGINE'] ?: '—') ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 border-b">
                                            <span class="bg-gray-50 text-gray-700 px-3 py-1 rounded text-sm border border-gray-200">
                                                <?= htmlspecialchars($t['TABLE_COLLATION'] ?: '—') ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 border-b">
                                            <span class="bg-teal-50 text-teal-700 px-3 py-1 rounded text-sm border border-teal-200">
                                                <i class="fa-solid fa-hard-drive mr-1"></i><?= formatBytes($size) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 border-b text-right">
                                            <button class="copyBtn px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow"
                                                    data-name="<?= htmlspecialchars($t['TABLE_NAME']) ?>">
                                                <i class="fa-regular fa-copy mr-1"></i>Copy
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-10 text-blue-100">
            <p><i class="fas fa-info-circle mr-2"></i>Ordered by <strong>CREATE_TIME DESC</strong>. If CREATE_TIME is <em>NULL</em>, alphabetic fallback is applied per query order.</p>
        </div>
    </div>

    <script>
        // Client-side filter
        const filterInput = document.getElementById('filterInput');
        const tableBody = document.getElementById('tableBody');

        function normalize(s) { return (s || '').toLowerCase().trim(); }

        filterInput?.addEventListener('input', function () {
            const q = normalize(this.value);
            const rows = tableBody.querySelectorAll('tr');
            let currentGroupDate = null;

            rows.forEach(r => {
                // Check if row is a group header
                if (r.classList.contains('group-header')) {
                    currentGroupDate = normalize(r.textContent);
                    r.style.display = 'none'; // Hide by default
                    return;
                }

                const nameBadge = r.querySelector('td:nth-child(1) span');
                if (!nameBadge) {
                    r.style.display = '';
                    return;
                }

                const name = normalize(nameBadge.textContent);
                const shouldShow = name.includes(q);
                r.style.display = shouldShow ? '' : 'none';

                // Show group header if any table in the group matches
                if (shouldShow && currentGroupDate) {
                    const groupHeader = Array.from(rows).find(row => row.classList.contains('group-header') && normalize(row.textContent).includes(currentGroupDate));
                    if (groupHeader) groupHeader.style.display = '';
                }
            });

            // Ensure at least one group header is visible if any tables are visible
            const visibleRows = Array.from(rows).filter(r => r.style.display !== 'none' && !r.classList.contains('group-header'));
            if (visibleRows.length === 0) {
                rows.forEach(r => {
                    if (r.classList.contains('group-header')) r.style.display = 'none';
                });
            }
        });

        // Copy table name
        document.querySelectorAll('.copyBtn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const name = btn.getAttribute('data-name');
                try {
                    await navigator.clipboard.writeText(name);
                    const prev = btn.innerHTML;
                    btn.innerHTML = '<i class="fa-solid fa-check mr-1"></i>Copied';
                    setTimeout(() => btn.innerHTML = prev, 1200);
                } catch (e) {
                    alert('Copy failed: ' + e);
                }
            });
        });
    </script>
</body>
</html>
