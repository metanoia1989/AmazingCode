> 本文由 [简悦 SimpRead](http://ksria.com/simpread/) 转码， 原文地址 [www.kancloud.cn](https://www.kancloud.cn/martist/be_new_friends/1902124)

关于接口设计，需要注意区分 pc,wap,app 不同端的接口请求和通用性，以及实现签名鉴权，访问控制等功能。

接口参数定义
------

接口设计中往可以抽象出一些新的公共参数，从事了近三年的接口开发工作中，我目前能想到了一些较为常见的公共接口参数如下：

<table><thead><tr><th>公共参数</th><th>含意</th><th>定义该参数的意义</th></tr></thead><tbody><tr><td>timestamp</td><td>毫秒级时间戳</td><td>1. 客户端的请求时间标示 2. 后端可以做请求过期验证 3. 该参数参与签名算法增加签名的唯一性</td></tr><tr><td>app_key/source</td><td>签名公钥 (来源)</td><td>签名算法的公钥，后端通过公钥可以得到对应的私钥（也就是来源的意义）</td></tr><tr><td>sign</td><td>接口签名</td><td>通过请求的参数和定义好的签名算法生成接口签名，作用防止中间人篡改请求参数</td></tr><tr><td>did</td><td>设备 ID</td><td>设备的唯一标示，生成规则例如 android 的 mac 地址的 md5 和 ios 曾今 udid(目前无法获取) 的 md5, 1: 数据收集 2. 便于问题追踪 3. 消息推送标示</td></tr></tbody></table>

接口版本化
-----

我不太习惯把版本号直接放到路由里面去，还有其他方式可以区别版本，比如 get、post 传参。

接口安全性
-----

### 过期验证

通过时间戳进行验证

```php
<?php
if (microtime(true)*1000 - $_REQUEST['timestamp'] > 5000) {
    throw new \Exception(401, 'Expired request');
}
```


### 签名验证 (公钥校验省略，如果是 saas，密钥可能不同)

通过配对私钥的加密算法产生签名，请求中携带签名进行鉴权。

```php
<?php
$params = ksort($_REQUEST);
unset($params['sign']);
$sign = md5(sha1(implode('-', $params) . $_REQUEST['app_key']));
if ($sign !== $_REQUEST['sign']) {
    throw new \Exception(401, 'Invalid sign');
}
```


### 重放攻击

防止一次相同请求多次攻击 API 服务器。

```php
<?php
 /**
 @params noise string 随机字符串或随机正整数，与 Timestamp 联合起来, 用于防止重放攻击 例如腾讯云是6位随机正整数
 */
$key = md5("{$_REQUEST['REQUEST_URI']}-{$_REQUEST['timestamp']}-{$_REQUEST['noise']}-{$_REQUEST['did']}");
if ($redisInstance->exists($key)) {
    throw new \Exception(401, 'Repeated request');
}
```


### 限流

防止同一 ip 频繁访问 API 服务器。

```php
<?php
$key = md5("{$_REQUEST['REQUEST_URI']}-{$_REQUEST['REMOTE_ADDR']}-{$_REQUEST['did']}");
if ($redisInstance->get($key) > 60) {
    throw new \Exception(401, 'Request limit');
}
$redisInstance->incre($key);
```


### 转义

防止注入，xss 等攻击。

```php
<?php
$username = htmlspecialchars($_REQUEST['username']);
```


接口的解耦设计
-------

1.  活用中间件、钩子
2.  借口多用 post 请求，少用 get
3.  废弃的代码及时删掉，或者注释掉并且标注
4.  接口文件合理切割（laravel，lumen 等有接口文件的框架）
5.  服务间调用不要私钥公钥相同，免得一破百破

接口的状态码
------

推荐一些公用的，如果还有私信，广播，商城等状态码可以另加。

```
200 -> 正常

400 -> 缺少公共必传参数或者业务必传参数

401 -> 接口校验失败 例如签名

403 -> 没有该接口的访问权限

499 -> 上游服务响应时间超过接口设置的超时时间

500 -> 代码错误

501 -> 不支持的接口method

502 -> 上游服务返回的数据格式不正确

503 -> 上游服务超时

504 -> 上游服务不可用


```
