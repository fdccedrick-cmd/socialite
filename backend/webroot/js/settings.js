(function () {
  // Account Section App
  const accountEl = document.getElementById('accountSection');
  if (accountEl) {
    const accountApp = Vue.createApp({
      data() {
        return {
          isSubmitting: false,
          showCurrent: false,
          showNew: false,
          showConfirm: false,
          form: {
            current_password: '',
            new_password: '',
            confirm_password: ''
          },
          errors: {}
        };
      },
      mounted() {
        if (window.lucide) lucide.createIcons();
      },
      watch: {
        showCurrent() {
          this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        showNew() {
          this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        showConfirm() {
          this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        }
      },
      methods: {
        async handlePasswordSubmit() {
          this.errors = {};
          this.isSubmitting = true;

          if (!this.form.current_password) {
            this.errors.current_password = 'Current password is required';
          }
          if (!this.form.new_password) {
            this.errors.new_password = 'New password is required';
          } else if (this.form.new_password.length < 6) {
            this.errors.new_password = 'Password must be at least 6 characters';
          }
          if (this.form.new_password !== this.form.confirm_password) {
            this.errors.confirm_password = 'Passwords do not match';
          }

          if (Object.keys(this.errors).length) {
            this.isSubmitting = false;
            return;
          }

          const formData = new FormData();
          formData.append('current_password', this.form.current_password);
          formData.append('new_password', this.form.new_password);

          try {
            const resp = await fetch('/settings/update-password', {
              method: 'POST',
              body: formData
            });
            const data = await resp.json();
            if (data.success) {
              this.form.current_password = '';
              this.form.new_password = '';
              this.form.confirm_password = '';
              window.location.href = '/settings?section=account&updated=1';
              return;
            } else {
              if (data.errors) {
                this.errors = data.errors;
              } else if (data.message) {
                this.errors.general = data.message;
              }
            }
          } catch (e) {
            console.error('Error updating password', e);
            alert('Failed to update password. Please try again.');
          } finally {
            this.isSubmitting = false;
          }
        }
      }
    });

    accountApp.mount(accountEl);
  }

  // Theme Section App
  const themeEl = document.getElementById('themeSection');
  if (themeEl) {
    const themeApp = Vue.createApp({
      data() {
        return {
          theme: 'dark' // Default to dark mode
        };
      },
      mounted() {
        // Load saved theme priority: 1. Database (via window.userTheme), 2. localStorage, 3. Default (dark)
        let savedTheme = 'dark';
        if (window.userTheme && (window.userTheme === 'light' || window.userTheme === 'dark')) {
          savedTheme = window.userTheme;
        } else {
          savedTheme = localStorage.getItem('socialite_theme') || 'dark';
        }
        
        this.theme = savedTheme;
        this.applyTheme(savedTheme);
        
        if (window.lucide) lucide.createIcons();
      },
      watch: {
        theme() {
          this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        }
      },
      methods: {
        setTheme(newTheme) {
          this.theme = newTheme;
          this.applyTheme(newTheme);
          localStorage.setItem('socialite_theme', newTheme);
          
          // Save theme to database
          this.saveThemeToDatabase(newTheme);
          
          // Show feedback
          if (window.showToast) {
            window.showToast(`Theme changed to ${newTheme} mode`, 'success');
          }
        },
        
        async saveThemeToDatabase(theme) {
          try {
            const formData = new FormData();
            formData.append('theme', theme);
            
            const resp = await fetch('/settings/update-theme', {
              method: 'POST',
              body: formData
            });
            
            const data = await resp.json();
            
            if (!data.success) {
              console.error('Failed to save theme to database:', data.message);
            }
          } catch (error) {
            console.error('Error saving theme:', error);
          }
        },
        
        applyTheme(theme) {
          if (theme === 'dark') {
            document.documentElement.classList.add('dark');
            document.body.classList.add('dark:bg-gray-900');
            document.body.classList.remove('bg-gray-100');
          } else {
            document.documentElement.classList.remove('dark');
            document.body.classList.remove('dark:bg-gray-900');
            document.body.classList.add('bg-gray-100');
          }
        }
      }
    });

    themeApp.mount(themeEl);
  }
})();
