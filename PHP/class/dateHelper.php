<?php
/**
* dateHelper
*
* 为了统一，这里所有的时间都是用时间戳来代替的
*/
class dateHelper
{
    /**
     * 格式化成字符串
     *
     * 可以根据需求来修改程序，这里只实现了几[年|月|天|小时|分钟|秒]前的功能。
     * $format 留作备用字段，不需要可以删除。
     *
     * <code>
     * echo (new dateHelper())->toString(1510037401);
     * </code>
     * 
     * @param  string $timestamp
     * @param  string $format
     * @return string
     */
    public function toString($timestamp, $format = 'Y-m-d H:i:s')
    {
        $diff = $this->diff($timestamp, time());

        if ($diff->y) {
            return $diff->y . '年前';
        }

        if ($diff->m) {
            return $diff->m . '月前';
        }

        if ($diff->d) {
            return $diff->d . '天前';
        }

        if ($diff->h) {
            return $diff->h . '小时前';
        }

        if ($diff->i) {
            return $diff->i . '分钟前';
        }

        if ($diff->s) {
            return $diff->s . '秒前';
        }

        return '刚刚';
    }

    /**
     * 获取年龄
     *
     * 这里返回的是实际年龄，满一年才算一岁
     * 
     * <code>
     * echo (new dateHelper())->getAge(strtotime('1993-01-23'));
     * </code>
     * 
     * @param  string $timestamp
     * @return integer
     */
    public function getAge($timestamp)
    {
        $now = new \DateTime();
        $before = new \DateTime('@' . $timestamp);

        $diff = $now->diff($before);

        return ($this->diff($timestamp, time()))->y;
    }

    /**
     * 比较两个时间戳的不同
     *
     * <code>
     * (new dateHelper())->diff($start, $end);
     * </code>
     * 
     * @param  string $start
     * @param  string $end
     * @return \DateInterval
     */
    public function diff($start, $end)
    {
        $start = new \DateTime('@' . $start);
        $end = new \DateTime('@' . $end);

        return $end->diff($start);
    }
}

/**
* dateHelperV2
*
* 刚刚 | 2秒前 | 3分钟前 | 4小时前 | 5天前 | 半个月前 | 1个月前 | 3个月前 | 半年前 | 1年前
*/
class dateHelperV2
{
    const DAY = 86400;
    const HOUR = 3600;
    const MINUTE = 60;

    /**
     * 格式化时间
     *
     * <code>
     * (new dateHelperV2())->formatDate('2018-11-09')
     * </code>
     * 
     * @param  string $date 时间
     * @return string
     */
    public function formatDate($date)
    {
        $time = strtotime($date);
        return $this->format($time);
    }

    /**
     * 格式化时间戳
     *
     * <code>
     * (new dateHelperV2())->formatTime(1541732229)
     * </code>
     * 
     * @param  integer $time 时间戳
     * @return string
     */
    public function formatTime($time)
    {
        return $this->format($time);
    }

    /**
     * 格式化
     * 
     * @param  integer $time 时间戳
     * @return string
     */
    protected function format($time)
    {
        $current = time();
        $diff = $current - $time;

        if ($diff <= 0) {
            return '刚刚';
        }

        // 先计算天
        $day = floor($diff / self::DAY);
        if ($day) {
            if ($day < 15) {
                return "{$day}天前";
            }
            if ($day < 30) {
                return "半个月前";
            }
            if ($day < 90) {
                return "1个月前";
            }
            if ($day < 187) {
                return "3个月前";
            }
            if ($day < 365) {
                return "半年前";
            }
            if ($day >= 365) {
                return "1年前";
            }
        }

        // 计算小时
        $rest = $diff % self::DAY;
        $hour = floor($rest / self::HOUR);
        if ($hour) {
            return "{$hour}小时前";
        }

        // 计算分钟
        $rest = $hour % self::HOUR;
        $minute = floor($rest / self::MINUTE);
        if ($minute) {
            return "{$minute}分钟前";
        }

        // 计算秒
        $second = $rest % self::MINUTE;
        return $second ? "{$second}秒前" : '刚刚';
    }
}