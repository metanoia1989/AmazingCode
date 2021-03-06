<?php
/**
 * 高级筛选条件
 * @author Michael_xu
 * @param  $array 条件数组
 * @param  $module 相关模块
 * @return string           
 */
function where_arr($array = [], $m = '', $c = '', $a = '')
{
    $userModel = new UserModel();
    $checkStatusList = ['待审核','审核中','审核通过','审核失败','已撤回','未提交','已作废'];
    $checkStatusArray = ['待审核' => '0','审核中'=>'1','审核通过'=>'2','审核失败'=>'3','已撤回'=>'4','未提交'=>'5','已作废'=>'6'];
    //查询自定义字段模块多选字段类型
    $check_field_arr = [];
    //特殊字段

    //过滤系统参数
    $unset_arr = ['page','limit','order_type','order_field'];
    if (!is_array($array)) {
       return []; 
    }
    $types = $c;
    foreach ($array as $k=>$v) {
        if (!in_array($k, $unset_arr)) {
            $c = $types.'.';
            if ($k == 'customer_name') {
                $k = 'name';
                $c = 'customer.';
            }
            if ($k == 'contract_name') {
                $k = 'name';
                $c = 'contract.';
            }
            if ($k == 'business_name') {
                $k = 'name';
                $c = 'business.';
            }
            if ($k == 'contacts_name') {
                $k = 'name';
                $c = 'contacts.';
            }
            if ($k == 'check_status' && is_array($v) && in_array($v['value'],$checkStatusList)) {
                $v['value'] = $checkStatusArray[$v['value']] ? : '0';
            }
            if (is_array($v)) {
                if ($v['state']) {
                    $address_where[] = '%'.$v['state'].'%';
                    if ($v['city']) {
                        $address_where[] = '%'.$v['city'].'%';
                        if ($v['area']) {
                            $address_where[] = '%'.$v['area'].'%';
                        }
                    }
                    if ($v['condition'] == 'not_contain') {
                        $where[$c.$k] = ['notlike', $address_where, 'OR'];
                    } else {
                        $where[$c.$k] = ['like', $address_where, 'AND'];
                    }
                } elseif (!empty($v['start']) || !empty($v['end'])) {
                    if ($v['start'] && $v['end']) {
                        $where[$c.$k] = ['between', [$v['start'], $v['end']]];
                    } elseif ($v['start']) {
                        $where[$c.$k] = ['egt', $v['start']];
                    } else {
                        $where[$c.$k] = ['elt', $v['end']];
                    }
                } elseif (!empty($v['start_date']) || !empty($v['end_date'])) {
                    if ($v['start_date'] && $v['end_date']) {
                        $where[$c.$k] = ['between', [$v['start_date'], $v['end_date']]];
                    } elseif ($v['start_date']) {
                        $where[$c.$k] = ['egt', $v['start_date']];
                    } else {
                        $where[$c.$k] = ['elt', $v['end_date']];
                    }                                     
                } elseif (!empty($v['value']) || $v['value'] === '0') {
                    if (in_array($k, $check_field_arr)) {
                        $where[$c.$k] = field($v['value'], 'contains');
                    } else {
                        $where[$c.$k] = field($v['value'], $v['condition']);
                    }
                } elseif (in_array($v['condition'], ['is_empty','is_not_empty'])) {
                    $where[$c.$k] = field($v['value'], $v['condition']);
                } else {
                    $where[$c.$k] = $v;
                }                  
            } elseif (!empty($v)) {
                $where[$c.$k] = field($v);
            } else {
                $where[$c.$k] = $v;
            }
        }
    }    
    return $where ? : [];
}

/**
 * 根据搜索生成where条件
 * @author Michael_xu
 * @param  string $search 搜索内容
 * @param  $condition 搜索条件
 * @return array           
 */
