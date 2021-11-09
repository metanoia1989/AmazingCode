# 简单的Hook实现
将键与对应的函数存放到数组中，而要实现优先级，则利用二维数组。    
两个关键的方法，`register()` 注册钩子， `dispath()` 触发钩子。  

使用示例
```php
<?php
// 注册钩子
$options['hooks'] = new Requests_Hooks();
$options['hooks']->register('transport.internal.parse_response', array('Requests', 'parse_multiple'));
$options['hooks']->register('multiple.request.complete', $options['complete']);

// 触发钩子 
$request['options']['hooks']->dispatch('multiple.request.complete', array(&$response, $id));
```
