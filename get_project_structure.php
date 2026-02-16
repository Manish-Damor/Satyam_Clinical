<?php

// Folders to ignore (predefined / library folders)
$ignoreFolders = [
    'node_modules',
    'bootstrap',
    'plugins',
    'fonts',
    'images',
    '.git'
];

// File extensions you care about (custom code)
$allowedExtensions = ['php', 'js', 'css'];

// Recursive function
function scanProject($dir, $ignoreFolders, $allowedExtensions, $level = 0)
{
    $files = scandir($dir);

    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;

        $fullPath = $dir . DIRECTORY_SEPARATOR . $file;

        // Ignore unwanted folders
        if (is_dir($fullPath) && in_array($file, $ignoreFolders)) {
            continue;
        }

        echo str_repeat("│   ", $level);

        if (is_dir($fullPath)) {
            echo "├── 📁 $file\n";
            scanProject($fullPath, $ignoreFolders, $allowedExtensions, $level + 1);
        } else {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            if (in_array($extension, $allowedExtensions)) {
                echo "├── 📄 $file\n";
            }
        }
    }
}

// Run from current folder
ob_start();
echo "Satyam_Clinical/\n";
scanProject(__DIR__, $ignoreFolders, $allowedExtensions);
$content = ob_get_clean();

file_put_contents("clean_structure.txt", $content);

echo "Structure saved in clean_structure.txt";


?>
