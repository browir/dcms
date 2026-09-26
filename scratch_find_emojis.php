<?php
$dirs = ['app', 'resources'];
foreach ($dirs as $d) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d));
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getPathname(), 'vendor') === false) {
            $content = file_get_contents($file->getPathname());
            if (preg_match_all('/[\x{203C}-\x{3299}\x{1F000}-\x{1F9FF}]/u', $content, $matches)) {
                foreach (explode("\n", $content) as $lineNum => $line) {
                    if (preg_match('/[\x{203C}-\x{3299}\x{1F000}-\x{1F9FF}]/u', $line)) {
                        echo $file->getPathname() . ':' . ($lineNum + 1) . ' ' . trim($line) . "\n";
                    }
                }
            }
        }
    }
}