function field($search, $condition = '')
{
    switch (trim($condition)) {
        case "is" : $where = ['eq',$search];break;
        case "isnot" :  $where = ['neq',$search];break;
        case "contains" :  $where = ['like','%'.$search.'%'];break;
        case "not_contain" :  $where = ['notlike','%'.$search.'%'];break;
        case "start_with" :  $where = ['like',$search.'%'];break;
        case "end_with" :  $where = ['like','%'.$search];break;
        case "is_empty" :  $where = ['eq',''];break;
        case "is_not_empty" :  $where = ['neq',''];break;
        case "eq" : $where = ['eq',$search];break;
        case "neq" : $where = ['neq',$search];break;        
        case "gt" :  $where = ['gt',$search];break;
        case "egt" :  $where = ['egt',$search];break;
        case "lt" :  
                if (strtotime($search) !== false && strtotime($search) != -1) {
                    $where = ['lt',strtotime($search)];
                } else {
                    $where = ['lt',$search];
                }
                break;
        case "elt" :  $where = ['elt',$search];break;
        case "in" :  $where = ['in',$search];break;
        default : $where = ['eq',$search]; break;      
    }
    return $where;
}

/**
 * 将单个搜索转换为高级搜索格式
 * @author Michael_xu
 * @param  string $value 搜索内容
 * @param  $condition 搜索条件
 * @return array           
 */
function field_arr($value, $condition = '')
{
    if (is_array($value)) {

    } else {
        $condition = $condition ? : 'eq';
        $where_arr = ['value' => $value,'condition' => $condition];
    }
    
    return $where_arr;    
}

/**
 * 根据类型获取时间
 *
 * @param string $type
 * @return array
 */
function getWhereByTime($type) {
    if (!empty($param['type'])) {
        $between_time = getTimeByType($param['type']);
    } else {
        //自定义时间
        if (!empty($param['start_time'])) {
            $between_time = array($param['start_time'],$param['end_time']);
        }
    }
    return $between_time;
}

/**
 * 根据类型获取开始结束时间戳数组
 * @param 
 */
function getTimeByType($type = 'today')
{
    switch ($type) {
        case 'yesterday' : $timeArr = Time::yesterday(); break;
        case 'week' : $timeArr = Time::week(); break;
        case 'lastWeek' : $timeArr = Time::lastWeek(); break;
        case 'month' : $timeArr = Time::month(); break;
        case 'lastMonth' : $timeArr = Time::lastMonth(); break;
        case 'quarter' :
            //本季度
            $month=date('m');
            if ($month == 1 || $month == 2 || $month == 3) {
                $daterange_start_time = strtotime(date('Y-01-01 00:00:00'));
                $daterange_end_time = strtotime(date("Y-03-31 23:59:59"));
            } elseif ($month == 4 || $month == 5 || $month == 6) {
                $daterange_start_time = strtotime(date('Y-04-01 00:00:00'));
                $daterange_end_time = strtotime(date("Y-06-30 23:59:59"));
            } elseif ($month == 7 || $month == 8 || $month == 9) {
                $daterange_start_time = strtotime(date('Y-07-01 00:00:00'));
                $daterange_end_time = strtotime(date("Y-09-30 23:59:59"));
            } else {
                $daterange_start_time = strtotime(date('Y-10-01 00:00:00'));
                $daterange_end_time = strtotime(date("Y-12-31 23:59:59"));
            }
            $timeArr = array($daterange_start_time,$daterange_end_time);
            break;
        case 'lastQuarter' : 
            //上季度
            $month = date('m');
            if ($month == 1 || $month == 2 ||$month == 3) {
                $year = date('Y')-1;
                $daterange_start_time = strtotime(date($year.'-10-01 00:00:00'));
                $daterange_end_time = strtotime(date($year.'-12-31 23:59:59'));
            } elseif ($month == 4 || $month == 5 ||$month == 6) {
                $daterange_start_time = strtotime(date('Y-01-01 00:00:00'));
                $daterange_end_time = strtotime(date("Y-03-31 23:59:59"));
            } elseif ($month == 7 || $month == 8 ||$month == 9) {
                $daterange_start_time = strtotime(date('Y-04-01 00:00:00'));
                $daterange_end_time = strtotime(date("Y-06-30 23:59:59"));
            } else {
                $daterange_start_time = strtotime(date('Y-07-01 00:00:00'));
                $daterange_end_time = strtotime(date("Y-09-30 23:59:59"));
            }            
            $timeArr = array($daterange_start_time,$daterange_end_time);           
            break;        
        case 'year' : $timeArr = Time::year(); break;
        case 'lastYear' : $timeArr = Time::lastYear(); break;
        default : $timeArr = Time::today(); break;
    }
    return $timeArr;
}
