<?php
require 'includes/db.php';
$roles = $pdo->query('SELECT * FROM roles')->fetchAll(PDO::FETCH_ASSOC);
print_r($roles);
