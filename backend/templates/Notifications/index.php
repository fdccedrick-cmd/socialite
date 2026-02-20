<div class="max-w-4xl mx-auto py-4 px-3 sm:px-6">
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <!-- Header -->
    <div class="px-4 sm:px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
      <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">Notifications</h2>
      <?php if (!empty($notifications) && count($notifications) > 0): ?>
        <?= $this->Form->postLink(
          'Mark all as read',
          ['action' => 'markAllAsRead'],
          [
            'class' => 'text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-500 font-medium',
            'confirm' => 'Mark all notifications as read?'
          ]
        ) ?>
      <?php endif; ?>
    </div>

    <!-- Notifications List -->
    <div class="divide-y divide-gray-100 dark:divide-gray-700">
      <?php if (empty($notifications) || count($notifications) === 0): ?>
        <div class="px-4 sm:px-6 py-12 text-center">
          <i data-lucide="bell-off" class="w-16 h-16 text-gray-300 dark:text-gray-500 mx-auto mb-3"></i>
          <p class="text-gray-500 dark:text-gray-400 text-sm">No notifications yet</p>
        </div>
      <?php else: ?>
        <?php foreach ($notifications as $notification): ?>
          <?php 
            // Build notification URL
            $notifUrl = '#';
            if ($notification->notifiable_type === 'Post') {
                $notifUrl = '/posts/' . $notification->notifiable_id;
            } elseif ($notification->notifiable_type === 'Comment') {
                $notifUrl = '/comments/' . $notification->notifiable_id;
            } elseif ($notification->notifiable_type === 'User') {
                if ($notification->type === 'friend_request') {
                    $notifUrl = '/friendships/requests';
                } elseif ($notification->type === 'friend_accept') {
                    $notifUrl = '/profile/' . $notification->actor_id;
                } else {
                    $notifUrl = '/profile/' . $notification->notifiable_id;
                }
            }
          ?>
          <div class="relative group">
            <a href="<?= h($notifUrl) ?>" class="block px-4 sm:px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors <?= $notification->is_read ? '' : 'bg-blue-50 dark:bg-blue-900/30' ?>">
              <div class="flex gap-3">
                <!-- Actor Avatar -->
                <?php 
                  $actorAvatar = '/img/default/default_avatar.jpg';
                  if (isset($notification->actor) && !empty($notification->actor->profile_photo_path)) {
                      $actorAvatar = $notification->actor->profile_photo_path;
                  }
                ?>
                <img 
                  src="<?= h($actorAvatar) ?>" 
                  alt="<?= h($notification->actor->full_name ?? $notification->actor->username ?? 'User') ?>"
                  class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0"
                  onerror="this.src='/img/default/default_avatar.jpg'"
                />
                
                <!-- Notification Content -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-gray-900 dark:text-white">
                    <span class="font-semibold"><?= h($notification->actor->full_name ?? $notification->actor->username ?? 'Unknown') ?></span><?= h($notification->message) ?>
                  </p>
                  <p class="text-xs text-gray-500 mt-1">
                    <?= $notification->created->timeAgoInWords() ?>
                  </p>
                </div>

                <!-- Unread indicator -->
                <?php if (!$notification->is_read): ?>
                  <div class="flex-shrink-0">
                    <span class="h-2 w-2 bg-blue-600 rounded-full block mt-2"></span>
                  </div>
                <?php endif; ?>
              </div>
            </a>
            
            <!-- Delete Button (shows on hover) -->
            <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
              <?= $this->Form->postLink(
                '<i data-lucide="x" class="w-4 h-4"></i>',
                ['action' => 'delete', $notification->id],
                [
                  'confirm' => 'Delete this notification?',
                  'escape' => false,
                  'class' => 'text-gray-400 hover:text-red-500 transition-colors p-1 bg-white rounded-full shadow-sm'
                ]
              ) ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Initialize Lucide icons
if (window.lucide) {
  lucide.createIcons();
}
</script>
