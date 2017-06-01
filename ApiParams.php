<?php
require_once 'init.php';
$service = $_GET['service'];

list($className, $methodName) = explode('/', $service);
$className = str_replace('Controller', '', $className);
$classFile = $root . D_S . 'application/controllers' . D_S . $className . '.php';
include_once $classFile;
$rMethod = new ReflectionMethod($className . 'Controller', $methodName);
$docComment = $rMethod->getDocComment();
$docCommentArr = explode("\n", $docComment);

$description = '';
$params = array();
$returns = array();
$typeMaps = array(
	'string' => '字符串',
	'int' => '整型',
	'float' => '浮点型',
	'boolean' => '布尔型',
	'date' => '日期',
	'array' => '数组',
	'fixed' => '固定值',
	'enum' => '枚举类型',
	'object' => '对象',
);
foreach ($docCommentArr as $comment) {
	$comment = trim($comment);

	//标题描述
	if (empty($description) && strpos($comment, '@') === false && strpos($comment, '/') === false) {
		$description = substr($comment, strpos($comment, '*') + 1);
		continue;
	}

	//@desc注释
	$pos = stripos($comment, '@desc');
	if ($pos !== false) {
		$descComment = substr($comment, $pos + 5);
		continue;
	}

	//@exception注释
	$pos = stripos($comment, '@exception');
	if ($pos !== false) {
		$exceptions[] = explode(' ', trim(substr($comment, $pos + 10)));
		continue;
	}

	// 参数
	$pos = stripos($comment, '@param');
	if ($pos !== false) {
		$paramCommentArr = explode(' ', substr($comment, $pos + 6));
		//将数组中的空值过滤掉，同时将需要展示的值返回
		$paramCommentArr = array_values(array_filter($paramCommentArr));
		if (count($paramCommentArr) < 2) {
			continue;
		}
		if (!isset($paramCommentArr[2])) {
			$paramCommentArr[2] = ''; //可选的字段说明
		} else {
			//兼容处理有空格的注释
			//$paramCommentArr[2] = implode(' ', array_slice($paramCommentArr, 2));
		}
		$params[] = $paramCommentArr;
		continue;
	}

	//@return注释
	$pos = stripos($comment, '@return');
	if ($pos === false) {
		continue;
	}

	$returnCommentArr = explode(' ', substr($comment, $pos + 8));
	//将数组中的空值过滤掉，同时将需要展示的值返回
	$returnCommentArr = array_values(array_filter($returnCommentArr));
	if (count($returnCommentArr) < 2) {
		continue;
	}
	if (!isset($returnCommentArr[2])) {
		$returnCommentArr[2] = ''; //可选的字段说明
	} else {
		//兼容处理有空格的注释
		$returnCommentArr[2] = implode(' ', array_slice($returnCommentArr, 2));
	}

	$returns[] = $returnCommentArr;
}
// echo '<pre>'; print_r($params); exit;
?>
<?php
echo <<<EOT
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{$service} - 在线接口文档</title>
    <script src="public/jquery/1.11.3/jquery.min.js"></script>
    <link rel="stylesheet" href="public/semantic-ui/2.1.6/semantic.min.css">
    <link rel="stylesheet" href="public/semantic-ui/2.1.6/components/table.min.css">
    <link rel="stylesheet" href="public/semantic-ui/2.1.6/components/container.min.css">
    <link rel="stylesheet" href="public/semantic-ui/2.1.6/components/message.min.css">
    <link rel="stylesheet" href="public/semantic-ui/2.1.6/components/label.min.css">
</head>
<body><br />
    <div class="ui text container" style="max-width: none !important;">
        <div class="ui floating message">

EOT;

echo "<h2 class='ui header'>接口：$service</h2><br/> <span class='ui teal tag label'>$description</span>";
echo '<h3><strong style="color:red">注意：</strong> 除了User.AuthWechat接口外，其他所有接口都需要以header的形式传token<br /></h3><strong style="color:red">参数格式：</strong> <br>string => 字符串, int => 整型, float => 浮点型, boolean => 布尔型, date => 日期, array => 数组, fixed => 固定值, enum => 枚举类型, object => 对象<br/>';

