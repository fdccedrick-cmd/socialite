<?php
/**
 * Single Post view — reuse dashboard-style Vue hydration but load profile.js
 * so single-post pages get the same interactive behavior as profile (edit/delete).
 *
 * Variables:
 *  - $post (entity) or $postArray (array)
 *  - $currentUser
 */

$currentUser = $currentUser ?? ($this->Identity->get() ?? null);

// Convert entity to array for JSON encoding
if (!isset($postArray)) {
      $postArray = (is_object($post) && method_exists($post, 'toArray')) ? $post->toArray() : (array)$post;
}

// Ensure fields expected by the frontend
$postArray['is_liked'] = $postArray['is_liked'] ?? false;
$postArray['like_count'] = $postArray['like_count'] ?? 0;
$postArray['comments'] = $postArray['comments'] ?? [];
$postArray['comment_count'] = $postArray['comment_count'] ?? count($postArray['comments']);

// Provide postsArray (array of posts) for the client app
if (!isset($postsArray)) {
      $postsArray = [$postArray];
}

$currentUserArray = null;
if ($currentUser) {
      $currentUserArray = (is_object($currentUser) && method_exists($currentUser, 'toArray')) ? $currentUser->toArray() : (array)$currentUser;
}

$postsJson = json_encode($postsArray, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$currentUserJson = $currentUserArray !== null ? json_encode($currentUserArray, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null';
?>

<div id="profileApp">
      <div v-for="post in posts" :key="post.id" >
            <?= $this->element('posts/post_card', ['post' => $post, 'currentUser' => $currentUser]) ?>
      </div>
</div>

<script>
      // Provide data shape expected by profile.js
      window.profileData = window.profileData || {};
      try {
            window.profileData.posts = <?= $postsJson ?: '[]' ?>;
      } catch (e) { window.profileData.posts = []; console.error('post json parse error', e); }

      try {
            window.profileData.user = <?= $currentUserJson ?: 'null' ?>;
            window.profileData.postCount = window.profileData.postCount || 1;
            window.profileData.currentUserId = <?= isset($currentUserArray['id']) ? (int)$currentUserArray['id'] : 'null' ?>;
      } catch (e) { console.error('currentUser injection error', e); }
</script>

<?= $this->Html->script('profile') ?>

<?php

