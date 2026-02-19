<!-- Edit Profile Modal -->
<Teleport to="body">
  <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-0 sm:p-4" @click.self="closeEditModal" style="z-index: 9999;">
    <div class="bg-white dark:bg-gray-800 rounded-none sm:rounded-2xl shadow-2xl max-w-2xl w-full h-full sm:h-auto sm:max-h-[90vh] overflow-y-auto">
    <!-- Modal Header -->
    <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between">
      <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">Edit Profile</h2>
      <button @click="closeEditModal" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
        <i data-lucide="x" class="w-5 h-5 sm:w-6 sm:h-6"></i>
      </button>
    </div>

    <!-- Modal Body -->
    <!-- Cropper assets are loaded dynamically from profile.js -->
    <form @submit.prevent="handleSubmit" class="p-4 sm:p-6 space-y-4 sm:space-y-6">
      <!-- Profile Picture Upload -->
      <div class="space-y-2 sm:space-y-3">
        <label class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300">Profile Picture</label>
        <div class="flex flex-col sm:flex-row items-center gap-3 sm:gap-6">
          <div class="shrink-0">
            <img 
              :src="editForm.avatar || user.avatar" 
              :alt="editForm.full_name" 
              class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover border-2 sm:border-4 border-gray-100 dark:border-gray-600"
            />
          </div>
          <div class="flex-1 text-center sm:text-left">
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
              class="inline-flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-1.5 sm:py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg cursor-pointer transition-colors"
            >
              <i data-lucide="upload" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
              <span class="text-xs sm:text-sm font-medium">Upload New Photo</span>
            </label>
            <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 mt-1 sm:mt-2">JPG, PNG or GIF (MAX. 5MB)</p>
            <p v-if="uploadError" class="text-[10px] sm:text-xs text-red-600 dark:text-red-400 mt-1">{{ uploadError }}</p>
          </div>
        </div>

        <!-- Cropper modal -->
        <div v-if="showCropper" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center p-4 z-[10000]">
          <div class="bg-white dark:bg-gray-800 rounded-lg max-w-3xl w-full p-4">
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-semibold text-gray-900 dark:text-white">Crop Photo</h3>
              <button type="button" @click="cancelCrop" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">Close</button>
            </div>
            <div class="w-full flex flex-col sm:flex-row gap-4">
              <div class="w-full sm:w-3/4 flex items-center justify-center">
                <div style="max-width:600px; width:100%;">
                  <img id="cropperImage" :src="cropperImageSrc" alt="Crop preview" class="w-full max-h-[60vh] object-contain" />
                </div>
              </div>
              <div class="w-full sm:w-1/4 flex flex-col gap-3 items-center">
                <div class="w-40 h-40 rounded-full overflow-hidden border-2 border-gray-200 dark:border-gray-600 flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                  <img :src="cropPreviewSrc" alt="Circle preview" class="w-full h-full object-cover" />
                </div>
                <button type="button" @click="cropAndUse" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Crop & Use</button>
                <button type="button" @click="cancelCrop" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Divider -->
      <div class="border-t border-gray-200 dark:border-gray-700"></div>
      <div class="space-y-1.5 sm:space-y-2">
        <label for="bio" class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300">
          Bio
        </label>
        <textarea
          id="bio"
          v-model="editForm.bio"
          maxlength="500"
          class="w-full px-3 sm:px-4 py-2 sm:py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
          placeholder="Tell us about yourself"
          rows="4"
        ></textarea>
        <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 mt-1">Maximum 500 characters</p>
        </div>
      <!-- Full Name -->
      <div class="space-y-1.5 sm:space-y-2">
        <label for="full_name" class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300">
          Full Name <span class="text-red-500">*</span>
        </label>
        <input 
          type="text" 
          id="full_name"
          v-model="editForm.full_name"
          required
          maxlength="150"
          class="w-full px-3 sm:px-4 py-2 sm:py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
          placeholder="Enter your full name"
        />
        <p v-if="errors.full_name" class="text-xs sm:text-sm text-red-600 dark:text-red-400">{{ errors.full_name }}</p>
      </div>

      <!-- Username (Read-only) -->
      <div class="space-y-1.5 sm:space-y-2">
        <label for="username" class="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300">
          Username
        </label>
        <div class="relative">
          <span class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">@</span>
          <input 
            type="text" 
            id="username"
            v-model="editForm.username"
            readonly
            disabled
            class="w-full pl-7 sm:pl-8 pr-3 sm:pr-4 py-2 sm:py-2.5 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-500 dark:text-gray-400 cursor-not-allowed"
          />
        </div>
        <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">Username cannot be changed</p>
      </div>

      <!-- Divider -->
      <div class="border-t border-gray-200 dark:border-gray-700"></div>

      <!-- Password moved to Settings/account.php -->

      <!-- Modal Footer -->
      <div class="flex items-center justify-end gap-2 sm:gap-3 pt-3 sm:pt-4 border-t border-gray-200 dark:border-gray-700">
        <button 
          type="button"
          @click="closeEditModal"
          class="px-4 sm:px-6 py-2 sm:py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors font-medium text-xs sm:text-sm"
        >
          Cancel
        </button>
        <button 
          type="submit"
          :disabled="isSubmitting"
          class="px-4 sm:px-6 py-2 sm:py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium text-xs sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5 sm:gap-2"
        >
          <span v-if="isSubmitting">
            <i data-lucide="loader-2" class="w-3.5 h-3.5 sm:w-4 sm:h-4 animate-spin"></i>
          </span>
          <span>{{ isSubmitting ? 'Saving...' : 'Save Changes' }}</span>
        </button>
      </div>
    </form>
  </div>
</div>
</Teleport>
