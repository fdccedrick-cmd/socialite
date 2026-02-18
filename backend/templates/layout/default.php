<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Socialite - Social Media App</title>
    <?php // Expose CSRF token for JS to read if middleware provided one ?>
    <meta name="csrf-token" content="<?= h($this->request->getAttribute('csrfToken') ?? '') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    <script src="/js/confirmModal.js?v=<?= time() ?>"></script>
    <script src="/js/toast.js?v=<?= time() ?>"></script>
    <script src="/js/flashClient.js?v=<?= time() ?>"></script>
    
    <style>
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            
        }
        
        @media (min-width: 1024px) {
            body {
                overflow: hidden;
                height: 100vh;
            }
        }
        
        /* Flash animation styles */
        .flash-container { position: relative; z-index: 60; }
        .flash-message { display: inline-block; margin: 0.5rem 0; padding: .75rem 1rem; border-radius: .5rem; transition: transform .36s ease, opacity .36s ease; opacity: 0; transform: translateY(-6px); }
        .flash-in { opacity: 1; transform: translateY(0); }
        .flash-out { opacity: 0; transform: translateY(-8px); }
        [v-cloak] { display: none; }
        
        /* Slide-down animation for flash messages */
        @keyframes slide-down {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-slide-down {
            animation: slide-down 0.3s ease-out;
        }
        
        .main-scroll-container {
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        @media (min-width: 1024px) {
            .main-scroll-container {
                height: calc(100vh - 5rem);
            }
        }
        
        /* Hide scrollbar for main content (cross-browser) */
        .main-scroll-container {
            -ms-overflow-style: none; /* IE and Edge */
            scrollbar-width: none; /* Firefox */
        }
        .main-scroll-container::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        /* Vue transition styles */
        .fade-enter-active, .fade-leave-active {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .fade-enter-from {
            opacity: 0;
            transform: translateY(-10px);
        }
        .fade-leave-to {
            opacity: 0;
            transform: translateY(10px);
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php $currentUser = $currentUser ?? ($this->Identity->get() ?? null); ?>
    <?= $this->element('navigation/header', ['user' => $currentUser]) ?>
    
    <div id="flashContainer" class="flash-container fixed top-16 sm:top-20 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-4">
        <?= $this->Flash->render() ?>
    </div>
    <?= $this->element('toast_container') ?>
    
    <!-- Mobile Menu Overlay -->
    <div id="mobileMenuOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden" onclick="this.classList.add('hidden')"></div>
    
    <!-- Mobile Sidebar -->
    <div id="mobileSidebar" class="fixed top-14 sm:top-16 left-0 h-[calc(100vh-3.5rem)] sm:h-[calc(100vh-4rem)] w-64 bg-white shadow-lg transform -translate-x-full transition-transform duration-300 z-40 lg:hidden overflow-y-auto">
        <?= $this->element('navigation/leftnav', ['user' => $currentUser]) ?>
    </div>
    
    <div class="w-full px-2 sm:px-4 lg:px-0 pt-16 sm:pt-20">
        <div class="flex flex-col lg:flex-row gap-3 sm:gap-4 lg:gap-4 xl:gap-5 2xl:gap-6">
            <!-- Left Navigation - Desktop Only -->
            <aside class="hidden lg:block flex-shrink-0 w-64 lg:w-72 xl:w-80 2xl:w-96 lg:pl-4 xl:pl-6 2xl:pl-8">
                <?= $this->element('navigation/leftnav', ['user' => $currentUser]) ?>
            </aside>
            
            <!-- Main Content - Scrollable -->
            <main class="flex-1 min-w-0 w-full main-scroll-container pb-6 sm:pb-10">
                <?= $this->fetch('content') ?>
            </main>
            
            <!-- Right Sidebar - Desktop Only -->
            <aside class="hidden lg:block flex-shrink-0 w-64 lg:w-72 xl:w-80 2xl:w-96 lg:pr-4 xl:pr-6 2xl:pr-8">
                <?= $this->element('navigation/rightnav') ?>
            </aside>
        </div>
    </div>
    
    <script>
    // Mobile menu toggle handler
    window.addEventListener('mobile-menu-toggle', function(e) {
        const overlay = document.getElementById('mobileMenuOverlay');
        const sidebar = document.getElementById('mobileSidebar');
        if (e.detail.open) {
            overlay.classList.remove('hidden');
            sidebar.classList.remove('-translate-x-full');
        } else {
            overlay.classList.add('hidden');
            sidebar.classList.add('-translate-x-full');
        }
    });
    
    // Close mobile menu when clicking overlay
    document.getElementById('mobileMenuOverlay')?.addEventListener('click', function() {
        window.dispatchEvent(new CustomEvent('mobile-menu-toggle', { detail: { open: false } }));
    });
    
    // Initialize Lucide icons after DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
    </script>

    <?= $this->element('confirmation_modal') ?>

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


















































































































