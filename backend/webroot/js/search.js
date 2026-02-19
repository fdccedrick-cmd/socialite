console.log('=== search.js LOADED ===');
const { createApp } = Vue;

// Ensure searchData exists
if (typeof window.searchData === 'undefined') {
    window.searchData = { user: null, posts: [] };
}

const app = createApp({
    data() {
        const userId = window.searchData?.user?.id ?? null;
        return {
            currentUserId: userId ? parseInt(userId, 10) : null,
            user: {
                id: userId ? parseInt(userId, 10) : null,
                username: window.searchData?.user?.username || 'user',
                avatar: window.searchData?.user?.avatar || 'https://i.pravatar.cc/150?img=1'
            },
            
            posts: (window.searchData?.posts || []).map(post => ({
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
            wsManager: null,
            wsConnected: false,
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
        
        // WebSocket
        initWebSocket() {
            console.log('[Search] Initializing WebSocket...');
            this.wsManager = new WebSocketManager(this.currentUserId);
            
            this.wsManager.addMessageHandler((data) => {
                this.handleWebSocketMessage(data);
            });
            
            this.wsManager.connect();
        },
        handleWebSocketMessage(data) {
            console.log('[Search] WebSocket message:', data);
            
            switch(data.type) {
                case 'connection':
                    this.wsConnected = true;
                    console.log('[Search] WebSocket connected');
                    break;
                case 'like':
                    this.handleLikeUpdate(data);
                    break;
                case 'comment':
                    this.handleCommentAdded(data);
                    break;
                case 'notification':
                    this.handleNotification(data);
                    break;
            }
        },
        handleLikeUpdate(data) {
            const post = this.posts.find(p => p.id === data.post_id);
            if (post) {
                this.refreshPostLikes(post.id);
            }
        },
        handleCommentAdded(data) {
            const post = this.posts.find(p => p.id === data.post_id);
            if (post) {
                if (data.post_image_id && this.postDetailView.isOpen && 
                    this.postDetailView.currentImageId === data.post_image_id) {
                    this.loadImageComments(data.post_image_id);
                } else if (!data.post_image_id) {
                    if (post.showComments) {
                        this.loadComments(post.id);
                    } else {
                        post.comment_count = (post.comment_count || 0) + 1;
                    }
                }
            }
        },
        handleNotification(data) {
            this.showToast(data.message, 'info');
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
        },
        showToast(message, type = 'info') {
            if (typeof window.showFlash === 'function') {
                window.showFlash(message, type);
            }
        },
        
        // Post actions
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
        
        // Image viewer
        openImageViewer(images, index = 0) {
            this.imageViewer.images = images;
            this.imageViewer.currentIndex = index;
            this.imageViewer.isOpen = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        closeImageViewer() {
            this.imageViewer.isOpen = false;
            this.imageViewer.images = [];
            this.imageViewer.currentIndex = 0;
            document.body.style.overflow = '';
        },
        nextImage() {
            if (this.imageViewer.currentIndex < this.imageViewer.images.length - 1) {
                this.imageViewer.currentIndex++;
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        prevImage() {
            if (this.imageViewer.currentIndex > 0) {
                this.imageViewer.currentIndex--;
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        
        // Post detail view
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
        postDetailNextImage() {
            if (!this.postDetailView.post || !this.postDetailView.post.post_images?.length) return;
            const imgs = this.postDetailView.post.post_images;
            const totalImages = imgs.length;
            
            // Loop navigation: go to first image if at last, otherwise go to next
            if (this.postDetailView.imageIndex >= totalImages - 1) {
                this.postDetailView.imageIndex = 0;
            } else {
                this.postDetailView.imageIndex++;
            }
            
            const img = imgs[this.postDetailView.imageIndex];
            if (img && img.id && imgs.length >= 2) {
                this.postDetailView.currentImageId = parseInt(img.id, 10);
                this.loadImageComments(this.postDetailView.currentImageId);
                this.loadImageLikeStatus(this.postDetailView.currentImageId);
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        postDetailPrevImage() {
            if (!this.postDetailView.post || !this.postDetailView.post.post_images?.length) return;
            const imgs = this.postDetailView.post.post_images;
            const totalImages = imgs.length;
            
            // Loop navigation: go to last image if at first, otherwise go to previous
            if (this.postDetailView.imageIndex === 0) {
                this.postDetailView.imageIndex = totalImages - 1;
            } else {
                this.postDetailView.imageIndex--;
            }
            
            const img = imgs[this.postDetailView.imageIndex];
            if (img && img.id && imgs.length >= 2) {
                this.postDetailView.currentImageId = parseInt(img.id, 10);
                this.loadImageComments(this.postDetailView.currentImageId);
                this.loadImageLikeStatus(this.postDetailView.currentImageId);
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        
        // Comments
        async loadComments(postId) {
            try {
                const response = await fetch(`/comments/get-by-post/${postId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) return;
                const data = await response.json();
                const post = this.posts.find(p => p.id === postId);
                if (post) {
                    post.comments = data.comments || [];
                    post.comment_count = post.comments.length;
                }
            } catch (e) {
                console.error('Error loading comments:', e);
            }
        },
        toggleComments(postId) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;
            post.showComments = !post.showComments;
            if (post.showComments && post.comments.length === 0) {
                this.loadComments(postId);
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        async submitComment(postId) {
            const post = this.posts.find(p => p.id === postId);
            if (!post || (!post.newComment.trim() && !post.commentImage)) return;
            
            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('content_text', post.newComment);
            if (post.commentImage) {
                formData.append('content_image', post.commentImage);
            }
            
            try {
                const response = await fetch('/comments/add', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                
                if (response.ok) {
                    post.newComment = '';
                    post.commentImage = null;
                    post.commentImagePreview = null;
                    await this.loadComments(postId);
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                } else {
                    (typeof window.showFlash === 'function' ? window.showFlash : alert)('Failed to add comment', 'error');
                }
            } catch (err) {
                console.error('Error adding comment:', err);
                (typeof window.showFlash === 'function' ? window.showFlash : alert)('Failed to add comment', 'error');
            }
        },
        handleOpenComment(postId) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;
            if (!post.showComments) {
                post.showComments = true;
                if (post.comments.length === 0) {
                    this.loadComments(postId);
                }
            }
            this.$nextTick(() => {
                const commentInput = document.querySelector(`#post-${postId} .comment-input`);
                if (commentInput) commentInput.focus();
                if (window.lucide) lucide.createIcons();
            });
        },
        async deleteComment(commentId, postId) {
            const confirmed = typeof window.showConfirmModal === 'function'
                ? await window.showConfirmModal('Are you sure you want to delete this comment?')
                : confirm('Are you sure you want to delete this comment?');
            if (!confirmed) return;
            
            try {
                const response = await fetch(`/comments/delete/${commentId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                if (response.ok) {
                    await this.loadComments(postId);
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    (typeof window.showFlash === 'function' ? window.showFlash : alert)('Comment deleted', 'success');
                } else {
                    (typeof window.showFlash === 'function' ? window.showFlash : alert)('Failed to delete comment', 'error');
                }
            } catch (err) {
                console.error('Error deleting comment:', err);
                (typeof window.showFlash === 'function' ? window.showFlash : alert)('Failed to delete comment', 'error');
            }
        },
        editComment(postId, commentId) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;
            
            const comment = post.comments.find(c => c.id === commentId);
            if (!comment) return;
            
            // Set editing state
            comment.isEditing = true;
            comment.editContent = comment.content_text || '';
            this.$forceUpdate();
        },
        async saveCommentEdit(postId, commentId) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;
            
            const comment = post.comments.find(c => c.id === commentId);
            if (!comment || !comment.isEditing) return;
            
            const newContent = (comment.editContent || '').trim();
            if (!newContent) {
                alert('Comment cannot be empty.');
                return;
            }
            
            try {
                const response = await fetch(`/comments/edit/${commentId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        content_text: newContent
                    })
                });

                if (!response.ok) throw new Error('Failed to update comment');

                const data = await response.json();
                
                // Update comment in local state
                comment.content_text = newContent;
                comment.isEditing = false;
                delete comment.editContent;
                
                this.$forceUpdate();
            } catch (error) {
                console.error('Error updating comment:', error);
                alert('Failed to update comment.');
            }
        },
        cancelCommentEdit(postId, commentId) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;
            
            const comment = post.comments.find(c => c.id === commentId);
            if (!comment) return;
            
            comment.isEditing = false;
            delete comment.editContent;
            this.$forceUpdate();
        },
        async toggleCommentLike(commentId, postId) {
            try {
                const response = await fetch(`/likes/toggle-comment/${commentId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                if (response.ok) {
                    await this.loadComments(postId);
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                }
            } catch (err) {
                console.error('Error toggling comment like:', err);
            }
        },
        
        // Image comments
        async loadImageComments(postImageId) {
            const imageId = parseInt(postImageId, 10);
            console.log('[Search] Loading comments for image:', imageId);
            try {
                const response = await fetch(`/comments/get-by-post-image/${imageId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) {
                    console.error('[Search] Failed to load image comments:', response.status);
                    return;
                }
                const data = await response.json();
                console.log('[Search] Image comments response:', data);
                this.postDetailView.imageComments = (data.comments || data || []).map(c => ({ ...c, is_liked: false, like_count: 0 }));
                
                // Load like status for each comment
                for (let i = 0; i < this.postDetailView.imageComments.length; i++) {
                    const c = this.postDetailView.imageComments[i];
                    const likeRes = await fetch(`/likes/comment/${c.id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (likeRes.ok) {
                        const likeData = await likeRes.json();
                        this.postDetailView.imageComments[i].like_count = likeData.count || 0;
                        this.postDetailView.imageComments[i].is_liked = likeData.is_liked || false;
                    }
                }
                console.log('[Search] Loaded', this.postDetailView.imageComments.length, 'image comments');
            } catch (e) { 
                console.error('[Search] Error loading image comments:', e); 
            }
        },
        async submitImageComment() {
            const v = this.postDetailView;
            if (!v.post || !v.currentImageId) return;
            const text = (v.imageNewComment || '').trim();
            if (!text && !v.imageCommentImage) return;
            
            // Ensure IDs are plain integers
            const postId = parseInt(v.post.id, 10);
            const imageId = parseInt(v.currentImageId, 10);
            
            console.log('[Search] Submitting comment for image:', imageId, 'post:', postId);
            
            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('post_image_id', imageId);
            if (text) formData.append('content_text', text);
            if (v.imageCommentImage) formData.append('content_image', v.imageCommentImage);
            
            try {
                const response = await fetch('/comments/add', { 
                    method: 'POST', 
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }, 
                    body: formData 
                });
                const data = await response.json();
                console.log('[Search] submitImageComment response:', { ok: response.ok, data });
                
                if (response.ok && data.success) {
                    v.imageNewComment = '';
                    v.imageCommentImage = null;
                    v.imageCommentImagePreview = null;
                    
                    // Add the new comment to the list
                    const newComment = { ...data.comment, is_liked: false, like_count: 0 };
                    console.log('[Search] Adding comment to imageComments:', newComment);
                    v.imageComments.push(newComment);
                    
                    const fileInput = document.getElementById('comment-image-postimage-' + v.currentImageId);
                    if (fileInput) fileInput.value = '';
                    
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                } else {
                    console.error('[Search] Failed to submit image comment:', data);
                    (typeof window.showFlash === 'function' ? window.showFlash : alert)(data.message || 'Failed to add comment', 'error');
                }
            } catch (e) { 
                console.error('[Search] Error submitting image comment:', e); 
                (typeof window.showFlash === 'function' ? window.showFlash : alert)('Failed to add comment', 'error');
            }
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
            
            const imageId = parseInt(this.postDetailView.currentImageId, 10);
            if (isNaN(imageId)) return;
            
            const wasLiked = this.postDetailView.imageIsLiked;
            const oldLikeCount = this.postDetailView.imageLikeCount || 0;
            
            this.postDetailView.imageIsLiked = !wasLiked;
            this.postDetailView.imageLikeCount = wasLiked ? oldLikeCount - 1 : oldLikeCount + 1;
            
            try {
                const response = await fetch(`/likes/toggle-post-image/${imageId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                
                const responseText = await response.text();
                const data = responseText ? JSON.parse(responseText) : {};
                
                if (!response.ok || !data.success) {
                    this.postDetailView.imageIsLiked = wasLiked;
                    this.postDetailView.imageLikeCount = oldLikeCount;
                } else {
                    this.postDetailView.imageLikeCount = data.count ?? this.postDetailView.imageLikeCount;
                    this.postDetailView.imageIsLiked = data.is_liked ?? this.postDetailView.imageIsLiked;
                }
            } catch (err) {
                console.error('Error toggling image like:', err);
                this.postDetailView.imageIsLiked = wasLiked;
                this.postDetailView.imageLikeCount = oldLikeCount;
            }
        },
        
        // Likes
        async handlePostLike(postId) {
            const post = this.posts.find(p => p.id === postId);
            if (!post) return;
            
            const wasLiked = post.is_liked;
            const oldCount = post.like_count || 0;
            
            post.is_liked = !wasLiked;
            post.like_count = wasLiked ? oldCount - 1 : oldCount + 1;
            
            try {
                const response = await fetch(`/likes/toggle-post/${postId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                if (!response.ok) {
                    post.is_liked = wasLiked;
                    post.like_count = oldCount;
                } else {
                    const data = await response.json();
                    post.like_count = data.count ?? post.like_count;
                    post.is_liked = data.is_liked ?? post.is_liked;
                }
                
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            } catch (err) {
                console.error('Error toggling like:', err);
                post.is_liked = wasLiked;
                post.like_count = oldCount;
            }
        },
        
        // Utilities
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const seconds = Math.floor(diff / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);
            
            if (seconds < 60) return 'just now';
            if (minutes < 60) return `${minutes}m ago`;
            if (hours < 24) return `${hours}h ago`;
            if (days < 7) return `${days}d ago`;
            
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    },
    mounted() {
        console.log('[Search] App mounted with', this.posts.length, 'posts');
        
        // Initialize Lucide icons
        if (window.lucide) {
            lucide.createIcons();
        }
        
        // Initialize WebSocket
        if (this.currentUserId && typeof WebSocketManager !== 'undefined') {
            this.initWebSocket();
        }
        
        // Click outside handler
        document.addEventListener('click', this.handleClickOutside);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (this.imageViewer.isOpen) {
                if (e.key === 'Escape') this.closeImageViewer();
                if (e.key === 'ArrowLeft') this.prevImage();
                if (e.key === 'ArrowRight') this.nextImage();
            }
            if (this.postDetailView.isOpen) {
                if (e.key === 'Escape') this.closePostDetailView();
                if (e.key === 'ArrowLeft') this.prevPostDetailImage();
                if (e.key === 'ArrowRight') this.nextPostDetailImage();
            }
        });
        
        this.appReady = true;
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
        if (this.wsManager) {
            this.wsManager.disconnect();
        }
    }
});

app.mount('#searchApp');
console.log('[Search] App initialized');
