<div id="profileApp" class="space-y-3 sm:space-y-4 lg:space-y-6" v-cloak>
  <!-- Profile Header Card -->
  <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 lg:p-8 mb-3 sm:mb-4 lg:mb-6">
    <div class="flex flex-col md:flex-row items-start md:items-center gap-3 sm:gap-4 lg:gap-6">
      <!-- Profile Photo -->
      <div class="shrink-0 mx-auto md:mx-0">
        <img 
          :src="user.avatar" 
          :alt="user.full_name" 
          class="w-20 h-20 sm:w-24 sm:h-24 lg:w-32 lg:h-32 rounded-full object-cover border-2 sm:border-4 border-gray-100"
        />
      </div>
      
      <!-- Profile Info -->
      <div class="flex-1 text-center md:text-left w-full">
        <div class="mb-2 sm:mb-3">
          <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900">{{ user.full_name }}</h1>
          <p class="text-gray-500 text-sm sm:text-base mt-0.5 sm:mt-1">@{{ user.username }}</p>
          <p class="text-gray-400 text-xs sm:text-sm mt-0.5 sm:mt-1">{{ user.joinedDate }}</p>
        </div>
        
        <div class="mb-3 sm:mb-4">
          <p class="text-gray-700 text-xs sm:text-sm lg:text-base">{{ user.bio }}</p>
        </div>
        
        <!-- Stats -->
        <div class="flex justify-center md:justify-start gap-4 sm:gap-6 lg:gap-8 mb-3 sm:mb-4">
          <div>
            <span class="font-bold text-gray-900 text-sm sm:text-base lg:text-lg">{{ user.stats.posts }}</span>
            <span class="text-gray-500 text-xs sm:text-sm ml-1">Posts</span>
          </div>
          <div>
            <span class="font-bold text-gray-900 text-sm sm:text-base lg:text-lg">{{ user.stats.friends }}</span>
            <span class="text-gray-500 text-xs sm:text-sm ml-1">Friends</span>
          </div>
          <div>
            <span class="font-bold text-gray-900 text-sm sm:text-base lg:text-lg">{{ user.stats.likes }}</span>
            <span class="text-gray-500 text-xs sm:text-sm ml-1">Likes</span>
          </div>
        </div>
      </div>
      
      <!-- Edit Profile Button -->
      <div class="shrink-0 md:self-start w-full md:w-auto">
        <button @click="openEditModal" class="flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-1.5 sm:py-2 pr-1.5 sm:pr-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors w-full md:w-auto">
          <i data-lucide="settings" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-700"></i>
          <span class="text-xs sm:text-sm font-medium text-gray-700">Edit Profile</span>
        </button>
      </div>
    </div>
  </div>
  
  <!-- Tabs -->
  <div class="bg-white rounded-t-xl sm:rounded-t-2xl shadow-sm border border-gray-100 border-b-0">
    <div class="flex border-b border-gray-200">
      <button 
        @click="activeTab = 'posts'" 
        :class="{'border-b-2 border-gray-900 text-gray-900': activeTab === 'posts', 'text-gray-500': activeTab !== 'posts'}"
        class="flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 lg:px-6 py-2 sm:py-3 lg:py-4 font-medium text-xs sm:text-sm transition-colors"
      >
        <i data-lucide="layout-grid" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
        <span>Posts</span>
      </button>
      <button 
        @click="activeTab = 'saved'" 
        :class="{'border-b-2 border-gray-900 text-gray-900': activeTab === 'saved', 'text-gray-500': activeTab !== 'saved'}"
        class="flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 lg:px-6 py-2 sm:py-3 lg:py-4 font-medium text-xs sm:text-sm transition-colors"
      >
        <i data-lucide="bookmark" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
        <span>Saved</span>
      </button>
    </div>
  </div>
  
  <!-- Tab Content -->
  <div class="bg-white rounded-b-xl sm:rounded-b-2xl shadow-sm border border-gray-100">
    <!-- Posts Tab -->
    <div v-if="activeTab === 'posts'" class="p-3 sm:p-4 lg:p-6">
      <?= $this->element('posts/post_list', [
        'posts' => $postsArray ?? [],
        'currentUser' => $user ?? [],
        'emptyMessage' => 'No posts yet. Share your first post to get started!'
      ]) ?>
    </div>
    
    <!-- Saved Tab -->
    <div v-if="activeTab === 'saved'" class="p-3 sm:p-4 lg:p-6">
      <div class="text-center py-12 sm:py-16">
        <i data-lucide="bookmark" class="w-12 h-12 sm:w-16 sm:h-16 text-gray-300 mx-auto mb-3 sm:mb-4"></i>
        <p class="text-gray-500 text-base sm:text-lg">No saved posts</p>
        <p class="text-gray-400 text-xs sm:text-sm mt-1 sm:mt-2">Save posts to view them later</p>
      </div>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <?= $this->element('users/edit_profile_modal') ?>
  
  <!-- Image Viewer Modal -->
  <transition name="fade">
    <div 
      v-if="imageViewer.isOpen"
      @click="closeImageViewer"
      class="fixed inset-0 bg-black bg-opacity-95 z-[9999] flex items-center justify-center"
    >
      <!-- Close Button -->
      <button 
        @click="closeImageViewer"
        class="absolute top-4 right-4 text-white hover:text-gray-300 transition-colors z-10"
        title="Close (Esc)"
      >
        <i data-lucide="x" class="w-8 h-8"></i>
      </button>
      
      <!-- Image Counter -->
      <div class="absolute top-4 left-1/2 transform -translate-x-1/2 text-white text-sm font-medium bg-black bg-opacity-50 px-3 py-1.5 rounded-full">
        {{ imageViewer.currentIndex + 1 }} / {{ imageViewer.images.length }}
      </div>
      
      <!-- Previous Button -->
      <button 
        v-if="imageViewer.currentIndex > 0"
        @click.stop="prevImage"
        class="absolute left-4 text-white hover:text-gray-300 transition-colors bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full p-3"
        title="Previous (←)"
      >
        <i data-lucide="chevron-left" class="w-6 h-6"></i>
      </button>
      
      <!-- Image Container -->
      <div 
        @click.stop
        class="max-w-7xl max-h-screen w-full h-full flex items-center justify-center p-4"
      >
        <img 
          :src="imageViewer.images[imageViewer.currentIndex]?.image_path || imageViewer.images[imageViewer.currentIndex]"
          :alt="'Image ' + (imageViewer.currentIndex + 1)"
          class="max-w-full max-h-full object-contain"
        />
      </div>
      
      <!-- Next Button -->
      <button 
        v-if="imageViewer.currentIndex < imageViewer.images.length - 1"
        @click.stop="nextImage"
        class="absolute right-4 text-white hover:text-gray-300 transition-colors bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full p-3"
        title="Next (→)"
      >
        <i data-lucide="chevron-right" class="w-6 h-6"></i>
      </button>
    </div>
  </transition>
