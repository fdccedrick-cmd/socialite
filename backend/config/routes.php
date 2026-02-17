<?php
declare(strict_types=1);

use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes): void {
    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Users', 'action' => 'login']);
        $builder->connect('/login', ['controller' => 'Users', 'action' => 'login']);
        $builder->connect('/register', ['controller' => 'Users', 'action' => 'register']);
        $builder->connect('/logout', ['controller' => 'Users', 'action' => 'logout']);
        $builder->connect('/dashboard', ['controller' => 'Dashboard', 'action' => 'index']);
        
        // Profile routes
        $builder->connect('/profile', ['controller' => 'Profile', 'action' => 'view']);
        $builder->connect('/profile/{id}', ['controller' => 'Profile', 'action' => 'view'], ['pass' => ['id']]);
        $builder->connect('/profile/update', ['controller' => 'Profile', 'action' => 'update']);
        
        // Posts routes
        $builder->connect('/posts/create', ['controller' => 'Posts', 'action' => 'create']);
        // View a single post by id
        $builder->connect('/posts/{id}', ['controller' => 'Posts', 'action' => 'view'], ['pass' => ['id']]);
        // Render a single post as an element (for AJAX or embedding in other templates)
        $builder->connect('/posts/get-any/{id}', ['controller' => 'Posts', 'action' => 'getAnyPost'], ['pass' => ['id']]);
        $builder->connect('/posts/edit/{id}', ['controller' => 'Posts', 'action' => 'edit'], ['pass' => ['id']]);
        $builder->connect('/posts/delete/{id}', ['controller' => 'Posts', 'action' => 'delete'], ['pass' => ['id']]);
        
        // Comments routes
        $builder->connect('/comments/add', ['controller' => 'Comments', 'action' => 'add']);
        // Direct comment link (redirects to post view with anchor)
        $builder->connect('/comments/{id}', ['controller' => 'Comments', 'action' => 'view'], ['pass' => ['id']]);
        $builder->connect('/comments/edit/{id}', ['controller' => 'Comments', 'action' => 'edit'], ['pass' => ['id']]);
        $builder->connect('/comments/delete/{id}', ['controller' => 'Comments', 'action' => 'delete'], ['pass' => ['id']]);
        $builder->connect('/comments/get-by-post/{postId}', ['controller' => 'Comments', 'action' => 'getByPost'], ['pass' => ['postId']]);
        
        // Likes routes
        $builder->connect('/likes/toggle-post/{id}', ['controller' => 'Likes', 'action' => 'togglePost'], ['pass' => ['id']]);
        $builder->connect('/likes/toggle-comment/{id}', ['controller' => 'Likes', 'action' => 'toggleComment'], ['pass' => ['id']]);
        $builder->connect('/likes/post/{id}', ['controller' => 'Likes', 'action' => 'getPostLikes'], ['pass' => ['id']]);
        $builder->connect('/likes/comment/{id}', ['controller' => 'Likes', 'action' => 'getCommentLikes'], ['pass' => ['id']]);
        
        // Notifications routes
        $builder->connect('/notifications', ['controller' => 'Notifications', 'action' => 'index']);
        
        // Notifications API routes
        $builder->connect('/api/notifications/recent', ['controller' => 'Notifications', 'action' => 'recent']);
        $builder->connect('/api/notifications/count', ['controller' => 'Notifications', 'action' => 'count']);
        $builder->connect('/api/notifications/mark-read/{id}', ['controller' => 'Notifications', 'action' => 'markAsRead'], ['pass' => ['id']]);
        $builder->connect('/api/notifications/mark-all-read', ['controller' => 'Notifications', 'action' => 'markAllAsRead']);
        
        $builder->fallbacks();
    });
};
