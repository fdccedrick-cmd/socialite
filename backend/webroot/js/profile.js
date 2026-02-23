(function () {
  const el = document.getElementById('profileApp');
  if (!el) return;
  
  // CSRF token helper
  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }
  
  const app = Vue.createApp({
    data() {
      const userId = window.profileData?.currentUserId || null;
      // Check URL parameters for tab
      const urlParams = new URLSearchParams(window.location.search);
      const tabParam = urlParams.get('tab');
      const initialTab = (tabParam === 'saved' || tabParam === 'posts') ? tabParam : 'posts';
      return {
        activeTab: initialTab,
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
          newEditImagePreviews: [],
          isExpanded: false
        })),
        savedPosts: (window.profileData?.savedPosts || []).map(post => ({
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
          newEditImagePreviews: [],
          isExpanded: false
        })),
        currentUser: {
          id: window.profileData?.currentUser?.id || null,
          username: window.profileData?.currentUser?.username || 'user',
          full_name: window.profileData?.currentUser?.full_name || 'User',
          avatar: window.profileData?.currentUser?.avatar || '/img/default/default_avatar.jpg'
        },
        user: {
          full_name: window.profileData?.user?.full_name || 'User',
          username: window.profileData?.user?.username || 'user',
          profile_photo_path: window.profileData?.user?.profile_photo_path || null,
          avatar: window.profileData?.user?.avatar || '/img/default/default_avatar.jpg',
          coverPhoto: window.profileData?.user?.coverPhoto || null,
          profile_photo_privacy: window.profileData?.user?.profile_photo_privacy || 'public',
          cover_photo_privacy: window.profileData?.user?.cover_photo_privacy || 'public',
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
          imageNewComment: '',
          isExpanded: false
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
      activeTab(newTab) {
        // Update URL parameter when tab changes
        const url = new URL(window.location);
        if (newTab === 'posts') {
          url.searchParams.delete('tab'); // Default tab, no need to show in URL
        } else {
          url.searchParams.set('tab', newTab);
        }
        window.history.pushState({}, '', url);
      },
      showFriendsMenu(newVal) {
        if (newVal) {
          this.$nextTick(() => {
            if (window.lucide) lucide.createIcons();
          });
        }
      }
    },
    methods: {
      // ============ Import Shared Post Utilities ============
      ...window.SharedPostUtils,
      ...window.SharedPostEditingUtils,
      
      // ============ Profile-Specific Methods ============
        // WebSocket Methods
        initWebSocket() {
          this.wsManager = new WebSocketManager(this.currentUserId);
          
          this.wsManager.addMessageHandler((data) => {
            this.handleWebSocketMessage(data);
          });
          
          this.wsManager.connect();
        },
        handleWebSocketMessage(data) {
          switch(data.type) {
            case 'connection':
              this.wsConnected = data.status === 'connected';
              break;
              
            case 'like_added':
            case 'like_removed':
              // Handle post image likes
              if (data.target_type === 'PostImage' && data.post_image_id) {
                // Skip if current user triggered this
                if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
                  break;
                }
                
                // Refresh image like if viewing this image
                if (this.postDetailView.isOpen && this.postDetailView.currentImageId === data.post_image_id) {
                  this.loadImageLikeStatus(data.post_image_id);
                }
                break;
              }
              
              // Handle regular post likes
              // Skip if current user triggered this (already updated optimistically)
              if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
                break;
              }
              
              const post = this.posts.find(p => p.id === data.target_id);
              if (post && data.target_type === 'Post') {
                this.refreshPostLikes(post.id);
              }
              break;
              
            case 'comment_added':
              const commentPost = this.posts.find(p => p.id === data.post_id);
              if (commentPost) {
                // Handle image comments
                if (data.post_image_id && this.postDetailView.isOpen && 
                    this.postDetailView.currentImageId === data.post_image_id) {
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
          try {
            const response = await fetch(`/likes/get-post-likes/${postId}`, {
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (response.ok) {
              const data = await response.json();
              const post = this.posts.find(p => p.id === postId);
              if (post) {
                post.like_count = data.like_count;
                post.is_liked = data.is_liked;
              }
            }
          } catch (error) {
            console.error('[Profile-Refresh] Failed:', error);
          }
        },
        
        
        isUsingDefaultAvatar() {
          return this.user?.avatar === '/img/default/default_avatar.jpg';
        },
        
        handleProfilePhotoClick() {
          // If using default avatar, open edit modal to upload a profile photo (only for own profile)
          if (this.isUsingDefaultAvatar() && this.isOwnProfile) {
            this.openEditModal();
          } else if (!this.isUsingDefaultAvatar()) {
            // Otherwise, open the profile photo view
            this.openProfilePhotoView();
          }
          // For non-owners with default avatar, do nothing
        },
        
        openProfilePhotoView() {
          if (!this.user?.avatar || this.isUsingDefaultAvatar()) return;
          
          // If viewing own profile, find the actual post (most recent)
          if (this.isOwnProfile) {
            const profilePhotoPosts = this.posts.filter(post => 
              post.post_type === 'profile_photo' && 
              post.user_id === (this.user?.id || this.profileUserId)
            );
            
            // Use the most recent profile photo post
            if (profilePhotoPosts.length > 0) {
              const profilePhotoPost = profilePhotoPosts.sort((a, b) => 
                new Date(b.created) - new Date(a.created)
              )[0];
              this.openPostDetailView(profilePhotoPost, 0);
              return;
            }
          }
          
          // For non-owners or if no post found, always show current avatar as temporary post
          // This ensures they see the correct current profile photo, not an old one
          const tempPost = {
            id: `temp-profile-${Date.now()}`,
            user_id: this.user?.id || this.profileUserId,
            content_text: `${this.user?.full_name || 'User'}'s profile picture`,
            created: new Date().toISOString(),
            user: {
              id: this.user?.id || this.profileUserId,
              full_name: this.user?.full_name || 'User',
              username: this.user?.username || 'user',
              avatar: this.user?.avatar || '/img/default/default_avatar.jpg'
            },
            post_images: [{
              id: `temp-img-${Date.now()}`,
              image_path: this.user.avatar,
              created: new Date().toISOString()
            }],
            like_count: 0,
            is_liked: false,
            comment_count: 0,
            comments: [],
            showComments: false,
            is_profile_photo: true,
            is_cover_photo: false,
            is_temporary: true,
            post_type: 'profile_photo',
            privacy: this.user?.profile_photo_privacy || 'public' // Use actual privacy from backend
          };
          
          console.log('Opening profile photo - tempPost:', tempPost);
          console.log('Privacy:', tempPost.privacy);
          console.log('Is profile photo:', tempPost.is_profile_photo);
          
          this.openPostDetailView(tempPost, 0);
        },
        
        openCoverPhotoView() {
          if (!this.user?.coverPhoto) return;
          
          // If viewing own profile, find the actual post (most recent)
          if (this.isOwnProfile) {
            const coverPhotoPosts = this.posts.filter(post => 
              post.post_type === 'cover_photo' && 
              post.user_id === (this.user?.id || this.profileUserId)
            );
            
            // Use the most recent cover photo post
            if (coverPhotoPosts.length > 0) {
              const coverPhotoPost = coverPhotoPosts.sort((a, b) => 
                new Date(b.created) - new Date(a.created)
              )[0];
              this.openPostDetailView(coverPhotoPost, 0);
              return;
            }
          }
          
          // For non-owners or if no post found, always show current cover as temporary post
          // This ensures they see the correct current cover photo, not an old one
          const tempPost = {
            id: `temp-cover-${Date.now()}`,
            user_id: this.user?.id || this.profileUserId,
            content_text: `${this.user?.full_name || 'User'}'s cover photo`,
            created: new Date().toISOString(),
            user: {
              id: this.user?.id || this.profileUserId,
              full_name: this.user?.full_name || 'User',
              username: this.user?.username || 'user',
              avatar: this.user?.avatar || '/img/default/default_avatar.jpg'
            },
            post_images: [{
              id: `temp-img-${Date.now()}`,
              image_path: this.user.coverPhoto,
              created: new Date().toISOString()
            }],
            like_count: 0,
            is_liked: false,
            comment_count: 0,
            comments: [],
            showComments: false,
            is_profile_photo: false,
            is_cover_photo: true,
            is_temporary: true,
            post_type: 'cover_photo',
            privacy: this.user?.cover_photo_privacy || 'public' // Use actual privacy from backend
          };
          
          console.log('Opening cover photo - tempPost:', tempPost);
          console.log('Privacy:', tempPost.privacy);
          console.log('Is cover photo:', tempPost.is_cover_photo);
          
          this.openPostDetailView(tempPost, 0);
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
      
      openEditModal() {
        // Only allow profile owners to open edit modal
        if (!this.isOwnProfile) {
          console.warn('Cannot edit profile: not the profile owner');
          return;
        }
        
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
        
        this.croppedFile = null;
        this.croppedDataURL = null;
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
        // Only allow profile owners to upload cover photo
        if (!this.isOwnProfile) {
          console.warn('Cannot upload cover photo: not the profile owner');
          return;
        }
        
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
          const response = await fetch('/friendships/accept/' + this.friendshipId, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
          });
          
          const data = await response.json();
          
          if (data.success) {
            this.friendshipStatus = 'accepted';
            this.isSender = false;
            this.user.stats.friends += 1;
            this.$forceUpdate();
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
            // Update friendship status to show Add Friend button
            this.friendshipStatus = null;
            this.friendshipId = null;
            this.isSender = false;
            this.user.stats.friends = Math.max(0, this.user.stats.friends - 1);
            
            // Force re-render
            this.$forceUpdate();
            
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
        const isCurrentUserInvolved = Number(data.user_id) === Number(this.currentUserId) || Number(data.friend_id) === Number(this.currentUserId);
        
        if (!isCurrentUserInvolved) {
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
              }
            }
            break;

          case 'accepted':
            // Friend request was accepted
            this.friendshipStatus = 'accepted';
            this.isSender = false;
            this.user.stats.friends = (this.user.stats.friends || 0) + 1;
            break;

          case 'cancelled':
            // Friend request was cancelled
            if (Number(data.user_id) === Number(this.profileUserId)) {
              // The person whose profile we're viewing cancelled their request
              this.friendshipStatus = null;
              this.isSender = false;
              this.friendshipId = null;
              this.showRequestSent = false;
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
            }
            break;

          case 'removed':
            // Friendship was removed (unfriended)
            this.friendshipStatus = null;
            this.isSender = false;
            this.friendshipId = null;
            this.user.stats.friends = Math.max(0, (this.user.stats.friends || 0) - 1);
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
        try {
          const canvas = this.cropperInstance.getCroppedCanvas({ width: 400, height: 400, imageSmoothingQuality: 'high' });
          canvas.toBlob((blob) => {
            if (!blob) {
              this.uploadError = 'Failed to crop image';
              return;
            }
            const file = new File([blob], 'profile_' + Date.now() + '.png', { type: 'image/png' });
            
            // Store file in both locations for redundancy
            this.croppedFile = file;
            this.editForm.profile_picture_file = file;
            
            // Store dataURL as backup
            const dataURL = canvas.toDataURL('image/png');
            this.croppedDataURL = dataURL;
            this.editForm.avatar = dataURL;
            
            this.showCropper = false;
            try { this.cropperInstance.destroy(); } catch(e){}
            this.cropperInstance = null;
            this.cropperImageSrc = null;
            this.cropPreviewSrc = null;
            // clear file input value to keep behavior consistent
            const input = document.getElementById('profilePictureInput');
            if (input) input.value = '';
          }, 'image/png');
        } catch (e) {
          console.error('[TRACK] cropAndUse error', e);
        }
      },

      cancelCrop() {
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
        
        // Try multiple sources for the file, with priority order
        let fileToUpload = this.croppedFile || this.editForm.profile_picture_file;
        
        if (fileToUpload) {
          formData.append('profile_picture', fileToUpload);
        } else if (this.croppedDataURL || (this.editForm.avatar && typeof this.editForm.avatar === 'string' && this.editForm.avatar.indexOf('data:') === 0)) {
          // Fallback: if cropped image is stored as data URL but File not present, convert to blob
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
        }
        
        

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

          let data = {};
          try {
            data = await response.json();
          } catch (e) {
            const text = await response.text().catch(() => '');
            console.error('[TRACK] Profile update: failed to parse JSON response', e, text);
          }
          
          if (data.success) {
            // Update user data with response
            this.user.full_name = data.user.full_name;
            this.user.username = data.user.username;
            this.user.bio = data.user.bio;
            this.user.address = data.user.address;
            this.user.relationship_status = data.user.relationship_status;
            this.user.contact_links = data.user.contact_links;
            
            // Only update cover photo if it was actually changed
            if (data.user.cover_photo_path !== undefined && data.user.cover_photo_path !== null) {
              this.user.coverPhoto = data.user.cover_photo_path;
            }
            
            // Only update profile photo if it was actually changed
            if (data.user.profile_photo_path !== undefined && data.user.profile_photo_path !== null) {
              this.user.avatar = data.user.profile_photo_path;
              this.user.profile_photo_path = data.user.profile_photo_path;
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
          console.error('[TRACK] Error updating profile:', error);
          // Redirect with error flag
          window.location.href = window.location.pathname + '?error=network';
        } finally {
          this.isSubmitting = false;
        }
      },
      // Safe delegator used by templates to avoid "not a function" errors
        handleOpenComment(postId) {
          if (typeof this.openCommentInput === 'function') return this.openCommentInput(postId);
          if (typeof window.openCommentInput === 'function') return window.openCommentInput(postId);
          console.warn('openCommentInput not available on profile instance');
        },
      
      // Image Comment Edit/Delete Methods
      canEditPost(post) {
        return this.currentUserId && post && post.user_id === this.currentUserId;
      },
      safeCanEditPost(post) {
        return typeof this.canEditPost === 'function' && this.canEditPost(post);
      },
      safeOpenPostDetailView(post, index) {
        if (typeof this.openPostDetailView === 'function') this.openPostDetailView(post, index);
      },
    },
    mounted() {
      // Detect profile/cover photos in posts
      if (this.posts && this.posts.length > 0) {
        this.posts.forEach(post => {
          this.detectProfileCoverPhoto(post);
        });
      }
      
      // Detect profile/cover photos in saved posts
      if (this.savedPosts && this.savedPosts.length > 0) {
        this.savedPosts.forEach(post => {
          this.detectProfileCoverPhoto(post);
        });
      }
      
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
      window.toggleSavePost = this.toggleSavePost.bind(this);
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
        // Create wrapper for opening post detail with specific image
        window.openPostDetailWithImage = (post, imageIndex = 0) => {
          console.log('openPostDetailWithImage called:', { post, imageIndex });
          this.openPostDetailView(post, imageIndex);
        };
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
