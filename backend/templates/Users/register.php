<div id="registerApp">
    <h2 class="register-title">Create Your Account</h2>
    
    <form method="post" action="/register" @submit="handleSubmit">
        <div class="form-group">
            <label class="form-label">Username</label>
            <input 
                type="text" 
                name="username" 
                v-model="formData.username"
                required
                class="form-input"
                @focus="$event.target.style.borderColor='#667eea'"
                @blur="$event.target.style.borderColor='#e0e0e0'"
            >
            <small v-if="errors.username" class="error-message">{{ errors.username }}</small>
        </div>
        
        <div class="form-group">
            <label class="form-label">Full Name</label>
            <input 
                type="text" 
                name="full_name" 
                v-model="formData.full_name"
                class="form-input"
                @focus="$event.target.style.borderColor='#667eea'"
                @blur="$event.target.style.borderColor='#e0e0e0'"
            >
        </div>
        
        <div class="form-group">
            <label class="form-label">Email</label>
            <input 
                type="email" 
                name="email" 
                v-model="formData.email"
                required
                class="form-input"
                @focus="$event.target.style.borderColor='#667eea'"
                @blur="$event.target.style.borderColor='#e0e0e0'"
            >
            <small v-if="errors.email" class="error-message">{{ errors.email }}</small>
        </div>
        
        <div class="form-group">
            <label class="form-label">Password</label>
            <input 
                type="password" 
                name="password" 
                v-model="formData.password"
                required
                class="form-input"
                @focus="$event.target.style.borderColor='#667eea'"
                @blur="$event.target.style.borderColor='#e0e0e0'"
            >
            <small v-if="errors.password" class="error-message">{{ errors.password }}</small>
        </div>
        
        <div class="form-group last">
            <label class="form-label">Confirm Password</label>
            <input 
                type="password" 
                v-model="formData.confirmPassword"
                required
                class="form-input"
                @focus="$event.target.style.borderColor='#667eea'"
                @blur="$event.target.style.borderColor='#e0e0e0'"
            >
            <small v-if="errors.confirmPassword" class="error-message">{{ errors.confirmPassword }}</small>
        </div>
        
        <button 
            type="submit"
            class="submit-btn"
            @mouseover="$event.target.style.transform='translateY(-2px)'"
            @mouseout="$event.target.style.transform='translateY(0)'"
        >
            Register
        </button>
    </form>
    
    <p class="footer-text">
        Already have an account? 
        <a href="/login" class="footer-link">Login here</a>
    </p>
</div>

<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            formData: {
                username: '',
                full_name: '',
                email: '',
                password: '',
                confirmPassword: ''
            },
            errors: {}
        }
    },
    methods: {
        handleSubmit(e) {
            this.errors = {};
            
            // Username validation
            if (this.formData.username.length < 3) {
                this.errors.username = 'Username must be at least 3 characters';
                e.preventDefault();
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(this.formData.email)) {
                this.errors.email = 'Please enter a valid email';
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
            if (this.formData.password !== this.formData.confirmPassword) {
                this.errors.confirmPassword = 'Passwords do not match';
                e.preventDefault();
                return;
            }
        }
    }
}).mount('#registerApp');
</script>

<style>
.register-title {
    text-align: center;
    color: #667eea;
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group.last {
    margin-bottom: 25px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.form-input {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 5px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.error-message {
    color: #dc3545;
    margin-top: 5px;
    display: block;
}

.submit-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s;
}

.footer-text {
    text-align: center;
    margin-top: 20px;
    color: #666;
}

.footer-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}
</style>
