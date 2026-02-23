/**
 * Shared Post Utilities
 * Common methods used across Dashboard, Profile, and Search pages
 * This module exports a mixin object that can be spread into Vue component methods
 */

// CSRF token helper
window.getCsrfToken = function() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
};

// Export shared methods as a mixin object
window.SharedPostUtils = {
    // ============ Post Type Detection ============
    detectProfileCoverPhoto(post) {
        // Use post_type field if available (most reliable)
        if (post.post_type === 'profile_photo') {
            post.is_profile_photo = true;
            post.is_cover_photo = false;
            return;
        } else if (post.post_type === 'cover_photo') {
            post.is_profile_photo = false;
            post.is_cover_photo = true;
            return;
        } else if (post.post_type === 'regular') {
            post.is_profile_photo = false;
            post.is_cover_photo = false;
            return;
        }
        
        // Fallback: Check if already marked (for temporary posts)
        if (post.is_profile_photo || post.is_cover_photo || post.is_temporary) {
            return;
        }
        
        // Legacy fallback: Detect based on content text (for old posts before migration)
        // This should rarely be needed after migration
        if (post.content_text) {
            const text = post.content_text.toLowerCase();
            if (text.includes('uploaded a new profile picture') || text.includes("'s profile picture")) {
                post.is_profile_photo = true;
            } else if (text.includes('uploaded a new cover photo') || text.includes("'s cover photo")) {
                post.is_cover_photo = true;
            }
        }
    },
    
    isProfileOrCoverPhoto(post) {
        return !!(post.is_profile_photo || post.is_cover_photo);
    },
    
    isPrivateProfileCoverPhoto(post) {
        return this.isProfileOrCoverPhoto(post) && post.privacy === 'private';
    },
    
    // ============ Date Formatting ============
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

    // ============ Post Likes ============
    async toggleLike(postId) {
        console.debug('toggleLike called for', postId);
        
        const postIndex = this.posts.findIndex(p => p.id === postId);
        if (postIndex === -1) return;
        
        const post = this.posts[postIndex];
        
        // Optimistic update
        const wasLiked = post.is_liked;
        const oldLikeCount = post.like_count || 0;
        
        post.is_liked = !wasLiked;
        post.like_count = wasLiked ? oldLikeCount - 1 : oldLikeCount + 1;
        
        try {
            const response = await fetch(`/likes/toggle-post/${postId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.getCsrfToken()
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('[Like] Server response:', data);

            if (data.success) {
                post.is_liked = data.liked;
                post.like_count = data.likeCount;
                console.log('[Like] Updated - liked:', post.is_liked, 'count:', post.like_count);
            } else {
                post.is_liked = wasLiked;
                post.like_count = oldLikeCount;
            }
        } catch (error) {
            console.error('Error toggling like:', error);
            post.is_liked = wasLiked;
            post.like_count = oldLikeCount;
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
                    console.log('[Refresh] Updating - old:', post.like_count, '/', post.is_liked, 'new:', data.like_count, '/', data.is_liked);
                    post.like_count = data.like_count;
                    post.is_liked = data.is_liked;
                }
            }
        } catch (error) {
            console.error('Failed to refresh likes:', error);
        }
    },

    // ============ Post Comments ============
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
                    if (comment) {
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
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.getCsrfToken()
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
                
                if (post.showComments) {
                    const newComment = {
                        ...data.comment,
                        is_liked: false,
                        like_count: 0
                    };
                    post.comments.push(newComment);
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                }
            } else {
                alert(data.message || 'Failed to post comment. Please try again.');
            }
        } catch (error) {
            console.error('Error submitting comment:', error);
            alert('Failed to post comment. Please try again.');
        }
    },

    // ============ UI Helpers ============
    autoResize(event) {
        const textarea = event.target;
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
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

    async toggleCommentLike(postId, commentId) {
        try {
            const response = await fetch(`/likes/toggle-comment/${commentId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.getCsrfToken()
                }
            });
            
            if (!response.ok) throw new Error('Failed to toggle comment like');
            
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

    async deleteComment(postId, commentId) {
        const confirmed = typeof window.showConfirmModal === 'function'
            ? await window.showConfirmModal('Are you sure you want to delete this comment?')
            : confirm('Delete this comment?');
        if (!confirmed) return;
        
        try {
            const response = await fetch(`/comments/delete/${commentId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.getCsrfToken()
                },
                credentials: 'same-origin'
            });

            if (!response.ok) throw new Error('Failed to delete comment');

            const data = await response.json();
            const post = this.posts.find(p => p.id === postId);
            if (post) {
                const idx = post.comments.findIndex(c => c.id === commentId);
                if (idx !== -1) {
                    post.comments.splice(idx, 1);
                    post.comment_count = Math.max(0, (post.comment_count || 0) - 1);
                }
            }
        } catch (error) {
            console.error('Error deleting comment:', error);
            const errorMsg = 'Failed to delete comment.';
            if (typeof window.showFlash === 'function') {
                window.showFlash(errorMsg, 'error');
            } else {
                alert(errorMsg);
            }
        }
    },

    editComment(postId, commentId) {
        const post = this.posts.find(p => p.id === postId);
        if (!post) return;
        
        const comment = post.comments.find(c => c.id === commentId);
        if (!comment) return;
        
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
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.getCsrfToken()
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    content_text: newContent
                })
            });

            if (!response.ok) throw new Error('Failed to update comment');

            const data = await response.json();
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

    // ============ Image Viewer ============
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

    // ============ Post Detail View ============
    openPostDetailView(post, imageIndex = 0) {
        const p = typeof post === 'object' && post && post.id ? post : this.posts.find(ps => ps.id === post);
        if (!p) return;
        
        // Detect if this is a profile/cover photo post
        this.detectProfileCoverPhoto(p);
        
        // Check if this is a temporary post (used for profile/cover photos without actual posts)
        const isTemporaryPost = typeof p.id === 'string' && p.id.startsWith('temp-');
        
        this.postDetailView.post = p;
        this.postDetailView.imageIndex = Math.max(0, Math.min(imageIndex, (p.post_images && p.post_images.length) ? p.post_images.length - 1 : 0));
        this.postDetailView.isOpen = true;
        
        // Only load comments for real posts, not temporary ones
        if (!isTemporaryPost && !p.showComments && (p.comment_count > 0 || p.post_images?.length)) {
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
        this.postDetailView.isExpanded = false;
        
        const img = p.post_images && p.post_images[this.postDetailView.imageIndex];
        // Only load image comments/likes for real images, not temporary ones
        const isTemporaryImage = img && typeof img.id === 'string' && img.id.startsWith('temp-');
        if (!isTemporaryImage && p.post_images && p.post_images.length >= 2 && img && img.id) {
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

    // ============ Image-Specific Comments ============
    async loadImageComments(postImageId) {
        const imageId = parseInt(postImageId, 10);
        try {
            const response = await fetch(`/comments/get-by-post-image/${imageId}`, { 
                headers: { 'X-Requested-With': 'XMLHttpRequest' } 
            });
            if (!response.ok) return;
            const data = await response.json();
            this.postDetailView.imageComments = (data.comments || data || []).map(c => ({ 
                ...c, is_liked: false, like_count: 0 
            }));
            for (let i = 0; i < this.postDetailView.imageComments.length; i++) {
                const c = this.postDetailView.imageComments[i];
                const likeRes = await fetch(`/likes/comment/${c.id}`, { 
                    headers: { 'X-Requested-With': 'XMLHttpRequest' } 
                });
                if (likeRes.ok) {
                    const likeData = await likeRes.json();
                    this.postDetailView.imageComments[i].like_count = likeData.count || 0;
                    this.postDetailView.imageComments[i].is_liked = likeData.is_liked || false;
                }
            }
        } catch (e) { 
            console.error('Error loading image comments:', e); 
        }
    },

    async loadImageLikeStatus(postImageId) {
        const imageId = parseInt(postImageId, 10);
        try {
            const response = await fetch(`/likes/post-image/${imageId}`, { 
                headers: { 'X-Requested-With': 'XMLHttpRequest' } 
            });
            if (!response.ok) return;
            const data = await response.json();
            this.postDetailView.imageLikeCount = data.count ?? 0;
            this.postDetailView.imageIsLiked = data.is_liked ?? false;
        } catch (e) { 
            console.error('Error loading image like status:', e); 
        }
    },

    async toggleImageLike() {
        if (!this.postDetailView.currentImageId) return;
        
        const imageId = parseInt(this.postDetailView.currentImageId, 10);
        if (isNaN(imageId)) {
            console.error('[Image-Like] Invalid image ID:', this.postDetailView.currentImageId);
            return;
        }
        
        const wasLiked = this.postDetailView.imageIsLiked;
        const oldLikeCount = this.postDetailView.imageLikeCount || 0;
        
        this.postDetailView.imageIsLiked = !wasLiked;
        this.postDetailView.imageLikeCount = wasLiked ? oldLikeCount - 1 : oldLikeCount + 1;
        
        console.log('[Image-Like] Optimistic update - imageId:', imageId, 'liked:', this.postDetailView.imageIsLiked, 'count:', this.postDetailView.imageLikeCount);
        
        try {
            const response = await fetch(`/likes/toggle-post-image/${imageId}`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-Requested-With': 'XMLHttpRequest', 
                    'X-CSRF-Token': window.getCsrfToken() 
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('[Image-Like] Server response:', data);
            
            if (data.success) {
                this.postDetailView.imageLikeCount = data.likeCount;
                this.postDetailView.imageIsLiked = data.liked;
                console.log('[Image-Like] Updated - liked:', this.postDetailView.imageIsLiked, 'count:', this.postDetailView.imageLikeCount);
            } else {
                this.postDetailView.imageIsLiked = wasLiked;
                this.postDetailView.imageLikeCount = oldLikeCount;
            }
        } catch (e) {
            console.error('Error toggling image like:', e);
            this.postDetailView.imageIsLiked = wasLiked;
            this.postDetailView.imageLikeCount = oldLikeCount;
        }
    },

    async submitImageComment() {
        const v = this.postDetailView;
        if (!v.post || !v.currentImageId) return;
        const text = (v.imageNewComment || '').trim();
        if (!text && !v.imageCommentImage) return;
        
        const postId = parseInt(v.post.id, 10);
        const imageId = parseInt(v.currentImageId, 10);
        
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('post_image_id', imageId);
        if (text) formData.append('content_text', text);
        if (v.imageCommentImage) formData.append('content_image', v.imageCommentImage);
        
        console.log('FormData contents:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}:`, value);
        }
        
        try {
            const response = await fetch('/comments/add', { 
                method: 'POST', 
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest', 
                    'X-CSRF-Token': window.getCsrfToken() 
                }, 
                body: formData 
            });
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
        } catch (e) { 
            console.error('Error submitting image comment:', e); 
        }
    },

    postDetailPrevImage() {
        if (!this.postDetailView.post || !this.postDetailView.post.post_images?.length) return;
        const imgs = this.postDetailView.post.post_images;
        const totalImages = imgs.length;
        console.log('postDetailPrevImage - BEFORE: imageIndex=', this.postDetailView.imageIndex, 'totalImages=', totalImages);
        
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

    // ============ Image Comment Edit/Delete ============
    editImageComment(commentId) {
        const comment = this.postDetailView.imageComments.find(c => c.id === commentId);
        if (!comment) return;
        
        comment.isEditing = true;
        comment.editContent = comment.content_text || '';
        this.$forceUpdate();
    },

    async saveImageCommentEdit(commentId) {
        const comment = this.postDetailView.imageComments.find(c => c.id === commentId);
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
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.getCsrfToken()
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    content_text: newContent
                })
            });

            if (!response.ok) throw new Error('Failed to update comment');

            const data = await response.json();
            comment.content_text = newContent;
            comment.isEditing = false;
            delete comment.editContent;
            
            this.$forceUpdate();
        } catch (error) {
            console.error('Error updating image comment:', error);
            alert('Failed to update comment.');
        }
    },

    cancelImageCommentEdit(commentId) {
        const comment = this.postDetailView.imageComments.find(c => c.id === commentId);
        if (!comment) return;
        
        comment.isEditing = false;
        delete comment.editContent;
        this.$forceUpdate();
    },

    async deleteImageComment(commentId) {
        const confirmed = typeof window.showConfirmModal === 'function'
            ? await window.showConfirmModal('Are you sure you want to delete this comment?')
            : confirm('Delete this comment?');
        if (!confirmed) return;
        
        try {
            const response = await fetch(`/comments/delete/${commentId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.getCsrfToken()
                },
                credentials: 'same-origin'
            });

            if (!response.ok) throw new Error('Failed to delete comment');

            const data = await response.json();
            const idx = this.postDetailView.imageComments.findIndex(c => c.id === commentId);
            if (idx !== -1) {
                this.postDetailView.imageComments.splice(idx, 1);
            }
        } catch (error) {
            console.error('Error deleting image comment:', error);
            const errorMsg = 'Failed to delete comment.';
            if (typeof window.showFlash === 'function') {
                window.showFlash(errorMsg, 'error');
            } else {
                alert(errorMsg);
            }
        }
    },

    async toggleImageCommentLike(commentId) {
        try {
            const response = await fetch(`/likes/toggle-comment/${commentId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.getCsrfToken()
                }
            });
            
            if (!response.ok) throw new Error('Failed to toggle comment like');
            
            const data = await response.json();
            if (data.success) {
                const comment = this.postDetailView.imageComments.find(c => c.id === commentId);
                if (comment) {
                    comment.is_liked = data.liked;
                    comment.like_count = data.likeCount;
                }
            }
        } catch (error) {
            console.error('Error toggling image comment like:', error);
        }
    },

    // ============ WebSocket Handlers ============
    initWebSocket() {
        console.log('[App] Initializing WebSocket...');
        this.wsManager = new WebSocketManager(this.currentUserId);
        
        this.wsManager.addMessageHandler((data) => {
            this.handleWebSocketMessage(data);
        });
        
        this.wsManager.connect();
    },

    handleWebSocketMessage(data) {
        console.log('[App] WebSocket message:', data);
        
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
                
            case 'notification':
                if (typeof window.showFlash === 'function') {
                    window.showFlash(data.message, 'info');
                }
                break;
        }
    },

    handleLikeAdded(data) {
        console.log('[WS] handleLikeAdded FIRED');
        console.log('[WS]   data.user_id:', data.user_id, 'type:', typeof data.user_id);
        console.log('[WS]   this.currentUserId:', this.currentUserId, 'type:', typeof this.currentUserId);
        
        // Handle post image likes
        if (data.target_type === 'PostImage' && data.post_image_id) {
            if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
                console.log('[WS] ✓ Skipping image like refresh - current user action');
                return;
            }
            
            if (this.postDetailView.isOpen && this.postDetailView.currentImageId === data.post_image_id) {
                console.log('[WS] Refreshing image like for image:', data.post_image_id);
                this.loadImageLikeStatus(data.post_image_id);
            }
            return;
        }
        
        // Handle regular post likes
        if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
            console.log('[WS] ✓ Skipping refresh - current user action');
            return;
        }
        
        console.log('[WS] × Will refresh - different user');
        const post = this.posts.find(p => p.id === data.target_id);
        if (post && data.target_type === 'Post') {
            console.log('[WS] Refreshing post likes for post:', data.target_id);
            this.refreshPostLikes(post.id);
        }
    },

    handleLikeRemoved(data) {
        console.log('[WS] handleLikeRemoved FIRED');
        console.log('[WS]   data.user_id:', data.user_id, 'type:', typeof data.user_id);
        console.log('[WS]   this.currentUserId:', this.currentUserId, 'type:', typeof this.currentUserId);
        
        // Handle post image unlikes
        if (data.target_type === 'PostImage' && data.post_image_id) {
            if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
                console.log('[WS] ✓ Skipping image unlike refresh - current user action');
                return;
            }
            
            if (this.postDetailView.isOpen && this.postDetailView.currentImageId === data.post_image_id) {
                console.log('[WS] Refreshing image unlike for image:', data.post_image_id);
                this.loadImageLikeStatus(data.post_image_id);
            }
            return;
        }
        
        // Handle regular post unlikes
        if (data.user_id && this.currentUserId && Number(data.user_id) === Number(this.currentUserId)) {
            console.log('[WS] ✓ Skipping refresh - current user action');
            return;
        }
        
        console.log('[WS] × Will refresh - different user');
        const post = this.posts.find(p => p.id === data.target_id);
        if (post && data.target_type === 'Post') {
            console.log('[WS] Refreshing post likes for post:', data.target_id);
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
                if (post.showComments) {
                    this.loadComments(post.id);
                } else {
                    post.comment_count = (post.comment_count || 0) + 1;
                }
            }
        }
    }
};
