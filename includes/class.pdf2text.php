<?php
class PdfToText {
    public static function getText($filename) {
        $infile = @file_get_contents($filename, FILE_BINARY);
        if (empty($infile)) return "";
        
        $transformations = array();
        $texts = array();

        // Get all objects
        preg_match_all("#obj(.*)endobj#ismU", $infile, $objects);
        $objects = @$objects[1];

        foreach ($objects as $object) {
            // Check for stream
            if (preg_match("#stream(.*)endstream#ismU", $object, $stream)) {
                $stream = ltrim($stream[1]);
                $options = self::getObjectOptions($object);
                
                $data = $stream;
                if (isset($options["Filter"]) && $options["Filter"] == "FlateDecode") {
                    if (function_exists('gzuncompress')) {
                        $o = @gzuncompress($data);
                        if ($o !== false) $data = $o;
                    }
                }

                // Extract text
                $texts = array_merge($texts, self::extractTextFromStream($data));
            }
        }

        return implode(" ", $texts);
    }

    private static function getObjectOptions($object) {
        $options = array();
        if (preg_match("#<<(.*)>>#ismU", $object, $options)) {
            $options = explode("/", $options[1]);
            @array_shift($options);
            $o = array();
            for ($i = 0; $i < @count($options); $i++) {
                $options[$i] = preg_replace("#\s+#", " ", trim($options[$i]));
                if (strpos($options[$i], " ") !== false) {
                    $parts = explode(" ", $options[$i]);
                    $o[$parts[0]] = $parts[1];
                } else {
                    $o[$options[$i]] = true;
                }
            }
            $options = $o;
        }
        return $options;
    }

    private static function extractTextFromStream($data) {
        $texts = [];
        // Extract text blocks between BT and ET
        if (preg_match_all("#BT(.*)ET#ismU", $data, $textContainers)) {
            foreach ($textContainers[1] as $textContainer) {
                // Handle Tj (Show text)
                if (preg_match_all("#\((.*)\)\s*Tj#ismU", $textContainer, $matches)) {
                    foreach ($matches[1] as $m) $texts[] = self::cleanText($m);
                }
                // Handle TJ (Show text array)
                if (preg_match_all("#\[(.*)\]\s*TJ#ismU", $textContainer, $matches)) {
                    foreach ($matches[1] as $m) {
                        // Extract strings in parentheses from the array
                        if (preg_match_all("#\((.*)\)#ismU", $m, $submatches)) {
                            foreach ($submatches[1] as $sm) $texts[] = self::cleanText($sm);
                        }
                    }
                }
                // Handle Td/TD (Move text position) - just treat as separator
            }
        }
        
        // Fallback: Just look for (text) if BT/ET failing
        if (empty($texts)) {
             if (preg_match_all("#\((.*)\)#ismU", $data, $matches)) {
                 foreach ($matches[1] as $m) $texts[] = self::cleanText($m);
             }
        }
        
        return $texts;
    }

    private static function cleanText($text) {
        // Handle escapes
        $text = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $text);
        // Remove non-printable
        //$text = preg_replace('/[^a-zA-Z0-9\s,\.\-\n\r]/', '', $text);
        return trim($text);
    }
}
