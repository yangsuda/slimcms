<?php
declare(strict_types=1);

namespace App\Core;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

class Routes extends \SlimCMS\Core\Routes
{
    public function route(App $app)
    {
        $app->options('/[{params:.*}]', function (ServerRequestInterface $req, ResponseInterface $res) {
            return $res;
        });
        $app->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], '/[{params:.*}]', $this->routeCallable($app));
    }
}
