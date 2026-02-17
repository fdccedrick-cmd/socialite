<!-- Notifications Dropdown -->
<div v-if="notificationsOpen" @click.stop class="origin-top-right absolute right-0 mt-2 w-80 sm:w-96 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
  <div class="py-2">
    <!-- Header -->
    <div class="px-4 py-3 border-b flex items-center justify-between">
      <h3 class="font-semibold text-gray-900">Notifications</h3>
      <button @click="markAllAsRead" class="text-xs text-indigo-600 hover:text-indigo-700">Mark all read</button>
    </div>

    <!-- Notifications List -->
    <div class="max-h-96 overflow-y-auto">
      <div v-if="notifications.length === 0" class="px-4 py-8 text-center text-gray-500 text-sm">
        <i data-lucide="bell-off" class="h-12 w-12 mx-auto mb-2 text-gray-300"></i>
        <p>No notifications yet</p>
      </div>

      <div 
        v-for="notif in notifications" 
        :key="notif.id"
        @click="handleNotificationClick(notif.id)"
+       @click="handleNotificationClick(notif)"
        :class="notif.is_read ? 'bg-white' : 'bg-blue-50'"
        class="px-4 py-3 hover:bg-gray-50 border-b last:border-b-0 transition-colors cursor-pointer"
      >
        <div class="flex gap-3">
          <img 
            :src="notif.actor_avatar || 'https://i.pravatar.cc/150?img=' + (notif.actor_id % 70 + 1)" 
            :alt="notif.actor_username"
            class="h-10 w-10 rounded-full flex-shrink-0 object-cover border border-gray-200" 
            @error="$event.target.src='https://i.pravatar.cc/150?img=1'"
          />
          <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-1">
              <p class="text-sm text-gray-900 font-bold" v-html="notif.actor_full_name"></p>
              <p class="text-sm text-gray-900" v-html="notif.message"></p>
            </div>

            <p class="text-xs text-gray-500 mt-1">{{ formatTime(notif.created) }}</p>
          </div>
          <div v-if="!notif.is_read" class="flex-shrink-0">
            <span class="h-2 w-2 bg-blue-600 rounded-full block"></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="px-4 py-3 border-t bg-gray-50">
      <a href="/notifications" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium block text-center">View all notifications</a>
    </div>
  </div>
</div>

