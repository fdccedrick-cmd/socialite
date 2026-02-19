/**
 * Reusable loader component for Socialite
 * Usage: window.showLoader() / window.hideLoader()
 */

(function() {
    // Create loader HTML
    const loaderHTML = `
        <div id="socialite-loader" class="loader-overlay" style="display: none;">
            <div class="loader-container">
                <div class="loader-spinner"></div>
                <p class="loader-text">Please wait...</p>
            </div>
        </div>
    `;

    // Create loader styles
    const loaderStyles = `
        <style id="socialite-loader-styles">
            .loader-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 99999;
                backdrop-filter: blur(4px);
                animation: fadeIn 0.2s ease-in;
            }

            .loader-container {
                background: white;
                padding: 2rem 3rem;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 1.5rem;
                animation: scaleIn 0.3s ease-out;
            }

            .dark .loader-container {
                background: #1f2937;
                color: white;
            }

            .loader-spinner {
                width: 50px;
                height: 50px;
                border: 4px solid #e5e7eb;
                border-top-color: #3b82f6;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
            }

            .dark .loader-spinner {
                border-color: #374151;
                border-top-color: #60a5fa;
            }

            .loader-text {
                margin: 0;
                font-size: 1rem;
                font-weight: 500;
                color: #374151;
            }

            .dark .loader-text {
                color: #e5e7eb;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                }
                to {
                    opacity: 1;
                }
            }

            @keyframes scaleIn {
                from {
                    transform: scale(0.9);
                    opacity: 0;
                }
                to {
                    transform: scale(1);
                    opacity: 1;
                }
            }
        </style>
    `;

    // Initialize loader when DOM is ready
    function initLoader() {
        // Add styles to head
        document.head.insertAdjacentHTML('beforeend', loaderStyles);
        
        // Add loader HTML to body
        document.body.insertAdjacentHTML('beforeend', loaderHTML);
    }

    // Show loader function
    function showLoader(text = 'Please wait...') {
        const loader = document.getElementById('socialite-loader');
        const loaderText = loader.querySelector('.loader-text');
        
        if (loaderText) {
            loaderText.textContent = text;
        }
        
        if (loader) {
            loader.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    // Hide loader function
    function hideLoader() {
        const loader = document.getElementById('socialite-loader');
        if (loader) {
            loader.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoader);
    } else {
        initLoader();
    }

    // Expose functions globally
    window.showLoader = showLoader;
    window.hideLoader = hideLoader;
})();
