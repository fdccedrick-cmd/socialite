<div id="loginApp" class="max-w-md mx-auto my-32 p-8 ">
    <!-- Success Message -->
    <div v-if="showSuccess" class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center gap-3 animate-fade-in">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span class="font-medium">{{ successMessage }}</span>
    </div>
    
    <!-- Error Message -->
    <div v-if="showError" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center gap-3 animate-fade-in">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <span class="font-medium">{{ errorMessage }}</span>
    </div>
    
    <div class="flex items-center justify-center gap-3 mb-5">
        <h2 class="text-gray-600 text-3xl font-base">Login</h2>
    </div>
    <p class="text-gray-700 text-sm text-right pb-8">Connect with friends and the world around you.</p>
    
    <form method="post" action="/login" @submit="handleSubmit">
        <div class="mt-8 space-y-6">
            <div>
                <label for="login_username" class="block mb-2 text-gray-600 font-small">Username</label>
                <input 
                    id="login_username"
                    type="text" 
                    name="username" 
                    autocomplete="username"
                    placeholder="johndoe"
                    v-model="formData.username"
                    required
                    :disabled="isSubmitting"
                    class="w-full px-3 py-3 border border-gray-300 rounded-md text-base transition-all focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed"
                    :class="{ 'border-red-400': errors.username }"
                >
                <small v-if="errors.username" class="text-red-600 text-sm mt-1 block">{{ errors.username }}</small>
            </div>
            
            <div class="relative">
                <label for="login_password" class="block mb-2 text-gray-600 font-small">Password</label>
                <input 
                    id="login_password"
                    :type="showPassword ? 'text' : 'password'"
                    name="password" 
                    autocomplete="current-password"
                    v-model="formData.password"
                    required
                    :disabled="isSubmitting"
                    class="w-full pr-12 px-3 py-3 border border-gray-300 rounded-md text-base transition-all focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed"
                    :class="{ 'border-red-400': errors.password }"
                >
                <button type="button" @click.prevent="showPassword = !showPassword" class="absolute inset-y-0 right-3 mt-8 flex items-center text-gray-500 px-2" aria-label="Toggle password visibility" title="Toggle password visibility">
                    <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-liejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>

                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a10.06 10.06 0 012.223-3.582M6.18 6.18A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.96 9.96 0 01-1.768 3.042M3 3l18 18"/>
                                        </svg>
                                </button>

                <small v-if="errors.password" class="text-red-600 text-sm mt-1 block">{{ errors.password }}</small>
            </div>
            
            <button 
                type="submit"
                :disabled="isSubmitting"
                class="w-full px-3 py-3 bg-blue-500 text-white border-none rounded-md text-base font-semibold cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:translate-y-0"
            >
                <span v-if="!isSubmitting">Log In</span>
                <span v-else class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Logging in...
                </span>
            </button>
        </div>
    </form>
    
    <p class="text-center mt-6 text-gray-600">
        Don't have an account? 
        <a href="/register" class="text-blue-500 no-underline font-medium hover:underline transition-all">Register here</a>
    </p>
</div>

<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>

<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            formData: {
                username: '',
                password: ''
            },
            showPassword: false,
            errors: {},
            isSubmitting: false,
            showSuccess: false,
            showError: false,
            successMessage: '',
            errorMessage: ''
        }
    },
    methods: {
        handleSubmit(e) {
            // Controlled submit: prevent default then submit natively after validation
            e.preventDefault();
            this.errors = {};

            // Basic validation
            if (!this.formData.username) {
                this.errors.username = 'Username is required';
                return;
            }

            if (!this.formData.password) {
                this.errors.password = 'Password is required';
                return;
            }

            this.isSubmitting = true;

            // Submit the native form to ensure fields are sent
            try {
                e.target.submit();
            } catch (err) {
                const form = document.querySelector('#loginApp').closest('form') || document.querySelector('form');
                if (form) form.submit();
            }
        }
    },
    mounted() {
        // Check for flash messages in URL params or session
        const urlParams = new URLSearchParams(window.location.search);
        const success = urlParams.get('success');
        
        // Display success message if coming from registration
        if (success) {
            this.showSuccess = true;
            this.successMessage = 'Account created successfully! Please login.';
            setTimeout(() => {
                this.showSuccess = false;
            }, 5000);
        }
        
        lucide.createIcons();

        // password toggle handled by Vue's `showPassword` reactive state
    }
}).mount('#loginApp');

</script>
