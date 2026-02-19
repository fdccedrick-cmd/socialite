// Theme initialization - run as early as possible to prevent flash
(function() {
  // Priority: 1. Database (window.userTheme), 2. localStorage, 3. Default (dark)
  let theme = 'dark';
  
  if (window.userTheme && (window.userTheme === 'light' || window.userTheme === 'dark')) {
    // User's theme from database (highest priority)
    theme = window.userTheme;
    // Sync to localStorage
    localStorage.setItem('socialite_theme', theme);
  } else {
    // Fallback to localStorage or default
    theme = localStorage.getItem('socialite_theme') || 'dark';
  }
  
  if (theme === 'dark') {
    document.documentElement.classList.add('dark');
    if (document.body) {
      document.body.classList.add('dark:bg-gray-900');
      document.body.classList.remove('bg-gray-100');
    }
  } else {
    document.documentElement.classList.remove('dark');
    if (document.body) {
      document.body.classList.remove('dark:bg-gray-900');
      document.body.classList.add('bg-gray-100');
    }
  }
})();
