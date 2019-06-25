<?php
/**
 * 子server
 *
 * @author
 */
namespace hyf\init\job;

class server
{
    public static function run()
    {
        $services = [];
        foreach (glob(app_dir()  . 'server/*.php') as $service) {
            array_push($services, "application\\" . app_name() . "\\server\\" . basename($service, '.php'));
        }
        if (!empty($services)) {
            foreach ($services as $service_val) {
                $service = new $service_val();
                $serv = server()->listen($service->server_config['host'], $service->server_config['port'], SWOOLE_SOCK_TCP);
                $serv->set($service->server_config['server_set']);
                $serv->on('request', function ($request, $response) use ($service) {
                    $service->onRequest($request, $response);
                });
            }
        }
    }
}
