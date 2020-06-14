
define(["jquery", "layer", "dialog", "common"], function ($, layer, Dialog, common) {
    var Cart = {};
    // 购物车优惠券展示隐藏
    Cart.showCoupons = function () {
        $(".cart-coupons-border").on("click", function () {
            $(this).siblings(".coupon-popup").toggleClass("hovers");
        });
        $(".icon-close").on("click", function () {
            $(this).parent().parent(".coupon-popup").addClass("hovers");
        });
        $(document).click(function (e) {
            var target = $(e.target);
            if (target.closest(".cart-coupons").length == 0) {
                $(".coupon-popup").addClass("hovers");
            }
        });
        //取每个店铺的goods_id
        var goods_id_obj = {};
        $.each($("input[name='sel_cartgoods[]']"), function () {
            var shop_id = $(this).attr('data-goods-shop-id');
            var goods_id = $(this).attr('data-goods-id');

            if (!goods_id_obj[shop_id]) {
                goods_id_obj[shop_id] = [goods_id];
            } else {
                goods_id_obj[shop_id].push(goods_id);
            }
        });
        $.each(goods_id_obj, function (i, v) {
            var input = 'input[data-shop-id=' + i + ']';

            if (v) {
                var coupon_type = getCoupon(v);
                if (coupon_type.length > 0) {
                    var coupon_html = '';
                    $.each(coupon_type, function (k, coupon) {
                        if (coupon.coupon_genre == 1) {
                            coupon_html += '<li class="clearfix">' +
                                    '<div class="coupon-amount">' +
                                    '<span class="rmb">￥</span><span class="coupon-num">' + coupon.money + '</span>' +
                                    '</div>' +
                                    '<div class="coupon-info">' +
                                    '<p class="coupon-title">' + coupon.coupon_name + ' 无门槛' + coupon.money + '元</p>';
                        } else if (coupon.coupon_genre == 2) {
                            coupon_html += '<li class="clearfix">' +
                                    '<div class="coupon-amount">' +
                                    '<span class="rmb">￥</span><span class="coupon-num">' + coupon.money + '</span>' +
                                    '</div>' +
                                    '<div class="coupon-info">' +
                                    '<p class="coupon-title">' + coupon.coupon_name + ' 满' + coupon.at_least + '元减' + coupon.money + '元</p>';
                        } else {
                            coupon_html += '<li class="clearfix">' +
                                    '<div class="coupon-amount">' +
                                    '<span class="rmb">' + coupon.discount + '折</span>' +
                                    '</div>' +
                                    '<div class="coupon-info">' +
                                    '<p class="coupon-title">' + coupon.coupon_name + ' 满' + coupon.at_least + '元打' + coupon.discount + '折</p>';
                        }
                        coupon_html += '<p class="coupon-time">' + common.timeStampTurnTime(coupon.start_time) + '-' + common.timeStampTurnTime(coupon.end_time) + '</p>' +
                                '</div><div class="coupon-op"><span data-id="' + coupon.coupon_type_id + '" class="J-receive">领取</span></div></li>';
                    });
                    $(input).siblings(".cart-coupons").find(".J_ShopCouponList").html(coupon_html);
                } else {
                    $(input).siblings(".cart-coupons").hide();
                }
            } else {

                $(input).siblings(".cart-coupons").hide();
            }
        });
        $(".cart-coupons").on('click', ".J-receive", function () {
            $(this).attr('disabled', true);
            var coupon_type_id = $(this).attr('data-id');
            //领取优惠券
            fetchCouponType(coupon_type_id);
            //设置优惠券不可再领取
            disabledFetchCoupon(coupon_type_id);
        });
        function disabledFetchCoupon(coupon_type_id) {
            $(".J-receive[data-id='" + coupon_type_id + "']").attr('disabled', true);
        }
        function getCoupon(goods_id_array) {
            var url = $('#getGoodsCouponTypeUrl').val();
            var result = {};
            $.ajax({
                type: 'POST',
                url: url,
                dataType: 'json',
                async: false,
                data: {goods_id_array: goods_id_array},
                success: function (data) {
                    result = data;
                }
            });
            return result;
        }

        function fetchCouponType(coupon_type_id) {
            var url = $('#fetchCouponTypeUrl').val();
            $.ajax({
                type: 'POST',
                url: url,
                dataType: 'json',
                data: {coupon_type_id: coupon_type_id, get_type: 4},
                success: function (data) {
                    if (data['code'] > 0) {
                        layer.open({
                            type: 1,
                            skin: 'layui-layer-rim', //加上边框
                            area: ['420px', '200px'], //宽高
                            title: '领取优惠券',
                            content: '<div class="receive_success"><p><i class="icon icon-success"></i></p>' +
                                    '<p class="receive_success_tips">领取成功</p></div>'
                        });
                    } else if (data['code'] == -2011) {
                        layer.open({
                            type: 1,
                            skin: 'layui-layer-rim', //加上边框
                            area: ['420px', '200px'], //宽高
                            title: '领取优惠券',
                            content: '<div class="tip-info"><div class="fl"><i></i></div><div class="fr">' +
                                    '<p class="coupon_desc">您已经领取过了！</p></div></div>'
                        });
                    } else {
                        layer.msg(data['message']);
                    }
                }
            });
        }
    };

    //购物车操作的js
    Cart.car = function () {
        // 购物车数量控制的js
        var $plus = $(".plus"), $reduce = $(".minus"), $all_sum = $(".text-amount");
        $('.J-cartsubmit').on('click', function () {
            if ($(this).hasClass('submit-btn-disabled')) {
                return;
            }
            if (Dialog.isLogin(1))
            {
                selcart_submit();
            }else{
                Dialog.loginMember("会员登录", ".login-dialog");
            }
        });
        // 清空购物车
        $(".J-clear-cart").click(function () {
            var sel_goods = new Array();// 保存选中要购买的商品
            var obj_cart_goods = $("input[name='sel_cartgoods[]']");
            $(obj_cart_goods).each(function () {
                if ($(this).is(':checked')) {
                    sel_goods.push($(this).val());
                }
            });
            var goods_id_arr = "";
            for (var k = 0; k < sel_goods.length; k++) {
                goods_id_arr += sel_goods[k] + ",";
            }
            goods_id_arr = goods_id_arr.substr(0, goods_id_arr.length - 1);
            if (!goods_id_arr) {
                return;
            }
            layer.confirm('确认要删除选择的商品吗？', {
                btn: ['确定', '取消'], //按钮
                title: "删除商品"
            }, function (index) {
                layer.close(index);
                $.ajax({
                    url: __URL(SHOPMAIN + "/goods/deleteshoppingcartbyid"),
                    type: "POST",
                    data: {"cart_id_array": goods_id_arr},
                    success: function (data) {
                        if (data["code"] > 0)
                        {
                            location.reload();
                        }
                    }
                });
            });
        });
        /**
         * 去结算
         */
        function selcart_submit() {
            var cart_id_arr = [];
            var obj_cart_goods = $("input[name='sel_cartgoods[]']");
            $(obj_cart_goods).each(function () {
                if ($(this).is(':checked')) {
                    cart_id_arr.push($(this).val());
                }
            });
            if (cart_id_arr.length > 0) {
                $.ajax({
                    url: __URL(SHOPMAIN + "/member/ordercreatesession"),
                    type: "post",
                    data: {"tag": "cart", "cart_id": cart_id_arr},
                    success: function (res) {
                        location.href = __URL(SHOPMAIN + "/member/paymentorder");
                    }
                });
            }
        }
        //点击增加购物车数量
        $plus.click(function () {
            var $inputVal = $(this).prev("input"), $count = parseInt($inputVal.val());
            var $obj = $(this).parents(".item-amount").find(".minus"),
                    $priceTotalObj = $(this).parents(".item-content").find(".sum_price"),
                    $price = $(this).parents(".item-content").find(".unitPrice").data("price"), //单价
                    $priceTotal = 0;
            // 获取到当前商品，然后判断数量
            var count = 0;
            var temp_num = 0;// 要改变的数量
            var max_buy = $(this).parent().data("max_buy");
            var stock = $(this).parent().data("stock");
            var goodsid = $(this).parent().data("goods_id");
            var cart_id = $(this).parent().data("id");
            count = $(".J-amount[data-goods_id='" + goodsid + "']").length;
            if (max_buy == 0) {// 不限购
                if ($count < stock) {
                    // 正常情况
                    $count++;
                    temp_num = $count;
                } else {
                    temp_num = stock;// 最大库存
                    layer.msg("超出范围");
                }
            } else {
                // 限购
                if (count > 1 && count >= max_buy) {// 同样商品，不同SKU，只有限购的情况下，才需要判断count的数量
                    temp_num = max_buy;
                    layer.msg("该商品限购" + temp_num + "件");
                } else if ($count < max_buy) {
                    $count++;
                    temp_num = $count;// 正常情况
                } else {
                    temp_num = max_buy;
                    layer.msg("该商品限购" + temp_num + "件");
                }
            }
            $inputVal.val(temp_num);
            $priceTotal = temp_num * $price;
            $priceTotal = $priceTotal.toFixed(2);
            $inputVal.attr("value", $count);
            $priceTotalObj.html("￥" + $priceTotal);
            $priceTotalObj.data("total", $priceTotal);
            totalMoney();
            updateGoodsNumber(cart_id, temp_num);
            if ($inputVal.val() > 1 && $obj.hasClass("reSty")) {
                $obj.removeClass("reSty");
            }
        });
        //点击减少购物车数量
        $reduce.click(function () {
            var $inputVal = $(this).next("input"),
                    cart_id = $(this).parent().data("id"),
                    $count = parseInt($inputVal.val()),
                    $priceTotalObj = $(this).parents(".item-content").find(".sum_price"),
                    $price = $(this).parents(".item-content").find(".unitPrice").data("price"); //单价

            if ($count > 1) {
                $count--;
                $inputVal.val($count);
                var $priceTotal = $count * $price;
                $priceTotal = $priceTotal.toFixed(2);
                $priceTotalObj.html("￥" + $priceTotal);
                $priceTotalObj.data("total", $priceTotal);
                totalMoney();
                if ($inputVal.val() == 1 && !$(this).hasClass("reSty")) {
                    $(this).addClass("reSty");
                }
                updateGoodsNumber(cart_id, $count);
            }

        });
        //用户自己输入数量
        $all_sum.keyup(function () {
            var $count = 0,
                    $priceTotalObj = $(this).parents(".item-content").find(".sum_price"),
                    $price = $(this).parents(".item-content").find(".unitPrice").data("price"), //单价
                    $priceTotal = 0;
            var r = /^[1-9]+[0-9]*]*$/;
            if ($(this).val() == "" || $(this).val() == "0" || !r.test($(this).val())) {
                $(this).val($(this).attr("data-default-num"));
            }
            // 获取到当前商品，然后判断数量
            var count = 0;
            var temp_num = 0;// 要改变的数量
            var max_buy =  $(this).parent().data("max_buy");
            var stock = $(this).parent().data("stock");
            var goodsid = $(this).parent().data("goods_id");
            var cart_id = $(this).parent().data("id");
            count = $(".J-amount[data-goods_id='" + goodsid + "']").length;
            if (max_buy == 0) {// 不限购
                if ($(this).val() < stock) {
                    // 正常情况
                    temp_num = $(this).val();
                } else {
                    temp_num = stock;// 最大库存
                    layer.msg("数量超出范围");
                }
            } else {
                // 限购
                if (count > 1 && count >= max_buy) {// 同样商品，不同SKU，只有限购的情况下，才需要判断count的数量
                    temp_num = max_buy;
                    layer.msg("该商品限购" + temp_num + "件");
                } else if ($(this).val() <= max_buy) {
                    temp_num = $(this).val();// 正常情况
                } else {
                    temp_num = max_buy;
                    layer.msg("该商品限购" + temp_num + "件");
                }
            }
            $(this).val(temp_num);
            $count = $(this).val();
            $priceTotal = parseFloat($count * $price);
            $(this).attr("value", $count);
            $priceTotalObj.html("￥" + $priceTotal);
            $priceTotalObj.data("total", $priceTotal);
            totalMoney();
            updateGoodsNumber(cart_id, temp_num);
        });

        //全局的checkbox选中和未选中的样式
        var $allCheckbox = $('input[type="checkbox"]'), //全局的全部checkbox
                $wholeChexbox = $(".whole_check"),
                $cartBox = $(".cart-ol-item"), //每个商铺盒子
                $shopCheckbox = $(".shopChoice"), //每个商铺的checkbox
                $sonCheckBox = $(".son_check"); //每个商铺下的商品的checkbox
        //===============================================全局全选与单个商品的关系================================
        $wholeChexbox.click(function () {
            var $checkboxs = $cartBox.find('input[type="checkbox"]');
            if ($(this).is(":checked")) {
                $checkboxs.prop("checked", true);
                $(".whole_check").prop("checked", true);
            } else {
                $checkboxs.prop("checked", false);
                $(".whole_check").prop("checked", false);
            }
            totalMoney();
        });
        $sonCheckBox.each(function () {
            $(this).click(function () {
                if ($(this).is(":checked")) {
                    //判断：所有单个商品是否勾选
                    var len = $sonCheckBox.length;
                    var num = 0;
                    $sonCheckBox.each(function () {
                        if ($(this).is(":checked")) {
                            num++;
                        }
                    });
                    if (num == len) {
                        $wholeChexbox.prop("checked", true);
                    }
                } else {
                    //单个商品取消勾选，全局全选取消勾选
                    $wholeChexbox.prop("checked", false);
                }
            });
        });
        //=======================================每个店铺checkbox与全选checkbox的关系/每个店铺与其下商品样式的变化===================================================

        //店铺有一个未选中，全局全选按钮取消对勾，若店铺全选中，则全局全选按钮打对勾。
        $shopCheckbox.each(function () {
            $(this).click(function () {
                if ($(this).is(":checked")) {
                    //判断：店铺全选中，则全局全选按钮打对勾。
                    var len = $shopCheckbox.length;
                    var num = 0;
                    $shopCheckbox.each(function () {
                        if ($(this).is(":checked")) {
                            num++;
                        }
                    });
                    if (num == len) {
                        $wholeChexbox.prop("checked", true);
                        $wholeChexbox.next("label").addClass("mark");
                    }

                    //店铺下的checkbox选中状态
                    $(this)
                            .parents(".cart-ol-item")
                            .find(".son_check")
                            .prop("checked", true);
                } else {
                    //否则，全局全选按钮取消对勾
                    $wholeChexbox.prop("checked", false);

                    //店铺下的checkbox选中状态
                    $(this)
                            .parents(".cart-ol-item")
                            .find(".son_check")
                            .prop("checked", false);
                }
                totalMoney();
            });
        });
        //========================================每个店铺checkbox与其下商品的checkbox的关系======================================================

        //店铺$sonChecks有一个未选中，店铺全选按钮取消选中，若全都选中，则全选打对勾
        $cartBox.each(function () {
            var $this = $(this);
            var $sonChecks = $this.find(".son_check");
            $sonChecks.each(function () {
                $(this).click(function () {
                    if ($(this).is(":checked")) {
                        //判断：如果所有的$sonChecks都选中则店铺全选打对勾！
                        var len = $sonChecks.length;
                        var num = 0;
                        $sonChecks.each(function () {
                            if ($(this).is(":checked")) {
                                num++;
                            }
                        });
                        if (num == len) {
                            $(this)
                                    .parents(".cart-ol-item")
                                    .find(".shopChoice")
                                    .prop("checked", true);
                        }
                    } else {
                        //否则，店铺全选取消
                        $(this)
                                .parents(".cart-ol-item")
                                .find(".shopChoice")
                                .prop("checked", false);
                    }
                    totalMoney();
                });
            });
        });
        
        //======================================总计==========================================
        function totalMoney() {
            var total_money = 0;
            // var total_count = 0;
            var calBtn = $(".btn-area a");
            var submit_btn = false;
            $sonCheckBox.each(function () {
                if ($(this).is(":checked")) {
                    submit_btn = true;
                    var goods = parseFloat($(this).parents(".item-content").find(".sum_price").data("total"));
                    total_money += goods;
                }
            });
            $(".totalPrice").html("￥" + parseFloat(total_money).toFixed(2));
            if (submit_btn) {
                calBtn.removeClass("submit-btn-disabled");
            } else {
                calBtn.addClass("submit-btn-disabled");
            }
        }
        //更新购物车商品数量
        function updateGoodsNumber(cart_id, num) {
            if (null != cart_id && null != num) {
                $.ajax({
                    url: __URL(SHOPMAIN + "/goods/updatecartgoodsnumber"),
                    type: "post",
                    async: true,
                    data: {
                        "cart_id": cart_id,
                        "num": num
                    },
                    success: function (res) {
                        if (res > 0) {
                            Dialog.refreshCart();// 刷新购物车
                        }
                    }
                });
            } else {
                layer.msg("数据错误");
            }
        }

        //======================================移除商品========================================
        // 商品删除
        $(".delBtn").on("click", function (e) {
            var id = $(this).data("id");
            var $order_lists = $(this).parents(".item-content"); //ul
            var $order_content = $order_lists.parents(".order-content");
            var that = $(this);
            layer.confirm('确认要删除该宝贝吗？', {
                btn: ['确定', '取消'], //按钮
                title: "删除宝贝"
            }, function (index) {
                layer.close(index);
                $.ajax({
                    url: __URL(SHOPMAIN + "/goods/deleteshoppingcartbyid"),
                    type: "POST",
                    data: {"cart_id_array": id},
                    success: function (data) {
                        if (data["code"] > 0)
                        {
                            layer.msg("删除成功", {time: 500}, function () {
                                Dialog.refreshCart();//刷新购物车
                                that.parents(".item-body").remove();
                                if ($order_content.find(".item-body").length === 0) {
                                    $order_content.parents(".cart-ol-item").remove();
                                }
                                $sonCheckBox = $(".son_check");
                                totalMoney();
                            });
                        }
                    }
                });
            });
        });
    };
  //购物车结算栏fixed或者固定在商品下面
    Cart.footview = function() {
        var hsTop = document.getElementById("hide_site").offsetTop;
        var footerTop = 806;
        var noneSite = hsTop - footerTop; //隐藏位置
        var nowSite = document.documentElement.scrollTop; //当前位置
        if (nowSite <= noneSite) {
        $(".float-bar").addClass("float-bar-fixed");
        } else {
        $(".float-bar").removeClass("float-bar-fixed");
        }
    };
    return Cart;
});