</div>

<script>
  (function () {
    const el = document.getElementById('profileApp');
    if (!el) return;
    
    const app = Vue.createApp({
      data() {
        return {
          activeTab: 'posts',
          currentUserId: <?= json_encode($currentUserId ?? null) ?>,
          posts: <?= json_encode($postsArray ?? []) ?>.map(post => ({
            ...post,
            showComments: false,
            newComment: '',
            commentImage: null,
            commentImagePreview: null,
            comments: [],
            showMenu: false,
            isEditing: false,
            editContent: '',
            editImages: [],
            removedImageIds: [],
            newEditImages: [],
            newEditImagePreviews: []
          })),
          user: {
            full_name: <?= json_encode(!empty($user['full_name']) ? $user['full_name'] : (!empty($user['username']) ? $user['username'] : 'User')) ?>,
            username: <?= json_encode(!empty($user['username']) ? $user['username'] : 'user') ?>,
            avatar: <?= json_encode(!empty($user['profile_photo_path']) ? $user['profile_photo_path'] : 'https://i.pravatar.cc/150?img=1') ?>,
            joinedDate: <?php 
              $joinedDate = 'Joined recently';
              if (!empty($user['created'])) {
                try {
                  $dateStr = is_string($user['created']) ? $user['created'] : (is_object($user['created']) ? $user['created']->format('Y-m-d') : '');
                  if ($dateStr) {
                    $timestamp = strtotime($dateStr);
                    if ($timestamp !== false) {
                      $joinedDate = 'Joined ' . date('M Y', $timestamp);
                    }
                  }
                } catch (Exception $e) {
                  $joinedDate = 'Joined recently';
                }
              }
              echo json_encode($joinedDate);
            ?>,
            bio: <?= json_encode(!empty($user['bio']) ? $user['bio'] : '🌍 Explorer · 📷 Photography enthusiast · ☕ Coffee lover') ?>,
            stats: {
              posts: <?= json_encode($postCount ?? 0) ?>,
              friends: '0',
              likes: '0'
            }
          },
          showEditModal: false,
          isSubmitting: false,
          showCurrentPassword: false,
          showNewPassword: false,
          showConfirmPassword: false,
          uploadError: '',
          editForm: {
            full_name: '',
            username: '',
            avatar: '',
            profile_picture_file: null,
            current_password: '',
            new_password: '',
            confirm_password: ''
          },
          errors: {},
          imageViewer: {
            isOpen: false,
            images: [],
            currentIndex: 0
          }
        };
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
        openImageViewer(images, index = 0) {
          this.imageViewer.images = images;
          this.imageViewer.currentIndex = index;
          this.imageViewer.isOpen = true;
          document.body.style.overflow = 'hidden';
          this.$nextTick(() => {
            if (window.lucide) lucide.createIcons();
          });
        },
        closeImageViewer() {
          this.imageViewer.isOpen = false;
          document.body.style.overflow = '';
        },
        nextImage() {
          if (this.imageViewer.currentIndex < this.imageViewer.images.length - 1) {
            this.imageViewer.currentIndex++;
          }
        },
        prevImage() {
          if (this.imageViewer.currentIndex > 0) {
            this.imageViewer.currentIndex--;
          }
        },
        openEditModal() {
          this.editForm = {
            full_name: this.user.full_name,
            username: this.user.username,
            avatar: this.user.avatar,
            profile_picture_file: null,
            current_password: '',
            new_password: '',
            confirm_password: ''
          };
          this.errors = {};
          this.uploadError = '';
          this.showEditModal = true;
          this.$nextTick(() => {
            if (window.lucide) lucide.createIcons();
          });
        },
        closeEditModal() {
          this.showEditModal = false;
          this.editForm = {
            full_name: '',
            username: '',
            avatar: '',
            profile_picture_file: null,
            current_password: '',
            new_password: '',
            confirm_password: ''
          };
          this.errors = {};
          this.uploadError = '';
        },
        handleFileChange(event) {
          const file = event.target.files[0];
          if (!file) return;

          // Validate file type
          const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
          if (!validTypes.includes(file.type)) {
            this.uploadError = 'Please upload a valid image file (JPG, PNG, or GIF)';
            event.target.value = '';
            return;
          }

          // Validate file size (5MB max)
          const maxSize = 5 * 1024 * 1024;
          if (file.size > maxSize) {
            this.uploadError = 'File size must be less than 5MB';
            event.target.value = '';
            return;
          }

          this.uploadError = '';
          this.editForm.profile_picture_file = file;

          // Preview the image
          const reader = new FileReader();
          reader.onload = (e) => {
            this.editForm.avatar = e.target.result;
          };
          reader.readAsDataURL(file);
        },
        async handleSubmit() {
          this.errors = {};
          this.isSubmitting = true;

          // Validate form
          if (!this.editForm.full_name || this.editForm.full_name.trim() === '') {
            this.errors.full_name = 'Full name is required';
            this.isSubmitting = false;
            return;
          }

          // Validate password fields if any password field is filled
          if (this.editForm.current_password || this.editForm.new_password || this.editForm.confirm_password) {
            if (!this.editForm.current_password) {
              this.errors.current_password = 'Current password is required to change password';
              this.isSubmitting = false;
              return;
            }
            if (!this.editForm.new_password) {
              this.errors.new_password = 'New password is required';
              this.isSubmitting = false;
              return;
            }
            if (this.editForm.new_password.length < 6) {
              this.errors.new_password = 'Password must be at least 6 characters';
              this.isSubmitting = false;
              return;
            }
            if (this.editForm.new_password !== this.editForm.confirm_password) {
              this.errors.confirm_password = 'Passwords do not match';
              this.isSubmitting = false;
              return;
            }
          }

          // Create FormData for file upload
          const formData = new FormData();
          formData.append('full_name', this.editForm.full_name);
          
          if (this.editForm.profile_picture_file) {
            formData.append('profile_picture', this.editForm.profile_picture_file);
          }
          
          if (this.editForm.current_password) {
            formData.append('current_password', this.editForm.current_password);
            formData.append('new_password', this.editForm.new_password);
          }

          try {
            const response = await fetch('/users/update-profile', {
              method: 'POST',
              body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
              // Update user data with response
              this.user.full_name = data.user.full_name;
              this.user.username = data.user.username;
              if (data.user.profile_photo_path) {
                this.user.avatar = data.user.profile_photo_path;
              }
              
              // Close modal first
              this.closeEditModal();
              
              // Reload to show Flash success message
              window.location.reload();
            } else {
              // Handle validation errors - reload to show Flash error message
              if (data.errors) {
                this.errors = data.errors;
              }
              window.location.reload();
            }
          } catch (error) {
            console.error('Error updating profile:', error);
            // Redirect with error flag
            window.location.href = window.location.pathname + '?error=network';
          } finally {
            this.isSubmitting = false;
          }
        },
        async toggleComments(postId) {
          const post = this.posts.find(p => p.id === postId);
          if (!post) return;
          
          post.showComments = !post.showComments;
          
          if (post.showComments && post.comments.length === 0) {
            await this.loadComments(postId);
          }
        },
        async loadComments(postId) {
          try {
            const response = await fetch(`/comments/get-by-post/${postId}`, {
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              }
            });
            
            if (!response.ok) throw new Error('Failed to load comments');
            
            const data = await response.json();
            const post = this.posts.find(p => p.id === postId);
            if (post && data.comments) {
              post.comments = data.comments.map(comment => ({
                ...comment,
                is_liked: false,
                like_count: 0
              }));
              
              for (const comment of post.comments) {
                await this.loadCommentLikeStatus(postId, comment.id);
              }
            }
          } catch (error) {
            console.error('Error loading comments:', error);
          }
        },
        async loadCommentLikeStatus(postId, commentId) {
          try {
            const response = await fetch(`/likes/comment/${commentId}`, {
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              }
            });
            
            if (response.ok) {
              const data = await response.json();
              const post = this.posts.find(p => p.id === postId);
              if (post) {
                const comment = post.comments.find(c => c.id === commentId);
                if (comment && data) {
                  comment.like_count = data.count || 0;
                  comment.is_liked = data.is_liked || false;
                }
              }
            }
          } catch (error) {
            console.error('Error loading comment like status:', error);
          }
        },
        async submitComment(postId) {
          const post = this.posts.find(p => p.id === postId);
          if (!post) return;
          
          const text = post.newComment?.trim();
          if (!text && !post.commentImage) {
            return;
          }
          
          const formData = new FormData();
          formData.append('post_id', postId);
          if (text) formData.append('content_text', text);
          if (post.commentImage) formData.append('content_image', post.commentImage);
          
          try {
            const response = await fetch('/comments/add', {
              method: 'POST',
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: formData
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
              post.newComment = '';
              post.commentImage = null;
              post.commentImagePreview = null;
              
              const fileInput = document.getElementById('comment-image-' + postId);
              if (fileInput) fileInput.value = '';
              
              post.comment_count = (post.comment_count || 0) + 1;
              
              if (post.showComments && data.comment) {
                const newComment = {
                  ...data.comment,
                  is_liked: false,
                  like_count: 0
                };
                post.comments.push(newComment);
              } else {
                post.showComments = true;
                await this.loadComments(postId);
              }
              
              if (window.lucide) {
                this.$nextTick(() => lucide.createIcons());
              }
            } else {
              console.error('Error posting comment:', data.message);
              alert(data.message || 'Failed to post comment. Please try again.');
            }
          } catch (error) {
            console.error('Error submitting comment:', error);
            alert('Failed to post comment. Please try again.');
          }
        },
        async toggleCommentLike(postId, commentId) {
          try {
            const response = await fetch(`/likes/toggle-comment/${commentId}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              }
            });
            
            if (!response.ok) throw new Error('Failed to toggle like');
            
            const data = await response.json();
            if (data.success) {
              const post = this.posts.find(p => p.id === postId);
              if (post) {
                const comment = post.comments.find(c => c.id === commentId);
                if (comment) {
                  comment.is_liked = data.liked;
                  comment.like_count = data.likeCount;
                }
              }
            }
          } catch (error) {
            console.error('Error toggling comment like:', error);
          }
        },
        handleCommentImage(event, postId) {
          const file = event.target.files[0];
          if (!file) return;
          
          const post = this.posts.find(p => p.id === postId);
          if (!post) return;
          
          const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
          if (!validTypes.includes(file.type)) {
            alert('Please upload a valid image file (JPG, PNG, or GIF)');
            return;
          }
          
          if (file.size > 10 * 1024 * 1024) {
            alert('Image must be less than 10MB');
            return;
          }
          
          post.commentImage = file;
          
          const reader = new FileReader();
          reader.onload = (e) => {
            post.commentImagePreview = e.target.result;
          };
          reader.readAsDataURL(file);
        },
        removeCommentImage(postId) {
          const post = this.posts.find(p => p.id === postId);
          if (post) {
            post.commentImage = null;
            post.commentImagePreview = null;
            const input = document.getElementById('comment-image-' + postId);
            if (input) input.value = '';
          }
        },
        triggerCommentImageInput(postId) {
          const input = document.getElementById('comment-image-' + postId);
          if (input) input.click();
        },
        showCommentOptions(postId) {
          const post = this.posts.find(p => p.id === postId);
          if (post && !post.showComments && post.comment_count > 0) {
            // Optionally auto-show comments when user starts typing
          }
        },
        canEditPost(post) {
          return this.currentUserId && post.user_id === this.currentUserId;
        },
        togglePostMenu(postId, event) {
          // Stop event propagation to prevent immediate click-outside
          if (event) {
            event.stopPropagation();
          }
          
          const post = this.posts.find(p => p.id === postId);
          if (post) {
            // Close all other menus first
            this.posts.forEach(p => {
              if (p.id !== postId) {
                p.showMenu = false;
              }
            });
            
            // Toggle this menu
            post.showMenu = !post.showMenu;
            this.$nextTick(() => {
              if (window.lucide) lucide.createIcons();
            });
          }
        },
        editPost(postId) {
          // Close menu
          const post = this.posts.find(p => p.id === postId);
          if (!post) return;
          
          post.showMenu = false;
          
          // Enter edit mode
          post.isEditing = true;
          post.editContent = post.content_text || '';
          post.editImages = JSON.parse(JSON.stringify(post.post_images || [])); // Deep copy
          post.removedImageIds = [];
          post.newEditImages = [];
          post.newEditImagePreviews = [];
          
          this.$nextTick(() => {
            if (window.lucide) lucide.createIcons();
          });
        },
        cancelEditPost(postId) {
          const post = this.posts.find(p => p.id === postId);
          if (!post) return;
          
          // Exit edit mode and reset
          post.isEditing = false;
          post.editContent = '';
          post.editImages = [];
          post.removedImageIds = [];
          post.newEditImages = [];
          post.newEditImagePreviews = [];
          
          // Clear file input
          const fileInput = document.getElementById('edit-images-' + postId);
          if (fileInput) fileInput.value = '';
        },
        removeExistingImage(postId, imageId, index) {
          const post = this.posts.find(p => p.id === postId);
          if (!post) return;
          
          // Add to removed list
          post.removedImageIds.push(imageId);
          
          // Remove from edit images array
          post.editImages.splice(index, 1);
        },
        removeNewEditImage(postId, index) {
          const post = this.posts.find(p => p.id === postId);
          if (!post) return;
          
          // Remove from new images arrays
          post.newEditImages.splice(index, 1);
          post.newEditImagePreviews.splice(index, 1);
        },
        handleEditImageSelect(event, postId) {
          const post = this.posts.find(p => p.id === postId);
          if (!post) return;
          
          const files = Array.from(event.target.files);
          
          // Validate file count
          const totalImages = post.editImages.length + post.newEditImages.length + files.length;
          if (totalImages > 10) {
            alert('Maximum 10 images per post');
            event.target.value = '';
            return;
          }
          
          // Process each file
          files.forEach(file => {
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            if (!validTypes.includes(file.type)) {
              alert('Please upload valid image files (JPG, PNG, or GIF)');
              return;
            }
            
            // Validate file size
            if (file.size > 10 * 1024 * 1024) {
              alert('Each image must be less than 10MB');
              return;
            }
            
            // Add to new images
            post.newEditImages.push(file);
            
            // Create preview
            const reader = new FileReader();
            reader.onload = (e) => {
              post.newEditImagePreviews.push(e.target.result);
            };
            reader.readAsDataURL(file);
          });
          
          event.target.value = '';
          
          this.$nextTick(() => {
            if (window.lucide) lucide.createIcons();
          });
        },
        async saveEditPost(postId) {
          const post = this.posts.find(p => p.id === postId);
          if (!post) return;
          
          // Validate at least content or images
          const hasContent = post.editContent && post.editContent.trim();
          const hasImages = (post.editImages.length + post.newEditImages.length) > 0;
          
          if (!hasContent && !hasImages) {
            alert('Post must have either text or images');
            return;
          }
          
          // Create FormData
          const formData = new FormData();
          formData.append('content_text', post.editContent || '');
          
          // Add removed image IDs
          if (post.removedImageIds.length > 0) {
            post.removedImageIds.forEach(id => {
              formData.append('removed_images[]', id);
            });
          }
          
          // Add new images
          if (post.newEditImages.length > 0) {
            post.newEditImages.forEach(image => {
              formData.append('new_images[]', image);
            });
          }
          
          try {
            const response = await fetch(`/posts/edit/${postId}`, {
              method: 'POST',
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: formData
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
              // Update post with new data
              post.content_text = data.post.content_text;
              post.post_images = data.post.post_images || [];
              post.modified = data.post.modified;
              
              // Exit edit mode
              post.isEditing = false;
              post.editContent = '';
              post.editImages = [];
              post.removedImageIds = [];
              post.newEditImages = [];
              post.newEditImagePreviews = [];
              
              this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
              });
              
              alert('Post updated successfully!');
            } else {
              alert(data.message || 'Failed to update post. Please try again.');
            }
          } catch (error) {
            console.error('Error updating post:', error);
            alert('Failed to update post. Please try again.');
          }
        },
        async deletePost(postId) {
          const post = this.posts.find(p => p.id === postId);
          if (post) post.showMenu = false;
          
          if (!confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
            return;
          }
          
          try {
            const response = await fetch(`/posts/delete/${postId}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              }
            });
            
            if (response.ok) {
              // Remove post from array
              this.posts = this.posts.filter(p => p.id !== postId);
              this.user.stats.posts = this.posts.length;
              
              // Show success message
              alert('Post deleted successfully!');
            } else {
              alert('Failed to delete post. Please try again.');
            }
          } catch (error) {
            console.error('Error deleting post:', error);
            alert('Failed to delete post. Please try again.');
          }
        },
        handleClickOutside(event) {
          // Close all post menus when clicking outside
          const target = event.target;
          // Check if click is not on the menu button or inside the menu
          if (!target.closest('[data-post-menu]') && !target.closest('button[data-menu-trigger]')) {
            this.posts.forEach(post => {
              if (post.showMenu) {
                post.showMenu = false;
              }
            });
          }
        }
      },
      mounted() {
        // Initialize Lucide icons
        if (window.lucide) {
          lucide.createIcons();
        }
        
        // Close post menu when clicking outside
        document.addEventListener('click', this.handleClickOutside);
        
        // Handle keyboard navigation for image viewer
        document.addEventListener('keydown', (e) => {
          if (this.imageViewer.isOpen) {
            if (e.key === 'Escape') {
              this.closeImageViewer();
            } else if (e.key === 'ArrowLeft') {
              this.prevImage();
            } else if (e.key === 'ArrowRight') {
              this.nextImage();
            }
          }
        });
      },
      beforeUnmount() {
        // Clean up event listener
        document.removeEventListener('click', this.handleClickOutside);
      },
      updated() {
        // Re-initialize Lucide icons after DOM updates
        if (window.lucide) {
          lucide.createIcons();
        }
      }
    });
    
    app.mount(el);
  })();
</script>
