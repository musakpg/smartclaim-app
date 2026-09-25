<?php
$directories = [
    'resources/views/manager',
    'resources/views/finance'
];

$updatedCount = 0;

foreach ($directories as $dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
            $path = $file->getPathname();
            $content = file_get_contents($path);
            
            // Regex to find the header block
            // Looks for <div class="... border-b border-slate-200 pb-5 ...">
            // followed by <h1 ...>...</h1>
            // and <p ...>...</p>
            // and closing </div>
            
            $pattern = '/(<div[^>]*class="[^"]*border-b border-slate-200 pb-5[^"]*"[^>]*>\s*)(<h1.*?(?:<\/h1>|<h1\b).*?<\/p>\s*|.*?<\/p>\s*)(<\/div>)/is';
            
            if (preg_match($pattern, $content, $matches)) {
                $div_open = $matches[1];
                $inner = $matches[2];
                $div_close = $matches[3];
                
                if (strpos($content, '<x-system-clock') === false) {
                    if (strpos($div_open, 'flex') === false) {
                        $div_open = str_replace('pb-5"', 'pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4"', $div_open);
                    }
                    
                    $new_inner = "                    <div>\n                        " . trim($inner) . "\n                    </div>\n                    <div class=\"hidden lg:flex items-center gap-3\">\n                        <x-system-clock />\n                    </div>\n                ";
                    
                    $replacement = $div_open . $new_inner . $div_close;
                    
                    $new_content = preg_replace($pattern, $replacement, $content, 1);
                    
                    file_put_contents($path, $new_content);
                    echo "Updated: $path\n";
                    $updatedCount++;
                }
            }
        }
    }
}
echo "Total updated: $updatedCount\n";
