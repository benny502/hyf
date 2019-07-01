<?php
/**
 * 自定义Process
 * 可以用来处理特殊任务
 *  
 *  @author
 */
namespace hyf\init\job;

class process
{

    public static function run()
    {
        $process_map = [];
        // process
        foreach (glob(app_dir() . 'process/process_*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(root_path(), '', $start_file)));
            array_push($process_map, $class);
        }
        foreach (glob(app_dir() . 'process/*/process_*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(root_path(), '', $start_file)));
            array_push($process_map, $class);
        }
        if (!empty($process_map)) {
            foreach ($process_map as $p) {
                $process = new \Swoole\Process(function ($process) use ($p) {
                    $p_object = new $p();
                    swoole_set_process_name(server_config('process_name')['base'] . '_process[' . $process->id . '][' . $p_object->name . ']');
                    $p_object->run();
                });
                server()->addProcess($process);
            }
        }
    }
}
