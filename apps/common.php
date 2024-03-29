<?php
// +----------------------------------------------------------------------
// | Yzncms [ 御宅男工作室 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2007 http://yzncms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 御宅男 <530765310@qq.com>
// +----------------------------------------------------------------------
// 公用函数
use think\Cache;
use think\Config;
/**
 * 获取表名（不含表前缀）
 * @param string $model_id
 * @return string 表名
 */
function get_table_name($modelid = null)
{
    if (empty($modelid)) {
        return false;
    }
    $modelCache = cache("Model");
    if (empty($modelCache[$modelid])) {
        return false;
    }
    ;
    $tableName = $modelCache[$modelid]['tablename'];
    return ucwords($tableName);
}
/**
 * 获取扩展模型对象
 * @param  integer $model_id 模型编号
 * @param string   默认公共模型 base基础模型 Independent独立模型公共模型 Document 继承模型公共模型
 * @return object         模型对象
 */
function logic($model_id, $Base = 'Base')
{
    $modelCache = cache("Model");
    if (empty($modelCache[$model_id])) {
        return false;
    }
    $tableName = $modelCache[$model_id]['tablename'];
    $class = 'app\common\logic\\' . $Base;
    return new $class(['table_name' => $tableName]);
}
/**
 * 系统缓存缓存管理
 * cache('model') 获取model缓存
 * cache('model',null) 删除model缓存
 * @param mixed $name 缓存名称
 * @param mixed $value 缓存值
 * @param mixed $options 缓存参数
 * @return mixed
 */
function cache($name, $value = '', $options = null)
{
    static $cache = '';
    if (empty($cache)) {
        $cache = \Libs\System\Cache_factory::instance();
    }
    // 获取缓存
    if ('' === $value) {
        if (false !== strpos($name, '.')) {
            $vars = explode('.', $name);
            $data = $cache->get($vars[0]);
            return is_array($data) ? $data[$vars[1]] : $data;
        } else {
            return $cache->get($name);
        }
    } elseif (is_null($value)) {
//删除缓存
        return $cache->remove($name);
    } else {
//缓存数据
        if (is_array($options)) {
            $expire = isset($options['expire']) ? $options['expire'] : null;
        } else {
            $expire = is_numeric($options) ? $options : null;
        }
        return $cache->set($name, $value, $expire);
    }
}

/**
 * 根据PHP各种类型变量生成唯一标识号
 * @param mixed $mix 变量
 * @return string
 */
