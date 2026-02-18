<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Not Found - Socialite</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen overflow-hidden">
    <div class="min-h-screen flex items-center justify-center p-4 overflow-hidden">
        <div class="max-w-md w-full text-center">
            <!-- Icon -->
            <div class="mb-6">
                <svg class="w-24 h-24 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            
            <!-- Title -->
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-3">
                Post Not Found
            </h1>
            
            <!-- Description -->
            <p class="text-gray-600 text-base sm:text-lg mb-8">
                This post may have been deleted or doesn't exist.
            </p>
            
            <!-- Actions -->
            <div class="space-y-3">
                <a href="/dashboard" class="block w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    Go to Dashboard
                </a>
                <button onclick="window.history.back()" class="block w-full px-6 py-3 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors">
                    Go Back
                </button>
            </div>
            
            <!-- Additional Info -->
            <p class="mt-8 text-sm text-gray-500">
                If you believe this is an error, please contact support.
            </p>
        </div>
    </div>
</body>
</html>
