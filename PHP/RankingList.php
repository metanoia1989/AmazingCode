<?php

namespace app\common;

/**
 * 活动的投票排行榜
 */
class RankingList 
{
    protected $redis;
    /**
     * 有序列表的键
     *
     * @var string
     */
    protected $key;

    /**
     * 初始化排行榜类
     *
     * @param integer $event_id 活动ID
     */
    public function __construct(int $event_id)
    {
        $this->key = "event@ranking_list@{$event_id}"; 
        $this->redis = RedisSingleton::getInstance();        
    }  

    /**
     * 初始化选手票数
     *
     * @param array $votes 二维关联数组 voting_object_id, num
     * @return void
     */
    public function initVotes(array $votes)
    {
        foreach ($votes as $item) {
            $this->redis->zAdd($this->key, $item["num"], $item["voting_object_id"]);
        }  
    }

    /**
     * 清除内存统计数据
     *
     * @return void
     */
    public function clear() 
    {
        $this->redis->del($this->key); 
    }
    

    /**
     * 投票
     *
     * @param integer $vo_id 作品或选手ID
     * @param integer $num 票数 默认 1
     * @return integer
     */
    public function upVote(int $vo_id, int $num = 1) 
    {
        return $this->redis->zIncrBy($this->key, $num, $vo_id);
    } 
       
    /**
     * 获取票数
     *
     * @param integer $vo_id
     * @return integer
     */
    public function getVote(int $vo_id) : int
    {
        return $this->redis->zScore($this->key, $vo_id); 
    }

    /**
     * 获取选手排名 - 降序
     *
     * @param integer $vo_id
     * @return integer
     */
    public function getRank(int $vo_id) : int
    {
        return $this->redis->zRevRank($this->key, $vo_id); 
    }

    /**
     * 获取指定排名范围的成员，从高到底
     *
     * @param integer $start
     * @param integer $stop
     * @return array
     */
    public function getRangeRank($start, $stop) : array
    {
        return $this->redis->zRevRange($this->key, $start, $stop); 
    }
}