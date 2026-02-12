<div id="profileApp" class="space-y-6" v-cloak>
  <!-- Profile Header Card -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6">
    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
      <!-- Profile Photo -->
      <div class="shrink-0">
        <img 
          :src="user.avatar" 
          :alt="user.full_name" 
          class="w-32 h-32 rounded-full object-cover border-4 border-gray-100"
        />
      </div>
      
      <!-- Profile Info -->
      <div class="flex-1">
        <div class="mb-3">
          <h1 class="text-3xl font-bold text-gray-900">{{ user.full_name }}</h1>
          <p class="text-gray-500 text-base mt-1">@{{ user.username }}</p>
          <p class="text-gray-400 text-sm mt-1">{{ user.joinedDate }}</p>
        </div>
        
        <div class="mb-4">
          <p class="text-gray-700 text-base">{{ user.bio }}</p>
        </div>
        
        <!-- Stats -->
        <div class="flex gap-8 mb-4">
          <div>
            <span class="font-bold text-gray-900 text-lg">{{ user.stats.posts }}</span>
            <span class="text-gray-500 text-sm ml-1">Posts</span>
          </div>
          <div>
            <span class="font-bold text-gray-900 text-lg">{{ user.stats.friends }}</span>
            <span class="text-gray-500 text-sm ml-1">Friends</span>
          </div>
          <div>
            <span class="font-bold text-gray-900 text-lg">{{ user.stats.likes }}</span>
            <span class="text-gray-500 text-sm ml-1">Likes</span>
          </div>
        </div>
      </div>
      
      <!-- Edit Profile Button -->
      <div class="shrink-0 md:self-start">
        <button class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
          <i data-lucide="settings" class="w-4 h-4 text-gray-700"></i>
          <span class="text-sm font-medium text-gray-700">Edit Profile</span>
        </button>
      </div>
    </div>
  </div>
  
  <!-- Tabs -->
  <div class="bg-white rounded-t-2xl shadow-sm border border-gray-100 border-b-0">
    <div class="flex border-b border-gray-200">
      <button 
        @click="activeTab = 'posts'" 
        :class="{'border-b-2 border-gray-900 text-gray-900': activeTab === 'posts', 'text-gray-500': activeTab !== 'posts'}"
        class="flex items-center gap-2 px-6 py-4 font-medium text-sm transition-colors"
      >
        <i data-lucide="layout-grid" class="w-4 h-4"></i>
        <span>Posts</span>
      </button>
      <button 
        @click="activeTab = 'saved'" 
        :class="{'border-b-2 border-gray-900 text-gray-900': activeTab === 'saved', 'text-gray-500': activeTab !== 'saved'}"
        class="flex items-center gap-2 px-6 py-4 font-medium text-sm transition-colors"
      >
        <i data-lucide="bookmark" class="w-4 h-4"></i>
        <span>Saved</span>
      </button>
    </div>
  </div>
  
  <!-- Tab Content -->
  <div class="bg-white rounded-b-2xl shadow-sm border border-gray-100">
    <!-- Posts Tab -->
    <div v-if="activeTab === 'posts'" class="p-6">
      <!-- Sample Post -->
      <div class="bg-white rounded-xl border border-gray-100 p-6 mb-4">
        <!-- Post Header -->
        <div class="flex items-center gap-3 mb-4">
          <img 
            src="https://i.pravatar.cc/150?img=5" 
            alt="Sarah Chen" 
            class="w-12 h-12 rounded-full object-cover border border-gray-200"
          />
          <div class="flex-1">
            <h3 class="font-semibold text-gray-900">Sarah Chen</h3>
            <p class="text-sm text-gray-500">2h ago</p>
          </div>
        </div>
        
        <!-- Post Content -->
        <p class="text-gray-800 mb-4">
          Just finished a morning hike ⛰️ The view from the top was absolutely breathtaking!
        </p>
        
        <!-- Post Image -->
        <div class="rounded-lg overflow-hidden mb-4">
          <img 
            src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&h=500&fit=crop" 
            alt="Mountain view" 
            class="w-full h-auto object-cover"
          />
        </div>
      </div>
      
      <!-- Empty State (when no posts) -->
      <div v-if="false" class="text-center py-16">
        <i data-lucide="image" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
        <p class="text-gray-500 text-lg">No posts yet</p>
        <p class="text-gray-400 text-sm mt-2">Share your first post to get started</p>
      </div>
    </div>
    
    <!-- Saved Tab -->
    <div v-if="activeTab === 'saved'" class="p-6">
      <div class="text-center py-16">
        <i data-lucide="bookmark" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
        <p class="text-gray-500 text-lg">No saved posts</p>
        <p class="text-gray-400 text-sm mt-2">Save posts to view them later</p>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    const el = document.getElementById('profileApp');
    if (!el) return;
    
    const app = Vue.createApp({
      data() {
        return {
          activeTab: 'posts',
          user: {
            full_name: <?= json_encode($user['full_name'] ?? $user['username'] ?? 'User') ?>,
            username: <?= json_encode($user['username'] ?? 'user') ?>,
            avatar: <?= json_encode($user['profile_photo_path'] ?? 'https://i.pravatar.cc/150?img=1') ?>,
            joinedDate: <?= json_encode(isset($user['created']) ? 'Joined ' . date('M Y', strtotime($user['created'])) : 'Joined recently') ?>,
            bio: '🌍 Explorer · 📷 Photography enthusiast · ☕ Coffee lover',
            stats: {
              posts: '12',
              friends: '482',
              likes: '1.2K'
            }
          }
        };
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
    });
    
    app.mount(el);
  })();
</script>
