<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhoneUploadController extends Controller
{
    private $uploadDir = 'phone-uploads';

    public function showPage(string $context, int|string $targetId)
    {
        // Handle "new" as a placeholder for new items
        if ($targetId === 'new' || $targetId === '') {
            $targetId = 0;
        } else {
            $targetId = (int) $targetId;
        }
        $contexts = $this->getValidContexts();

        if (! isset($contexts[$context])) {
            return response()->json(['error' => 'Invalid context'], 400);
        }

        // Get the session key from query param (passed from PC) or use phone's own session
        $sessionKey = request()->query('session', session()->getId());

        return view('phone-upload.index', [
            'context' => $context,
            'targetId' => $targetId,
            'contextLabelKey' => $contexts[$context]['label'],
            'targetLabel' => $this->getTargetLabel($context, $targetId),
            'sessionKey' => $sessionKey,
        ]);
    }

    public function upload(Request $request)
    {
        // Check PHP limits
        $maxUpload = ini_get('upload_max_filesize');
        $maxPost = ini_get('post_max_size');

        // Log raw request info
        \Log::info('Phone upload request', [
            'target_id' => $request->input('target_id'),
            'has_file' => $request->hasFile('image'),
            'file_image' => $request->file('image'),
            'files_keys' => array_keys($request->files->all()),
            'php_max_upload' => $maxUpload,
            'php_max_post' => $maxPost,
            'content_type' => $request->header('Content-Type'),
        ]);

        // Manual validation - bypass Laravel's file validation
        $sessionKey = $request->input('session_key');
        $context = $request->input('context');
        $targetId = $request->input('target_id');

        if (! $sessionKey || ! $context || ! $targetId === null) {
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        // Check if file was uploaded via PHP's raw global
        if (! isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            return response()->json(['error' => 'No file uploaded', 'files_detail' => $_FILES], 400);
        }

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            ];
            $errMsg = $errors[$_FILES['image']['error']] ?? 'Unknown upload error';

            return response()->json(['error' => $errMsg, 'upload_error_code' => $_FILES['image']['error']], 400);
        }

        // Manually process the uploaded file
        $file = $request->file('image');
        if (! $file || ! $file->isValid()) {
            return response()->json(['error' => 'Invalid file'], 400);
        }

        $contexts = $this->getValidContexts();
        $context = $request->input('context');

        if (! isset($contexts[$context])) {
            return response()->json(['error' => 'Invalid context'], 400);
        }

        $sessionKey = $request->input('session_key');
        $targetId = $request->input('target_id');

        // Store the file with session key as folder
        $path = $request->file('image')->storeAs(
            "{$this->uploadDir}/{$sessionKey}",
            "{$context}_{$targetId}_".time().'.'.$request->file('image')->getClientOriginalExtension(),
            'public'
        );

        $uploadData = [
            'id' => Str::uuid()->toString(),
            'session_key' => $sessionKey,
            'context' => $context,
            'target_id' => $targetId,
            'file_path' => $path,
            'file_name' => $request->file('image')->getClientOriginalName(),
            'created_at' => now()->toIso8601String(),
        ];

        // Store metadata in a JSON file for easy polling
        $metaFile = storage_path("app/public/{$this->uploadDir}/{$sessionKey}_meta.json");
        $meta = [];
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true) ?: [];
        }
        $meta[] = $uploadData;
        file_put_contents($metaFile, json_encode($meta));

        return response()->json([
            'success' => true,
            'message' => 'Upload successful',
            'upload_id' => $uploadData['id'],
        ]);
    }

    public function getUploads(string $sessionKey)
    {
        $metaFile = storage_path("app/public/{$this->uploadDir}/{$sessionKey}_meta.json");

        if (! file_exists($metaFile)) {
            return response()->json(['uploads' => []]);
        }

        $meta = json_decode(file_get_contents($metaFile), true) ?: [];

        $uploads = array_filter($meta, function ($item) use ($sessionKey) {
            return $item['session_key'] === $sessionKey && ! ($item['retrieved'] ?? false);
        });

        // Add full URLs
        foreach ($uploads as &$upload) {
            $upload['url'] = asset('storage/'.$upload['file_path']);
        }

        return response()->json(['uploads' => array_values($uploads)]);
    }

    public function markAsReceived(string $uploadId, Request $request)
    {
        $sessionKey = $request->input('session_key');
        $metaFile = storage_path("app/public/{$this->uploadDir}/{$sessionKey}_meta.json");

        if (! file_exists($metaFile)) {
            return response()->json(['error' => 'No uploads found'], 404);
        }

        $meta = json_decode(file_get_contents($metaFile), true) ?: [];

        foreach ($meta as &$item) {
            if ($item['id'] === $uploadId) {
                $item['retrieved'] = true;
                $item['retrieved_at'] = now()->toIso8601String();
            }
        }

        file_put_contents($metaFile, json_encode($meta));

        return response()->json(['success' => true]);
    }

    public function promoteToMain(Request $request)
    {
        $uploadId = $request->input('upload_id');
        $sessionKey = $request->input('session_key');
        $context = $request->input('context');

        $metaFile = storage_path("app/public/{$this->uploadDir}/{$sessionKey}_meta.json");

        if (! file_exists($metaFile)) {
            return response()->json(['error' => 'No uploads found'], 404);
        }

        $meta = json_decode(file_get_contents($metaFile), true) ?: [];
        $uploadData = null;

        foreach ($meta as &$item) {
            if ($item['id'] === $uploadId) {
                $uploadData = $item;
                $item['retrieved'] = true;
                $item['retrieved_at'] = now()->toIso8601String();
                $item['promoted'] = true;
                $item['promoted_at'] = now()->toIso8601String();
                break;
            }
        }

        if (! $uploadData) {
            return response()->json(['error' => 'Upload not found'], 404);
        }

        // Read the original file
        $originalPath = storage_path('app/public/'.$uploadData['file_path']);
        if (! file_exists($originalPath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        // Generate new filename in main storage
        $extension = pathinfo($uploadData['file_name'], PATHINFO_EXTENSION);
        $newFilename = $context.'_'.time().'_'.Str::random(8).'.'.$extension;
        $newPath = 'uploads/'.$newFilename;

        // Copy to main storage
        $newFullPath = storage_path('app/public/'.$newPath);
        $dir = dirname($newFullPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        copy($originalPath, $newFullPath);

        // Delete original from phone-uploads
        unlink($originalPath);

        file_put_contents($metaFile, json_encode($meta));

        return response()->json([
            'success' => true,
            'file_path' => $newPath,
            'url' => asset('storage/'.$newPath),
        ]);
    }

    public function getLocalIp()
    {
        // Get the local IP address of the server
        $ip = '127.0.0.1';

        // Try to get the local IP from network interfaces
        if (PHP_OS === 'WIN') {
            exec('ipconfig', $output);
            foreach ($output as $line) {
                if (preg_match('/IPv4.*?(\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
                    $ip = $matches[1];
                    if (strpos($ip, '192.168.') !== false || strpos($ip, '10.') !== false) {
                        break;
                    }
                }
            }
        } else {
            exec('ip route get 1', $output);
            if (isset($output[0]) && preg_match('/(\d+\.\d+\.\d+\.\d+)/', $output[0], $matches)) {
                $ip = $matches[1];
            }
        }

        return $ip;
    }

    private function getValidContexts(): array
    {
        return [
            'item_image' => [
                'label' => __('messages.item_image'),
                'model' => \App\Models\Item::class,
            ],
            'invoice_image' => [
                'label' => __('messages.invoice_image'),
                'model' => \App\Models\Invoice::class,
            ],
            'invoice_item_image' => [
                'label' => __('messages.invoice_item_image'),
                'model' => \App\Models\InvoiceItem::class,
            ],
            'bdl_image' => [
                'label' => __('messages.delivery_note'),
                'model' => \App\Models\BonDeLivraison::class,
            ],
            'po_item_image' => [
                'label' => __('messages.item'),
                'model' => \App\Models\PurchaseOrderItem::class,
            ],
        ];
    }

    private function getTargetLabel(string $context, int $targetId): string
    {
        return match ($context) {
            'item_image' => "Item #{$targetId}",
            'invoice_image' => "Invoice #{$targetId}",
            'invoice_item_image' => "Invoice Item #{$targetId}",
            'bdl_image' => "BDL #{$targetId}",
            'po_item_image' => "PO Item #{$targetId}",
            default => "#{$targetId}",
        };
    }
}
