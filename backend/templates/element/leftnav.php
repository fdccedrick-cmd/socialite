<?php
/** @var \App\View\AppView $this */
$user = $currentUser ?? $user ?? null;
$username = $user->full_name ?? $user->username ?? 'Guest';
$avatar = $user->profile_photo_path ?? 'https://i.pravatar.cc/150?img=1';
$currentPath = $this->request->getPath();
?>

<div class="hidden lg:block w-56 bg-white rounded-xl shadow-sm border border-gray-100 p-3 lg:p-4 sticky top-20 h-fit">
  <!-- Profile Section -->
  <div class="text-center mb-3 lg:mb-4 pb-3 lg:pb-4 border-b border-gray-100">
    <img 
      src="<?= h($avatar) ?>" 
      alt="<?= h($username) ?>" 
      class="w-12 h-12 lg:w-16 lg:h-16 rounded-full object-cover border-2 border-gray-100 mx-auto mb-1.5 lg:mb-2"
    />
    <h3 class="font-semibold text-gray-900 text-xs lg:text-sm truncate px-2"><?= h($username) ?></h3>
    <p class="text-gray-500 text-[10px] lg:text-xs truncate px-2">@<?= h($user->username ?? '') ?></p>
  </div>
  
  <!-- Navigation Menu -->
  <nav class="space-y-0.5 lg:space-y-1 mb-3 lg:mb-4">
    <a 
      href="/dashboard" 
      class="flex items-center gap-2 lg:gap-2.5 px-2 lg:px-3 py-1.5 lg:py-2 rounded-lg transition-colors <?= str_contains($currentPath, '/dashboard') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' ?>"
    >
      <i data-lucide="home" class="w-3.5 h-3.5 lg:w-4 lg:h-4 flex-shrink-0"></i>
      <span class="text-xs lg:text-sm font-medium">Feed</span>
    </a>
    
    <a 
      href="/profile" 
      class="flex items-center gap-2 lg:gap-2.5 px-2 lg:px-3 py-1.5 lg:py-2 rounded-lg transition-colors <?= str_contains($currentPath, '/profile') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' ?>"
    >
      <i data-lucide="user" class="w-3.5 h-3.5 lg:w-4 lg:h-4 flex-shrink-0"></i>
      <span class="text-xs lg:text-sm font-medium">Profile</span>
    </a>
    
    <a 
      href="/friends" 
      class="flex items-center gap-2 lg:gap-2.5 px-2 lg:px-3 py-1.5 lg:py-2 rounded-lg transition-colors <?= str_contains($currentPath, '/friends') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' ?>"
    >
      <i data-lucide="users" class="w-3.5 h-3.5 lg:w-4 lg:h-4 flex-shrink-0"></i>
      <span class="text-xs lg:text-sm font-medium">Friends</span>
    </a>
    
    <a 
      href="/settings" 
      class="flex items-center gap-2 lg:gap-2.5 px-2 lg:px-3 py-1.5 lg:py-2 rounded-lg transition-colors <?= str_contains($currentPath, '/settings') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' ?>"
    >
      <i data-lucide="settings" class="w-3.5 h-3.5 lg:w-4 lg:h-4 flex-shrink-0"></i>
      <span class="text-xs lg:text-sm font-medium">Settings</span>
    </a>
  </nav>
  
  <!-- Logout -->
  <form method="post" action="/logout" class="mt-auto pt-3 lg:pt-4 border-t border-gray-100">
    <button 
      type="submit" 
      class="flex items-center gap-2 lg:gap-2.5 px-2 lg:px-3 py-1.5 lg:py-2 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors w-full"
    >
      <i data-lucide="log-out" class="w-3.5 h-3.5 lg:w-4 lg:h-4 flex-shrink-0"></i>
      <span class="text-xs lg:text-sm font-medium">Log out</span>
    </button>
  </form>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (window.lucide) {
      lucide.createIcons();
    }
  });
</script>
