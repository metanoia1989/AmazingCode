<?php
//*************************************************************************
// 安装模块的不错的函数封装
//*************************************************************************

/**
 * 将一个文件夹下的所有文件及文件夹
 * 复制到另一个文件夹里（保持原有结构）
 *
 * @param <string> $rootFrom 源文件夹地址（最好为绝对路径）
 * @param <string> $rootTo 目的文件夹地址（最好为绝对路径）
 */
function cpFiles($rootFrom, $rootTo)
{

    $handle = opendir($rootFrom);
    while (false !== ($file = readdir($handle))) {
        //DIRECTORY_SEPARATOR 为系统的文件夹名称的分隔符 例如：windos为'/'; linux为'/'
        $fileFrom = $rootFrom . DIRECTORY_SEPARATOR . $file;
        $fileTo = $rootTo . DIRECTORY_SEPARATOR . $file;
        if ($file == '.' || $file == '..') {
            continue;
        }

        if (is_dir($fileFrom)) {
            if (!is_dir($fileTo)) { //目标目录不存在则创建
                mkdir($fileTo, 0777);
            }
            cpFiles($fileFrom, $fileTo);
        } else {
            if (!file_exists($fileTo)) {
                @copy($fileFrom, $fileTo);
                if (strstr($fileTo, "access_token.txt")) {
                    chmod($fileTo, 0777);
                }
            }
        }

    }
}

/**
 * Notes: 目录的容量，这些小函数真的是妙用无穷啊 
 * 
 * @author luzg(2020/8/25 15:21)
 * @param $dir
 * @return string
 */
function freeDiskSpace($dir)
{
    // M
    $freeDiskSpace = disk_free_space(realpath(__DIR__)) / 1024 / 1024;

    // G
    if ($freeDiskSpace > 1024) {
        return number_format($freeDiskSpace / 1024, 2) . 'G';
    }

    return number_format($freeDiskSpace, 2) . 'M';
}

class InstallModel
{
    /**
     * Notes: 当前版本是否符合
     * @author luzg(2020/8/25 9:57)
     * @return string
     */
    public function checkPHP()
    {
        return $result = version_compare(PHP_VERSION, '7.2.0') >= 0 ? 'ok' : 'fail';
    }

    /**
     * Notes: 创建表
     * @author luzg(2020/8/25 11:57)
     * @param $version
     * @param $post
     * @return bool
     * @throws Exception
     */
    public function createTable($version, $post)
    {
        $dbFile = $this->getInstallRoot() . '/db/like.sql';
        $content = str_replace(";\r\n", ";\n", file_get_contents($dbFile));
        $tables = explode(";\n", $content);
        $tables[] = $this->initAccount($post);
        $installTime = microtime(true) * 10000;

        foreach ($tables as $table) {
            $table = trim($table);
            if (empty($table)) {
                continue;
            }

            if (strpos($table, 'CREATE') !== false and $version <= 4.1) {
                $table = str_replace('DEFAULT CHARSET=utf8', '', $table);
            }

            /* Skip sql that is note. */
            if (strpos($table, '--') === 0) {
                continue;
            }

            $table = str_replace('`ls_', $this->name . '.`ls_', $table);
            $table = str_replace('`ls_', '`' . $this->prefix, $table);

            if (strpos($table, 'CREATE') !== false) {
                $tableName = explode('`', $table)[1];
                $installTime += random_int(3000, 7000);
                $this->successTable[] = [$tableName, date('Y-m-d H:i:s', $installTime / 10000)];
            }

            try {
                if (!$this->dbh->query($table)) {
                    return false;
                }

            } catch (Exception $e) {
                echo 'error sql: ' . $table . "<br>";
                echo $e->getMessage() . "<br>";
                return false;
            }
        }
        return true;
    }

    /**
     * Notes: 检查数据库是否存在
     * @author luzg(2020/8/25 11:56)
     * @return mixed
     */
    public function dbExists()
    {
        $sql = "SHOW DATABASES like '{$this->name}'";
        return $this->dbh->query($sql)->fetch();
    }

