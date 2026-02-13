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
    <p v-if="post.content_text" class="text-gray-800 text-xs sm:text-sm whitespace-pre-wrap" :class="{'mb-2 sm:mb-3': post.post_images && post.post_images.length > 0}">{{ post.content_text }}</p>
  </div>
  
  <!-- Post Images -->
  <div v-if="post.post_images && post.post_images.length > 0" class="w-full">
    <!-- Single Image -->
    <img 
      v-if="post.post_images.length === 1"
      :src="post.post_images[0].image_path" 
      :alt="'Post image'" 
      class="w-full h-auto max-h-96 sm:max-h-none object-cover"
    />
    
    <!-- Multiple Images Grid -->
    <div v-else-if="post.post_images.length === 2" class="grid grid-cols-2 gap-0.5 sm:gap-1">
      <img 
        v-for="(image, index) in post.post_images" 
        :key="index"
        :src="image.image_path" 
        :alt="'Post image ' + (index + 1)" 
        class="w-full h-40 sm:h-64 object-cover"
      />
    </div>
    
    <div v-else-if="post.post_images.length === 3" class="grid grid-cols-2 gap-0.5 sm:gap-1">
      <img 
        :src="post.post_images[0].image_path" 
        alt="Post image 1" 
        class="w-full h-full object-cover row-span-2"
      />
      <img 
        :src="post.post_images[1].image_path" 
        alt="Post image 2" 
        class="w-full h-20 sm:h-32 object-cover"
      />
      <img 
        :src="post.post_images[2].image_path" 
        alt="Post image 3" 
        class="w-full h-20 sm:h-32 object-cover"
      />
    </div>
    
    <div v-else class="grid grid-cols-2 gap-0.5 sm:gap-1">
      <img 
        v-for="(image, index) in post.post_images.slice(0, 4)" 
        :key="index"
        :src="image.image_path" 
        :alt="'Post image ' + (index + 1)" 
        class="w-full h-32 sm:h-48 object-cover"
      />
    </div>
  </div>
  
  <!-- Post Actions (Likes & Comments) -->
  <?= $this->element('likes/like_button', ['post' => $post ?? []]) ?>
  <?= $this->element('comments/comment_input', ['post' => $post ?? [], 'currentUser' => $currentUser]) ?>
</div>
