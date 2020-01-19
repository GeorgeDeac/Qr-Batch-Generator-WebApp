<?php
$db = new PDO('mysql:host=localhost;dbname=qr', 'root', '');
if (!$db) {
   die('Database not available: ' . mysql_error());
   exit;
}
$result = $db->query("SET NAMES 'utf8'");
date_default_timezone_set("Europe/Bucharest");
?>