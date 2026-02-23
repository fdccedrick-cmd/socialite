<div id="registerApp" class="max-w-md mx-auto mt-16 md:mt-28 my-8 md:my-24 p-4 md:p-8 flex flex-col" style="max-height: calc(100vh - 8rem); height: calc(100vh - 8rem);">
    <!-- Fixed Header Section -->
    <div class="flex-shrink-0">
        <!-- Error Messages -->
        <div v-if="showError" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-start gap-3 animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span class="font-medium">{{ errorMessage }}</span>
        </div>
        
        <div class="flex items-center justify-center gap-3 mb-3 md:mb-5">
            <h2 class="text-gray-900 text-xl md:text-2xl font-base">Sign Up</h2>
        </div>
        <p class="text-gray-700 text-xs md:text-sm text-center pb-4 md:pb-6">Create your account and start sharing.</p>
    </div>
    
    <!-- Scrollable Form Section -->
    <?= $this->Form->create(null, [
        'url' => '/register',
        'type' => 'post',
        'class' => 'flex flex-col flex-1 min-h-0',
        'templates' => [
            'formStart' => '<form{{attrs}} @submit="handleSubmit" class="flex flex-col flex-1 min-h-0">',
        ]
    ]) ?>
    <div class="flex-1 overflow-y-auto pr-1 md:pr-2 pb-4" style="scrollbar-width: none; min-height: 0;">
        <div class="mt-2 md:mt-4 flex flex-col gap-3 md:gap-4">
            <div>
                <?= $this->Form->control('full_name', [
                    'label' => ['text' => 'Full Name', 'class' => 'block mb-2 font-small text-sm text-gray-500'],
                    'type' => 'text',
                    'required' => true,
                    'autocomplete' => 'name',
                    'templates' => [
                        'input' => '<input type="{{type}}" name="{{name}}"{{attrs}} v-model="formData.full_name" :disabled="isSubmitting" :class="{ \'border-red-400\': errors.full_name, \'border-green-400\': !errors.full_name && formData.full_name.trim().length >= 2 }" aria-required="true" class="w-full px-3 py-3 border-2 border-gray-200 rounded-md text-sm transition-all focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed"/>',
                        'inputContainer' => '<div class="form-group">{{content}}</div>',
                        'inputContainerError' => '<div class="form-group error">{{content}}</div>',
                    ],
                ]) ?>
                <div class="flex items-center justify-between mt-1.5 text-xs">
                    <div class="flex items-center gap-2">
                        <svg v-if="formData.full_name.trim().length >= 2" class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <!-- <span :class="formData.full_name.trim().length >= 2 ? 'text-green-600' : 'text-gray-500'">
                            Minimum 2 characters
                        </span> -->
                    </div>
                    <span class="text-gray-400">{{ formData.full_name.trim().length }}/150</span>
                </div>
                <small v-if="errors.full_name" class="text-red-600 text-sm mt-1 block flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ errors.full_name }}
                </small>
            </div>
            
            <div>
                <?= $this->Form->control('username', [
                    'label' => ['text' => 'Username', 'class' => 'block mb-2 font-small text-sm text-gray-500'],
                    'type' => 'text',
                    'required' => true,
                    'autocomplete' => 'username',
                    'templates' => [
                        'input' => '<div class="relative"><input type="{{type}}" name="{{name}}"{{attrs}} v-model="formData.username" @input="checkUsernameDebounced" :disabled="isSubmitting" :class="{ \'border-red-400\': errors.username || usernameStatus === \'taken\', \'border-green-400\': usernameStatus === \'available\' }" aria-required="true" class="w-full px-3 py-3 border-2 border-gray-200 rounded-md text-sm transition-all focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed"/><div v-if="isCheckingUsername" class="absolute right-3 top-1/2 transform -translate-y-1/2"><svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div><div v-else-if="usernameStatus === \'available\'" class="absolute right-3 top-1/2 transform -translate-y-1/2"><svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg></div><div v-else-if="usernameStatus === \'taken\'" class="absolute right-3 top-1/2 transform -translate-y-1/2"><svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg></div></div>',
                        'inputContainer' => '<div class="form-group">{{content}}</div>',
                        'inputContainerError' => '<div class="form-group error">{{content}}</div>',
                    ],
                ]) ?>
                <div class="flex flex-col gap-1 mt-1.5 text-xs">
                    <div class="flex items-center gap-2">
                        <svg v-if="formData.username.length >= 3" class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <svg v-else class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span :class="formData.username.length >= 3 ? 'text-green-600' : 'text-gray-500'">
                            Minimum 3 characters ({{ formData.username.length }}/50)
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg v-if="isAlphaNumeric(formData.username)" class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <svg v-else class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span :class="isAlphaNumeric(formData.username) ? 'text-green-600' : 'text-gray-500'">
                            Lowercase letters and numbers only (no spaces or special characters)
                        </span>
                    </div>
                </div>
                <div v-if="usernameStatus === 'taken'" class="mt-2 p-3 bg-red-50 border border-red-200 rounded-md flex items-start gap-2">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-red-800">Username already taken</p>
                        <p class="text-xs text-red-700 mt-0.5">This username is already in use. Please choose a different one.</p>
                    </div>
                </div>
                <div v-else-if="usernameStatus === 'available'" class="mt-2 p-3 bg-green-50 border border-green-200 rounded-md flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm font-medium text-green-800">Username is available!</p>
                </div>
                <small v-if="errors.username" class="text-red-600 text-sm mt-1 block flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ errors.username }}
                </small>
            </div>
            
            <div>
                <?= $this->Form->control('password', [
                    'label' => ['text' => 'Password', 'class' => 'block mb-2 font-small text-sm text-gray-500'],
                    'type' => 'password',
                    'required' => true,
                    'autocomplete' => 'new-password',
                    'templates' => [
                        'input' => '<input type="{{type}}" name="{{name}}"{{attrs}} v-model="formData.password" :disabled="isSubmitting" :class="{ \'border-red-400\': errors.password, \'border-green-400\': !errors.password && formData.password.length >= 6 }" aria-required="true" class="w-full px-3 py-3 border-2 border-gray-200 rounded-md text-sm transition-all focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed"/>',
                        'inputContainer' => '<div class="form-group">{{content}}</div>',
                        'inputContainerError' => '<div class="form-group error">{{content}}</div>',
                    ],
                ]) ?>
                <div class="flex items-center justify-between mt-1.5 text-xs">
                    <div class="flex items-center gap-2">
                        <svg v-if="formData.password.length >= 6" class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span :class="formData.password.length >= 6 ? 'text-green-600' : 'text-gray-500'">
                            Minimum 6 characters
                        </span>
                    </div>
                    <span class="text-gray-400">{{ formData.password.length }}/255</span>
                </div>
                <small v-if="errors.password" class="text-red-600 text-sm mt-1 block flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ errors.password }}
                </small>
            </div>
            
            <div class="mb-2">
                <?= $this->Form->control('confirm_password', [
                    'label' => ['text' => 'Confirm Password', 'class' => 'block mb-2 font-small text-sm text-gray-500'],
                    'type' => 'password',
                    'required' => true,
                    'autocomplete' => 'new-password',
                    'templates' => [
                        'input' => '<input type="{{type}}" name="{{name}}"{{attrs}} v-model="formData.confirm_password" :disabled="isSubmitting" :class="{ \'border-red-400\': errors.confirm_password, \'border-green-400\': !errors.confirm_password && formData.confirm_password && formData.password === formData.confirm_password }" aria-required="true" class="w-full px-3 py-3 border-2 border-gray-200 rounded-md text-sm transition-all focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:opacity-60 disabled:cursor-not-allowed"/>',
                        'inputContainer' => '<div class="form-group">{{content}}</div>',
                        'inputContainerError' => '<div class="form-group error">{{content}}</div>',
                    ],
                ]) ?>
                <div class="flex items-center gap-2 mt-1.5 text-xs">
                    <svg v-if="formData.confirm_password && formData.password === formData.confirm_password" class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span :class="formData.confirm_password && formData.password === formData.confirm_password ? 'text-green-600' : 'text-gray-500'">
                        Passwords match
                    </span>
                </div>
                <small v-if="errors.confirm_password" class="text-red-600 text-sm mt-1 block flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ errors.confirm_password }}
                </small>
            </div>
        </div>
    </div>
    <!-- End Scrollable Section -->
    
    <!-- Fixed Button Section -->
    <div class="flex-shrink-0 mt-3 md:mt-4 pt-3 md:pt-4 border-t border-gray-100">
        <button 
            type="submit"
            :disabled="isSubmitting"
            class="w-full px-3.5 py-3 md:py-3.5 bg-blue-500 text-white border-none rounded-md text-sm md:text-base font-semibold cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:translate-y-0"
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
    
    <!-- Fixed Footer Section -->
    <div class="flex-shrink-0 mt-3 md:mt-5">
        <p class="text-center text-xs md:text-sm text-gray-600">
            Already have an account? 
            <a href="/login" class="text-blue-500 no-underline font-medium hover:underline transition-all">Login here</a>
        </p>
    </div>
