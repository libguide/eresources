
<?php
require_once __DIR__ . '/db.php';
$pdo = get_pdo();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="resources_export.csv"');


$out = fopen('php://output', 'w');

// Header
fputcsv($out, ['title','publisher','subject','type','year','link']);

// Data
$stmt = $pdo->query("SELECT title, publisher, subject, type, year, link FROM resources ORDER BY title ASC");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
        $row['title'],
        $row['publisher'],
        $row['subject'],
        $row['type'],
        $row['year'],
        $row['link']
    ]);
}
fclose($out);
