<?php
/**
 * Client-side approach: inject a JSON-safe version of the post and
 * current user, render the shared `post_card` element (pass PHP $post
 * so any server-side sub-elements still work), and mount the dashboard
 * Vue app by loading `/js/dashboard.js` so the card behaves identically
 * to the dashboard.
 */

$currentUser = $currentUser ?? ($this->Identity->get() ?? null);

// Convert entities/objects to arrays for safe JSON encoding
if (!isset($postArray)) {
      $postArray = (is_object($post) && method_exists($post, 'toArray')) ? $post->toArray() : (array)$post;
}
// Ensure defaults expected by the Vue app
$postArray['is_liked'] = $postArray['is_liked'] ?? false;
$postArray['like_count'] = $postArray['like_count'] ?? ($postArray['like_count'] ?? 0);
$postArray['comments'] = $postArray['comments'] ?? [];
$postArray['comment_count'] = $postArray['comment_count'] ?? count($postArray['comments']);
$currentUserArray = null;
if ($currentUser) {
            $currentUserArray = (is_object($currentUser) && method_exists($currentUser, 'toArray')) ? $currentUser->toArray() : (array)$currentUser;
}

$postJson = json_encode($postArray, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$currentUserJson = $currentUserArray !== null ? json_encode($currentUserArray, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null';
?>

<div id="dashboardApp">
      <div v-for="post in posts" :key="post.id">
            <?= $this->element('posts/post_card', ['post' => $post, 'currentUser' => $currentUser]) ?>
      </div>
</div>

<script>
      window.dashboardData = window.dashboardData || {};
      try {
            window.dashboardData.posts = [ <?= $postJson ?: 'null' ?> ];
      } catch (e) { window.dashboardData.posts = []; console.error('post json parse error', e); }

      try {
            window.dashboardData.user = <?= $currentUserJson ?: 'null' ?>;
            window.dashboardData.postCount = window.dashboardData.postCount || 1;
      } catch (e) { console.error('currentUser injection error', e); }
</script>

<?= $this->Html->script('dashboard') ?>

<?php
   
