<?php
include('../../../../common.php');
pe_lead('hook/order.hook.php');
pe_lead('hook/alipay.hook.php');


$order_id = pe_dbhold($_g_id);
$order = $db->pe_select(order_table($order_id), array('order_id'=>$order_id));

$info = alipay_webpay($order_id);

if (!$info['result']) pe_404($info['show']);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php if ($pe['mobile']): ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0" />
<?php endif; ?>
<title>支付宝扫码支付</title>
<script type="text/javascript" src="{$pe['host_root']}public/js/jquery.js"></script>
<script type="text/javascript" src="{$pe['host_root']}public/js/qrcode.min.js"></script>
<script type="text/javascript" src="{$pe['host_root']}public/js/global.js"></script>
<script type="text/javascript" src="{$pe['host_root']}public/plugin/layer/layer.js"></script>
</head>
<body style="background:#2E3B41">
	<div class="sm_box">
		<div id="qrcode"></div>
		<p>请用支付宝扫描二维码完成支付</p>
		<div class="smje">支付金额：<span><?php echo $lan['price_ico']." ".$order['order_money']; ?></span></div>
	</div>
	<style type="text/css">
	.sm_box{width:380px; margin:70px auto 0; background:#fff; border-radius:4px; text-align:center; height:480px; font-family:微软雅黑}
	.sm_box img{width:270px; height:270px; margin-top:50px}
	.sm_box p{font-size:22px; color:#666}
	.smje{font-size:18px; color:#666}
	.smje span{color:#ff6600; font-size:22px;}
	.layui-layer{width:160px; !important}
	</style>
	<script type="text/javascript">
	/**
	 * 渲染QRCode
	 *
	 * @return void
	 */
	function renderQRCode() {
		var el = document.getElementById("qrcode");
		var text = "<?php echo $info['qrcode']; ?>";
		var qrcode = new QRCode(el, {
			text: text,
			width: 280,
			height: 280,
			colorDark : "#000000",
			colorLight : "#ffffff",
			correctLevel : QRCode.CorrectLevel.H
		});
	}
	$(function(){
		renderQRCode();

		setInterval(function(){
			$.getJSON("<?php echo $pe['host_root']; ?>public/plugin/payment/wechat/order_result.php?id=<?php echo $order_id;?>", 
            function(json){
				if (json.result) {
					layer.msg(json.show, {icon: 1});
					setTimeout(function(){
						window.location.href = json.url;
					}, 2000)
				}
			})
		}, 5000);
	})
	setTimeout('codeInvalidFun()', (60*1000)*3);
	function codeInvalidFun(){
		alert('二维码已失效，请重新支付~');
	}
	</script>
</body>
</html>