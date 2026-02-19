<div class="max-w-7xl mx-auto p-4 sm:p-6">
  <h1 class="text-2xl font-bold mb-6 dark:text-white">Settings</h1>
  
  <div class="flex flex-col md:flex-row gap-6">
    <!-- Left Navigation -->
    <div class="w-full md:w-64 flex-shrink-0">
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <nav class="flex flex-col">
          <a 
            href="/settings?section=account"
            class="px-4 py-3 text-left font-medium transition-colors flex items-center gap-3 <?= ($section === 'account') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-l-4 border-blue-700 dark:border-blue-500' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>"
          >
            <i data-lucide="user" class="w-5 h-5"></i>
            <span>Account</span>
          </a>
          <a 
            href="/settings?section=theme"
            class="px-4 py-3 text-left font-medium transition-colors flex items-center gap-3 <?= ($section === 'theme') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-l-4 border-blue-700 dark:border-blue-500' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>"
          >
            <i data-lucide="palette" class="w-5 h-5"></i>
            <span>Theme</span>
          </a>
        </nav>
      </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="flex-1">
      <?php if ($section === 'account'): ?>
        <?= $this->element('settings/account') ?>
      <?php elseif ($section === 'theme'): ?>
        <?= $this->element('settings/theme') ?>
      <?php else: ?>
        <?= $this->element('settings/account') ?>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="/js/settings.js"></script>
<script>
  // Initialize lucide icons after page load
  if (window.lucide) {
    lucide.createIcons();
  }
</script>
