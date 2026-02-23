console.log('=== dashboard.js LOADED with image comment debugging ===');
const { createApp } = Vue;

if (typeof window.dashboardData === 'undefined') {
    window.dashboardData = { user: null, posts: [] };
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

const app = createApp({
    data() {
        const userId = window.dashboardData?.user?.id ?? null;
        return {
            currentUserId: userId ? parseInt(userId, 10) : null,
            user: {
                id: userId ? parseInt(userId, 10) : null,
                username: window.dashboardData?.user?.username || 'user',
                avatar: window.dashboardData?.user?.avatar || 'https://i.pravatar.cc/150?img=1'
            },
            
            posts: (window.dashboardData?.posts || []).map(post => ({
                ...post,
                showComments: false,
                newComment: '',
                commentImage: null,
                commentImagePreview: null,
                comments: post.comments || [],
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
            newPost: {
                content: '',
                images: [],
                imagePreview: [],
                privacy: 'public',
                isSubmitting: false,
                error: '',
                isDragging: false
            },
           
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
                imageCommentImage: null,
                imageCommentImagePreview: null,
                isExpanded: false
            },
            wsManager: null,
            wsConnected: false,
            appReady: false
        }
    },
    computed: {
        window() {
            return window;
        },
        currentUser() {
            return this.user;
        }
    },
    methods: {
        // ============ Import Shared Post Utilities ============
        ...window.SharedPostUtils,
        ...window.SharedPostEditingUtils,
        
        // ============ Dashboard-Specific Methods ============
        // ============ Import Shared Post Utilities ============
        ...window.SharedPostUtils,
        
        // ============ Dashboard-Specific Methods ============
        canEditPost(post) {
            return this.currentUserId && post && post.user_id === this.currentUserId;
        },
        safeCanEditPost(post) {
            return typeof this.canEditPost === 'function' && this.canEditPost(post);
        },
        safeOpenPostDetailView(post, index) {
            if (typeof this.openPostDetailView === 'function') this.openPostDetailView(post, index);
        },
        initWebSocket() {
            console.log('[Dashboard] Initializing WebSocket...');
            this.wsManager = new WebSocketManager(this.currentUserId);
            
            // Add message handler
            this.wsManager.addMessageHandler((data) => {
                this.handleWebSocketMessage(data);
            });
            
            // Connect
            this.wsManager.connect();
        },
        handleWebSocketMessage(data) {
            console.log('[Dashboard] WebSocket message:', data);
            
            switch(data.type) {
                case 'connection':
                    this.wsConnected = data.status === 'connected';
                    if (this.wsConnected) {
                        this.showToast('Real-time updates enabled', 'success');
                    }
                    break;
                    
                case 'like_added':
                    this.handleLikeAdded(data);
                    break;
                    
                case 'like_removed':
                    this.handleLikeRemoved(data);
                    break;
                    
                case 'comment_added':
                    this.handleCommentAdded(data);
                    break;
                    
                case 'new_post':
                    this.handleNewPost(data);
                    break;
                    
                case 'notification':
                    this.handleNotification(data);
                    break;
            }
        },
        handleLikeAdded(data) {
            console.log('[WS] handleLikeAdded FIRED');
            console.log('[WS]   data.user_id:', data.user_id, 'type:', typeof data.user_id);
            console.log('[WS]   this.currentUserId:', this.currentUserId, 'type:', typeof this.currentUserId);
            console.log('[WS]   Loose comparison (==):', data.user_id == this.currentUserId);
            console.log('[WS]   Strict comparison (===):', data.user_id === this.currentUserId);
            
            // Handle post image likes
            if (data.target_type === 'PostImage' && data.post_image_id) {
                // Skip if current user triggered this
                if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
                    console.log('[WS] ✓ Skipping image like refresh - current user action');
                    return;
                }
                
                // Refresh image like if viewing this image
                if (this.postDetailView.isOpen && this.postDetailView.currentImageId === data.post_image_id) {
                    console.log('[WS] Refreshing image like for image:', data.post_image_id);
                    this.loadImageLikeStatus(data.post_image_id);
                }
                return;
            }
            
            // Handle regular post likes
            // Skip if current user triggered this (already updated optimistically)
            if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
                console.log('[WS] ✓ Skipping refresh - current user action');
                return;
            }
            
            console.log('[WS] × Will refresh - different user');
            const post = this.posts.find(p => p.id === data.target_id);
            if (post && data.target_type === 'Post') {
                console.log('[WS] Refreshing post likes for post:', data.target_id);
                // Refresh like count for other users
                this.refreshPostLikes(post.id);
            }
        },
        handleLikeRemoved(data) {
            console.log('[WS] handleLikeRemoved FIRED');
            console.log('[WS]   data.user_id:', data.user_id, 'type:', typeof data.user_id);
            console.log('[WS]   this.currentUserId:', this.currentUserId, 'type:', typeof this.currentUserId);
            
            // Handle post image unlikes
            if (data.target_type === 'PostImage' && data.post_image_id) {
                // Skip if current user triggered this
                if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
                    console.log('[WS] ✓ Skipping image unlike refresh - current user action');
                    return;
                }
                
                // Refresh image like if viewing this image
                if (this.postDetailView.isOpen && this.postDetailView.currentImageId === data.post_image_id) {
                    console.log('[WS] Refreshing image unlike for image:', data.post_image_id);
                    this.loadImageLikeStatus(data.post_image_id);
                }
                return;
            }
            
            // Handle regular post unlikes
            // Skip if current user triggered this (already updated optimistically)
            if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
                console.log('[WS] ✓ Skipping refresh - current user action');
                return;
            }
            
            console.log('[WS] × Will refresh - different user');
            const post = this.posts.find(p => p.id === data.target_id);
            if (post && data.target_type === 'Post') {
                console.log('[WS] Refreshing post likes for post:', data.target_id);
                // Refresh like count for other users
                this.refreshPostLikes(post.id);
            }
        },
        handleCommentAdded(data) {
            const post = this.posts.find(p => p.id === data.post_id);
            if (post) {
                // Handle image comments
                if (data.post_image_id && this.postDetailView.isOpen && 
                    this.postDetailView.currentImageId === data.post_image_id) {
                    console.log('[WS] Refreshing image comments for image:', data.post_image_id);
                    this.loadImageComments(data.post_image_id);
                } else if (!data.post_image_id) {
                    // Handle regular post comments
                    // Reload comments for this post
                    if (post.showComments) {
                        this.loadComments(post.id);
                    } else {
                        post.comment_count = (post.comment_count || 0) + 1;
                    }
                }
            }
        },
        handleNewPost(data) {
            // Show notification about new post
            if (data.user_id !== this.currentUserId) {
                this.showToast(`${data.user_name} created a new post`, 'info');
                // Optionally reload feed
            }
        },
        handleNotification(data) {
            // Show notification
            this.showToast(data.message, 'info');
            
            // Update notification badge if exists
            if (typeof window.updateNotificationBadge === 'function') {
                window.updateNotificationBadge();
            }
        },
        async refreshPostLikes(postId) {
            console.log('[Refresh] Fetching likes for post:', postId);
            try {
                const response = await fetch(`/likes/get-post-likes/${postId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (response.ok) {
                    const data = await response.json();
                    console.log('[Refresh] Got data:', data);
                    const post = this.posts.find(p => p.id === postId);
                    if (post) {
                        console.log('[Refresh] Updating post - old:', post.like_count, '/', post.is_liked, 'new:', data.like_count, '/', data.is_liked);
                        post.like_count = data.like_count;
                        post.is_liked = data.is_liked;
                    }
                }
            } catch (error) {
                console.error('Failed to refresh likes:', error);
            }
        },
        showToast(message, type = 'info') {
            if (typeof window.showFlash === 'function') {
                window.showFlash(message, type);
            }
        },
        removeNewEditImage(postId, index) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;
            post.newEditImages.splice(index, 1);
            post.newEditImagePreviews.splice(index, 1);
        },
        noop() {},
        handleOpenComment(postId) {
            if (typeof this.openCommentInput === 'function') return this.openCommentInput(postId);
            if (typeof window.openCommentInput === 'function') return window.openCommentInput(postId);
                console.warn('openCommentInput not available');
        },
        
        handleImageSelect(event) {
            const files = Array.from(event.target.files || []);
            this.addImages(files);
            // Clear file input so same file can be re-selected later
            if (event.target) event.target.value = '';
        },
        handleDragEnter(event) {
            event.preventDefault();
            event.stopPropagation();
            this.newPost.isDragging = true;
        },
        handleDragOver(event) {
            event.preventDefault();
            event.stopPropagation();
            this.newPost.isDragging = true;
        },
        handleDragLeave(event) {
            event.preventDefault();
            event.stopPropagation();
            // Only set isDragging to false if we're leaving the drop zone entirely
            if (event.target.classList.contains('post-create-card')) {
                this.newPost.isDragging = false;
            }
        },
        handleDrop(event) {
            event.preventDefault();
            event.stopPropagation();
            this.newPost.isDragging = false;
            
            const files = Array.from(event.dataTransfer.files || []);
            const imageFiles = files.filter(file => file.type.startsWith('image/'));
            
            if (imageFiles.length > 0) {
                this.addImages(imageFiles);
            } else if (files.length > 0) {
                this.newPost.error = 'Please drop only image files';
            }
        },
        addImages(files) {
            this.newPost.error = '';

            // Validate file count
            if (this.newPost.images.length + files.length > 10) {
                this.newPost.error = 'You can upload a maximum of 10 images per post';
                return;
            }

            const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            const maxSize = 10 * 1024 * 1024;

            for (const file of files) {
                if (!validTypes.includes(file.type)) {
                    this.newPost.error = 'Please upload valid image files (JPG, PNG, or GIF)';
                    return;
                }

                if (file.size > maxSize) {
                    this.newPost.error = 'Each image must be less than 10MB';
                    return;
                }

                this.newPost.images.push(file);

                const reader = new FileReader();
                reader.onload = (e) => {
                    this.newPost.imagePreview.push(e.target.result);
                };
                reader.readAsDataURL(file);
            }
        },
        // toast removed
        removeImage(index) {
            this.newPost.images.splice(index, 1);
            this.newPost.imagePreview.splice(index, 1);
        },
        async createPost() {
            this.newPost.error = '';
            this.newPost.isSubmitting = true;
            
            const textContent = this.newPost.content.trim();
            
            
            if (!textContent && this.newPost.images.length === 0) {
                this.newPost.error = 'Please add some text or images to your post';
                this.newPost.isSubmitting = false;
                return;
            }
            
          
            const formData = new FormData();
            formData.append('content_text', textContent);
            formData.append('privacy', this.newPost.privacy);
            
            
            this.newPost.images.forEach((image, index) => {
                formData.append('post_images[]', image);
            });
            
            try {
                const response = await fetch('/posts/create', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': getCsrfToken() },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Reset form
                    this.newPost.content = '';
                    this.newPost.images = [];
                    this.newPost.imagePreview = [];
                    this.newPost.privacy = 'public';
                    
                    // Reset textarea height
                    if (this.$refs.postTextarea) {
                        this.$refs.postTextarea.style.height = 'auto';
                    }
                    
                    // Reload to show new post and Flash success message
                    window.location.reload();
                } else {
                    // Error 
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error creating post:', error);
                this.newPost.error = 'Failed to create post. Please try again.';
            } finally {
                this.newPost.isSubmitting = false;
            }
        },
        // Image Comment Edit/Delete Methods
        
        // WebSocket Methods
        initWebSocket() {
            console.log('[Dashboard] Initializing WebSocket...');
            this.wsManager = new WebSocketManager(this.currentUserId);
            
            // Add message handler
            this.wsManager.addMessageHandler((data) => {
                this.handleWebSocketMessage(data);
            });
            
            // Connect
            this.wsManager.connect();
        },
        handleWebSocketMessage(data) {
            console.log('[Dashboard] WebSocket message:', data);
            
            switch(data.type) {
                case 'connection':
                    this.wsConnected = data.status === 'connected';
                    break;
                    
                case 'like_added':
                    this.handleLikeAdded(data);
                    break;
                    
                case 'like_removed':
                    this.handleLikeRemoved(data);
                    break;
                    
                case 'comment_added':
                    this.handleCommentAdded(data);
                    break;
                    
                case 'new_post':
                    this.handleNewPost(data);
                    break;
                    
                case 'notification':
                    this.handleNotification(data);
                    break;
            }
        },
        handleLikeAdded(data) {
            console.log('[WS-App2] handleLikeAdded FIRED');
            console.log('[WS-App2]   data.user_id:', data.user_id, 'type:', typeof data.user_id);
            console.log('[WS-App2]   this.currentUserId:', this.currentUserId, 'type:', typeof this.currentUserId);
            
            // Handle post image likes
            if (data.target_type === 'PostImage' && data.post_image_id) {
                // Skip if current user triggered this
                if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
                    console.log('[WS-App2] ✓ Skipping image like refresh - current user action');
                    return;
                }
                
                // Refresh image like if viewing this image
                if (this.postDetailView.isOpen && this.postDetailView.currentImageId === data.post_image_id) {
                    console.log('[WS-App2] Refreshing image like for image:', data.post_image_id);
                    this.loadImageLikeStatus(data.post_image_id);
                }
                return;
            }
            
            // Handle regular post likes
            // Skip if current user triggered this (already updated optimistically)
            if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
                console.log('[WS-App2] ✓ Skipping refresh - current user action');
                return;
            }
            
            console.log('[WS-App2] × Will refresh - different user');
            const post = this.posts.find(p => p.id === data.target_id);
            if (post && data.target_type === 'Post') {
                console.log('[WS-App2] Refreshing post likes for post:', data.target_id);
                this.refreshPostLikes(post.id);
            }
        },
        handleLikeRemoved(data) {
            console.log('[WS-App2] handleLikeRemoved FIRED');
            console.log('[WS-App2]   data.user_id:', data.user_id, 'type:', typeof data.user_id);
            console.log('[WS-App2]   this.currentUserId:', this.currentUserId, 'type:', typeof this.currentUserId);
            
            // Handle post image unlikes
            if (data.target_type === 'PostImage' && data.post_image_id) {
                // Skip if current user triggered this
                if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
                    console.log('[WS-App2] ✓ Skipping image unlike refresh - current user action');
                    return;
                }
                
                // Refresh image like if viewing this image
                if (this.postDetailView.isOpen && this.postDetailView.currentImageId === data.post_image_id) {
                    console.log('[WS-App2] Refreshing image unlike for image:', data.post_image_id);
                    this.loadImageLikeStatus(data.post_image_id);
                }
                return;
            }
            
            // Handle regular post unlikes
            // Skip if current user triggered this (already updated optimistically)
            if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
                console.log('[WS-App2] ✓ Skipping refresh - current user action');
                return;
            }
            
            console.log('[WS-App2] × Will refresh - different user');
            const post = this.posts.find(p => p.id === data.target_id);
            if (post && data.target_type === 'Post') {
                console.log('[WS-App2] Refreshing post likes for post:', data.target_id);
                this.refreshPostLikes(post.id);
            }
        },
        handleCommentAdded(data) {
            const post = this.posts.find(p => p.id === data.post_id);
            if (post) {
                // Handle image comments
                if (data.post_image_id && this.postDetailView.isOpen && 
                    this.postDetailView.currentImageId === data.post_image_id) {
                    console.log('[WS-App2] Refreshing image comments for image:', data.post_image_id);
                    this.loadImageComments(data.post_image_id);
                } else if (!data.post_image_id) {
                    // Handle regular post comments
                    if (post.showComments) {
                        this.loadComments(post.id);
                    } else {
                        post.comment_count = (post.comment_count || 0) + 1;
                    }
                }
            }
        },
        handleNewPost(data) {
            if (data.user_id !== this.currentUserId) {
                if (typeof window.showFlash === 'function') {
                    window.showFlash(`${data.user_name} created a new post`, 'info');
                }
            }
        },
        handleNotification(data) {
            if (typeof window.showFlash === 'function') {
                window.showFlash(data.message, 'info');
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
                console.error('Failed to refresh likes:', error);
            }
        }
        
    },
    mounted() {
        console.log('Dashboard mounted');
        
        // Detect profile/cover photos in posts
        if (this.posts && this.posts.length > 0) {
            this.posts.forEach(post => {
                this.detectProfileCoverPhoto(post);
            });
        }
        
        // Initialize WebSocket for real-time updates
        if (typeof WebSocketManager !== 'undefined' && this.currentUserId) {
            this.initWebSocket();
        }
        
        // Initialize Lucide icons
        if (window.lucide) {
            lucide.createIcons();
        }
        console.debug('dashboard mounted, posts=', this.posts && this.posts.length);
        
        document.addEventListener('click', this.handleClickOutside);
        // Expose comment opener globally as a fallback for templates
        if (typeof this.openCommentInput === 'function') {
            window.openCommentInput = this.openCommentInput.bind(this);
        }
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
        // Expose the delegator globally so templates outside instance scope still work
        if (typeof this.handleOpenComment === 'function') {
            window.handleOpenComment = this.handleOpenComment.bind(this);
        }
        // Expose main image viewer globally so comment images can call it
        if (typeof this.openImageViewer === 'function') {
            window.openImageViewer = this.openImageViewer.bind(this);
        }
        // drag/drop document listeners removed
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
        if (this._keydownHandler) {
            document.removeEventListener('keydown', this._keydownHandler);
        }
        
        // Handle keyboard navigation for image viewer and post detail view
        this._keydownHandler = (e) => {
            if (this.postDetailView && this.postDetailView.isOpen) {
                if (e.key === 'Escape') {
                    this.closePostDetailView();
                } else if (e.key === 'ArrowLeft') {
                    this.postDetailPrevImage();
                } else if (e.key === 'ArrowRight') {
                    this.postDetailNextImage();
                }
                return;
            }
            if (this.imageViewer && this.imageViewer.isOpen) {
                if (e.key === 'Escape') {
                    this.closeImageViewer();
                } else if (e.key === 'ArrowLeft') {
                    this.prevImage();
                } else if (e.key === 'ArrowRight') {
                    this.nextImage();
                }
            }
        };
        document.addEventListener('keydown', this._keydownHandler);
        // Remove global fallback
        if (window.openCommentInput && window.openCommentInput === this.openCommentInput) {
            try { delete window.openCommentInput; } catch (e) { window.openCommentInput = undefined; }
        }
        if (window.handleOpenComment && window.handleOpenComment === this.handleOpenComment) {
            try { delete window.handleOpenComment; } catch (e) { window.handleOpenComment = undefined; }
        }
        // Removed drag/drop cleanup
    },
    updated() {
        // Re-initialize Lucide icons after DOM updates
        if (window.lucide) {
            lucide.createIcons();
        }
    }
});

const dashboardEl = document.getElementById('dashboardApp');
if (dashboardEl) {
    app.mount('#dashboardApp');
}
