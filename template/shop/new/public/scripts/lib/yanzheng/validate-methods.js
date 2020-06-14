require(['jquery','jquery.validate'],function($,validate){
    $.validator.addMethod("isPhone", function(value, element) {  
        var length = value.length;  
        var regPhone = /^1(3|4|5|7|8|9)\d{9}$/;  
        return this.optional(element) || (regPhone.test( value ) );    
    }, "请正确填写您的手机号码");
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
        var chrnum2 = /^((0\d{2,3}-\d{7,8})|(1[34578]\d{9}))$/;
        return this.optional(element) || (chrnum2.test(value));
    }, "请输入正确的联系方式");
// 输入固话或者手机号码
    $.validator.addMethod("idCard", function(value, element) {
        var idCard = /^\d{6}(18|19|20)?\d{2}(0[1-9]|1[012])(0[1-9]|[12]\d|3[01])\d{3}(\d|[xX])$/;
        return this.optional(element) || (idCard.test(value));
    }, "请输入正确的身份证号码");

})