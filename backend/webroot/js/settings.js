(function () {
  const el = document.getElementById('settingsApp');
  if (!el) return;

  const app = Vue.createApp({
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
      async handleSubmit() {
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
            window.location.href = '/settings/account?updated=1';
            return;
          } else {
            if (data.errors) {
              this.errors = data.errors;
            } else if (data.message) {
              // fall back to inline error display; optionally redirect to show flash
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

  app.mount(el);
})();
