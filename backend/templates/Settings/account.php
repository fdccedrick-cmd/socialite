<div id="settingsApp">
  <div class="max-w-2xl mx-auto p-4 sm:p-6">
    <h1 class="text-2xl font-bold mb-4">Account Settings</h1>

    <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-6">
      <h2 class="text-lg font-semibold mb-2">Change Password</h2>
      <p class="text-sm text-gray-500 mb-4">Enter your current password and choose a new password.</p>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Current Password</label>
          <div class="relative mt-1">
            <input :type="showCurrent ? 'text' : 'password'" v-model="form.current_password" class="w-full px-3 py-2 border rounded-lg" placeholder="Current password" />
            <button type="button" @click="showCurrent = !showCurrent" class="absolute right-2 top-2 text-gray-500" aria-label="Toggle current password visibility">
              <i v-if="showCurrent" data-lucide="eye-off" class="w-4 h-4"></i>
              <i v-else data-lucide="eye" class="w-4 h-4"></i>
            </button>
          </div>
          <p v-if="errors.current_password" class="text-xs text-red-600 mt-1">{{ errors.current_password }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">New Password</label>
          <div class="relative mt-1">
            <input :type="showNew ? 'text' : 'password'" v-model="form.new_password" class="w-full px-3 py-2 border rounded-lg" placeholder="New password (min 6 chars)" />
            <button type="button" @click="showNew = !showNew" class="absolute right-2 top-2 text-gray-500" aria-label="Toggle new password visibility">
              <i v-if="showNew" data-lucide="eye-off" class="w-4 h-4"></i>
              <i v-else data-lucide="eye" class="w-4 h-4"></i>
            </button>
          </div>
          <p v-if="errors.new_password" class="text-xs text-red-600 mt-1">{{ errors.new_password }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
          <div class="relative mt-1">
            <input :type="showConfirm ? 'text' : 'password'" v-model="form.confirm_password" class="w-full px-3 py-2 border rounded-lg" placeholder="Confirm new password" />
            <button type="button" @click="showConfirm = !showConfirm" class="absolute right-2 top-2 text-gray-500" aria-label="Toggle confirm password visibility">
              <i v-if="showConfirm" data-lucide="eye-off" class="w-4 h-4"></i>
              <i v-else data-lucide="eye" class="w-4 h-4"></i>
            </button>
          </div>
          <p v-if="errors.confirm_password" class="text-xs text-red-600 mt-1">{{ errors.confirm_password }}</p>
        </div>

        <div class="flex justify-end">
          <button type="submit" :disabled="isSubmitting" class="bg-blue-600 text-white px-4 py-2 rounded-lg">
            <span v-if="isSubmitting">Saving...</span>
            <span v-else>Change Password</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <script src="/js/settings.js"></script>
</div>