/**
 * 接口说明 & 接口参数
 */
echo <<<EOT
            <div class="ui raised segment">
                <span class="ui red ribbon label">接口说明</span>
                <div class="ui message">
                    <p>{$descComment}</p>
                </div>
            </div>
            <h3>接口参数</h3>
            <table class="ui red celled striped table" >
                <thead>
                    <tr><th>参数名字</th><th>类型</th><th>是否必须</th><th>默认值</th><th>其他</th><th>说明</th></tr>
                </thead>
                <tbody>
EOT;
foreach ($params as $key => $rule) {
	$name = $rule[0];
	if (!isset($rule[1])) {
		$rule[1] = 'string';
	}
	$type = isset($typeMaps[$rule[1]]) ? $typeMaps[$rule[1]] : $rule[1];
	$require = isset($rule[2]) && strtolower($rule[2]) == 'y' ? '<font color="red">必须</font>' : '可选';
	$default = isset($rule[3]) ? $rule[3] : '';
	if ($default === NULL) {
		$default = 'NULL';
	} else if (is_array($default)) {
		$default = json_encode($default);
	} else if (!is_string($default)) {
		$default = var_export($default, true);
	}

	$other = '';
	if (isset($rule['min'])) {
		$other .= ' 最小：' . $rule['min'];
	}
	if (isset($rule['max'])) {
		$other .= ' 最大：' . $rule['max'];
	}
	if (isset($rule['range'])) {
		$other .= ' 范围：' . implode('/', $rule['range']);
	}
	$desc = isset($rule['4']) ? trim($rule['4']) : '';

	echo "<tr><td>$name</td><td>$type</td><td>$require</td><td>$default</td><td>$other</td><td>$desc</td></tr>\n";
}

/**
 * 返回结果
 */
echo <<<EOT
              </tbody>
          </table>
          <h3>返回结果</h3>
          <table class="ui green celled striped table" >
            <thead>
                <tr><th>返回字段</th><th>类型</th><th>说明</th></tr>
            </thead>
            <tbody>
EOT;

foreach ($returns as $item) {
	$name = $item[1];
	$type = isset($typeMaps[$item[0]]) ? $typeMaps[$item[0]] : $item[0];
	$detail = $item[2];

	echo "<tr><td>$name</td><td>$type</td><td>$detail</td></tr>";
}
echo <<<EOT
           </tbody>
       </table>
EOT;

/**
 * 异常情况
 */
if (!empty($exceptions)) {
	echo <<<EOT
           <h3>异常情况</h3>
           <table class="ui red celled striped table" >
            <thead>
                <tr><th>错误码</th><th>错误描述信息</th>
                </thead>
                <tbody>
EOT;

	foreach ($exceptions as $exItem) {
		$exCode = $exItem[0];
		$exMsg = isset($exItem[1]) ? $exItem[1] : '';
		echo "<tr><td>$exCode</td><td>$exMsg</td></tr>";
	}

	echo <<<EOT
              </tbody>
          </table>
EOT;
}

/**
 * 返回结果
 */
echo <<<EOT
  </tbody>
</table>
<h3>
    请求模拟 &nbsp;&nbsp;
    <select name="request_type"><option value="1">POST</option><option value="2">GET</option></select>
EOT;

$url = curPageURL();
echo '&nbsp;<input name="request_url" value="' . $url . '" style="width:500px; height:24px; line-height:18px; font-size:13px;position:relative;top:-2px; padding-left:5px;" />';
echo <<<EOT
    <input type="submit" name="submit" value="send" id="submit" />
