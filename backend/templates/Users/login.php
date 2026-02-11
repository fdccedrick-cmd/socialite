<div id="loginApp" class="login-container">
    <div class="login-header">
        <i data-lucide="message-circle" class="login-icon" aria-hidden="true"></i>
        <h2 class="login-title">Login to Socialite</h2>
    </div>
    <span class="span">Connect with friends and the world around you.</span>
    <form method="post" action="/login" @submit="handleSubmit">
        <div class="form-container">
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
            </div>
            
            <div class="form-group last">
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
            </div>
            
            <button 
                type="submit"
                class="submit-btn"
                @mouseover="$event.target.style.transform='translateY(-2px)'"
                @mouseout="$event.target.style.transform='translateY(0)'"
            >
                Login
            </button>
        </div>
    </form>
    
    <p class="footer-text">
        Don't have an account? 
        <a href="/register" class="footer-link">Register here</a>
    </p>
</div>

<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            formData: {
                email: '',
                password: ''
            }
        }
    },
    methods: {
        handleSubmit(e) {
            if (!this.formData.email || !this.formData.password) {
                e.preventDefault();
                alert('Please fill in all fields');
            }
        }
    },
    mounted() {
        // Initialize Lucide icons
        lucide.createIcons();
    }
}).mount('#loginApp');
</script>

<style>
.login-container {
    max-width: 400px;
    margin: 80px auto;
    padding: 30px;
    background: white;
}
.login-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 20px;
}
.login-icon {
    width: 30px;
    height: 30px;
    color: #667eea;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.login-header h2{
    color: #030304;
    font-size: 24px;
    font-weight: 600;
   
}
.span {
  color: #333;
  margin-left: 30px;
  margin-top: 0;
  text-align: right;
  font-size: 14px;
  padding-bottom: 2rem;
}
.form-container {
  margin-top: 4rem;
}
.form-group {
    margin-bottom: 1.5rem;
    margin-top: 20px;
}

.form-group.last {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    color: #555;
    font-weight: 500;
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    transition: border-color 0.3s;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
}

.submit-btn {
    width: 100%;
    padding: 0.75rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s;
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
}

.submit-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.footer-text {
    text-align: center;
    margin-top: 1.5rem;
    color: #666;
}

.footer-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.footer-link:hover {
    text-decoration: underline;
}

.error-message {
    background: #fee;
    color: #c33;
    padding: 0.75rem;
    border-radius: 5px;
    margin-bottom: 1rem;
    text-align: center;
}

.success-message {
    background: #efe;
    color: #3c3;
    padding: 0.75rem;
    border-radius: 5px;
    margin-bottom: 1rem;
    text-align: center;
}
</style>
