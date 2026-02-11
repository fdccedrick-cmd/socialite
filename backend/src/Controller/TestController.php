<?php
declare(strict_types=1);

namespace App\Controller;

class TestController extends AppController
{
    public function index()
    {
        // Use autoRender = false to prevent view rendering
        $this->autoRender = false;
        
        $html = "<!DOCTYPE html><html><head><title>Socialite Test</title></head><body>";
        $html .= "<h1>✓ CakePHP Route Works!</h1>";
        $html .= "<p>PHP Version: " . PHP_VERSION . "</p>";
        $html .= "<p><a href='/login'>Go to Login</a></p>";
        $html .= "<p><a href='/register'>Go to Register</a></p>";
        $html .= "</body></html>";
        
        $this->response = $this->response->withStringBody($html);
        return $this->response;
    }
    
    public function view()
    {
        // This will try to use a template
        $this->set('message', 'Testing view rendering');
    }
}
