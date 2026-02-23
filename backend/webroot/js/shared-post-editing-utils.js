/**
 * Shared Post Editing Utilities
 * Common post editing methods used across Dashboard and Profile
 */

window.SharedPostEditingUtils = {
    // ============ Post Menu & Editing ============
    togglePostMenu(postId, event) {
        if (event) event.stopPropagation();
        const post = this.posts.find(p => p.id === postId);
        if (post) {
            this.posts.forEach(p => {
                if (p.id !== postId) p.showMenu = false;
            });
            post.showMenu = !post.showMenu;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
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
        this.$nextTick(() => {
            if (window.lucide) lucide.createIcons();
        });
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
    
    triggerEditImageInput(postId) {
        const input = document.getElementById('edit-images-' + postId);
        if (input) {
            input.click();
        }
    },
    
    handleEditImageSelect(event, postId) {
        if (!event || !event.target || !event.target.files) {
            console.error('Invalid event passed to handleEditImageSelect');
            return;
        }
        
        const post = this.posts.find(p => p.id === postId);
        if (!post) return;
        
        const files = Array.from(event.target.files);
        const totalImages = post.editImages.length + post.newEditImages.length + files.length;
        
        if (totalImages > 10) {
            alert('You can only have up to 10 images per post.');
            event.target.value = '';
            return;
        }
        
        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        
        files.forEach(file => {
            if (validTypes.includes(file.type)) {
                post.newEditImages.push(file);
                const reader = new FileReader();
                reader.onload = (e) => {
                    post.newEditImagePreviews.push(e.target.result);
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Clear the input
        if (event.target) {
            event.target.value = '';
        }
        
        // Re-render icons if available
        if (this.$nextTick && typeof this.$nextTick === 'function') {
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        } else if (window.lucide) {
            // Fallback if $nextTick is not available
            setTimeout(() => lucide.createIcons(), 0);
        }
    },
    
    async saveEditPost(postId) {
        const post = this.posts.find(p => p.id === postId);
        if (!post) return;
        const hasContent = post.editContent && post.editContent.trim();
        const hasImages = (post.editImages.length + post.newEditImages.length) > 0;
        if (!hasContent && !hasImages) {
            alert('Post must have content or images.');
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
                headers: {
                    'X-CSRF-Token': window.getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            if (!response.ok) throw new Error('Failed to update post');
            const data = await response.json();
            post.content_text = data.post.content_text;
            post.privacy = data.post.privacy;
            post.post_images = data.post.post_images || [];
            post.isEditing = false;
            post.editContent = '';
            post.editImages = [];
            post.removedImageIds = [];
            post.newEditImages = [];
            post.newEditImagePreviews = [];
            if (typeof window.showFlash === 'function') {
                window.showFlash('Post updated successfully!', 'success');
            }
        } catch (err) {
            console.error('Error updating post:', err);
            alert('Failed to update post.');
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
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) throw new Error('Failed to delete post');
            const idx = this.posts.findIndex(p => p.id === postId);
            if (idx !== -1) this.posts.splice(idx, 1);
            if (typeof window.showFlash === 'function') {
                window.showFlash('Post deleted successfully!', 'success');
            }
        } catch (err) {
            console.error('Error deleting post:', err);
            alert('Failed to delete post.');
        }
    },
    
    handleClickOutside(event) {
        const target = event.target;
        if (!target.closest('[data-post-menu]') && !target.closest('button[data-menu-trigger]')) {
            this.posts.forEach(post => { if (post.showMenu) post.showMenu = false; });
        }
    }
};
