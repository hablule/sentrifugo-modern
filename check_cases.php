<?php
$directories = [
    'application/modules/default/models/',
    'application/modules/default/controllers/'
];

echo "Scanning for Linux Case-Sensitivity Mismatches...\n\n";
$mismatches = 0;

foreach ($directories as $dir) {
    if (!is_dir($dir)) continue;
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;
        
        $content = file_get_contents($dir . $file);
        
        // Find the class name inside the file
        if (preg_match('/class\s+([a-zA-Z0-9_]+)/', $content, $matches)) {
            $className = $matches[1];
            
            // Zend expects the filename to be the last part of the class name + .php
            $parts = explode('_', $className);
            $expectedFilename = end($parts) . '.php';
            
            // If the actual filename doesn't match the expected filename EXACTLY (case-sensitive)
            if ($file !== $expectedFilename) {
                echo "❌ CRASH RISK IN: $dir\n";
                echo "   Physical File : $file\n";
                echo "   Zend Expects  : $expectedFilename\n";
                echo "   (Run: git mv \"$dir$file\" \"$dir$expectedFilename\")\n\n";
                $mismatches++;
            }
        }
    }
}

if ($mismatches === 0) {
    echo "✅ PERFECT! No case-sensitivity mismatches found!\n";
} else {
    echo "Found $mismatches mismatches. Fix them using the git mv commands above.\n";
}
