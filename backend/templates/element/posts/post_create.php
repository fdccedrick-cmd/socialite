<?php
/**
 * Post Create Element
 * Compact UI for creating a new post with images or text
 * 
 * @var array $currentUser - Current logged in user data
 */
$currentUser = $currentUser ?? [];
$avatar = $currentUser['profile_photo_path'] ?? 'https://i.pravatar.cc/150?img=1';
$username = $currentUser['username'] ?? 'user';
$fullName = $currentUser['full_name'] ?? $username;
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
  <div
    class="p-3 sm:p-4 relative post-create-card transition-all"
    @dragenter="handleDragEnter"
    @dragover="handleDragOver"
    @dragleave="handleDragLeave"
    @drop="handleDrop"
    :class="{
      'bg-blue-50 border-2 border-blue-400 border-dashed ring-4 ring-blue-100': newPost.isDragging
    }"
  >
    <!-- Drag & Drop Overlay -->
    <transition name="fade">
      <div 
        v-if="newPost.isDragging" 
        class="absolute inset-0 bg-blue-500 bg-opacity-10 backdrop-blur-sm rounded-xl flex items-center justify-center pointer-events-none z-10"
      >
        <div class="text-center">
          <i data-lucide="image-plus" class="w-12 h-12 sm:w-16 sm:h-16 text-blue-600 mx-auto mb-2"></i>
          <p class="text-base sm:text-lg font-semibold text-blue-600">Drop images here</p>
          <p class="text-xs sm:text-sm text-blue-500 mt-1">Release to upload</p>
        </div>
      </div>
    </transition>
    
    <!-- Profile + Input Row -->
    <div class="flex items-start gap-2 sm:gap-3 mb-3">
      <img 
        :src="user.avatar" 
        :alt="user.username" 
        class="w-9 h-9 sm:w-10 sm:h-10 rounded-full object-cover border-2 border-gray-100 flex-shrink-0"
      />
      <div class="flex-1 min-w-0">
        <textarea 
          v-model="newPost.content"
          @input="autoResize"
          ref="postTextarea"
          placeholder="What's on your mind?"
          rows="1"
          maxlength="5000"
          class="w-full px-3 py-2 bg-gray-50 rounded-lg text-sm sm:text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white resize-none transition-all"
        ></textarea>
        
        <!-- Character Counter -->
        <div v-if="newPost.content.length > 0" class="flex justify-end mt-1 px-1">
          <span 
            class="text-xs font-medium"
            :class="newPost.content.length > 4500 ? 'text-red-500' : 'text-gray-400'"
          >
            {{ newPost.content.length }}/5000
          </span>
        </div>
      </div>
    </div>
    
    <!-- Image Preview Thumbnails -->
    <transition name="fade">
      <div v-if="newPost.imagePreview.length > 0" class="mb-3">
        <div
          class="flex items-center gap-2 overflow-x-auto pb-1"
        >
          <!-- Image Thumbnails -->
          <div 
            v-for="(preview, index) in newPost.imagePreview" 
            :key="index" 
            class="relative flex-shrink-0 group"
          >
            <img 
              :src="preview" 
              class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-lg border-2 border-gray-200"
            />
            <button 
              @click="removeImage(index)"
              class="absolute -top-1.5 -right-1.5 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 shadow-md transition-all transform hover:scale-110"
              title="Remove"
            >
              <i data-lucide="x" class="w-3 h-3"></i>
            </button>
          </div>
          
          <!-- Add More Photos Button -->
          <label 
            class="flex-shrink-0 w-16 h-16 sm:w-20 sm:h-20 border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-all group"
            title="Add more photos"
          >
            <input 
              type="file" 
              @change="handleImageSelect" 
              accept="image/*" 
              multiple 
              class="hidden"
            />
            <i data-lucide="plus" class="w-5 h-5 sm:w-6 sm:h-6 text-gray-400 group-hover:text-blue-500 mb-0.5"></i>
            <span class="text-[10px] text-gray-500 group-hover:text-blue-500">Photo</span>
          </label>
        </div>
      </div>
    </transition>
    
    <!-- Error Message -->
    <transition name="fade">
      <div v-if="newPost.error" class="mb-3 p-2.5 bg-red-50 border border-red-200 rounded-lg flex items-start gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5"></i>
        <p class="text-xs sm:text-sm text-red-700 flex-1">{{ newPost.error }}</p>
      </div>
    </transition>
    
    <!-- Actions Bar -->
    <div class="flex items-center justify-between pt-3 border-t border-gray-100 gap-2">
      <!-- Left Actions -->
      <div class="flex items-center gap-1 sm:gap-1.5">
        <!-- Photo Upload (Only show when no images) -->
        <label 
          v-if="newPost.imagePreview.length === 0"
          class="flex items-center gap-1 px-2 sm:px-2.5 py-1.5 text-gray-700 hover:bg-gray-50 rounded-lg cursor-pointer transition-all group"
          title="Add photos"
        >
          <input 
            type="file" 
            @change="handleImageSelect" 
            accept="image/*" 
            multiple 
            class="hidden"
            ref="imageInput"
          />
          <i data-lucide="image" class="w-4 h-4 sm:w-5 sm:h-5 text-green-600"></i>
          <span class="text-xs sm:text-sm font-medium hidden sm:inline">Photo</span>
        </label>
        
        <!-- Emoji Button -->
        <!-- <button 
          @click="toggleEmojiPicker"
          type="button"
          class="flex items-center gap-1 px-2 sm:px-2.5 py-1.5 text-gray-700 hover:bg-gray-50 rounded-lg transition-all group"
          :class="{'bg-gray-100': newPost.showEmojiPicker}"
          title="Add emoji"
        >
          <i data-lucide="smile" class="w-4 h-4 sm:w-5 sm:h-5 text-yellow-500"></i>
          <span class="text-xs sm:text-sm font-medium hidden sm:inline">Emoji</span>
        </button>
         -->
        <!-- Privacy Selector -->
        <!-- <div class="relative ml-1">
          <select 
            v-model="newPost.privacy" 
            class="appearance-none pl-2 pr-7 py-1.5 text-xs sm:text-sm font-medium border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white hover:bg-gray-50 transition-colors cursor-pointer"
          >
            <option value="public">🌍 Public</option>
            <option value="friends">👥 Friends</option>
            <option value="private">🔒 Private</option>
          </select>
          <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-gray-400 absolute right-1.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        </div> -->
      </div>
      
      <!-- Post Button -->
      <button 
        @click="createPost"
        :disabled="newPost.isSubmitting || (!newPost.content.trim() && newPost.images.length === 0)"
        class="px-4 sm:px-5 py-1.5 sm:py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold rounded-lg disabled:bg-gray-300 disabled:cursor-not-allowed transition-all shadow-sm hover:shadow-md flex items-center gap-1.5"
      >
        <span v-if="newPost.isSubmitting">
          <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>
        </span>
        <span>{{ newPost.isSubmitting ? 'Posting...' : 'Post' }}</span>
      </button>
    </div>
  </div>
</div>
