<div id="global-toast-container" class="flash-container fixed top-16 sm:top-20 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-4 pointer-events-none">
  <div class="w-full space-y-3 pointer-events-none"></div>
</div>

<style>
  /* Use same flash container behaviour: outer is pointer-events none, inner toasts allow interactions */
  #global-toast-container { pointer-events: none; }
  #global-toast-container > div > .toast { pointer-events: auto; }
</style>
