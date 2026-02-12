<div id="registerApp" class="max-w-md mx-auto my-20 p-8 bg-white rounded-lg border border-black/5 shadow-sm transition-all duration-300">
    <!-- Error Messages -->
    <div v-if="showError" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-start gap-3 animate-fade-in">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <span class="font-medium">{{ errorMessage }}</span>
    </div>
    
    <div class="flex items-center justify-center gap-3 mb-5">
        <h2 class="text-gray-900 text-2xl font-semibold">Sign Up</h2>
    </div>
    <p class="text-gray-700 text-sm text-right pb-6">Connect with friends and the world around you.</p>
    
    <form method="post" action="/register" @submit="handleSubmit">
        <div class="mt-8 flex flex-col gap-4">
            <div>
                <label for="full_name" class="block mb-2 font-medium text-gray-700">Full Name</label>
                <input 
                    id="full_name"
                    type="text" 
                    name="full_name" 
                    autocomplete="name"
                    v-model="formData.full_name"
                    required
                    aria-required="true"
                    :disabled="isSubmitting"
                    class="w-full px-3 py-3 border-2 border-gray-200 rounded-md text-sm transition-all focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed"
                    :class="{ 'border-red-400': errors.full_name }"
                >
                <small v-if="errors.full_name" class="text-red-600 text-sm mt-1 block">{{ errors.full_name }}</small>
            </div>
            
            <div>
                <label for="username" class="block mb-2 font-medium text-gray-700">Username</label>
                <input 
                    id="username"
                    type="text" 
                    name="username" 
                    autocomplete="username"
                    v-model="formData.username"
                    required
                    aria-required="true"
                    :disabled="isSubmitting"
                    class="w-full px-3 py-3 border-2 border-gray-200 rounded-md text-sm transition-all focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed"
                    :class="{ 'border-red-400': errors.username }"
                >
                <small v-if="errors.username" class="text-red-600 text-sm mt-1 block">{{ errors.username }}</small>
            </div>
            
            <div>
                <label for="password" class="block mb-2 font-medium text-gray-700">Password</label>
                <input 
                    id="password"
                    type="password" 
                    name="password" 
                    autocomplete="new-password"
                    v-model="formData.password"
                    required
                    aria-required="true"
                    :disabled="isSubmitting"
                    class="w-full px-3 py-3 border-2 border-gray-200 rounded-md text-sm transition-all focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed"
                    :class="{ 'border-red-400': errors.password }"
                >
                <small v-if="errors.password" class="text-red-600 text-sm mt-1 block">{{ errors.password }}</small>
            </div>
            
            <div class="mb-2">
                <label for="confirm_password" class="block mb-2 font-medium text-gray-700">Confirm Password</label>
                <input 
                    id="confirm_password"
                    type="password" 
                    name="confirm_password"
                    autocomplete="new-password"
                    v-model="formData.confirm_password"
                    required
                    aria-required="true"
                    :disabled="isSubmitting"
                    class="w-full px-3 py-3 border-2 border-gray-200 rounded-md text-sm transition-all focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed"
                    :class="{ 'border-red-400': errors.confirm_password }"
                >
                <small v-if="errors.confirm_password" class="text-red-600 text-sm mt-1 block">{{ errors.confirm_password }}</small>
            </div>
            
            <button 
                type="submit"
                :disabled="isSubmitting"
                class="w-full mt-2 px-3.5 py-3.5 bg-gradient-to-br from-indigo-500 to-purple-600 text-white border-none rounded-md text-base font-semibold cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:translate-y-0"
            >
                <span v-if="!isSubmitting">Create Account</span>
                <span v-else class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creating account...
                </span>
            </button>
        </div>
    </form>
    <?php if (!empty($rawPost)): ?>
    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded">
        <strong>Debug (server received):</strong>
        <pre><?= h(json_encode($rawPost)) ?></pre>
    </div>
    <?php endif; ?>
    
    <p class="text-center mt-5 text-gray-600">
        Already have an account? 
        <a href="/login" class="text-indigo-500 no-underline font-semibold hover:underline transition-all">Login here</a>
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
                full_name: '',
                password: '',
                confirm_password: ''
            },
            errors: {},
            isSubmitting: false,
            showError: false,
            errorMessage: ''
        }
    },
    methods: {
        handleSubmit(e) {
            // Always prevent default and perform a controlled submit
            e.preventDefault();
            this.errors = {};
            this.showError = false;

            // Full name validation
            if (!this.formData.full_name || this.formData.full_name.trim().length < 2) {
                this.errors.full_name = 'Full name is required (at least 2 characters)';
                return;
            }
            
            // Username validation
            if (this.formData.username.length < 3) {
                this.errors.username = 'Username must be at least 3 characters';
                e.preventDefault();
                return;
            }
            
            // Check if username contains only alphanumeric characters
            const alphaNumeric = /^[a-zA-Z0-9]+$/;
            if (!alphaNumeric.test(this.formData.username)) {
                this.errors.username = 'Username can only contain letters and numbers';
                e.preventDefault();
                return;
            }
            
            // Password validation
            if (this.formData.password.length < 6) {
                this.errors.password = 'Password must be at least 6 characters';
                e.preventDefault();
                return;
            }
            
            // Confirm password validation
            if (this.formData.password !== this.formData.confirm_password) {
                this.errors.confirm_password = 'Passwords do not match';
                return;
            }

            this.isSubmitting = true;

            // Submit the native form to ensure the browser sends the fields
            try {
                e.target.submit();
            } catch (err) {
                // fallback: find the closest form and submit
                const form = document.querySelector('#registerApp').closest('form') || document.querySelector('form');
                if (form) form.submit();
            }
        }
    },
    mounted() {
        lucide.createIcons();
    }
}).mount('#registerApp');
</script>
