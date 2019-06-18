<?php
namespace hyf\component\crond;

class parseCron
{

    /**
     * 解析crontab的定时格式(支持到秒)
     *
     * @param string $crontab_string
     *            0 1 2 3 4 5
     *            * * * * * *
     *            - - - - - -
     *            | | | | | |
     *            | | | | | +--- day of week (0 - 6) (Sunday=0)
     *            | | | | +----- month (1 - 12)
     *            | | | +------- day of month (1 - 31)
     *            | | +--------- hour (0 - 23)
     *            | +----------- min (0 - 59)
     *            +------------- sec (0 - 59) [秒可选参数，不填写默认为 1]
     *
     * @param int $start_time
     *            timestamp [default=current timestamp]
     * @param int $last_run_time
     *            timestamp [default=null timestamp]
     * @return int unix timestamp 当前分钟内是否需要执行任务，返回需要执行任务的秒[数组]
     */
    public static function Run($crontab_string, $start_time = null, $last_run_time = null)
    {
        // 是否符合规则
        if (!preg_match('/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i', trim($crontab_string))) {
            if (!preg_match('/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i', trim($crontab_string))) {
                return false;
            }
        }

        if ($start_time && !is_numeric($start_time)) {
            return false;
        }

        if ($last_run_time && !is_numeric($last_run_time)) {
            return false;
        }

        // 拆分时间段配置
        $cron = preg_split("/[\s]+/i", trim($crontab_string));

        // 处理类 “*/20” 配置，以第一个配置的为主，后续配置将被忽略
        if (preg_match('/\*\/[\d]+/', $crontab_string) && $start_time && $last_run_time) {
            $timest = [];
            foreach ($cron as $k => $v) {
                $cr = explode("/", $v);
                if (!empty($cr[1])) {
                    $timest = [
                        $k,
                        $cr[1]
                    ];
                    break;
                }
            }
            $each_second = 0;
            switch ($timest[0]) {
                case 0:
                    $each_second = $timest[1];
                    break;
                case 1:
                    $each_second = $timest[1] * 60;
                    break;
                case 2:
                    $each_second = $timest[1] * 60 * 60;
                    break;
                case 3:
                    $each_second = $timest[1] * 24 * 60 * 60;
                    break;
                case 4:
                    $each_second = $timest[1] * 30 * 24 * 60 * 60;
                    break;
                case 5:
                    $each_second = $timest[1] * 7 * 24 * 60 * 60;
                    break;
            }
            if ($last_run_time + $each_second == $start_time) {
                return [
                    intval(date("s", $start_time)) => intval(date("s", $start_time))
                ];
            }
            return false;
        }

        $start = empty($start_time) ? time() : $start_time;

        if (count($cron) == 6) {
            $date = array(
                'second' => self::parse_cron($cron[0], 0, 59),
                'minutes' => self::parse_cron($cron[1], 0, 59),
                'hours' => self::parse_cron($cron[2], 0, 23),
                'day' => self::parse_cron($cron[3], 1, 31),
                'month' => self::parse_cron($cron[4], 1, 12),
                'week' => self::parse_cron($cron[5], 0, 6)
            );
        } elseif (count($cron) == 5) {
            $date = array(
                'second' => array(
                    1 => 1
                ),
                'minutes' => self::parse_cron($cron[0], 0, 59),
                'hours' => self::parse_cron($cron[1], 0, 23),
                'day' => self::parse_cron($cron[2], 1, 31),
                'month' => self::parse_cron($cron[3], 1, 12),
                'week' => self::parse_cron($cron[4], 0, 6)
            );
        }

        if (in_array(intval(date('i', $start)), $date['minutes']) && in_array(intval(date('G', $start)), $date['hours']) && in_array(intval(date('j', $start)), $date['day']) && in_array(intval(date('w', $start)), $date['week']) && in_array(intval(date('n', $start)), $date['month'])) {
            return $date['second'];
        }
        return false;
    }

    /**
     * 解析单个配置，支持传入配置格式： 1 | 1,2,5,7,11 | 1-5 | 3-23,25-29,40,44,45
     *
     * @param string $s
     * @param int $min
     * @param int $max
     * @return array
     */
    private static function parse_cron($s, $min, $max)
    {
        $result = [];
        $section_array = explode(",", $s);
        foreach ($section_array as $section) {
            $in_section = explode("-", $section);
            $_min = count($in_section) == 2 ? $in_section[0] : ($section == "*" ? $min : $section);
            $_max = count($in_section) == 2 ? $in_section[1] : ($section == "*" ? $max : $section);
            for ($i = $_min; $i <= $_max; $i += 1) {
                $result[$i] = intval($i);
            }
        }
        ksort($result);
        return $result;
    }
}
