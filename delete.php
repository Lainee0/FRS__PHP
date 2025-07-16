<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

// Delete the record
$stmt = $pdo->prepare("DELETE FROM families WHERE id = ?");
$stmt->execute([$id]);

header("Location: index.php");
exit;
?>