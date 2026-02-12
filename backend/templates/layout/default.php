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
            background: linear-gradient(rgba(0,0,0,0.15), rgba(0,0,0,0.15));
            background-size: auto;
        }
        /* Flash animation styles */
        .flash-container { position: relative; z-index: 60; }
        .flash-message { display: inline-block; margin: 0.5rem 0; padding: .75rem 1rem; border-radius: .5rem; transition: transform .36s ease, opacity .36s ease; opacity: 0; transform: translateY(-6px); }
        .flash-in { opacity: 1; transform: translateY(0); }
        .flash-out { opacity: 0; transform: translateY(-8px); }
    </style>
</head>
<body class="min-h-screen flex justify-center items-center">
    <div class="w-full max-w-7xl p-5 pt-16">
        <?php $currentUser = $currentUser ?? ($this->Identity->get() ?? null); ?>
        <?= $this->element('header', ['user' => $currentUser]) ?>
        
        <div id="flashContainer" class="flash-container">
            <?= $this->Flash->render() ?>
        </div>
        
        <div>
            <?= $this->fetch('content') ?>
        </div>
    </div>

</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('flashContainer');
    if (!container) return;

    // Duration the message stays visible (ms)
    const VISIBLE_FOR = 4000;
    const messages = Array.from(container.children).filter(n => n.nodeType === 1);

    messages.forEach((el, idx) => {
        // normalize class name so styling applies
        if (!el.classList.contains('flash-message')) el.classList.add('flash-message');

        // enter animation
        requestAnimationFrame(() => {
            el.classList.add('flash-in');
        });

        // auto-hide after VISIBLE_FOR + small stagger
        const delay = VISIBLE_FOR + idx * 150;
        setTimeout(() => {
            el.classList.remove('flash-in');
            el.classList.add('flash-out');
            // remove after transition
            setTimeout(() => { try { el.remove(); } catch(e){} }, 400);
        }, delay);

        // allow click to dismiss immediately
        el.addEventListener('click', function () {
            el.classList.remove('flash-in');
            el.classList.add('flash-out');
            setTimeout(() => { try { el.remove(); } catch(e){} }, 200);
        });
    });
});
</script>


















































































































