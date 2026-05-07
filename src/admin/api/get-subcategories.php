<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../init.php';

$parentId = intval($_GET['parent_id'] ?? 0);

$db = Database::getInstance();
$subCategories = $db->fetchAll("SELECT id, name FROM categories WHERE parent_id = ? ORDER BY sort_order, id", [$parentId]);

echo json_encode($subCategories);
