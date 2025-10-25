<?php
// Save this as hash.php and run: php hash.php
$password = 'admin123';
echo password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;
