<?php
declare(strict_types=1);

use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes): void {
    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Users', 'action' => 'login']);
        $builder->connect('/login', ['controller' => 'Users', 'action' => 'login']);
        $builder->connect('/register', ['controller' => 'Users', 'action' => 'register']);
        $builder->connect('/logout', ['controller' => 'Users', 'action' => 'logout']);
        $builder->connect('/dashboard', ['controller' => 'Users', 'action' => 'dashboard']);
        $builder->connect('/profile', ['controller' => 'Users', 'action' => 'profile']);
        $builder->connect('/users/update-profile', ['controller' => 'Users', 'action' => 'updateProfile']);
        
        // Posts routes
        $builder->connect('/posts/create', ['controller' => 'Posts', 'action' => 'create']);
        
        // Likes routes
        $builder->connect('/likes/toggle-post/{id}', ['controller' => 'Likes', 'action' => 'togglePost'], ['pass' => ['id']]);
        $builder->connect('/likes/toggle-comment/{id}', ['controller' => 'Likes', 'action' => 'toggleComment'], ['pass' => ['id']]);
        $builder->connect('/likes/post/{id}', ['controller' => 'Likes', 'action' => 'getPostLikes'], ['pass' => ['id']]);
        
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
