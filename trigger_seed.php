<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "Seeding questions...\n";
seed_jamb_question_bank_if_empty();

echo "Checking CRS specifically:\n";
$stmt = $pdo->query("SELECT * FROM jamb_questions WHERE subject = 'CRS'");
$crs = $stmt->fetchAll();
echo "Found " . count($crs) . " CRS questions.\n";

$stmt = $pdo->query("SELECT subject, COUNT(*) as c FROM jamb_questions GROUP BY subject");
$rows = $stmt->fetchAll();
echo "Current Question Counts:\n";
foreach ($rows as $r) {
    echo $r['subject'] . ": " . $r['c'] . "\n";
}
?>