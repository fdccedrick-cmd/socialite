<div id="accountSection" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 sm:p-6">
  <h2 class="text-lg font-semibold mb-2 dark:text-white">Change Password</h2>
  <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Enter your current password and choose a new password.</p>

  <form @submit.prevent="handlePasswordSubmit" class="space-y-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Current Password</label>
      <div class="relative mt-1">
        <input 
          :type="showCurrent ? 'text' : 'password'" 
          v-model="form.current_password" 
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
          placeholder="Current password" 
        />
        <button 
          type="button" 
          @click="showCurrent = !showCurrent" 
          class="absolute right-2 top-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" 
          aria-label="Toggle current password visibility"
        >
          <i v-show="showCurrent" data-lucide="eye-off" class="w-4 h-4"></i>
          <i v-show="!showCurrent" data-lucide="eye" class="w-4 h-4"></i>
        </button>
      </div>
      <p v-if="errors.current_password" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ errors.current_password }}</p>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Password</label>
      <div class="relative mt-1">
        <input 
          :type="showNew ? 'text' : 'password'" 
          v-model="form.new_password" 
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
          placeholder="New password (min 6 chars)" 
        />
        <button 
          type="button" 
          @click="showNew = !showNew" 
          class="absolute right-2 top-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" 
          aria-label="Toggle new password visibility"
        >
          <i v-show="showNew" data-lucide="eye-off" class="w-4 h-4"></i>
          <i v-show="!showNew" data-lucide="eye" class="w-4 h-4"></i>
        </button>
      </div>
      <p v-if="errors.new_password" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ errors.new_password }}</p>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm New Password</label>
      <div class="relative mt-1">
        <input 
          :type="showConfirm ? 'text' : 'password'" 
          v-model="form.confirm_password" 
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
          placeholder="Confirm new password" 
        />
        <button 
          type="button" 
          @click="showConfirm = !showConfirm" 
          class="absolute right-2 top-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" 
          aria-label="Toggle confirm password visibility"
        >
          <i v-show="showConfirm" data-lucide="eye-off" class="w-4 h-4"></i>
          <i v-show="!showConfirm" data-lucide="eye" class="w-4 h-4"></i>
        </button>
      </div>
      <p v-if="errors.confirm_password" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ errors.confirm_password }}</p>
    </div>

    <div class="flex justify-end">
      <button 
        type="submit" 
        :disabled="isSubmitting" 
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        <span v-if="isSubmitting">Saving...</span>
        <span v-else>Change Password</span>
      </button>
    </div>
  </form>
</div>
