<?php
/** @var \App\View\AppView $this */
?>

<div class="w-full space-y-3 lg:space-y-4 sticky top-20 h-fit">
  <!-- Friends Section -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 xl:p-4">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold text-gray-900 text-sm">Friends</h3>
      <a href="/friends" class="text-blue-600 text-xs font-medium hover:underline">See all</a>
    </div>
    
    <div class="space-y-2">
      <!-- Friend 1 -->
      <a href="/profile/sarahchen" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-50 transition-colors">
        <img 
          src="https://i.pravatar.cc/150?img=5" 
          alt="Sarah Chen" 
          class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-xs truncate">Sarah Chen</p>
          <p class="text-gray-500 text-[10px] truncate">@sarahchen</p>
        </div>
      </a>
      
      <!-- Friend 2 -->
      <a href="/profile/jamesliu" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-50 transition-colors">
        <img 
          src="https://i.pravatar.cc/150?img=12" 
          alt="James Liu" 
          class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-xs truncate">James Liu</p>
          <p class="text-gray-500 text-[10px] truncate">@jamesliu</p>
        </div>
      </a>
      
      <!-- Friend 3 -->
      <a href="/profile/emmawilson" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-50 transition-colors">
        <img 
          src="https://i.pravatar.cc/150?img=47" 
          alt="Emma Wilson" 
          class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-xs truncate">Emma Wilson</p>
          <p class="text-gray-500 text-[10px] truncate">@emmawilson</p>
        </div>
      </a>
      
      <!-- Friend 4 -->
      <a href="/profile/mikeross" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-50 transition-colors">
        <img 
          src="https://i.pravatar.cc/150?img=33" 
          alt="Mike Ross" 
          class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-xs truncate">Mike Ross</p>
          <p class="text-gray-500 text-[10px] truncate">@mikeross</p>
        </div>
      </a>
    </div>
  </div>
  
  <!-- Suggested for You Section -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 xl:p-4">
    <h3 class="font-semibold text-gray-900 text-sm mb-3">Suggested for you</h3>
    
    <div class="space-y-2">
      <!-- Suggestion 1 -->
      <div class="flex items-center gap-2">
        <img 
          src="https://i.pravatar.cc/150?img=16" 
          alt="Olivia Davis" 
          class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-xs truncate">Olivia Davis</p>
          <p class="text-gray-500 text-[10px] truncate">15 mutual</p>
        </div>
        <button class="flex items-center gap-1 px-2 py-1 bg-blue-600 text-white text-[10px] font-medium rounded-md hover:bg-blue-700 transition-colors flex-shrink-0">
          <i data-lucide="user-plus" class="w-2.5 h-2.5"></i>
          <span>Add</span>
        </button>
      </div>
      
      <!-- Suggestion 2 -->
      <div class="flex items-center gap-2">
        <img 
          src="https://i.pravatar.cc/150?img=68" 
          alt="Noah Martinez" 
          class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-xs truncate">Noah Martinez</p>
          <p class="text-gray-500 text-[10px] truncate">9 mutual</p>
        </div>
        <button class="flex items-center gap-1 px-2 py-1 bg-blue-600 text-white text-[10px] font-medium rounded-md hover:bg-blue-700 transition-colors flex-shrink-0">
          <i data-lucide="user-plus" class="w-2.5 h-2.5"></i>
          <span>Add</span>
        </button>
      </div>
      
      <!-- Suggestion 3 -->
      <div class="flex items-center gap-2">
        <img 
          src="https://i.pravatar.cc/150?img=20" 
          alt="Ava Johnson" 
          class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0"
        />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 text-xs truncate">Ava Johnson</p>
          <p class="text-gray-500 text-[10px] truncate">4 mutual</p>
        </div>
        <button class="flex items-center gap-1 px-2 py-1 bg-blue-600 text-white text-[10px] font-medium rounded-md hover:bg-blue-700 transition-colors flex-shrink-0">
          <i data-lucide="user-plus" class="w-2.5 h-2.5"></i>
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
