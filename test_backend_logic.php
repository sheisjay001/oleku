<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Simulate User
$_SESSION['user_id'] = 'test_user_debug'; 

// Create a dummy file
$dummyFile = __DIR__ . '/uploads/test_debug.txt';
if (!file_exists(__DIR__ . '/uploads')) mkdir(__DIR__ . '/uploads');
file_put_contents($dummyFile, "This is a test document about Photosynthesis. Photosynthesis is the process by which green plants and some other organisms use sunlight to synthesize foods from carbon dioxide and water.");

$course = "BIO 101 Test";
$notes = "Focus on the definition.";
$filePath = $dummyFile;

echo "<h1>Backend Logic Test</h1>";
echo "Processing file: $filePath<br>";

try {
    ensure_material_tables();
    
    // Check if we can extract text (it's text/plain, so ocr_extract_text might fail if it only expects PDF/Image)
    // Let's check ocr_extract_text behavior for txt
    // ocr_extract_text only handles PDF and Image.
    // So sourceText will be empty from OCR.
    
    $sourceText = '';
    $extractedText = ocr_extract_text($filePath); // Should return '' for .txt
    echo "Extracted Text: '$extractedText'<br>";
    
    if ($extractedText !== '') {
        $sourceText = $extractedText;
    }
    
    // Fallback 1: Notes
    if ($sourceText === '' && $notes !== '') {
        $sourceText = $notes; // Should hit this
        echo "Using notes as source text.<br>";
    }
    
    // Fallback 2: Fallback text
    if ($sourceText === '') {
        $sourceText = build_fallback_text($course, $filePath);
        echo "Using fallback text.<br>";
    }
    
    echo "Final Source Text: $sourceText<br>";
    
    // Save Material
    $materialId = save_material($_SESSION['user_id'], $course, $filePath, $notes);
    echo "Material Saved ID: $materialId<br>";
    
    // Generate AI Content
    $notesLower = strtolower($notes);
    $doSummary = true; // Force all for test
    
    if ($doSummary) {
        $summary = generate_summary($sourceText, 5); // Use local for speed/reliability in test
        echo "Summary Generated: " . substr($summary, 0, 50) . "...<br>";
        if ($summary !== '') {
            save_summary($materialId, $summary);
            echo "Summary Saved.<br>";
        }
    }
    
    // Verify DB
    $savedMat = $pdo->query("SELECT * FROM materials WHERE id = $materialId")->fetch(PDO::FETCH_ASSOC);
    echo "<pre>Saved Material: " . print_r($savedMat, true) . "</pre>";
    
    $savedSum = $pdo->query("SELECT * FROM summaries WHERE material_id = $materialId")->fetch(PDO::FETCH_ASSOC);
    echo "<pre>Saved Summary: " . print_r($savedSum, true) . "</pre>";

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