function to_guid_string($mix)
{
    if (is_object($mix)) {
        return spl_object_hash($mix);
    } elseif (is_resource($mix)) {
        $mix = get_resource_type($mix) . strval($mix);
    } else {
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 * 获取模型数据
 * @param type $modelid 模型ID
 * @param type $field 返回的字段，默认返回全部，数组
 * @return boolean
 */
function getModel($modelid, $field = '')
{
    if (empty($modelid)) {
        return false;
    }
    $key = 'getModel_' . $modelid;
    $cache = Cache::get($key);
    if ($cache === 'false') {
        return false;
    }
    if (empty($cache)) {
        //读取数据
        $cache = db('Model')->where(array('modelid' => $modelid))->find();
        if (empty($cache)) {
            Cache::set($key, 'false', 60);
            return false;
        } else {
            Cache::set($key, $cache, 3600);
        }
    }
    if ($field) {
        return $cache[$field];
    } else {
        return $cache;
    }
}

/**
 * 获取栏目相关信息
 * @param type $catid 栏目id
 * @param type $field 返回的字段，默认返回全部，数组
 * @param type $newCache 是否强制刷新
 * @return boolean
 */
function getCategory($catid, $field = '', $newCache = false)
{
    if (empty($catid)) {
        return false;
    }
    $key = 'getCategory_' . $catid;
    //强制刷新缓存
    if ($newCache) {
        Cache::rm($key, null);
    }
    $cache = Cache::get($key);
    if ($cache === 'false') {
        return false;
    }
    if (empty($cache)) {
        //读取数据
        $cache = db('Category')->where(array('catid' => $catid))->find();
        if (empty($cache)) {
            Cache::set($key, 'false', 60);
            return false;
        } else {
            //扩展配置
            $cache['setting'] = unserialize($cache['setting']);
            //栏目扩展字段
            $cache['extend'] = $cache['setting']['extend'];
            Cache::set($key, $cache, 3600);
        }
    }
    if ($field) {
        //支持var.property，不过只支持一维数组
        if (false !== strpos($field, '.')) {
            $vars = explode('.', $field);
            return $cache[$vars[0]][$vars[1]];
        } else {
            return $cache[$field];
        }
    } else {
        return $cache;
    }
}

/**
 * 字符截取
 * @param $string 需要截取的字符串
 * @param $length 长度
 * @param $dot
 */
function str_cut($sourcestr, $length, $dot = '...')
{
    $returnstr = '';
    $i = 0;
    $n = 0;
    $str_length = strlen($sourcestr); //字符串的字节数
    while (($n < $length) && ($i <= $str_length)) {
        $temp_str = substr($sourcestr, $i, 1);
        $ascnum = Ord($temp_str); //得到字符串中第$i位字符的ascii码
        if ($ascnum >= 224) { //如果ASCII位高与224，
            $returnstr = $returnstr . substr($sourcestr, $i, 3); //根据UTF-8编码规范，将3个连续的字符计为单个字符
            $i = $i + 3; //实际Byte计为3
            $n++; //字串长度计1
        } elseif ($ascnum >= 192) { //如果ASCII位高与192，
            $returnstr = $returnstr . substr($sourcestr, $i, 2); //根据UTF-8编码规范，将2个连续的字符计为单个字符
            $i = $i + 2; //实际Byte计为2
            $n++; //字串长度计1
        } elseif ($ascnum >= 65 && $ascnum <= 90) {
            //如果是大写字母，
            $returnstr = $returnstr . substr($sourcestr, $i, 1);
            $i = $i + 1; //实际的Byte数仍计1个
            $n++; //但考虑整体美观，大写字母计成一个高位字符
        } else {
//其他情况下，包括小写字母和半角标点符号，
            $returnstr = $returnstr . substr($sourcestr, $i, 1);
            $i = $i + 1; //实际的Byte数计1个
            $n = $n + 0.5; //小写字母和半角标点等与半个高位字符宽...
        }
    }
    if ($str_length > strlen($returnstr)) {
        $returnstr = $returnstr . $dot; //超过长度时在尾处加上省略号
    }
    return $returnstr;
}

/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '')
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) {
        $size /= 1024;
    }

    return round($size, 2) . $delimiter . $units[$i];
}
/**
 * 根据用户ID获取用户名
 * @param  integer $uid 用户ID
 * @return string       用户名
 */
function get_username($uid = 0)
{
    static $list;
    if (!($uid && is_numeric($uid))) {
        //获取当前登录用户名
        return session('user_auth.username');
    }
    /* 获取缓存数据 */
    if (empty($list)) {
        $list = Cache::get('sys_active_user_list');
    }
    /* 查找用户信息 */
    $key = "u{$uid}";
    if (isset($list[$key])) {
        //已缓存，直接使用
        $name = $list[$key];
    } else {
        //调用接口获取用户信息
        $info = db('admin')->where('userid', $uid)->value('username');
        if (!empty($info)) {
            $name = $list[$key] = $info;
            /* 缓存用户 */
            $count = count($list);
            $max = config('USER_MAX_CACHE');
            while ($count-- > $max) {
                array_shift($list);
            }
            Cache::set('sys_active_user_list', $list);
        } else {
            $name = '';
        }
    }
    return $name;
}

/**
 * 检测用户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID
 */
function is_login()
{
    $user = session('user_auth');
    if (empty($user)) {
        return 0;
    } else {
        return session('user_auth_sign') == data_auth_sign($user) ? $user['uid'] : 0;
    }
}

/**
 * 检测当前用户是否为管理员
 * @return boolean true-管理员，false-非管理员
 */
function is_administrator($uid = null)
{
    $uid = is_null($uid) ? is_login() : $uid;
    return $uid && (intval($uid) === config('USER_ADMINISTRATOR'));
}

/**
 * 数据签名认证
 * @param  array  $data 被认证的数据
 * @return string       签名
 */
function data_auth_sign($data)
{
    //数据类型检测
    if (!is_array($data)) {
        $data = (array) $data;
    }
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    $sign = sha1($code); //生成签名
    return $sign;
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function list_to_tree($list, $pk = 'id', $pid = 'parentid', $child = '_child', $root = 0)
{
    // 创建Tree
    $tree = array();
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    $parent[$child][] = &$list[$key];
                }
            }
        }
    }
    return $tree;
}

