// Simple toast helper using Tailwind-style classes. Exposes `window.showToast(message, type, durationMs)`
try {
  console.log('toast.js loaded');
} catch(e){}

(function(){
  function makeToast(message, type){
    const el = document.createElement('div');
    // Smooth, centered toast with subtle shadow
    el.className = 'toast w-full max-w-md bg-white shadow-md rounded-lg p-3 flex items-start gap-3 ring-1 ring-black ring-opacity-5 transform transition-all duration-300 ease-out';

    const colorClasses = {
      success: 'border-l-4 border-green-500',
      error: 'border-l-4 border-red-500',
      info: 'border-l-4 border-blue-500'
    };

    el.className += ' ' + (colorClasses[type] || colorClasses.info) + ' translate-y-[-6px] opacity-0';

    el.innerHTML = `
      <div class="flex-shrink-0 mt-0.5">
        <svg class="w-6 h-6 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 9v2m0 4h.01M21 12A9 9 0 1 1 3 12a9 9 0 0 1 18 0z"></path></svg>
      </div>
      <div class="flex-1 text-sm text-gray-700">${message}</div>
      <button class="ml-2 text-gray-400 hover:text-gray-600 close-btn" aria-label="Close">&times;</button>
    `;

    return el;
  }

  function getContainer(){
    let outer = document.getElementById('global-toast-container');
    if (!outer) {
      outer = document.createElement('div');
      outer.id = 'global-toast-container';
      outer.className = 'flash-container fixed top-16 sm:top-20 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-4 pointer-events-none';
      const inner = document.createElement('div');
      inner.className = 'w-full space-y-3 pointer-events-none';
      outer.appendChild(inner);
      document.body.appendChild(outer);
      return inner;
    }

    // If outer exists but is not a direct child of body, move it to body to avoid stacking/overflow issues
    if (outer.parentNode !== document.body) {
      try { document.body.appendChild(outer); } catch (e) { /* ignore */ }
    }

    if (outer.firstElementChild) return outer.firstElementChild;

    // If outer exists but no inner, create inner wrapper
    const inner = document.createElement('div');
    inner.className = 'w-full space-y-3 pointer-events-none';
    outer.appendChild(inner);
    return inner;
  }

  window.__TOAST_LOADED = true;
  window.showToast = function(message, type = 'info', duration = 4000){
    try {
      console.log('showToast called', { message, type, duration });

      let container = null;
      try { container = getContainer(); } catch (e) { console.error('getContainer error', e); }

      const toast = makeToast(message, type);
      try { console.debug('toast: appending to container', { container, toastHtml: toast.outerHTML }); } catch(e){}

      // Fallback: if container missing, append to body
      const appendTarget = container || document.body;

      // Force inline styles to make toast visible (debugging safety)
      try {
        toast.style.position = 'relative';
        toast.style.zIndex = '999999';
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
        toast.style.margin = '6px auto';
        toast.style.maxWidth = '36rem';
      } catch (e) { /* ignore */ }

      appendTarget.appendChild(toast);
    // initial state already set via class; ensure layout
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-6px)';
    // allow pointer events on toast
    toast.style.pointerEvents = 'auto';
    container.appendChild(toast);


    // Use flash classes/animation consistent with server flash UI
    if (!toast.classList.contains('flash-message')) toast.classList.add('flash-message');

    // enter
    requestAnimationFrame(()=>{
      try { toast.classList.add('flash-in'); } catch(e) { console.warn('toast enter failed', e); }
    });

    const VISIBLE_FOR = duration || 4000;
    const timeoutId = setTimeout(()=>{
      // exit
      toast.classList.remove('flash-in');
      toast.classList.add('flash-out');
      setTimeout(()=>{ try{ container.removeChild(toast); }catch(e){} }, 400);
    }, VISIBLE_FOR);

    // click to dismiss
    toast.addEventListener('click', function(){
      clearTimeout(timeoutId);
      toast.classList.remove('flash-in');
      toast.classList.add('flash-out');
      setTimeout(()=>{ try{ container.removeChild(toast); }catch(e){} }, 200);
    });

    // close button
    toast.querySelector('.close-btn').addEventListener('click', (ev)=>{
      ev.stopPropagation();
      clearTimeout(timeoutId);
      toast.classList.remove('flash-in');
      toast.classList.add('flash-out');
      setTimeout(()=>{ try{ container.removeChild(toast); }catch(e){} }, 200);
    });

    function removeToast(){
      try { clearTimeout(timeoutId); } catch(e){}
      try { if (toast.parentNode) toast.parentNode.removeChild(toast); } catch(e){}
    }

    return {
      close() { removeToast(); }
    };
    } catch (err) {
      try { console.error('showToast error', err); } catch(e){}
      try { alert(message); } catch(e){}
    }
  };

})();
