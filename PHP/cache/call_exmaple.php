<?php
if ($category_id = $db->pe_insert('category', pe_dbhold($_p_info))) {
    cache_write('category');
    pe_success('添加成功!', 'txadmin.php?mod=category');
}