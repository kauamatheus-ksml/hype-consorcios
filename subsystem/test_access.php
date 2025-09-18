<?php
// Simple test to verify subsystem access is working
echo "✅ Subsystem access is working!";
echo "<br>";
echo "Current file: " . __FILE__;
echo "<br>";
echo "Directory: " . __DIR__;
echo "<br>";
echo "Commission settings file exists: " . (file_exists(__DIR__ . '/commission_settings.php') ? 'YES' : 'NO');
?>