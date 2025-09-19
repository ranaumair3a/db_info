<?php
require_once 'config_seci.php';
date_default_timezone_set('Asia/Karachi');

$error = '';
$success = '';
$data = null;
$table = 'NANA2024000_RESULT';

if (isset($_POST['table'])) {
    $table = mysqli_real_escape_string($con, $_POST['table']);
}

// Fetch RNO range for the current table
$min_rno = $max_rno = null;
if ($con) {
    $range_query = "SELECT MIN(RNO) as min_rno, MAX(RNO) as max_rno FROM `{$table}`";
    $range_result = mysqli_query($con, $range_query);
    if ($range_result) {
        $range = mysqli_fetch_assoc($range_result);
        $min_rno = $range['min_rno'];
        $max_rno = $range['max_rno'];
    } else {
        $error = "Failed to fetch RNO range: " . htmlspecialchars(mysqli_error($con));
    }
}

// Handle search and update
if (isset($_POST['action'])) {
    $rno = intval($_POST['rno']);
    
    if ($_POST['action'] === 'search') {
        // Search for the record
        $query = "SELECT * FROM `{$table}` WHERE `RNO` = $rno LIMIT 1";
        $result = mysqli_query($con, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $data = mysqli_fetch_assoc($result);
        } else {
            $error = "No record found for Roll Number: " . htmlspecialchars($rno);
        }
    } elseif ($_POST['action'] === 'update' && $data === null) {
        // Fetch current data for update
        $query = "SELECT * FROM `{$table}` WHERE `RNO` = $rno LIMIT 1";
        $result = mysqli_query($con, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $data = mysqli_fetch_assoc($result);
            
            // Update the record
            $updateFields = [];
            foreach ($data as $key => $value) {
                if ($key !== 'RNO') { // Don't update primary key
                    $newValue = $_POST[$key] ?? '';
                    // Convert empty strings to 0 for numeric fields like SUB8_MAX
                    if ($key === 'SUB8_MAX' && empty($newValue)) {
                        $newValue = 0;
                    }
                    $newValue = mysqli_real_escape_string($con, $newValue);
                    $originalValue = $value !== null ? $value : '';
                    
                    // Only update if the value has changed
                    if ($newValue !== $originalValue) {
                        $updateFields[] = "`$key` = '$newValue'";
                    }
                }
            }
            
            if (!empty($updateFields)) {
                $updateQuery = "UPDATE `{$table}` SET " . implode(', ', $updateFields) . " WHERE `RNO` = $rno";
                if (mysqli_query($con, $updateQuery)) {
                    $success = "Record updated successfully for Roll Number: " . htmlspecialchars($rno);
                    // Refresh data
                    $refreshQuery = "SELECT * FROM `{$table}` WHERE `RNO` = $rno LIMIT 1";
                    $refreshResult = mysqli_query($con, $refreshQuery);
                    $data = mysqli_fetch_assoc($refreshResult);
                } else {
                    $error = "Update failed: " . htmlspecialchars(mysqli_error($con));
                }
            } else {
                $success = "No changes detected for Roll Number: " . htmlspecialchars($rno);
            }
        } else {
            $error = "No record found for update.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View and Edit Results - <?= htmlspecialchars($table) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .glass { backdrop-filter: blur(10px); background: rgba(255,255,255,.92); border: 1px solid rgba(255,255,255,.25); }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">
                <i class="fa-solid fa-edit mr-3"></i>Result Viewer & Editor
            </h1>
            <p class="text-blue-100 text-lg">Search and edit records from table: <span class="font-semibold"><?= htmlspecialchars($table) ?></span></p>
            <p class="text-blue-100 text-lg">Searchable RNO Range: From <?= htmlspecialchars($min_rno ?? 'N/A') ?> to <?= htmlspecialchars($max_rno ?? 'N/A') ?></p>
        </div>

        <!-- Search Form -->
        <div class="max-w-6xl mx-auto mb-6">
            <div class="glass rounded-xl p-5 shadow-xl">
                <form method="POST" class="flex flex-col md:flex-row md:items-center gap-4">
                    <input type="hidden" name="action" value="search">
                    <div class="flex-1 relative">
                        <input type="number" name="rno" placeholder="Enter Roll Number (RNO)" required
                            class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-300">
                        <i class="fa-solid fa-search absolute left-4 top-3.5 text-gray-400"></i>
                    </div>
                    <div class="flex-1 relative">
                        <input type="text" name="table" value="<?= htmlspecialchars($table) ?>" placeholder="Enter Table Name" required
                            class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-300">
                        <i class="fa-solid fa-table absolute left-4 top-3.5 text-gray-400"></i>
                    </div>
                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold rounded-lg hover:from-blue-600 hover:to-purple-700 transform hover:scale-105 transition-all duration-300 shadow-lg">
                        <i class="fa-solid fa-search mr-2"></i>Search
                    </button>
                </form>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="max-w-6xl mx-auto mb-6">
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-r-lg shadow-md">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-3 text-xl"></i>
                        <?= $error ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="max-w-6xl mx-auto mb-6">
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-r-lg shadow-md">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3 text-xl"></i>
                        <?= $success ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <?php if ($data): ?>
            <div class="max-w-6xl mx-auto glass rounded-xl p-5 shadow-xl">
                <h2 class="text-2xl font-bold mb-4">Edit Record for RNO: <?= htmlspecialchars($data['RNO']) ?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="rno" value="<?= htmlspecialchars($data['RNO']) ?>">
                    <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($data as $key => $value): ?>
                            <?php if ($key !== 'RNO'): // Don't edit primary key ?>
                                <div class="flex flex-col">
                                    <label class="text-gray-700 font-medium mb-1"><?= htmlspecialchars($key) ?></label>
                                    <input type="text" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value ?? '') ?>"
                                        class="px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-blue-500">
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-green-500 to-teal-600 text-white font-semibold rounded-lg hover:from-green-600 hover:to-teal-700 transform hover:scale-105 transition-all duration-300 shadow-lg">
                            <i class="fa-solid fa-save mr-2"></i>Update Record
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
