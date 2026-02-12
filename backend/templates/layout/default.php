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
            background: #f3f4f6;
            overflow: hidden;
            height: 100vh;
        }
        /* Flash animation styles */
        .flash-container { position: relative; z-index: 60; }
        .flash-message { display: inline-block; margin: 0.5rem 0; padding: .75rem 1rem; border-radius: .5rem; transition: transform .36s ease, opacity .36s ease; opacity: 0; transform: translateY(-6px); }
        .flash-in { opacity: 1; transform: translateY(0); }
        .flash-out { opacity: 0; transform: translateY(-8px); }
        [v-cloak] { display: none; }
        
        .main-scroll-container {
            height: calc(100vh - 5rem);
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        /* Custom scrollbar for main content */
        .main-scroll-container::-webkit-scrollbar {
            width: 8px;
        }
        .main-scroll-container::-webkit-scrollbar-track {
            background: transparent;
        }
        .main-scroll-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .main-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php $currentUser = $currentUser ?? ($this->Identity->get() ?? null); ?>
    <?= $this->element('header', ['user' => $currentUser]) ?>
    
    <div id="flashContainer" class="flash-container fixed top-20 left-1/2 -translate-x-1/2 z-50">
        <?= $this->Flash->render() ?>
    </div>
    
    <div class="max-w-7xl mx-auto px-4 pt-20">
        <div class="flex gap-6">
            <!-- Left Navigation -->
            <aside class="hidden lg:block flex-shrink-0">
                <?= $this->element('leftnav', ['user' => $currentUser]) ?>
            </aside>
            
            <!-- Main Content - Scrollable -->
            <main class="flex-1 min-w-0 main-scroll-container pb-10">
                <?= $this->fetch('content') ?>
            </main>
            
            <!-- Right Sidebar -->
            <aside class="hidden xl:block flex-shrink-0">
                <?= $this->element('rightnav') ?>
            </aside>
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


















































































































