<?php
/**
 * 接口列表 - 自动生成
 * - 对application_controller_系列的接口，进行罗列
 * - 支持多级目录扫描
 */
require_once 'init.php';

$apiDirName = $root . 'application' . D_S . 'controllers';
$listDir = listDir($apiDirName);

foreach ($listDir as $value) {
	$value = realpath($value);
	$subValue = substr($value, strpos($value, 'controllers' . D_S) + strlen('controllers' . D_S));

	//支持多层嵌套，不限级
	$arr = explode(D_S, $subValue);
	$subValue = implode(D_S, $arr);
	$apiServer = str_replace('.php', '', $subValue);
	include_once $value;
	$apiServer = $apiServer . 'Controller';

	if (!class_exists($apiServer)) {
		// 暂时不做校验
		continue;
	}

	//  左菜单的标题
	$ref = new ReflectionClass($apiServer);
	$title = "//请检测接口服务注释($apiServer)";
	$desc = '//请使用@desc 注释';
	$docComment = $ref->getDocComment();

	if ($docComment !== false) {
		$docCommentArr = explode("\n", $docComment);
		$comment = trim($docCommentArr[1]);
		$title = trim(substr($comment, strpos($comment, '*') + 1));
		foreach ($docCommentArr as $comment) {
			$pos = stripos($comment, '@desc');
			if ($pos !== false) {
				$desc = substr($comment, $pos + 5);
			}
		}
	}
	$allApiS[$apiServer]['title'] = $title;
	$allApiS[$apiServer]['desc'] = $desc;

	//过滤不需要显示的方法
	$passMethod = array('forward', 'getViewPath', 'initView', 'getInvokeArg', 'getInvokeArgs', 'getModuleName', 'getRequest', 'getResponse', 'getView', 'getViewpath', 'indexAction', 'redirect', 'setViewpath');
	$method = array_diff(get_class_methods($apiServer), $passMethod);

	sort($method);
	foreach ($method as $mValue) {
		$rMethod = new Reflectionmethod($apiServer, $mValue);
		if (!$rMethod->isPublic() || strpos($mValue, '__') === 0) {
			continue;
		}

		$title = '//请检测函数注释';
		$desc = '//请使用@desc 注释';
		$docComment = $rMethod->getDocComment();
		if ($docComment !== false) {
			$docCommentArr = explode("\n", $docComment);
			$comment = trim($docCommentArr[1]);
			$title = trim(substr($comment, strpos($comment, '*') + 1));

			foreach ($docCommentArr as $comment) {
				$pos = stripos($comment, '@desc');
				if ($pos !== false) {
					$desc = substr($comment, $pos + 5);
				}
			}
		}
		$service = $apiServer . '/' . ($mValue);
		$allApiS[$apiServer]['methods'][$service] = array(
			'service' => $service,
			'title' => $title,
			'desc' => $desc,
		);
	}
}

//字典排列
ksort($allApiS);

function listDir($dir) {
	$dir .= substr($dir, -1) == D_S ? '' : D_S;
	$dirInfo = array();
	foreach (glob($dir . '*') as $v) {
		if (is_dir($v)) {
			$dirInfo = array_merge($dirInfo, listDir($v));
		} else {
			$dirInfo[] = $v;
		}
	}
	return $dirInfo;
}
$table_color_arr = explode(" ", "red orange yellow olive teal blue violet purple pink grey black");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $apiDirName; ?> - 接口列表</title>
    <link href="public/semantic-ui/2.1.6/semantic.min.css" rel="stylesheet">
    <script src="public/jquery/1.11.3/jquery.min.js"></script>
    <script src="public/semantic-ui/2.1.6/semantic.min.js"></script>
    <meta name="robots" content="none"/>
</head>
<body>
<br/>


<div class="ui text container" style="max-width: none !important; width: 1200px">
    <div class="ui floating message">
    	<div class="ui blue message">
    		<strong style="color:red">注意：</strong> 除了User.AuthWechat接口外，其他所有接口都需要以header的形式传token</div>
        <div class="ui grid container" style="max-width: none !important;">
            <div class="four wide column">
                <div class="ui vertical pointing menu">
                    <div class="item"><h4>服务列表</h4></div>
                    <?php
$num = 0;
foreach ($allApiS as $key => $item) {
	?>
                        <a class="item <?php if ($num == 0) {
		echo 'active';
	}?>" data-tab="<?php echo $key; ?>"><?php echo $item['title']; ?> </a>
                        <?php
$num++;
}
?>

                </div>
            </div>
            <div class="twelve wide stretched column">

                <?php
$uri = str_ireplace('ApiList.php', 'ApiParams.php', $_SERVER['REQUEST_URI']);
$num2 = 0;
foreach ($allApiS as $key => $item) {
	?>
                    <div class="ui  tab <?php if ($num2 == 0) {?>active<?php }?>" data-tab="<?php echo $key; ?>">
                        <table
                            class="ui red celled striped table <?php echo $table_color_arr[$num2 % count($table_color_arr)]; ?> celled striped table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>接口服务</th>
                                <th>接口名称</th>
                                <th>更多说明</th>
                            </tr>
                            </thead>
                            <tbody>

                            <?php
$num = 1;
	if (isset($item['methods']) && is_array($item['methods'])) {
		foreach ($item['methods'] as $mKey => $mItem) {
			$link = $uri . '?service=' . $mItem['service'];
			$NO = $num++;
			echo "<tr><td>{$NO}</td><td><a href=\"$link\" target='_blank'>{$mItem['service']}</a></td><td>{$mItem['title']}</td><td>{$mItem['desc']}</td></tr>";
		}
	}
	?>
                            </tbody>
                        </table>

                    </div>
                    <?php
$num2++;
}
?>
            </div>
        </div>
        <div class="ui blue message">
            <strong>温馨提示：</strong> 此接口服务列表根据后台代码自动生成，可在接口类的文件注释的第一行修改左侧菜单标题。
        </div>
    </div>
    </div>
<script type="text/javascript">
    $('.pointing.menu .item').tab();
    $('.ui.sticky').sticky();
</script>

</body>
</html>