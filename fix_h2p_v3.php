<?php
$dir = __DIR__ . '/app/Helpers/h2p';
$files = scandir($dir);

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;
    $fullPath = $dir . '/' . $file;
    $content = file_get_contents($fullPath);
    
    // Improved regex to catch $var{idx}, $this->prop{idx}, $array[idx]{idx}
    // It looks for a sequence starting with $ followed by alphanum, ->, [], etc.
    $newContent = preg_replace('/(\$[a-zA-Z0-9_]+(?:->[a-zA-Z0-9_]+|\[[^\]]+\])*)\{([^}]+)\}/', '$1[$2]', $content);
    
    // In case there are multiple nested ones, we can loop
    $it = 0;
    while ($it < 5) {
        $lastContent = $newContent;
        $newContent = preg_replace('/(\$[a-zA-Z0-9_]+(?:->[a-zA-Z0-9_]+|\[[^\]]+\]|\[[^\]]+\])*)\{([^}]+)\}/', '$1[$2]', $newContent);
        if ($newContent === $lastContent) break;
        $it++;
    }

    // Specifically fix gif.php undefined variable $gbColor
    if ($file === 'gif.php') {
        $newContent = str_replace('$gbColor', '$bgColor', $newContent);
    }
    
    if ($newContent !== $content) {
        file_put_contents($fullPath, $newContent);
        echo "Fixed $file\n";
    } else {
        echo "No changes in $file\n";
    }
}
echo "Done.";
