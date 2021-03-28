<?php


namespace AndPHP\Postman;


use Illuminate\Support\Facades\File;

class PostmanToMarkdown
{
    const MAX_SHOW = 50;
    # 不需要的header字段
    const EXCEPT = [
        'Server',
        'Date',
    ];

    # 如果没有描述，就取这里的描述
    const DESCRIPTION_ENUMS = [
        'account'          => '账户，用来区分配置信息',
        'address'          => '地址',
        'api_token'        => '身份认证token',
        'app_id'           => '应用ID',
        'avatar'           => '头像',
        'birthday'         => '生日',
        'brand'            => '品牌名称',
        'brand_id'         => '品牌ID',
        'captcha'          => '验证码',
        'city'             => '城市',
        'code'             => '临时授权码',
        'company'          => '公司',
        'country'          => '国家',
        'country_code'     => '国家区号',
        'created_at'       => '创建时间',
        'ctoken'           => '旧的登陆方式',
        'education'        => '教育程度',
        'email'            => '邮箱',
        'first_name'       => '名',
        'gender'           => '性别',
        'id_token'         => '校验token',
        'identity_token'   => '身份认证token',
        'industry'         => '行业',
        'is_native'        => '是否客户端应用',
        'key'              => '二维码标识key',
        'language'         => '语言',
        'last_login_time'  => '上次登录时间',
        'last_name'        => '姓',
        'name'             => '名称',
        'nickname'         => '昵称',
        'oauth_id'         => 'Oauth的id',
        'open_id'          => '第三方授权开放id',
        'password'         => '密码',
        'password_confirm' => '确认密码',
        'position'         => '职位',
        'postcode'         => '邮编',
        'provider'         => '第三方授权名称',
        'qrcode_key'       => '二维码key',
        'scene'            => '场景',
        'status'           => '状态',
        'sylogin_hash'     => '旧的登陆方式',
        'telephone'        => '电话/手机',
        'type'             => '登录类型',
        'uid'              => '旧的登陆方式，用户id',
        'unique_id'        => '第三方登录唯一id',
        'url'              => 'url地址',
        'user_id'          => '用户id',
        'user_profile'     => '用户相关信息',
    ];

    /**
     * 分组 数组格式
     * @var array
     */
    protected $categories = array();

    /**
     * 给数组降维
     * @param array $array [description]
     * @return [type] [description]
     * @author Bruce 2020-07-29
     *
     */
    public function reduceArray(array $array)
    {
        $return = [];
        array_walk_recursive($array, function ($x, $key) use (&$return) {
            $return[] = $x;
        });
        return $return;
    }

    /**
     * 获取所有的键
     * @param array $array [description]
     * @return [type] [description]
     * @author Bruce 2020-07-29
     *
     */
    public function getKeys(array $array)
    {
        $return = [];
        array_walk_recursive($array, function ($x, $key) use (&$return) {
            $return[] = $key;
        });
        return $return;
    }

    /**
     * 获取所有参数和返回结果里面的key
     * @param string $intput_file [description]
     * @return [type] [description]
     * @author Bruce 2020-07-29
     *
     */
    public function getAllKeys($intput_file = 'passport.postman_collection.json')
    {
        $data = json_decode(file_get_contents($intput_file), true);
        $params = $this->getValuesFromMultiarray($data, ['key']);
        $bodys = $this->getValuesFromMultiarray($data, ['body']);
        $body_keys = $body_params = [];
        foreach ($bodys as $key => $value) {
            $data = json_decode($value, true);
            if (!empty($data['data']) && is_array($data['data'])) {
                $body_keys[] = $this->getKeys($data['data']);
            }
        }
        $body_keys = $this->reduceArray($body_keys);
        $raw_keys = array_merge($body_keys, $params);
        $keys = array_filter($raw_keys, function ($value) {
            return $value && !is_int($value) && !preg_match('/^[A-Z]/', $value);
        });
        sort($keys);
        $keys = array_unique($keys);
        // $keys = array_diff($keys, array_keys(self::DESCRIPTION_ENUMS));
        file_put_contents('keys.txt', implode('=>\'\'' . PHP_EOL, $keys) . '=>\'\'');
    }

    /**
     * 多维数组降维，获取所有的值
     * @param array $array [description]
     * @param array $keys [description]
     * @return [type] [description]
     * @author Bruce 2020-07-29
     *
     */
    public function getValuesFromMultiarray(array $array, array $keys)
    {
        $return = [];
        array_walk_recursive($array, function ($x, $key) use (&$return, $keys) {
            if (in_array($key, $keys)) {
                $return[$key][] = $x;
            }
        });
        if (!$return) {
            return;
        }
        return count($keys) == 1 ? $return[$keys[0]] : $return;
    }

    /**
     * 获取markdown文档
     * @return [type] [description]
     * @author Bruce 2020-07-17
     *
     */
    public function htag(int $num, $title)
    {
        return PHP_EOL . str_repeat('#', $num) . ' ' . $title . PHP_EOL;
    }

