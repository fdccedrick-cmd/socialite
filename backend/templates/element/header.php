<?php
/** @var \App\View\AppView $this */
$user = $currentUser ?? $user ?? null;
$username = $user->full_name ?? $user->username ?? 'Guest';
$avatar = $user->profile_photo_path ?? 'https://i.pravatar.cc/150?img=1';
?>
<div id="headerApp" class="fixed top-0 left-0 w-full bg-white border-b z-50 shadow-sm" v-cloak>
  <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8">
    <div class="flex items-center justify-between h-14 sm:h-16">
      <!-- Left: Logo + Search -->
       <div class="flex items-center flex-1 gap-2 sm:gap-4">
  <!-- Logo -->
  <a href="/" class="flex items-center gap-1.5 sm:gap-3 shrink-0">
    <svg class="h-7 w-7 sm:h-9 sm:w-9 text-indigo-600" viewBox="0 0 24 24" fill="none">
      <path d="M21 12c0 4.97-4.03 9-9 9-1.5 0-2.92-.36-4.18-1L3 21l1.03-4.82C3.36 14.92 3 13.5 3 12 3 7.03 7.03 3 12 3s9 4.03 9 9z" stroke="currentColor" stroke-width="1.5"/>
    </svg>
    <span class="font-bold text-lg sm:text-2xl text-gray-800">Socialite</span>
  </a>

  <!-- Search -->
  <div class="flex-1 hidden md:flex">
    <form action="/search" method="get" class="w-full">
        <div class="relative w-80">
        <i data-lucide="search" class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2"></i>
        <input
          id="header-search"
          name="q"
          type="search"
          placeholder="Search people, posts, groups"
          class="pl-12 pr-4 py-2 rounded-full border border-gray-200 bg-white text-sm text-gray-700 shadow-sm w-full focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </div>
    </form>
  </div>
        <button @click="focusSearch" class="md:hidden p-1.5 sm:p-2 rounded hover:bg-gray-100" title="Search">
          <i data-lucide="search" class="h-4 w-4 sm:h-5 sm:w-5 text-gray-700"></i>
        </button>
      </div>

      <!-- Center: Nav icons -->
      

      <!-- Right: actions -->
      <div class="flex items-center gap-1 sm:gap-2 lg:gap-4">
        <div class="hidden lg:flex items-center gap-2">
        <a href="/dashboard" class="px-3 py-2 rounded-md hover:bg-gray-100 text-gray-700" title="Home">
          <i data-lucide="home" class="h-6 w-6"></i>
        </a>
        <a href="/explore" class="px-3 py-2 rounded-md hover:bg-gray-100 text-gray-700" title="Explore">
          <i data-lucide="compass" class="h-6 w-6"></i>
        </a>
        <a href="/posts/new" class="px-3 py-2 rounded-md hover:bg-gray-100 text-gray-700" title="Create">
          <i data-lucide="plus" class="h-6 w-6"></i>
        </a>
      </div>
        <!-- Notifications -->
        <a href="/notifications" class="relative p-1.5 sm:p-2 rounded hover:bg-gray-100" title="Notifications">
          <i data-lucide="bell" class="h-5 w-5 sm:h-6 sm:w-6 text-gray-700"></i>
          <span v-if="notificationCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] sm:text-xs rounded-full px-1 sm:px-1.5 min-w-[16px] sm:min-w-[20px] text-center">{{ notificationCount }}</span>
        </a>

        <!-- Messages -->
        <!-- <a href="/messages" class="relative p-2 rounded hover:bg-gray-100" title="Messages">
          <svg class="h-6 w-6 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2z"/></svg>
          <span v-if="messageCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-1.5">{{ messageCount }}</span>
        </a> -->

        <!-- Avatar / username / dropdown -->
        <div class="relative">
            <button @click="toggle" class="flex items-center gap-1.5 sm:gap-3 p-0.5 sm:p-1 rounded hover:bg-gray-100 focus:outline-none" aria-label="User menu">
            <img :src="avatar" :alt="username" class="h-7 w-7 sm:h-9 sm:w-9 rounded-full object-cover border"/>
            <div class="hidden md:flex flex-col leading-tight">
              <span class="text-sm font-medium text-gray-800"><?= h($user ? ($user->full_name ?? $user->username) : 'Guest') ?></span>
              <span class="text-xs text-gray-500">@<?= h($user->username ?? '') ?></span>
            </div>
            <i data-lucide="chevron-down" class="h-4 w-4 text-gray-500 hidden md:inline"></i>
          </button>

          <div v-if="open" class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
            <div class="py-2 text-sm text-gray-700">
              <div class="px-4 py-3 border-b">
                <div class="font-medium"><?= h($username) ?></div>
                <div class="text-xs text-gray-500">View profile and settings</div>
              </div>
              <a href="/profile" class="block px-4 py-2 hover:bg-gray-50">Profile</a>
              <a href="/settings" class="block px-4 py-2 hover:bg-gray-50">Settings</a>
              <a href="/saved" class="block px-4 py-2 hover:bg-gray-50">Saved</a>
              <form method="post" action="/logout" class="m-0">
                <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-50">Logout</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/lucide@0.259.0/dist/lucide.min.js"></script>
  <script>document.addEventListener('DOMContentLoaded', function(){ if (window.lucide) lucide.createIcons(); });</script>

  <script>
    (function () {
      if (typeof Vue === 'undefined') return;
      const el = document.getElementById('headerApp');
      if (!el) return;
      const app = Vue.createApp({
        data() {
          return {
            open: false,
            username: <?= json_encode($username) ?>,
            avatar: <?= json_encode($avatar) ?>,
            notificationCount: 0,
            messageCount: 0
          };
        },
        methods: {
          toggle() { this.open = !this.open; },
          close() { this.open = false; },
          focusSearch() {
            const s = document.getElementById('header-search');
            if (s) { s.focus(); } else { /* fallback: open a quick search modal later */ }
          }
        },
        mounted() {
          document.addEventListener('click', (e) => {
            if (!el.contains(e.target)) this.close();
          });
          // optionally fetch counts via API (uncomment and implement endpoint)
          // fetch('/api/notifications/count').then(r=>r.json()).then(d=>{ this.notificationCount = d.count })
        }
      });
      app.mount(el);
    })();
  </script>
</div>
