<?php
// Command-line fix for 500 error
// Run: php fix_500_cli.php from the EspoCRM root directory

echo "=== EspoCRM 500 Error Fix (CLI) ===\n\n";

// Step 1: Clear cache manually
echo "Step 1: Clearing cache...\n";
$cacheDir = 'data/cache';
if (is_dir($cacheDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        @$todo($fileinfo->getRealPath());
    }
    echo "✓ Cache cleared\n";
} else {
    echo "✗ Cache directory not found\n";
}

// Step 2: Fix permissions
echo "\nStep 2: Fixing permissions...\n";
@chmod('data', 0755);
@chmod('data/cache', 0755);
@chmod('data/logs', 0755);
@chmod('data/upload', 0755);
echo "✓ Permissions set\n";

// Step 3: Remove problematic fields from layouts
echo "\nStep 3: Cleaning layout files...\n";
$layoutsToCheck = [
    'custom/Espo/Custom/Resources/layouts/User/detail.json',
    'custom/Espo/Custom/Resources/layouts/User/edit.json',
    'custom/Espo/Custom/Resources/layouts/User/list.json',
    'custom/Espo/Custom/Resources/layouts/Opportunity/detail.json',
    'custom/Espo/Custom/Resources/layouts/Opportunity/list.json'
];

$droppedFields = [
    'home_publication_id_id',
    'c_home_publication_i_d',
    'c_legacy_lead_id',
    'c_legacy_company_id',
    'c_legacy_staff_id',
    'c_legacy_publication_id',
    'c_publications_id'
];

foreach ($layoutsToCheck as $layoutFile) {
    if (file_exists($layoutFile)) {
        $content = file_get_contents($layoutFile);
        $modified = false;
        
        foreach ($droppedFields as $field) {
            if (strpos($content, $field) !== false) {
                echo "  - Found '$field' in $layoutFile\n";
                $layoutData = json_decode($content, true);
                if ($layoutData) {
                    $cleaned = removeFieldFromLayout($layoutData, $field);
                    file_put_contents($layoutFile, json_encode($cleaned, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    $modified = true;
                }
            }
        }
        
        if ($modified) {
            echo "  ✓ Cleaned $layoutFile\n";
        }
    }
}

echo "\n=== Fix Complete ===\n";
echo "1. Try accessing EspoCRM in your browser\n";
echo "2. If still broken, check data/logs/ for errors\n";
echo "3. Run populate_missing_legacy_ids.php after system is working\n";

function removeFieldFromLayout($layout, $fieldToRemove) {
    if (is_array($layout)) {
        $cleaned = [];
        foreach ($layout as $key => $value) {
            if (is_array($value)) {
                if (isset($value['name']) && $value['name'] === $fieldToRemove) {
                    continue;
                }
                $cleaned[$key] = removeFieldFromLayout($value, $fieldToRemove);
            } else {
                if ($value !== $fieldToRemove) {
                    $cleaned[$key] = $value;
                }
            }
        }
        return array_values(array_filter($cleaned));
    }
    return $layout;
} 