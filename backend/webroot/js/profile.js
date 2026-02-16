(function () {
  const el = document.getElementById('profileApp');
  if (!el) return;
  
  const app = Vue.createApp({
    data() {
      return {
        activeTab: 'posts',
        currentUserId: window.profileData?.currentUserId || null,
        posts: (window.profileData?.posts || []).map(post => ({
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
          full_name: window.profileData?.user?.full_name || 'User',
          username: window.profileData?.user?.username || 'user',
          avatar: window.profileData?.user?.avatar || 'https://i.pravatar.cc/150?img=1',
          joinedDate: window.profileData?.user?.joinedDate || 'Joined recently',
          bio: window.profileData?.user?.bio || '🌍 Explorer · 📷 Photography enthusiast · ☕ Coffee lover',
          stats: {
            posts: window.profileData?.postCount || 0,
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
          const response = await fetch('/profile/update', {
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
      async openCommentInput(postId) {
        const post = this.posts.find(p => p.id === postId);
        if (!post) return;

        post.showComments = true;

        if (post.comments.length === 0) {
          try {
            await this.loadComments(postId);
          } catch (e) {
            console.error('Error loading comments for openCommentInput:', e);
          }
        }

        this.$nextTick(() => {
          const input = document.getElementById('comment-input-' + postId);
          if (input) input.focus();
        });
      },
      // Safe delegator used by templates to avoid "not a function" errors
        handleOpenComment(postId) {
          if (typeof this.openCommentInput === 'function') return this.openCommentInput(postId);
          if (typeof window.openCommentInput === 'function') return window.openCommentInput(postId);
          console.warn('openCommentInput not available on profile instance');
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
      // Expose global fallback handlers
      window.openCommentInput = this.openCommentInput.bind(this);
      window.handleOpenComment = this.handleOpenComment.bind(this);
      
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
      if (window.openCommentInput && window.openCommentInput === this.openCommentInput) {
        try { delete window.openCommentInput; } catch (e) { window.openCommentInput = undefined; }
      }
      if (window.handleOpenComment && window.handleOpenComment === this.handleOpenComment) {
        try { delete window.handleOpenComment; } catch (e) { window.handleOpenComment = undefined; }
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
