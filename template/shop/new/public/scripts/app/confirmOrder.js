define(["jquery", "layer", "dialog"], function ($, layer, dialog) {
    var confirmOrder = {};
    var post_data = {};
    var postCoupon = {};
   
    var is_empty = function (obj) {
        if (obj == '' || obj == null || obj == undefined){
            return true;
        }
        if (!obj && obj !== 0 && obj !== '') {
            return true;
        }
        if (Array.prototype.isPrototypeOf(obj) && obj.length === 0) {
            return true;
        }
        if (Object.prototype.isPrototypeOf(obj) && Object.keys(obj).length === 0) {
            return true;
        }
        return false;
    };
    confirmOrder.opreation = function (map) {
        map.centerAndZoom(new BMap.Point(116.331398,39.897445),11);
	map.enableScrollWheelZoom(true);
	var geolocation = new BMap.Geolocation();
        geolocation.getCurrentPosition(function(r){
            if(this.getStatus() == BMAP_STATUS_SUCCESS){
                theLocation(r.point.lng,r.point.lat);
                $('#current_lng').val(r.point.lng);
                $('#current_lat').val(r.point.lat);
            }
        });
        function theLocation(lng,lat){
            map.clearOverlays(); 
            var new_point = new BMap.Point(lng,lat,11);
            var marker = new BMap.Marker(new_point);  // 创建标注
            map.addOverlay(marker);              // 将标注添加到地图中
            map.panTo(new_point); 
	}
        // var $plus = $(".plus"), $reduce = $(".minus"), $all_sum = $(".text-amount");
        LoadingAddressInfo();
        resetShippingFee();
        calculateTotalAmount();

        //清空收货地址输入框
        function clearAddress() {
            $("#addr_name").val("");
            $("#address_info").val("");
            $("#zip_code").val("");
            $("#addr_tel").val("");
            $("#default").prop("checked", false);
            $("#province_id").html('<option value="-1" selected="selected">请选择省</option>');
            $("#city_id").html('<option value="-1" selected="selected">请选择市</option>');
            $("#district_id").html('<option value="-1" selected="selected">请选择区</option>');
        };
        //点击增加购物车数量
        $(".order-orderBody").on('click','.plus',function () {
            var $inputVal = $(this).prev("input"), $count = parseInt($inputVal.val());
            var ul = $(this).parents(".J-goodsInfo");
            var $priceTotalObj = $(this).parents(".J-goodsInfo").find(".J-sum_price");
            var $price = $(this).parents(".J-goodsInfo").data("discount-price"); //单价
            var $priceTotal = 0;
            // 获取到当前商品，然后判断数量
            var temp_num = 0;// 要改变的数量
            var max_buy = $(this).parent().data("max_buy");
            var stock = $(this).parent().data("stock");
            if (max_buy == 0) {// 不限购
                if ($count < stock) {
                    // 正常情况
                    $count++;
                    temp_num = $count;
                } else {
                    temp_num = stock;// 最大库存
                    layer.msg("数量超出范围");
                }
            } else {
                // 限购
                if ($count >= max_buy) {
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
            ul.attr('data-num', temp_num);
            $inputVal.val(temp_num);
            $priceTotal = temp_num * $price;
            $priceTotal = $priceTotal.toFixed(2);
            $inputVal.attr("value", $count);
            $priceTotalObj.html("￥" + $priceTotal);
            $priceTotalObj.data("subtotal", $priceTotal);
            getFullCutList();
            getCouponList();
            calculateTotalAmount();
        });
        //点击减少购物车数量
        $(".order-orderBody").on('click','.minus',function () {
            var $inputVal = $(this).next("input");
            var $count = parseInt($inputVal.val());
            var ul = $(this).parents(".J-goodsInfo");
            var $priceTotalObj = $(this).parents(".J-goodsInfo").find(".J-sum_price");
            var $price = $(this).parents(".J-goodsInfo").data("discount-price"); //单价

            if ($count > 1) {
                $count--;
                $inputVal.val($count);
                ul.attr('data-num', $count);
                var $priceTotal = $count * $price;
                $inputVal.attr("value", $count);
                $priceTotal = $priceTotal.toFixed(2);
                $priceTotalObj.html("￥" + $priceTotal);
                $priceTotalObj.data("subtotal", $priceTotal);
                getFullCutList();
                getCouponList();
                calculateTotalAmount();
            }

        });
        //用户自己输入数量
        $(".order-orderBody").on('keyup','.text-amount',function () {
            var $count = 0;
            var ul = $(this).parents(".J-goodsInfo");
            var $priceTotalObj = $(this).parents(".J-goodsInfo").find(".J-sum_price");
            var $price = $(this).parents(".J-goodsInfo").data("discount-price"); //单价
            var $priceTotal = 0;
            var r = /^[1-9]+[0-9]*]*$/;
            if ($(this).val() == "" || $(this).val() == "0" || !r.test($(this).val())) {
                $(this).val($(this).attr("data-default-num"));
            }
            var temp_num = 0;// 要改变的数量
            var max_buy = $(this).parent().data("max_buy");
            var stock = $(this).parent().data("stock");
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
                if ($count >= max_buy) {
                    temp_num = max_buy;
                    layer.msg("该商品限购" + temp_num + "件");
                } else if ($(this).val() < max_buy) {
                    temp_num = $(this).val();// 正常情况
                } else {
                    temp_num = max_buy;
                    layer.msg("该商品限购" + temp_num + "件");
                }
            }
            $(this).val(temp_num);
            ul.attr('data-num', temp_num);
            $count = $(this).val();
            $priceTotal = parseFloat($count * $price);
            $(this).attr("value", $count);
            $priceTotal = $priceTotal.toFixed(2);
            $priceTotalObj.html("￥" + $priceTotal);
            $priceTotalObj.data("subtotal", $priceTotal);
            getFullCutList();
            getCouponList();
            calculateTotalAmount();
        });
        $('.J-shop_loop').on('change', ".J-couponSelect", function () {
            calculateTotalAmount();
            //全平台的优惠券只能使用一次
            $(".J-couponSelect option[disabled]").removeAttr('disabled');
            $(".J-couponSelect").each(function (k,v) {
                var disabled_coupon_id = $(this).val();
                if (disabled_coupon_id != 0){
                    $(".J-couponSelect option[value=" + disabled_coupon_id + "]").prop('disabled', true);
                }
            })

        });

        //根据更新满减送信息
        function getFullCutList() {
            var post = {};
            $(".J-shop_loop").each(function () {
                var shop_id = $(this).data('shop-id');
                post[shop_id] = {};
                $(this).find(".J-goodsInfo").each(function (k, v) {
                    var temp_sku_id = $(v).attr('data-sku-id');
                    var temp_goods_id = $(v).attr('data-goods-id');
                    var temp_sub_num = parseInt($(v).attr('data-num'));
                    var temp_sub_price = parseFloat($(v).attr('data-price'));
                    var temp_sub_discount_price = parseFloat($(v).attr('data-discount-price'));//所有折扣价(包括会员)
                    post[shop_id][temp_sku_id] = {};
                    post[shop_id][temp_sku_id].sku_id = temp_sku_id;
                    post[shop_id][temp_sku_id].goods_id = temp_goods_id;
                    post[shop_id][temp_sku_id].price = temp_sub_price;
                    post[shop_id][temp_sku_id].discount_price = temp_sub_discount_price;
                    post[shop_id][temp_sku_id].num = temp_sub_num;
                    postCoupon = post;
                });
            });
            $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + '/member/getFullCutList'),
                data: {
                    'postdata': post
                },
                async: false,
                success: function (data) {
                    if (!is_empty(data)) {
                        $.each(data, function (shopid, item) {
                            var str = '<div class="welfare">';
                            if (item.full_cut) {
                                var rang_type = '';
                                if (item.full_cut.range_type === 0) {
                                    rang_type = '(部分商品)';
                                }
                                str += '<p data-full-cut-type="full_cut" data-man-song-id="' + item.full_cut.man_song_id + '"';
                                str += 'data-goods-limit="' + item.full_cut.goods_limit + '"';
                                str += 'data-rule-id="' + item.full_cut.rule_id + '" data-rule-price="' + item.full_cut.price + '"';
                                str += 'data-man-song-shop-id="' + item.full_cut.shop_id + '" data-man-song-range-type="' + item.full_cut.range_type + '"';
                                str += 'data-man-song-coupon-type-id="' + item.full_cut.coupon_type_id + '" data-man-song-point="' + item.full_cut.give_point + '"';
                                str += 'data-at-discount="' + item.full_cut.discount + '"><span class="w-border">' + item.full_cut.man_song_name + '</span> 满' + item.full_cut.price + '减' + item.full_cut.discount + rang_type + '</p>';
                                if(item.full_cut.give_coupon){
                                    str += '<p data-full-cut-type="full_cut" data-man-song-id="' + item.full_cut.give_coupon + '"';
                                    str += 'data-goods-limit="' + item.full_cut.goods_limit + '"';
                                    str += 'data-rule-id="' + item.full_cut.rule_id + '" data-rule-price="' + item.full_cut.price + '"';
                                    str += 'data-man-song-shop-id="' + item.full_cut.shop_id + '" data-man-song-range-type="' + item.full_cut.range_type + '"';
                                    str += 'data-man-song-coupon-type-id="' + item.full_cut.give_coupon+ '" data-man-song-point="' + item.full_cut.give_point + '"';
                                    str += 'data-at-discount="' + item.full_cut.discount + '"><span class="w-border">' + item.full_cut.man_song_name + '</span> 满' + item.full_cut.price + '送优惠券(' + item.full_cut.coupon_type_name + ')'+rang_type + '</p>';
                                }
                                if(item.full_cut.gift_card_id){
                                    str += '<p data-full-cut-type="full_cut" data-man-song-id="' + item.full_cut.gift_card_id + '"';
                                    str += 'data-goods-limit="' + item.full_cut.goods_limit + '"';
                                    str += 'data-rule-id="' + item.full_cut.rule_id + '" data-rule-price="' + item.full_cut.price + '"';
                                    str += 'data-man-song-shop-id="' + item.full_cut.shop_id + '" data-man-song-range-type="' + item.full_cut.range_type + '"';
                                    str += 'data-man-song-coupon-type-id="' + item.full_cut.gift_card_id+ '" data-man-song-point="' + item.full_cut.give_point + '"';
                                    str += 'data-at-discount="' + item.full_cut.discount + '"><span class="w-border">' + item.full_cut.man_song_name + '</span> 满' + item.full_cut.price + '送礼品券(' + item.full_cut.gift_voucher_name + ')'+rang_type + '</p>';
                                }
                                if(item.full_cut.gift_id){
                                    str += '<p data-full-cut-type="full_cut" data-man-song-id="' + item.full_cut.gift_id + '"';
                                    str += 'data-goods-limit="' + item.full_cut.goods_limit + '"';
                                    str += 'data-rule-id="' + item.full_cut.rule_id + '" data-rule-price="' + item.full_cut.price + '"';
                                    str += 'data-man-song-shop-id="' + item.full_cut.shop_id + '" data-man-song-range-type="' + item.full_cut.range_type + '"';
                                    str += 'data-man-song-coupon-type-id="' + item.full_cut.gift_id+ '" data-man-song-point="' + item.full_cut.give_point + '"';
                                    str += 'data-at-discount="' + item.full_cut.discount + '"><span class="w-border">' + item.full_cut.man_song_name + '</span> 满' + item.full_cut.price + '送赠品(' + item.full_cut.gift_name + ')'+rang_type + '</p>';
                                }

                                if (item.discount_percent) {
                                    $.each(item.discount_percent, function (k, v) {
                                        if (item.full_cut && item.full_cut.discount > 0) {
                                            postCoupon[shopid][k].full_cut_amount = item.full_cut.discount;
                                            postCoupon[shopid][k].full_cut_percent = v;
                                            postCoupon[shopid][k].full_cut_percent_amount = (v * item.full_cut.discount).toFixed(2);
                                        }
                                        str += '<div class="full-cut-data-sku-percent" data-sku-id="' + k + '" data-sku-percent="' + v + '"></div>'
                                    });
                                }
                            }
                            if (item.shipping) {
                                var rang_type = '';
                                if (item.shipping.range_type === 0) {
                                    rang_type = '(部分商品)';
                                }
                                str += '<p data-full-cut-type="shipping" data-rule-id="' + item.shipping.rule_id + '"';
                                str += 'data-man-song-id="' + item.shipping.man_song_id + '" data-rule-price="' + item.shipping.price + '" data-man-song-shop-id="' + item.shipping.shop_id + '" data-man-song-range-type="' + item.shipping.range_type + '"';
                                str += 'data-goods-limit="' + item.shipping.goods_limit + '"><span class="w-border">' + item.shipping.man_song_name + '</span> 满' + item.shipping.price + '包邮' + rang_type + '</p>';
                            }
                            str += '</div>';
                            $(".J-fullcut_" + shopid).html(str);
                        });
                    }
                }
            });
        };
        //更新优惠券列表
        function getCouponList() {
            $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + '/member/getCouponList'),
                data: {
                    'postdata': postCoupon
                },
                async: false,
                success: function (data) {
                    if (!is_empty(data)) {
                        $.each(data, function (shopid, item) {
                            var str = '<div class="order-coupons pb14 clearfix">\n\
                                        <div class="orderExt-item">优惠券：</div>\n\
                                        <div class="orderExt-info">\n\
                                        <select class="ins-select J-couponSelect">\n\
                                        <option value="0">不使用优惠券</option>';
                            if (item.coupon_info) {
                                $.each(item.coupon_info,function(coupon_key,coupon){
                                    if(coupon.coupon_type.coupon_genre === 2){
                                        str += '<option data-money="'+coupon.coupon_type.money+'"';
                                        str += 'data-genre="'+coupon.coupon_type.coupon_genre+'"';
                                        str += 'value="'+coupon.coupon_id+'"';
                                        str += 'data-goods-limit="'+coupon.goods_limit+'"';
                                        str += 'data-at-least="'+coupon.coupon_type.at_least+'"';
                                        str += 'data-coupon-shop-id="'+coupon.coupon_type.shop_id+'">';
                                        str += '满'+coupon.coupon_type.at_least+'减'+coupon.coupon_type.money+'元&nbsp;-&nbsp;'+coupon.coupon_type.coupon_name;
                                    }else if(coupon.coupon_type.coupon_genre === 1){
                                        str += '<option data-money="'+coupon.coupon_type.money+'"';
                                        str += 'data-genre="'+coupon.coupon_type.coupon_genre+'"';
                                        str += 'value="'+coupon.coupon_id+'"';
                                        str += 'data-goods-limit="'+coupon.goods_limit+'"';
                                        str += 'data-at-least="'+coupon.coupon_type.at_least+'"';
                                        str += 'data-coupon-shop-id="'+coupon.coupon_type.shop_id+'">';
                                        str += '减'+coupon.coupon_type.money+'元&nbsp;-&nbsp;'+coupon.coupon_type.coupon_name;
                                    }else if(coupon.coupon_type.coupon_genre === 3){
                                        str += '<option data-discount="'+coupon.coupon_type.discount+'"';
                                        str += 'data-genre="'+coupon.coupon_type.coupon_genre+'"';
                                        str += 'value="'+coupon.coupon_id+'"';
                                        str += 'data-goods-limit="'+coupon.goods_limit+'"';
                                        str += 'data-at-least="'+coupon.coupon_type.at_least+'"';
                                        str += 'data-coupon-shop-id="'+coupon.coupon_type.shop_id+'">';
                                        str += '减'+coupon.coupon_type.at_least+'打'+coupon.coupon_type.discount+'折&nbsp;-&nbsp;'+coupon.coupon_type.coupon_name;
                                    }
                                });
                            }
                            str += '</select>'
                            if (item.sku_percent) {
                                $.each(item.sku_percent,function(coupon_id,coupon_info){
                                    $.each(coupon_info,function(sku_id,sku_info){
                                       str += '<div class="coupon-data-sku-percent" data-coupon-id="'+coupon_id+'" data-sku-id="'+sku_id+'" data-shop-id="'+shopid+'" data-sku-percent="'+sku_info.coupon_percent+'" data-sku-amount="'+sku_info.coupon_percent_amount+'"></div>';
                                    });
                                });
                            }
                            str += '</div></div>';
                            $(".J-coupon_" + shopid).html(str);
                        });
                    }
                }
            });
        }
        /**
         * 提交订单
         */
        var flag = false;//防止重复提交
        $(".J-settlement").click(function () {
            if (validationOrder()) {
                if (flag) {
                    return;
                }
                flag = true;
                $(".J-settlement").html('提交中...');
                //买家留言
                $(".leave-message").each(function () {
                    var shop_id = $(this).attr('data-shop-id');
                    post_data['shop'][shop_id]['leave_message'] = $(this).val();
                });
                var sendObj = $('#sendList .selected');
                if(sendObj.data('select') === 1){
                    post_data['address_id'] = $(".suggest-address.selected").data('id');
                }else{
                    post_data['address_id'] = $(".suggest-address.selected").data('id');
                    $(".J-store_id").each(function () {
                        var shop_id = $(this).data('shop-id');
                        post_data['shop'][shop_id]['store_id'] = $(this).val();
                        post_data['shop'][shop_id]['card_store_id'] = $("#card_store_id").val();
                    });
                    $(".J-card_store_id").each(function () {
                        var shop_id = $(this).data('shop-id');
                        post_data['shop'][shop_id]['card_store_id'] = $(this).val();
                    });
                }
               
                post_data['pay_type'] = $("#payList .selected").data("select");//支付方式 0：在线支付，4：货到付款 5：账号余额支付
                post_data['shipping_type'] = $("#sendList .selected").data("select");//配送方式 1：商家配送，2：门店自提
                if (post_data['pay_type'] == 5) {
                    post_data['user_platform_money'] = post_data['total_pay_amount'];
                }
                $.ajax({
                    url: __URL(SHOPMAIN + "/order/ordercreate"),
                    type: "post",
                    data: {
                        'post_data': post_data
                    },
                    success: function (res) {

                        if (res.code > 0) {
                            $(".J-settlement").css("background-color", "#ccc");
                            //如果实际付款金额为0，跳转到个人中心的订单界面中
                            if (post_data['total_pay_amount'] == 0 || post_data['pay_type'] == 5) {
                                location.href = __URL(SHOPMAIN + '/pay/paycallback?msg=1&out_trade_no=' + res.code);
                            } else if (post_data['pay_type'] == 4) {
                                location.href = __URL(SHOPMAIN + '/member/orderlist');
                            } else {
                                window.location.href = __URL(SHOPMAIN + '/pay/getpayvalue?out_trade_no=' + res.code);
                            }
                        } else {
                            layer.msg(res.message);
                            flag = false;
                            $(".J-settlement").html('结算');
                            if (res.operation !== undefined && res.operation === 'refresh'){
                                setTimeout(window.location.reload(),3000)
                            }
                        }
                    }
                });
            }
        });

        /**
         * 验证
         * @returns {Boolean}
         */
        function validationOrder() {
            var sendObj = $('#sendList .selected');
            var select_store = true;
            var goods_type = $("#goods_type").val();
            if(sendObj.data('select') === 1){
                if ($("#address_id").val() == undefined || $("#address_id").val() == '' || $("#address_id").val() == 0) {
                    layer.msg("请先选择收货地址");
                    return false;
                }
            }else{
            	if(goods_type==1){
                    $(".J-shop_loop").each(function () {
                        var obj = $(this).find('.J-delivery');
                        var shop_id = $(this).data('shop-id');
                        if (($("#store_id_" + shop_id).val() == undefined || $("#store_id_" + shop_id).val() == '' || $("#store_id_" + shop_id).val() == 0) && obj.data('has_store') == '1') {
                            select_store = false;
                            return false;
                        }
                    });
                    if(!select_store){
                        layer.msg("请先选择门店");
                        return false;
                    }
            	}else if(goods_type==0){
                    $(".J-shop_loop").each(function () {
                        var shop_id = $(this).data('shop-id');
                        if ($("#card_store_id_" + shop_id).val() == undefined || $("#card_store_id_" + shop_id).val() == '' || $("#card_store_id_" + shop_id).val() == 0) {
                            select_store = false;
                            return false;
                        }
                    });
                    if(!select_store){
                        layer.msg("请先选择核销门店");
                        return false;
                    }
            	}
            }
            var obj = $('#payList .selected');
            if (obj.data('select') == '5') {
                var balance = obj.data('balance');
                var real_price = $('#real_price').html();
                if (Number(balance) < Number(real_price)) {
                    layer.msg("余额不足，请先充值");
                    return false;
                }
                var payPw = $('.pay_password').val();
                if (payPw === '') {
                    layer.msg("请输入支付密码");
                    $('.pay_password').focus();
                    return false;
                }
                if (!checkPayPw(payPw)) {
                    return false;
                }
            }
            return true;
        }

        function checkPayPw(payPw) {
            var result = true;
            $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + '/member/checkPayPw'),
                data: {
                    'payPw': payPw
                },
                dataType: "json",
                async: false,
                success: function (data) {
                    if (data.code <= 0) {
                        result = false;
                        layer.msg(data.message);
                    }
                }
            });
            return result;
        }

        //获取省列表
        function getProvince(pid) {
            $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + '/member/getprovince'),
                dataType: "json",
                async:false,
                success: function (data) {
                    if (data.length > 0) {
                        var str = "";
                        for (var i = 0; i < data.length; i++) {
                            if (data[i].province_id == pid) {
                                str += '<option  selected value="' + data[i].province_id + '">' + data[i].province_name + '</option>';
                            } else {
                                str += '<option value="' + data[i].province_id + '">' + data[i].province_name + '</option>';
                            }
                        }
                        $("#province_id").append(str);
                    }
                }
            });
        }

        //获取市列表
        function getCity(pid, cid) {
            $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + '/member/getcity'),
                data: {
                    'province_id': pid
                },
                dataType: "json",
                async:false,
                success: function (data) {
                    if (data.length > 0) {
                        var str = '<option value="-1" selected="selected">请选择市</option>';
                        for (var i = 0; i < data.length; i++) {
                            if (data[i].city_id == cid) {
                                str += '<option  selected value="' + data[i].city_id + '">' + data[i].city_name + '</option>';
                            } else {
                                str += '<option value="' + data[i].city_id + '">' + data[i].city_name + '</option>';
                            }
                        }
                        $("#city_id").html(str);
                        $("#district_id").html('<option value="-1" selected="selected">请选择区</option>');
                    }
                }
            });
        }

        //获取区列表
        function getDistrict(cid, did) {
            $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + '/member/getDistrict'),
                dataType: "json",
                async:false,
                data: {
                    'city_id': cid
                },
                success: function (data) {
                    if (data.length > 0) {
                        var str = '<option value="-1" selected="selected">请选择区</option>';
                        for (var i = 0; i < data.length; i++) {
                            if (data[i].district_id == did) {
                                str += '<option  selected value="' + data[i].district_id + '">' + data[i].district_name + '</option>';
                            } else {
                                str += '<option value="' + data[i].district_id + '">' + data[i].district_name + '</option>';
                            }
                        }
                        $("#district_id").html(str);
                    }
                }
            });
        }

        //选择省获取市列表
        $('.getProvince').change('click', function () {
            var pid = $("#province_id").val();
            if (pid !== '-1') {
                getCity(pid);
            }
        });
        //选择市获取区列表
        $('.getCity').change('click', function () {
            var cid = $("#city_id").val();
            if (cid !== '-1') {
                getDistrict(cid);
            }
        });
        $('.J-addressList').on("click", '.suggest-address', function () {
            var address_id = $(this).data('id');
            $(this).addClass('selected').siblings().removeClass('selected');
            $('#address_id').val(address_id);
            resetShippingFee();
            calculateTotalAmount();
        });
        $('#payList').on("click", '.payItems', function () {
            $(this).addClass('selected').siblings().removeClass('selected');
            if ($(this).data('select') == '5') {

                var balance = $(this).data('balance');
                var real_price = $('#real_price').html();
                if (Number(balance) < Number(real_price)) {
                    $('.J-recharge').show();
                }
                $('.J-payPW').show();
            } else {
                $('.J-payPW').hide();
            }
        });
        $('#sendList').on("click", '.payItems', function () {
            $(this).addClass('selected').siblings().removeClass('selected');
            if ($(this).data('select') === 2) {
                var show_address = 0;
                $('.J-delivery').each(function(){
                    if($(this).data('has_store') == 1){
                        $(this).find('.J-chooseStore').show();
                        $(this).find('.J-storeChooosed').show();
                        $(this).find('.J-chooseDelivery').hide();
                    }else{
                        show_address = 1;
                        $(this).find('.J-chooseStore').hide();
                        $(this).find('.J-storeChooosed').hide();
                        $(this).find('.J-chooseDelivery').show();
                    }
                    if(show_address){
                        $('.J-address').show();
                    }else{
                        $('.J-address').hide();
                    }
                })
                
            } else {
                $('.J-address').show();
                $('.J-chooseStore').hide();
                $('.J-storeChooosed').hide();
                $('.J-chooseDelivery').show();
                window.location.reload();
            }
            resetShippingFee();
            calculateTotalAmount();
        });

        //根据地址获取运费
        function resetShippingFee() {
            var address_id = $('#address_id').val();
            post_data['shipping'] = {};
            var sendObj = $('#sendList .selected');
                
            $(".J-shop_loop").each(function () {
                var shop_id = $(this).data('shop-id');
                var goods_id_obj = {};
                $(".J-goodsInfo").each(function () {
                    var temp_goods_id = $(this).data('goods-id');
                    var temp_num = $(this).data('num');
                    if (goods_id_obj[temp_goods_id]) {
                        goods_id_obj[temp_goods_id] += temp_num;
                    } else {
                        goods_id_obj[temp_goods_id] = temp_num;
                    }
                });
                $.each(goods_id_obj, function (goods_id, num) {
                    post_data['shipping'][goods_id] = {};
                    post_data['shipping'][goods_id]['shipping_fee'] = 0;
                    post_data['shipping'][goods_id]['total_num'] = num;

                    // 后续计算需要邮费，获取到邮费再计算后续
                    $.ajax({
                        type: "post",
                        async: false,
                        url: __URL(SHOPMAIN + '/member/resetshippingfee'),
                        data: {
                            'goodIds': [goods_id],
                            'nums': [num],
                            'address_id': address_id
                        },
                        success: function (data) {
                            if (data.data) {
                                if(sendObj.data('select') === 2){
                                    data.data = 0;
                                }
                                //console.log(data.data);
                                post_data['shipping'][goods_id]['shipping_fee'] += data.data;
                                $('#shop_express_'+shop_id).html(data.data);
                                $('#shop_shipping_info_'+shop_id).html(data.data);
                                $('#shop_shipping_info_'+shop_id).data('shop-shipping-fee',data.data);
                            }
                        }
                    });
                    //console.log( post_data['shipping'][goods_id]['shipping_fee'],post_data['shipping'][goods_id]['total_num']);
                });
            });
        }

        //加载地址列表
        function LoadingAddressInfo() {
            $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + "/member/orderAddressList"),
                data: {},
                async: false,
                success: function (data) {
                    var html = '';
                    if (data.length > 0) {
                        for (var i = 0; i < data.length; i++) {
                            var sel = "";
                            if (data[i].is_default === 1) {
                                $('#address_id').val(data[i].id);
                                sel = "selected";
                            }
                            html += '<div class="suggest-address ' + sel + '" data-id="' + data[i].id + '" data-province-id="' + data[i].province + '" data-city-id="' + data[i].city + '">';
                            html += '<div class="addr-hd clearfix">';
                            html += '<div class="addr-Rname fl">' + data[i].consigner + '</div>';
                            html += '<div class="addr-op fr">';
                            html += '<a href="javascript:void(0);" class="addr-op-a J-updateAddress mr-04" data-id="' + data[i].id + '"><i class="icon icon-edit"></i></a>';
                            html += '<a href="javascript:void(0);" class="addr-op-del J-delAddress" data-id="' + data[i].id + '"><i class="icon icon-close"></i></a>';
                            html += '</div>';
                            html += '</div>';
                            html += '<div class="addr-bd">' + data[i].province_name + data[i].city_name + data[i].district_name + data[i].address + '</div>';
                            html += '<div class="addr-pd">' + data[i].mobile + '</div>';
                            html += '<b></b>';
                            html += '</div>';
                        }
                    }
                    html += '<div class="addAddress J-addAddress">';
                    html += '<a href="javascript:void(0);">';
                    html += '<div class="icon-add2 icon"></div><div>添加新地址</div>';
                    html += '</a>';
                    html += '</div>';
                    $(".J-addressList").html(html);
                }
            });
        }

        //删除收货地址
        $('.J-addressList').on('click', '.J-delAddress', function () {
            var id = $(this).data('id');
            layer.confirm('你确定删除该地址？', {
                btn: ['确定', '取消']//按钮
            }, function (index) {
                layer.close(index);
                $.ajax({
                    type: "post",
                    url: __URL(SHOPMAIN + '/member/memberAddressDelete'),
                    data: {
                        'id': id
                    },
                    success: function (data) {
                        if (data["code"] > 0) {
                            layer.msg('删除成功', {icon: 1, time: 2000}, LoadingAddressInfo());
                        } else {
                            layer.msg('删除失败');
                        }
                    }
                });
            });
        });
        //修改收货地址
        $('.J-addressList').on('click', '.J-updateAddress', function () {
            clearAddress();
            var id = $(this).data('id');
            $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + '/member/getMemberAddressDetail'),
                data: {
                    'id': id
                },
                success: function (data) {
                    $("#addr_name").val(data['consigner']);
                    $("#addr_tel").val(data['mobile']);
                    $("#address_info").val(data['address']);
                    $("#zip_code").val(data['zip_code']);
                    var pid = data['province'];
                    var cid = data['city'];
                    var did = data['district'];
                    if (data['is_default'] == 1) {
                        $("#default").prop("checked", true);
                    }
                    getProvince(pid);
                    getCity(pid, cid);
                    getDistrict(cid, did);
                    dialog.updateAddress("修改收货地址", ".addAddress-dialog", function () {
                        var consigner = $("#addr_name").val();
                        var mobile = $("#addr_tel").val();
                        var address = $("#address_info").val();
                        var province = $("#province_id").val();
                        var zip_code = $("#zip_code").val();
                        var city = $("#city_id").val();
                        var district = $("#district_id").val();
                        var is_default = $('#default').is(':checked') ? '1' : '0';
                        if (consigner == '') {
                            $(".error_tel").html('收货人不能为空');
                            $(".pop-msg").css("display", "block");
                            $("#addr_name").focus();
                            return false;
                        }
                        if (mobile == '') {
                            $(".error_tel").html('手机号不能为空');
                            $(".pop-msg").css("display", "block");
                            $("#addr_tel").focus();
                            return false;
                        }
                        if(!(/^1[34578]\d{9}$/.test(mobile))){ 
                            $(".error_tel").html('手机号码有误，请重填');
                            $(".pop-msg").css("display", "block");
                            $("#addr_tel").focus();
                            return false;
                        } 
                        if (province == '-1') {
                            $(".error_tel").html('请选择省');
                            $(".pop-msg").css("display", "block");
                            return false;
                        }
                        if (city == '-1') {
                            $(".error_tel").html('请选择市');
                            $(".pop-msg").css("display", "block");
                            return false;
                        }
                        if (district == '-1') {
                            $(".error_tel").html('请选择区');
                            $(".pop-msg").css("display", "block");
                            return false;
                        }
                        if (address == '') {
                            $(".error_tel").html('详细地址不能为空');
                            $(".pop-msg").css("display", "block");
                            $("#address_info").focus();
                            return false;
                        }
                        $.ajax({
                            type: "post",
                            url: __URL(SHOPMAIN + '/member/updateMemberAddress'),
                            dataType: "json",
                            data: {
                                "consigner": consigner,
                                "mobile": mobile,
                                "address": address,
                                "province": province,
                                "city": city,
                                "district": district,
                                "id": id,
                                "zip_code": zip_code,
                                "is_default": is_default
                            },
                            success: function (data) {
                                if (data['code'] > 0) {
                                    layer.msg('修改成功', {icon: 1, time: 2000}, LoadingAddressInfo());
                                } else {
                                    layer.msg('修改失败');
                                }
                            }
                        });
                    });
                }
            });
        });
        //添加收货地址
        $('.J-addressList').on('click', '.J-addAddress', function () {
            //判断是否设置了账号体系
            var mark;
            //判断当前用户的账号体系
            $.ajax({
                'url':__URL(SHOPMAIN + "/login/getAccountType"),
                'type':'post',
                'async':false,
                'data':{},
                'success':function(data){
                    //code为1说明要绑定手机
                    if(data.code == 1){
                        mark = 1;
                        dialog.loginMember('关联手机','.associated-dialog');return;
                    }else{
                        mark = 0;
                    }
                }
            })
            if(mark == 1){
                return false;
            }
            clearAddress();
            getProvince();
            dialog.updateAddress("添加收货地址", ".addAddress-dialog", function () {
                var consigner = $("#addr_name").val();
                var mobile = $("#addr_tel").val();
                var address = $("#address_info").val();
                var province = $("#province_id").val();
                var city = $("#city_id").val();
                var district = $("#district_id").val();
                var is_default = $('#default').is(':checked') ? '1' : '0';
                var zip_code = $("#zip_code").val();
                if (consigner == '') {
                    $(".error_tel").html('收货人不能为空');
                    $(".pop-msg").css("display", "block");
                    $("#addr_name").focus();
                    return false;
                }
                if (mobile == '') {
                    $(".error_tel").html('手机号不能为空');
                    $(".pop-msg").css("display", "block");
                    $("#addr_tel").focus();
                    return false;
                }
                if(!(/^1[34578]\d{9}$/.test(mobile))){ 
                    $(".error_tel").html('手机号码有误，请重填');
                    $(".pop-msg").css("display", "block");
                    $("#addr_tel").focus();
                    return false;
                } 
                
                if (province == '-1' || province == '') {
                    $(".error_tel").html('请选择省');
                    $(".pop-msg").css("display", "block");
                    return false;
                }
                if (city == '-1' || city == '') {
                    $(".error_tel").html('请选择市');
                    $(".pop-msg").css("display", "block");
                    return false;
                }
                if (district == '-1' || district == '') {
                    $(".error_tel").html('请选择区');
                    $(".pop-msg").css("display", "block");
                    return false;
                }
                if (address == '') {
                    $(".error_tel").html('详细地址不能为空');
                    $(".pop-msg").css("display", "block");
                    $("#address_info").focus();
                    return false;
                }
                $.ajax({
                    type: "post",
                    url: __URL(SHOPMAIN + '/member/addressInsert'),
                    dataType: "json",
                    data: {
                        "consigner": consigner,
                        "mobile": mobile,
                        "address": address,
                        "province": province,
                        "city": city,
                        "district": district,
                        "zip_code": zip_code,
                        "is_default": is_default
                    },
                    success: function (data) {
                        if (data['code'] > 0) {
                            layer.msg('添加成功', {icon: 1, time: 2000}, LoadingAddressInfo());
                            resetShippingFee();
                            calculateTotalAmount();
                        } else {
                            layer.msg('添加失败');
                        }
                    }
                });
            });
        });
        //选择门店
        $('.J-orderExt').on('click', '.J-chooseStore', function () {
            var shop_id = $(this).data('shop');
            var that = $(this);
            getStoreList(shop_id,'');
            dialog.updateAddress("选择门店", ".chooseStore-dialog", function () {
                var store_id = $('input[name=store_id]:checked').val();
                var store = $('input[name=store_id]:checked').data('store');
                var shopName = that.parents('.order-orderItem').find('.order-shopName');
                var orderBody = that.parents('.order-orderItem').find('.order-orderBody');
                var orderBodyUl = orderBody.find('.orderBody-ul');
                var arrCartId = [];
                orderBodyUl.each(function(){
                    var cartId = $(this).data('cart-id');
                    arrCartId.push(cartId);
                });
                $('#store_id_' + shop_id).val(store_id);
                $('.J-shop_' + shop_id).html('重新选择');
                $('#shipping_fee_' + shop_id + ' .J-storeChooosed').html(store);
                $.ajax({
                    type: "post",
                    url: __URL(SHOPMAIN + '/member/paymentOrder'),
                    dataType: "json",
                    data: {
                        "store_id":store_id,
                        "cart_ids":arrCartId,
                    },
                    success: function (data) {
                            var html = "";
                            for (let i in data) {
                                shopName.html('<div class="order-shopName">'+'店铺'+':'+data[i].shop.shop_name+'<span>'+'</span>'+'</div>');
                                 for(let key  in data[i].sku){
                                    html+='<ul class="clearfix orderBody-ul J-goodsInfo" data-goods-id="'+data[i].sku[key].goods_id+'"\n' +
                                        'data-goods-name="'+data[i].sku[key].goods_name+'" \n' +
                                        'data-sku-id="'+data[i].sku[key].sku_id+'" data-price="'+data[i].sku[key].price+'" data-member-price="'+data[i].sku[key].member_price+'"\n' +
                                        'data-discount-price="'+data[i].sku[key].discount_price+'" data-promotion-price="'+data[i].sku[key].promotion_price+'"\n' +
                                        'data-discount-id="'+data[i].sku[key].discount_id+'"\n' +
                                        'data-num="'+data[i].sku[key].num+'"\n' +
                                        'data-promotion-shop-id="'+data[i].sku[key].promotion_shop_id+'"\n' +
                                        'data-seckill-id="'+data[i].sku[key].seckill_id+'"\n' +
                                        'data-cart-id="'+data[i].sku[key].cart_id+'"\n' +
                                        'data-store-id="'+store_id+'"\n' +
                                        '>';
                                    html+='<li class="td td-info">';
                                    html+='<div class="td-inner clearfix">';
                                    html+='<div class="item-pic">';
                                    html+='<a href="'+__URL(SHOPMAIN+'goods/goodsinfo?goodsid='+data[i].sku[key].goods_id)+'">';
                                    html+='<img src="'+__IMG(data[i].sku[key].picture_info.pic_cover_small)+'" alt="" width="80" height="80">';
                                    html+='</a>';
                                    html+='</div>';
                                    html+='<div class="item-info">';
                                    html+='<div class="item-basic-info">';
                                    html+='<a href="'+__URL(SHOPMAIN+'goods/goodsinfo?goodsid='+data[i].sku[key].goods_id)+'"'+ 'target="_blank" title="'+data[i].sku[key].goods_name+'" class="item-title">'+data[i].sku[key].goods_name+'</a>';
                                    html+='<div class="spec">'+data[i].sku[key].sku_name+'</div>';
                                    html+='</div>';
                                    html+='</div>';
                                    html+='</div>';
                                    html+='</li>';
                                    html+='<li class="td td-price">';
                                    html+='<div class="td-inner pl46">';
                                    html+='￥'+data[i].sku[key].price;
                                    html+='</div>';
                                    html+='</li>';
                                    html+='<li class="td td-num">';
                                    html+='<div class="td-inner pl70">';
                                    html+='<div class="item-amount J-amount"  data-max_buy="'+data[i].sku[key].max_buy+'" data-goods_id="'+data[i].sku[key].goods_id+'" data-stock="'+data[i].sku[key].stock+'">';
                                    html+='<a href="javascript:void(0);" class="J_Minus minus">-</a>';
                                    html+='<input type="text" value="'+data[i].sku[key].num+'" class="text text-amount J_ItemAmount" autocomplete="off" data-default-num="'+data[i].sku[key].num+'">';
                                    html+='<a href="javascript:void(0);" class="J_Plus plus">+</a>';
                                    html+='</div></div>';
                                    html+='</li>';
                                    html+='<li class="td td-sum">';
                                    html+='<div class="td-inner pl50 J-sum_price" data-subtotal="'+data[i].sku[key].discount_price * data[i].sku[key].num+'" >';
                                    html+='￥'+data[i].sku[key].discount_price * data[i].sku[key].num;
                                    html+='</div>';
                                    html+='</li>';
                                    html+='</ul>';
                                }
                            }
                            orderBody.html(html);
                            calculateTotalAmount();
                    }
                });
            },'720px');
        });
        //选择核销门店
        $('.J-orderExt').on('click', '.J-wxchooseStore', function () {
            var shop_id = $(this).data('shop');
            var store_list = $('#store_list').val();
            getStoreList(shop_id,store_list);
            dialog.updateAddress("选择门店", ".chooseStore-dialog", function () {
                var store_id = $('input[name=store_id]:checked').val();
                var store = $('input[name=store_id]:checked').data('store');
                $('#card_store_id_' + shop_id).val(store_id);
                $('.J-shop_' + shop_id).html('重新选择');
                $('#write_off_' + shop_id + ' .J-wxstoreChooosed').html(store);
            },'720px');
        });
        function getStoreList(shop_id,store_list) {
            var store_id = $('#store_id_' + shop_id).val();
            var url = $('#storeListUrl').val();
            var lng = $('#current_lng').val();
            var lat = $('#current_lat').val();
            var data = {};
            data.shop_id = shop_id;
            data.lng = lng;
            data.lat = lat;
            if(store_list){
            	data.store_list = store_list;
            }
            $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + '/member/paymentOrder'),
                dataType: "json",
                data: data,
                success: function (data) {
                    var data = data['store_list'];
                    if(data.length>0){
                        var html = "";
                        for (var i = 0; i < data.length; i++) {
                            if(store_id>0){
                                if(data[i].store_id == store_id){
                                    theLocation(data[i].lng,data[i].lat);
                                    html += '<div style="height:50px;padding:10px;position:relative;border-bottom:1px solid #eee;">';
                                    html += '<label><input style="margin-right:5px" type="radio" name="store_id" data-lat="' + data[i].lat + '" data-lng="' + data[i].lng + '" data-store="'+data[i].store_name+'('+data[i].address+')&nbsp;&nbsp;电话：'+data[i].store_tel+'&nbsp;&nbsp;时间：'+data[i].start_time+'-'+data[i].finish_time+'" value="' + data[i].store_id + '" checked>' + data[i].store_name+'('+data[i].province_name + data[i].city_name + data[i].dictrict_name + data[i].address+')</label>';
                                    html += '<span style="position:absolute;right:5px;bottom:5px">' + data[i].distance + 'km</span>';
                                    html += '</div>';
                                }else{
                                    html += '<div style="height:50px;padding:10px;position:relative;border-bottom:1px solid #eee;">';
                                    html += '<label><input style="margin-right:5px" type="radio" name="store_id" data-lat="' + data[i].lat + '" data-lng="' + data[i].lng + '" data-store="'+data[i].store_name+'('+data[i].address+')&nbsp;&nbsp;电话：'+data[i].store_tel+'&nbsp;&nbsp;时间：'+data[i].start_time+'-'+data[i].finish_time+'" value="' + data[i].store_id + '">' + data[i].store_name+'('+data[i].province_name + data[i].city_name + data[i].dictrict_name + data[i].address+')</label>';
                                    html += '<span style="position:absolute;right:5px;bottom:5px">' + data[i].distance + 'km</span>';
                                    html += '</div>';
                                }
                            }else{
                                if(i===0){
                                    theLocation(data[i].lng,data[i].lat);
                                    html += '<div style="height:50px;padding:10px;position:relative;border-bottom:1px solid #eee;">';
                                    html += '<label><input style="margin-right:5px" type="radio" name="store_id" data-lat="' + data[i].lat + '" data-lng="' + data[i].lng + '" data-store="'+data[i].store_name+'('+data[i].address+')&nbsp;&nbsp;电话：'+data[i].store_tel+'&nbsp;&nbsp;时间：'+data[i].start_time+'-'+data[i].finish_time+'" value="' + data[i].store_id + '" checked>' + data[i].store_name+'('+data[i].province_name + data[i].city_name + data[i].dictrict_name + data[i].address+')</label>';
                                    html += '<span style="position:absolute;right:5px;bottom:5px">' + data[i].distance + 'km</span>';
                                    html += '</div>';
                                }else{
                                    html += '<div style="height:50px;padding:10px;position:relative;border-bottom:1px solid #eee;">';
                                    html += '<label><input style="margin-right:5px" type="radio" name="store_id" data-lat="' + data[i].lat + '" data-lng="' + data[i].lng + '" data-store="'+data[i].store_name+'('+data[i].address+')&nbsp;&nbsp;电话：'+data[i].store_tel+'&nbsp;&nbsp;时间：'+data[i].start_time+'-'+data[i].finish_time+'" value="' + data[i].store_id + '">' + data[i].store_name+'('+data[i].province_name + data[i].city_name + data[i].dictrict_name + data[i].address+')</label>';
                                    html += '<span style="position:absolute;right:5px;bottom:5px">' + data[i].distance + 'km</span>';
                                    html += '</div>';
                                }
                            }
                            
                        }
                        $('#J-storeList').html(html);
                    }
                    
                }
            });
        }
        $('#J-storeList').on('change','input[name=store_id]',function(){
            var lat = $(this).data('lat');
            var lng = $(this).data('lng');
            theLocation(lng,lat);
        })
        /**
         * 计算总金额
         */
        function calculateTotalAmount() {
            var total_pay_amount = 0.00;
            post_data['order'] = {};
            post_data['promotion'] = {};
            post_data['shop'] = {};
            $(".J-shop_loop").each(function () {
                var shop_id = $(this).attr("data-shop-id");
                post_data['order'][shop_id] = {};
                post_data['order'][shop_id]['sku'] = {};
                post_data['shop'][shop_id] = {};
                post_data['shop'][shop_id]['goods_id_array'] = [];
                post_data['shop'][shop_id]['member_amount'] = 0;
                post_data['shop'][shop_id]['shipping_fee'] = 0;
                post_data['shop'][shop_id]['promotion_free_shipping'] = 0;

                var shop_total_num = 0;//商品数目
                var shop_total_amount = 0.00;//店铺商品原价总金额
                var shop_discount_amount = 0.00;//打折之后的店铺商品总金额,用于后台验证是否满足满减
                var shop_promotion_reduction_amount = 0.00;//店铺商品优惠总金额
                var shop_should_paid_amount = 0.00;//买家应付店铺金额
                var shop_member_reduction_amount = 0.00;//会员价格优惠的总金额
                $(this).find(".J-goodsInfo").each(function (k, v) {
                    var temp_sku_id = $(v).attr('data-sku-id');
                    var temp_goods_id = $(v).attr('data-goods-id');
                    var temp_sub_num = parseInt($(v).attr('data-num'));
                    var temp_sub_price = parseFloat($(v).attr('data-price'));

                    var temp_seckill_id = $(v).attr('data-seckill-id');
                    var temp_store_id = $(v).attr('data-store-id');

                    var temp_sub_member_price = parseFloat($(v).attr('data-member-price'));//会员折扣价
                    var temp_sub_discount_price = parseFloat($(v).attr('data-discount-price'));//所有折扣价(包括会员)
                    var temp_sub_point_deduction_max = parseFloat($(v).attr('data-point-deduction-max'));//积分抵扣
                    var temp_sub_point_return_max = parseFloat($(v).attr('data-point-return-max'));//积分返还

                    var temp_sub_amount = parseFloat(temp_sub_num * temp_sub_price);
                    var temp_sub_discount_amount = parseFloat((temp_sub_discount_price * temp_sub_num).toFixed(2));//买家应付折扣之后的金额

                    var temp_sub_member_amount = parseFloat((temp_sub_member_price * temp_sub_num).toFixed(2));
                    var temp_sub_sku_shipping_fee = parseFloat(((temp_sub_num / post_data['shipping'][temp_goods_id]['total_num']) * post_data['shipping'][temp_goods_id]['shipping_fee']).toFixed(2));

                    var temp_sub_promotion_amount = temp_sub_member_amount - temp_sub_discount_amount;

                    shop_total_num += temp_sub_num;
                    shop_total_amount += temp_sub_amount;
                    shop_discount_amount += temp_sub_discount_amount;
                    shop_member_reduction_amount += parseFloat((temp_sub_amount - temp_sub_member_amount).toFixed(2));
                    shop_promotion_reduction_amount += temp_sub_promotion_amount;
                    //console.log(shop_promotion_reduction_amount)
                    shop_should_paid_amount += temp_sub_discount_amount;

                    post_data['order'][shop_id]['sku'][temp_sku_id] = {};
                    post_data['order'][shop_id]['sku'][temp_sku_id]['sku_id'] = temp_sku_id;
                    post_data['order'][shop_id]['sku'][temp_sku_id]['goods_id'] = temp_goods_id;
                    post_data['order'][shop_id]['sku'][temp_sku_id]['seckill_id'] = temp_seckill_id;
                    post_data['order'][shop_id]['sku'][temp_sku_id]['price'] = temp_sub_price;
                    post_data['order'][shop_id]['sku'][temp_sku_id]['member_price'] = temp_sub_member_price;
                    post_data['order'][shop_id]['sku'][temp_sku_id]['discount_price'] = temp_sub_discount_price;
                    post_data['order'][shop_id]['sku'][temp_sku_id]['num'] = temp_sub_num;
                    post_data['order'][shop_id]['sku'][temp_sku_id]['sku_amount'] = temp_sub_amount;
                    post_data['order'][shop_id]['sku'][temp_sku_id]['discount_id'] = $(v).attr('data-discount-id');
                    post_data['order'][shop_id]['sku'][temp_sku_id]['promotion_shop_id'] = $(v).attr('data-promotion-shop-id');
                    post_data['order'][shop_id]['sku'][temp_sku_id]['shop_id'] = shop_id;
                    post_data['order'][shop_id]['sku'][temp_sku_id]['shipping_fee'] = temp_sub_sku_shipping_fee;
                    post_data['order'][shop_id]['sku'][temp_sku_id]['point_deduction_max'] = (isNaN(temp_sub_point_deduction_max))?'':temp_sub_point_deduction_max;
                    post_data['order'][shop_id]['sku'][temp_sku_id]['point_return_max'] = (isNaN(temp_sub_point_return_max))?'':temp_sub_point_return_max;
                    post_data['order'][shop_id]['sku'][temp_sku_id]['store_id'] = temp_store_id;

                    post_data['shop'][shop_id]['goods_id_array'].push(temp_goods_id);
                    post_data['shop'][shop_id]['member_amount'] += temp_sub_member_price * temp_sub_num;
                    post_data['shop'][shop_id]['shipping_fee'] += parseFloat(temp_sub_sku_shipping_fee);
                });
                post_data['shop'][shop_id]['shop_total_amount'] = shop_total_amount;
                post_data['shop'][shop_id]['discount_amount'] = shop_discount_amount;
                
                //满减送-满减
                var man_song_full_cut_obj = $(this).find("[data-full-cut-type='full_cut']");
                var man_song_shipping_obj = $(this).find("[data-full-cut-type='shipping']");

                post_data['promotion'][shop_id] = {};
                post_data['promotion'][shop_id]['man_song'] = {};
                var temp_man_song_id = man_song_full_cut_obj.attr('data-man-song-id');
                var temp_man_song_rule_id = man_song_full_cut_obj.attr('data-rule-id');
                if (temp_man_song_id !== undefined) {
                    var temp_discount = parseFloat(man_song_full_cut_obj.attr('data-at-discount'));
                    var temp_full_cut_goods_limit = man_song_full_cut_obj.attr('data-goods-limit').split(',');
                    var temp_full_cut_shop_id = man_song_full_cut_obj.attr('data-man-song-shop-id');

                    $(this).find(".full-cut-data-sku-percent").each(function (k, v) {
                        var temp_sku_id = $(v).attr('data-sku-id');
                        var temp_sku_percent = $(v).attr('data-sku-percent');

                        post_data['order'][shop_id]['sku'][temp_sku_id]['promotion_id'] = temp_man_song_id;
                        post_data['order'][shop_id]['sku'][temp_sku_id]['full_cut_sku_percent'] = temp_sku_percent;
                        post_data['order'][shop_id]['sku'][temp_sku_id]['full_cut_sku_amount'] = temp_discount;
                        post_data['order'][shop_id]['sku'][temp_sku_id]['full_cut_shop_id'] = temp_full_cut_shop_id;
                    });

                    post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id] = {};
                    post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['full_cut'] = {};
                    post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['full_cut']['man_song_id'] = temp_man_song_id;
                    post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['full_cut']['rule_id'] = temp_man_song_rule_id;
                    post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['full_cut']['discount'] = temp_discount;
                    post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['full_cut']['price'] = parseFloat(man_song_full_cut_obj.attr('data-rule-price'));
                    post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['full_cut']['goods_limit'] = temp_full_cut_goods_limit;
                    post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['full_cut']['shop_id'] = temp_full_cut_shop_id;


                    post_data['shop'][shop_id]['man_song_coupon_type_id'] = man_song_full_cut_obj.attr('data-man-song-coupon-type-id');
                    post_data['shop'][shop_id]['man_song_point'] = man_song_full_cut_obj.attr('data-man-song-point');
                    post_data['shop'][shop_id]['man_song_amount'] = temp_discount;
                    post_data['shop'][shop_id]['man_song_shop_id'] = temp_full_cut_shop_id;

                    shop_promotion_reduction_amount += temp_discount;
                    if (shop_should_paid_amount > temp_discount) {
                        shop_should_paid_amount -= temp_discount;
                    } else {
                        shop_should_paid_amount = 0;
                    }
                }
                //选中的优惠券信息
                var coupon_obj = $(this).find(".J-couponSelect option:selected");
                var coupon_id = coupon_obj.val();
                //有效的coupon信息
                if (coupon_id > 0) {
                    var temp_coupon_genre = coupon_obj.attr('data-genre');
                    var temp_coupon_money = parseFloat(coupon_obj.attr('data-money')) || 0;
                    var temp_coupon_discount = parseFloat(coupon_obj.attr('data-discount')) || 10;
                    var temp_coupon_goods_limit = coupon_obj.attr('data-goods-limit');
                    var temp_coupon_shop_id = coupon_obj.attr('data-coupon-shop-id');

                    post_data['promotion'][shop_id]['coupon'] = {};
                    post_data['promotion'][shop_id]['coupon']['coupon_id'] = coupon_id;
                    post_data['promotion'][shop_id]['coupon']['money'] = temp_coupon_money;
                    post_data['promotion'][shop_id]['coupon']['coupon_genre'] = temp_coupon_genre;
                    post_data['promotion'][shop_id]['coupon']['goods_limit'] = temp_coupon_goods_limit.split(',');
                    post_data['promotion'][shop_id]['coupon']['at_least'] = coupon_obj.attr('data-at-least');
                    post_data['promotion'][shop_id]['coupon']['discount'] = temp_coupon_discount;
                    post_data['promotion'][shop_id]['coupon']['shop_id'] = temp_coupon_shop_id;
                    if (temp_coupon_genre == 1 || temp_coupon_genre == 2) {

                    } else if (temp_coupon_genre == 3) {
                        var temp_total_amount_before_coupon_discount = 0.00;
                        if (temp_coupon_goods_limit) {//部分商品可用
                            $.each(post_data['order'][shop_id]['sku'], function (sku_id, sku) {
                                if ($.inArray(sku['goods_id'], post_data['promotion'][shop_id]['coupon']['goods_limit']) != -1) {
                                    temp_total_amount_before_coupon_discount += sku.discount_price * sku.num
                                    if (sku.full_cut_sku_percent != undefined && sku.full_cut_sku_amount != undefined){
                                        temp_total_amount_before_coupon_discount -= sku.full_cut_sku_percent * sku.full_cut_sku_amount
                                    }
                                }
                            })
                        } else {
                            temp_total_amount_before_coupon_discount = shop_should_paid_amount;
                        }
                        temp_coupon_money = parseFloat((temp_total_amount_before_coupon_discount * (1 - temp_coupon_discount / 10)).toFixed(2));
                    }
                    shop_promotion_reduction_amount += temp_coupon_money;
                    if (shop_should_paid_amount > temp_coupon_money){
                        shop_should_paid_amount = shop_should_paid_amount - temp_coupon_money;
                    } else {
                        shop_should_paid_amount = 0;
                    }

                    post_data['promotion'][shop_id]['coupon']['coupon_reduction_amount'] = temp_coupon_money;
                    post_data['promotion'][shop_id]['coupon']['coupon_shop_id'] = temp_coupon_shop_id;

                    var coupon_sku_percent_obj = $("div[data-coupon-id=" + coupon_id + "][data-shop-id=" + shop_id + "]");
                    if (coupon_sku_percent_obj) {
                        $.each(coupon_sku_percent_obj, function (k, v) {
                            var temp_sku_id = $(v).attr('data-sku-id');
                            post_data['order'][shop_id]['sku'][temp_sku_id]['coupon_id'] = coupon_id;
                            post_data['order'][shop_id]['sku'][temp_sku_id]['coupon_sku_percent'] = $(v).attr('data-sku-percent');
                            post_data['order'][shop_id]['sku'][temp_sku_id]['coupon_sku_percent_amount'] = $(v).attr('data-sku-amount');
                        })
                    }
                }

                //满减送-包邮
                var temp_man_song_id = man_song_shipping_obj.attr('data-man-song-id');
                var temp_man_song_rule_id = man_song_shipping_obj.attr('data-rule-id');
                if (temp_man_song_id !== undefined) {
                    var temp_man_song_shop_id = man_song_shipping_obj.attr('data-man-song-shop-id');
                    var temp_man_song_goods_limit = man_song_shipping_obj.attr('data-goods-limit').split(',');
                    var temp_man_song_range_type = man_song_shipping_obj.attr('data-man-song-range-type');
                    if (temp_man_song_id !== undefined) {
                        if (!post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]) {
                            post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id] = {};
                        }
                        post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['shipping'] = {};
                        post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['shipping']['man_song_id'] = temp_man_song_id;
                        post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['shipping']['rule_id'] = temp_man_song_rule_id;
                        post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['shipping']['free_shipping_fee'] = true;
                        post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['shipping']['price'] = parseFloat(man_song_full_cut_obj.attr('data-rule-price'));
                        post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['shipping']['goods_limit'] = temp_man_song_goods_limit;
                        post_data['promotion'][shop_id]['man_song'][temp_man_song_rule_id]['shipping']['shop_id'] = temp_man_song_shop_id;

                        $.each(post_data['order'][shop_id]['sku'], function (sku_id, sku) {
                            if (temp_man_song_range_type == 0) {
                                if ($.inArray(sku['goods_id'], temp_man_song_goods_limit) != -1) {
                                    // 部分满减邮费
                                    post_data['order'][shop_id]['sku'][sku_id]['is_free_shipping'] = 1;
                                    post_data['order'][shop_id]['sku'][sku_id]['free_shipping_shop_id'] = temp_man_song_shop_id;
                                    post_data['shop'][shop_id]['promotion_free_shipping'] += sku['shipping_fee'];
                                    shop_promotion_reduction_amount = shop_promotion_reduction_amount + parseFloat(sku['shipping_fee']);
                                    post_data['order'][shop_id]['sku'][sku_id]['shipping_fee'] = post_data['order'][shop_id]['sku'][sku_id]['shipping_fee'] - sku['shipping_fee'];
                                } else {
                                    // 不在包邮范围内的商品邮费+在应付金额里面
                                    shop_should_paid_amount += sku['shipping_fee'];
                                }
                            } else {
                                // 全部满减邮费
                                post_data['order'][shop_id]['sku'][sku_id]['is_free_shipping'] = 1;
                                post_data['order'][shop_id]['sku'][sku_id]['free_shipping_shop_id'] = temp_man_song_shop_id;
                                post_data['shop'][shop_id]['promotion_free_shipping'] += sku['shipping_fee'];
                                shop_promotion_reduction_amount = shop_promotion_reduction_amount + parseFloat(sku['shipping_fee']);
                                post_data['order'][shop_id]['sku'][sku_id]['shipping_fee'] = post_data['order'][shop_id]['sku'][sku_id]['shipping_fee'] - sku['shipping_fee'];
                            }
                        })

                        post_data['shop'][shop_id]['man_song_is_free_shipping'] = true;
                        post_data['shop'][shop_id]['man_song_shipping_shop_id'] = temp_man_song_shop_id;
                    }
                } else {
                    shop_should_paid_amount += post_data['shop'][shop_id]['shipping_fee'];
                }
                // 显示邮费
                $(this).find("#shop_shipping_info_" + shop_id).html(post_data['shop'][shop_id]['shipping_fee']);

                if (shop_promotion_reduction_amount > shop_total_amount){
                    shop_promotion_reduction_amount = shop_total_amount;
                }
                post_data['shop'][shop_id]['shop_should_paid_amount'] = (shop_should_paid_amount >= 0) ? shop_should_paid_amount : 0;
                post_data['shop'][shop_id]['shop_promotion_reduction_amount'] = shop_promotion_reduction_amount;

                $(this).find('#shop_total_num_' + shop_id).text(shop_total_num);
                $(this).find('#shop_total_amount_' + shop_id).text(shop_total_amount.toFixed(2));
                $(this).find('#shop_member_reduction_amount_' + shop_id).text(-shop_member_reduction_amount.toFixed(2));
                $(this).find('#shop_express_' + shop_id).text((post_data['shop'][shop_id]['shipping_fee'] - post_data['shop'][shop_id]['promotion_free_shipping']).toFixed(2));

                $(this).find('#shop_reduction_amount_' + shop_id).text(-shop_promotion_reduction_amount.toFixed(2));
                $(this).find('#shop_should_paid_amount_' + shop_id).text(shop_should_paid_amount.toFixed(2));

                total_pay_amount += shop_should_paid_amount;
            })
            total_pay_amount = total_pay_amount.toFixed(2);
            post_data['total_pay_amount'] = total_pay_amount;
            $("#real_price").text(total_pay_amount);
            //validationMemberBalance();
            
            //积分返还
            var is_point = $("#is_point").val();
            var member_point = $("#member_point").val();
            var total_return_point = 0;
            //积分抵扣
            post_data['is_deduction'] = 0;
        	var total_deduction_point = 0;
        	var total_deduction_money = 0;
            var is_point_deduction = $("#is_point_deduction").val();
            post_data['shipping_type'] = $("#sendList .selected").data("select");
            

            $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + '/order/pointDeduction'),
                dataType: "json",
                data: {"post_data": post_data},
                success: function (data) {
                	if(is_point==1){
                        var datas = data['data']['return'];
                        if(datas.length>0){
                            for (var i = 0; i < datas.length; i++) {
                            	total_return_point += datas[i].total_return_point;
                            }
                        }
                    	$('#point_return').removeClass('hide');
                        $('#total_return_point').text(total_return_point);
                	}
                	if(is_point_deduction==1){
                        var datas = data['data']['deduction'];
                        if(datas.length>0){
                        	var points = 1;
                            for (var i = 0; i < datas.length; i++) {
                            	if(points==1){
                                	total_deduction_point += datas[i].total_deduction_point;
                                	total_deduction_money += datas[i].total_deduction_money;
                            	}
                            	if(total_deduction_point>=member_point)points=0;
                            }
                        }
                    	$('#point_deduction').removeClass('hide');
                        $('#total_deduction_point').text(total_deduction_point.toFixed(2));
                        real_moneyp = total_pay_amount - total_deduction_money;
                        $('#total_deduction_point').attr('data-money',total_deduction_money.toFixed(2));
                        $('#total_deduction_point').attr('data-real-money',total_pay_amount);
                        $('#total_deduction_point').attr('data-real-moneyp',real_moneyp.toFixed(2));
                        if($('#is_deduction').is(':checked')) {
                        	$('#total_deduction_money').text(total_deduction_money.toFixed(2));
                        	$('#real_price').text(real_moneyp.toFixed(2));
                        	post_data['is_deduction'] = 1;
                        	post_data['total_pay_amount'] = real_moneyp.toFixed(2);
                        }
                	}
                }
            });
        }
        /**
         * 积分抵扣
         */
        $('#is_deduction').click(function(){
        	var real_price =  parseInt($("#real_price").html());
        	var money_total_deduction = $('#total_deduction_point').attr('data-money');
        	var real_money = $('#total_deduction_point').attr('data-real-money');
        	var real_moneyp = $('#total_deduction_point').attr('data-real-moneyp');
        	if(this.checked){
        		post_data['is_deduction'] = 1;
                $('#total_deduction_money').text(money_total_deduction);
            	$('#real_price').text(real_moneyp);
            	post_data['total_pay_amount'] = real_moneyp;
        	}else{
        		post_data['is_deduction'] = 0;
                $('#total_deduction_money').text(0);
            	$('#real_price').text(real_money);
            	post_data['total_pay_amount'] = real_money;
        	}
        });
    };
    return confirmOrder;
});