</h3>
<table class="ui green celled striped table" >
    <thead>
        <tr><th>key</th><th>value</th></tr>
    </thead>
    <tbody id="params">
        <tr>
            <td><input name="request_key[]" value="" style="width:400px; height:30px; line-height:18px; font-size:15px" class="C_input" /></td>
            <td><input name="request_value[]" value="" style="width:400px; height:30px; line-height:18px; font-size:15px" class="C_input" /></td>
        </tr>
EOT;
echo <<<EOT
    </tbody>
</table>
EOT;

/**
 * JSON结果
 */
echo <<<EOT
<div class="ui blue message" id="json_output">
</div>
EOT;

/**
 * 底部
 */
echo <<<EOT
<div class="ui blue message">
  <strong>温馨提示：</strong> 此接口参数列表根据后台代码自动生成，可将 ?service= 改成您需要查询的接口/服务
</div>
<p style="text-align:center;">感谢PhalApi提供参考<p>
</div>
</div>
</body>
</html>
EOT;

function curPageURL() {
	$pageURL = 'http';
	if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
		$pageURL .= "s";
	}
	$pageURL .= "://";
	$this_page = $_SERVER["REQUEST_URI"];
	// echo "<pre>";
	// print_r($_REQUEST['service']);
	// echo "</pre>";
	// exit;
	// 只取 ? 前面的内容
	if (strpos($this_page, "?") !== false) {
		$this_pages = explode("?", $this_page);
		$this_page = reset($this_pages);
	}
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $this_page;
	} else {
		//控制器和方法组成URL
		$pageURL .= $_SERVER["SERVER_NAME"] . '/' . str_replace(array('.', 'Controller', 'Action'), array('/', ''), $_REQUEST['service']);
	}
	return $pageURL;
}

/**
 * 输出json格式数据
 * @param number $code
 * @param array $data
 * @param string $msg
 */
function outputJson($code = 0, array $data = array(), $msg = '') {
	$data = is_array($data) ? $data : array();

	header('Content-Type:application/json; charset=utf-8');
	header('Cache-Control: no-cache, must-revalidate');
	header("Access-Control-Allow-Origin: {$this->config->item('allow_header')}"); // 允许任何访问(包括ajax跨域)
	header('Access-Control-Allow-Credentials: true');
	header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

	exit(json_encode(array(
		'data' => $data,
		'code' => intval($code),
		'msg' => $msg,
	)));
}
?>
<script type="text/javascript">
    $(function(){
        $("#params").on("click",".C_input", function() {
            //alert(1122);
            var num=$(this).parent().parent().nextAll().length; max=$("#params tr").length;
            if(num == 0) {
                var temp =   '<tr>' +
                '<td><input name="request_key[]" value="" style="width:400px; height:30px; line-height:18px; font-size:15px" class="C_input" /></td>' +
                '<td><input name="request_value[]" value="" style="width:400px; height:30px; line-height:18px; font-size:15px" class="C_input" /></td>' +
                '</tr>';
                $("#params").append(temp);
            }
        });

        var $input=$("input");
        var url="";
        $.each($input,function(index,ele){
            if($(ele).attr("name")=="request_url"){
                url=$(ele).val();
                console.log(url)
            }
        });

        var type="";
        var data={};
        $("#submit").on("click",function(){
            if($("select").val()==1){
                type="post";
            }else {
                type="get";
            }

            var $tr=$("#params").find("tr");
            $.each($tr,function(index,ele){
                var $td=$(ele).find("td");
                data[$td.eq(0).find("input").val()]=$td.eq(1).find("input").val();
            });

            $.ajax({
                url:url,
                type:type,
                data:data,
                success:function(res){
                    var json_text = JSON.stringify(res, null, 4);    // 缩进4个空格
                    // 浏览器中打印输出结果
                    var data=JSON.parse(JSON.stringify(res));
                    // console.log(data)
                    $("#json_output").html('<pre>' + json_text + '</pre>');
                },
                error:function(error){
                    console.log(error)
                }
            });
        });

    });
</script>