<?php
/**
 * Render a single post using the existing post element
 * Expects: $post
 */
echo $this->element('posts/post_card', ['post' => $post]);
