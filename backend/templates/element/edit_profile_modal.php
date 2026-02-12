<!-- Edit Profile Modal -->
<Teleport to="body">
  <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4" @click.self="closeEditModal" style="z-index: 9999;">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
    <!-- Modal Header -->
    <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
      <h2 class="text-xl font-bold text-gray-900">Edit Profile</h2>
      <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600 transition-colors">
        <i data-lucide="x" class="w-6 h-6"></i>
      </button>
    </div>

    <!-- Modal Body -->
    <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
      <!-- Profile Picture Upload -->
      <div class="space-y-3">
        <label class="block text-sm font-semibold text-gray-700">Profile Picture</label>
        <div class="flex items-center gap-6">
          <div class="shrink-0">
            <img 
              :src="editForm.avatar || user.avatar" 
              :alt="editForm.full_name" 
              class="w-24 h-24 rounded-full object-cover border-4 border-gray-100"
            />
          </div>
          <div class="flex-1">
            <input 
              type="file" 
              ref="profilePicture"
              @change="handleFileChange"
              accept="image/jpeg,image/png,image/jpg,image/gif"
              class="hidden"
              id="profilePictureInput"
            />
            <label 
              for="profilePictureInput"
              class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg cursor-pointer transition-colors"
            >
              <i data-lucide="upload" class="w-4 h-4"></i>
              <span class="text-sm font-medium">Upload New Photo</span>
            </label>
            <p class="text-xs text-gray-500 mt-2">JPG, PNG or GIF (MAX. 5MB)</p>
            <p v-if="uploadError" class="text-xs text-red-600 mt-1">{{ uploadError }}</p>
          </div>
        </div>
      </div>

      <!-- Divider -->
      <div class="border-t border-gray-200"></div>

      <!-- Full Name -->
      <div class="space-y-2">
        <label for="full_name" class="block text-sm font-semibold text-gray-700">
          Full Name <span class="text-red-500">*</span>
        </label>
        <input 
          type="text" 
          id="full_name"
          v-model="editForm.full_name"
          required
          maxlength="150"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
          placeholder="Enter your full name"
        />
        <p v-if="errors.full_name" class="text-sm text-red-600">{{ errors.full_name }}</p>
      </div>

      <!-- Username (Read-only) -->
      <div class="space-y-2">
        <label for="username" class="block text-sm font-semibold text-gray-700">
          Username
        </label>
        <div class="relative">
          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">@</span>
          <input 
            type="text" 
            id="username"
            v-model="editForm.username"
            readonly
            disabled
            class="w-full pl-8 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-gray-500 cursor-not-allowed"
          />
        </div>
        <p class="text-xs text-gray-500">Username cannot be changed</p>
      </div>

      <!-- Divider -->
      <div class="border-t border-gray-200"></div>

      <!-- Password Section Header -->
      <div>
        <h3 class="text-base font-semibold text-gray-900 mb-1">Change Password</h3>
        <p class="text-sm text-gray-500">Leave blank if you don't want to change your password</p>
      </div>

      <!-- Current Password -->
      <div class="space-y-2">
        <label for="current_password" class="block text-sm font-semibold text-gray-700">
          Current Password
        </label>
        <div class="relative">
          <input 
            :type="showCurrentPassword ? 'text' : 'password'"
            id="current_password"
            v-model="editForm.current_password"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all pr-12"
            placeholder="Enter your current password"
          />
          <button 
            type="button"
            @click="showCurrentPassword = !showCurrentPassword"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
          >
            <i :data-lucide="showCurrentPassword ? 'eye-off' : 'eye'" class="w-5 h-5"></i>
          </button>
        </div>
        <p v-if="errors.current_password" class="text-sm text-red-600">{{ errors.current_password }}</p>
      </div>

      <!-- New Password -->
      <div class="space-y-2">
        <label for="new_password" class="block text-sm font-semibold text-gray-700">
          New Password
        </label>
        <div class="relative">
          <input 
            :type="showNewPassword ? 'text' : 'password'"
            id="new_password"
            v-model="editForm.new_password"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all pr-12"
            placeholder="Enter new password (min. 6 characters)"
            minlength="6"
          />
          <button 
            type="button"
            @click="showNewPassword = !showNewPassword"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
          >
            <i :data-lucide="showNewPassword ? 'eye-off' : 'eye'" class="w-5 h-5"></i>
          </button>
        </div>
        <p v-if="errors.new_password" class="text-sm text-red-600">{{ errors.new_password }}</p>
      </div>

      <!-- Confirm New Password -->
      <div class="space-y-2">
        <label for="confirm_password" class="block text-sm font-semibold text-gray-700">
          Confirm New Password
        </label>
        <div class="relative">
          <input 
            :type="showConfirmPassword ? 'text' : 'password'"
            id="confirm_password"
            v-model="editForm.confirm_password"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all pr-12"
            placeholder="Re-enter new password"
          />
          <button 
            type="button"
            @click="showConfirmPassword = !showConfirmPassword"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
          >
            <i :data-lucide="showConfirmPassword ? 'eye-off' : 'eye'" class="w-5 h-5"></i>
          </button>
        </div>
        <p v-if="errors.confirm_password" class="text-sm text-red-600">{{ errors.confirm_password }}</p>
      </div>

      <!-- Modal Footer -->
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
        <button 
          type="button"
          @click="closeEditModal"
          class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
        >
          Cancel
        </button>
        <button 
          type="submit"
          :disabled="isSubmitting"
          class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <span v-if="isSubmitting">
            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
          </span>
          <span>{{ isSubmitting ? 'Saving...' : 'Save Changes' }}</span>
        </button>
      </div>
    </form>
  </div>
</div>
</Teleport>
