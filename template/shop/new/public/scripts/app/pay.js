
define(["jquery","layer"], function ($) {
    var pay = {};
    pay.operation = function(){
        countdown();
        $(".J-selectPay a").on('click', function () {
            $(this).addClass('selected').siblings().removeClass('selected');
        });
        //去支付
	$(".J-pay").click(function() {
            if ($('.J-selectPay>.selected').attr("data-pay") != null) {
                var out_trade_no = $('#out_trade_no').val();
                var url = '';
                switch ($('.J-selectPay>.selected').attr("data-pay")) {
                case "wechat":
                        url = __URL(SHOPMAIN+'/pay/wchatpay?no='+out_trade_no);
                        location.href=url;
                        break;
                case "alipay":
                        //跳转到支付宝
                        url = __URL(SHOPMAIN+'/pay/alipay?no='+out_trade_no);
                        window.open(url);
                        break;
                }
                
            }
	});
        
    };
    pay.wechat = function(){
        setInterval("wchatOverdue()", 1000);
        
    };
    return pay;
});
