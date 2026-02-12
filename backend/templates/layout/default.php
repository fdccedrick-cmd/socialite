<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Socialite - Social Media App</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(rgba(0,0,0,0.15), rgba(0,0,0,0.15)),
            url('/img/backgrounds/bg1.png') center -40px no-repeat fixed;
            background-size: auto;
        }
    </style>
</head>
<body class="min-h-screen flex justify-center items-center">
    <div class="w-full max-w-7xl p-5">
        <?php if ($currentUser ?? null): ?>
        <div class="bg-white p-4 px-8 rounded-lg mb-5 flex justify-between items-center shadow-sm">
            <h1 class="text-indigo-500 text-2xl font-semibold">🌟 Socialite</h1>
            <div class="flex items-center gap-4">
                <span class="text-gray-700">Welcome, <?= h($currentUser->username) ?>!</span>
                <a href="/logout" class="text-indigo-500 px-4 py-2 rounded-md hover:bg-gray-100 transition-colors no-underline">Logout</a>
            </div>
        </div>
        <?php endif; ?>
        
        <?= $this->Flash->render() ?>
        
        <div>
            <?= $this->fetch('content') ?>
        </div>
    </div>
</body>
</html>


















































































































