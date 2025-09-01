<?php
require_once 'config_seci.php';

// Initialize variables
$resultData = [];
$error = '';
$search_value = '';
$firstOnly = true;
$total_results = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_value'])) {
    $search_value = trim($_POST['search_value']);
    $firstOnly = !isset($_POST['multiple']);

    if (!empty($search_value)) {
        // Escape search value for mysqli
        $search_value_esc = mysqli_real_escape_string($con, $search_value);

        // Get all varchar/text columns from all tables
         $columnsQuery = "
            SELECT c.TABLE_NAME, c.COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS c
            JOIN INFORMATION_SCHEMA.TABLES t 
              ON c.TABLE_NAME = t.TABLE_NAME 
             AND c.TABLE_SCHEMA = t.TABLE_SCHEMA
            WHERE c.TABLE_SCHEMA = '{$db}'
              AND c.DATA_TYPE IN ('varchar','text','char','longtext','mediumtext','tinytext')
            ORDER BY t.CREATE_TIME DESC, c.ORDINAL_POSITION
        ";
        $columnsResult = mysqli_query($con, $columnsQuery);

        if (!$columnsResult) {
            $error = "Database Error: " . mysqli_error($con);
        } else {
            while ($col = mysqli_fetch_assoc($columnsResult)) {
                $table = $col['TABLE_NAME'];
                $column = $col['COLUMN_NAME'];

                $query = "SELECT * FROM `$table` WHERE `$column` LIKE '%$search_value_esc%'";
                if ($firstOnly) {
                    $query .= " LIMIT 1";
                }

                $searchResult = mysqli_query($con, $query);
                if ($searchResult) {
                    while ($row = mysqli_fetch_assoc($searchResult)) {
                        $resultData[] = [
                            'table' => $table,
                            'column' => $column,
                            'data' => $row,
                            'matched_value' => $row[$column]
                        ];
                        $total_results++;

                        if ($firstOnly) break 2; // Stop searching after first match
                    }
                } else {
                    $error = "Database Error: " . mysqli_error($con);
                }
            }
        }
    } else {
        $error = "Please enter a search value.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Database Search Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .table-row:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .search-animation {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">
                <i class="fas fa-search mr-3"></i>Database Search Tool
            </h1>
            <p class="text-blue-100 text-lg">Search across all database tables and columns</p>
        </div>

        <!-- Search Form -->
        <div class="max-w-2xl mx-auto mb-8">
            <form method="POST" class="glass-effect rounded-xl p-6 shadow-xl">
                <div class="flex flex-col md:flex-row gap-4 mb-4">
                    <div class="flex-1 relative">
                        <input type="text" 
                               name="search_value" 
                               value="<?= htmlspecialchars($search_value) ?>"
                               placeholder="Enter value to search..." 
                               class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-300"
                               required>
                        <i class="fas fa-search absolute left-4 top-4 text-gray-400"></i>
                    </div>
                    <button type="submit" 
                            class="px-8 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold rounded-lg hover:from-blue-600 hover:to-purple-700 transform hover:scale-105 transition-all duration-300 shadow-lg">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
                
                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" 
                               name="multiple" 
                               <?= isset($_POST['multiple']) ? 'checked' : '' ?>
                               class="w-5 h-5 text-blue-500 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-3 text-gray-700 font-medium">
                            <i class="fas fa-list mr-2"></i>Search for multiple matches
                        </span>
                    </label>
                </div>
            </form>
        </div>

        <!-- Error Display -->
        <?php if($error): ?>
            <div class="max-w-4xl mx-auto mb-6">
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-r-lg shadow-md">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-3 text-xl"></i>
                        <strong>Error:</strong>&nbsp;<?= htmlspecialchars($error) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Results Section -->
        <?php if($resultData): ?>
            <div class="max-w-6xl mx-auto">
                <!-- Results Header -->
                <div class="glass-effect rounded-t-xl p-6 border-b">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-table mr-3 text-blue-600"></i>Search Results
                        </h2>
                        <div class="flex items-center space-x-4">
                            <span class="bg-blue-100 text-blue-800 px-4 py-2 rounded-full font-semibold">
                                <?= $total_results ?> results found
                            </span>
                            <span class="bg-green-100 text-green-800 px-4 py-2 rounded-full font-semibold">
                                "<?= htmlspecialchars($search_value) ?>"
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="glass-effect rounded-b-xl shadow-xl overflow-hidden">
                    <div class="max-h-96 overflow-y-auto">
                        <table class="min-w-full">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 sticky top-0 z-10">
                                <tr>
                                    <th class="py-4 px-6 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-b">
                                        <i class="fas fa-database mr-2"></i>Table
                                    </th>
                                    <th class="py-4 px-6 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-b">
                                        <i class="fas fa-columns mr-2"></i>Column
                                    </th>
                                    <th class="py-4 px-6 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-b">
                                        <i class="fas fa-eye mr-2"></i>Matched Value
                                    </th>
                                    <th class="py-4 px-6 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-b">
                                        <i class="fas fa-info-circle mr-2"></i>Full Data
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach($resultData as $index => $item): ?>
                                    <tr class="table-row transition-all duration-300 <?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?>">
                                        <td class="py-4 px-6 border-b">
                                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                                <?= htmlspecialchars($item['table']) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 border-b">
                                            <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-medium">
                                                <?= htmlspecialchars($item['column']) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 border-b">
                                            <div class="max-w-xs truncate bg-yellow-50 px-3 py-1 rounded border-l-4 border-yellow-400">
                                                <?= htmlspecialchars($item['matched_value']) ?>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 border-b">
                                            <details class="cursor-pointer">
                                                <summary class="text-blue-600 hover:text-blue-800 font-medium">
                                                    <i class="fas fa-chevron-right mr-2"></i>View Details
                                                </summary>
                                                <div class="mt-3 p-3 bg-gray-50 rounded border max-h-40 overflow-y-auto">
                                                    <?php foreach($item['data'] as $k => $v): ?>
                                                        <div class="mb-2 text-sm">
                                                            <strong class="text-gray-700"><?= htmlspecialchars($k) ?>:</strong>
                                                            <span class="text-gray-600"><?= htmlspecialchars($v ?: 'NULL') ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </details>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif(isset($_POST['search_value']) && !$error): ?>
            <div class="max-w-4xl mx-auto">
                <div class="glass-effect rounded-xl p-8 text-center shadow-xl">
                    <div class="text-6xl text-gray-300 mb-4">
                        <i class="fas fa-search-minus"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-700 mb-2">No Results Found</h3>
                    <p class="text-gray-600 text-lg">
                        No matches found for <strong>"<?= htmlspecialchars($search_value) ?>"</strong>
                    </p>
                    <p class="text-gray-500 mt-2">Try using different search terms or check your spelling.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="text-center mt-12 text-blue-100">
            <p><i class="fas fa-info-circle mr-2"></i>This tool searches across all varchar, text, and char columns in the database.</p>
        </div>
    </div>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search_value"]');
            const submitBtn = document.querySelector('button[type="submit"]');
            
            // Add loading animation on form submit
            document.querySelector('form').addEventListener('submit', function() {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Searching...';
                submitBtn.disabled = true;
            });

            // Auto-focus on search input
            searchInput?.focus();
        });
    </script>
</body>
</html>
