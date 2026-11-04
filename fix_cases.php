<?php
$directories = [
    'application/modules/default/models/',
    'application/modules/default/controllers/'
];

echo "Auto-fixing Linux case mismatches with Git...\n\n";

foreach ($directories as $dir) {
    if (!is_dir($dir)) continue;
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;
        
        $content = file_get_contents($dir . $file);
        
        if (preg_match('/class\s+([a-zA-Z0-9_]+)/', $content, $matches)) {
            $className = $matches[1];
            $parts = explode('_', $className);
            $expectedFilename = end($parts) . '.php';
            
            // Only rename if it's purely a CASE difference (not a different class)
            if ($file !== $expectedFilename && strtolower($file) === strtolower($expectedFilename)) {
                echo "Renaming: $file -> $expectedFilename\n";
                
                // Two-step rename so Windows Git doesn't complain about case collision
                $src = $dir . $file;
                $tmp = $dir . $file . '.tmp';
                $dst = $dir . $expectedFilename;
                
                shell_exec("git mv \"$src\" \"$tmp\"");
                shell_exec("git mv \"$tmp\" \"$dst\"");
            }
        }
    }
}

echo "\nDone! All case-sensitivity mismatches have been auto-fixed.\n";
