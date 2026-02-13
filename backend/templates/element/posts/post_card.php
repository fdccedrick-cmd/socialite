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
        <h3 class="font-semibold text-gray-900 text-xs sm:text-sm truncate">{{ post.user.full_name }}</h3>
        <p class="text-[10px] sm:text-xs text-gray-500">{{ formatDate(post.created) }}</p>
      </div>
    </div>
    
    <!-- Post Content -->
    <div 
      v-if="post.content_text" 
      class="text-gray-800 text-xs sm:text-sm whitespace-pre-wrap" 
      :class="{'mb-2 sm:mb-3': post.post_images && post.post_images.length > 0}"
    >{{ post.content_text }}</div>
  </div>
  
  <!-- Post Images -->
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
  
  <!-- Post Actions (Likes & Comments) -->
  <?= $this->element('likes/like_button', ['post' => $post ?? []]) ?>
  <?= $this->element('comments/comment_input', ['post' => $post ?? [], 'currentUser' => $currentUser]) ?>
</div>
