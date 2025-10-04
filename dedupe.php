
<?php
// Optional script: remove existing duplicates, keep the latest record per checksum
// Usage: run once after adding checksum index.
require_once __DIR__ . '/db.php';
$pdo = get_pdo();

$sql = "
  DELETE r1 FROM resources r1
  JOIN resources r2
    ON r1.checksum = r2.checksum
   AND r1.id < r2.id
";
$deleted = $pdo->exec($sql);
echo "Deleted $deleted duplicate row(s).";
