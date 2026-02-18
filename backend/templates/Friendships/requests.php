<div id="requestsApp" class="max-w-4xl mx-auto p-4 sm:p-6" v-cloak>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 mb-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Friends</h1>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow-sm mb-4">
        <div class="flex border-b border-gray-200">
            <a 
                href="/friendships/index" 
                class="flex-1 px-4 py-3 sm:px-6 sm:py-4 text-center font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors"
            >
                All Friends
            </a>
            <a 
                href="/friendships/requests" 
                class="flex-1 px-4 py-3 sm:px-6 sm:py-4 text-center font-medium text-blue-600 border-b-2 border-blue-600 hover:bg-gray-50 transition-colors"
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

    <!-- Requests List -->
    <div class="space-y-3">
        <template v-if="requests.length > 0">
            <div 
                v-for="request in requests" 
                :key="request.friendship_id"
                class="bg-white rounded-lg shadow-sm p-4 sm:p-6 hover:shadow-md transition-shadow"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 sm:space-x-4 flex-1 min-w-0">
                        <!-- Profile Photo -->
                        <a :href="'/profile/view/' + request.id" class="flex-shrink-0">
                            <img 
                                :src="request.profile_photo_path || '/img/default-avatar.png'" 
                                :alt="request.full_name"
                                class="w-12 h-12 sm:w-16 sm:h-16 rounded-full object-cover"
                            >
                        </a>
                        
                        <!-- User Info -->
                        <div class="flex-1 min-w-0">
                            <a :href="'/profile/view/' + request.id" class="block hover:underline">
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate">
                                    {{ request.full_name }}
                                </h3>
                            </a>
                            <p class="text-sm text-gray-600 truncate">@{{ request.username }}</p>
                            <p class="text-xs sm:text-sm text-gray-500 mt-1">
                                {{ request.mutual_friends_count }} mutual
                            </p>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="ml-3 flex-shrink-0 flex flex-col sm:flex-row gap-2">
                        <button
                            @click="acceptRequest(request.friendship_id)"
                            class="flex items-center justify-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Accept</span>
                        </button>
                        <button
                            @click="rejectRequest(request.friendship_id)"
                            class="flex items-center justify-center space-x-2 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium transition-colors"
                        >
                            <i data-lucide="x" class="w-4 h-4"></i>
                            <span>Reject</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- Empty State -->
        <div v-else class="bg-white rounded-lg shadow-sm p-8 sm:p-12 text-center">
            <i data-lucide="inbox" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No friend requests</h3>
            <p class="text-gray-600 mb-6">
                You don't have any pending friend requests.
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
            requests: <?= json_encode($requests ?? []) ?>
        }
    },
    mounted() {
        lucide.createIcons();
    },
    updated() {
        this.$nextTick(() => {
            lucide.createIcons();
        });
    },
    methods: {
        async acceptRequest(friendshipId) {
            try {
                const response = await fetch('/friendships/accept/' + friendshipId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Remove from local array
                    this.requests = this.requests.filter(r => r.friendship_id !== friendshipId);
                    
                    window.toast.show('Friend request accepted', 'success');
                } else {
                    window.toast.show(data.message || 'Failed to accept request', 'error');
                }
            } catch (error) {
                console.error('Error accepting request:', error);
                window.toast.show('An error occurred', 'error');
            }
        },
        async rejectRequest(friendshipId) {
            const confirmed = await window.confirmModal.show({
                title: 'Reject Friend Request',
                message: 'Are you sure you want to reject this friend request?',
                confirmText: 'Reject',
                confirmClass: 'bg-red-600 hover:bg-red-700'
            });
            
            if (!confirmed) return;
            
            try {
                const response = await fetch('/friendships/reject/' + friendshipId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Remove from local array
                    this.requests = this.requests.filter(r => r.friendship_id !== friendshipId);
                    
                    window.toast.show('Friend request rejected', 'success');
                } else {
                    window.toast.show(data.message || 'Failed to reject request', 'error');
                }
            } catch (error) {
                console.error('Error rejecting request:', error);
                window.toast.show('An error occurred', 'error');
            }
        }
    }
}).mount('#requestsApp');
</script>
