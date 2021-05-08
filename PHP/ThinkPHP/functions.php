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
