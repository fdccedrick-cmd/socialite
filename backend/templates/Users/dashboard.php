<div id="dashboardApp" class="space-y-3 sm:space-y-4" v-cloak>
  <!-- Create Post -->
  <?= $this->element('posts/post_create', ['currentUser' => $user ?? []]) ?>
  
  <!-- Feed Posts -->
  <?= $this->element('posts/post_list', [
    'posts' => $postsArray ?? [],
    'currentUser' => $user ?? [],
    'emptyMessage' => 'No posts yet. Be the first to share something!'
  ]) ?>
  
  <!-- Image Viewer Modal -->
  <transition name="fade">
    <div 
      v-if="imageViewer.isOpen"
      @click="closeImageViewer"
      class="fixed inset-0 bg-black bg-opacity-95 z-[9999] flex items-center justify-center"
    >
      <!-- Close Button -->
      <button 
        @click="closeImageViewer"
        class="absolute top-4 right-4 text-white hover:text-gray-300 transition-colors z-10"
        title="Close (Esc)"
      >
        <i data-lucide="x" class="w-8 h-8"></i>
      </button>
      
      <!-- Image Counter -->
      <div class="absolute top-4 left-1/2 transform -translate-x-1/2 text-white text-sm font-medium bg-black bg-opacity-50 px-3 py-1.5 rounded-full">
        {{ imageViewer.currentIndex + 1 }} / {{ imageViewer.images.length }}
      </div>
      
      <!-- Previous Button -->
      <button 
        v-if="imageViewer.currentIndex > 0"
        @click.stop="prevImage"
        class="absolute left-4 text-white hover:text-gray-300 transition-colors bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full p-3"
        title="Previous (←)"
      >
        <i data-lucide="chevron-left" class="w-6 h-6"></i>
      </button>
      
      <!-- Image Container -->
      <div 
        @click.stop
        class="max-w-7xl max-h-screen w-full h-full flex items-center justify-center p-4"
      >
        <img 
          :src="imageViewer.images[imageViewer.currentIndex]?.image_path || imageViewer.images[imageViewer.currentIndex]"
          :alt="'Image ' + (imageViewer.currentIndex + 1)"
          class="max-w-full max-h-full object-contain"
        />
      </div>
      
      <!-- Next Button -->
      <button 
        v-if="imageViewer.currentIndex < imageViewer.images.length - 1"
        @click.stop="nextImage"
        class="absolute right-4 text-white hover:text-gray-300 transition-colors bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full p-3"
        title="Next (→)"
      >
        <i data-lucide="chevron-right" class="w-6 h-6"></i>
      </button>
    </div>
  </transition>
</div>

<!-- Emoji Picker (Outside Vue App) -->
<div 
  id="emojiPickerContainer"
  class="fixed z-50 mt-2"
  style="left: 50%; transform: translateX(-50%); visibility: hidden; opacity: 0; pointer-events: none;"
>
  <div class="shadow-2xl rounded-lg overflow-hidden border border-gray-200 bg-white">
    <emoji-picker class="light"></emoji-picker>
  </div>
</div>

<script>
const { createApp } = Vue;

const app = createApp({
    data() {
        return {
            user: {
                username: <?= json_encode($user['username'] ?? 'user') ?>,
                avatar: <?= json_encode($user['profile_photo_path'] ?? 'https://i.pravatar.cc/150?img=1') ?>
            },
            posts: <?= json_encode($postsArray ?? []) ?>.map(post => ({
                ...post,
                showComments: false,
                newComment: '',
                commentImage: null,
                commentImagePreview: null,
                comments: []
            })),
            newPost: {
                content: '',
                images: [],
                imagePreview: [],
                privacy: 'public',
                isSubmitting: false,
                error: '',
                showEmojiPicker: false
            },
            imageViewer: {
                isOpen: false,
                images: [],
                currentIndex: 0
            }
        }
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
        async toggleLike(postId) {
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
                    // Find the index of the post
                    const postIndex = this.posts.findIndex(p => p.id === postId);
                    if (postIndex !== -1) {
                        // Use Vue's splice to ensure reactivity
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
        openImageViewer(images, index = 0) {
            this.imageViewer.images = images;
            this.imageViewer.currentIndex = index;
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
            const files = Array.from(event.target.files);
            this.newPost.error = '';
            
            // Validate file count
            if (this.newPost.images.length + files.length > 10) {
                this.newPost.error = 'You can upload a maximum of 10 images per post';
                event.target.value = '';
                return;
            }
            
            // Validate each file
            for (const file of files) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    this.newPost.error = 'Please upload valid image files (JPG, PNG, or GIF)';
                    event.target.value = '';
                    return;
                }
                
                // Validate file size (10MB max)
                const maxSize = 10 * 1024 * 1024;
                if (file.size > maxSize) {
                    this.newPost.error = 'Each image must be less than 10MB';
                    event.target.value = '';
                    return;
                }
                
                // Add to images array
                this.newPost.images.push(file);
                
                // Create preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.newPost.imagePreview.push(e.target.result);
                };
                reader.readAsDataURL(file);
            }
            
            event.target.value = '';
        },
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
        }
    },
    mounted() {
        // Initialize Lucide icons
        if (window.lucide) {
            lucide.createIcons();
        }
        
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

app.mount('#dashboardApp');
</script>
