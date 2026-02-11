<?php
echo "<!DOCTYPE html>";
echo "<html><head><title>Direct View Test</title></head><body>";
echo "<h1>Direct HTML Output Works!</h1>";
echo "<p>If you see this, PHP and routing work.</p>";
echo "<p>Template path should be: " . ROOT . "/templates/Users/login.php</p>";
echo "<p>Does it exist? " . (file_exists(ROOT . "/templates/Users/login.php") ? "YES" : "NO") . "</p>";

if (file_exists(ROOT . "/templates/Users/login.php")) {
    echo "<h2>Template Contents:</h2>";
    echo "<textarea style='width:100%;height:300px'>";
    echo htmlspecialchars(file_get_contents(ROOT . "/templates/Users/login.php"));
    echo "</textarea>";
}

echo "</body></html>";
exit;
?>
