<?php
/** @var \App\View\AppView $this */
$user = $user ?? ($this->Identity->get() ?? null);
$username = $user->full_name ?? $user->username ?? 'Guest';
$avatar = $user->profile_photo_path ?? 'https://i.pravatar.cc/150?img=1';
$currentPath = $this->request->getPath();
?>

<div class="w-64 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-20 h-fit">
  <!-- Profile Section -->
  <div class="text-center mb-6 pb-6 border-b border-gray-100">
    <img 
      src="<?= h($avatar) ?>" 
      alt="<?= h($username) ?>" 
      class="w-24 h-24 rounded-full object-cover border-4 border-gray-100 mx-auto mb-3"
    />
    <h3 class="font-bold text-gray-900 text-lg"><?= h($username) ?></h3>
    <p class="text-gray-500 text-sm">@<?= h($user->username ?? '') ?></p>
  </div>
  
  <!-- Navigation Menu -->
  <nav class="space-y-1 mb-6">
    <a 
      href="/dashboard" 
      class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= str_contains($currentPath, '/dashboard') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' ?>"
    >
      <i data-lucide="home" class="w-5 h-5"></i>
      <span class="font-medium">Feed</span>
    </a>
    
    <a 
      href="/profile" 
      class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= str_contains($currentPath, '/profile') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' ?>"
    >
      <i data-lucide="user" class="w-5 h-5"></i>
      <span class="font-medium">Profile</span>
    </a>
    
    <a 
      href="/friends" 
      class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= str_contains($currentPath, '/friends') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' ?>"
    >
      <i data-lucide="users" class="w-5 h-5"></i>
      <span class="font-medium">Friends</span>
    </a>
    
    <a 
      href="/settings" 
      class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?= str_contains($currentPath, '/settings') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' ?>"
    >
      <i data-lucide="settings" class="w-5 h-5"></i>
      <span class="font-medium">Settings</span>
    </a>
  </nav>
  
  <!-- Logout -->
  <form method="post" action="/logout" class="mt-auto pt-6 border-t border-gray-100">
    <button 
      type="submit" 
      class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors w-full"
    >
      <i data-lucide="log-out" class="w-5 h-5"></i>
      <span class="font-medium">Log out</span>
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