</div>

<style>
.form-group {
    width: 100%;
}

.form-group.error input {
    border-color: inherit;
}

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

/* Custom scrollbar styling */
#registerApp div[style*="overflow-y-auto"]::-webkit-scrollbar {
    width: 6px;
}

#registerApp div[style*="overflow-y-auto"]::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#registerApp div[style*="overflow-y-auto"]::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#registerApp div[style*="overflow-y-auto"]::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    #registerApp {
        max-height: calc(100vh - 4rem) !important;
        height: calc(100vh - 4rem) !important;
    }
}
</style>

<script src="/js/loader.js"></script>
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
            errorMessage: '',
            usernameStatus: '', // '', 'available', 'taken'
            isCheckingUsername: false,
            usernameCheckTimeout: null
        }
    },
    methods: {
        isAlphaNumeric(str) {
            if (!str) return str === '';
            // Only allow lowercase letters and numbers - no uppercase, no spaces, no special chars
            return /^[a-z0-9_]+$/.test(str);
        },
        
        checkUsernameDebounced() {
           
            const originalValue = this.formData.username;
           
            let sanitized = originalValue.toLowerCase();
            
            sanitized = sanitized.replace(/\s/g, '');
         
            sanitized = sanitized.replace(/[^a-z0-9_]/g, '');
            
           
            if (sanitized !== originalValue) {
                this.formData.username = sanitized;
            }
            
          
            if (this.usernameCheckTimeout) {
                clearTimeout(this.usernameCheckTimeout);
            }
        
            if (this.formData.username.length < 3 || !this.isAlphaNumeric(this.formData.username)) {
                this.usernameStatus = '';
                return;
            }
            
           
            this.usernameCheckTimeout = setTimeout(() => {
                this.checkUsername();
            }, 500); 
        },
        
        async checkUsername() {
            if (this.formData.username.length < 3) {
                this.usernameStatus = '';
                return;
            }
            
            if (!this.isAlphaNumeric(this.formData.username)) {
                this.usernameStatus = '';
                return;
            }
            
            this.isCheckingUsername = true;
            
            try {
                const response = await fetch(`/users/check-username?username=${encodeURIComponent(this.formData.username)}`);
                const data = await response.json();
                
                if (data.available) {
                    this.usernameStatus = 'available';
                    if (this.errors.username && this.errors.username.includes('taken')) {
                        delete this.errors.username;
                    }
                } else {
                    this.usernameStatus = 'taken';
                    this.errors.username = 'This username is already taken';
                }
            } catch (error) {
                console.error('Error checking username:', error);
                this.usernameStatus = '';
            } finally {
                this.isCheckingUsername = false;
            }
        },
        
        handleSubmit(e) {
            e.preventDefault();
            this.errors = {};
            this.showError = false;

            // validations
            if (!this.formData.full_name || this.formData.full_name.trim().length < 2) {
                this.errors.full_name = 'Full name is required (at least 2 characters)';
                return;
            }
            
            // Username validation
            if (this.formData.username.length < 3) {
                this.errors.username = 'Username must be at least 3 characters';
                return;
            }
            
            if (!this.isAlphaNumeric(this.formData.username)) {
                this.errors.username = 'Username can only contain lowercase letters and numbers (no spaces or special characters)';
                return;
            }
            
            // Additional validation for spaces and uppercase
            if (/\s/.test(this.formData.username)) {
                this.errors.username = 'Username cannot contain spaces';
                return;
            }
            
            if (/[A-Z]/.test(this.formData.username)) {
                this.errors.username = 'Username must be lowercase only';
                return;
            }
            
            // Check if username is taken
            if (this.usernameStatus === 'taken') {
                this.errors.username = 'This username is already taken';
                return;
            }
            
            // Password validation
            if (this.formData.password.length < 6) {
                this.errors.password = 'Password must be at least 6 characters';
                return;
            }
            
            // Confirm password validation
            if (this.formData.password !== this.formData.confirm_password) {
                this.errors.confirm_password = 'Passwords do not match';
                return;
            }
            
            // Prevent submission if username check is still in progress
            if (this.isCheckingUsername) {
                return;
            }

            // Show loader and submit form
            this.isSubmitting = true;
            window.showLoader('Creating your account...');
            
            // Submit the form
            e.target.submit();
        }
    },
    mounted() {
        lucide.createIcons();
    }
}).mount('#registerApp');
</script>
