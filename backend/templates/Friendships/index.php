<div id="friendshipsApp" class="max-w-4xl mx-auto p-4 sm:p-6" v-cloak>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4">
        <h1 class="text-2xl sm:text-2xl font-bold text-gray-900 mb-4">Friends</h1>
        
        <!-- Search Bar -->
        <div class="relative">
            <form method="get" action="/friendships/index">
                <div class="relative">
                    <input 
                        type="text" 
                        name="search"
                        v-model="searchQuery"
                        @input="filterFriends"
                        placeholder="Search friends..."
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <i data-lucide="search" class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow-sm mb-4">
        <div class="flex border-b border-gray-200">
            <a 
                href="/friendships/index" 
                class="flex-1 px-4 py-3 sm:px-6 sm:py-4 text-center font-medium text-blue-600 border-b-2 border-blue-600 hover:bg-gray-50 transition-colors"
            >
                All Friends
            </a>
            <a 
                href="/friendships/requests" 
                class="flex-1 px-4 py-3 sm:px-6 sm:py-4 text-center font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors"
            >
                Requests
            </a>
            <a 
                href="/friendships/suggestions" 
                class="flex-1 px-4 py-3 sm:px-6 sm:py-4 text-center font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors"
            >
                Suggestions
            </a>
        </div>
    </div>

    <!-- Friends List -->
    <div class="space-y-3">
        <template v-if="filteredFriends.length > 0">
            <div 
                v-for="friend in filteredFriends" 
                :key="friend.id"
                class="bg-white rounded-lg shadow-sm p-4 sm:p-6 hover:shadow-md transition-shadow"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 sm:space-x-4 flex-1 min-w-0">
                        <!-- Profile Photo -->
                        <a :href="'/profile/view/' + friend.id" class="flex-shrink-0">
                            <img 
                                :src="friend.profile_photo_path || '/img/default-avatar.png'" 
                                :alt="friend.full_name"
                                class="w-12 h-12 sm:w-16 sm:h-16 rounded-full object-cover"
                            >
                        </a>
                        
                        <!-- User Info -->
                        <div class="flex-1 min-w-0">
                            <a :href="'/profile/view/' + friend.id" class="block hover:underline">
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate">
                                    {{ friend.full_name }}
                                </h3>
                            </a>
                            <p class="text-sm text-gray-600 truncate">@{{ friend.username }}</p>
                            <p class="text-xs sm:text-sm text-gray-500 mt-1">
                                {{ friend.mutual_friends_count }} mutual
                            </p>
                        </div>
                    </div>
                    
                    <!-- Action Button -->
                    <div class="ml-3 flex-shrink-0">
                        <button
                            @click="removeFriend(friend.id)"
                            class="flex items-center space-x-2 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium transition-colors"
                        >
                            <i data-lucide="user-check" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">Friends</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- Empty State -->
        <div v-else class="bg-white rounded-lg shadow-sm p-8 sm:p-12 text-center">
            <i data-lucide="users" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No friends found</h3>
            <p class="text-gray-600 mb-6">
                <template v-if="searchQuery">
                    No friends match your search.
                </template>
                <template v-else>
                    Start adding friends to see them here!
                </template>
            </p>
            <a 
                href="/friendships/suggestions" 
                class="inline-block px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
            >
                Find Friends
            </a>
        </div>
    </div>
</div>

<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            friends: <?= json_encode($friends ?? []) ?>,
            searchQuery: '<?= h($searchQuery ?? '') ?>',
            filteredFriends: []
        }
    },
    mounted() {
        this.filterFriends();
        lucide.createIcons();
    },
    updated() {
        this.$nextTick(() => {
            lucide.createIcons();
        });
    },
    methods: {
        filterFriends() {
            if (!this.searchQuery) {
                this.filteredFriends = this.friends;
                return;
            }
            
            const query = this.searchQuery.toLowerCase();
            this.filteredFriends = this.friends.filter(friend => {
                return friend.full_name.toLowerCase().includes(query) || 
                       friend.username.toLowerCase().includes(query);
            });
        },
        async removeFriend(friendId) {
            const confirmed = await window.confirmModal.show({
                title: 'Remove Friend',
                message: 'Are you sure you want to remove this friend?',
                confirmText: 'Remove',
                confirmClass: 'bg-red-600 hover:bg-red-700'
            });
            
            if (!confirmed) return;
            
            try {
                const response = await fetch('/friendships/remove', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ friend_id: friendId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Remove from local array
                    this.friends = this.friends.filter(f => f.id !== friendId);
                    this.filterFriends();
                    
                    window.toast.show('Friend removed successfully', 'success');
                } else {
                    window.toast.show(data.message || 'Failed to remove friend', 'error');
                }
            } catch (error) {
                console.error('Error removing friend:', error);
                window.toast.show('An error occurred', 'error');
            }
        }
    }
}).mount('#friendshipsApp');
</script>
