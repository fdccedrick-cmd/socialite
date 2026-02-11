<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

// Check platform requirements
require dirname(__DIR__) . '/config/requirements.php';

// For built-in server
if (php_sapi_name() === 'cli-server') {
    if ($_SERVER['REQUEST_URI'] !== '/' && file_exists(__DIR__ . $_SERVER['REQUEST_URI'])) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Application;
use Cake\Http\Server;

/*
 * Bootstrap the application.
 */
$app = new Application(dirname(__DIR__) . '/config');

/*
 * Create the server with the application
 */
$server = new Server($app);

/*
 * Run the request/response through the application
 * and emit the response.
 */
$server->emit($server->run());
