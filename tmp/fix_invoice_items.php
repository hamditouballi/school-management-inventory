<?php
$file = 'app/Http/Controllers/Api/InvoiceController.php';
$content = file_get_contents($file);

// More flexible regex
$newContent = preg_replace(
    "/'item_name'\s*=>\s*\\\$itemData\['item_name'\],?/",
    "'item_name' => \$itemData['item_name'] ?? (\\App\\Models\\Item::find(\$itemData['item_id'] ?? null)?->designation ?? 'Unknown Item'),",
    $content
);

if ($content !== $newContent) {
    file_put_contents($file, $newContent);
    echo "Successfully updated InvoiceController.php\n";
} else {
    echo "No matches found in InvoiceController.php\n";
}