/**
 * select返回的数组进行整数映射转换
 *
 * @param array $map  映射关系二维数组  array(
 *                                          '字段名1'=>array(映射关系数组),
 *                                          '字段名2'=>array(映射关系数组),
 *                                           ......
 *                                       )
 * @author 朱亚杰 <zhuyajie@topthink.net>
 * @return array
 *
 *  array(
 *      array('id'=>1,'title'=>'标题','status'=>'1','status_text'=>'正常')
 *      ....
 *  )
 *
 */
function int_to_string(&$data, $map = array('status' => array(1 => '正常', -1 => '删除', 0 => '禁用', 2 => '未审核', 3 => '草稿')))
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array) $data;
    foreach ($data as $key => $row) {
        foreach ($map as $col => $pair) {
            if (isset($row[$col]) && isset($pair[$row[$col]])) {
                $data[$key][$col . '_text'] = $pair[$row[$col]];
            }
        }
    }
    return $data;
}

/**
 * 获取客户端IP地址
 * @param int $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param bool $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0, $adv = false)
{
    $type = $type ? 1 : 0;
    static $ip = null;
    if ($ip !== null) {
        return $ip[$type];
    }

    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }

            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
 * 调用系统的API接口方法（静态方法）
 * api('User/getName','id=5'); 调用公共模块的User接口的getName方法
 * api('Admin/User/getName','id=5');  调用Admin模块的User接口
 * @param  string  $name 格式 [模块名]/接口名/方法名
 * @param  array|string  $vars 参数
 */
function api($name, $vars = array())
{
    $array = explode('/', $name);
    $method = array_pop($array);
    $classname = array_pop($array);
    $module = $array ? array_pop($array) : 'common';
    $callback = 'app\\' . $module . '\\Api\\' . $classname . 'Api::' . $method;
    if (is_string($vars)) {
        parse_str($vars, $vars);
    }
    return call_user_func_array($callback, $vars);
}

/**
 * 时间戳格式化
 * @param int $time
 * @return string 完整的时间显示
 */
function time_format($time = null, $format = 'Y-m-d H:i')
{
    $time = $time === null ? NOW_TIME : intval($time);
    return date($format, $time);
}

/**
 * 生成上传附件验证
 * @param $args   参数
 */
function upload_key($args)
{
    return md5($args . md5(config("AUTHCODE") . $_SERVER['HTTP_USER_AGENT']));
}

/**
 * 发送邮件
 * @param $toemail 收件人email
 * @param $subject 邮件主题
 * @param $message 正文
 * @param $from 发件人
 * @param $cfg 邮件配置信息
 * @param $sitename 邮件站点名称
 */
