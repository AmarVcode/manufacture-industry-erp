<?php
require 'includes/db.php';

// Add raw material mappings for the fake products
$stmt = $pdo->query("SELECT item_code FROM products WHERE item_code LIKE 'TST-%'");
$products = $stmt->fetchAll();

$insert = $pdo->prepare("INSERT INTO raw_material_conversion (parent_product_code, component_name, exact_multiplier_qty, process_type) VALUES (?, ?, ?, ?)");

foreach ($products as $p) {
    $code = $p['item_code'];
    // Add 1 packaging box
    $insert->execute([$code, "Standard Box", 0.01, "packaging"]);
    // Add 1 core per piece
    $insert->execute([$code, "Generic Core", 1.0, "moulding"]);
}
echo "Added conversions.\n";
