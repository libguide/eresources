
<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
$pdo = get_pdo();

$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';

// Handle login
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $msg = 'Invalid credentials.';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php?msg=' . urlencode('Logged out.'));
    exit;
}

$logged_in = !empty($_SESSION['admin_logged_in']);

// If logged in, fetch resources (basic pagination optional)
$rows = [];
if ($logged_in) {
    $stmt = $pdo->query("SELECT id, title, publisher, subject, year, link FROM resources ORDER BY id DESC LIMIT 500");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Resource Portal</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; margin: 24px; }
    h1 { margin-bottom: 8px; }
    .card { border: 1px solid #e5e5e5; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
    label { display: block; margin-top: 12px; }
    input[type="text"], input[type="password"], input[type="file"] { padding: 8px; width: 100%; box-sizing: border-box; }
    button, .btn { padding: 8px 12px; border: 1px solid #ccc; background: #f8f8f8; cursor: pointer; border-radius: 6px; text-decoration:none; color:inherit; }
    button:hover, .btn:hover { background: #efefef; }
    .msg { padding: 10px 12px; background: #eef9f0; border: 1px solid #cfe8d4; border-radius: 6px; margin: 12px 0; }
    .top { display:flex; align-items:center; gap:12px; margin-bottom:16px;}
    .spacer { flex:1; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #e5e5e5; padding: 8px; vertical-align: top; }
    th { background: #fafafa; text-align: left; }
    .nowrap { white-space: nowrap; }
    .actions a { margin-right: 6px; }
  </style>
</head>
<body>
  <div class="top">
    <h1>Admin Area</h1>
    <div class="spacer"></div>
    <a class="btn" href="index.php">Public View</a>
    <?php if ($logged_in): ?>
      <a class="btn" href="admin.php?logout=1">Logout</a>
    <?php endif; ?>
  </div>

  <?php if ($msg): ?>
    <div class="msg"><?php echo $msg; ?></div>
  <?php endif; ?>

  <?php if (!$logged_in): ?>
    <div class="card" style="max-width:640px;">
      <h3>Login</h3>
      <form method="post" action="admin.php">
        <input type="hidden" name="action" value="login">
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <div style="margin-top:12px;">
          <button type="submit">Login</button>
        </div>
      </form>
      <p style="color:#777; font-size:.9em; margin-top:8px;">Default: <code>admin / admin123</code>. Change in <code>config.php</code>.</p>
    </div>
  <?php else: ?>
    <div class="card" style="max-width:640px;">
      <h3>Upload CSV</h3>
      <p>CSV header must be exactly: <code>title,publisher,subject,year,link</code></p>
      <form method="post" action="import.php" enctype="multipart/form-data">
        <label>Select CSV file</label>
        <input type="file" name="csv_file" accept=".csv,text/csv" required>
        <div style="margin-top:12px;">
          <button type="submit">Import</button>
        </div>
      </form>
      <p style="color:#555; font-size:.9em; margin-top:8px;">
        Duplicate rows are skipped automatically. If you upload the <em>same CSV file</em> again, it will be ignored.
      </p>
    </div>

    <div class="card">
      <h3>Manage Resources</h3>
      <table>
        <thead>
          <tr>
            <th class="nowrap">#</th>
            <th>Title</th>
            <th>Publisher</th>
            <th>Subject</th>
            <th class="nowrap">Year</th>
            <th>Link</th>
            <th class="nowrap">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7">No records yet.</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?php echo (int)$r['id']; ?></td>
                <td><?php echo htmlspecialchars($r['title']); ?></td>
                <td><?php echo htmlspecialchars($r['publisher'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['subject'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['year'] ?? ''); ?></td>
                <td>
                  <?php if (!empty($r['link'])): ?>
                    <a href="<?php echo htmlspecialchars($r['link']); ?>" target="_blank" rel="noopener">Open</a>
                  <?php endif; ?>
                </td>
                <td class="actions">
                  <a class="btn" href="edit.php?id=<?php echo (int)$r['id']; ?>">Edit</a>
                  <a class="btn" href="delete.php?id=<?php echo (int)$r['id']; ?>" onclick="return confirm('Delete this record?');">Delete</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</body>
</html>
