
define(["jquery", "layer", "common", "dialog", "goods"], function ($, layer, common, dialog, Goods) {
    var goodslist = {};
    var cart_id_arr = dialog.cart_id_arr;//购物车中的商品Id array
    var cart_num = dialog.cart_num; //商品的数量 array
    var attr_item = $('#hidden_attr_item').val();
    var spec_item = $('#hidden_spec_item').val();
    goodslist.operation = function () {
        var is_post = true;
        var isMore = false;
        Goods.getGuessYouLike();
        var view_type = localStorage.getItem("type");
        if(view_type == 'list'){
            $('.showStyle-ul li[data-type=list]').addClass('active').siblings().removeClass('active');
            $('.filter-results').hide();
            $('.filter-results2').show();
        }else{
            $('.showStyle-ul li[data-type=grid]').addClass('active').siblings().removeClass('active');
            $('.filter-results').show();
            $('.filter-results2').hide();
        }
        // 添加
        $(".add-cart").click(function () {
            var goods_id = $(this).data('id');
            var goods_name = $(this).data('name');
            var pic_id = $(this).data('pic');
            var max_buy = $(this).data('max_buy');
            var state = $(this).data('state');
            ShowGoodsAttribute(goods_id, goods_name, pic_id, max_buy, state);
        });
        // 立即购买
        $(".buy-now").click(function () {
            var goods_id = $(this).data('id');
            var sku_id = $(this).data('skuid');
            var state = $(this).data('state');
            buyNowCart(goods_id,sku_id,state);
        });
        $("#search_sure").on('click', function () {
            searchConditions();
        });
        $(".attrs").on('click', '.J-brand', function () {
            var brand_id = $(this).data('brand_id');
            var brand_name = $(this).data('brand_name');
            selectBrand($(this), brand_id, brand_name);
        });
        $(".attrs").on('click', '.J-spec', function () {
            specSelect($(this));
        });
        $(".attrs").on('click', '.J-attr', function () {
            attrSelect($(this));
        });
        $(".attrs").on('click', '.J-removeAttr', function () {
            removeAttr($(this));
        });
        $(".attrs").on('click', '.J-removeSpec', function () {
            removeSpec($(this));
        });
        $(".attrs").on('click', '.J-brandMore', function () {
            brandMoreSearch($(this));
        });
        $("#speDiv").on('click', '.J-selectAttr', function () {
            var i = $(this).data('i');
            var goods_id = $(this).data('id');
            var picture = $(this).data('pic');
            selectAttr($(this), i, goods_id, picture);
        });
        $(".J-addfromlist").click(function () {
            addToCart();
        });
        $(".J-closespe").click(function () {
            closeBuy();
        });
        $('.showStyle-ul').on('click', 'li', function () {
            dialog.lazyLoad();
            $(this).addClass('active').siblings().removeClass('active');
            var type = $(this).data('type');
            if (type == 'list') {
                localStorage.setItem("type", 'list'); 
                $('.filter-results').hide();
                $('.filter-results2').show();
            } else {
                localStorage.setItem("type", 'grid'); 
                $('.filter-results').show();
                $('.filter-results2').hide();
            }
        });
        // 添加收藏商品
        $(".collect-goods").on('click', function () {
            if (dialog.isLogin(1)) {
                var obj = $(this);
                var is_member_fav_goods = obj.data('collect');
                var goods_id = obj.data('id');
                var key = obj.data('key');
                if (is_member_fav_goods == 0) {
                    //点击收藏
                    $.ajax({
                        url: __URL(SHOPMAIN + '/components/collectiongoodsorshop'),
                        type: "post",
                        data: {"fav_id": goods_id, "fav_type": "goods", "log_msg": ""},
                        success: function (data) {
                            if (data.code > 0) {
                                $('.J-collect-'+key).data('collect', 1);
                                $('.J-collect-'+key).html("已收藏");
                            } else {
                                layer.msg("商品已经收藏过了");
                            }
                        }
                    });
                } else {
                    //取消收藏
                    $.ajax({
                        url: __URL(SHOPMAIN + '/components/cancelcollgoodsorShop'),
                        type: "post",
                        data: {"fav_id": goods_id, "fav_type": "goods", "log_msg": ""},
                        success: function (data) {
                            if (data.code > 0) {
                                $('.J-collect-'+key).data('collect', 0);
                                $('.J-collect-'+key).html("收藏商品");
                            } else {
                                layer.msg("商品已经取消收藏了");
                            }
                        }
                    });
                }
            }else{
                dialog.loginMember("会员登录", ".login-dialog");
            }
        });
        $(".guessBuy-ul li").on('click', function () {
            $(this).addClass('active').siblings().removeClass('active');
            var type = $(this).data('type');
            if (type === 'history') {
                $(this).siblings('.J-changeLike').hide();
                $(this).siblings('.J-del').show();
                Goods.getMemberHistory();
            } else {
                $(this).siblings('.fr').show();
                $(this).siblings('.J-del').hide();
                Goods.getGuessYouLike();
            }
        });
        $(".guessBuy-ul").on('click', '.J-changeLike', function () {
            Goods.change_like();
        });
        $(".guessBuy-ul").on('click', '.J-del', function () {
            Goods.clear_history();
        });
        // 点击多选的js
        $(".av-options").on("click", ".avo-multiple", function () {
            if ($(this).parent(".av-options").siblings(".av-state").hasClass("av-expand")) {
                $(this).parent(".av-options").siblings(".av-state").removeClass("av-expand").addClass("av-collapse");
            }
            $(this).parents(".av-options").hide();
            $(this).parents(".j_Cate").toggleClass("multiple");
            $(this).parents(".j_Cate").find(".av-state").toggleClass("av-expand av-collapse");
            $(this).parent(".av-options").siblings(".sl-btns").toggleClass("hovers");
            $(this).parents(".av-options").siblings(".av-state").find("i").addClass("iBorders");
            isMore = true;
        });
        // 点击取消
        $(".J_btnsCancel").on("click", function () {
            $(this).parents(".sl-btns").siblings(".av-options").show();
            $(this).parents(".sl-btns").siblings(".av-state").toggleClass("av-expand av-collapse");
            $(this).parents(".sl-btns").toggleClass("hovers");
            $(this).parents(".j_Cate").toggleClass("multiple");
            $(this).parents(".sl-btns").siblings(".av-state").find("i").removeClass("icon-selected");
            $(".J_btnsConfirm").removeClass("select-button-sumbit").addClass("disabled");
            isMore = false;
        });
        /**
         * 商品列表，点击购物车，弹出商品属性 -wyj
         * 2017年3月3日 10:12:27
         * @param goods_id
         */
        function getAttribute(goods_id) {
            var sku_attribute = new Array();
            $("input[name='goods_sku" + goods_id + "']").each(function () {
                var obj = new Object();
                obj.sku = $(this).val();
                obj.stock = $(this).attr("stock");
                obj.price = $(this).attr("price");
                obj.skuid = $(this).attr("skuid");
                obj.skuname = $(this).attr("skuname");
                sku_attribute.push(obj);
            });
            return sku_attribute;
        }

        //加入购物车 state ：'商品状态 0下架，1正常
        function ShowGoodsAttribute(goods_id, goods_name, pic_id, max_buy, state) {
            if (state == 1) {
                $("#hidden_goodsid").val(goods_id);
                $("#hidden_goods_name").val(goods_name);
                $("#hidden_default_img_id").val(pic_id);
                $("#hidden_max_buy").val(max_buy);//存储当前选中商品的最大限购数量
                var sku_attribute = getAttribute(goods_id);
                $.ajax({
                    url: __URL(SHOPMAIN + "/goods/getgoodsskuinfo"),
                    type: "post",
                    data: {"goods_id": goods_id},
                    success: function (res) {
                        var str = "";
                        if (res.length > 0) {
                            for (var i = 0; i < res.length; i++) {

                                var spec_value_list = res[i]["value"];
                                str += '<div class="dt">' + res[i]["spec_name"] + '</div>';
                                str += '<div class="dd radio-dd">';
                                var index = 0;

                                for (var j = 0; j < spec_value_list.length; j++) {

                                    var value = spec_value_list[j]["spec_id"] + ':' + spec_value_list[j]["spec_value_id"];
                                    var picture = parseInt(spec_value_list[j]['picture']);
                                    if (index == 0) {
                                        str += '<span class="attr-radio curr">';
                                        //存在SKU商品，就用。否则用商品主图
                                        if (picture > 0){
                                            $("#hidden_default_img_id").val(picture);
                                        }
                                    } else {
                                        str += '<span class="attr-radio">';
                                    }
                                    index++;
                                    str += '<label class="J-selectAttr" data-i="' + i + '" data-id="' + goods_id + '" data-pic="' + picture + '" name="attribute_' + i + '" value="' + value + '" >';
                                    str += '<font>' + spec_value_list[j]["spec_value_name"] + '</font></label></span>';
                                }
                                str += '</div><div class="blank"></div>';

                            }
                            $(".js-sku-list").html(str);
                            $('#speDiv').css({'top': ($(window).height() - $('#speDiv').outerHeight()) / 2, "display": "block"});
                            $("#mask").show();
                            setSelectAttr(goods_id);

                        } else {

                            //没有SKU直接取
                            $("#hidden_skuname").val(sku_attribute[0].skuname);
                            $("#hidden_sku_price").val(sku_attribute[0].price);
                            $("#hidden_skuid").val(sku_attribute[0].skuid);
                            var no_sku_max_buy = $("#hidden_max_buy").val();
                            if(no_sku_max_buy < '1'){
                                layer.msg('库存不足');
                                return;
                            }
                            addToCart();
                        }
                    }
                });
            } else {
                var state_msg = "";//'商品状态 0下架，1正常
                switch (state) {
                    case 0:
                        state_msg = "该商品已下架";
                        break;
                }
                layer.msg(state_msg);
            }
        }

        //弹出框，选择商品属性加入购物车
        function addToCart() {
            if (!is_post) {
                return false;
            }
            var num = 0;
            if (cart_id_arr != null) {
                for (var i = 0; i < cart_id_arr.length; i++) {
                    if (cart_id_arr[i] == parseInt($("#hidden_goodsid").val())) {
                        num++;
                    }
                    if (cart_id_arr[i] == parseInt($("#hidden_goodsid").val()) && cart_num[i] > 1 && cart_num[i] == $("#hidden_max_buy").val()) {
                        layer.msg("该商品限购" + cart_num[i] + "件");
                        return false;
                    }
                }
                //再次检查购物车中的商品，是否有同一件商品，不同的SKU
                if (num > 0 && num == $("#hidden_max_buy").val()) {
                    layer.msg("购物车中已存在该商品");
                    return false;
                }
                setSelectAttr($("#hidden_goodsid").val());
                var cart_detail = new Object();
                cart_detail.goods_id = $("#hidden_goodsid").val();
                cart_detail.count = 1;//$("#num").val();
                cart_detail.goods_name = $("#hidden_goods_name").val();
                cart_detail.sku_id = $("#hidden_skuid").val();
                cart_detail.sku_name = $("#hidden_skuname").val();
                cart_detail.price = $("#hidden_sku_price").val();
                cart_detail.picture_id = $("#hidden_default_img_id").val();
                cart_detail.cost_price = $("#hidden_sku_price").val();//成本价
                var cart_tag = "addCart";//暂时没用，保留。
                $.ajax({
                    url: __URL(SHOPMAIN + "/goods/addcart"),
                    type: "post",
                    data: {"cart_detail": JSON.stringify(cart_detail), "cart_tag": cart_tag},
                    success: function (res) {
                        if (res > 0) {
                            $(".add-cart").removeClass("js-disabled");
                            dialog.refreshCart();//里边会加载购物车中的数量
                            layer.msg("添加购物车成功");
                        } else if (res === -2) {
                            layer.msg("数量超出范围");
                        }else{
                            layer.msg("添加购物车失败");
                        }
                        closeBuy();
                    }
                });
            }
        }
        
        //立即购买
        function buyNowCart(goods_id,sku_id,state) {
        	if (isLogin(1)) {
        	if (state == 1) {
	            if (!is_post) {
	                return false;
	            }
	            $.ajax({
	                url: __URL(SHOPMAIN + "/member/ordercreatesession"),
	                type: "post",
	                data: {"tag": "buy_now", "sku_id": sku_id, "num": 1, "seckill_id": 0},
	                success: function (res) {
	                    if (res['code'] > 0) {
	                        location.href = __URL(SHOPMAIN + "/member/paymentorder");
	                    } else {
	                        layer.msg(res['message']);
	                    }
	                }
	            });
            } else {
                var state_msg = "";//'商品状态 0下架，1正常
                switch (state) {
                    case 0:
                        state_msg = "该商品已下架";
                        break;
                }
                layer.msg(state_msg);
            }
            }else{
                loginMember("会员登录", ".login-dialog");
            }
        }

        function isLogin(type){
            if ($("#hidden_uid").val() == null || $("#hidden_uid").val() == "") {
                if(type==1){
                    return false;
                }else{
                    layer.msg('您还没有登陆哦', {icon: 2, time: 1000}, function () {
                        location.href = __URL(SHOPMAIN + '/login/index');
                    });
                    return false;
                }
            }
            return true;
        }
        
        function loginMember(titles, dialogEle, callback){
            layer.open({
                type: 1,
                title: [titles, "font-weight:bold"],
                skin: "layui-layer-rim", //加上边框
                area: ["480px","480px"], //宽高
                content: $(dialogEle),
                btn: [],
                btnAlign: "c",
                // 点击确定的回调
                yes: function (index, layero) {
                    if (!callback || callback() !== false) {
                        layer.close(index);
                    }
                },
                // 弹出框出现的回调
                success: function (layero, index) {
                    layero.find(".layui-layer-btn").css("text-align", "center");
                }
            });
        }
        
        //关闭sku弹出框
        function closeBuy() {
            is_post = true;
            $("#mask").hide();
            $('#speDiv').hide();
        }
        /**
         * 选择对应的属性进行匹配
         * @param goods_id
         */
        function setSelectAttr(goods_id) {
            var arr = new Array();
            $("span[class='attr-radio curr']").each(function () {
                arr.push($(this).find("label").attr("value"));
            });
            arr.sort();
            $("input[name='goods_sku" + goods_id + "']").each(function () {
                var curr = $(this).val().split(";");
                var goods_sku_arr = new Array();
                for (var j = 0; j < curr.length; j++) {
                    if (curr[j] != '') {
                        goods_sku_arr.push(curr[j]);
                    }
                }
                goods_sku_arr.sort();
                if (goods_sku_arr.toString() == arr.toString()) {
                    $("#hidden_skuid").val($(this).attr("skuid"));
                    $("#hidden_skuname").val($(this).attr("skuname"));
                    $("#hidden_sku_price").val($(this).attr("price"));
                    if ($(this).attr("stock") == 0) {
                        is_post = false;
                        $(".spe-btn .sure-btn").css("background-color", "#d8d8d8");
                        $(".spe-btn .sure-btn").css("border", "1px solid #d8d8d8");
                    } else {
                        is_post = true;
                        $(".spe-btn .sure-btn").css("background-color", "#ff0000");
                        $(".spe-btn .sure-btn").css("border", "1px solid #ff0000");
                    }
                }
            });
        }
        /**
         * 选择sku对应的属性。同时判断是否有SKU主图，有就用
         * @param obj
         * @param i
         * @param goods_id
         * @param picture
         */
        function selectAttr(obj, i, goods_id, picture) {
            $("label[name='attribute_" + i + "']").each(function () {
                $(this).parent().removeClass("curr");
            });
            $(obj).parent().addClass("curr");

            //如果有SKU主图，用就用。
            if (picture > 0){
                $("#hidden_default_img_id").val(picture);
            }
            setSelectAttr(goods_id);
        }
        function searchConditions() {
            var min_price = $("#min_price").val();
            var max_price = $("#max_price").val();
            var url_parameter = $("#hidden_url_parameter").val();
            if (max_price === '') {
                $('#max_price').focus();
                layer.msg("应搜索小于等于此价格的商品");
                return;
            }
            if (min_price === "") {
                min_price = 0;
            } 
            if (parseFloat(min_price) > parseFloat(max_price) || min_price.length > 15 || max_price.length > 15) {
                layer.msg("价格输入错误");
                return;
            }
            var url = SHOPMAIN + "/goods/goodslist?" + url_parameter;
            url += "&min_price=" + min_price + "&max_price=" + max_price;
            if ($.trim(attr_item) !== "" && $.trim(attr_item) !== undefined) {
                url += "&attr=" + attr_item;
            }
            if ($.trim(spec_item) !== "" && $.trim(spec_item) !== undefined) {
                url += "&spec=" + spec_item;
            }
            location.href = __URL(url);
            
        }
        // 单个品牌查询
        function selectBrand(obj, brand_id, brand_name) {
            var url_parameter = $("#hidden_url_parameter").val();
            if (obj.parent().hasClass("brand-seled")) {
                obj.parent().removeClass("brand-seled");
            } else {
                obj.parent().addClass("brand-seled");
            }
            if (isMore) {
                //多选
                if ($("#brand-abox li.brand-seled").length) {

                    $(".J_btnsConfirm").removeClass("disabled").addClass("select-button-sumbit");
                } else {
                    $(".J_btnsConfirm").removeClass("select-button-sumbit").addClass("disabled");
                }

            } else {
                // 单选
                $("#brand-abox").find("li").removeClass("brand-seled");
                var url = SHOPMAIN + "/goods/goodslist?" + url_parameter;
                url += "&brand_id=" + brand_id + "&brand_name=" + brand_name;
                //拼装属性条件
                if ($.trim(attr_item) !== "" && $.trim(attr_item) !== undefined) {
                    url += "&attr=" + attr_item;
                }
                if ($.trim(spec_item) !== "" && $.trim(spec_item) !== undefined) {
                    url += "&spec=" + spec_item;
                }
                location.href = __URL(url);
            }
        }
        // 多个品牌查询
        function brandMoreSearch(obj) {
            if (!obj.hasClass("disabled")) {
                var url_parameter = $("#hidden_url_parameter").val();
                var arr_id = new Array();
                var arr_name = new Array();
                $("#brand-abox").find(".brand-seled").each(function () {
                    arr_id.push($(this).attr("data-brand-id"));
                    arr_name.push($(this).attr("data-brand-name"));
                });
                var brand_id = arr_id.join(",");
                var brand_name = arr_name.join(",");
                var url = SHOPMAIN + "/goods/goodslist?" + url_parameter;
                url += "&brand_id=" + brand_id + "&brand_name=" + brand_name;
                if ($.trim(attr_item) !== "" && $.trim(attr_item) !== undefined) {
                    url += "&attr=" + attr_item;
                }
                if ($.trim(spec_item) !== "" && $.trim(spec_item) !== undefined) {
                    url += "&spec=" + spec_item;
                }
                location.href = __URL(url);
            }
        }
        //规格
        function specSelect(obj) {
            var url_parameter = $("#hidden_url_parameter").val();
            if (obj.parent().hasClass("brand-seled")) {
                obj.parent().removeClass("brand-seled");
            } else {
                obj.parent().addClass("brand-seled");
            }
            var spec_id = obj.data("spec_id");
            var spec_value_id = obj.data("spec_value_id");
            judgeSpecIsHaveData(spec_id, spec_value_id, true);
            var url = SHOPMAIN + "/goods/goodslist?" + url_parameter;
            if ($.trim(attr_item) !== "" && $.trim(attr_item) !== undefined) {
                url += "&attr=" + attr_item;
            }
            if ($.trim(spec_item) !== "" && $.trim(spec_item) !== undefined) {
                url += "&spec=" + spec_item;
            }
            location.href = __URL(url);
        }
        /**
         * 规格筛选
         * @param spec_id
         * @param spec_value_id
         * @param is_remove
         */
        function judgeSpecIsHaveData(spec_id, spec_value_id, is_remove) {
            var temp_array = new Array();
            var spec_array = new Array()
            temp_array = spec_item.split(";");
            for (var i = 0; i < temp_array.length; i++) {
                spec_array.push(temp_array[i].split(":"));
            }
            var is_have = true;
            //如果本规格值已存在要改变吃规格值
            $.each(spec_array, function (k, v) {
                if (v[1] == spec_value_id) {
                    if (is_remove) {
                        spec_array[k][1] = spec_value_id;
                    } else {
                        SpliceArrayItem(spec_array, v);
                    }
                    is_have = false;
                    return false;
                }
            });
            if (is_have) {
                if (spec_item == "") {
                    spec_item = spec_id + ":" + spec_value_id;
                } else {
                    spec_item += ";" + spec_id + ":" + spec_value_id;
                }
            } else {
                specArrayChangeString(spec_array);
            }

        }
        //移除某个属性条件
        function removeSpec(obj) {
            var url_parameter = $("#hidden_url_parameter").val();
            var spec_id = obj.data("spec_id");
            var spec_value_id = obj.data("spec_value_id");

            judgeSpecIsHaveData(spec_id, spec_value_id, false);
            var url = SHOPMAIN + "/goods/goodslist?" + url_parameter;
            if ($.trim(attr_item) !== "" && $.trim(attr_item) !== undefined) {
                url += "&attr=" + attr_item;
            }
            if ($.trim(spec_item) !== "" && $.trim(spec_item) !== undefined) {
                url += "&spec=" + spec_item;
            }

            location.href = __URL(url);
        }
        //规格值字符串转数组
        function specArrayChangeString(array) {
            var temp_array = new Array();
            $.each(array, function (k, v) {
                temp_array.push(v.join(":"));
            });
            spec_item = temp_array.join(";");
        }
        //属性
        function attrSelect(obj) {
            var url_parameter = $("#hidden_url_parameter").val();
            if (obj.parent().hasClass("brand-seled")) {
                obj.parent().removeClass("brand-seled");
            } else {
                obj.parent().addClass("brand-seled");
            }
            var attr_value = obj.data("attr-value");
            var attr_value_name = obj.data("attr-value-name");
            var attr_value_id = obj.data("attr_value_id");
            judgeAttrIsHaveData(attr_value, attr_value_name, true, attr_value_id);
            var url = SHOPMAIN + "/goods/goodslist?" + url_parameter;
            if ($.trim(attr_item) !== "" && $.trim(attr_item) !== undefined) {
                url += "&attr=" + attr_item;
            }
            if ($.trim(spec_item) !== "" && $.trim(spec_item) !== undefined) {
                url += "&spec=" + spec_item;
            }
            location.href = __URL(url);
        }
        //判断数据是否在数据中  存在就改变  不存在就添加 (is_remove true为添加 false位删除)
        function judgeAttrIsHaveData(attr_value, attr_value_name, is_remove, attr_value_id) {
            if (attr_value != "" && attr_value_name != "" && attr_value_id != "") {

                var temp_array = new Array();
                var attr_array = new Array()
                temp_array = attr_item.split(";");
                for (var i = 0; i < temp_array.length; i++) {
                    attr_array.push(temp_array[i].split(","));
                }
                var is_have = true;
                //如果本属性已存在要改变吃属性值
                $.each(attr_array, function (k, v) {
                    if (v[2] == attr_value_id) {
                        if (is_remove) {
                            attr_array[k][1] = attr_value_name;
                        } else {
                            SpliceArrayItem(attr_array, v);
                        }
                        is_have = false;
                        return false;
                    }
                });
                if (is_have) {
                    if (attr_item === "") {
                        attr_item = attr_value + "," + attr_value_name + "," + attr_value_id;
                    } else {
                        attr_item += ";" + attr_value + "," + attr_value_name + "," + attr_value_id;
                    }
                } else {
                    arrayChangeString(attr_array);
                }
            }
        }
        //移除某个属性条件
        function removeAttr(event) {
            var url_parameter = $("#hidden_url_parameter").val();
            var attr_value = event.data("attr-value");
            var attr_value_name = event.data("attr-value-name");
            var attr_value_id = event.data("attr-value-id");
            judgeAttrIsHaveData(attr_value, attr_value_name, false, attr_value_id);
            var url = SHOPMAIN + "/goods/goodslist?" + url_parameter;
            if ($.trim(attr_item) !== "" && $.trim(attr_item) !== undefined) {
                url += "&attr=" + attr_item;
            }
            if ($.trim(spec_item) !== "" && $.trim(spec_item) !== undefined) {
                url += "&spec=" + spec_item;
            }
            location.href = __URL(url);
        }
        //属性值字符串转数组
        function arrayChangeString(array) {
            var temp_array = new Array();
            $.each(array, function (k, v) {
                temp_array.push(v.join(","));
            });
            attr_item = temp_array.join(";");
        }
        /**
         * 删除数组中的指定元素
         * @param arr
         * @param val
         */
        function SpliceArrayItem(arr, val) {
            for (var i = 0; i < arr.length; i++) {
                if (arr[i] == val) {
                    arr.splice(i, 1);
                    break;
                }
            }
        }
    };
    return goodslist;
});
