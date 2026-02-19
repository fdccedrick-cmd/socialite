<?php
/** @var \App\View\AppView $this */
$user = $currentUser ?? $user ?? null;
if (is_array($user)) {
    $user = (object)$user;
}
$username = $user->full_name ?? $user->username ?? 'Guest';
$avatar = $user->profile_photo_path ?? 'https://i.pravatar.cc/150?img=1';
?>
<style>
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.animate-spin {
  animation: spin 1s linear infinite;
}
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
<div id="headerApp" class="fixed top-0 left-0 w-full bg-white dark:bg-gray-800 border-b dark:border-gray-700 z-50 shadow-sm" v-cloak>
  <div class="max-w-[1920px] mx-auto px-2 sm:px-4 lg:px-6 xl:px-8">
    <div class="flex items-center justify-between h-14 sm:h-16">
      <!-- Left: Mobile Menu + Logo + Search -->
       <div class="flex items-center flex-1 gap-2 sm:gap-4">
  <!-- Mobile Menu Button -->
  <button @click="toggleMobileMenu" class="lg:hidden p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Menu">
    <i data-lucide="menu" class="h-5 w-5 text-gray-700 dark:text-gray-200"></i>
  </button>
  
  <!-- Logo -->
  <a href="/dashboard" class="flex items-center gap-1.5 sm:gap-2 shrink-0">
    <svg class="h-6 w-6 sm:h-8 sm:w-8 text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="none">
      <path d="M21 12c0 4.97-4.03 9-9 9-1.5 0-2.92-.36-4.18-1L3 21l1.03-4.82C3.36 14.92 3 13.5 3 12 3 7.03 7.03 3 12 3s9 4.03 9 9z" stroke="currentColor" stroke-width="1.5"/>
    </svg>
    <span class="font-bold text-base sm:text-xl lg:text-2xl text-gray-800 dark:text-white">Socialite</span>
  </a>

  <!-- Search -->
  <div class="flex-1 hidden md:flex max-w-md lg:max-w-lg relative">
    <div class="relative w-full">
      <i data-lucide="search" class="w-4 h-4 lg:w-5 lg:h-5 text-gray-400 dark:text-gray-500 absolute left-3 lg:left-4 top-1/2 -translate-y-1/2 pointer-events-none z-10"></i>
      <input 
        v-model="searchQuery"
        @input="onSearchInput"
        @focus="showSearchResults = true"
        @keydown.enter.prevent="goToFullSearch"
        @keydown.esc="closeSearch"
        type="search"
        placeholder="Search people, posts..."
        class="pl-10 lg:pl-12 pr-4 py-1.5 lg:py-2 rounded-full border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-sm text-gray-700 dark:text-white shadow-sm w-full focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-gray-600"
      />
      
      <!-- Search Results Dropdown -->
      <div 
        v-if="showSearchResults && searchQuery.length > 0"
        v-click-outside="closeSearch"
        class="absolute top-full mt-2 w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 max-h-[80vh] overflow-y-auto z-50"
      >
        <!-- Loading -->
        <div v-if="searchLoading" class="p-4 text-center text-gray-500 dark:text-gray-400">
          <i data-lucide="loader-2" class="w-5 h-5 animate-spin inline-block"></i>
          <span class="ml-2">Searching...</span>
        </div>
        
        <!-- Results -->
        <div v-else-if="searchResults.users.length > 0 || searchResults.posts.length > 0">
          <!-- Users Section -->
          <div v-if="searchResults.users.length > 0" class="border-b border-gray-100">
            <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">People</div>
            <a 
              v-for="user in searchResults.users"
              :key="'user-' + user.id"
              :href="'/profile/' + user.username"
              class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <img 
                :src="user.profile_photo_path || 'https://i.pravatar.cc/150?img=1'" 
                :alt="user.full_name"
                class="w-10 h-10 rounded-full object-cover border"
              />
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="font-semibold text-gray-900 dark:text-white text-sm truncate">{{ user.full_name }}</span>
                  <span v-if="user.is_friend" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-indigo-100 dark:bg-indigo-800 text-indigo-700 dark:text-indigo-300 shrink-0">
                    <i data-lucide="check" class="w-3 h-3"></i>
                  </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">@{{ user.username }}</p>
              </div>
            </a>
          </div>
          
          <!-- Posts Section -->
          <div v-if="searchResults.posts.length > 0">
            <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Posts</div>
            <a 
              v-for="post in searchResults.posts"
              :key="'post-' + post.id"
              :href="'/posts/' + post.id"
              class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <img 
                :src="post.user.profile_photo_path || 'https://i.pravatar.cc/150?img=1'" 
                :alt="post.user.full_name"
                class="w-8 h-8 rounded-full object-cover"
              />
              <div class="flex-1 min-w-0">
                <p class="font-medium text-sm text-gray-900 dark:text-white">{{ post.user.full_name }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2 mt-0.5">{{ post.content }}</p>
              </div>
            </a>
          </div>
          
          <!-- See All Results Link -->
          <a 
            :href="'/search?q=' + encodeURIComponent(searchQuery)"
            class="block px-4 py-3 text-center text-sm font-medium text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-800 border-t border-gray-100 dark:border-gray-700"
          >
            See all results for "{{ searchQuery }}"
          </a>
        </div>
        
        <!-- No Results -->
        <div v-else class="p-6 text-center text-gray-500 dark:text-gray-400">
          <i data-lucide="search" class="w-8 h-8 mx-auto mb-2 text-gray-400 dark:text-gray-500"></i>
          <p class="text-sm">No results found for "{{ searchQuery }}"</p>
        </div>
      </div>
    </div>
  </div>
        <button @click="focusSearch" class="md:hidden p-1.5 rounded hover:bg-gray-100" title="Search">
          <i data-lucide="search" class="h-4 w-4 sm:h-5 sm:w-5 text-gray-700"></i>
        </button>
      </div>

      <!-- Center: Nav icons -->
      

      <!-- Right: actions -->
      <div class="flex items-center gap-1 sm:gap-2 lg:gap-4">
        <div class="hidden lg:flex items-center gap-2">
        <a href="/dashboard" class="px-3 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300" title="Home">
          <i data-lucide="home" class="h-6 w-6"></i>
        </a>
        <a href="/explore" class="px-3 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300" title="Explore">
          <i data-lucide="compass" class="h-6 w-6"></i>
        </a>
        <button @click="focusPostCreate" class="px-3 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300" title="Create">
          <i data-lucide="plus" class="h-6 w-6"></i>
        </button>
      </div>
        <!-- Notifications -->
        <div class="relative">
          <button type="button" @click.stop="toggleNotifications" class="relative p-1.5 sm:p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Notifications">
            <i data-lucide="bell" class="h-5 w-5 sm:h-6 sm:w-6 text-gray-700 dark:text-gray-300"></i>
            <span v-if="notificationCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] sm:text-xs rounded-full px-1 sm:px-1.5 min-w-[16px] sm:min-w-[20px] text-center">{{ notificationCount }}</span>
          </button>

          <?= $this->element('/notif/notification_dropdown') ?>
        </div>

        <!-- Messages -->
        <!-- <a href="/messages" class="relative p-2 rounded hover:bg-gray-100" title="Messages">
          <svg class="h-6 w-6 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2z"/></svg>
          <span v-if="messageCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-1.5">{{ messageCount }}</span>
        </a> -->

        <!-- Avatar / username / dropdown -->
        <div class="relative">
            <button @click="toggle" class="flex items-center gap-1.5 sm:gap-3 p-0.5 sm:p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none" aria-label="User menu">
            <img :src="avatar" :alt="username" class="h-7 w-7 sm:h-9 sm:w-9 rounded-full object-cover border"/>
            <div class="hidden md:flex flex-col leading-tight">
              <span class="text-sm font-medium text-gray-800 dark:text-gray-200"><?= h($user ? ($user->full_name ?? $user->username) : 'Guest') ?></span>
              <span class="text-xs text-gray-500 dark:text-gray-400">@<?= h($user->username ?? '') ?></span>
            </div>
            <i data-lucide="chevron-down" class="h-4 w-4 text-gray-500 dark:text-gray-400 hidden md:inline"></i>
          </button>

          <div v-if="open" class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-50">
            <div class="py-2 text-sm text-gray-700 dark:text-gray-200">
              <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="font-medium"><?= h($username) ?></div>
                <div class="text-xs text-gray-500 dark:text-gray-400">View profile and settings</div>
              </div>
              <a href="/profile" class="block px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700">Profile</a>
              <a href="/settings" class="block px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700">Settings</a>
              <a href="/saved" class="block px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700">Saved</a>
              <?= $this->Form->create(null, [
                'type' => 'post',
                'url' => '/logout',
                'class' => 'm-0'
              ]) ?>
                <?= $this->Form->button('Logout', [
                  'type' => 'submit',
                  'class' => 'w-full text-left px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200'
                ]) ?>
              <?= $this->Form->end() ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    if (typeof Vue === 'undefined') return;
    const el = document.getElementById('headerApp');
    if (!el) return;
    
    function getCsrfToken() {
      const meta = document.querySelector('meta[name="csrf-token"]');
      return meta ? meta.getAttribute('content') : '';
    }
    
    const app = Vue.createApp({
      data() {
        return {
          open: false,
          mobileMenuOpen: false,
          notificationsOpen: false,
          messagesOpen: false,
          username: <?= json_encode($username) ?>,
          avatar: <?= json_encode($avatar) ?>,
          notificationCount: 0,
          messageCount: 0,
          notifications: [],
          searchQuery: '',
          searchResults: { users: [], posts: [] },
          showSearchResults: false,
          searchLoading: false,
          searchTimeout: null
        };
      },
      methods: {
        toggle() { 
          this.open = !this.open;
          this.notificationsOpen = false;
          this.messagesOpen = false;
        },
        close() { this.open = false; },
        toggleMobileMenu() { this.mobileMenuOpen = !this.mobileMenuOpen; },
        closeMobileMenu() { this.mobileMenuOpen = false; },
        toggleNotifications() {
          this.notificationsOpen = !this.notificationsOpen;
          this.open = false;
          this.messagesOpen = false;
        },
        toggleMessages() {
          this.messagesOpen = !this.messagesOpen;
          this.open = false;
          this.notificationsOpen = false;
        },
        focusSearch() {
          const s = document.querySelector('input[type="search"]');
          if (s) { 
            s.focus();
            this.showSearchResults = true;
          }
        },
        onSearchInput() {
          // Clear previous timeout
          if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
          }
          
          // If query is empty, clear results
          if (this.searchQuery.trim().length === 0) {
            this.searchResults = { users: [], posts: [] };
            this.showSearchResults = false;
            return;
          }
          
          // Debounce search
          this.searchLoading = true;
          this.searchTimeout = setTimeout(() => {
            this.performSearch();
          }, 300);
        },
        async performSearch() {
          if (!this.searchQuery || this.searchQuery.trim().length === 0) {
            this.searchLoading = false;
            return;
          }
          
          try {
            const response = await fetch(`/search/quick?q=${encodeURIComponent(this.searchQuery)}`, {
              credentials: 'same-origin'
            });
            
            if (response.ok) {
              const data = await response.json();
              this.searchResults = {
                users: data.users || [],
                posts: data.posts || []
              };
              this.showSearchResults = true;
            }
          } catch (error) {
            console.error('Search error:', error);
          } finally {
            this.searchLoading = false;
            // Re-initialize lucide icons for new elements
            this.$nextTick(() => {
              if (window.lucide) lucide.createIcons();
            });
          }
        },
        closeSearch() {
          this.showSearchResults = false;
        },
        goToFullSearch() {
          if (this.searchQuery.trim()) {
            window.location.href = `/search?q=${encodeURIComponent(this.searchQuery)}`;
          }
        },
        focusPostCreate() {
          // Try to find the post creation textarea
          const postField = document.getElementById('post_create') || 
                           document.querySelector('textarea[placeholder*="What\'s on your mind"]') ||
                           document.querySelector('textarea[name="content_text"]') ||
                           document.querySelector('#postContent');
          
          if (postField) {
            postField.focus();
            postField.scrollIntoView({ behavior: 'smooth', block: 'center' });
          } else {
            // If not on dashboard, navigate to dashboard
            window.location.href = '/dashboard';
          }
        },
        async fetchNotifications() {
          try {
            const response = await fetch('/api/notifications/recent', {
              credentials: 'same-origin'
            });
            if (response.ok) {
              const data = await response.json();
              this.notifications = data.notifications || [];
              this.notificationCount = data.unreadCount || 0;
            }
          } catch (error) {
            console.error('Failed to fetch notifications:', error);
          }
        },
        async markAsRead(notificationId) {
          try {
            await fetch(`/api/notifications/mark-read/${notificationId}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrfToken()
              },
              credentials: 'same-origin'
            });
            
            const notif = this.notifications.find(n => n.id === notificationId);
            if (notif && !notif.is_read) {
              notif.is_read = true;
              this.notificationCount = Math.max(0, this.notificationCount - 1);
            }
          } catch (error) {
            console.error('Failed to mark notification as read:', error);
          }
        },
        async markAllAsRead() {
          try {
            await fetch('/api/notifications/mark-all-read', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrfToken()
              },
              credentials: 'same-origin'
            });
            
            this.notifications.forEach(n => n.is_read = true);
            this.notificationCount = 0;
          } catch (error) {
            console.error('Failed to mark all as read:', error);
          }
        },
        async handleNotificationClick(notifOrId) {
          
          const notif = (typeof notifOrId === 'object') 
            ? notifOrId 
            : this.notifications.find(n => n.id === notifOrId);
          if (!notif) return;

          if (!notif.is_read) {
            await this.markAsRead(notif.id);
            notif.is_read = true; // Optimistically update UI
          }

          // Use the URL from the notification or build one based on type
          let to = notif.url;
          
          if (!to) {
            if (notif.notifiable_type === 'Post') {
              to = `/posts/${notif.notifiable_id}`;
            } else if (notif.notifiable_type === 'Comment') {
              to = `/comments/${notif.notifiable_id}`;
            } else if (notif.notifiable_type === 'User') {
              if (notif.type === 'friend_request') {
                to = '/friendships/requests';
              } else if (notif.type === 'friend_accept') {
                to = `/profile/${notif.actor_id}`;
              } else {
                to = `/profile/${notif.notifiable_id}`;
              }
            }
          }
          
          // Debug log
          console.log('Notification click ->', { notif, to });

          if (to) {
            if (this.$router && typeof this.$router.push === 'function') {
              this.$router.push(to);
            } else {
              window.location.href = to;
            }
          }
        },
     
        formatTime(timestamp) {
          const date = new Date(timestamp);
          const now = new Date();
          const seconds = Math.floor((now - date) / 1000);
          
          if (seconds < 60) return 'Just now';
          if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
          if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
          if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`;
          
          return date.toLocaleDateString();
        }
      },
      mounted() {
        document.addEventListener('click', (e) => {
          if (!el.contains(e.target)) {
            this.close();
            this.notificationsOpen = false;
            this.messagesOpen = false;
          }
        });
        
        // Emit custom event when mobile menu toggles
        this.$watch('mobileMenuOpen', (newVal) => {
          window.dispatchEvent(new CustomEvent('mobile-menu-toggle', { detail: { open: newVal } }));
        });
        
        // Fetch notifications on load
        this.fetchNotifications();
        
        // Poll for new notifications every 1 seconds
        setInterval(() => {
          this.fetchNotifications();
        }, 1000);
        
        // Initialize Lucide icons
        if (window.lucide) lucide.createIcons();
      },
      updated() {
        // Re-initialize icons when dropdown content updates
        if (window.lucide) {
          this.$nextTick(() => {
            lucide.createIcons();
          });
        }
      }
    });
    
    // Add click-outside directive for search dropdown
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
    
    app.mount(el);
  })();
</script>
