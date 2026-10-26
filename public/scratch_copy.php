<?php
header('Content-Type: text/plain; charset=utf-8');

$sourceFiles = [
    'media_1792873759978.png',
    'media_1792873760028.png',
    'media_1792873760442.png',
    'media_1792873760510.jpg',
    'media_1792873760525.jpg'
];

$srcDir = 'C:/Users/phon/.gemini/antigravity-ide/brain/638cdda1-fa61-4518-b310-bc16601b3cad/.user_uploaded';
$destDir = __DIR__ . '/platform/uploads';

foreach ($sourceFiles as $fn) {
    $srcPath = $srcDir . '/' . $fn;
    if (file_exists($srcPath)) {
        $content = file_get_contents($srcPath);
        $md5 = md5($content);
        $ext = pathinfo($fn, PATHINFO_EXTENSION);
        $destName = $md5 . '.' . $ext;
        $destPath = $destDir . '/' . $destName;
        file_put_contents($destPath, $content);
        $info = getimagesize($destPath);
        $dim = $info ? "{$info[0]}x{$info[1]}" : "unknown";
        echo "Copied: $fn -> $destName | $dim | " . strlen($content) . " bytes\n";
    } else {
        echo "Not found: $srcPath\n";
    }
}
