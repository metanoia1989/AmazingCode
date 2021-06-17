<?php

namespace app\command; 

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\Debug;

/**
 * 喜帖埋点数据报表生成 
 */
class Invistats extends Command
{
    protected $connection = "db_invitation";
    protected $datapoint = "dp_user_handle_record"; 
    protected $reportstats = "dp_invitation_statistics"; 
    protected $db;

    /**
     * 埋点类型
     *
     * @var array
     */
    protected $idents; 

    protected function configure()
    {
        //这里的setName和php文件名一致,setDescription随意
        $this->setName('Invistats')
            ->setDescription("喜帖埋点数据报表生成")
            ->addOption("first", "f", Option::VALUE_NONE, "是否是第一次生成报表，第一次生成会查询整张数据报表"); 
    }

    protected function execute(Input $input, Output $output)
    {
        /*** 这里写计划任务列表集 START ***/
        $output->writeln('喜帖埋点数据报表生成 start...');
        Debug::remark("begin");

        $data = Db::table("dq_buried_point_ident")->select();
        $this->idents = Db::table("dq_buried_point_ident")->select();
        if ($this->idents) {
            $this->idents = array_column($this->idents, "ident_name", "id");
        }

        $first = $input->hasOption("first");
        if ($first) {
            $output->writeln('超级棒，是第一次生成，解析整个埋点数据表...');
        } else {
            $output->writeln('哈哈，之前已经生成过了，现在只解析昨天的数据...');
        }
        $this->makeStats($first);

        Debug::remark("end");
        $executeTime = Debug::getRangeTime("begin", "end")."s";
        /*** 这里写计划任务列表集 END ***/
        $output->writeln("喜帖埋点数据报表生成，运行{$executeTime} end...");
    }
    
    /**
     * 生成完整埋点数据的报表
     *
     * @return void
     */
    private function makeStats($first = false)
    {
        $yesterday = date('d') - 1;
        $yesterday =  mktime(0, 0, 0, date('m'), $yesterday, date('Y'));
        if ($first) {
            Db::execute("truncate {$this->reportstats}");
            $where = ["date" => ["<=", $yesterday] ];
        }  else {
            $where = ["date" => $yesterday];
        }
        
        // 埋点数据查询
        $data = Db::table($this->datapoint)
            ->field("FROM_UNIXTIME(`date`, '%Y-%m-%d') as curr_date, sid, ident, COUNT(DISTINCT open_id) AS num")
            ->group("sid, ident, `date`")
            ->orderRaw("date ASC, sid ASC, ident ASC")
            ->where($where)
            ->select();

        // 整合处理埋点数据，合并 curr_date 和 sid 一致的数据项
        $stats = [];
        foreach ($data as $item) {
            $key = "{$item['curr_date']}::{$item['sid']}";
            if(!isset($stats[$key])) {
                $stats[$key] = [];
            }
            $ident = $this->idents[$item["ident"]] ?? null;
            if (is_null($ident)) {
                continue;
            }
            $stats[$key][$ident] = $item["num"];
        }

        // 生成数据插入项
        $rows = [];
        foreach ($stats as $key => $item) {
            list($curr_date, $sid) = explode("::", $key);
            $statistics = json_encode($item); 
            $rows[] = [
                "creation_date" => $curr_date,
                "sid" => $sid,
                "statistics" => $statistics
            ];
        }
        $chunks = array_chunk($rows, 100);
        foreach($chunks as $chunk) {
            Db::table($this->reportstats)->insertAll($chunk);
        }
    }
}