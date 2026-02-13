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
</div>

<script>
  (function () {
    const el = document.getElementById('profileApp');
    if (!el) return;
    
    const app = Vue.createApp({
      data() {
        return {
          activeTab: 'posts',
          posts: <?= json_encode($postsArray ?? []) ?>,
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
          errors: {}
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
    });
    
    app.mount(el);
  })();
</script>
