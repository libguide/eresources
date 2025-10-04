
<?php
session_start();
require_once __DIR__ . '/db.php';
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin.php?msg=' . urlencode('Please log in to delete.'));
    exit;
}
$pdo = get_pdo();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM resources WHERE id=:id");
    $stmt->execute([':id' => $id]);
}
header('Location: index.php');
exit;
