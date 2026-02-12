<?php
/** @var \App\View\AppView $this */
?>

<div class="w-80 space-y-6 sticky top-20">
  <!-- Friends Section -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-gray-900 text-lg">Friends</h3>
      <a href="/friends" class="text-blue-600 text-sm font-medium hover:underline">See all</a>
    </div>
    
    <div class="space-y-3">
      <!-- Friend 1 -->
      <a href="/profile/sarahchen" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors">
        <img 
          src="https://i.pravatar.cc/150?img=5" 
          alt="Sarah Chen" 
          class="w-12 h-12 rounded-full object-cover border border-gray-200"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-sm truncate">Sarah Chen</p>
          <p class="text-gray-500 text-xs truncate">@sarahchen</p>
        </div>
      </a>
      
      <!-- Friend 2 -->
      <a href="/profile/jamesliu" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors">
        <img 
          src="https://i.pravatar.cc/150?img=12" 
          alt="James Liu" 
          class="w-12 h-12 rounded-full object-cover border border-gray-200"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-sm truncate">James Liu</p>
          <p class="text-gray-500 text-xs truncate">@jamesliu</p>
        </div>
      </a>
      
      <!-- Friend 3 -->
      <a href="/profile/emmawilson" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors">
        <img 
          src="https://i.pravatar.cc/150?img=47" 
          alt="Emma Wilson" 
          class="w-12 h-12 rounded-full object-cover border border-gray-200"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-sm truncate">Emma Wilson</p>
          <p class="text-gray-500 text-xs truncate">@emmawilson</p>
        </div>
      </a>
      
      <!-- Friend 4 -->
      <a href="/profile/mikeross" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors">
        <img 
          src="https://i.pravatar.cc/150?img=33" 
          alt="Mike Ross" 
          class="w-12 h-12 rounded-full object-cover border border-gray-200"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-sm truncate">Mike Ross</p>
          <p class="text-gray-500 text-xs truncate">@mikeross</p>
        </div>
      </a>
    </div>
  </div>
  
  <!-- Suggested for You Section -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-bold text-gray-900 text-lg mb-4">Suggested for you</h3>
    
    <div class="space-y-3">
      <!-- Suggestion 1 -->
      <div class="flex items-center gap-3">
        <img 
          src="https://i.pravatar.cc/150?img=16" 
          alt="Olivia Davis" 
          class="w-12 h-12 rounded-full object-cover border border-gray-200"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-sm truncate">Olivia Davis</p>
          <p class="text-gray-500 text-xs truncate">15 mutual</p>
        </div>
        <button class="flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors">
          <i data-lucide="user-plus" class="w-3 h-3"></i>
          <span>Add</span>
        </button>
      </div>
      
      <!-- Suggestion 2 -->
      <div class="flex items-center gap-3">
        <img 
          src="https://i.pravatar.cc/150?img=68" 
          alt="Noah Martinez" 
          class="w-12 h-12 rounded-full object-cover border border-gray-200"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-sm truncate">Noah Martinez</p>
          <p class="text-gray-500 text-xs truncate">9 mutual</p>
        </div>
        <button class="flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors">
          <i data-lucide="user-plus" class="w-3 h-3"></i>
          <span>Add</span>
        </button>
      </div>
      
      <!-- Suggestion 3 -->
      <div class="flex items-center gap-3">
        <img 
          src="https://i.pravatar.cc/150?img=20" 
          alt="Ava Johnson" 
          class="w-12 h-12 rounded-full object-cover border border-gray-200"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-sm truncate">Ava Johnson</p>
          <p class="text-gray-500 text-xs truncate">4 mutual</p>
        </div>
        <button class="flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors">
          <i data-lucide="user-plus" class="w-3 h-3"></i>
          <span>Add</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (window.lucide) {
      lucide.createIcons();
    }
  });
</script>
