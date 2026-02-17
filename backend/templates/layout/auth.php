<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Socialite - Auth</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js"></script>
    <style>
        body {
            font-family: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: 
                        url('/img/backgrounds/bg1.png') center -40px no-repeat fixed;
            background-size: auto;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center overflow-hidden">
    <div class="w-full max-w-2xl p-5">
        <div id="flashContainer">
            <?= $this->Flash->render() ?>
        </div>
        <?= $this->fetch('content') ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Remove `logged_out` query param from URL so refresh doesn't re-trigger server flash
        try {
            const url = new URL(window.location.href);
            if (url.searchParams.has('logged_out')) {
                url.searchParams.delete('logged_out');
                const newUrl = url.pathname + (url.search ? '?' + url.searchParams.toString() : '');
                window.history.replaceState({}, document.title, newUrl);
            }
        } catch (e) {
            // ignore
        }

        // Auto-hide any flash messages after a short delay
        try {
            const container = document.getElementById('flashContainer');
            if (!container) return;
            const children = Array.from(container.children).filter(n => n.nodeType === 1);
            const VISIBLE_FOR = 3500;
            children.forEach((el, idx) => {
                setTimeout(() => {
                    try { el.remove(); } catch(e) {}
                }, VISIBLE_FOR + idx * 150);
            });
        } catch (e) {
            // ignore
        }
    });
    </script>
</body>
</html>
      