function send_email($toemail, $subject, $message, $from = '', $cfg = array(), $sitename = '')
{
    //判断openssl是否开启
    $openssl_funcs = get_extension_funcs('openssl');
    if (!$openssl_funcs) {
        return array('status' => -1, 'msg' => '请先开启openssl扩展');
    }
    //表单提交 测试发送
    if ($cfg && is_array($cfg)) {
        $from = $cfg['from'];
        $email = $cfg;
    } else {
        $config = cache('Config');

    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer();
    //Server settings
    $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->SMTPDebug = 0; // Enable verbose debug output
    $mail->isSMTP(); // Set mailer to use SMTP
    $mail->Host = $email['server']; // Specify main and backup SMTP servers
    $mail->SMTPAuth = true; // Enable SMTP authentication
    $mail->Username = $email['auth_username']; // SMTP username
    $mail->Password = $email['auth_password']; // SMTP password
    $mail->SMTPSecure = 'tls'; // Enable TLS encryption, `ssl` also accepted
    $mail->Port = $email['port']; //端口 - likely to be 25, 465 or 587

    //Recipients
    $mail->setFrom($from, 'Mailer'); //发送方地址和昵称
    $mail->addAddress($toemail, 'Joe User'); // Add a recipient
    //$mail->addReplyTo('info@example.com', 'Information'); //回复地址

    //Content
    $mail->isHTML(true); // Set email format to HTML
    $mail->Subject = $subject; //标题
    $mail->Body = $message; //内容
    //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
    if (!$mail->send()) {
        return array('status' => -1, 'msg' => '发送失败: ' . $mail->ErrorInfo);
    } else {
        return array('status' => 1, 'msg' => '发送成功');
    }

}

/**
 * 分页处理
 * @param type $total 信息总数
 * @param type $size 每页数量
 * @param type $number 当前分页号（页码）
 * @param type $config 配置，会覆盖默认设置
 * @return \Page|array
 */
function page($total, $size = 0, $number = 0, $config = array())
{
    //配置
    $defaultConfig = array(
        //当前分页号
        'number' => $number,
        //接收分页号参数的标识符
        'param' => Config::get("VAR_PAGE"),
        //分页规则
        'rule' => '',
        //是否启用自定义规则
        'isrule' => false,
        //分页模板
        'tpl' => '',
        //分页具体可控制配置参数默认配置
        //'tplconfig' => array('listlong' => 6, 'listsidelong' => 2, "first" => "首页", "last" => "尾页", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => ""),
        'tplconfig' => array('listlong' => 3, 'listsidelong' => 1, "first" => "首页", "last" => "尾页", "prev" => "<", "next" => ">", "list" => "*", "disabledclass" => "..."),
    );
    //分页具体可控制配置参数
    $cfg = array(
        //每次显示几个分页导航链接
        'listlong' => 6,
        //分页链接列表首尾导航页码数量，默认为2，html 参数中有”{liststart}”或”{listend}”时才有效
        'listsidelong' => 2,
        //分页链接列表
        'list' => '*',
        //当前页码的CSS样式名称，默认为”current”
        'currentclass' => 'current',
        //第一页链接的HTML代码，默认为 ”«”，即显示为 «
        'first' => '&laquo;',
        //上一页链接的HTML代码，默认为”‹”,即显示为 ‹
        'prev' => '&#8249;',
        //下一页链接的HTML代码，默认为”›”,即显示为 ›
        'next' => '&#8250;',
        //最后一页链接的HTML代码，默认为”»”,即显示为 »
        'last' => '&raquo;',
        //被省略的页码链接显示为，默认为”…”
        'more' => '...',
        //当处于首尾页时不可用链接的CSS样式名称，默认为”disabled”
        'disabledclass' => 'disabled',
        //页面跳转方式，默认为”input”文本框，可设置为”select”下拉菜单
        'jump' => '',
        //页面跳转文本框或下拉菜单的附加内部代码
        'jumpplus' => '',
        //跳转时要执行的javascript代码，用*代表页码，可用于Ajax分页
        'jumpaction' => '',
        //当跳转方式为下拉菜单时最多同时显示的页码数量，0为全部显示，默认为50
        'jumplong' => 50,
    );
    //覆盖配置
    if (!empty($config) && is_array($config)) {
        $defaultConfig = array_merge($defaultConfig, $config);
    }
    //每页显示信息数量
    $defaultConfig['size'] = $size ? $size : Config::get("PAGE_LISTROWS");
    //把默认配置选项设置到tplconfig
    foreach ($cfg as $key => $value) {
        if (isset($defaultConfig[$key])) {
            $defaultConfig['tplconfig'][$key] = isset($defaultConfig[$key]) ? $defaultConfig[$key] : $value;
        }
    }
    //是否启用自定义规则，规则是一个数组，index和list。不启用的情况下，直接以当前$_GET的参数组成地址
    if ($defaultConfig['isrule'] && empty($defaultConfig['rule'])) {
        //通过全局参数获取分页规则
        $URLRULE = isset($GLOBALS['URLRULE']) ? $GLOBALS['URLRULE'] : (defined('URLRULE') ? URLRULE : '');
        $PageLink = array();
        if (!is_array($URLRULE)) {
            $URLRULE = explode('~', $URLRULE);
        }
        $PageLink['index'] = isset($URLRULE['index']) && $URLRULE['index'] ? $URLRULE['index'] : $URLRULE[0];
        $PageLink['list'] = isset($URLRULE['list']) && $URLRULE['list'] ? $URLRULE['list'] : $URLRULE[1];
        $defaultConfig['rule'] = $PageLink;
    } else if ($defaultConfig['isrule'] && !is_array($defaultConfig['rule'])) {
        $URLRULE = explode('|', $defaultConfig['rule']);
        $PageLink = array();
        $PageLink['index'] = $URLRULE[0];
        $PageLink['list'] = $URLRULE[1];
        $defaultConfig['rule'] = $PageLink;
    }
    $Page = new \util\Page($total, $defaultConfig['size'], $defaultConfig['number'], $defaultConfig['list'], $defaultConfig['param'], $defaultConfig['rule'], $defaultConfig['isrule']);
    $Page->SetPager('default', $defaultConfig['tpl'], $defaultConfig['tplconfig']);
    return $Page;
}
