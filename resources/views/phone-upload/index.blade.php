<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.upload_from_phone') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        @media (prefers-color-scheme: dark) { body { background-color: #1a1a1a; color: #fff; } }
    </style>
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="text-center mb-6">
            <div class="text-4xl mb-2">📱</div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('messages.upload_image_from_phone') }}</h1>
            <p class="text-gray-600 mt-2">{{ __($contextLabelKey) }}: {{ $targetLabel }} (ID: {{ $targetId }})</p>
        </div>

        <form id="uploadForm" action="{{ url('/phone-upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="session_key" value="{{ $sessionKey }}">
            <input type="hidden" name="context" value="{{ $context }}">
            <input type="hidden" name="target_id" value="{{ $targetId }}">

            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors">
                <label class="cursor-pointer block">
                    <input type="file" name="image" id="imageInput" accept="image/*" capture="environment" class="hidden" onchange="previewImage(event)">
                    <div id="previewContainer">
                        <div class="text-6xl mb-2">📷</div>
                        <p class="text-gray-600">{{ __('messages.tap_to_take_photo') }}</p>
                    </div>
                </label>
            </div>

            <div id="imagePreview" class="hidden">
                <img id="previewImg" src="" alt="Preview" class="w-full h-48 object-cover rounded-lg border">
            </div>

            <button type="submit" id="submitBtn" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                {{ __('messages.upload') }}
            </button>
        </form>

        <div id="statusMessage" class="mt-4 p-3 rounded-lg text-center hidden"></div>
    </div>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').classList.remove('hidden');
                    document.getElementById('previewContainer').classList.add('hidden');
                };
                reader.readAsDataURL(file);
            }
        }

        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitBtn');
            const statusMessage = document.getElementById('statusMessage');
            
            // Debug: check form data
            for (var pair of formData.entries()) {
                console.log(pair[0]+ ', ' + pair[1]);
            }
            
            // Debug: show image info
            const imageInput = document.getElementById('imageInput');
            console.log('imageInput.files:', imageInput.files);
            console.log('imageInput.files.length:', imageInput.files.length);
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Uploading...';
            
            try {
                const csrfToken = document.querySelector('input[name="_token"]').value;
                const response = await fetch('{{ url('/phone-upload') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });
                
                const responseText = await response.text();
                
                if (!response.ok) {
                    statusMessage.innerHTML = `<div class="text-left text-xs bg-red-50 p-2 rounded overflow-x-auto">
                        Status: ${response.status}<br>
                        Body: ${responseText.substring(0, 500)}
                    </div>`;
                    statusMessage.className = 'mt-4 p-3 rounded-lg text-center bg-red-100 text-red-700';
                    return;
                }
                
                const data = JSON.parse(responseText);
                
                if (data.success) {
                    statusMessage.textContent = '✅ Upload successful!';
                    statusMessage.className = 'mt-4 p-3 rounded-lg text-center bg-green-100 text-green-700';
                    
                    setTimeout(() => {
                        window.location.href = '{{ url('/phone-upload') }}/{{ $context }}/{{ $targetId }}';
                    }, 1500);
                } else {
                    throw new Error(data.error || 'Upload failed');
                }
            } catch (error) {
                statusMessage.textContent = '❌ Error: ' + error.message;
                statusMessage.className = 'mt-4 p-3 rounded-lg text-center bg-red-100 text-red-700';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = '{{ __('messages.upload') }}';
            }
        });
    </script>
</body>
</html>