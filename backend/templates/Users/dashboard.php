<div id="dashboardApp" class="max-w-5xl mx-auto my-10 animate-fade-in">
    <!-- Welcome Hero Section -->
    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 text-white p-10 rounded-2xl shadow-lg mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-4xl font-bold mb-3">Welcome back! 👋</h2>
                <p class="text-xl opacity-90">{{ user.full_name || user.username }}</p>
                <p class="text-sm opacity-75 mt-1">@{{ user.username }}</p>
            </div>
            <div class="hidden md:block">
                <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                    <span class="text-5xl">🎉</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">📊</span>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Account Status</p>
                    <p class="text-xl font-semibold text-gray-800">Active</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">🎯</span>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Posts</p>
                    <p class="text-xl font-semibold text-gray-800">0</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">👥</span>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Friends</p>
                    <p class="text-xl font-semibold text-gray-800">0</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Account Information -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-800">Account Information</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Full Name</p>
                    <p class="text-base font-medium text-gray-800">{{ user.full_name || 'Not set' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Username</p>
                    <p class="text-base font-medium text-gray-800">@{{ user.username }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Member Since</p>
                    <p class="text-base font-medium text-gray-800">{{ formatDate(user.created) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Last Updated</p>
                    <p class="text-base font-medium text-gray-800">{{ formatDate(user.modified) }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-xl p-8 text-center">
        <h3 class="text-2xl font-semibold text-gray-800 mb-3">🚀 Getting Started</h3>
        <p class="text-gray-600 mb-6">Your account is ready! More exciting features are coming soon.</p>
        <div class="flex flex-wrap justify-center gap-4">
            <button class="px-6 py-3 bg-indigo-500 text-white rounded-lg font-medium hover:bg-indigo-600 transition-colors shadow-sm hover:shadow-md">
                Explore Features
            </button>
            <button class="px-6 py-3 bg-white text-gray-700 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors shadow-sm">
                View Tutorial
            </button>
        </div>
    </div>
</div>

<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in {
    animation: fade-in 0.5s ease-out;
}
</style>

<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            user: <?= json_encode($user) ?>
        }
    },
    methods: {
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
    },
    mounted() {
        console.log('Dashboard loaded for user:', this.user);
    }
}).mount('#dashboardApp');
</script>

