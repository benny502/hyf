<?php
/**
 * 子server
 *
 * @author Makle <zhang.tao@hylinkad.com>
 */
namespace hyf\jobs;

class server
{

    private static function list($app_name)
    {
        $service_array = [];
        foreach (glob(\Hyf::$dir . 'application/' . $app_name . '/server/*.php') as $service) {
            array_push($service_array, "application\\{$app_name}\\server\\" . basename($service, '.php'));
        }
        return $service_array;
    }

    public static function run($server, $app_name)
    {
        $services = self::list($app_name);
        if (!empty($services)) {
            foreach ($services as $service_val) {
                $service = new $service_val();
                $serv = $server->listen($service->server_config['host'], $service->server_config['port'], SWOOLE_SOCK_TCP);
                $serv->set($service->server_config['server_set']);
                $serv->on('request', function ($request, $response) use ($service) {
                    $service->onRequest($request, $response);
                });
            }
        }
    }
}