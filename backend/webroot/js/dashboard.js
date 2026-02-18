console.log('=== dashboard.js LOADED with image comment debugging ===');
const { createApp } = Vue;

// Ensure dashboardData exists so data() never sees undefined (e.g. script order)
if (typeof window.dashboardData === 'undefined') {
    window.dashboardData = { user: null, posts: [] };
}

const app = createApp({
    data() {
        return {
            currentUserId: window.dashboardData?.user?.id ?? null,
            user: {
                id: window.dashboardData?.user?.id || null,
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
                newEditImagePreviews: []
            })),
            newPost: {
                content: '',
                images: [],
                imagePreview: [],
                privacy: 'public',
                isSubmitting: false,
                error: '',
                showEmojiPicker: false,
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
                imageCommentImagePreview: null
            },
            appReady: false
        }
    },
    computed: {
        window() {
            return window;
        }
    },
    methods: {
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
            if (event) event.stopPropagation();
            const post = this.posts.find(p => p.id === postId);
            if (post) {
                this.posts.forEach(p => {
                    if (p.id !== postId) p.showMenu = false;
                });
                post.showMenu = !post.showMenu;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            }
        },
        editPost(postId) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;
            post.showMenu = false;
            post.isEditing = true;
            post.editContent = post.content_text || '';
            post.editPrivacy = post.privacy || 'public';
            post.editImages = JSON.parse(JSON.stringify(post.post_images || []));
            post.removedImageIds = [];
            post.newEditImages = [];
            post.newEditImagePreviews = [];
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        cancelEditPost(postId) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;
            post.isEditing = false;
            post.editContent = '';
            post.editImages = [];
            post.removedImageIds = [];
            post.newEditImages = [];
            post.newEditImagePreviews = [];
            const fileInput = document.getElementById('edit-images-' + postId);
            if (fileInput) fileInput.value = '';
        },
        removeExistingImage(postId, imageId, index) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;
            post.removedImageIds.push(imageId);
            post.editImages.splice(index, 1);
        },
        removeNewEditImage(postId, index) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;
            post.newEditImages.splice(index, 1);
            post.newEditImagePreviews.splice(index, 1);
        },
        handleEditImageSelect(event, postId) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;
            const files = Array.from(event.target.files);
            const totalImages = post.editImages.length + post.newEditImages.length + files.length;
            if (totalImages > 10) {
                (typeof window.showFlash === 'function' ? window.showFlash : alert)('Maximum 10 images per post', 'error');
                event.target.value = '';
                return;
            }
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            files.forEach(file => {
                if (!validTypes.includes(file.type) || file.size > 10 * 1024 * 1024) return;
                post.newEditImages.push(file);
                const reader = new FileReader();
                reader.onload = (e) => { post.newEditImagePreviews.push(e.target.result); };
                reader.readAsDataURL(file);
            });
            event.target.value = '';
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        async saveEditPost(postId) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;
            const hasContent = post.editContent && post.editContent.trim();
            const hasImages = (post.editImages.length + post.newEditImages.length) > 0;
            if (!hasContent && !hasImages) {
                (typeof window.showFlash === 'function' ? window.showFlash : alert)('Post must have either text or images', 'error');
                return;
            }
            const formData = new FormData();
            formData.append('content_text', post.editContent || '');
            formData.append('privacy', post.editPrivacy || 'public');
            (post.removedImageIds || []).forEach(id => formData.append('removed_images[]', id));
            (post.newEditImages || []).forEach(image => formData.append('new_images[]', image));
            try {
                const response = await fetch(`/posts/edit/${postId}`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    post.content_text = data.post.content_text;
                    post.privacy = data.post.privacy || 'public';
                    post.post_images = data.post.post_images || [];
                    post.modified = data.post.modified;
                    post.isEditing = false;
                    post.editContent = '';
                    post.editPrivacy = 'public';
                    post.editImages = [];
                    post.removedImageIds = [];
                    post.newEditImages = [];
                    post.newEditImagePreviews = [];
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    (typeof window.showFlash === 'function' ? window.showFlash : alert)('Post updated successfully!', 'success');
                } else {
                    (typeof window.showFlash === 'function' ? window.showFlash : alert)(data.message || 'Failed to update post.', 'error');
                }
            } catch (err) {
                console.error('Error updating post:', err);
                (typeof window.showFlash === 'function' ? window.showFlash : alert)('Failed to update post.', 'error');
            }
        },
        async deletePost(postId) {
            const post = this.posts.find(p => p.id === postId);
            if (post) post.showMenu = false;
            const confirmed = typeof window.showConfirmModal === 'function'
                ? await window.showConfirmModal('Are you sure you want to delete this post? This action cannot be undone.')
                : confirm('Are you sure you want to delete this post? This action cannot be undone.');
            if (!confirmed) return;
            try {
                const response = await fetch(`/posts/delete/${postId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (response.ok) {
                    this.posts = this.posts.filter(p => p.id !== postId);
                    if (this.postDetailView.post && this.postDetailView.post.id === postId) {
                        this.postDetailView.isOpen = false;
                        this.postDetailView.post = null;
                    }
                    (typeof window.showFlash === 'function' ? window.showFlash : alert)('Post deleted successfully!', 'success');
                } else {
                    (typeof window.showFlash === 'function' ? window.showFlash : alert)('Failed to delete post.', 'error');
                }
            } catch (err) {
                console.error('Error deleting post:', err);
                (typeof window.showFlash === 'function' ? window.showFlash : alert)('Failed to delete post.', 'error');
            }
        },
        handleClickOutside(event) {
            const target = event.target;
            if (!target.closest('[data-post-menu]') && !target.closest('button[data-menu-trigger]')) {
                this.posts.forEach(post => { if (post.showMenu) post.showMenu = false; });
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
            this.postDetailView.imageCommentImage = null;
            this.postDetailView.imageCommentImagePreview = null;
            // Facebook-style: only use per-image likes/comments when post has 2+ images; single image = use post-level
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
            if (!text && !v.imageCommentImage) return;
            const formData = new FormData();
            formData.append('post_id', v.post.id);
            formData.append('post_image_id', v.currentImageId);
            if (text) formData.append('content_text', text);
            if (v.imageCommentImage) formData.append('content_image', v.imageCommentImage);
            
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
                    v.imageCommentImage = null;
                    v.imageCommentImagePreview = null;
                    const newComment = { ...data.comment, is_liked: false, like_count: 0 };
                    console.log('Adding comment to imageComments:', newComment);
                    v.imageComments.push(newComment);
                    const fileInput = document.getElementById('comment-image-postimage-' + v.currentImageId);
                    if (fileInput) fileInput.value = '';
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
                this.postDetailView.currentImageId = img.id;
                this.loadImageComments(img.id);
                this.loadImageLikeStatus(img.id);
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
                this.postDetailView.currentImageId = img.id;
                this.loadImageComments(img.id);
                this.loadImageLikeStatus(img.id);
            } else {
                this.postDetailView.currentImageId = null;
            }
            
            this.$forceUpdate();
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
                    }
                }
            } catch (error) {
                console.error('Error toggling like:', error);
            }
        },
        handleOpenComment(postId) {
            if (typeof this.openCommentInput === 'function') return this.openCommentInput(postId);
            if (typeof window.openCommentInput === 'function') return window.openCommentInput(postId);
                console.warn('openCommentInput not available');
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
        openImageViewer(images, index = 0) {
            // Accept single image (string or object) or array and normalize to objects with image_path
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
                if (window.lucide) {
                    lucide.createIcons();
                }
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
        autoResize(event) {
            const textarea = event.target;
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
        },
        // drag/drop handlers removed
        toggleEmojiPicker() {
            const picker = document.getElementById('emojiPickerContainer');
            if (picker) {
                const isVisible = picker.style.visibility === 'visible';
                picker.style.visibility = isVisible ? 'hidden' : 'visible';
                picker.style.opacity = isVisible ? '0' : '1';
                picker.style.pointerEvents = isVisible ? 'none' : 'auto';
                this.newPost.showEmojiPicker = !this.newPost.showEmojiPicker;
            }
        },
        insertEmoji(emoji) {
            const textarea = this.$refs.postTextarea;
            if (textarea) {
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = this.newPost.content;
                this.newPost.content = text.substring(0, start) + emoji + text.substring(end);
                
                // Hide emoji picker
                const picker = document.getElementById('emojiPickerContainer');
                if (picker) {
                    picker.style.visibility = 'hidden';
                    picker.style.opacity = '0';
                    picker.style.pointerEvents = 'none';
                }
                this.newPost.showEmojiPicker = false;
                
                this.$nextTick(() => {
                    textarea.focus();
                    const newPos = start + emoji.length;
                    textarea.setSelectionRange(newPos, newPos);
                    
                    // Trigger auto-resize
                    textarea.style.height = 'auto';
                    textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
                });
            }
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
            
            // Validate at least content or images
            if (!textContent && this.newPost.images.length === 0) {
                this.newPost.error = 'Please add some text or images to your post';
                this.newPost.isSubmitting = false;
                return;
            }
            
            // Create FormData for file upload
            const formData = new FormData();
            formData.append('content_text', textContent);
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
                    
                    // Reset textarea height
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
        },
        async toggleComments(postId) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;
            
            // Toggle visibility
            post.showComments = !post.showComments;
            
            // Load comments if showing and not loaded yet
            if (post.showComments && post.comments.length === 0) {
                await this.loadComments(postId);
            }
        },
        async loadComments(postId) {
            console.debug('loadComments called for', postId);
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
                    // Add like status to each comment
                    post.comments = data.comments.map(comment => ({
                        ...comment,
                        is_liked: false,
                        like_count: 0
                    }));
                    
                    // Load like status for each comment
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
                return; // Nothing to submit
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
                    // Reset input
                    post.newComment = '';
                    post.commentImage = null;
                    post.commentImagePreview = null;
                    
                    // Clear file input
                    const fileInput = document.getElementById('comment-image-' + postId);
                    if (fileInput) fileInput.value = '';
                    
                    // Increment comment count
                    post.comment_count = (post.comment_count || 0) + 1;
                    
                    // Add the new comment to the list if comments are visible
                    if (post.showComments && data.comment) {
                        // Add like status to the new comment
                        const newComment = {
                            ...data.comment,
                            is_liked: false,
                            like_count: 0
                        };
                        post.comments.push(newComment);
                    } else {
                        // Show comments section and load them
                        post.showComments = true;
                        await this.loadComments(postId);
                    }
                    
                    // Re-initialize icons
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
            
            // Validate file
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
            
            // Create preview
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
        async deleteComment(postId, commentId) {
            if (!confirm('Delete this comment?')) return;
            try {
                const response = await fetch(`/comments/delete/${commentId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) throw new Error('Failed to delete comment');

                const data = await response.json();
                // On success, remove comment from local state
                const post = this.posts.find(p => p.id === postId);
                if (post) {
                    const idx = post.comments.findIndex(c => c.id === commentId);
                    if (idx !== -1) {
                        post.comments.splice(idx, 1);
                        post.comment_count = Math.max(0, (post.comment_count || 1) - 1);
                    }
                }
            } catch (error) {
                console.error('Error deleting comment:', error);
                alert('Failed to delete comment.');
            }
        },
        
    },
    mounted() {
        // Initialize Lucide icons
        if (window.lucide) {
            lucide.createIcons();
        }
        console.debug('dashboard mounted, posts=', this.posts && this.posts.length);
        
        // Setup emoji picker event listener
        this.$nextTick(() => {
            const picker = document.querySelector('emoji-picker');
            if (picker && !picker.hasAttribute('data-listener')) {
                picker.setAttribute('data-listener', 'true');
                picker.addEventListener('emoji-click', (event) => {
                    this.insertEmoji(event.detail.unicode);
                });
            }
        });        
        document.addEventListener('click', this.handleClickOutside);
        // Expose comment opener globally as a fallback for templates
        if (typeof this.openCommentInput === 'function') {
            window.openCommentInput = this.openCommentInput.bind(this);
        }
        if (typeof this.openPostDetailView === 'function') {
            window.openPostDetailView = this.openPostDetailView.bind(this);
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
        // Close emoji picker when clicking outside
        document.addEventListener('click', (e) => {
            const emojiButton = e.target.closest('[title="Add emoji"]');
            const emojiPicker = e.target.closest('emoji-picker');
            const emojiContainer = e.target.closest('#emojiPickerContainer');
            
            if (!emojiButton && !emojiPicker && !emojiContainer && this.newPost.showEmojiPicker) {
                this.newPost.showEmojiPicker = false;
                const picker = document.getElementById('emojiPickerContainer');
                if (picker) {
                    picker.style.visibility = 'hidden';
                    picker.style.opacity = '0';
                    picker.style.pointerEvents = 'none';
                }
            }
        });
        
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
        
        // Setup emoji picker event listener when it appears
        this.$nextTick(() => {
            const picker = document.querySelector('emoji-picker');
            if (picker && !picker.hasAttribute('data-listener')) {
                picker.setAttribute('data-listener', 'true');
                picker.addEventListener('emoji-click', (event) => {
                    this.insertEmoji(event.detail.unicode);
                });
            }
        });
    }
});

const dashboardEl = document.getElementById('dashboardApp');
if (dashboardEl) {
    app.mount('#dashboardApp');
}
