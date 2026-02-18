<?php
/** @var \App\View\AppView $this */
$friends = $friends ?? [];
$suggestions = $suggestions ?? [];
?>

<div id="rightnavApp" class="w-full space-y-3 lg:space-y-4 sticky top-20 h-fit" v-cloak>
  <!-- Friends Section -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 xl:p-4">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold text-gray-900 text-sm">Friends</h3>
      <a href="/friendships/index" class="text-blue-600 text-xs font-medium hover:underline">See all</a>
    </div>
    
    <div v-if="friends.length > 0" class="space-y-2">
      <a 
        v-for="friend in friends" 
        :key="friend.id"
        :href="'/profile/' + friend.id" 
        class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-50 transition-colors"
      >
        <img 
          :src="friend.profile_photo_path || '/img/default-avatar.png'" 
          :alt="friend.full_name" 
          class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-xs truncate">{{ friend.full_name }}</p>
          <p class="text-gray-500 text-[10px] truncate">@{{ friend.username }}</p>
        </div>
      </a>
    </div>
    
    <div v-else class="text-center py-4">
      <p class="text-gray-500 text-xs">No friends yet</p>
      <a href="/friendships/suggestions" class="text-blue-600 text-xs hover:underline mt-1 inline-block">Find friends</a>
    </div>
  </div>
  
  <!-- Suggested for You Section -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 xl:p-4">
    <h3 class="font-semibold text-gray-900 text-sm mb-3">Suggested for you</h3>
    
    <div v-if="suggestions.length > 0" class="space-y-2">
      <div 
        v-for="suggestion in suggestions" 
        :key="suggestion.id"
        class="flex items-center gap-2"
      >
        <a :href="'/profile/' + suggestion.id">
          <img 
            :src="suggestion.profile_photo_path || '/img/default-avatar.png'" 
            :alt="suggestion.full_name" 
            class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0"
          />
        </a>
        <div class="flex-1 min-w-0">
          <a :href="'/profile/' + suggestion.id">
            <p class="font-semibold text-gray-900 text-xs truncate hover:underline">{{ suggestion.full_name }}</p>
          </a>
          <p class="text-gray-500 text-[10px] truncate">{{ suggestion.mutual_friends_count }} mutual</p>
        </div>
        <button 
          @click="addFriend(suggestion.id)"
          :disabled="suggestion.requestSent"
          :class="suggestion.requestSent ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
          class="flex items-center gap-1 px-2 py-1 text-white text-[10px] font-medium rounded-md transition-colors flex-shrink-0"
        >
          <i :data-lucide="suggestion.requestSent ? 'check' : 'user-plus'" class="w-2.5 h-2.5"></i>
          <span>{{ suggestion.requestSent ? 'Sent' : 'Add' }}</span>
        </button>
      </div>
    </div>
    
    <div v-else class="text-center py-4">
      <p class="text-gray-500 text-xs">No suggestions with mutual friends</p>
      <a href="/friendships/suggestions" class="text-blue-600 text-xs hover:underline mt-1 inline-block">View all suggestions</a>
    </div>
  </div>
</div>

<script>
(function() {
  const el = document.getElementById('rightnavApp');
  if (!el) return;

  const { createApp } = Vue;

  createApp({
    data() {
      return {
        friends: <?= json_encode($friends) ?>,
        suggestions: <?= json_encode($suggestions) ?>.map(s => ({ ...s, requestSent: false }))
      };
    },
    methods: {
      async addFriend(userId) {
        const suggestion = this.suggestions.find(s => s.id === userId);
        if (!suggestion) return;

        try {
          const response = await fetch('/friendships/add', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ friend_id: userId })
          });
          
          const data = await response.json();
          
          if (data.success) {
            suggestion.requestSent = true;
            if (window.toast) {
              window.toast.show('Friend request sent', 'success');
            }
          } else {
            if (window.toast) {
              window.toast.show(data.message || 'Failed to send friend request', 'error');
            }
          }
        } catch (error) {
          console.error('Error sending friend request:', error);
          if (window.toast) {
            window.toast.show('An error occurred', 'error');
          }
        }
      }
    },
    mounted() {
      this.$nextTick(() => {
        if (window.lucide) {
          lucide.createIcons();
        }
      });
    },
    updated() {
      this.$nextTick(() => {
        if (window.lucide) {
          lucide.createIcons();
        }
      });
    }
  }).mount(el);
})();
</script>
