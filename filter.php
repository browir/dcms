<?php
$content = file_get_contents('emojis_found.txt');
foreach (explode("\n", $content) as $line) {
    if (trim($line) !== '' && strpos($line, '─') === false && strpos($line, '═') === false && strpos($line, '→') === false && strpos($line, '✔') === false) {
        echo $line . "\n";
    }
}
