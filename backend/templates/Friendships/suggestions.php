<div id="suggestionsApp" class="max-w-4xl mx-auto p-4 sm:p-6" v-cloak>
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-6 mb-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">Friends</h1>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 mb-4">
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <a 
                href="/friendships/index" 
                class="flex-1 px-4 py-3 sm:px-6 sm:py-4 text-center font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
                All Friends
            </a>
            <a 
                href="/friendships/requests" 
                class="flex-1 px-4 py-3 sm:px-6 sm:py-4 text-center font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
                Requests
            </a>
            <a 
                href="/friendships/suggestions" 
                class="flex-1 px-4 py-3 sm:px-6 sm:py-4 text-center font-medium text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
                Suggestions
            </a>
        </div>
    </div>

    <!-- Suggestions List -->
    <div class="space-y-3">
        <template v-if="suggestions.length > 0">
            <div 
                v-for="suggestion in suggestions" 
                :key="suggestion.id"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-6 hover:shadow-md transition-shadow"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 sm:space-x-4 flex-1 min-w-0">
                        <!-- Profile Photo -->
                        <a :href="'/profile/view/' + suggestion.id" class="flex-shrink-0">
                            <img 
                                :src="suggestion.profile_photo_path || '/img/default-avatar.png'" 
                                :alt="suggestion.full_name"
                                class="w-12 h-12 sm:w-16 sm:h-16 rounded-full object-cover border border-gray-200 dark:border-gray-600"
                            >
                        </a>
                        
                        <!-- User Info -->
                        <div class="flex-1 min-w-0">
                            <a :href="'/profile/view/' + suggestion.id" class="block hover:underline">
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white truncate">
                                    {{ suggestion.full_name }}
                                </h3>
                            </a>
                            <p class="text-sm text-gray-600 dark:text-gray-400 truncate">@{{ suggestion.username }}</p>
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-500 mt-1" v-if="suggestion.mutual_friends_count > 0">
                                {{ suggestion.mutual_friends_count }} mutual
                            </p>
                        </div>
                    </div>
                    
                    <!-- Action Button -->
                    <div class="ml-3 flex-shrink-0">
                        <button
                            v-if="!suggestion.requestSent"
                            @click="sendRequest(suggestion.id)"
                            class="flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                        >
                            <i data-lucide="user-plus" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">Add Friend</span>
                        </button>
                        <button
                            v-else
                            disabled
                            class="flex items-center space-x-2 px-4 py-2 bg-gray-300 text-gray-600 rounded-lg font-medium cursor-not-allowed"
                        >
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">Sent</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- Empty State -->
        <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-8 sm:p-12 text-center">
            <i data-lucide="user-search" class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No suggestions available</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                We couldn't find any friend suggestions at the moment.
            </p>
            <a 
                href="/friendships/index" 
                class="inline-block px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
            >
                View Friends
            </a>
        </div>
    </div>
</div>

<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            suggestions: <?= json_encode($suggestions ?? []) ?>.map(s => ({ ...s, requestSent: false }))
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
        async sendRequest(userId) {
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
                    // Mark as sent in local array
                    const suggestion = this.suggestions.find(s => s.id === userId);
                    if (suggestion) {
                        suggestion.requestSent = true;
                    }
                    
                    window.toast.show('Friend request sent', 'success');
                } else {
                    window.toast.show(data.message || 'Failed to send request', 'error');
                }
            } catch (error) {
                console.error('Error sending request:', error);
                window.toast.show('An error occurred', 'error');
            }
        }
    }
}).mount('#suggestionsApp');
</script>
