
<?php
session_start();
require_once __DIR__ . '/db.php';
$pdo = get_pdo();

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin.php?msg=' . urlencode('Please log in to upload.'));
    exit;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    header('Location: admin.php?msg=' . urlencode('Upload failed.'));
    exit;
}

$tmpPath = $_FILES['csv_file']['tmp_name'];
$originalName = $_FILES['csv_file']['name'];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $tmpPath);
finfo_close($finfo);

$allowed_mimes = ['text/plain', 'text/csv', 'application/vnd.ms-excel', 'application/csv'];
if (!in_array($mime, $allowed_mimes, true)) {
    header('Location: admin.php?msg=' . urlencode('Invalid file type. Please upload a CSV.'));
    exit;
}

// Check if this exact file (by content hash) was uploaded before
$hash = md5_file($tmpPath);
$insUpload = $pdo->prepare("INSERT IGNORE INTO uploads (file_name, file_hash) VALUES (:name, :hash)");
$insUpload->execute([':name' => $originalName, ':hash' => $hash]);
if ($insUpload->rowCount() === 0) {
    header('Location: admin.php?msg=' . urlencode('This CSV file has already been uploaded. No changes made.'));
    exit;
}

// Read CSV
$fh = fopen($tmpPath, 'r');
if (!$fh) {
    header('Location: admin.php?msg=' . urlencode('Unable to read the uploaded file.'));
    exit;
}

// Validate header
$header = fgetcsv($fh);
if (!$header) {
    fclose($fh);
    header('Location: admin.php?msg=' . urlencode('Empty CSV.'));
    exit;
}

$header = array_map(function($h) {
    return strtolower(trim($h ?? ''));
}, $header);

$required = ['title','publisher','subject','type','year','link'];
if ($header !== $required) {
    fclose($fh);
    header('Location: admin.php?msg=' . urlencode('Invalid header. Expected: title,publisher,subject,year,link'));
    exit;
}

// Prepare insert IGNORE (duplicates skipped via checksum UNIQUE)
$insert = $pdo->prepare("
   INSERT IGNORE INTO resources (title, publisher, subject, type, year, link)
    VALUES (:title, :publisher, :subject, :type, :year, :link)
");

$pdo->beginTransaction();
$count_total = 0;
$count_inserted = 0;

while (($row = fgetcsv($fh)) !== false) {
    if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;
    $data = array_combine($required, array_map('trim', $row));

    $year = null;
    if (isset($data['year']) && $data['year'] !== '') {
        $y = (int)$data['year'];
        if ($y >= 0 && $y <= 9999) $year = $y;
    }

    $count_total++;
    try {
        $insert->execute([
            ':title'     => $data['title'],
            ':publisher' => $data['publisher'] !== '' ? $data['publisher'] : null,
            ':subject'   => $data['subject'] !== '' ? $data['subject'] : null,
            ':type' => $data['type'] !== '' ? $data['type'] : null,
            ':year'      => $year,
            ':link'      => $data['link'] !== '' ? $data['link'] : null,
        ]);
        if ($insert->rowCount() > 0) $count_inserted++;
    } catch (PDOException $e) {
        // ignore row errors
    }
}

$pdo->commit();
fclose($fh);

$skipped_rows = $count_total - $count_inserted;
header('Location: admin.php?msg=' . urlencode("Imported $count_inserted new row(s). Skipped $skipped_rows duplicate row(s)."));
exit;
