<div class="max-w-7xl mx-auto p-4 sm:p-6">
  <h1 class="text-2xl font-bold mb-6 dark:text-white">Settings</h1>
  
  <div class="flex flex-col md:flex-row gap-6">
    <!-- Left Navigation -->
    <div class="w-full md:w-64 flex-shrink-0">
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <nav class="flex flex-col">
          <a 
            href="/settings?section=account"
            class="px-4 py-3 text-left font-medium transition-colors flex items-center gap-3 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-l-4 border-blue-700 dark:border-blue-500"
          >
            <i data-lucide="user" class="w-5 h-5"></i>
            <span>Account</span>
          </a>
          <a 
            href="/settings?section=theme"
            class="px-4 py-3 text-left font-medium transition-colors flex items-center gap-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            <i data-lucide="palette" class="w-5 h-5"></i>
            <span>Theme</span>
          </a>
        </nav>
      </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="flex-1">
      <div id="accountSection" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 sm:p-6">
        <h2 class="text-lg font-semibold mb-2 dark:text-white">Change Password</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Enter your current password and choose a new password.</p>

        <form @submit.prevent="handlePasswordSubmit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Current Password</label>
            <div class="relative mt-1">
              <input :type="showCurrent ? 'text' : 'password'" v-model="form.current_password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" placeholder="Current password" />
              <button type="button" @click="showCurrent = !showCurrent" class="absolute right-2 top-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" aria-label="Toggle current password visibility">
                <i v-if="showCurrent" data-lucide="eye-off" class="w-4 h-4"></i>
                <i v-else data-lucide="eye" class="w-4 h-4"></i>
              </button>
            </div>
            <p v-if="errors.current_password" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ errors.current_password }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Password</label>
            <div class="relative mt-1">
              <input :type="showNew ? 'text' : 'password'" v-model="form.new_password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" placeholder="New password (min 6 chars)" />
              <button type="button" @click="showNew = !showNew" class="absolute right-2 top-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" aria-label="Toggle new password visibility">
                <i v-if="showNew" data-lucide="eye-off" class="w-4 h-4"></i>
                <i v-else data-lucide="eye" class="w-4 h-4"></i>
              </button>
            </div>
            <p v-if="errors.new_password" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ errors.new_password }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm New Password</label>
            <div class="relative mt-1">
              <input :type="showConfirm ? 'text' : 'password'" v-model="form.confirm_password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" placeholder="Confirm new password" />
              <button type="button" @click="showConfirm = !showConfirm" class="absolute right-2 top-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" aria-label="Toggle confirm password visibility">
                <i v-if="showConfirm" data-lucide="eye-off" class="w-4 h-4"></i>
                <i v-else data-lucide="eye" class="w-4 h-4"></i>
              </button>
            </div>
            <p v-if="errors.confirm_password" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ errors.confirm_password }}</p>
          </div>

          <div class="flex justify-end">
            <button type="submit" :disabled="isSubmitting" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
              <span v-if="isSubmitting">Saving...</span>
              <span v-else>Change Password</span>
            </button>
          </div>
        </form>
      </div>
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
