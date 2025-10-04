<?php
require_once __DIR__ . '/db.php';
$pdo = get_pdo();

// Inputs
$q    = isset($_GET['q']) ? trim($_GET['q']) : '';
$tab  = isset($_GET['tab']) ? strtoupper(trim($_GET['tab'])) : 'ALL'; // ALL, A..Z, 0-9

// Build query
$sql    = "SELECT id, title, publisher, subject, year, link FROM resources";
$where  = [];
$params = [];

// Search across Title/Publisher/Subject (use distinct placeholders)
if ($q !== '') {
    $where[] = "(title LIKE :q1 OR publisher LIKE :q2 OR subject LIKE :q3)";
    $params[':q1'] = "%$q%";
    $params[':q2'] = "%$q%";
    $params[':q3'] = "%$q%";
}

// Tab filter
if ($tab !== 'ALL') {
    if ($tab === '0-9') {
        // Titles starting with a digit
        $where[] = "title REGEXP :re";
        $params[':re'] = '^[0-9]';
    } elseif (preg_match('/^[A-Z]$/', $tab)) {
        $where[] = "title LIKE :letter";
        $params[':letter'] = $tab . '%';
    } else {
        // Fallback to ALL for any unexpected value
        $tab = 'ALL';
    }
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// Default alphabetical sort by Title
$sql .= " ORDER BY title ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Tabs: ALL, A..Z, 0-9
$tabs = array_merge(['ALL'], range('A','Z'), ['0-9']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Resource Portal</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; margin: 24px; }
    h1 { margin-bottom: 8px; }
    .topbar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    input[type="text"] { padding: 8px; min-width: 260px; }
    button, .btn { padding: 8px 12px; border: 1px solid #ccc; background: #f8f8f8; cursor: pointer; border-radius: 6px; text-decoration:none; color:inherit; }
    button:hover, .btn:hover { background: #efefef; }
    .tabs { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
    .tab { padding: 6px 10px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: inherit; background: #fafafa; font-size: 0.95em; }
    .tab.active { background: #e9f3ff; border-color: #bcd7ff; font-weight: 600; }
    table { border-collapse: collapse; width: 100%; margin-top: 16px; }
    th, td { border: 1px solid #e5e5e5; padding: 8px; vertical-align: top; }
    th { background: #fafafa; text-align: left; }
    .mini { color: #666; font-size: 0.9em; }
    .footer { margin-top: 24px; color: #777; font-size: 0.9em; }
    .nowrap { white-space: nowrap; }
    .right { margin-left:auto }
  </style>
</head>
<body>
  <h1>Resource Portal</h1>
  <div class="mini">Browse resources. Sorted A→Z by title. Use tabs to filter by first letter.</div>

  <div class="topbar">
    <form method="get" action="index.php">
      <input type="text" name="q" placeholder="Search Title / Publisher / Subject" value="<?php echo htmlspecialchars($q); ?>">
      <?php if ($tab && $tab !== 'ALL'): ?>
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
      <?php endif; ?>
      <button type="submit">Search</button>
      <?php if ($q !== '' || ($tab && $tab !== 'ALL')): ?>
        <a class="btn" href="index.php">Clear</a>
      <?php endif; ?>
    </form>

    <form method="post" action="export.php" class="right">
      <button type="submit">Export CSV</button>
    </form>

    <a class="btn" href="admin.php">Admin</a>
  </div>

  <!-- A–Z Tabs -->
  <div class="tabs">
    <?php foreach ($tabs as $t): 
      // Preserve current search 'q' when switching tabs
      $query = ['tab' => $t];
      if ($q !== '') $query['q'] = $q;
      $href = 'index.php?' . http_build_query($query);
      $active = ($t === $tab) || ($t === 'ALL' && $tab === 'ALL');
    ?>
      <a class="tab <?php echo $active ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($href); ?>">
        <?php echo htmlspecialchars($t); ?>
      </a>
    <?php endforeach; ?>
  </div>

  <table>
    <thead>
      <tr>
        <th class="nowrap">#</th>
        <th>Title</th>
        <th>Publisher</th>
        <th>Subject</th>
        <th>Type</th>
        <th class="nowrap">Year</th>
        <th>Link</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="6">No records found.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['title']); ?></td>
            <td><?php echo htmlspecialchars($r['publisher'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['subject'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['type'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['year'] ?? ''); ?></td>

            
            <td>
              <?php if (!empty($r['link'])): ?>
                <a href="<?php echo htmlspecialchars($r['link']); ?>" target="_blank" rel="noopener">Open</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="footer">
    Tip: Use the tabs to jump to titles starting with a specific letter. “0-9” shows titles that start with a number.
  </div>
</body>
</html>