    /**
     * Notes: 检查表是否存在
     * @author luzg(2020/8/25 11:56)
     * @return mixed
     */
    public function tableExits()
    {
        $configTable = sprintf("'%s'", $this->prefix . TESTING_TABLE);
        $sql = "SHOW TABLES FROM {$this->name} like $configTable";
        return $this->dbh->query($sql)->fetch();
    }
    /**
     * Notes: 当前应用程序的相对路径
     * @author luzg(2020/8/25 10:55)
     * @return string
     */
    public function getAppRoot()
    {
        return realpath($this->getInstallRoot() . '/../../');
    }

    /**
     * Notes: 获取安装目录
     * @author luzg(2020/8/26 16:15)
     * @return string
     */
    public function getInstallRoot()
    {
        return INSTALL_ROOT;
    }

    /**
     * Notes: 取得临时目录路径
     * @author luzg(2020/8/25 10:05)
     * @return array
     */
    public function getTmpRoot()
    {
        $path = $this->getAppRoot() . '/runtime';
        return [
            'path' => $path,
            'exists' => is_dir($path),
            'writable' => is_writable($path),
        ];
    }

    /**
     * Notes: 初始化管理账号
     * @param $post
     * @return string
     */
    public function initAccount($post)
    {
        $time = time();
        $salt = substr(md5($time . $post['admin_user']), 0, 4); //随机4位密码盐
        $password = $this->createPassword($post['admin_password'], $salt);

        // 直接使用SQL语句其实也挺高效的
        $sql = "INSERT INTO `ls_admin` VALUES (1, 1, '{$post['admin_user']}', NULL, '{$post['admin_user']}',
                '{$password}', '{$salt}', 0, '{$time}', '{$time}', '{$time}', '', 0, 0);";
        return $sql;
    }

    /**
     * Notes: 生成密码密文
     * @param $pwd
     * @param $salt
     * @return string
     */
    public function createPassword($pwd, $salt)
    {
        $salt = md5('y' . $salt . 'x');
        $salt .= '2021';
        return md5($pwd . $salt);
    }
}

class LoadEnv
{

    /**
     * Notes: 写入Env文件，使用文件写入函数，换行使用换行符。改动文件的本质就是文件的读写。 
     * @author luzg(2020/8/27 18:12)
     * @param $envFilePath
     * @param array $databaseEnv
     */
    public function putEnv($envFilePath, array $databaseEnv)
    {
        $applyDbEnv = [
            'database.hostname' => $databaseEnv['host'],
            'database.database' => $databaseEnv['name'],
            'database.username' => $databaseEnv['user'],
            'database.password' => $databaseEnv['password'],
            'database.hostport' => $databaseEnv['port'],
            'database.prefix' => $databaseEnv['prefix'],
            'project.file_domain' => $_SERVER['HTTP_HOST'],
        ];

        $envLine = array_merge($this->data, $applyDbEnv);
        ksort($envLine);

        $content = '';
        $lastPrefix = '';

        foreach ($envLine as $index => $value) {
            list($prefix, $key) = explode('.', $index);

            if ($prefix != $lastPrefix && $key != null) {
                if ($lastPrefix != '')
                    $content .= "\n";

                $content .= "[$prefix]\n";
                $lastPrefix = $prefix;
            }

            if ($prefix != $lastPrefix && $key == null) {
                $content .= "$index = \"$value\"\n";
            } else {
                $content .= "$key = \"$value\"\n";
            }
        }
        if ( !empty($content)) {
            file_put_contents($envFilePath, $content);
        }
    }

    /**
     * 设置环境变量值
     * @access public
     * @param string|array $env 环境变量
     * @param mixed $value 值
     * @return void
     */
    public function set($env, $value = null)
    {
        if (is_array($env)) {
            // 两次 foreach 解析二维数组，抹平赋值 
            foreach ($env as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $this->data[$key . '.' . $k] = $v;
                    }
                } else {
                    $this->data[$key] = $val;
                }
            }
        } else {
            $name = strtoupper(str_replace('.', '_', $env));

            $this->data[$name] = $value;
        }
    }
}