// Simple Promise-based confirmation modal helper
try { console.log('confirmModal.js loaded'); } catch(e){}
(function(){
  function getModal() {
    return document.getElementById('global-confirm-modal');
  }

  // Ensure modal is a direct child of <body> to avoid transform/stacking-context issues
  function ensureModalInBody() {
    const modal = getModal();
    if (!modal) return null;
    if (modal.parentNode !== document.body) {
      // Move node to body
      try {
        document.body.appendChild(modal);
      } catch (e) {
        // ignore
      }
    }

    // Force overlay styles in case page CSS interferes
    modal.style.position = 'fixed';
    modal.style.inset = '0';
    modal.style.zIndex = '99999';
    modal.style.display = 'none';

    return modal;
  }

  function show(message, opts){
    opts = opts || {};
    
    // Support object-based API: show({title, message, confirmText, confirmClass})
    if (typeof message === 'object' && message !== null) {
      opts = message;
      message = opts.message || 'Are you sure?';
    }
    
    const modal = ensureModalInBody() || getModal();
    if (!modal) {
      // Fallback to window.confirm if element not present
      return Promise.resolve(window.confirm(message));
    }

    const titleEl = modal.querySelector('#confirm-modal-title');
    const msgEl = modal.querySelector('#confirm-modal-message');
    const okBtn = modal.querySelector('[data-confirm-ok]');
    const cancelBtn = modal.querySelector('[data-confirm-cancel]');
    
    // Set title if provided
    if (titleEl && opts.title) {
      titleEl.textContent = opts.title;
    }
    
    msgEl.textContent = message || 'Are you sure?';
    
    // Set confirm button text and class
    if (okBtn) {
      okBtn.textContent = opts.confirmText || 'Confirm';
      
      // Reset button classes
      okBtn.className = 'px-3 py-2 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2';
      
      // Apply custom class or default
      if (opts.confirmClass) {
        okBtn.className += ' ' + opts.confirmClass;
      } else {
        okBtn.className += ' bg-red-600 hover:bg-red-700 focus:ring-red-300';
      }
    }
    
    // Set cancel button text
    if (cancelBtn && opts.cancelText) {
      cancelBtn.textContent = opts.cancelText;
    }

    // Save previously focused element to restore focus later
    const previousFocus = document.activeElement;

    // Try to make background inert (if supported) to prevent focus/AT issues
    const inerted = [];
    try {
      if ('inert' in HTMLElement.prototype) {
        Array.from(document.body.children).forEach(child => {
          if (child !== modal) {
            if (!child.hasAttribute('inert')) {
              child.setAttribute('inert', '');
              inerted.push(child);
            }
          }
        });
      }
    } catch (e) {
      // ignore
    }

    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden','false');

    return new Promise((resolve)=>{
      function cleanup(){
        try {
          // blur any focused element inside modal to remove focus from descendants
          try { okBtn.blur(); } catch(e){}
          try { cancelBtn.blur(); } catch(e){}

          // restore focus to previous element before hiding modal
          if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
          } else {
            try { document.body.focus(); } catch(e){}
          }
        } catch (e) {
          // ignore focus restore errors
        }

        // remove inert from background elements
        try {
          inerted.forEach(el => { try { el.removeAttribute('inert'); } catch(e){} });
        } catch (e) { /* ignore */ }

        // hide modal after moving focus away to avoid aria-hidden on focused element
        // use a short delay to let focus change take effect in AT/browsers
        setTimeout(()=>{
          modal.style.display = 'none';
          modal.setAttribute('aria-hidden','true');

          okBtn.removeEventListener('click', onOk);
          cancelBtn.removeEventListener('click', onCancel);
          modal.removeEventListener('click', onBackdrop);
          document.removeEventListener('keydown', onKey);
          
          // Reset to defaults
          if (titleEl) titleEl.textContent = 'Confirm action';
          if (msgEl) msgEl.textContent = 'Are you sure?';
          if (okBtn) {
            okBtn.textContent = 'Delete';
            okBtn.className = 'px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300';
          }
          if (cancelBtn) cancelBtn.textContent = 'Cancel';
        }, 50);
      }

      function onOk(e){ e.stopPropagation(); cleanup(); resolve(true); }
      function onCancel(e){ e.stopPropagation(); cleanup(); resolve(false); }
      function onBackdrop(e){ if (e.target === modal || e.target.classList.contains('confirm-modal-backdrop')){ cleanup(); resolve(false); } }
      function onKey(e){ if (e.key === 'Escape'){ cleanup(); resolve(false); } }

      okBtn.addEventListener('click', onOk);
      cancelBtn.addEventListener('click', onCancel);
      modal.addEventListener('click', onBackdrop);
      document.addEventListener('keydown', onKey);

      // focus the confirm button for quick keyboard confirm
      try { okBtn.focus(); } catch (e) { /* ignore */ }
    });
  }

  // Expose globally
  window.showConfirmModal = show;
  
  // Also expose as an object with show method for consistency
  window.confirmModal = {
    show: show
  };
})();
