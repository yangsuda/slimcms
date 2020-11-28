<?php
declare(strict_types=1);

namespace SlimCMS\Core;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use SlimCMS\Interfaces\RouteInterface;
use App\Core\Request;
use App\Core\Response;

class Routes implements RouteInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(App $app)
    {
        return $this->route($app);
    }

    /**
     * {@inheritdoc}
     */
    public function route(App $app)
    {
        $route = function (ServerRequestInterface $req, ResponseInterface $res) use ($app) {
            $request = new Request($req, $res, $app);
            $response = new Response($req, $res, $app);
            $p = $request->input('p');
            if (empty($p)) {
                $p = 'index';
            }
            $path = str_replace('/', '\\', dirname($p));
            $path = trim($path, '\.');
            $controlname = $path ? basename($path) : basename($p);
            $path = empty($path) || $path == $controlname ? '' : dirname($path) . '\\';
            $controlname = ucfirst($controlname);
            $controlpath = '\App\Control\\' . CURSCRIPT . '\\' . $path;
            $classname = $controlpath . $controlname . 'Control';
            if (!class_exists($classname)) {
                $classname = $controlpath . 'DefaultControl';
                if (!class_exists($classname)) {
                    $classname = '\App\Control\\' . CURSCRIPT . '\\' . 'DefaultControl';
                    if (!class_exists($classname)) {
                        $classname = '\App\Control\\' . 'DefaultControl';
                    }
                }
            }
            $method = basename($p);
            $obj = new $classname($request, $response);
            if (!is_callable([$obj, $method])) {
                $classname = '\App\Control\\' . 'DefaultControl';
                $obj = new $classname($request, $response);
            }
            if (!is_callable([$obj, $method])) {
                return $response->output($request->getOutput()->withCode(21009));
            }
            return $obj->$method();
        };
        $app->get('/[{params:.*}]', $route);
        $app->post('/[{params:.*}]', $route);
    }
}
