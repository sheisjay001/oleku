<?php
// includes/ai_helper.php

// Groq Configuration
// define('GROQ_API_KEY', '...'); // Moved to config/secrets.php

if (!function_exists('groq_call')) {
    function groq_call($prompt, $model = 'llama-3.3-70b-versatile') {
        $apiKey = defined('GROQ_API_KEY') ? constant('GROQ_API_KEY') : getenv('GROQ_API_KEY');
        if (!$apiKey) return ['', 'missing_groq_key'];

        $url = 'https://api.groq.com/openai/v1/chat/completions';
        $body = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful study assistant. Return only the requested content without conversational filler.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        // Set a timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code < 200 || $code >= 300 || $resp === false) {
            return ['', 'http_' . $code . '_groq_error'];
        }

        $json = json_decode($resp, true);
        $text = $json['choices'][0]['message']['content'] ?? '';
        return [$text, null];
    }
}

if (!function_exists('groq_ocr_call')) {
    function groq_ocr_call($mime, $b64) {
        $apiKey = defined('GROQ_API_KEY') ? constant('GROQ_API_KEY') : getenv('GROQ_API_KEY');
        if (!$apiKey) return ['', 'missing_groq_key'];

        $url = 'https://api.groq.com/openai/v1/chat/completions';
        $body = json_encode([
            'model' => 'llama-3.2-11b-vision-preview',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'Extract readable text from this image. Return plain text only.'],
                        ['type' => 'image_url', 'image_url' => ['url' => "data:$mime;base64,$b64"]]
                    ]
                ]
            ],
            'temperature' => 0.2
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code < 200 || $code >= 300 || $resp === false) {
            return ['', 'http_' . $code . '_groq_error'];
        }

        $json = json_decode($resp, true);
        $text = $json['choices'][0]['message']['content'] ?? '';
        return [$text, null];
    }
}

