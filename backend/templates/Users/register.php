<div id="registerApp" class="max-w-md mx-auto mt-28 my-24 p-8 ">
    <!-- Error Messages -->
    <div v-if="showError" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-start gap-3 animate-fade-in">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <span class="font-medium">{{ errorMessage }}</span>
    </div>
    
    <div class="flex items-center justify-center gap-3 mb-5">
        <h2 class="text-gray-900 text-2xl font-base">Sign Up</h2>
    </div>
    <p class="text-gray-700 text-sm text-center pb-6">Create your account and start sharing.</p>
    
    <?= $this->Form->create(null, [
        'url' => '/register',
        'type' => 'post',
        'templates' => [
            'formStart' => '<form{{attrs}} @submit="handleSubmit">',
        ]
    ]) ?>
        <div class="mt-4 flex flex-col gap-4">
            <div>
                <?= $this->Form->control('full_name', [
                    'label' => ['text' => 'Full Name', 'class' => 'block mb-2 font-small text-sm text-gray-500'],
                    'type' => 'text',
                    'required' => true,
                    'autocomplete' => 'name',
                    'templates' => [
                        'input' => '<input type="{{type}}" name="{{name}}"{{attrs}} v-model="formData.full_name" :disabled="isSubmitting" :class="{ \'border-red-400\': errors.full_name }" aria-required="true" class="w-full px-3 py-3 border-2 border-gray-200 rounded-md text-sm transition-all focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed"/>',
                        'inputContainer' => '<div class="form-group">{{content}}</div>',
                        'inputContainerError' => '<div class="form-group error">{{content}}</div>',
                    ],
                ]) ?>
                <small v-if="errors.full_name" class="text-red-600 text-sm mt-1 block">{{ errors.full_name }}</small>
            </div>
            
            <div>
                <?= $this->Form->control('username', [
                    'label' => ['text' => 'Username', 'class' => 'block mb-2 font-small text-sm text-gray-500'],
                    'type' => 'text',
                    'required' => true,
                    'autocomplete' => 'username',
                    'templates' => [
                        'input' => '<input type="{{type}}" name="{{name}}"{{attrs}} v-model="formData.username" :disabled="isSubmitting" :class="{ \'border-red-400\': errors.username }" aria-required="true" class="w-full px-3 py-3 border-2 border-gray-200 rounded-md text-sm transition-all focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed"/>',
                        'inputContainer' => '<div class="form-group">{{content}}</div>',
                        'inputContainerError' => '<div class="form-group error">{{content}}</div>',
                    ],
                ]) ?>
                <small v-if="errors.username" class="text-red-600 text-sm mt-1 block">{{ errors.username }}</small>
            </div>
            
            <div>
                <?= $this->Form->control('password', [
                    'label' => ['text' => 'Password', 'class' => 'block mb-2 font-small text-sm text-gray-500'],
                    'type' => 'password',
                    'required' => true,
                    'autocomplete' => 'new-password',
                    'templates' => [
                        'input' => '<input type="{{type}}" name="{{name}}"{{attrs}} v-model="formData.password" :disabled="isSubmitting" :class="{ \'border-red-400\': errors.password }" aria-required="true" class="w-full px-3 py-3 border-2 border-gray-200 rounded-md text-sm transition-all focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed"/>',
                        'inputContainer' => '<div class="form-group">{{content}}</div>',
                        'inputContainerError' => '<div class="form-group error">{{content}}</div>',
                    ],
                ]) ?>
                <small v-if="errors.password" class="text-red-600 text-sm mt-1 block">{{ errors.password }}</small>
            </div>
            
            <div class="mb-2">
                <?= $this->Form->control('confirm_password', [
                    'label' => ['text' => 'Confirm Password', 'class' => 'block mb-2 font-small text-sm text-gray-500'],
                    'type' => 'password',
                    'required' => true,
                    'autocomplete' => 'new-password',
                    'templates' => [
                        'input' => '<input type="{{type}}" name="{{name}}"{{attrs}} v-model="formData.confirm_password" :disabled="isSubmitting" :class="{ \'border-red-400\': errors.confirm_password }" aria-required="true" class="w-full px-3 py-3 border-2 border-gray-200 rounded-md text-sm transition-all focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed"/>',
                        'inputContainer' => '<div class="form-group">{{content}}</div>',
                        'inputContainerError' => '<div class="form-group error">{{content}}</div>',
                    ],
                ]) ?>
                <small v-if="errors.confirm_password" class="text-red-600 text-sm mt-1 block">{{ errors.confirm_password }}</small>
            </div>
            
            <button 
                type="submit"
                :disabled="isSubmitting"
                class="w-full mt-2 px-3.5 py-3.5 bg-blue-500 text-white border-none rounded-md text-base font-semibold cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:translate-y-0"
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
    <?= $this->Form->end() ?>
    <?php if (!empty($rawPost)): ?>
    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded">
        <strong>Debug (server received):</strong>
        <pre><?= h(json_encode($rawPost)) ?></pre>
    </div>
    <?php endif; ?>
    
    <p class="text-center mt-5 text-gray-600">
        Already have an account? 
        <a href="/login" class="text-blue-500 no-underline font-medium hover:underline transition-all">Login here</a>
    </p>
</div>

<style>
/* Reset CakePHP Form Helper default styles */
.form-group {
    width: 100%;
}

.form-group.error input {
    border-color: inherit;
}

/* Hide CakePHP's default error messages - using Vue validation */
.error-message {
    display: none;
}

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
            this.errors = {};
            this.showError = false;
            
            // Debug logging
            console.log('Register form submit - Vue formData:', this.formData);
            console.log('Form inputs:', {
                full_name: e.target.querySelector('[name="full_name"]')?.value,
                username: e.target.querySelector('[name="username"]')?.value,
                password: e.target.querySelector('[name="password"]')?.value,
                confirm_password: e.target.querySelector('[name="confirm_password"]')?.value
            });

            // Full name validation - only prevent if validation fails
            if (!this.formData.full_name || this.formData.full_name.trim().length < 2) {
                this.errors.full_name = 'Full name is required (at least 2 characters)';
                e.preventDefault();
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
                e.preventDefault();
                return;
            }

            // Validation passed - let browser submit naturally
            // DON'T set isSubmitting = true here as it disables inputs, preventing their values from POSTing
            console.log('Form validation passed, allowing natural submit');
        }
    },
    mounted() {
        lucide.createIcons();
    }
}).mount('#registerApp');
</script>
