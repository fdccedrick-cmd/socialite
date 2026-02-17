// Simple Promise-based confirmation modal helper
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
    const modal = ensureModalInBody() || getModal();
    if (!modal) {
      // Fallback to window.confirm if element not present
      return Promise.resolve(window.confirm(message));
    }

    const msgEl = modal.querySelector('#confirm-modal-message');
    const okBtn = modal.querySelector('[data-confirm-ok]');
    const cancelBtn = modal.querySelector('[data-confirm-cancel]');
    msgEl.textContent = message || 'Are you sure?';

    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden','false');

    return new Promise((resolve)=>{
      function cleanup(){
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden','true');
        okBtn.removeEventListener('click', onOk);
        cancelBtn.removeEventListener('click', onCancel);
        modal.removeEventListener('click', onBackdrop);
        document.removeEventListener('keydown', onKey);
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
      okBtn.focus();
    });
  }

  // Expose globally
  window.showConfirmModal = show;
})();
