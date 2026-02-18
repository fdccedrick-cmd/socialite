(function () {
  console.log('=== profile.js v2.1 LOADED (with image comment debugging) ===');
  try { console.log('profile.js loaded - window.profileData:', window.profileData); } catch(e){}
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
          bio: window.profileData?.user?.bio || null,
          stats: {
            posts: window.profileData?.postCount || 0,
            friends: '0',
            likes: (window.profileData && typeof window.profileData.likes === 'number') 
              ? window.profileData.likes 
              : 0
          }
        },
        showEditModal: false,
        isSubmitting: false,
        
        uploadError: '',
        // cropper state
        showCropper: false,
        cropperInstance: null,
        cropperImageSrc: null,
        cropPreviewSrc: null,
        // Store cropped file separately to avoid reactivity issues
        croppedFile: null,
        croppedDataURL: null,

        editForm: {
          full_name: '',
          username: '',
          avatar: '',
          profile_picture_file: null,
          bio: ''
        },
        errors: {},
        imageViewer: {
          isOpen: false,
          images: [],
          currentIndex: 0
        },
        postDetailView: {
          isOpen: false,
          post: null,
          imageIndex: 0,
          currentImageId: null,
          imageComments: [],
          imageLikeCount: 0,
          imageIsLiked: false,
          imageNewComment: ''
        },
        appReady: false
      };
    },
    computed: {
      window() {
        return window;
      }
    },
    methods: {
        openPostDetailView(post, imageIndex = 0) {
          const p = typeof post === 'object' && post && post.id ? post : this.posts.find(ps => ps.id === post);
          if (!p) return;
          this.postDetailView.post = p;
          this.postDetailView.imageIndex = Math.max(0, Math.min(imageIndex, (p.post_images && p.post_images.length) ? p.post_images.length - 1 : 0));
          this.postDetailView.isOpen = true;
          if (!p.showComments && (p.comment_count > 0 || p.post_images?.length)) {
            p.showComments = true;
            if (p.comments.length === 0) this.loadComments(p.id);
          }
          this.postDetailView.imageComments = [];
          this.postDetailView.imageLikeCount = 0;
          this.postDetailView.imageIsLiked = false;
          this.postDetailView.currentImageId = null;
          this.postDetailView.imageNewComment = '';
          const img = p.post_images && p.post_images[this.postDetailView.imageIndex];
          if (p.post_images && p.post_images.length >= 2 && img && img.id) {
            this.postDetailView.currentImageId = img.id;
            this.loadImageComments(img.id);
            this.loadImageLikeStatus(img.id);
          }
          document.body.style.overflow = 'hidden';
          this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        closePostDetailView() {
          this.postDetailView.isOpen = false;
          this.postDetailView.post = null;
          this.postDetailView.imageIndex = 0;
          this.postDetailView.currentImageId = null;
          this.postDetailView.imageComments = [];
          document.body.style.overflow = '';
          this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        async loadImageComments(postImageId) {
          try {
            const response = await fetch(`/comments/get-by-post-image/${postImageId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) return;
            const data = await response.json();
            this.postDetailView.imageComments = (data.comments || data || []).map(c => ({ ...c, is_liked: false, like_count: 0 }));
            for (let i = 0; i < this.postDetailView.imageComments.length; i++) {
              const c = this.postDetailView.imageComments[i];
              const likeRes = await fetch(`/likes/comment/${c.id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
              if (likeRes.ok) {
                const likeData = await likeRes.json();
                this.postDetailView.imageComments[i].like_count = likeData.count || 0;
                this.postDetailView.imageComments[i].is_liked = likeData.is_liked || false;
              }
            }
          } catch (e) { console.error('Error loading image comments:', e); }
        },
        async loadImageLikeStatus(postImageId) {
          try {
            const response = await fetch(`/likes/post-image/${postImageId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) return;
            const data = await response.json();
            this.postDetailView.imageLikeCount = data.count ?? 0;
            this.postDetailView.imageIsLiked = data.is_liked ?? false;
          } catch (e) { console.error('Error loading image like status:', e); }
        },
        async toggleImageLike() {
          if (!this.postDetailView.currentImageId) return;
          try {
            const response = await fetch(`/likes/toggle-post-image/${this.postDetailView.currentImageId}`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
              credentials: 'same-origin'
            });
            if (!response.ok) return;
            const data = await response.json();
            if (data.success) {
              this.postDetailView.imageLikeCount = data.likeCount ?? this.postDetailView.imageLikeCount;
              this.postDetailView.imageIsLiked = data.liked ?? false;
            }
          } catch (e) { console.error('Error toggling image like:', e); }
        },
        async submitImageComment() {
          const v = this.postDetailView;
          if (!v.post || !v.currentImageId) return;
          const text = (v.imageNewComment || '').trim();
          if (!text) return;
          const formData = new FormData();
          formData.append('post_id', v.post.id);
          formData.append('post_image_id', v.currentImageId);
          formData.append('content_text', text);
          
          // Log all FormData entries
          console.log('FormData contents:');
          for (let [key, value] of formData.entries()) {
            console.log(`  ${key}:`, value);
          }
          
          try {
            const response = await fetch('/comments/add', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: formData });
            const data = await response.json();
            console.log('submitImageComment response:', { ok: response.ok, data });
            if (response.ok && data.success) {
              v.imageNewComment = '';
              v.imageComments.push({ ...data.comment, is_liked: false, like_count: 0 });
            } else {
              console.error('Failed to submit image comment:', data);
            }
          } catch (e) { console.error('Error submitting image comment:', e); }
        },
        postDetailPrevImage() {
          if (!this.postDetailView.post || !this.postDetailView.post.post_images?.length) return;
          if (this.postDetailView.imageIndex > 0) {
            this.postDetailView.imageIndex--;
            const imgs = this.postDetailView.post.post_images;
            const img = imgs[this.postDetailView.imageIndex];
            console.log('postDetailPrevImage - switching to index:', this.postDetailView.imageIndex, 'img:', img);
            if (imgs.length >= 2 && img && img.id) {
              this.postDetailView.currentImageId = img.id;
              console.log('postDetailPrevImage - set currentImageId to:', img.id);
              this.loadImageComments(img.id);
              this.loadImageLikeStatus(img.id);
            } else {
              this.postDetailView.currentImageId = null;
              console.log('postDetailPrevImage - cleared currentImageId');
            }
          }
        },
        postDetailNextImage() {
          if (!this.postDetailView.post || !this.postDetailView.post.post_images?.length) return;
          if (this.postDetailView.imageIndex < this.postDetailView.post.post_images.length - 1) {
            this.postDetailView.imageIndex++;
            const imgs = this.postDetailView.post.post_images;
            const img = imgs[this.postDetailView.imageIndex];
            console.log('postDetailNextImage - switching to index:', this.postDetailView.imageIndex, 'img:', img);
            if (imgs.length >= 2 && img && img.id) {
              this.postDetailView.currentImageId = img.id;
              console.log('postDetailNextImage - set currentImageId to:', img.id);
              this.loadImageComments(img.id);
              this.loadImageLikeStatus(img.id);
            } else {
              this.postDetailView.currentImageId = null;
            }
          }
        },
      ensureCropperLoaded() {
        if (window.Cropper) return Promise.resolve();
        const cssHref = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css';
        const jsSrc = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js';

       
        if (![...document.styleSheets].some(s => s.href && s.href.indexOf('cropperjs') !== -1)) {
          const link = document.createElement('link');
          link.rel = 'stylesheet';
          link.href = cssHref;
          document.head.appendChild(link);
        }

        
        return new Promise((resolve, reject) => {
          if (window.Cropper) return resolve();
        
          if (document.querySelector('script[data-cropperjs]')) {
            const checkInterval = setInterval(() => {
              if (window.Cropper) {
                clearInterval(checkInterval);
                resolve();
              }
            }, 50);
            setTimeout(() => reject(new Error('Cropper failed to load')), 5000);
            return;
          }

          const script = document.createElement('script');
          script.src = jsSrc;
          script.setAttribute('data-cropperjs', '1');
          script.onload = () => {
            if (window.Cropper) resolve();
            else reject(new Error('Cropper loaded but not available'));
          };
          script.onerror = () => reject(new Error('Failed to load Cropper.js'));
          document.head.appendChild(script);
        });
      },
      noop() {},
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
      async toggleLike(postId) {
        console.debug('toggleLike called for', postId);
        try {
          const response = await fetch(`/likes/toggle-post/${postId}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
          });

          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }

          const data = await response.json();

          if (data.success) {
            const postIndex = this.posts.findIndex(p => p.id === postId);
            if (postIndex !== -1) {
              const updatedPost = {
                ...this.posts[postIndex],
                is_liked: data.liked,
                like_count: data.likeCount
              };
              this.posts.splice(postIndex, 1, updatedPost);
              
              const likeDelta = data.liked ? 1 : -1;
              this.user.stats.likes = Math.max(0, this.user.stats.likes + likeDelta);
            }
          }
        } catch (error) {
          console.error('Error toggling like:', error);
        }
      },
      openImageViewer(images, index = 0) {
        if (!Array.isArray(images)) {
          images = [images];
          index = 0;
        }
        images = images.map(img => (typeof img === 'string' ? { image_path: img } : img));
        this.imageViewer.images = images;
        this.imageViewer.currentIndex = Math.max(0, Math.min(index, images.length - 1));
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
          bio: this.user.bio,
          current_password: '',
          new_password: '',
          confirm_password: ''
        };
        this.croppedFile = null;
        this.croppedDataURL = null;
        this.errors = {};
        console.log('[TRACK] openEditModal - editForm initialized', { full_name: this.editForm.full_name, bio: this.editForm.bio, username: this.editForm.username });
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
          bio: '',
          current_password: '',
          new_password: '',
          confirm_password: ''
        };
        this.croppedFile = null;
        this.croppedDataURL = null;
        this.errors = {};
        this.uploadError = '';
        console.log('[TRACK] closeEditModal - form cleared');
      },
      async handleFileChange(event) {
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

        // Preview for cropping
        const reader = new FileReader();
        reader.onload = async (e) => {
          this.cropperImageSrc = e.target.result;
          this.cropPreviewSrc = e.target.result;
          // ensure Cropper is loaded before attempting to initialize
          try {
            await this.ensureCropperLoaded();
          } catch (err) {
            console.error('Could not load cropper assets', err);
            this.uploadError = 'Failed to load image editor. Try again later.';
            return;
          }

          this.showCropper = true;
          this.$nextTick(() => {
            try {
              if (this.cropperInstance) {
                this.cropperInstance.destroy();
                this.cropperInstance = null;
              }
              const image = document.getElementById('cropperImage');
              if (image && window.Cropper) {
                this.cropperInstance = new Cropper(image, {
                  aspectRatio: 1,
                  viewMode: 1,
                  background: false,
                  autoCropArea: 1,
                  movable: true,
                  zoomable: true,
                  rotatable: false,
                  scalable: false,
                  ready: () => {
                    try { this.updateCropPreview(); } catch(e){}
                  },
                  crop: () => { this.updateCropPreview(); }
                });
              }
            } catch (e) {
              console.error('Failed to init cropper', e);
            }
          });
        };
        reader.readAsDataURL(file);
      },

      updateCropPreview() {
        if (!this.cropperInstance) return;
        try {
          const canvas = this.cropperInstance.getCroppedCanvas({ width: 300, height: 300 });
          this.cropPreviewSrc = canvas.toDataURL('image/png');
        } catch (e) {
          // ignore
        }
      },

      cropAndUse() {
        if (!this.cropperInstance) return;
        console.log('[TRACK] cropAndUse - starting crop process');
        try {
          const canvas = this.cropperInstance.getCroppedCanvas({ width: 400, height: 400, imageSmoothingQuality: 'high' });
          console.log('[TRACK] cropAndUse - canvas created:', canvas.width, 'x', canvas.height);
          canvas.toBlob((blob) => {
            console.log('[TRACK] cropAndUse - blob callback fired, blob:', blob);
            if (!blob) {
              console.error('[TRACK] cropAndUse - blob is null');
              this.uploadError = 'Failed to crop image';
              return;
            }
            const file = new File([blob], 'profile_' + Date.now() + '.png', { type: 'image/png' });
            console.log('[TRACK] cropAndUse - File created:', file.name, file.type, file.size, 'bytes');
            
            // Store file in both locations for redundancy
            this.croppedFile = file;
            this.editForm.profile_picture_file = file;
            
            // Store dataURL as backup
            const dataURL = canvas.toDataURL('image/png');
            this.croppedDataURL = dataURL;
            this.editForm.avatar = dataURL;
            
            console.log('[TRACK] cropAndUse - Stored file:', {
              croppedFile: this.croppedFile ? this.croppedFile.name : 'NULL',
              editFormFile: this.editForm.profile_picture_file ? this.editForm.profile_picture_file.name : 'NULL',
              hasDataURL: !!this.croppedDataURL
            });
            
            this.showCropper = false;
            try { this.cropperInstance.destroy(); } catch(e){}
            this.cropperInstance = null;
            this.cropperImageSrc = null;
            this.cropPreviewSrc = null;
            // clear file input value to keep behavior consistent
            const input = document.getElementById('profilePictureInput');
            if (input) input.value = '';
            console.log('[TRACK] cropAndUse - completed successfully');
          }, 'image/png');
        } catch (e) {
          console.error('[TRACK] cropAndUse error', e);
        }
      },

      cancelCrop() {
        console.log('[TRACK] cancelCrop - clearing cropper');
        this.showCropper = false;
        this.editForm.profile_picture_file = null;
        this.croppedFile = null;
        this.croppedDataURL = null;
        this.cropperImageSrc = null;
        this.cropPreviewSrc = null;
        if (this.cropperInstance) {
          try { this.cropperInstance.destroy(); } catch(e){}
          this.cropperInstance = null;
        }
        const input = document.getElementById('profilePictureInput');
        if (input) input.value = '';
      },
      async handleSubmit() {
        this.errors = {};
        this.isSubmitting = true;

        // Debug: log what is about to be submitted
        console.log('[TRACK] === handleSubmit START ===');
        console.log('[TRACK] State check:', {
          croppedFile: this.croppedFile ? this.croppedFile.name : 'NULL',
          editFormFile: this.editForm.profile_picture_file ? this.editForm.profile_picture_file.name : 'NULL',
          hasDataURL: !!this.croppedDataURL,
          avatarType: typeof this.editForm.avatar,
          avatarPrefix: this.editForm.avatar ? this.editForm.avatar.substring(0, 20) : 'NULL'
        });

        // Validate form
        if (!this.editForm.full_name || this.editForm.full_name.trim() === '') {
          this.errors.full_name = 'Full name is required';
          this.isSubmitting = false;
          return;
        }

        // Create FormData for file upload
        const formData = new FormData();
        formData.append('full_name', this.editForm.full_name);
        formData.append('bio', this.editForm.bio);
        
        // Try multiple sources for the file, with priority order
        let fileToUpload = this.croppedFile || this.editForm.profile_picture_file;
        
        if (fileToUpload) {
          console.log('[TRACK] Appending file to FormData:', fileToUpload.name, fileToUpload.type, fileToUpload.size);
          formData.append('profile_picture', fileToUpload);
        } else if (this.croppedDataURL || (this.editForm.avatar && typeof this.editForm.avatar === 'string' && this.editForm.avatar.indexOf('data:') === 0)) {
          // Fallback: if cropped image is stored as data URL but File not present, convert to blob
          console.log('[TRACK] Using dataURL fallback');
          try {
            const dataUrl = this.croppedDataURL || this.editForm.avatar;
            const arr = dataUrl.split(',');
            const mime = arr[0].match(/:(.*?);/)[1];
            const bstr = atob(arr[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while (n--) {
              u8arr[n] = bstr.charCodeAt(n);
            }
            const blob = new Blob([u8arr], { type: mime });
            const file = new File([blob], 'profile_' + Date.now() + '.png', { type: mime });
            formData.append('profile_picture', file);
            console.log('[TRACK] Appended fallback blob as profile_picture:', file.name, file.type, file.size);
          } catch (e) {
            console.error('[TRACK] Failed to convert dataURL to blob fallback', e);
          }
        } else {
          console.log('[TRACK] No profile picture to upload');
        }
        
        

        try {
          const headers = { 'X-Requested-With': 'XMLHttpRequest' };
          const meta = document.querySelector('meta[name="csrf-token"]');
          if (meta && meta.getAttribute('content')) headers['X-CSRF-Token'] = meta.getAttribute('content');

          // Debug: log formData contents
          try {
            console.log('[TRACK] Submitting FormData entries:');
            for (const entry of formData.entries()) {
              const [k, v] = entry;
              if (v instanceof File) {
                console.log('[TRACK] FormData entry file:', k, v.name, v.type, v.size);
              } else {
                console.log('[TRACK] FormData entry:', k, v);
              }
            }
          } catch (e) { console.error('[TRACK] FormData debug error', e); }

          const response = await fetch('/profile/update', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers
          });

          let data = {};
          try {
            data = await response.json();
          } catch (e) {
            const text = await response.text().catch(() => '');
            console.error('[TRACK] Profile update: failed to parse JSON response', e, text);
          }
          console.log('[TRACK] Profile update response from backend:', data);
          console.log('[TRACK] Profile update - reached_controller:', data.reached_controller === true);
          
          if (data.success) {
            console.log('[TRACK] Profile update SUCCESS');
            // Update user data with response
            this.user.full_name = data.user.full_name;
            this.user.username = data.user.username;
            this.user.bio = data.user.bio;
            if (data.user.profile_photo_path) {
              console.log('[TRACK] Updating avatar to:', data.user.profile_photo_path);
              this.user.avatar = data.user.profile_photo_path;
            }
            
            // Close modal first
            this.closeEditModal();
            
            // Reload to show Flash success message
            window.location.reload();
          } else {
            // Handle validation errors - reload to show Flash error message
            console.log('[TRACK] Profile update FAILED:', data.errors || 'unknown error');
            if (data.errors) {
              this.errors = data.errors;
            }
            window.location.reload();
          }
          } catch (error) {
          console.error('[TRACK] Error updating profile:', error);
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
            if (typeof window.showFlash === 'function') window.showFlash(data.message || 'Failed to post comment. Please try again.', 'error');
            else alert(data.message || 'Failed to post comment. Please try again.');
          }
        } catch (error) {
          console.error('Error submitting comment:', error);
          if (typeof window.showFlash === 'function') window.showFlash('Failed to post comment. Please try again.', 'error');
          else alert('Failed to post comment. Please try again.');
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
          if (typeof window.showFlash === 'function') window.showFlash('Please upload a valid image file (JPG, PNG, or GIF)', 'error');
          else alert('Please upload a valid image file (JPG, PNG, or GIF)');
          return;
        }
        
        if (file.size > 10 * 1024 * 1024) {
          if (typeof window.showFlash === 'function') window.showFlash('Image must be less than 10MB', 'error');
          else alert('Image must be less than 10MB');
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
        return this.currentUserId && post && post.user_id === this.currentUserId;
      },
      safeCanEditPost(post) {
        return typeof this.canEditPost === 'function' && this.canEditPost(post);
      },
      safeOpenPostDetailView(post, index) {
        if (typeof this.openPostDetailView === 'function') this.openPostDetailView(post, index);
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
          if (typeof window.showFlash === 'function') window.showFlash('Maximum 10 images per post', 'error');
          else alert('Maximum 10 images per post');
          event.target.value = '';
          return;
        }
        
        // Process each file
        files.forEach(file => {
          // Validate file type
          const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
          if (!validTypes.includes(file.type)) {
            if (typeof window.showFlash === 'function') window.showFlash('Please upload valid image files (JPG, PNG, or GIF)', 'error');
            else alert('Please upload valid image files (JPG, PNG, or GIF)');
            return;
          }
          
          // Validate file size
          if (file.size > 10 * 1024 * 1024) {
            if (typeof window.showFlash === 'function') window.showFlash('Each image must be less than 10MB', 'error');
            else alert('Each image must be less than 10MB');
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
          if (typeof window.showFlash === 'function') window.showFlash('Post must have either text or images', 'error');
          else alert('Post must have either text or images');
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
            
            if (typeof window.showFlash === 'function') window.showFlash('Post updated successfully!', 'success');
            else alert('Post updated successfully!');
          } else {
            if (typeof window.showFlash === 'function') window.showFlash(data.message || 'Failed to update post. Please try again.', 'error');
            else alert(data.message || 'Failed to update post. Please try again.');
          }
        } catch (error) {
          console.error('Error updating post:', error);
            if (typeof window.showFlash === 'function') window.showFlash('Failed to update post. Please try again.', 'error');
            else alert('Failed to update post. Please try again.');
        }
      },
      async deletePost(postId) {
        const post = this.posts.find(p => p.id === postId);
        if (post) post.showMenu = false;
        
        // Use the global modal helper if available, otherwise fallback to window.confirm
        let confirmed = false;
        if (typeof window.showConfirmModal === 'function') {
          confirmed = await window.showConfirmModal('Are you sure you want to delete this post? This action cannot be undone.');
        } else {
          confirmed = confirm('Are you sure you want to delete this post? This action cannot be undone.');
        }

        if (!confirmed) {
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
            if (typeof window.showToast === 'function') {
              console.debug('profile.js: calling showToast for deletion');
              window.showFlash('Post deleted successfully!', 'success');
            }
            else alert('Post deleted successfully!');
          } else {
            if (typeof window.showToast === 'function') window.showToast('Failed to delete post. Please try again.', 'error');
            else alert('Failed to delete post. Please try again.');
          }
        } catch (error) {
          console.error('Error deleting post:', error);
          if (typeof window.showToast === 'function') window.showToast('Failed to delete post. Please try again.', 'error');
          else alert('Failed to delete post. Please try again.');
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
      // Expose main image viewer globally
      if (typeof this.openImageViewer === 'function') {
        window.openImageViewer = this.openImageViewer.bind(this);
      }
      
      // Handle keyboard navigation for image viewer and post detail view
      document.addEventListener('keydown', (e) => {
        if (this.postDetailView && this.postDetailView.isOpen) {
          if (e.key === 'Escape') this.closePostDetailView();
          else if (e.key === 'ArrowLeft') this.postDetailPrevImage();
          else if (e.key === 'ArrowRight') this.postDetailNextImage();
          return;
        }
        if (this.imageViewer && this.imageViewer.isOpen) {
          if (e.key === 'Escape') this.closeImageViewer();
          else if (e.key === 'ArrowLeft') this.prevImage();
          else if (e.key === 'ArrowRight') this.nextImage();
        }
      });
      if (typeof this.openPostDetailView === 'function') {
        window.openPostDetailView = this.openPostDetailView.bind(this);
      }
      window.__appCanEditPost = typeof this.canEditPost === 'function' ? this.canEditPost.bind(this) : null;
      window.__appOpenPostDetailView = typeof this.openPostDetailView === 'function' ? this.openPostDetailView.bind(this) : null;
      this.appReady = true;
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
  
  // Debug: show what likes value we're initializing the header with
  try {
    const initialLikes = (typeof window.profileData?.likes !== 'undefined')
      ? Number(window.profileData.likes)
      : ((window.profileData?.posts || []).reduce((sum, p) => sum + (Number(p.like_count) || 0), 0) || 0);
    console.debug('profile.js initialLikes:', initialLikes, 'window.profileData.likes:', window.profileData?.likes, 'posts:', window.profileData?.posts);
  } catch (e) {
    console.debug('profile.js debug error:', e);
  }

  app.mount(el);
})();
