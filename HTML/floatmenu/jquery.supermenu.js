//客服插件
$(document).ready(function() {
	$(document).on("mouseleave", ".hovermenu, .hovermenu .a-top", function() {
		$(".hovermenu").find(".d").hide()
	});
	$(document).on("mouseenter", ".hovermenu .a-top", function() {
		$(".hovermenu").find(".d").hide()
	});
	$(document).on("click", ".hovermenu .a-top", function() {
		$("html,body").animate({
			scrollTop: 0
		})
	});
	$(window).scroll(function() {
		var st = $(document).scrollTop();
		var $top = $(".hovermenu .a-top");
		if (st > 400) {
			$top.css({
                opacity: 1,
			})
		} else {
			if ($top.is(":visible")) {
                $top.css({
                    opacity: 0,
                })
			}
		}
	})
});

$(document).ready(function() {
	//判断网页宽度
	var DW = $(document).width(); 
	if(DW <= 720){  
	$(document).on("click", ".hovermenu .i", function() {
		var _this = $(this);
		var s = $(".hovermenu");
		var isQQ = _this.hasClass("a-qq");
        var isWANG = _this.hasClass("a-wang");
        var isWX = _this.hasClass("a-wechat");
        var isDY = _this.hasClass("a-douyin");
        var isKS = _this.hasClass("a-kuaishou");
		var isTEL = _this.hasClass("a-tel");
        var isTEL2 = _this.hasClass("a-tel2");
        var isADD = _this.hasClass("a-add");
        var isEM = _this.hasClass("a-email");
        var isBUY = _this.hasClass("a-buy");
        var isDOWN = _this.hasClass("a-down");
        var isRESE = _this.hasClass("a-reserve");
        var isPAYEE = _this.hasClass("a-payee");
        var isAPP = _this.hasClass("a-app");
        var isWAP = _this.hasClass("a-wap");
		var isQrcode = _this.hasClass("a-qrcode");
        
		if (isQQ) {
			s.find(".d-qq").toggle().parent(".a").siblings().children(".d").hide();
            $(".d-link").hide();
            $(".d-share").hide();
		}
        if (isWANG) {
			s.find(".d-wang").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
        if (isWX) {
			s.find(".d-wechat").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
        if (isDY) {
			s.find(".d-douyin").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
        if (isKS) {
			s.find(".d-kuaishou").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
		if (isTEL) {
			s.find(".d-tel").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
        if (isTEL2) {
			s.find(".d-tel2").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
        if (isADD) {
			s.find(".d-add").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
        if (isEM) {
			s.find(".d-email").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
        if (isBUY) {
			s.find(".d-buy").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
        if (isDOWN) {
			s.find(".d-down").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
        if (isRESE) {
			s.find(".d-reserve").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
        if (isPAYEE) {
			s.find(".d-payee").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
        if (isAPP) {
			s.find(".d-app").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
        if (isWAP) {
			s.find(".d-wap").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
		if (isQrcode) {
			s.find(".d-qrcode").toggle().parent(".a").siblings().children(".d").hide();
             $(".d-link").hide();
            $(".d-share").hide();
		}
	});
	}
	else{  
	$(document).on("mouseenter", ".hovermenu .i", function() {
		var _this = $(this);
		var s = $(".hovermenu");
		var isQQ = _this.hasClass("a-qq");
        var isWANG = _this.hasClass("a-wang");
        var isWX = _this.hasClass("a-wechat");
        var isDY = _this.hasClass("a-douyin");
        var isKS = _this.hasClass("a-kuaishou");
		var isTEL = _this.hasClass("a-tel");
        var isTEL2 = _this.hasClass("a-tel2");
        var isADD = _this.hasClass("a-add");
        var isEM = _this.hasClass("a-email");
        var isBUY = _this.hasClass("a-buy");
        var isDOWN = _this.hasClass("a-down");
        var isRESE = _this.hasClass("a-reserve");
        var isPAYEE = _this.hasClass("a-payee");
        var isAPP = _this.hasClass("a-app");
        var isWAP = _this.hasClass("a-wap");
		var isQrcode = _this.hasClass("a-qrcode");
		if (isQQ) {
			s.find(".d-qq").show().parent(".a").siblings().children(".d").hide()
		}
        if (isWANG) {
			s.find(".d-wang").show().parent(".a").siblings().children(".d").hide()
		}
        if (isWX) {
			s.find(".d-wechat").show().parent(".a").siblings().children(".d").hide()
		}
         if (isDY) {
			s.find(".d-douyin").show().parent(".a").siblings().children(".d").hide()
		}
        if (isKS) {
			s.find(".d-kuaishou").show().parent(".a").siblings().children(".d").hide()
		}
		if (isTEL) {
			s.find(".d-tel").show().parent(".a").siblings().children(".d").hide()
		}
        if (isTEL2) {
			s.find(".d-tel2").show().parent(".a").siblings().children(".d").hide()
		}
        if (isADD) {
			s.find(".d-add").show().parent(".a").siblings().children(".d").hide()
		}
        if (isEM) {
			s.find(".d-email").show().parent(".a").siblings().children(".d").hide()
		}
        if (isBUY) {
			s.find(".d-buy").show().parent(".a").siblings().children(".d").hide()
		}
        if (isDOWN) {
			s.find(".d-down").show().parent(".a").siblings().children(".d").hide()
		}
        if (isRESE) {
			s.find(".d-reserve").show().parent(".a").siblings().children(".d").hide()
		}
        if (isPAYEE) {
			s.find(".d-payee").show().parent(".a").siblings().children(".d").hide()
		}
        if (isAPP) {
			s.find(".d-app").show().parent(".a").siblings().children(".d").hide()
		}
        if (isWAP) {
			s.find(".d-wap").show().parent(".a").siblings().children(".d").hide()
		}
		if (isQrcode) {
			s.find(".d-qrcode").show().parent(".a").siblings().children(".d").hide()
		}
	});
	}
});

$(document).ready(function() {
	$(".a-show").click(function() {
	$(this).hide();
	$(".a").slideDown("slow");
	});
	$(".a-hide").click(function() {
	$(this).hide();		
    $(".a").slideUp("slow");
	$(".a-show").show();
	}); 

});

$(document).ready(function() {
	$(".a-share").click(function() {
	$(".d-share").fadeToggle(500);
    $(".d-link").fadeOut();
	});
    $(".d-share .d-close").click(function() {
    $(".d-share").fadeOut();
	});
    $(".a-link").click(function() {
	$(".d-link").fadeToggle(500);
    $(".d-share").fadeOut();
	});
    $(".d-link .d-close").click(function() {
    $(".d-link").fadeOut();
	});
});

//二维码选项
$(function(){
$(".Hpayee li").click(function(){
$(this).addClass('pay_active').siblings().removeClass('pay_active');
var index = $(this).index();
$('.Hpayee div').hide();
$('.Hpayee div:eq('+index+')').show();
});
});