<div id="global-confirm-modal" class="" aria-hidden="true" style="display:none;">
  <div class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg max-w-lg w-full mx-4 p-6" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title">
      <div class="flex items-start gap-4">
        <div class="flex-shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9 text-red-600 dark:text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v2m0 4h.01M21 12A9 9 0 1 1 3 12a9 9 0 0 1 18 0z"/></svg>
        </div>
        <div class="min-w-0 flex-1">
          <h3 id="confirm-modal-title" class="text-lg font-semibold text-gray-900 dark:text-white">Confirm action</h3>
          <p id="confirm-modal-message" class="mt-1 text-sm text-gray-600 dark:text-gray-300">Are you sure?</p>
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-3">
        <button type="button" data-confirm-cancel class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600">Cancel</button>
        <button type="button" data-confirm-ok class="px-3 py-2 bg-red-600 dark:bg-red-500 text-white rounded-md hover:bg-red-700 dark:hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300 dark:focus:ring-red-400">Delete</button>
      </div>
    </div>
  </div>
</div>
