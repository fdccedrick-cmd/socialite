<div id="dashboardApp" class="space-y-3 sm:space-y-4" v-cloak>
  <!-- Create Post Card -->
  <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-100 p-3 sm:p-4">
    <div class="flex items-start gap-2 sm:gap-3 mb-2 sm:mb-3">
      <img 
        :src="user.avatar" 
        :alt="user.username" 
        class="w-8 h-8 sm:w-10 sm:h-10 rounded-full object-cover border border-gray-200 flex-shrink-0"
      />
      <div class="flex-1 min-w-0">
        <textarea 
          v-model="newPost.content"
          @input="autoResize"
          ref="postTextarea"
          placeholder="What's on your mind?"
          rows="1"
          class="w-full px-2 sm:px-3 py-1.5 sm:py-2 bg-gray-50 rounded-lg text-xs sm:text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
        ></textarea>
        
        <!-- Image Preview -->
        <div v-if="newPost.imagePreview.length > 0" class="mt-2 sm:mt-3 grid grid-cols-2 gap-1.5 sm:gap-2">
          <div v-for="(preview, index) in newPost.imagePreview" :key="index" class="relative group">
            <img :src="preview" class="w-full h-24 sm:h-32 object-cover rounded-lg border border-gray-200" />
            <button 
              @click="removeImage(index)"
              class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <i data-lucide="x" class="w-3 h-3"></i>
            </button>
          </div>
        </div>
        
        <!-- Error Message -->
        <p v-if="newPost.error" class="mt-2 text-xs text-red-600">{{ newPost.error }}</p>
      </div>
    </div>
    
    <div class="flex items-center justify-between pt-2 sm:pt-3 border-t border-gray-100 gap-2">
      <div class="flex items-center gap-1.5 sm:gap-2">
        <label class="flex items-center gap-1 sm:gap-1.5 px-2 sm:px-3 py-1 sm:py-1.5 text-gray-600 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors">
          <input 
            type="file" 
            @change="handleImageSelect" 
            accept="image/*" 
            multiple 
            class="hidden"
            ref="imageInput"
          />
          <i data-lucide="image" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
          <span class="text-[10px] sm:text-xs font-medium hidden xs:inline">Photos</span>
        </label>
        
        <select 
          v-model="newPost.privacy" 
          class="px-1.5 sm:px-2 py-1 sm:py-1.5 text-[10px] sm:text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="public">Public</option>
          <option value="friends">Friends</option>
          <option value="private">Private</option>
        </select>
      </div>
      
      <button 
        @click="createPost"
        :disabled="newPost.isSubmitting || (!newPost.content.trim() && newPost.images.length === 0)"
        class="px-3 sm:px-4 py-1 sm:py-1.5 bg-blue-600 text-white text-[10px] sm:text-xs font-medium rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors flex-shrink-0"
      >
        {{ newPost.isSubmitting ? 'Posting...' : 'Post' }}
      </button>
    </div>
  </div>
  
  <!-- Feed Posts -->
  <div class="space-y-3 sm:space-y-4">
    <!-- Empty state -->
    <div v-if="posts.length === 0" class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8 text-center">
      <i data-lucide="inbox" class="w-10 h-10 sm:w-12 sm:h-12 mx-auto text-gray-300 mb-2 sm:mb-3"></i>
      <p class="text-gray-500 text-xs sm:text-sm">No posts yet. Be the first to share something!</p>
    </div>
    
    <!-- Dynamic Posts -->
    <div v-for="post in posts" :key="post.id" class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-100 overflow-hidden">
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
      
      <!-- Post Actions -->
      <div class="p-3 sm:p-4 pt-2 sm:pt-3">
        <div class="flex items-center gap-4 sm:gap-5 mb-2 sm:mb-3">
          <button class="flex items-center gap-1 sm:gap-1.5 text-gray-600 hover:text-red-500 transition-colors">
            <i data-lucide="heart" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
            <span class="text-[10px] sm:text-xs font-medium">0</span>
          </button>
          <button class="flex items-center gap-1 sm:gap-1.5 text-gray-600 hover:text-blue-500 transition-colors">
            <i data-lucide="message-circle" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
            <span class="text-[10px] sm:text-xs font-medium">0</span>
          </button>
        </div>
        
        <!-- Comment Input -->
        <div class="flex items-center gap-1.5 sm:gap-2 pt-2 sm:pt-3 border-t border-gray-100">
          <img 
            :src="user.avatar" 
            :alt="user.username" 
            class="w-6 h-6 sm:w-8 sm:h-8 rounded-full object-cover border border-gray-200 flex-shrink-0"
          />
          <input 
            type="text" 
            placeholder="Write a comment..." 
            class="flex-1 min-w-0 px-2 sm:px-3 py-1 sm:py-1.5 bg-gray-50 rounded-full text-[10px] sm:text-xs text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <button class="p-1 sm:p-1.5 text-blue-600 hover:bg-blue-50 rounded-full transition-colors flex-shrink-0">
            <i data-lucide="send" class="w-3 h-3 sm:w-3.5 sm:h-3.5"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            user: {
                username: <?= json_encode($user['username'] ?? 'user') ?>,
                avatar: <?= json_encode($user['profile_photo_path'] ?? 'https://i.pravatar.cc/150?img=1') ?>
            },
            posts: <?= json_encode($postsArray ?? []) ?>,
            newPost: {
                content: '',
                images: [],
                imagePreview: [],
                privacy: 'public',
                isSubmitting: false,
                error: ''
            }
        }
    },
    methods: {
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            if (diffDays < 7) return `${diffDays}d ago`;
            
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        },
        autoResize(event) {
            const textarea = event.target;
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        },
        handleImageSelect(event) {
            const files = Array.from(event.target.files);
            this.newPost.error = '';
            
            // Validate file count
            if (this.newPost.images.length + files.length > 10) {
                this.newPost.error = 'You can upload a maximum of 10 images per post';
                event.target.value = '';
                return;
            }
            
            // Validate each file
            for (const file of files) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    this.newPost.error = 'Please upload valid image files (JPG, PNG, or GIF)';
                    event.target.value = '';
                    return;
                }
                
                // Validate file size (10MB max)
                const maxSize = 10 * 1024 * 1024;
                if (file.size > maxSize) {
                    this.newPost.error = 'Each image must be less than 10MB';
                    event.target.value = '';
                    return;
                }
                
                // Add to images array
                this.newPost.images.push(file);
                
                // Create preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.newPost.imagePreview.push(e.target.result);
                };
                reader.readAsDataURL(file);
            }
            
            event.target.value = '';
        },
        removeImage(index) {
            this.newPost.images.splice(index, 1);
            this.newPost.imagePreview.splice(index, 1);
        },
        async createPost() {
            this.newPost.error = '';
            this.newPost.isSubmitting = true;
            
            // Validate at least content or images
            if (!this.newPost.content.trim() && this.newPost.images.length === 0) {
                this.newPost.error = 'Please add some text or images to your post';
                this.newPost.isSubmitting = false;
                return;
            }
            
            // Create FormData for file upload
            const formData = new FormData();
            formData.append('content_text', this.newPost.content);
            formData.append('privacy', this.newPost.privacy);
            
            // Append all images
            this.newPost.images.forEach((image, index) => {
                formData.append('post_images[]', image);
            });
            
            try {
                const response = await fetch('/posts/create', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Reset form
                    this.newPost.content = '';
                    this.newPost.images = [];
                    this.newPost.imagePreview = [];
                    this.newPost.privacy = 'public';
                    if (this.$refs.postTextarea) {
                        this.$refs.postTextarea.style.height = 'auto';
                    }
                    
                    // Reload to show new post and Flash success message
                    window.location.reload();
                } else {
                    // Error will be shown via Flash message on reload
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error creating post:', error);
                this.newPost.error = 'Failed to create post. Please try again.';
            } finally {
                this.newPost.isSubmitting = false;
            }
        }
    },
    mounted() {
        // Initialize Lucide icons
        if (window.lucide) {
            lucide.createIcons();
        }
    },
    updated() {
        // Re-initialize Lucide icons after DOM updates
        if (window.lucide) {
            lucide.createIcons();
        }
    }
}).mount('#dashboardApp');
</script>
