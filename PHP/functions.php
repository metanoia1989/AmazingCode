<?php
/**
 * 数组转换字符串（以逗号隔开）
 * @param 
 * @author Michael_xu
 * @return 
 */
function arrayToString($array)
{
    if (!is_array($array)) {
        $data_arr[] = $array;
    } else {
    	$data_arr = $array;
    }
    $data_arr = array_filter($data_arr); //数组去空
    $data_arr = array_unique($data_arr); //数组去重
    $data_arr = array_merge($data_arr);
    $string = $data_arr ? ','.implode(',', $data_arr).',' : '';
    return $string ? : '';
}

/**
 * 字符串转换数组（以逗号隔开）
 * @param 
 * @author Michael_xu
 * @return 
 */
function stringToArray($string)
{
    if (is_array($string)) {
        $data_arr = array_unique(array_filter($string));
    } else {
        $data_arr = $string ? array_unique(array_filter(explode(',', $string))) : [];
    }
    $data_arr = $data_arr ? array_merge($data_arr) : [];
    return $data_arr ? : [];
}
