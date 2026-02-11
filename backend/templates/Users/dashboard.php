<div id="dashboardApp">
    <h2 class="dashboard-title">Welcome to Your Dashboard!</h2>
    
    <div class="user-card">
        <h3 class="user-greeting">Hello, {{ user.username }}! 👋</h3>
        <p class="user-email">{{ user.email }}</p>
        <p v-if="user.full_name" class="user-name">{{ user.full_name }}</p>
    </div>
    
    <div class="info-grid">
        <div class="info-card welcome">
            <h4 class="card-title welcome">🎉 Welcome!</h4>
            <p class="card-text">You have successfully logged into Socialite.</p>
        </div>
        
        <div class="info-card account">
            <h4 class="card-title account">📊 Account Info</h4>
            <p class="card-text small">Created: {{ formatDate(user.created) }}</p>
        </div>
    </div>
    
    <div class="footer-section">
        <p class="coming-soon">More features coming soon...</p>
    </div>
</div>

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
    }
}).mount('#dashboardApp');
</script>

<style>
.dashboard-title {
    text-align: center;
    color: #667eea;
    margin-bottom: 30px;
}

.user-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.user-greeting {
    margin-bottom: 10px;
}

.user-email {
    opacity: 0.9;
}

.user-name {
    opacity: 0.9;
    margin-top: 5px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.info-card {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
}

.info-card.welcome {
    border-left: 4px solid #667eea;
}

.info-card.account {
    border-left: 4px solid #764ba2;
}

.card-title {
    margin-bottom: 10px;
}

.card-title.welcome {
    color: #667eea;
}

.card-title.account {
    color: #764ba2;
}

.card-text {
    color: #666;
}

.card-text.small {
    font-size: 14px;
}

.footer-section {
    margin-top: 40px;
    text-align: center;
}

.coming-soon {
    color: #999;
    font-style: italic;
}
</style>
