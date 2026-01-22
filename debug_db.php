<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Info</h1>";

try {
    // Get last 5 materials GLOBALLY
    $stmt = $pdo->query("SELECT * FROM materials ORDER BY id DESC LIMIT 5");
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Last 5 Materials (Global)</h2>";
    if (empty($materials)) {
        echo "<p>No materials found in the entire database.</p>";
    } else {
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>User ID</th><th>Course</th><th>Notes</th><th>Created At</th><th>Summary Count</th><th>Exp Count</th><th>Q Count</th></tr>";
        foreach ($materials as $m) {
            // Check related data
            $sCount = $pdo->query("SELECT COUNT(*) FROM summaries WHERE material_id = " . $m['id'])->fetchColumn();
            $eCount = $pdo->query("SELECT COUNT(*) FROM explanations WHERE material_id = " . $m['id'])->fetchColumn();
            $qCount = $pdo->query("SELECT COUNT(*) FROM practice_questions WHERE material_id = " . $m['id'])->fetchColumn();
            
            echo "<tr>";
            echo "<td>{$m['id']}</td>";
            echo "<td>{$m['user_id']}</td>";
            echo "<td>" . htmlspecialchars($m['course']) . "</td>";
            echo "<td>" . htmlspecialchars($m['notes']) . "</td>";
            echo "<td>{$m['created_at']}</td>";
            echo "<td>$sCount</td>";
            echo "<td>$eCount</td>";
            echo "<td>$qCount</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>DB Error: " . $e->getMessage() . "</p>";
}
?>
