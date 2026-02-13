<?php
/**
 * Post Card Element
 * Displays a single post with images, likes, and comments
 * 
 * @var array $post - Post data with user and images
 * @var array $currentUser - Current logged in user data
 */
$currentUser = $currentUser ?? [];
?>

<div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-100 overflow-hidden">
  <!-- Post Header -->
  <div class="p-3 sm:p-4 pb-2 sm:pb-3">
    <div class="flex items-center gap-2 sm:gap-2.5 mb-2 sm:mb-3">
      <img 
        :src="post.user.profile_photo_path || 'https://i.pravatar.cc/150?img=1'" 
        :alt="post.user.full_name" 
        class="w-8 h-8 sm:w-10 sm:h-10 rounded-full object-cover border border-gray-200 flex-shrink-0"
      />
      <div class="flex-1 min-w-0">
        <a :href="`/users/profile/${post.user.id}`"
           class="block font-semibold text-xs sm:text-sm text-gray-900 truncate hover:underline cursor-pointer inline-block">
            {{ post.user.full_name }}
        </a>
        <!-- <h3 class="font-semibold text-gray-900 text-xs sm:text-sm truncate">{{ post.user.full_name }}</h3> -->
        <p class="text-[10px] sm:text-xs text-gray-500">{{ formatDate(post.created) }}</p>
      </div>
      
      <!-- Post Options Menu (3 dots) - Only show for own posts -->
      <div v-if="canEditPost && canEditPost(post)" class="relative">
        <button 
          @click="togglePostMenu(post.id, $event)"
          data-menu-trigger
          class="p-1.5 hover:bg-gray-100 rounded-full transition-colors"
        >
          <i data-lucide="more-horizontal" class="w-4 h-4 text-gray-600"></i>
        </button>
        
        <div 
          v-if="post.showMenu"
          data-post-menu
          class="absolute right-0 mt-1 w-32 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10"
        >
          <button
            @click="editPost(post.id)"
            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2"
          >
            <i data-lucide="edit" class="w-3.5 h-3.5"></i>
            Edit
          </button>
          <button
            @click="deletePost(post.id)"
            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
          >
            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            Delete
          </button>
        </div>
      </div>
    </div>
    
    <!-- Edit Mode -->
    <div v-if="post.isEditing" class="space-y-3">
      <!-- Edit Caption -->
      <textarea 
        v-model="post.editContent"
        placeholder="What's on your mind?"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs sm:text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
        rows="3"
      ></textarea>
      
      <!-- Existing Images in Edit Mode -->
      <div v-if="post.editImages && post.editImages.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
        <div v-for="(image, index) in post.editImages" :key="image.id" class="relative">
          <img :src="image.image_path" alt="Post image" class="w-full h-24 sm:h-32 object-cover rounded-lg">
          <button
            @click="removeExistingImage(post.id, image.id, index)"
            class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600"
          >
            ×
          </button>
        </div>
      </div>
      
      <!-- New Images Preview -->
      <div v-if="post.newEditImages && post.newEditImages.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
        <div v-for="(preview, index) in post.newEditImagePreviews" :key="'new-' + index" class="relative">
          <img :src="preview" alt="New image" class="w-full h-24 sm:h-32 object-cover rounded-lg">
          <button
            @click="removeNewEditImage(post.id, index)"
            class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600"
          >
            ×
          </button>
        </div>
      </div>
      
      <!-- Add Photos Button -->
      <div class="flex gap-2">
        <input 
          :id="'edit-images-' + post.id"
          type="file"
          accept="image/*"
          multiple
          @change="handleEditImageSelect($event, post.id)"
          class="hidden"
        />
        <button
          @click="document.getElementById('edit-images-' + post.id).click()"
          class="flex items-center gap-1.5 px-3 py-1.5 text-xs sm:text-sm text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
        >
          <i data-lucide="image" class="w-3.5 h-3.5"></i>
          Add Photos
        </button>
      </div>
      
      <!-- Save/Cancel Buttons -->
      <div class="flex gap-2 pt-2 border-t">
        <button
          @click="saveEditPost(post.id)"
          class="flex-1 px-4 py-2 bg-blue-600 text-white text-xs sm:text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
        >
          Save
        </button>
        <button
          @click="cancelEditPost(post.id)"
          class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 text-xs sm:text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors"
        >
          Cancel
        </button>
      </div>
    </div>
    
    <!-- Normal View Mode -->
    <div v-else>
      <!-- Post Content -->
      <div 
        v-if="post.content_text" 
        class="text-gray-800 text-xs sm:text-sm whitespace-pre-wrap" 
        :class="{'mb-2 sm:mb-3': post.post_images && post.post_images.length > 0}"
      >{{ post.content_text }}</div>
    </div>
  </div>
  
  <!-- Post Images (only show when not editing) -->
  <div v-if="!post.isEditing" class="post-images-container">
  <div v-if="post.post_images && post.post_images.length > 0" class="w-full bg-gray-100">
    <!-- Single Image -->
    <div 
      v-if="post.post_images.length === 1"
      class="w-full max-h-[500px] overflow-hidden cursor-pointer"
      @click="openImageViewer(post.post_images, 0)"
    >
      <img 
        :src="post.post_images[0].image_path" 
        :alt="'Post image'" 
        class="w-full h-full object-contain bg-black hover:opacity-95 transition-opacity"
      />
    </div>
    
    <!-- Two Images Grid -->
    <div v-else-if="post.post_images.length === 2" class="grid grid-cols-2 gap-0.5">
      <img 
        v-for="(image, index) in post.post_images" 
        :key="index"
        :src="image.image_path" 
        :alt="'Post image ' + (index + 1)" 
        @click="openImageViewer(post.post_images, index)"
        class="w-full h-[250px] sm:h-[350px] object-cover cursor-pointer hover:opacity-95 transition-opacity"
      />
    </div>
    
    <!-- Three Images Grid (Facebook Style: 1 large left, 2 stacked right) -->
    <div v-else-if="post.post_images.length === 3" class="grid grid-cols-2 gap-0.5 h-[350px] sm:h-[450px]">
      <img 
        :src="post.post_images[0].image_path" 
        alt="Post image 1" 
        @click="openImageViewer(post.post_images, 0)"
        class="w-full h-full object-cover cursor-pointer hover:opacity-95 transition-opacity"
      />
      <div class="grid grid-rows-2 gap-0.5 h-full">
        <img 
          :src="post.post_images[1].image_path" 
          alt="Post image 2" 
          @click="openImageViewer(post.post_images, 1)"
          class="w-full h-full object-cover cursor-pointer hover:opacity-95 transition-opacity"
        />
        <img 
          :src="post.post_images[2].image_path" 
          alt="Post image 3" 
          @click="openImageViewer(post.post_images, 2)"
          class="w-full h-full object-cover cursor-pointer hover:opacity-95 transition-opacity"
        />
      </div>
    </div>
    
    <!-- Four or More Images Grid (2x2 with +X overlay for 5+) -->
    <div v-else class="grid grid-cols-2 gap-0.5">
      <template v-for="(image, index) in post.post_images.slice(0, 4)" :key="index">
        <!-- Regular images for first 3 or all 4 if exactly 4 images -->
        <div 
          v-if="index < 3 || post.post_images.length === 4"
          class="relative w-full h-[175px] sm:h-[250px] overflow-hidden cursor-pointer"
          @click="openImageViewer(post.post_images, index)"
        >
          <img 
            :src="image.image_path" 
            :alt="'Post image ' + (index + 1)" 
            class="w-full h-full object-cover hover:opacity-95 transition-opacity"
          />
        </div>
        
        <!-- 4th image with overlay if 5+ images -->
        <div 
          v-else-if="index === 3 && post.post_images.length > 4"
          class="relative w-full h-[175px] sm:h-[250px] overflow-hidden cursor-pointer group"
          @click="openImageViewer(post.post_images, index)"
        >
          <img 
            :src="image.image_path" 
            :alt="'Post image ' + (index + 1)" 
            class="w-full h-full object-cover"
          />
          <div class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center group-hover:bg-opacity-70 transition-all">
            <span class="text-white text-3xl sm:text-4xl font-bold">+{{ post.post_images.length - 4 }}</span>
          </div>
        </div>
      </template>
    </div>
  </div>
  </div>
  
  <!-- Post Actions (Likes & Comments) - Hide when editing -->
  <div v-if="!post.isEditing">
    <?= $this->element('likes/like_button', ['post' => $post ?? []]) ?>
  
    <!-- Comment Section -->
    <div class="border-t border-gray-100">
      <?= $this->element('comments/comment_list', ['post' => $post ?? []]) ?>
      <?= $this->element('comments/comment_input', ['post' => $post ?? []]) ?>
    </div>
  </div>
</div>
