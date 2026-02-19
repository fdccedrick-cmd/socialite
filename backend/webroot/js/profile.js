(function () {
  console.log('=== profile.js v2.1 LOADED (with image comment debugging) ===');
  try { console.log('profile.js loaded - window.profileData:', window.profileData); } catch(e){}
  const el = document.getElementById('profileApp');
  if (!el) return;
  
  const app = Vue.createApp({
    data() {
      const userId = window.profileData?.currentUserId || null;
      return {
        activeTab: 'posts',
        currentUserId: userId ? parseInt(userId, 10) : null,
        profileUserId: window.profileData?.profileUserId || null,
        isOwnProfile: window.profileData?.isOwnProfile ?? true,
        friendshipStatus: window.profileData?.friendshipStatus || null,
        friendshipId: window.profileData?.friendshipId || null,
        isSender: window.profileData?.isSender || false,
        mutualFriendsCount: window.profileData?.mutualFriendsCount || 0,
        friendsCount: window.profileData?.friendsCount || 0,
        showFriendsMenu: false,
        showRequestSent: false,
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
          editPrivacy: 'public',
          editImages: [],
          removedImageIds: [],
          newEditImages: [],
          newEditImagePreviews: []
        })),
        user: {
          full_name: window.profileData?.user?.full_name || 'User',
          username: window.profileData?.user?.username || 'user',
          avatar: window.profileData?.user?.avatar || 'https://i.pravatar.cc/150?img=1',
          coverPhoto: window.profileData?.user?.coverPhoto || null,
          joinedDate: window.profileData?.user?.joinedDate || 'Joined recently',
          bio: window.profileData?.user?.bio || 'No bio yet',
          address: window.profileData?.user?.address || null,
          relationship_status: window.profileData?.user?.relationship_status || null,
          contact_links: window.profileData?.user?.contact_links || null,
          contactLinksArray: [],
          stats: {
            posts: window.profileData?.postCount || 0,
            friends: window.profileData?.friendsCount || 0,
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
          bio: '',
          address: '',
          relationship_status: '',
          contactLinksArray: []
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
        appReady: false,
        wsManager: null,
        wsConnected: false
      };
    },
    computed: {
      window() {
        return window;
      }
    },
    watch: {
      showFriendsMenu(newVal) {
        if (newVal) {
          this.$nextTick(() => {
            if (window.lucide) lucide.createIcons();
          });
        }
      }
    },
    methods: {
        // WebSocket Methods
        initWebSocket() {
          console.log('[Profile] Initializing WebSocket...');
          this.wsManager = new WebSocketManager(this.currentUserId);
          
          this.wsManager.addMessageHandler((data) => {
            this.handleWebSocketMessage(data);
          });
          
          this.wsManager.connect();
        },
        handleWebSocketMessage(data) {
          console.log('[Profile] WebSocket message:', data);
          
          switch(data.type) {
            case 'connection':
              this.wsConnected = data.status === 'connected';
              break;
              
            case 'like_added':
            case 'like_removed':
              console.log('[WS-Profile]', data.type, 'FIRED');
              console.log('[WS-Profile]   data.user_id:', data.user_id, 'type:', typeof data.user_id);
              console.log('[WS-Profile]   this.currentUserId:', this.currentUserId, 'type:', typeof this.currentUserId);
              
              // Handle post image likes
              if (data.target_type === 'PostImage' && data.post_image_id) {
                // Skip if current user triggered this
                if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
                  console.log('[WS-Profile] ✓ Skipping image like refresh - current user action');
                  break;
                }
                
                // Refresh image like if viewing this image
                if (this.postDetailView.isOpen && this.postDetailView.currentImageId === data.post_image_id) {
                  console.log('[WS-Profile] Refreshing image like for image:', data.post_image_id);
                  this.loadImageLikeStatus(data.post_image_id);
                }
                break;
              }
              
              // Handle regular post likes
              // Skip if current user triggered this (already updated optimistically)
              if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
                console.log('[WS-Profile] ✓ Skipping refresh - current user action');
                break;
              }
              
              console.log('[WS-Profile] × Will refresh - different user');
              const post = this.posts.find(p => p.id === data.target_id);
              if (post && data.target_type === 'Post') {
                console.log('[WS-Profile] Refreshing post likes for post:', data.target_id);
                this.refreshPostLikes(post.id);
              }
              break;
              
            case 'comment_added':
              const commentPost = this.posts.find(p => p.id === data.post_id);
              if (commentPost) {
                // Handle image comments
                if (data.post_image_id && this.postDetailView.isOpen && 
                    this.postDetailView.currentImageId === data.post_image_id) {
                  console.log('[WS-Profile] Refreshing image comments for image:', data.post_image_id);
                  this.loadImageComments(data.post_image_id);
                } else if (!data.post_image_id) {
                  // Handle regular post comments
                  if (commentPost.showComments) {
                    this.loadComments(commentPost.id);
                  } else {
                    commentPost.comment_count = (commentPost.comment_count || 0) + 1;
                  }
                }
              }
              break;
              
            case 'friendship_change':
              console.log('[WS-Profile] Friendship change:', data.action, 'userId:', data.user_id, 'friendId:', data.friend_id);
              
              // Only update if one of the involved users is the profile being viewed
              if (Number(data.user_id) === Number(this.profileUserId) || Number(data.friend_id) === Number(this.profileUserId)) {
                this.handleFriendshipUpdate(data);
              }
              break;
              
            case 'notification':
              if (typeof window.showFlash === 'function') {
                window.showFlash(data.message, 'info');
              }
              break;
          }
        },
        async refreshPostLikes(postId) {
          console.log('[Profile-Refresh] Fetching likes for post:', postId);
          try {
            const response = await fetch(`/likes/get-post-likes/${postId}`, {
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (response.ok) {
              const data = await response.json();
              console.log('[Profile-Refresh] Got data:', data);
              const post = this.posts.find(p => p.id === postId);
              if (post) {
                console.log('[Profile-Refresh] Updating - old:', post.like_count, '/', post.is_liked, 'new:', data.like_count, '/', data.is_liked);
                post.like_count = data.like_count;
                post.is_liked = data.is_liked;
              }
            }
          } catch (error) {
            console.error('[Profile-Refresh] Failed:', error);
          }
        },
        
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
            this.postDetailView.currentImageId = parseInt(img.id, 10);
            this.loadImageComments(this.postDetailView.currentImageId);
            this.loadImageLikeStatus(this.postDetailView.currentImageId);
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
          const imageId = parseInt(postImageId, 10);
          try {
            const response = await fetch(`/comments/get-by-post-image/${imageId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
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
          const imageId = parseInt(postImageId, 10);
          try {
            const response = await fetch(`/likes/post-image/${imageId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) return;
            const data = await response.json();
            this.postDetailView.imageLikeCount = data.count ?? 0;
            this.postDetailView.imageIsLiked = data.is_liked ?? false;
          } catch (e) { console.error('Error loading image like status:', e); }
        },
        async toggleImageLike() {
          if (!this.postDetailView.currentImageId) return;
          
          // Ensure ID is a plain number (not a reactive proxy)
          const imageId = parseInt(this.postDetailView.currentImageId, 10);
          if (isNaN(imageId)) {
            console.error('[Profile-Image-Like] Invalid image ID:', this.postDetailView.currentImageId);
            return;
          }
          
          // Optimistic update - update UI immediately
          const wasLiked = this.postDetailView.imageIsLiked;
          const oldLikeCount = this.postDetailView.imageLikeCount || 0;
          
          this.postDetailView.imageIsLiked = !wasLiked;
          this.postDetailView.imageLikeCount = wasLiked ? oldLikeCount - 1 : oldLikeCount + 1;
          
          console.log('[Profile-Image-Like] Optimistic update - imageId:', imageId, 'liked:', this.postDetailView.imageIsLiked, 'count:', this.postDetailView.imageLikeCount);
          
          try {
            const response = await fetch(`/likes/toggle-post-image/${imageId}`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
              credentials: 'same-origin'
            });
            
            if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('[Profile-Image-Like] Server response:', data);
            
            if (data.success) {
              this.postDetailView.imageLikeCount = data.likeCount;
              this.postDetailView.imageIsLiked = data.liked;
              console.log('[Profile-Image-Like] Updated - liked:', this.postDetailView.imageIsLiked, 'count:', this.postDetailView.imageLikeCount);
            } else {
              // Revert on failure
              this.postDetailView.imageIsLiked = wasLiked;
              this.postDetailView.imageLikeCount = oldLikeCount;
            }
          } catch (e) {
            console.error('Error toggling image like:', e);
            // Revert optimistic update
            this.postDetailView.imageIsLiked = wasLiked;
            this.postDetailView.imageLikeCount = oldLikeCount;
          }
        },
        async submitImageComment() {
          const v = this.postDetailView;
          if (!v.post || !v.currentImageId) return;
          const text = (v.imageNewComment || '').trim();
          if (!text) return;
          
          // Ensure IDs are plain integers
          const postId = parseInt(v.post.id, 10);
          const imageId = parseInt(v.currentImageId, 10);
          
          const formData = new FormData();
          formData.append('post_id', postId);
          formData.append('post_image_id', imageId);
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
          const imgs = this.postDetailView.post.post_images;
          const totalImages = imgs.length;
          console.log('postDetailPrevImage - BEFORE: imageIndex=', this.postDetailView.imageIndex, 'totalImages=', totalImages);
          
          // Loop navigation: go to last image if at first, otherwise go to previous
          if (this.postDetailView.imageIndex === 0) {
            this.postDetailView.imageIndex = totalImages - 1;
          } else {
            this.postDetailView.imageIndex--;
          }
          
          console.log('postDetailPrevImage - AFTER: imageIndex=', this.postDetailView.imageIndex);
          const img = imgs[this.postDetailView.imageIndex];
          
          if (totalImages >= 2 && img && img.id) {
            this.postDetailView.currentImageId = parseInt(img.id, 10);
            this.loadImageComments(this.postDetailView.currentImageId);
            this.loadImageLikeStatus(this.postDetailView.currentImageId);
          } else {
            this.postDetailView.currentImageId = null;
          }
          
          this.$forceUpdate();
        },
        postDetailNextImage() {
          if (!this.postDetailView.post || !this.postDetailView.post.post_images?.length) return;
          const imgs = this.postDetailView.post.post_images;
          const totalImages = imgs.length;
          console.log('postDetailNextImage - BEFORE: imageIndex=', this.postDetailView.imageIndex, 'totalImages=', totalImages);
          
          // Loop navigation: go to first image if at last, otherwise go to next
          if (this.postDetailView.imageIndex === totalImages - 1) {
            this.postDetailView.imageIndex = 0;
          } else {
            this.postDetailView.imageIndex++;
          }
          
          console.log('postDetailNextImage - AFTER: imageIndex=', this.postDetailView.imageIndex);
          const img = imgs[this.postDetailView.imageIndex];
          
          if (totalImages >= 2 && img && img.id) {
            this.postDetailView.currentImageId = parseInt(img.id, 10);
            this.loadImageComments(this.postDetailView.currentImageId);
            this.loadImageLikeStatus(this.postDetailView.currentImageId);
          } else {
            this.postDetailView.currentImageId = null;
          }
          
          this.$forceUpdate();
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
        
        // Find the post
        const postIndex = this.posts.findIndex(p => p.id === postId);
        if (postIndex === -1) return;
        
        const post = this.posts[postIndex];
        
        // Optimistic update - update UI immediately
        const wasLiked = post.is_liked;
        const oldLikeCount = post.like_count || 0;
        
        post.is_liked = !wasLiked;
        post.like_count = wasLiked ? oldLikeCount - 1 : oldLikeCount + 1;
        
        // Update stats optimistically
        const likeDelta = wasLiked ? -1 : 1;
        this.user.stats.likes = Math.max(0, this.user.stats.likes + likeDelta);
        
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
          console.log('[Profile-Like] Server response:', data);

          if (data.success) {
            // Update with server response to ensure accuracy
            post.is_liked = data.liked;
            post.like_count = data.likeCount;
            console.log('[Profile-Like] Updated - liked:', post.is_liked, 'count:', post.like_count);
          } else {
            // Revert on failure
            post.is_liked = wasLiked;
            post.like_count = oldLikeCount;
            this.user.stats.likes = Math.max(0, this.user.stats.likes - likeDelta);
          }
        } catch (error) {
          console.error('Error toggling like:', error);
          // Revert optimistic update
          post.is_liked = wasLiked;
          post.like_count = oldLikeCount;
          this.user.stats.likes = Math.max(0, this.user.stats.likes - likeDelta);
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
        // Parse contact links if available
        let contactLinksArray = [];
        if (this.user.contact_links) {
          try {
            const parsed = JSON.parse(this.user.contact_links);
            if (Array.isArray(parsed)) {
              contactLinksArray = parsed;
            }
          } catch (e) {
            console.error('Failed to parse contact_links:', e);
          }
        }
        
        console.log('[TRACK] openEditModal - user data:', {
          address: this.user.address,
          relationship_status: this.user.relationship_status,
          contact_links: this.user.contact_links,
          parsed_contact_links: contactLinksArray
        });
        
        this.editForm = {
          full_name: this.user.full_name,
          username: this.user.username,
          avatar: this.user.avatar,
          profile_picture_file: null,
          bio: this.user.bio,
          address: this.user.address || '',
          relationship_status: this.user.relationship_status || '',
          contactLinksArray: contactLinksArray.length > 0 ? contactLinksArray : [],
          current_password: '',
          new_password: '',
          confirm_password: ''
        };
        
        console.log('[TRACK] openEditModal - editForm initialized:', {
          address: this.editForm.address,
          relationship_status: this.editForm.relationship_status,
          contactLinksArray: this.editForm.contactLinksArray
        });
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
          address: '',
          relationship_status: '',
          contactLinksArray: [],
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
      
      // Friendship methods
      closeFriendsMenu() {
        this.showFriendsMenu = false;
      },
      
      // Contact Links methods
      addContactLink() {
        this.editForm.contactLinksArray.push({ label: '', url: '' });
        this.$nextTick(() => {
          if (window.lucide) lucide.createIcons();
        });
      },
      removeContactLink(index) {
        this.editForm.contactLinksArray.splice(index, 1);
        this.$nextTick(() => {
          if (window.lucide) lucide.createIcons();
        });
      },
      
      // Cover Photo methods  
      openCoverPhotoUpload() {
        // Create a hidden file input
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/jpeg,image/png,image/jpg,image/gif';
        input.onchange = async (e) => {
          const file = e.target.files[0];
          if (file) {
            await this.handleCoverPhotoUpload(file);
          }
        };
        input.click();
      },
      
      async handleCoverPhotoUpload(file) {
        // Validate file
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
          window.showToast('Invalid file type. Only JPG, PNG, and GIF are allowed.', 'error');
          return;
        }
        
        const maxSize = 10 * 1024 * 1024; // 10MB for cover photos
        if (file.size > maxSize) {
          window.showToast('File size must be less than 10MB.', 'error');
          return;
        }
        
        // Show loading toast
        window.showToast('Uploading cover photo...', 'info');
        
        // Upload the cover photo
        const formData = new FormData();
        formData.append('cover_photo', file);
        formData.append('full_name', this.user.full_name);
        formData.append('bio', this.user.bio || '');
        
        try {
          const headers = { 'X-Requested-With': 'XMLHttpRequest' };
          const meta = document.querySelector('meta[name="csrf-token"]');
          if (meta && meta.getAttribute('content')) headers['X-CSRF-Token'] = meta.getAttribute('content');
          
          const response = await fetch('/profile/update', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers
          });
          
          const data = await response.json();
          console.log('[Cover Photo] Upload response:', data);
          
          if (data.success) {
            console.log('[Cover Photo] Upload successful');
            if (data.user.cover_photo_path) {
              this.user.coverPhoto = data.user.cover_photo_path;
            }
            window.showToast('Cover photo updated successfully!', 'success');
            // Reload to show the new cover photo post
            setTimeout(() => window.location.reload(), 1500);
          } else {
            window.showToast(data.message || 'Failed to update cover photo', 'error');
          }
        } catch (error) {
          console.error('[Cover Photo] Upload error:', error);
          window.showToast('An error occurred while uploading', 'error');
        }
      },
      
      async sendFriendRequest() {
        try {
          const response = await fetch('/friendships/add', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ friend_id: this.profileUserId })
          });
          
          const data = await response.json();
          
          if (data.success) {
            this.friendshipStatus = 'pending';
            this.friendshipId = data.friendship_id || null;
            this.isSender = true;
            this.showRequestSent = true;
            
            // Show "Request Sent" for 2 seconds, then show "Cancel Request"
            setTimeout(() => {
              this.showRequestSent = false;
            }, 2000);
            
            window.showToast('Friend request sent', 'success');
          } else {
            window.showToast(data.message || 'Failed to send friend request', 'error');
          }
        } catch (error) {
          console.error('Error sending friend request:', error);
          window.showToast('An error occurred', 'error');
        }
      },
      async cancelFriendRequest() {
        try {
          const response = await fetch('/friendships/remove', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ friend_id: this.profileUserId })
          });
          
          const data = await response.json();
          
          if (data.success) {
            this.friendshipStatus = null;
            this.isSender = false;
            this.friendshipId = null;
            this.showRequestSent = false;
            window.showToast('Friend request cancelled', 'success');
          } else {
            window.showToast(data.message || 'Failed to cancel request', 'error');
          }
        } catch (error) {
          console.error('Error cancelling request:', error);
          window.showToast('An error occurred', 'error');
        }
      },
      async acceptFriendRequest() {
        try {
          console.log('Accepting friend request, friendshipId:', this.friendshipId);
          const response = await fetch('/friendships/accept/' + this.friendshipId, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
          });
          
          const data = await response.json();
          console.log('Accept response:', data);
          
          if (data.success) {
            this.friendshipStatus = 'accepted';
            this.isSender = false;
            this.user.stats.friends += 1;
            this.$forceUpdate();
            console.log('Friendship status updated to:', this.friendshipStatus);
            window.showToast('Friend request accepted', 'success');
          } else {
            window.showToast(data.message || 'Failed to accept request', 'error');
          }
        } catch (error) {
          console.error('Error accepting request:', error);
          window.showToast('An error occurred', 'error');
        }
      },
      async rejectFriendRequest() {
        const confirmed = await window.confirmModal.show({
          title: 'Reject Friend Request',
          message: 'Are you sure you want to reject this friend request?',
          confirmText: 'Reject',
          confirmClass: 'bg-red-600 hover:bg-red-700'
        });
        
        if (!confirmed) return;
        
        try {
          const response = await fetch('/friendships/reject/' + this.friendshipId, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
          });
          
          const data = await response.json();
          
          if (data.success) {
            this.friendshipStatus = null;
            this.friendshipId = null;
            window.showToast('Friend request rejected', 'success');
          } else {
            window.showToast(data.message || 'Failed to reject request', 'error');
          }
        } catch (error) {
          console.error('Error rejecting request:', error);
          window.showToast('An error occurred', 'error');
        }
      },
      async unfriend() {
        this.showFriendsMenu = false;
        
        const confirmed = await window.confirmModal.show({
          title: 'Unfriend',
          message: 'Are you sure you want to remove this friend?',
          confirmText: 'Unfriend',
          confirmClass: 'bg-red-600 hover:bg-red-700'
        });
        
        if (!confirmed) return;
        
        try {
          console.log('Unfriending user:', this.profileUserId);
          const response = await fetch('/friendships/remove', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ friend_id: this.profileUserId })
          });
          
          console.log('Unfriend response status:', response.status);
          const data = await response.json();
          console.log('Unfriend response data:', data);
          
          if (data.success) {
            // Update friendship status to show Add Friend button
            this.friendshipStatus = null;
            this.friendshipId = null;
            this.isSender = false;
            this.user.stats.friends = Math.max(0, this.user.stats.friends - 1);
            
            // Force re-render
            this.$forceUpdate();
            
            console.log('Friendship status updated to:', this.friendshipStatus);
            window.showToast('Friend removed', 'success');
          } else {
            window.showToast(data.message || 'Failed to remove friend', 'error');
          }
        } catch (error) {
          console.error('Error removing friend:', error);
          window.showToast('An error occurred', 'error');
        }
      },
      
      handleFriendshipUpdate(data) {
        console.log('[Profile] Handling friendship update:', data);
        
        const isCurrentUserInvolved = Number(data.user_id) === Number(this.currentUserId) || Number(data.friend_id) === Number(this.currentUserId);
        
        if (!isCurrentUserInvolved) {
          console.log('[Profile] Skipping - current user not involved');
          return;
        }

        switch(data.action) {
          case 'added':
            // Someone sent a friend request
            if (Number(data.friend_id) === Number(this.currentUserId)) {
              // Current user received a friend request
              if (Number(data.user_id) === Number(this.profileUserId)) {
                // Viewing the sender's profile
                this.friendshipStatus = 'pending';
                this.isSender = false;
                this.friendshipId = data.friendship_id;
                console.log('[Profile] Friend request received from this profile');
              }
            } else if (Number(data.user_id) === Number(this.currentUserId)) {
              // Current user sent a friend request (shouldn't need update as it's optimistic)
              console.log('[Profile] Friend request sent confirmed');
            }
            break;

          case 'accepted':
            // Friend request was accepted
            this.friendshipStatus = 'accepted';
            this.isSender = false;
            this.user.stats.friends = (this.user.stats.friends || 0) + 1;
            console.log('[Profile] Friend request accepted');
            break;

          case 'cancelled':
            // Friend request was cancelled
            if (Number(data.user_id) === Number(this.profileUserId)) {
              // The person whose profile we're viewing cancelled their request
              this.friendshipStatus = null;
              this.isSender = false;
              this.friendshipId = null;
              this.showRequestSent = false;
              console.log('[Profile] Friend request cancelled by profile user');
            }
            break;

          case 'rejected':
            // Friend request was rejected
            if (Number(data.user_id) === Number(this.profileUserId)) {
              // The person whose profile we're viewing rejected the request
              this.friendshipStatus = null;
              this.isSender = false;
              this.friendshipId = null;
              this.showRequestSent = false;
              console.log('[Profile] Friend request rejected by profile user');
            }
            break;

          case 'removed':
            // Friendship was removed (unfriended)
            this.friendshipStatus = null;
            this.isSender = false;
            this.friendshipId = null;
            this.user.stats.friends = Math.max(0, (this.user.stats.friends || 0) - 1);
            console.log('[Profile] Friendship removed');
            break;
        }

        // Force Vue to update the UI
        this.$forceUpdate();
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
          this.errors.full_name = 'Full name is required and cannot be empty';
          this.isSubmitting = false;
          // Re-render icons after error shows
          this.$nextTick(() => {
            if (window.lucide) lucide.createIcons();
          });
          return;
        }

        // Clear any previous errors
        this.errors = {};

        // Create FormData for file upload
        const formData = new FormData();
        formData.append('full_name', this.editForm.full_name.trim());
        // Send bio even if empty (backend will handle null)
        formData.append('bio', this.editForm.bio ? this.editForm.bio.trim() : '');
        // Add new personal details fields
        formData.append('address', this.editForm.address ? this.editForm.address.trim() : '');
        formData.append('relationship_status', this.editForm.relationship_status || '');
        // Filter out empty contact links and send as JSON
        const validContactLinks = this.editForm.contactLinksArray.filter(link => link.label && link.url);
        if (validContactLinks.length > 0) {
          formData.append('contact_links', JSON.stringify(validContactLinks));
        } else {
          formData.append('contact_links', '');
        }
        
        // Debug log the new fields
        console.log('[TRACK] Personal details to submit:', {
          address: this.editForm.address,
          relationship_status: this.editForm.relationship_status,
          contactLinksArray: this.editForm.contactLinksArray,
          validContactLinks: validContactLinks
        });
        
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
            console.log('[TRACK] Received user data:', data.user);
            // Update user data with response
            this.user.full_name = data.user.full_name;
            this.user.username = data.user.username;
            this.user.bio = data.user.bio;
            this.user.address = data.user.address;
            this.user.relationship_status = data.user.relationship_status;
            this.user.contact_links = data.user.contact_links;
            if (data.user.cover_photo_path) {
              this.user.coverPhoto = data.user.cover_photo_path;
            }
            console.log('[TRACK] Updated local user object:', {
              address: this.user.address,
              relationship_status: this.user.relationship_status,
              contact_links: this.user.contact_links,
              coverPhoto: this.user.coverPhoto
            });
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
        post.editPrivacy = post.privacy || 'public';
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
        formData.append('privacy', post.editPrivacy || 'public');
        
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
            post.privacy = data.post.privacy || 'public';
            post.post_images = data.post.post_images || [];
            post.modified = data.post.modified;
            
            // Exit edit mode
            post.isEditing = false;
            post.editContent = '';
            post.editPrivacy = 'public';
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
      // Parse contact links if available
      if (this.user.contact_links) {
        try {
          const parsed = JSON.parse(this.user.contact_links);
          if (Array.isArray(parsed)) {
            this.user.contactLinksArray = parsed;
          }
        } catch (e) {
          console.error('Failed to parse contact_links:', e);
          this.user.contactLinksArray = [];
        }
      }
      
      // Initialize WebSocket for real-time updates
      if (typeof WebSocketManager !== 'undefined' && this.currentUserId) {
        this.initWebSocket();
      }
      
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
      // Cleanup WebSocket
      if (this.wsManager) {
        this.wsManager.disconnect();
      }
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
  
  // Add click-outside directive for dropdown menus
  app.directive('click-outside', {
    mounted(el, binding) {
      el._clickOutsideHandler = (event) => {
        if (!(el === event.target || el.contains(event.target))) {
          binding.value(event);
        }
      };
      document.addEventListener('click', el._clickOutsideHandler);
    },
    unmounted(el) {
      document.removeEventListener('click', el._clickOutsideHandler);
      delete el._clickOutsideHandler;
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
