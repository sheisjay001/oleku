<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
requireLogin();

$uploadResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        setFlash('error', 'Security token mismatch. Please try again.');
        redirect(SITE_URL . '/university/index.php');
    }

    $course = sanitize($_POST['course'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    $pastedText = sanitize($_POST['pasted_text'] ?? '');
    
    // Debug logging
    $logFile = __DIR__ . '/../ai_debug.log';
    $fileInfo = isset($_FILES['material']) ? json_encode($_FILES['material']) : 'No file';
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Processing attempt. Course: $course. File: $fileInfo. Pasted Length: " . strlen($pastedText) . "\n", FILE_APPEND);
    
    $filePath = '';
    $uploadSuccess = false;
    
    // 1. Handle File Upload if present
    if (isset($_FILES['material']) && $_FILES['material']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadFile($_FILES['material'], __DIR__ . '/../uploads', ['image/jpeg','image/png','application/pdf','application/x-pdf','application/acrobat','applications/vnd.pdf','text/pdf','text/x-pdf','application/octet-stream'], 10);
        if ($uploadResult[0]) {
            $filePath = $uploadResult[2];
            $uploadSuccess = true;
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Upload successful: " . $filePath . "\n", FILE_APPEND);
        } else {
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Upload failed: " . $uploadResult[1] . "\n", FILE_APPEND);
            // Only fail hard if no pasted text
            if (empty($pastedText)) {
                setFlash('error', $uploadResult[1]);
                redirect(SITE_URL . '/university/index.php'); // Stop here
            }
        }
    } else {
        // No file uploaded
        if (empty($pastedText)) {
             setFlash('error', 'Please upload a file or paste content to process.');
             redirect(SITE_URL . '/university/index.php');
        }
    }

    try {
        ensure_material_tables();
        
        // Save Material (allow empty filePath if using pasted text)
        $materialId = save_material($_SESSION['user_id'], $course, $filePath, $notes);
        
        $didSomething = false;
        $sourceText = '';
        
        // 1. Prioritize Pasted Text
        if (!empty($pastedText)) {
            $sourceText = $pastedText;
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Using pasted text. Length: " . strlen($sourceText) . "\n", FILE_APPEND);
        }
        
        // 2. Extract from File (if exists) and append/use
        if ($uploadSuccess && file_exists($filePath)) {
            $extractedText = ocr_extract_text($filePath);
            if ($extractedText !== '') {
                if ($sourceText !== '') {
                    $sourceText .= "\n\n--- Content from File ---\n\n" . $extractedText;
                } else {
                    $sourceText = $extractedText;
                }
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Text extracted from file. Total Source Length: " . strlen($sourceText) . "\n", FILE_APPEND);
            } else {
                 file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "OCR returned empty for $filePath\n", FILE_APPEND);
                 if (empty($pastedText)) {
                     setFlash('warning', 'Could not read text from the uploaded file. Please try pasting the content directly.');
                 }
            }
        }
        
        // 3. Fallback: Notes (if no source text yet)
        if ($sourceText === '' && $notes !== '') {
            $sourceText = $notes;
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Using notes as source text.\n", FILE_APPEND);
        }
            
        if ($sourceText === '') {
            $sourceText = build_fallback_text($course, $filePath);
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Using fallback text. Length: " . strlen($sourceText) . "\n", FILE_APPEND);
            setFlash('info', 'We could not read text from your file. Results are based on the course name. Add notes for better results.');
        } else {
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Source text prepared. Length: " . strlen($sourceText) . "\n", FILE_APPEND);
        }

        // 3. Parse user notes for specific instructions/intent (with typo tolerance)
        $notesLower = strtolower($notes);
        $doSummary = false;
        $doExplanation = false;
        $doPractice = false;

        if (preg_match('/summ?ary|summa?ri[zs]e|notes/i', $notesLower)) {
            $doSummary = true;
        }
        if (preg_match('/explain|explana?tion|break down|simplif/i', $notesLower)) {
            $doExplanation = true;
        }
        if (preg_match('/practi[cs]e|question|quiz|test|exam|cbt/i', $notesLower)) {
            $doPractice = true;
        }

        // If no specific instruction found, do all (default behavior)
        if (!$doSummary && !$doExplanation && !$doPractice) {
            $doSummary = true;
            $doExplanation = true;
            $doPractice = true;
        }

        $summary = '';
        if ($doSummary) {
            $summary = ai_generate_summary($sourceText, $notes);
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Summary generated. Length: " . strlen($summary) . "\n", FILE_APPEND);
            
            if ($summary === '') {
                $summary = generate_summary($sourceText, 5);
            }
            if ($summary !== '') {
                $sid = save_summary($materialId, $summary);
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Summary saved. ID: $sid\n", FILE_APPEND);
                $didSomething = true;
            } else {
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Summary was empty after generation attempts.\n", FILE_APPEND);
            }
        }

        $expText = '';
        $points = [];
        if ($doExplanation) {
            $expText = ai_generate_explanation($sourceText, $notes);
            if ($expText !== '') {
                $lines = preg_split('/\r\n|\n|\r/u', $expText);
                $clean = [];
                foreach ($lines as $ln) {
                    $t = trim($ln);
                    if ($t !== '') $clean[] = $t;
                    if (count($clean) >= 6) break;
                }
                $points = $clean;
            }
            if (count($points) === 0) {
                $points = generate_explanations($sourceText, 6);
            }
            if (count($points) > 0) {
                $eid = save_explanations($materialId, $points);
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Explanations saved. ID: $eid\n", FILE_APPEND);
                $didSomething = true;
            } else {
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "No explanations generated.\n", FILE_APPEND);
            }
        }

        $qs = [];
        if ($doPractice) {
            $qs = ai_generate_practice_questions($sourceText, 6, '', $notes);
            if (!is_array($qs) || count($qs) === 0) {
                $qs = generate_practice_questions($sourceText, 6);
            }
            if (count($qs) > 0) {
                $qCount = save_practice_questions($materialId, $qs);
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Practice questions saved. Count: $qCount\n", FILE_APPEND);
                $didSomething = true;
            } else {
                 file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "No practice questions generated.\n", FILE_APPEND);
            }
        }
        if ($didSomething) {
            setFlash('success', 'Material uploaded and AI generated summary, explanations, and practice.');
        } else {
            setFlash('info', 'Material uploaded. Add detailed notes to generate AI content.');
        }
    } catch (Throwable $e) {
        log_message('University upload processing error: '.$e->getMessage(), 'ERROR');
        setFlash('error', 'Processing failed. Please try again with smaller files or add notes.');
    }
    // Redirect to the specific result if available
    if (isset($materialId) && $materialId) {
            session_write_close(); // Ensure session is saved before redirect
            redirect(SITE_URL . '/university/index.php?highlight=' . $materialId . '#result-' . $materialId);
        } else {
            session_write_close();
            redirect(SITE_URL . '/university/index.php#activities');
        }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University AI Assistant | <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo SITE_URL; ?>/assets/images/logo.svg">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0B2C4D',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#0B2C4D">
    
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
        /* Alert Styles Override/Enhancement */
        .alert { @apply p-4 mb-4 rounded-lg text-sm font-medium; }
        .alert-success { @apply bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400; }
        .alert-error { @apply bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400; }
        .alert-warning { @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400; }
        .alert-info { @apply bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 transition-colors duration-300" x-data="{ 
    darkMode: localStorage.getItem('darkMode') === 'true',
    mobileMenuOpen: false,
    toggleTheme() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode);
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}" x-init="$watch('darkMode', val => val ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')); if(darkMode) document.documentElement.classList.add('dark');">

    <!-- Navigation -->
    <nav class="bg-primary-900 text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="<?php echo SITE_URL; ?>/dashboard/" class="text-2xl font-bold tracking-tight flex items-center gap-2">
                <span class="bg-white text-primary-900 px-2 py-1 rounded-md text-lg">O</span> Oleku
            </a>
            
            <div class="flex items-center gap-6">
                <div class="hidden md:flex items-center gap-6 text-sm font-medium">
                    <a href="<?php echo SITE_URL; ?>/dashboard/" class="hover:text-primary-200 transition">Home</a>
                    <a href="<?php echo SITE_URL; ?>/jamb-subjects.php" class="hover:text-primary-200 transition">JAMB Prep</a>
                    <a href="<?php echo SITE_URL; ?>/university/index.php" class="text-white font-bold border-b-2 border-white pb-0.5">University</a>
                    <?php if (isAdmin()): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/" class="hover:text-primary-200 transition">Admin</a>
                    <?php endif; ?>
                    <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="bg-white/10 px-4 py-2 rounded-lg hover:bg-white/20 transition">Logout</a>
                </div>

                <!-- Dark Mode Toggle -->
                <button @click="toggleTheme()" class="p-2 rounded-full hover:bg-white/10 transition focus:outline-none" aria-label="Toggle Dark Mode">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </button>
                
                <!-- Mobile Menu Button -->
                <button class="md:hidden p-2 text-white focus:outline-none" @click="mobileMenuOpen = !mobileMenuOpen">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="md:hidden absolute top-full left-0 right-0 bg-primary-900 border-t border-white/10 shadow-xl z-50" @click.away="mobileMenuOpen = false" x-cloak>
            <div class="flex flex-col p-4 space-y-4 text-center">
                <a href="<?php echo SITE_URL; ?>/dashboard/" class="text-white hover:text-primary-200 transition py-2">Home</a>
                <a href="<?php echo SITE_URL; ?>/jamb-subjects.php" class="text-white hover:text-primary-200 transition py-2">JAMB Prep</a>
                <a href="<?php echo SITE_URL; ?>/university/index.php" class="text-white font-bold bg-white/10 rounded py-2">University</a>
                <?php if (isAdmin()): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/" class="text-white hover:text-primary-200 transition py-2">Admin</a>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="text-white hover:text-primary-200 transition py-2">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header class="bg-primary-900 text-white pb-24 pt-10 px-4 rounded-b-[3rem] shadow-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48ZyBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0wIDQwaDQwVjBIMHY0MHptMjAgMjBoMjBWMjBIMHYyMHpNNDAgNDBWMjBIMHYyMGg0MHoiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIi8+PC9nPjwvc3ZnPg==')]"></div>
        <div class="container mx-auto max-w-4xl text-center relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 !text-white">AI Study Assistant</h1>
            <p class="!text-blue-100 text-lg max-w-2xl mx-auto">Upload course materials and let our AI generate simplified explanations, summaries, and practice questions for you.</p>
            <div class="mt-6">
                 <?php displayFlash(); ?>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 max-w-5xl -mt-16 relative z-20 pb-16">
        
        <!-- DISPLAY HIGHLIGHTED RESULT IMMEDIATELY IF AVAILABLE -->
        <?php if (isset($_GET['highlight']) && is_numeric($_GET['highlight'])): ?>
            <?php
                $highlightId = (int)$_GET['highlight'];
                ensure_material_tables();
                $stmt = $pdo->prepare("
                    SELECT m.id AS material_id, m.course, m.file_path, m.notes, m.created_at,
                           s.summary_text,
                           e.content AS explanation,
                           (SELECT COUNT(*) FROM practice_questions pq WHERE pq.material_id = m.id) AS question_count
                    FROM materials m
                    LEFT JOIN summaries s ON s.material_id = m.id
                    LEFT JOIN explanations e ON e.material_id = m.id
                    WHERE m.id = ? AND m.user_id = ?
                ");
                $stmt->execute([$highlightId, $_SESSION['user_id']]);
                $newItem = $stmt->fetch();
            ?>
            <?php if ($newItem): ?>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 ring-4 ring-green-500 border-green-500 relative overflow-hidden">
                    <div class="flex justify-between items-center mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 p-2 rounded-lg">✅</span>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">New Result Ready</h2>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo time_ago($newItem['created_at']); ?></span>
                    </div>
                    
                    <h3 class="font-bold text-xl mb-4 text-primary-900 dark:text-primary-400"><?php echo htmlspecialchars($newItem['course'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    
                    <?php if (!empty($newItem['summary_text'])): ?>
                        <div class="mb-6 bg-gray-50 dark:bg-gray-700/50 p-5 rounded-xl border border-gray-100 dark:border-gray-700">
                            <h4 class="font-bold text-blue-600 dark:text-blue-400 mb-3 flex items-center gap-2">📝 Summary</h4>
                            <div class="text-gray-800 dark:text-gray-200 leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($newItem['summary_text'], ENT_QUOTES, 'UTF-8')); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($newItem['explanation'])): ?>
                        <div class="mb-6 bg-gray-50 dark:bg-gray-700/50 p-5 rounded-xl border border-gray-100 dark:border-gray-700">
                            <h4 class="font-bold text-indigo-600 dark:text-indigo-400 mb-3 flex items-center gap-2">📚 Explanations</h4>
                            <div class="text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-line">
                                <?php echo htmlspecialchars($newItem['explanation'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ((int)$newItem['question_count'] > 0): ?>
                        <div class="mb-6 bg-gray-50 dark:bg-gray-700/50 p-5 rounded-xl border border-gray-100 dark:border-gray-700">
                            <h4 class="font-bold text-purple-600 dark:text-purple-400 mb-3 flex items-center gap-2">❓ Practice Questions (<?php echo $newItem['question_count']; ?>)</h4>
                            <?php $previewQs = get_practice_questions_for_material($newItem['material_id'], 10); ?>
                            <div class="space-y-6">
                            <?php foreach ($previewQs as $idx => $pq): ?>
                                <div class="border-b border-gray-200 dark:border-gray-600 pb-4 last:border-0">
                                    <div class="font-medium text-gray-900 dark:text-white mb-2"><?php echo ($idx+1) . '. ' . htmlspecialchars($pq['question'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="ml-4 text-sm text-gray-700 dark:text-gray-300 grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2">
                                        <div>A) <?php echo htmlspecialchars($pq['option_a'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div>B) <?php echo htmlspecialchars($pq['option_b'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div>C) <?php echo htmlspecialchars($pq['option_c'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div>D) <?php echo htmlspecialchars($pq['option_d'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                    <div class="ml-4 mt-2 text-xs font-bold text-green-600 dark:text-green-400">
                                        Answer: <?php echo !empty($pq['answer']) ? htmlspecialchars($pq['answer'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($newItem['summary_text']) && empty($newItem['explanation']) && (int)$newItem['question_count'] === 0): ?>
                        <div class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 p-4 rounded-lg">
                            No specific AI content was generated. This might be because the file text was empty or the AI couldn't process it.
                            <br>Try pasting the text directly in the form below.
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-6">
                        <a href="<?php echo SITE_URL; ?>/university/index.php" class="text-green-700 dark:text-green-400 hover:underline font-medium">Clear Result</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 md:p-8 mb-12 border border-gray-100 dark:border-gray-700">
            <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Create New Study Material</h2>
            <form method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                
                <div class="mb-6">
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Course / Subject Name</label>
                    <input class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition" type="text" name="course" placeholder="e.g., CHM 201 Organic Chemistry" required>
                </div>

                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Upload Material (PDF, Images)</label>
                        <input class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100" type="file" name="material" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-gray-500 dark:text-gray-400 mt-1 block">Optional if pasting text.</small>
                    </div>
                    <div>
                        <label class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Instructions (Optional)</label>
                        <input class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition" type="text" name="notes" placeholder="e.g., 'Summarize chapter 3', 'Create hard quiz'">
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Or Paste Content Directly</label>
                    <textarea class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition" name="pasted_text" rows="6" placeholder="Paste your study material here (e.g. lecture notes, essay, article)"></textarea>
                </div>

                <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-primary-600/30 transition transform hover:-translate-y-0.5">
                    Process with AI
                </button>
            </form>
        </div>

        <!-- Features Grid -->
        <div class="grid md:grid-cols-3 gap-6 mb-12">
            <div class="text-center p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="text-4xl mb-4">📝</div>
                <h3 class="text-lg font-bold mb-2 text-gray-900 dark:text-white">Smart Summaries</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Get concise, easy-to-read notes generated instantly from your uploads.</p>
            </div>
            <div class="text-center p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="text-4xl mb-4">📚</div>
                <h3 class="text-lg font-bold mb-2 text-gray-900 dark:text-white">Clear Explanations</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Step-by-step breakdowns of complex topics to help you understand better.</p>
            </div>
            <div class="text-center p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="text-4xl mb-4">❓</div>
                <h3 class="text-lg font-bold mb-2 text-gray-900 dark:text-white">Practice Quizzes</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Test your knowledge with AI-generated questions tailored to your material.</p>
            </div>
        </div>

        <!-- Recent Activities -->
        <section id="activities">
            <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Recent Activities</h2>
            <?php
                ensure_material_tables();
                $recent = get_recent_activities($_SESSION['user_id'], 10);
            ?>
            <?php if (count($recent) === 0): ?>
                <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl text-center shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="text-gray-600 dark:text-gray-400">No activities yet. Upload material above to get started!</p>
                </div>
            <?php else: ?>
                <div class="grid gap-6">
                    <?php foreach ($recent as $item): ?>
                        <?php $isNew = (isset($_GET['highlight']) && $_GET['highlight'] == $item['material_id']); ?>
                        <div id="result-<?php echo $item['material_id']; ?>" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700 hover:shadow-md transition <?php echo $isNew ? 'ring-2 ring-blue-500 border-blue-500' : ''; ?>">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-3">
                                    <h3 class="font-bold text-lg text-gray-900 dark:text-white"><?php echo htmlspecialchars($item['course'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <?php if ($isNew): ?>
                                        <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-0.5 rounded-full">New</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm"><?php echo time_ago($item['created_at']); ?></div>
                            </div>
                            
                            <?php if (!empty($item['summary_text'])): ?>
                                <div class="mb-3">
                                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Summary Preview</div>
                                    <div class="text-gray-700 dark:text-gray-300 text-sm line-clamp-3">
                                        <?php 
                                        $summaryDisplay = $item['summary_text'];
                                        echo strip_tags(htmlspecialchars($summaryDisplay, ENT_QUOTES, 'UTF-8')); 
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                                <a href="<?php echo SITE_URL; ?>/university/index.php?highlight=<?php echo $item['material_id']; ?>#result-<?php echo $item['material_id']; ?>" class="text-primary-600 dark:text-primary-400 font-medium hover:text-primary-700 dark:hover:text-primary-300 text-sm flex items-center gap-1">
                                    View Full Result
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <footer class="bg-primary-900 text-white/60 py-8 border-t border-white/10 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Oleku. Built for Excellence.</p>
        </div>
    </footer>
</body>
</html>
