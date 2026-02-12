<?php
declare(strict_types=1);

namespace App\Controller;

class TestsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        // No auth for testing
    }
    
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['index', 'simple']);
    }
    
    public function index()
    {
        $this->autoRender = false;
        echo "Test controller works!";
    }
    
    public function simple()
    {
        // Try to render a view
        $this->set('message', 'Hello from test');
    }
}
