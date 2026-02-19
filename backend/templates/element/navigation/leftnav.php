<?php
/** @var \App\View\AppView $this */
$user = $currentUser ?? $user ?? null;
$username = $user->full_name ?? $user->username ?? 'Guest';
$avatar = $user->profile_photo_path ?? '/img/default/default_avatar.jpg';
$currentPath = $this->request->getPath();
?>

<div class="w-full bg-white dark:bg-gray-800 lg:rounded-xl lg:shadow-sm lg:border lg:border-gray-100 dark:border-gray-700 p-3 lg:p-4 lg:sticky lg:top-20 h-full lg:h-fit">
  <!-- Profile Section (Desktop Only) -->
  <div class="hidden lg:block text-center mb-3 lg:mb-4 pb-3 lg:pb-4 border-b border-gray-100 dark:border-gray-700 box-shadow-sm">
    <img 
      src="<?= h($avatar) ?>" 
      alt="<?= h($username) ?>" 
      class="w-14 h-14 lg:w-16 lg:h-16 rounded-full object-cover border-2 border-blue-300 dark:border-blue-500 mx-auto mb-2 "
    />
    <h3 class="font-semibold text-gray-900 dark:text-white text-sm truncate px-2"><?= h($username) ?></h3>
    <p class="text-gray-500 dark:text-gray-400 text-xs truncate px-2">@<?= h($user->username ?? '') ?></p>
  </div>
  
  <!-- Mobile Header -->
  <div class="lg:hidden mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
    <h2 class="font-semibold text-gray-900 dark:text-white text-base px-2">Menu</h2>
  </div>
  
  <!-- Navigation Menu -->
  <nav class="space-y-1 mb-4">
    <a 
      href="/dashboard" 
      class="flex items-center gap-3 px-3 py-2.5 lg:py-2 rounded-lg transition-colors <?= str_contains($currentPath, '/dashboard') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>"
    >
      <i data-lucide="home" class="w-5 h-5 lg:w-4 lg:h-4 flex-shrink-0"></i>
      <span class="text-sm font-medium">Feed</span>
    </a>
    
    <a 
      href="/profile" 
      class="flex items-center gap-3 px-3 py-2.5 lg:py-2 rounded-lg transition-colors <?= str_contains($currentPath, '/profile') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>"
    >
      <i data-lucide="user" class="w-5 h-5 lg:w-4 lg:h-4 flex-shrink-0"></i>
      <span class="text-sm font-medium">Profile</span>
    </a>
    
    <a 
      href="/friendships/index" 
      class="flex items-center gap-3 px-3 py-2.5 lg:py-2 rounded-lg transition-colors <?= str_contains($currentPath, '/friendships') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>"
    >
      <i data-lucide="users" class="w-5 h-5 lg:w-4 lg:h-4 flex-shrink-0"></i>
      <span class="text-sm font-medium">Friends</span>
    </a>
    
    <a 
      href="/settings/account" 
      class="flex items-center gap-3 px-3 py-2.5 lg:py-2 rounded-lg transition-colors <?= str_contains($currentPath, '/settings') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>"
    >
      <i data-lucide="settings" class="w-5 h-5 lg:w-4 lg:h-4 flex-shrink-0"></i>
      <span class="text-sm font-medium">Settings</span>
    </a>
  </nav>
  
  <!-- Logout -->
  <?= $this->Form->create(null, [
    'type' => 'post',
    'url' => '/logout',
    'class' => 'mt-auto pt-3 lg:pt-4 border-t border-gray-100 dark:border-gray-700'
  ]) ?>
    <button 
      type="submit" 
      class="flex items-center gap-3 px-3 py-2.5 lg:py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors w-full"
    >
      <i data-lucide="log-out" class="w-5 h-5 lg:w-4 lg:h-4 flex-shrink-0"></i>
      <span class="text-sm font-medium">Log out</span>
    </button>
  <?= $this->Form->end() ?>
</div>
