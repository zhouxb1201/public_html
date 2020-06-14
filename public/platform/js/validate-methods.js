require(['jquery','jquery.validate'],function($,validate){
    $.validator.addMethod("isMobile", function(value, element) {  
        var length = value.length;  
        var regPhone = /^1(3|4|5|7|8|9)\d{9}$/;  
        return this.optional(element) || ( length == 11 && regPhone.test( value ) );    
    }, "请正确填写您的手机号码");
    $.validator.addMethod("isPhone", function(value, element) {  
        var length = value.length;  
        var regPhone = /^1(3|4|5|7|8|9)\d{9}$/;  
        return this.optional(element) || (regPhone.test( value ) );    
    }, "请正确填写您的联系方式");
    $.validator.addMethod("sku_price", function(value, element) {
        var regPhone = /^[1-9]{1}\d*(.\d{1,2})?$|^0.\d{1,2}$/;
        return this.optional(element) || ( regPhone.test( value ) );
    }, " ");

    // $.validator.addMethod("market_price", function(value, element) {
    //     var regPhone = /^[1-9]{1}\d*(.\d{1,2})?$|^0.\d{1,2}$/;
    //     return this.optional(element) || (regPhone.test( value ) );
    // }, " ");

    $.validator.addMethod("cost_price", function(value, element) {
        var regPhone = /^[1-9]{1}\d*(.\d{1,2})?$|^0.\d{1,2}$/;
        return this.optional(element) || (regPhone.test( value ) );
    }, " ");

    $.validator.addMethod("stock_num", function(value, element) {
        var regPhone = /^[1-9]{1}\d*(.\d{1,2})?$|^0.\d{1,2}$/;
        return this.optional(element) || (regPhone.test( value ) );
    }, " ");

    $.validator.addMethod("int", function(value, element) {
        var int = /^[0-9]+$/;
        return this.optional(element) || (int.test( value ) );
    }, "输入正整数");

// 只能输入英文
    $.validator.addMethod("notChinese", function(value, element) {
        var chrnum = /^[a-zA-Z0-9_]{0,}$/;
        return this.optional(element) || (chrnum.test(value));
    }, "请填写英文或数字");
// 必须输入正整数
    $.validator.addMethod("mustNum", function(value, element) {
        var chrnum1 = /^[0-9_]{0,}$/;
        return this.optional(element) || (chrnum1.test(value));
    }, "请填正整数");
// 输入固话或者手机号码
    $.validator.addMethod("storePhone", function(value, element) {
        var chrnum2 = /^((0\d{2,3}-\d{7,8})|(1[345789]\d{9}))$/;
        return this.optional(element) || (chrnum2.test(value));
    }, "请输入正确的联系方式");

    $.validator.addMethod("goodsName", function(value, element) {
            var l = 0;
            for (var i = 0; i < value.length; i++) {
                if (/[\u4e00-\u9fa5]/.test(value[i])) {
                    l += 2;
                }else {
                    l++;
                }
            }
            if (l > 60) {
                return false;
            }else{
                return true;
            }
    }, " ");

})