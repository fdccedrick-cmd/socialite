<div class="max-w-4xl mx-auto py-4 px-3 sm:px-6">
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="text-lg sm:text-xl font-bold text-gray-900">Notifications</h2>
      <?php if (!empty($notifications) && count($notifications) > 0): ?>
        <?= $this->Form->postLink(
          'Mark all as read',
          ['action' => 'markAllAsRead'],
          [
            'class' => 'text-sm text-blue-600 hover:text-blue-700 font-medium',
            'confirm' => 'Mark all notifications as read?'
          ]
        ) ?>
      <?php endif; ?>
    </div>

    <!-- Notifications List -->
    <div class="divide-y divide-gray-100">
      <?php if (empty($notifications) || count($notifications) === 0): ?>
        <div class="px-4 sm:px-6 py-12 text-center">
          <i data-lucide="bell-off" class="w-16 h-16 text-gray-300 mx-auto mb-3"></i>
          <p class="text-gray-500 text-sm">No notifications yet</p>
        </div>
      <?php else: ?>
        <?php foreach ($notifications as $notification): ?>
          <div class="px-4 sm:px-6 py-4 hover:bg-gray-50 transition-colors <?= $notification->is_read ? 'opacity-60' : 'bg-blue-50' ?>">
            <div class="flex items-start gap-3">
              <!-- Actor Avatar -->
              <?php 
                $actorAvatar = 'https://i.pravatar.cc/150?img=' . ($notification->actor_id % 70 + 1);
                if (isset($notification->actor) && !empty($notification->actor->profile_photo_path)) {
                    $actorAvatar = $notification->actor->profile_photo_path;
                }
              ?>
              <img 
                src="<?= h($actorAvatar) ?>" 
                alt="<?= h($notification->actor->full_name ?? $notification->actor->username ?? 'User') ?>"
                class="w-10 h-10 sm:w-12 sm:h-12 rounded-full object-cover border-2 border-gray-100 flex-shrink-0"
                onerror="this.src='https://i.pravatar.cc/150?img=1'"
              />
              
              <!-- Notification Content -->
              <div class="flex-1 min-w-0">
                <p class="text-sm sm:text-base text-gray-900 mb-1">
                  <?= $notification->message ?>
                </p>
                <p class="text-xs text-gray-500">
                  <?= $notification->created->timeAgoInWords() ?>
                </p>
              </div>

              <!-- Type Icon -->
              <div class="flex-shrink-0">
                <?php 
                  $iconMap = [
                    'like' => ['icon' => 'heart', 'color' => 'text-red-500'],
                    'comment' => ['icon' => 'message-circle', 'color' => 'text-blue-500'],
                    'reply' => ['icon' => 'corner-down-right', 'color' => 'text-green-500'],
                    'follow' => ['icon' => 'user-plus', 'color' => 'text-purple-500'],
                    'mention' => ['icon' => 'at-sign', 'color' => 'text-yellow-500'],
                    'share' => ['icon' => 'share-2', 'color' => 'text-indigo-500'],
                  ];
                  $icon = $iconMap[$notification->type] ?? ['icon' => 'bell', 'color' => 'text-gray-500'];
                ?>
                <i data-lucide="<?= $icon['icon'] ?>" class="w-5 h-5 <?= $icon['color'] ?>"></i>
              </div>

              <!-- Delete Button -->
              <div class="flex-shrink-0">
                <?= $this->Form->postLink(
                  '<i data-lucide="x" class="w-4 h-4"></i>',
                  ['action' => 'delete', $notification->id],
                  [
                    'confirm' => 'Delete this notification?',
                    'escape' => false,
                    'class' => 'text-gray-400 hover:text-red-500 transition-colors'
                  ]
                ) ?>
              </div>
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