if (!function_exists('perform_ai_task')) {
    function perform_ai_task($task, $payload) {
        $logFile = __DIR__ . '/../ai_debug.log';
        
        if ($task === 'summary') {
            $text = $payload['text'] ?? '';
            $instructions = $payload['instructions'] ?? '';
            $prompt = "Summarize clearly and simply for a university student:\n\n" . $text;
            if (!empty($instructions)) {
                $prompt = "User Specific Instructions: $instructions\n\nTask: Summarize clearly and simply for a university student based on the instructions and content below:\n\n" . $text;
            }
            [$out, $err] = groq_call($prompt);
            if ($err) return ['ok'=>false, 'error'=>$err];
            return ['ok'=>true, 'data'=>['summary'=>$out]];
        }

        if ($task === 'explanation') {
            $text = $payload['text'] ?? '';
            $instructions = $payload['instructions'] ?? '';
            $prompt = "Explain step-by-step in simple, easy-to-understand bullets (max 8 bullets):\n\n" . $text;
            if (!empty($instructions)) {
                $prompt = "User Specific Instructions: $instructions\n\nTask: Explain step-by-step in simple, easy-to-understand bullets (max 8 bullets) based on the instructions and content below:\n\n" . $text;
            }
            [$out, $err] = groq_call($prompt);
            if ($err) return ['ok'=>false, 'error'=>$err];
            return ['ok'=>true, 'data'=>['explanation'=>$out]];
        }

        if ($task === 'practice_questions') {
            $topic = $payload['topic'] ?? '';
            $subject = $payload['subject'] ?? '';
            $instructions = $payload['instructions'] ?? '';
            $count = (int)($payload['count'] ?? 10);

            $books = [
                'English' => 'The Invisible Teacher, A - Z Use of English',
                'Chemistry' => 'New School Chemistry by Ababio',
                'Physics' => 'New School Physics by P. n Okeke',
                'Biology' => 'Modern Biology Text Book',
                'Mathematics' => 'Comprehensive Mathematics',
                'Government' => 'Comprehensive Government For Secondary Schools',
                'Literature' => 'Practical Guild in literature/2025 JAMB Literature Novels',
                'CRS' => 'Essential CRS along with the Bible',
                'Economics' => 'Fundamental Principles of Economics'
            ];

            $bookRef = '';
            if ($subject && isset($books[$subject])) {
                $bookRef = " strictly based on the content, style, and standard of '{$books[$subject]}'";
            }

            $schema = <<<EOT
Return a valid JSON array of {$count} items (no markdown formatting, just raw JSON), each with:
- question: string
- options: array of 4 strings
- answer: one of "A","B","C","D" (MUST be a single letter)
EOT;
            
            // Log request
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Requesting questions for topic length: " . strlen($topic) . " Subject: $subject\n", FILE_APPEND);

            $prompt = "Create comprehensive practice questions suitable for university students based on the following content: '{$topic}'. {$schema}";
            if (!empty($subject)) {
                $prompt = "Create JAMB UTME standard multiple-choice questions for '{$topic}'{$bookRef}. {$schema}";
            }
            
            if (!empty($instructions)) {
                $prompt = "User Instructions: $instructions. \n\n" . $prompt;
            }

            [$out, $err] = groq_call($prompt);
            
            // Log raw response
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Raw AI Response: " . substr($out, 0, 1000) . "...\n", FILE_APPEND);

            if ($err) { 
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Error: $err\n", FILE_APPEND);
                return ['ok'=>false, 'error'=>$err];
            }
            
            // Clean up markdown code blocks if present
            $out = trim($out);
            // Try to extract JSON array using regex if not immediately valid
            if (strpos($out, '[') !== 0 && preg_match('/\[.*\]/s', $out, $matches)) {
                $cleanOut = $matches[0];
            } else {
                // Remove markdown code blocks if they wrap the content
                $cleanOut = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $out);
            }
            
            $questions = json_decode($cleanOut, true);
            if (!is_array($questions)) {
                // Fallback to original output just in case extraction failed but it was valid
                $questions = json_decode($out, true);
            }
            if (!is_array($questions)) {
                // Last ditch effort: try to fix common JSON errors (optional, but let's log error first)
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "JSON Decode Failed. Output: " . substr($out, 0, 200) . "...\n", FILE_APPEND);
                $questions = [];
            }

            // Normalize keys
            foreach ($questions as &$q) {
                $q = array_change_key_case($q, CASE_LOWER);
                if (!isset($q['answer'])) {
                     if (isset($q['correct'])) $q['answer'] = $q['correct'];
                     elseif (isset($q['correctoption'])) $q['answer'] = $q['correctoption'];
                     elseif (isset($q['correct_answer'])) $q['answer'] = $q['correct_answer'];
                }
                
                // Validate answer is A, B, C, or D
                if (isset($q['answer'])) {
                    $q['answer'] = strtoupper(trim($q['answer']));
                    // If answer is the full string, map it to A/B/C/D
                    if (strlen($q['answer']) > 1 && isset($q['options']) && is_array($q['options'])) {
                         $idx = -1;
                         foreach ($q['options'] as $i => $opt) {
                             if (strcasecmp(trim($opt), $q['answer']) === 0) {
                                 $idx = $i;
                                 break;
                             }
                         }
                         if ($idx >= 0) {
                             $q['answer'] = ['A','B','C','D'][$idx];
                         }
                    }
                }
            }
            unset($q);

            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Parsed " . count($questions) . " questions.\n", FILE_APPEND);

            return ['ok'=>true, 'data'=>['questions'=>$questions]];
        }

        if ($task === 'ocr') {
            $mime = $payload['mime'] ?? '';
            $b64 = $payload['data'] ?? '';
            if ($mime === '' || $b64 === '') return ['ok'=>false, 'error'=>'bad_payload'];
            [$out, $err] = groq_ocr_call($mime, $b64);
            if ($err) return ['ok'=>false, 'error'=>$err];
            return ['ok'=>true, 'data'=>['text'=>$out]];
        }
        
        return ['ok'=>false, 'error'=>'unknown_task'];
    }
}
?>
