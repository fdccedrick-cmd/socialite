<div id="dashboardApp" class="space-y-3 sm:space-y-4" v-cloak>
  <!-- Create Post -->
  <?= $this->element('posts/post_create', ['currentUser' => $user ?? []]) ?>
  
  <!-- Feed Posts -->
  <?= $this->element('posts/post_list', [
    'posts' => $postsArray ?? [],
    'currentUser' => $user ?? [],
    'emptyMessage' => 'No posts yet. Be the first to share something!'
  ]) ?>
</div>

<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            user: {
                username: <?= json_encode($user['username'] ?? 'user') ?>,
                avatar: <?= json_encode($user['profile_photo_path'] ?? 'https://i.pravatar.cc/150?img=1') ?>
            },
            posts: <?= json_encode($postsArray ?? []) ?>,
            newPost: {
                content: '',
                images: [],
                imagePreview: [],
                privacy: 'public',
                isSubmitting: false,
                error: ''
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
        autoResize(event) {
            const textarea = event.target;
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
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
            
            // Validate at least content or images
            if (!this.newPost.content.trim() && this.newPost.images.length === 0) {
                this.newPost.error = 'Please add some text or images to your post';
                this.newPost.isSubmitting = false;
                return;
            }
            
            // Create FormData for file upload
            const formData = new FormData();
            formData.append('content_text', this.newPost.content);
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
        }
    },
    mounted() {
        // Initialize Lucide icons
        if (window.lucide) {
            lucide.createIcons();
        }
    },
    updated() {
        // Re-initialize Lucide icons after DOM updates
        if (window.lucide) {
            lucide.createIcons();
        }
    }
}).mount('#dashboardApp');
</script>
