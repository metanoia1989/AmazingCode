<?php
// +----------------------------------------------------------------------
// | LikeShop100%开源免费商用电商系统
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | 商业版本务必购买商业授权，以免引起法律纠纷
// | 禁止对系统程序代码以任何目的，任何形式的再发布
// | Gitee下载：https://gitee.com/likeshop_gitee/likeshop
// | 访问官网：https://www.likemarket.net
// | 访问社区：https://home.likemarket.net
// | 访问手册：http://doc.likemarket.net
// | 微信公众号：好象科技
// | 好象科技开发团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------

// | Author: LikeShopTeam
// +----------------------------------------------------------------------

namespace app\common\command;

use Cron\CronExpression;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Console;
use think\facade\Db;
use think\facade\Log;

$sql = <<<SQL
CRON Expressions
================

A CRON expression is a string representing the schedule for a particular command to execute.  The parts of a CRON schedule are as follows:

    *    *    *    *    *
    -    -    -    -    -
    |    |    |    |    |
    |    |    |    |    |
    |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
    |    |    |    +---------- month (1 - 12)
    |    |    +--------------- day of month (1 - 31)
    |    +-------------------- hour (0 - 23)
    +------------------------- min (0 - 59)

This library also supports a few macros:

* `@yearly`, `@annually` - Run once a year, midnight, Jan. 1 - `0 0 1 1 *`
* `@monthly` - Run once a month, midnight, first of month - `0 0 1 * *`
* `@weekly` - Run once a week, midnight on Sun - `0 0 * * 0`
* `@daily` - Run once a day, midnight - `0 0 * * *`
* `@hourly` - Run once an hour, first minute - `0 * * * *`

-- ----------------------------
-- Table structure for ls_dev_crontab
-- ----------------------------
DROP TABLE IF EXISTS `ls_dev_crontab`;
CREATE TABLE `ls_dev_crontab`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type` tinyint(1) NULL DEFAULT NULL COMMENT '类型：1-定时任务；2-守护进程',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '备注',
  `command` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '命令内容',
  `parameter` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '参数',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：1-运行；2-停止；3-错误；',
  `expression` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '运行规则',
  `error` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '运行失败原因',
  `create_time` int(11) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '最后执行时间',
  `last_time` int(11) NULL DEFAULT NULL COMMENT '最后执行时间',
  `time` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '实时执行时长',
  `max_time` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '最大执行时长',
  `system` tinyint(4) NULL DEFAULT 0 COMMENT '是否系统任务：0-否；1-是；',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ----------------------------
-- Records of ls_dev_crontab
-- ----------------------------
BEGIN;
INSERT INTO `ls_dev_crontab` VALUES 
(1, '测试', 1, '1111', 'distribution_order', '', 1, '0 23 * * *', '', 1623383659, 1623383659, 1625756402, '0', '0', 0), 
(2, '关闭超时订单', 1, '', 'order_close', '', 1, '* * * * *', '', 1624533266, 1624533266, 1625810461, '0', '0', 0), 
(3, '自动确认收货', 1, '', 'order_finish', '', 1, '0 */10 * * *', '', 1624534134, 1624616768, 1625796002, '0', '0', 0), 
(4, '结算分销订单', 1, '', 'distribution_order', '', 1, '* * * * *', '', 1624534522, 1624590654, 1625810461, '0', '0', 0), 
(5, '更新会员分销信息', 1, '', 'user_distribution', '', 1, '0 23 * * *', '', 1624590055, 1624601622, 1625756402, '0', '0', 0), 
(6, '关闭砍价记录', 1, '', 'bargain_close', '', 1, '* * * * *', '', 1625464882, 1625465895, 1625810461, '0', '0', 0), 
(7, '拼团超时关闭', 1, '', 'team_end', '', 3, '* * * * *', '', 1625644063, 1625740476, 1625805121, '0', '0', 0);
COMMIT;
SQL;


class Crontab extends Command
{
    protected function configure()
    {
        $this->setName('crontab')
            ->setDescription('定时任务');
    }

    /**
     * 启动定时任务守护进程
     * @param Input $input
     * @param Output $output
     * @return int|void|null
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function execute(Input $input, Output $output)
    {
        Log::close();

        $time = time();

        $crons = Db::name('dev_crontab')
            ->where(['status' => 1])
            ->select();
        if (empty($crons)) {
            return;
        }

        foreach ($crons as $cron) {
            //规则错误，不执行
            if (CronExpression::isValidExpression($cron['expression']) === false) {
                continue;
            }
            //未到时间，不执行
            $cron_expression = CronExpression::factory($cron['expression']);
            $next_time = $cron_expression->getNextRunDate(date('Y-m-d H:i:s', $cron['last_time']))->getTimestamp();
            if ($next_time >= $time) {
                continue;
            }

            //开始执行
            try {
//                Debug::remark('begin');
                $parameter = explode(' ', $cron['parameter']);
                if (is_array($parameter) && !empty($cron['parameter'])) {
                    Console::call($cron['command'], $parameter);
                } else {
                    Console::call($cron['command']);
                }
                Db::name('dev_crontab')
                    ->where(['id' => $cron['id']])
                    ->update(['error' => '']);
            } catch (\Exception $e) {
                Db::name('dev_crontab')
                    ->where(['id' => $cron['id']])
                    ->update(['error' => $e->getMessage(), 'status' => 3]);
            } finally {
//                Debug::remark('end');
//                $range_time = Debug::getRangeTime('begin', 'end');
//                $max_time = max($cron['max_time'], $range_time);
                Db::name('dev_crontab')
                    ->where(['id' => $cron['id']])
                    ->update(['last_time' => $time]);
            }
        }
    }


}