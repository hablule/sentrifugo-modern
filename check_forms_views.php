<?php
echo "Auditing Forms and View Folders for Linux Compatibility...\n\n";
$mismatches = 0;

// 1. Audit Forms (Class name vs File name)
$formsDir = 'application/modules/default/forms/';
if (is_dir($formsDir)) {
    foreach (scandir($formsDir) as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;
        
        $content = file_get_contents($formsDir . $file);
        if (preg_match('/^\s*class\s+([a-zA-Z0-9_]+)/m', $content, $matches)) {
            $parts = explode('_', $matches[1]);
            $expected = end($parts) . '.php';
            
            if ($file !== $expected) {
                echo "❌ FORM MISMATCH:\n   File: $formsDir$file\n   Zend Expects: $expected\n\n";
                $mismatches++;
            }
        }
    }
}

// 2. Audit View Script Folders (Must match lowercase Controller names)
$viewsDir = 'application/modules/default/views/scripts/';
if (is_dir($viewsDir)) {
    foreach (scandir($viewsDir) as $folder) {
        if ($folder === '.' || $folder === '..' || !is_dir($viewsDir . $folder)) continue;
        
        // In Zend 1, view folders should be lowercase
        if ($folder !== strtolower($folder)) {
            echo "❌ VIEW FOLDER HAS UPPERCASE (Linux Risk):\n   Folder: $viewsDir$folder\n   Should be: " . strtolower($folder) . "\n\n";
            $mismatches++;
        }
        
        // Check for backup copies inside view folders
        foreach (scandir($viewsDir . $folder) as $viewFile) {
            if (str_contains($viewFile, 'Copy') || str_contains($viewFile, 'backup')) {
                echo "⚠️ JUNK BACKUP VIEW FILE:\n   File: $viewsDir$folder/$viewFile\n\n";
                $mismatches++;
            }
        }
    }
}

if ($mismatches === 0) {
    echo "✅ PERFECT! All forms and view folders are 100% Linux compliant!\n";
} else {
    echo "Found $mismatches items to review.\n";
}