    /**
     * 代码片段
     * @param string $code [description]
     * @param string $language [description]
     * @return [type] [description]
     * @author Bruce 2020-07-22
     *
     */
    public function codeBlock($code = '', $language = 'http')
    {
        if (mb_strlen($code) > 3000) {
            $code = '出错了';
        }
        $codeBlock = PHP_EOL . '```' . $language . PHP_EOL . $code . PHP_EOL . '```' . PHP_EOL;
        return $codeBlock;
    }

    /**
     * 输出表格
     * @param  [type] $columns_name   [description]
     * @param  [type] $rows           [description]
     * @return [type] [description]
     * @author Bruce 2020-07-17
     *
     */
    public function table($rows, $type = '')
    {
        if (!$rows) {
            return PHP_EOL;
        }

        # 取第一行的列作为表头 marked by Bruce <2020-07-27 18:00:55>
        $columns_name = array_keys($rows[0]);
        if ($type == 'params') {
            $columns_name = [
                '参数名',
                '示例值',
                '描述',
                '类型',
                '是否必须'
            ];
        } elseif ($type == 'header') {
            $columns_name = [
                '参数名',
                '值'
            ];
        }
        // 去掉一些不必显示的
        $title = '|' . implode('|', $columns_name) . '|' . PHP_EOL;
        $str = '|' . str_repeat('--|', count($columns_name)) . PHP_EOL;

        foreach ($rows as $key => $value) {
            if (!in_array($value['key'], self::EXCEPT) && empty($value['disabled'])) {
                # 截取超长的值
                if (isset($value['value']) && mb_strlen($value['value']) > self::MAX_SHOW) {
                    $value['value'] = substr($value['value'], 0, self::MAX_SHOW) . '....';
                }
                if (isset($value['src']) && mb_strlen($value['src']) > self::MAX_SHOW) {
                    $value['value'] = substr($value['src'], 0, self::MAX_SHOW) . '....';
                }
                # 截取描述
                if (isset($value['description']) && !empty($value['description']) && mb_strlen($value['description']) > self::MAX_SHOW) {
                    $value['description'] = substr($value['description'], 0, self::MAX_SHOW) . '....';
                }
                if (!isset($value['value'])) {
                    $value['value'] = '';
                }
                if (!isset($value['required'])) {
                    $value['required'] = '';
                }
                # 补齐参数属性
                if ($type == 'params') {
                    $value = [
                        'key'         => $value['key'] ?? '',
                        'value'       => $value['value'] ?? '',
                        'description' => $this->descriptionEnum($value['key']),
                        'type'        => is_numeric($value['value']) && strlen($value['value']) < 11 ? 'number' : 'string',
                        'required'    => $value['required'] ?? '是',
                        # 如果是参数表格，追加必填，手动更改，因为大部分都是必填
                    ];
                } elseif ($type == 'header') {
                    $value = [
                        'key'   => $value['key'] ?? '',
                        'value' => $value['value'] ?? '',
                    ];
                }
                $new[] = '| ' . implode(' | ', $value) . ' |';
            }
        }
        if (empty($new)) {
            return;
        }
        $content = implode(PHP_EOL, $new);
        return PHP_EOL . $title . $str . $content . PHP_EOL;
    }

    /**
     * 获取全局默认描述
     * @param string $value [description]
     * @return [type] [description]
     * @author Bruce 2020-07-27
     *
     */
    public function descriptionEnum($key = '')
    {
        $enums = self::DESCRIPTION_ENUMS;
        return $enums[$key] ?? '';
    }

    /**
     * 返回描述信息
     * @param  [type] $description    [description]
     * @return [type] [description]
     * @author Bruce 2020-07-17
     *
     */
    public function description($description)
    {
        return PHP_EOL . '> Description:' . $description . PHP_EOL;
    }

    /**
     * 生成markdown文档
     * @return [type] [description]
     * @author Bruce 2020-07-18
     *
     */
    public function getMarkdownDocument($intput_file, $output_file)
    {

        if (!file_exists($intput_file)) {
            die($intput_file . ' file doesn\'t exist.');
        }

        $data = json_decode(file_get_contents($intput_file), true);
        if (json_last_error()) {
            die($intput_file . ' is not a json file.');
        }

        $this->categories[] = $data['info']['name'];

        $this->echoItem($data['item'], $output_file);

    }

