
<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin.php?msg=' . urlencode('Please log in to edit.'));
    exit;
}

$pdo = get_pdo();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $link = trim($_POST['link'] ?? '');

    if ($title === '') {
        $msg = 'Title is required.';
    } else {
        $stmt = $pdo->prepare("UPDATE resources SET title=:title, publisher=:publisher, subject=:subject, year=:year, link=:link WHERE id=:id");
        $y = null;
        if ($year !== '') {
            $yy = (int)$year;
            if ($yy >= 0 && $yy <= 9999) $y = $yy;
        }
        try {
            $stmt->execute([
                ':title' => $title,
                ':publisher' => $publisher !== '' ? $publisher : null,
                ':subject' => $subject !== '' ? $subject : null,
                ':year' => $y,
                ':link' => $link !== '' ? $link : null,
                ':id' => $id
            ]);
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $msg = 'Update failed: possible duplicate after change.';
        }
    }
}

// Load record
$stmt = $pdo->prepare("SELECT * FROM resources WHERE id=:id");
$stmt->execute([':id' => $id]);
$rec = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$rec) {
    die('Record not found.');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Resource</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; margin: 24px; }
    .card { max-width: 720px; border: 1px solid #e5e5e5; border-radius: 8px; padding: 16px; }
    label { display: block; margin-top: 12px; }
    input[type="text"], input[type="number"], textarea { padding: 8px; width: 100%; box-sizing: border-box; }
    button, .btn { padding: 8px 12px; border: 1px solid #ccc; background: #f8f8f8; cursor: pointer; border-radius: 6px; }
    button:hover, .btn:hover { background: #efefef; }
    .top { display:flex; align-items:center; gap:12px; margin-bottom:16px;}
    .spacer { flex:1; }
    .error { color: #b00020; }
  </style>
</head>
<body>
  <div class="top">
    <h1>Edit Resource</h1>
    <div class="spacer"></div>
    <a class="btn" href="index.php">Back</a>
  </div>

  <?php if (!empty($msg)): ?>
    <div class="error"><?php echo htmlspecialchars($msg); ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="post" action="edit.php?id=<?php echo (int)$rec['id']; ?>">
      <input type="hidden" name="id" value="<?php echo (int)$rec['id']; ?>">
      <label>Title</label>
      <input type="text" name="title" value="<?php echo htmlspecialchars($rec['title']); ?>" required>
      <label>Publisher</label>
      <input type="text" name="publisher" value="<?php echo htmlspecialchars($rec['publisher'] ?? ''); ?>">
      <label>Subject</label>
      <input type="text" name="subject" value="<?php echo htmlspecialchars($rec['subject'] ?? ''); ?>">
      <label>Year</label>
      <input type="number" name="year" min="0" max="9999" value="<?php echo htmlspecialchars($rec['year'] ?? ''); ?>">
      <label>Link</label>
      <input type="text" name="link" value="<?php echo htmlspecialchars($rec['link'] ?? ''); ?>">
      <div style="margin-top:12px;">
        <button type="submit">Save</button>
      </div>
    </form>
  </div>
</body>
</html>
