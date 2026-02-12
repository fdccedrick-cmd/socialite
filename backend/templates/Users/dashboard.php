<div id="dashboardApp" class="space-y-6" v-cloak>
  <!-- Create Post Card -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center gap-3">
      <img 
        :src="user.avatar" 
        :alt="user.username" 
        class="w-12 h-12 rounded-full object-cover border border-gray-200"
      />
      <input 
        type="text" 
        placeholder="Write a comment..." 
        class="flex-1 px-4 py-3 bg-gray-50 rounded-full text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
      <button class="p-3 text-blue-600 hover:bg-blue-50 rounded-full transition-colors">
        <i data-lucide="send" class="w-5 h-5"></i>
      </button>
    </div>
  </div>
  
  <!-- Feed Posts -->
  <div class="space-y-6">
    <!-- Post 1 - Sarah Chen -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <!-- Post Header -->
      <div class="p-6 pb-4">
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
      </div>
      
      <!-- Post Image -->
      <div class="w-full">
        <img 
          src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&h=500&fit=crop" 
          alt="Mountain view" 
          class="w-full h-auto object-cover"
        />
      </div>
      
      <!-- Post Actions -->
      <div class="p-6 pt-4">
        <div class="flex items-center gap-6 mb-4">
          <button class="flex items-center gap-2 text-gray-600 hover:text-red-500 transition-colors">
            <i data-lucide="heart" class="w-5 h-5"></i>
            <span class="text-sm font-medium">24</span>
          </button>
          <button class="flex items-center gap-2 text-gray-600 hover:text-blue-500 transition-colors">
            <i data-lucide="message-circle" class="w-5 h-5"></i>
            <span class="text-sm font-medium">1</span>
          </button>
        </div>
        
        <!-- Comment Input -->
        <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
          <img 
            :src="user.avatar" 
            :alt="user.username" 
            class="w-10 h-10 rounded-full object-cover border border-gray-200"
          />
          <input 
            type="text" 
            placeholder="Write a comment..." 
            class="flex-1 px-4 py-2 bg-gray-50 rounded-full text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <button class="p-2 text-blue-600 hover:bg-blue-50 rounded-full transition-colors">
            <i data-lucide="send" class="w-4 h-4"></i>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Post 2 - James Liu -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <!-- Post Header -->
      <div class="p-6 pb-4">
        <div class="flex items-center gap-3 mb-4">
          <img 
            src="https://i.pravatar.cc/150?img=12" 
            alt="James Liu" 
            class="w-12 h-12 rounded-full object-cover border border-gray-200"
          />
          <div class="flex-1">
            <h3 class="font-semibold text-gray-900">James Liu</h3>
            <p class="text-sm text-gray-500">5h ago</p>
          </div>
        </div>
        
        <!-- Post Content -->
        <p class="text-gray-800">
          Great coffee and productivity today! ☕️💻 Working on some exciting new projects.
        </p>
      </div>
      
      <!-- Post Actions -->
      <div class="p-6 pt-4">
        <div class="flex items-center gap-6 mb-4">
          <button class="flex items-center gap-2 text-gray-600 hover:text-red-500 transition-colors">
            <i data-lucide="heart" class="w-5 h-5"></i>
            <span class="text-sm font-medium">42</span>
          </button>
          <button class="flex items-center gap-2 text-gray-600 hover:text-blue-500 transition-colors">
            <i data-lucide="message-circle" class="w-5 h-5"></i>
            <span class="text-sm font-medium">3</span>
          </button>
        </div>
        
        <!-- Comment Input -->
        <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
          <img 
            :src="user.avatar" 
            :alt="user.username" 
            class="w-10 h-10 rounded-full object-cover border border-gray-200"
          />
          <input 
            type="text" 
            placeholder="Write a comment..." 
            class="flex-1 px-4 py-2 bg-gray-50 rounded-full text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <button class="p-2 text-blue-600 hover:bg-blue-50 rounded-full transition-colors">
            <i data-lucide="send" class="w-4 h-4"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            user: {
                username: <?= json_encode($user['username'] ?? 'user') ?>,
                avatar: <?= json_encode($user['profile_photo_path'] ?? 'https://i.pravatar.cc/150?img=1') ?>
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