    public function echoItem($item, $output_file)
    {

        foreach ($item as $key => $value) {

            // 多级目录递归
            if (isset($value['item'])) {
                $this->categories[] = $value['name'];
                $this->echoItem($value['item'], $output_file);
            } else {
                # 跳过copy的
                if (strpos($value['name'], 'Copy')) {
                    continue;
                }
                $output_path = $output_file;
                # 创建文件夹和文件
                $path = $value['request']['url']['path'];
                $pathCount = count($path);
                for ($p = 0; $p < $pathCount - 1; $p++) {
                    $output_path .= '/' . $path[$p];
                    File::isDirectory($output_path) or File::makeDirectory($output_path, 0777, true, true);
                }

                # 开启缓冲区
                ob_start();

                ### 配置
                # 输出title
                $this->echoTitle($value['name']);
                $this->echoUrlName(implode('/', $path));
                # 输出分组
                $this->echoCategories($this->categories);

                ## markdown正文
                echo $this->htag(3, "请求URI:");
                # 输出http请求
                echo $this->codeBlock($value['request']['url']['raw'], 'http');
                # 输出描述
                if (!empty($value['request']['description'])) {
                    echo $this->description($value['request']['description']);
                }

                # 输出参数注解
                # $this->echoParamsDesc( $this->categories);

                # 输出请求header参数
                echo $this->htag(3, "Header参数：");
                if (!empty($value['request']['header'])) {
                    echo $this->table($value['request']['header'], 'header');
                }
                if (isset($value['request']['body']) && isset($value['request']['body']['mode'])) {
                    echo $this->htag(3, "请求参数：【" . $value['request']['body']['mode'] . "】");
                }

                if (isset($value['request']['body']['formdata']) && !empty($value['request']['body']['formdata'])) {
                    echo $this->table($value['request']['body']['formdata'], 'params');
                }
                if (isset($value['request']['body']['xwwwformurlencoded']) && !empty($value['request']['body']['xwwwformurlencoded'])) {
                    echo $this->table($value['request']['body']['xwwwformurlencoded'], 'params');
                }
                if (isset($value['request']['body']['raw']) && !empty($value['request']['body']['raw'])) {
                    $rawParams = json_decode($value['request']['body']['raw'], true);
                    $rawArray = array();
                    foreach ($rawParams as $rawParamKey => $rawParamValue) {
                        $rawArrayItem['key'] = $rawParamKey;
                        $rawArrayItem['value'] = $rawParamValue;
                        $rawArray[] = $rawArrayItem;
                    }
                    echo $this->table($rawArray, 'params');
                }


                # 输出返回
                $example_numbers = count($value['response']);
                echo $this->htag(4, $value['name'] . "(共{$example_numbers}个示例)");

                foreach ($value['response'] as $k => $example) {
                    $k++;
                    # 输出example名称
                    echo $this->htag(5, "示例{$k}：" . $example['name']);
                    $request = $example['originalRequest'];

                    # 输出http请求
                    $code = $request['method'] . ' ' . $request['url']['raw'];
                    echo $this->codeBlock($code, 'http');

                    # 输出header
                    $headers = $request['header'];
                    if ($headers) {
                        echo $this->htag(6, 'Request Header:');
                        echo $this->table($headers, 'header');
                    }

                    # 输出参数
                    $params = $request['body']['urlencoded'] ?? '';
                    if (empty($params)) {
                        $params = $request['url']['query'] ?? '';
                    }
                    if ($params) {
                        echo $this->htag(6, 'Request Params:');
                        echo $this->table($params, 'params');
                    }

                    # 输出响应结果
                    $response_body = $example['body'];
                    if ($response_body) {
                        echo $this->htag(6, 'Response:');
                        echo $this->codeBlock($response_body, 'json');
                    }

                    echo PHP_EOL . '---' . PHP_EOL;
                }

                # 如果没有示例，就输出分割线
                if ($example_numbers == 0) {
                    echo PHP_EOL . '---' . PHP_EOL;
                }

                # 获取缓冲区内容，写入文件
                $contents = ob_get_clean();
                file_put_contents($output_path . '/' . $path[$pathCount - 1] . '.md', $contents);
            }
        }
        return true;
    }

    public function echoTitle($title)
    {
        echo '---' . PHP_EOL;
        echo 'title: ' . $title . PHP_EOL;
    }

    public function echoUrlName($path)
    {
        echo 'url_name: ' . $path . PHP_EOL;
        echo 'sticky: 1' . PHP_EOL;
        echo 'cover: false' . PHP_EOL;
        echo 'date: ' . now()->toDateTimeString() . PHP_EOL;
    }

    public function echoCategories($categories)
    {
        echo 'categories: ' . PHP_EOL;
        foreach ($categories as $item) {
            echo '- ' . $item . PHP_EOL;
        }
        echo '---' . PHP_EOL . PHP_EOL;
    }

    /**
     * 输出字段解释
     * @return [type] [description]
     * @author Bruce 2020-07-29
     *
     */
    public function echoParamsDesc($item)
    {
        echo '---' . PHP_EOL;
        echo ' "title": "' . $item['name'] . '"' . PHP_EOL;
        echo $this->htag(4, '参数解释，（字母顺序）：');
        echo PHP_EOL . '|字段名|解释|字段名|解释|' . PHP_EOL;
        echo '|--|--|--|--|' . PHP_EOL;
        $descs = self::DESCRIPTION_ENUMS;
        foreach ($descs as $key => $value) {
            $new_desc[] = $key;
            $new_desc[] = $value;
        }
        $times = ceil(count($new_desc) / 4);
        $str = str_repeat('|%s|%s|%s|%s|' . PHP_EOL, $times);
        $new_desc = array_pad($new_desc, 4 * $times, ' ');
        // var_dump($new_desc);
        $descs = vsprintf($str, $new_desc);
        echo $descs;
    }
}
