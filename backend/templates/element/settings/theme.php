<div id="themeSection" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 sm:p-6">
  <h2 class="text-lg font-semibold mb-2 dark:text-white">Appearance</h2>
  <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Customize how Socialite looks on your device.</p>

  <div class="space-y-4">
    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Theme</h3>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <!-- Light Mode Option -->
      <button 
        @click="setTheme('light')"
        :class="theme === 'light' ? 'ring-2 ring-blue-600 dark:ring-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'ring-1 ring-gray-300 dark:ring-gray-600 hover:ring-gray-400 dark:hover:ring-gray-500'"
        class="relative rounded-lg p-4 text-left transition-all bg-white dark:bg-gray-700"
      >
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <i data-lucide="sun" class="w-5 h-5 text-yellow-500"></i>
            <span class="font-medium dark:text-white">Light</span>
          </div>
          <div v-if="theme === 'light'" class="text-blue-600 dark:text-blue-400">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
          </div>
        </div>
        <div class="bg-white border border-gray-200 rounded p-2 h-20 flex items-center justify-center">
          <div class="w-full space-y-1">
            <div class="bg-gray-200 h-2 w-3/4 rounded"></div>
            <div class="bg-gray-200 h-2 w-1/2 rounded"></div>
            <div class="bg-gray-300 h-2 w-2/3 rounded"></div>
          </div>
        </div>
      </button>

      <!-- Dark Mode Option -->
      <button 
        @click="setTheme('dark')"
        :class="theme === 'dark' ? 'ring-2 ring-blue-600 dark:ring-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'ring-1 ring-gray-300 dark:ring-gray-600 hover:ring-gray-400 dark:hover:ring-gray-500'"
        class="relative rounded-lg p-4 text-left transition-all bg-white dark:bg-gray-700"
      >
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <i data-lucide="moon" class="w-5 h-5 text-indigo-500"></i>
            <span class="font-medium dark:text-white">Dark</span>
          </div>
          <div v-if="theme === 'dark'" class="text-blue-600 dark:text-blue-400">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
          </div>
        </div>
        <div class="bg-gray-900 border border-gray-700 rounded p-2 h-20 flex items-center justify-center">
          <div class="w-full space-y-1">
            <div class="bg-gray-700 h-2 w-3/4 rounded"></div>
            <div class="bg-gray-700 h-2 w-1/2 rounded"></div>
            <div class="bg-gray-600 h-2 w-2/3 rounded"></div>
          </div>
        </div>
      </button>
    </div>

    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
      <p class="text-xs text-gray-500 dark:text-gray-400">
        <i data-lucide="info" class="w-3 h-3 inline"></i>
        Your theme preference will be saved and applied across all pages.
      </p>
    </div>
  </div>
</div>
