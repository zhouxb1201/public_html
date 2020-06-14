define(["jquery", "layer", "distpicker", "common", "dialog"], function ($, layer, dist, common, dialog) {
    var Goods = {};
    var province_list = null;//省
    var city_list = null;//市
    var district_list = null;//区县
    var goods_id = $('#goods_id').val();
    var select_specifications = false;
    var min_buy = parseInt($(".amount-input").attr("data-min"));
    // 商品图插件
    Goods.detailsImg = function () {
        require(["ljsGlasses"], function (ljsGlasses) {
            // $(document).ready(function () {
                var showproduct = {
                    boxid: "showbox",
                    sumid: "showsum",
                    boxw: 400, //宽度,该版本中请把宽高填写成一样
                    boxh: 400, //高度,该版本中请把宽高填写成一样
                    sumw: 60, //列表每个宽度,该版本中请把宽高填写成一样
                    sumh: 60, //列表每个高度,该版本中请把宽高填写成一样
                    sumi: 7, //列表间隔
                    sums: 5, //列表显示个数
                    sumsel: "sel",
                    sumborder: 1, //列表边框，没有边框填写0，边框在css中修改
                    lastid: "showlast",
                    nextid: "shownext"
                }; //参数定义
                $.ljsGlasses.pcGlasses(showproduct); //方法调用，务必在加载完后执行
            // });
        });
    };
    // 商品详情页购买数量限制
    Goods.limitNum = function () {
        var min_buy = parseInt($(".amount-input").attr("data-min"));
        $("#num").keyup(function () {
            var obj = $(this);
            var r = /^\d+$/;
            if (!r.test(obj.val())) {
                if (min_buy > 0) {
                    obj.val(min_buy);
                } else {
                    obj.val(1);
                }
                layer.msg("请输入数字");
            } else {
                if (parseInt(obj.val()) > parseInt(obj.attr("data-max"))) {
                    obj.val(obj.attr("data-max"));
                }
                //购买数不能小于最小购买数
                if (parseInt(obj.val()) < min_buy) {
                    obj.val(min_buy);
                }
                if (parseInt(obj.val()) <= 0) {
                    if (min_buy > 0) {
                        obj.val(min_buy);
                    } else {
                        obj.val(1);
                    }
                }
            }
            // calculated_price(obj.val());
        });

        $(".amount-plus").click(function () {
            var obj = $("#num");
            var num = parseInt(obj.val());
            var max = parseInt(obj.attr("data-max"));
            var max_buy = parseInt($("#hidden_max_buy").val());
            num++;
            if (num > max)
                obj.val(max);
            else
                obj.val(num);
            Goods.setRegion();
        });

        $(".amount-minus").click(function () {
            var obj = $("#num");
            var num = parseInt(obj.val());
            if (num > 1) {
                num--;
                if (num < min_buy) {
                    $.msg("本商品最小购买数" + min_buy);
                    obj.val(min_buy);
                } else {
                    obj.val(num);
                }
            }
            Goods.setRegion();
            // calculated_price(obj.val());
        });
    };
    // 顾客评论图插件
    Goods.commentImgs = function () {
        require(["commentImg"], function () {
            $(function () {
                $("#comment_content .tm-m-photos").commentImg({
                    activeClass: "tm-current", //缩略图当前状态class,默认'current'
                    nextButton: ".tm-m-photo-viewer-navright", //向后翻页按钮，默认'.next'
                    prevButton: ".tm-m-photo-viewer-navleft", //向前翻页按钮，默认'.prev'
                    imgNavBox: ".tm-m-photos-thumb", //缩略图容器，默认'.photos-thumb'
                    imgViewBox: ".tm-m-photo-viewer" //浏览图容器，默认'.photo-viewer'
                });
            });

        });
    };
    //加载装修内容
    Goods.addCustomContent = function () {
        var detailbanner = $('.J-page').find("*[data-area='J-detailbanner']").html();
        var detail = $('.J-page').find("*[data-area='J-detail']").html();
        $('#tab1').prepend(detailbanner);
        $('.J-goodsBottom').html(detail);
    };
    //定位查询运费
    Goods.locationShippingFee = function () {
        var goods_sku_list = $("#goods_sku0").attr("skuid") + ":1";
        //定位查询运费
        $.ajax({
            type: "post",
            data: {"goods_id": goods_id, goods_sku_list: goods_sku_list},
            url: __URL(SHOPMAIN + '/goods/getShippingFeeNameByLocation'),
            success: function (data) {
                if (data.user_location != null) {
                    $(".js-region").html("<font>" + data.user_location.province + data.user_location.city + "<i></i></font>").show();
                    $(".region-tab[data-region-level=1]").html(data.user_location.province + "<i></i>");
                    $(".region-tab[data-region-level=2]").html(data.user_location.city + "<i></i>");
                }
                var html = "";
                if (data.express !== null && data.express !== "") {
                    html = data.express;
                } else {
                    html = "";
                }
                $(".js-shipping-name").html('运费：￥'+html);
            }
        });
    };
    //
    Goods.setRegion = function () {
            var region = "";
            var province = "";
            var city = "";
            var district = "";
            $(".region-tab").each(function () {
                region += $(this).text();
                if ($(this).attr("data-province-id") != null) {
                    province = $(this).attr("data-province-id");
                }
                if ($(this).attr("data-city-id") != null) {
                    city = $(this).attr("data-city-id");
                }
                if ($(this).attr("data-district-id") != null) {
                    district = $(this).attr("data-district-id");
                }

            });
            region = region.replace("请选择区/县", "");
            $(".js-region").text(region);
            if (province != "") {
                $(".js-region").attr("data-province", province);
            }
            if (city != "") {
                $(".js-region").attr("data-city", city);
            }
            if (district != "") {
                $(".js-region").attr("data-district", district);
            }
            //根据地区id，查询物流公司及运费
            var provice_id = $(".js-region").attr('data-province');
            var city_id = $(".js-region").attr('data-city');
            var disctrict_id = $(".js-region").attr("data-district");
            var count = $("#num").val();
            var goods_sku_list = $("#hidden_skuid").val() + ":1";
            $.ajax({
                url: __URL(SHOPMAIN + "/goods/selcectexpress"),
                type: "post",
                data: {"goods_id": goods_id, "provice_id": provice_id, "city_id": city_id, "disctrict_id": disctrict_id, goods_sku_list: goods_sku_list,count:count},
                success: function (data) {
                    if(data){
                        $(".post-age-info").html('运费：￥'+data);
                    }
                }
            });

        }
    // 商品详情页操作
    Goods.goodsOperation = function () {
        
        Goods.getGuessYouLike();
        countDown();

        function countDown(){
            $(".settime").each(function(i) {
                    var self = $(this);
                    var end_date = self.attr("endtime"); //结束时间字符串
                    if(end_date != undefined && end_date != ''){
                            var end_time = new Date(end_date.replace(/-/g,'/')).getTime();//月份是实际月份-1
                            var sys_second = (end_time-$("#ms_time").val())/1000;
                            if(sys_second>1){
                                    sys_second -= 1;
                                    var day = Math.floor((sys_second / 3600) / 24);
                                    var hour = Math.floor((sys_second / 3600) % 24);
                                    var minute = Math.floor((sys_second / 60) % 60);
                                    var second = Math.floor(sys_second % 60);
                                    $(".js-day").html(day);
                                    $(".js-hour").html(hour<10 ? "0" + hour : hour);
                                    $(".js-min").html(minute<10? "0" + minute : minute);
                                    $(".js-sec").html(second<10? "0" + second : second);
                            }
                            var timer = setInterval(function(){
                                    if (sys_second > 1) {
                                            sys_second -= 1;
                                            var day = Math.floor((sys_second / 3600) / 24);
                                            var hour = Math.floor((sys_second / 3600) % 24);
                                            var minute = Math.floor((sys_second / 60) % 60);
                                            var second = Math.floor(sys_second % 60);
                                            $(".js-day").html(day);
                                            $(".js-hour").html(hour<10 ? "0" + hour : hour);
                                            $(".js-min").html(minute<10? "0" + minute : minute);
                                            $(".js-sec").html(second<10? "0" + second : second);
                                    } else {
                                            $(".promotion-time").html("活动结束！");
                                            clearInterval(timer);
                                    }
                            }, 1000);
                    }
            });
    }
        // 添加收藏店铺
        $(".collect-shop").on('click', function () {
            var is_member_fav_shop = $('#is_member_fav_shop').val();
            var target = $(this);
            var fav_id = $('#store_shop_id').val();
            if (dialog.isLogin(1))
            {
                if (is_member_fav_shop == 0)
                {
                    //没有收藏，我要收藏
                    $.ajax({
                        url: __URL(SHOPMAIN + '/components/collectiongoodsorshop'),
                        type: "post",
                        data: {"fav_id": fav_id, "fav_type": "shop", "log_msg": ""},
                        success: function (data) {
                            if (data.code > 0)
                            {
                                $('body').find('.J-collectShop').find('.icon').removeClass('icon-collectioned').addClass('icon-collection');
                                $('body').find('.J-collectShop').removeClass('J-collectShop').addClass('J-cancelCollectShop');
                                $('body').find('.J-collectWord').text('取消收藏');
                                $(target).html("取消收藏");
                                $('#is_member_fav_shop').val('1');
                            }
                        }
                    });
                } else {
                    $.ajax({
                        url: __URL(SHOPMAIN + '/components/cancelcollgoodsorshop'),
                        type: "post",
                        data: {"fav_id": fav_id, "fav_type": "shop"},
                        success: function (data) {
                            if (data.code > 0)
                            {
                                $('body').find('.J-cancelCollectShop').find('.icon').removeClass('icon-collection').addClass('icon-collectioned');
                                $('body').find('.J-cancelCollectShop').removeClass('J-cancelCollectShop').addClass('J-collectShop');
                                $(target).html("收藏店铺");
                                $('body').find('.J-collectWord').text('收藏店铺');
                                $('#is_member_fav_shop').val('0');
                            }
                        }
                    });
                }
            } else {
                dialog.loginMember("会员登录", ".login-dialog");
            }
        });
        $('.J-shareGoods').click(function(){
            $('.bdsharebuttonbox').show();
        })
        // 添加
//        
        // 添加收藏商品
        $(".collect-goods").on('click', function () {
            var is_member_fav_goods = $('#is_member_fav_goods').val();
            var num = $(this).attr("data-collects");
            var obj = $(this);
            if (dialog.isLogin(1)) {
                if (is_member_fav_goods == 0) {
                    //点击收藏
                    $.ajax({
                        url: __URL(SHOPMAIN + '/components/collectiongoodsorshop'),
                        type: "post",
                        data: {"fav_id": goods_id, "fav_type": "goods", "log_msg": ""},
                        success: function (data) {
                            if (data.code > 0) {
                                $('#is_member_fav_goods').val('1');
                                num++;
                                obj.attr("data-collects", num);
                                obj.html("<i class='icon-collectioned'></i>取消收藏 (" + num + "人气)");
                            } else {
                                layer.msg("商品已经收藏过了");
                            }
                        }
                    });
                } else if (is_member_fav_goods == 1) {
                    //取消收藏
                    $.ajax({
                        url: __URL(SHOPMAIN + '/components/cancelcollgoodsorShop'),
                        type: "post",
                        data: {"fav_id": goods_id, "fav_type": "goods", "log_msg": ""},
                        success: function (data) {
                            if (data.code > 0) {
                                num--;
                                obj.attr("data-collects", num);
                                obj.html("<i class='icon-collection'></i>收藏商品 (" + num + "人气)");
                                $('#is_member_fav_goods').val('0');
                            } else {
                                layer.msg("商品已经取消收藏了");
                            }
                        }
                    });
                }
            } else {
                dialog.loginMember("会员登录", ".login-dialog");
            }
        });
        $(".comment-type li").on('click', function () {
            var target = $(this);
            $(".comment-type").find("li").removeClass("current");
            $(target).addClass("current");
            GetDataList(1);
        });
        $(".J-comment").on('click', function () {
            GetDataList(1);
        });
        $(".right-con-content-ul li").on('click', function () {
            $(this).addClass('active').siblings().removeClass('active');
            var index = $(this).index();
            $('.right-con-content-shop-ul').hide();
            $('.right-con-content-shop').find("ul:eq(" + index + ")").show();
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
        $(".J-coupon").on('click', function () {
            //领取优惠劵
            if (dialog.isLogin(1)) {
                // var data_at_least = $(this).data("at-least");
                var coupon_type_id = $(this).data('id');
                // var data_money = $(this).data("money");
                var data_start_time = $(this).data("start-time");
                var data_end_time = $(this).data("end-time");
                // var data_max_fetch = parseInt($(this).data("max-fetch"));
                // var data_receive_quantity = parseInt($(this).data("receive-quantity"));
                // if (data_max_fetch != 0 && data_receive_quantity >= data_max_fetch) {
                //     layer.open({
                //         type: 1,
                //         skin: 'layui-layer-rim', //加上边框
                //         area: ['420px', '200px'], //宽高
                //         title: "领取优惠劵",
                //         content: '<div class="tip-info"><div class="left"><i class="receiveTip"></i></div><div class="right"><p class="coupon_desc">您的领取已达到上限，去看看其他的劵吧！</p><p class="my_coupon">查看已领取优惠券：<a href="' + couponCentreUrl + '" target="_blank">我的优惠信息</a></p></div></div>'
                //     });
                //     return false;
                // }
                $.ajax({
                    url: fetchCouponTypeUrl,
                    type: "post",
                    data: {"coupon_type_id": coupon_type_id, 'get_type': 5},
                    success: function (res) {
                        if (res['code'] > 0) {
                            // $(".coupon" + coupon_type_id).attr("data-receive-quantity", data_receive_quantity + 1);
                            var html='';
                            html +='<div class="tip-info clearfix"><div class="left"><i class="icon icon-success"></i></div>';
                            html +='<div class="right"><h4>领券成功</h4><p class="coupon_desc">恭喜，您已成功领取优惠劵</p><p class="use_time">使用时间：' + data_start_time + '-' + data_end_time + '</p><p class="my_coupon">查看已领优惠劵：<a href="' + couponCentreUrl + '"  target="_blank" class="blue">我的优惠信息</a></p></div></div>'
                            layer.open({
                                type: 1,
                                skin: 'layui-layer-rim', //加上边框
                                area: ['420px', '200px'], //宽高
                                title: "领取优惠劵",
                                content:html
                                // content: '<div class="tip-info"><div class="left"><i></i></div><div class="right"><p class="coupon_desc">恭喜，您已成功领取优惠劵</p><p class="use_time">使用时间：' + data_start_time + '-' + data_end_time + '</p><p class="my_coupon">查看已领优惠劵：<a href="' + couponCentreUrl + '"  target="_blank">我的优惠信息</a></p></div></div>'
                            });
                        } else if (res['code'] == -2011) {
                            var html='';
                            html +='<div class="tip-info clearfix"><div class="left"><i class="icon icon-danger"></i></div>';
                            html +='<div class="right"><h4></h4><p class="coupon_desc">来迟了,已经领完了，去看看其他的劵吧</p><p class="my_coupon">查看已领优惠劵：<a href="' + couponCentreUrl + '"  target="_blank" class="blue">我的优惠信息</a></p></div></div>'
                            layer.open({
                                type: 1,
                                skin: 'layui-layer-rim', //加上边框
                                area: ['420px', '200px'], //宽高
                                title: "领取优惠劵",
                                content:html,
                                // content: '<div class="tip-info"><div class="left"><i class="receiveTip"></i></div><div class="right"><p class="coupon_desc">来迟了,已经领完了，去看看其他的劵吧</p><p class="my_coupon">查看已领优惠劵：<a href="' + couponCentreUrl + '" target="_blank">我的优惠信息</a></p></div></div>'
                            });
                        } else {
                            layer.msg(res['message']);
                        }
                    }
                })
            }else {
                dialog.loginMember("会员登录", ".login-dialog");
            }
        });
        //规格选择
        $(".goods-spec-item").on('click', function () {
            if ($("#hidden_shipping_fee_name").val() !== "无货") {
                $(".goods-spec-item").removeAttr("data-last");
                $(this).attr("data-last", 1);
                $(this).siblings(".selected").removeClass("selected").find("i").remove();
                $(this).addClass("selected");
                if ($(this).find("a i").length == 0) {
                    $(this).find("a").append("<i></i>");
                }
                var this_sku_vue = $(this).find("a").attr("id");
                var sku_vue = subSkuVue(this_sku_vue);
                showSkuPicture(sku_vue);
                Goods.initSku();
                //规格图片
                var show_big_pic = $(this).find("span").attr("data-show-big-pic");
                if (show_big_pic != undefined && show_big_pic != "") {
                    $("#showbox b").find('img').attr("src", show_big_pic);
                    $("#showbox p").find('img').attr("src", show_big_pic);
                    $("#showsum span").removeClass("sel");
                } else {
                    //如果点击了文本，则默认选中第一个
                    $("#showbox b").find('img').attr("src", $("#showsum span img").not(":hidden").eq(0).attr("data_big_img"));

                    $("#showsum span").not(":hidden").eq(0).addClass("on");
                }
            }
        });
        //根据sku_id显示相册中的图片
        function showSkuPicture(sku_id) {
            if ($(".spec-items ul li#sku_pic_" + sku_id).length > 0) {
                $(".spec-items ul li").hide();
                $(".spec-items ul li#sku_pic_" + sku_id).show();
                var firstPicUrl = $(".spec-items ul li img").not(":hidden").eq(0).attr("data_big_img");
                var picture_id = $(".spec-items ul li img").not(":hidden").eq(0).attr("data-picture-id");
                $(".spec-items ul li").not(":hidden").removeClass("on");
                $(".spec-items ul li").not(":hidden").eq(0).addClass("on");
                $(".MagTargetImg").attr("src", firstPicUrl);
                $(".MagTargetImg").attr("data_big_img", firstPicUrl);
                $("#hidden_default_img_id").val(picture_id);
            }
        }
        //截取sku属性值
        function subSkuVue(goods_sku_id) {
            var num = goods_sku_id.indexOf(":") + 1;
            var sku_vue_id = goods_sku_id.substr(num);
            return sku_vue_id;
        }
        function GetDataList(pageindex) {
            var page_size = $('#page_size').val();
            var page_index = pageindex;
            var commentsType = $(".comment-type li.current").attr('data-type');
            $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + '/goods/getgoodscomments'),
                data: {'page_index': page_index, 'page_size': page_size, 'goods_id': goods_id, 'comments_type': commentsType},
                dataType: 'json',
                success: function (data) {
                    var listhtml = '';
                    if (data['data'].length == 0) {
                        $('#comment_content .comment-list').html('<div class="tip-box" style="position:static;"><i class="tip-icon"></i><div class="tip-text">暂无评论</div></div>');
                        return false;
                    }
                    for (var i = 0; i < data['data'].length; i++) {
                        var dataitem = data['data'][i];
                        var member_name = dataitem['member_name'];
                        member_name = dataitem['is_anonymous'] == 1 ? member_name.replace(member_name.substring(1, member_name.length), '***') + '(匿名)' : member_name;
                        listhtml += '<div class="comment-item"><div class="topic-avatar fl"><div class="avatar">';
                        if (dataitem["user_img"] != "" && dataitem['user_img'] != undefined) {
                            listhtml += '<img class="back" src="' + __IMG(dataitem['user_img']) + '" width="40" height="40">';
                        } else {
                            listhtml += '<img class="back" src="http://iph.href.lu/40x40">';
                        }
                        listhtml += '</div><div class="username"><span>' + member_name + '</span></div></div>';
                        listhtml += '<div class="topic-main fl"><div class="topic-body"><p class="body-content">' + dataitem['content'] + '</p>';
                        if (dataitem['image'] != '') {
                            var imgs_arr = dataitem['image'].split(',');
                            listhtml += '<div class="body-images"><div class="tm-m-photos"><ul class="tm-m-photos-thumb">';
                            for (var key in imgs_arr) {
                                listhtml += '<li data-src="' + __IMG(imgs_arr[key]) + '"> <img src="' + __IMG(imgs_arr[key]) + '"> <b class="tm-photos-arrow"></b></li>';
                            }
                            listhtml += '</ul>';
                            listhtml += '<div class="tm-m-photo-viewer">';
                            listhtml += '<img src="">';
                            listhtml += '<a class="tm-m-photo-viewer-navleft"><i></i></a>';
                            listhtml += '<a class="tm-m-photo-viewer-navright"><i></i></a>';
                            listhtml += '</div>';
                            listhtml += '</div>';
                            listhtml += '</div>';
                        }
                        listhtml += '<div class="body-info">';
                        listhtml += '<span>' + common.timeStampTurnTime(dataitem['addtime']) + '</span>';
                        listhtml += '</div>';

                        listhtml += '</div>';
                        if (dataitem['explain_first'] != '') {
                            listhtml += '<div class="topic-body">';
                            listhtml += '<p class="body-top">[店家回复]</p>';
                            listhtml += '<p class="body-content">' + dataitem['explain_first'] + '</p>';
                            listhtml += '<div class="body-info">';
                            listhtml += '<span>' + common.timeStampTurnTime(dataitem['explain_time']) + '</span>';
                            listhtml += '</div>';
                            listhtml += ' </div>';
                        }
                        if (dataitem['again_content'] != '') {
                            listhtml += '<div class="topic-body">';
                            listhtml += '<p class="body-top">[追加评价]</p>';
                            listhtml += '<p class="body-content">' + dataitem['again_content'] + '</p>';
                            if (dataitem['again_image'] != '') {
                                var imgs_arr = dataitem['again_image'].split(',');
                                listhtml += '<div class="body-images"><div class="tm-m-photos"><ul class="tm-m-photos-thumb">';
                                for (var key in imgs_arr) {
                                    listhtml += '<li data-src="' + __IMG(imgs_arr[key]) + '"> <img src="' + __IMG(imgs_arr[key]) + '"> <b class="tm-photos-arrow"></b></li>';
                                }
                                listhtml += '</ul>';
                                listhtml += '<div class="tm-m-photo-viewer">';
                                listhtml += '<img src="">';
                                listhtml += '<a class="tm-m-photo-viewer-navleft"><i></i></a>';
                                listhtml += '<a class="tm-m-photo-viewer-navright"><i></i></a>';
                                listhtml += '</div>';
                                listhtml += '</div>';
                                listhtml += '</div>';
                            }
                            listhtml += '<div class="body-info">';
                            listhtml += '<span>' + common.timeStampTurnTime(dataitem['again_addtime']) + '</span>';
                            listhtml += '</div>';
                            listhtml += ' </div>';
                            if (dataitem['again_explain'] != '') {
                                listhtml += '<div class="topic-body">';
                                listhtml += '<p class="body-top">[再次回复]</p>';
                                listhtml += '<p class="body-content">' + dataitem['again_explain'] + '</p>';
                                listhtml += '<div class="body-info">';
                                listhtml += '<span>' + common.timeStampTurnTime(dataitem['again_explain_time']) + '</span>';
                                listhtml += '</div>';
                                listhtml += ' </div>';
                            }
                        }
                        listhtml += '</div>';
                        listhtml += '</div>';

                    }
                    $('#comment_content .comment-list').html(listhtml);
                    Goods.commentImgs();
                    page(".M-box3", data['total_count'], data["page_count"], page_index, GetDataList);
                }
            });
        }
        function page(select, totalData, pageCount, current, callbacks) {
            $(select).pagination({
                totalData: totalData,
                pageCount: pageCount,
                current: current,
                jump: true,
                coping: true,
                homePage: "首页",
                endPage: "末页",
                prevContent: "上页",
                nextContent: "下页",
                callback: function (api) {
                    callbacks && callbacks(api.getCurrent());
                }
            });
        }
        $(".J-more").click(function(){
            $(".J-couponList").toggle();
        });

    };
    // 商品详情页地址选择
    Goods.goodsAddress = function () {
        getAddress();
        function getAddress(){
            setProvince();
            setCity();
            
        }
        function setProvince(){
            $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + "/goods/getprovince"),
                async: true,
                success: function (data) {
                    if (data != null) {
                        province_list = data;
                    }
                }
            });
        }
        function setCity(){
            $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + "/goods/getcity"),
                async: true,
                success: function (data) {
                    if (data != null) {
                        city_list = data;
                    }
                }
            });
        }
        //鼠标经过、离开地区文字，显示、隐藏地区选择层，点击关闭按钮隐藏地区选择层
        var city_time = null;
        $('.region,.region-chooser-box').mouseover(function () {
            if($(".region-items[data-region-level='1']").html()===''){
                initAddress();
            }
            clearTimeout(city_time);
            $('.region-chooser-box').show();
            $('.region').addClass('active');
        });
        $('.region,.region-chooser-box').mouseout(function () {
            city_time = setTimeout(function () {
                $('.region-chooser-box').hide();
                $('.region').removeClass('active');
            }, 200);
        });
        $('.region-chooser-close').click(function () {
            $('.region-chooser-box').hide();
            $('.region').removeClass('active');
        });

        $(".region-tab").click(function () {
            $(".region-items[data-region-level='1']").hide();
            $(".region-items[data-region-level='2']").hide();
            $(".region-items[data-region-level='3']").hide();
            $(".region-tab").removeClass("selected");
            $(this).addClass("selected");
            switch (parseInt($(this).attr("data-region-level"))) {
                case 1:
                    //省
                    $(".region-items[data-region-level='1']").show();
                    break;
                case 2:
                    //市
                    $(".region-items[data-region-level='2']").show();
                    break;
                case 3:
                    //区县
                    $(".region-items[data-region-level='3']").show();
                    break;
            }
        });
        
        
        // 加载省市县
        function initAddress() {
            var str_province = "";
            if(province_list){
                for (var i = 0; i < province_list.length; i++) {
                    str_province += '<a href="javascript:;" data-region-level="1" class="J-province"';
                    str_province += ' data-region-province-id=' + province_list[i].province_id;
                    str_province += ' data-region-name="' + province_list[i].province_name + '">' + province_list[i].province_name + '</a>';
                    if ($("#hidden_province").val() == province_list[i].province_name) {
                        $("#hidden_province").attr("data-province-id", province_list[i].province_id);
                        $(".js-region").attr("data-province", province_list[i].province_id);
                    }
                }
            }
            $(".region-items[data-region-level='1']").html(str_province);
            $(".region-items[data-region-level='2']").html(getCity($("#hidden_province").attr("data-province-id")));
            $(".region-items[data-region-level='3']").html(getDistrict($("#hidden_city").attr("data-city-id")));
        }

        //获取市
        function getCity(province_id) {
            var str_city = "";
            if(city_list){
                for (var j = 0; j < city_list.length; j++) {
                    if (city_list[j].province_id == province_id) {
                        str_city += '<a href="javascript:;" data-region-level="2" class="J-city"';
                        str_city += ' data-region-city-id=' + city_list[j].city_id;
                        str_city += ' data-region-name="' + city_list[j].city_name + '">' + city_list[j].city_name + '</a>';
                        //第一次定位设置城市的Id，为了下一级
                        if ($("#hidden_city").val() == city_list[j].city_name) {
                            $("#hidden_city").attr("data-city-id", city_list[j].city_id);
                            $(".js-region").attr("data-city", city_list[j].city_id);
                        }
                    }
                }
            }
            return str_city;
        }

        //选择区县
        function getDistrict(city_id) {
            $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + "/goods/getdistrict"),
                data: {city_id : city_id},
                async: false,
                success: function (data) {
                    if (data != null) {
                        district_list = data;
                    }
                }
            });
            var str_district = "";
            var count = 0;
            for (var k = 0; k < district_list.length; k++) {
                if (district_list[k].city_id == city_id) {
                    str_district += '<a href="javascript:;" data-region-level="3" class="J-district"';
                    str_district += ' data-region-district-id=' + district_list[k].district_id;
                    str_district += ' data-region-name="' + district_list[k].district_name + '">' + district_list[k].district_name + '</a>';
                    count++;
                }
            }

            if (count != 0) {
                $(".region-items[data-region-level='1']").hide();
                $(".region-items[data-region-level='2']").hide();
                $(".region-items[data-region-level='3']").show();
                $(".region-tab[data-region-level='3']").show();
                $(".region-tab").removeClass("selected");
                $(".region-tab[data-region-level='3']").addClass("selected");
            }
            return str_district;
        }


        function clearAddressId() {
            $(".region-tab[data-region-level='1']").attr("data-province-id", "");
            $(".region-tab[data-region-level='2']").attr("data-city-id", "");
            $(".region-tab[data-region-level='3']").attr("data-district-id", "");
        }

        //选择省
        $('body').on('click', '.J-province', function () {
            $(".region-items[data-region-level='1']").hide();
            $(".region-items[data-region-level='2']").show();
            $(".region-items[data-region-level='3']").hide();

            $(".region-tab[data-region-level='1']").html($(this).text() + "<i></i>");
            $(".region-tab[data-region-level='1']").attr("data-province-id", $(this).attr("data-region-province-id"));
            $(".js-region").attr("data-province", $(this).attr("data-region-province-id"));

            $(".region-tab").removeClass("selected");
            $(".region-tab[data-region-level='2']").addClass("selected");

            $(".region-items[data-region-level='2']").html(getCity($(this).attr("data-region-province-id")));//该省下的市
            $(".region-tab[data-region-level='2']").html("请选择市<i></i>");
            $(".region-tab[data-region-level='3']").html("请选择区/县<i></i>");
            $(".region-items[data-region-level='3']").html("");
        })
        //选择市
        $('body').on('click', '.J-city', function () {
            $(".region-items[data-region-level='1']").hide();
            $(".region-items[data-region-level='2']").hide();
            $(".region-items[data-region-level='3']").show();

            $(".region-tab[data-region-level='2']").html($(this).text() + "<i></i>");
            $(".region-tab[data-region-level='2']").attr("data-city-id", $(this).attr("data-region-city-id"));
            $(".region-tab").removeClass("selected");
            var html = getDistrict($(this).attr("data-region-city-id"));
            if (html != "") {//是否有区县分类
                $(".region-items[data-region-level='3']").html(html);//该省下的区县
                $(".region-tab[data-region-level='3']").show();
                $(".region-tab[data-region-level='3']").addClass("selected");
            } else {
                $(".region-items[data-region-level='3']").hide();
                $(".region-items[data-region-level='2']").show();
                $(".region-tab[data-region-level='3']").hide();
                $(".region-tab[data-region-level='2']").addClass("selected");
                $(".region-tab[data-region-level='3']").attr("data-district-id", 0);
                Goods.setRegion();
            }
        });
        //选择区
        $('body').on('click', '.J-district', function () {
            $(".region-tab[data-region-level='3']").html($(this).text() + "<i></i>");
            $(".region-tab[data-region-level='3']").attr("data-district-id", $(this).attr("data-region-district-id"));
            Goods.setRegion();
        });
        
    };
    //加载规格
    Goods.initSku = function () {
        var curr_sku = '';
        var sku_arr = $(".goods-spec-item");
        sku_arr.each(function (i) {
            var $this = $(this);
            if ($this.hasClass("selected")) {
                curr_sku += $this.find("a").attr("id") + ";";
            }
        });
        if(curr_sku===''){
            return;
        }
        for (var i = 0; i < parseInt($("#goods_sku_count").val()); i++) {

            var sku_id = "#goods_sku" + i;
            var goods_sku_id = $(sku_id).val();
            //修改匹配规则，不能直接等值判断。判断值是否存在即可
            var temp_curr_sku_array = curr_sku.split(";");
            var temp_goods_sku_id_array = goods_sku_id.split(";");
            var sku_count = 0;
            var curr_sku_count = 0;
            //匹配当前选中的SKU规格和商品SKU规格，检测是否都存在
            for (var j = 0; j < temp_curr_sku_array.length; j++) {
                sku_count++;
                if ($.inArray(temp_curr_sku_array[j], temp_goods_sku_id_array) != -1)
                    curr_sku_count++;
            }
            if (curr_sku_count == sku_count) {
                var select_skuid = $(sku_id).attr("skuid");
                var select_skuName = $(sku_id).attr("skuname");
                var select_stock = $(sku_id).attr("stock");//sku商品库存
                if(select_stock==='0'){
                    continue;
                }
                var original_price = parseFloat($(sku_id).attr("original_price")); //sku商品原价
                if (select_stock == 0) {
                    $(".js-buy-now").addClass("disabled");
                    $(".add-cart").addClass("disabled");
                } else {
                    //当最小购买数大于总库存时,不可购买
                    if (min_buy > select_stock) {
                        $(".js-buy-now").addClass("disabled");
                        $(".add-cart").addClass("disabled");
                    } else {
                        $(".js-buy-now").removeClass("disabled");
                        $(".add-cart").removeClass("disabled");
                    }
                }
                $("#hidden_skuid").val(select_skuid);
                $("#hidden_skuname").val(select_skuName);
                var price = parseFloat($(sku_id).attr("price"));
                $(".J-price").text("￥" + price.toFixed(2));
                $(".J-marketprice").text("￥" + original_price.toFixed(2));
                $("#hidden_sku_price").val(price);
                $(".js-goods-number").text("库存:" + select_stock + "件");
                if ($("#hidden_max_buy").val() == 0)
                {
                    $(".amount-input").attr("data-max", select_stock);
                } else {
                    $(".amount-input").attr("data-max", $("#hidden_max_buy").val());
                }
                //最小购买数为0时,购买数默认为1
                if (min_buy > 0) {
                    $(".amount-input").val(min_buy);
                } else {
                    $(".amount-input").val(1);
                }

                // 当只有一个sku时，不用验证
                if (parseInt($("#attribute_list").val()) == 1) {
                    select_specifications = true;//是否选择过规格,来源与shopping_cart.js
                }
                break;
            }
        }
    };
    Goods.getGuessYouLike = function(){
        $.ajax({
                url: __URL(SHOPMAIN + "/goods/getguessmemberlikes"),
                type: "POST",
                success: function (res) {
                    var str = "";
                    if (res.length > 0) {
                        for (var i = 0; i < res.length; i++) {
                            if (i >= 6) {
                                str += '<li style="display:none;">';
                            } else {
                                str += '<li>';
                            }
                            str += '<div class="p-img">';
                            str += '<a target="_blank" title="' + res[i].goods_name + '" href="' + __URL(SHOPMAIN + '/goods/goodsinfo?goodsid=' + res[i].goods_id) + '">';
                            str += '<img src="' + __IMG(res[i].picture_info.pic_cover) + '" class="lazy_load">';
                            str += '</a>';
                            str += '</div>';
                            str += '<div class="p-name">';
                            str += '<a target="_blank" title="' + res[i].goods_name + '" href="' + __URL(SHOPMAIN + '/goods/goodsinfo?goodsid=' + res[i].goods_id) + '">' + res[i].goods_name + '</a>';
                            str += '</div>';
                            str += '<div class="p-comm">';
                            str += '<span class="p-price second-color red">￥' + res[i].price + '</span>';
                            str += '</div>';
                            str += '</li>';
                        }
                    } else {
                        str += '暂无推荐！';
                    }
                    $("#user_like").html(str);
                }
            });
    };
    Goods.change_like = function(){
        var p_count = 6;
            var li_len = $('#user_like>li').length;
            if (li_len > p_count) {
                if (!$('#user_like>li:eq(' + (p_count - 1) + ')').is(':hidden') && li_len >= p_count) {
                    $('#user_like>li:gt(' + (p_count - 1) + '):lt(' + (p_count) + ')').show();
                    $('#user_like>li:lt(' + (p_count) + ')').hide();
                } else if (!$('#user_like>li:eq(' + (p_count * 2 - 1) + ')').is(':hidden') && li_len >= (p_count * 2)) {
                    $('#user_like>li:gt(' + (p_count * 2 - 1) + ')').show();
                    $('#user_like>li:lt(' + (p_count * 2) + ')').hide();
                } else if (!$('#user_like>li:eq(' + (p_count * 3 - 1) + ')').is(':hidden') || li_len >= p_count) {
                    $('#user_like>li:lt(' + (p_count) + ')').show();
                    $('#user_like>li:gt(' + (p_count - 1) + ')').hide();
                }
            }
    };
    Goods.clear_history = function(){
         $.ajax({
                type: "post",
                url: __URL(SHOPMAIN + "/goods/deletememberhistory"),
                async: true,
                dataType: 'json',
                success: function (data) {
                    if (data['code'] > 0) {
                        layer.msg('浏览记录已清除!', {icon: 1, time: 1000}, function () {
                            $("#user_like").html('您已清空最近浏览过的商品');
                        });
                    }

                }
            });
    };
    Goods.getMemberHistory = function(){
        $.ajax({
                url: __URL(SHOPMAIN + "/goods/getmemberhistories"),
                type: "POST",
                success: function (res) {
                    var str = "";
                    if (res.length > 0) {
                        for (var i = 0; i < 6; i++) {
                            if (!res[i]) {
                                continue;
                            }
                            str += '<li>';
                            str += '<div class="p-img">';
                            str += '<a target="_blank" title="' + res[i].goods_name + '" href="' + __URL(SHOPMAIN + '/goods/goodsinfo?goodsid=' + res[i].goods_id) + '">';
                            str += '<img src="' + __IMG(res[i].picture_info.pic_cover) + '" class="lazy_load">';
                            str += '</a>';
                            str += '</div>';
                            str += '<div class="p-name">';
                            str += '<a target="_blank" title="' + res[i].goods_name + '" href="' + __URL(SHOPMAIN + '/goods/goodsinfo?goodsid=' + res[i].goods_id) + '">' + res[i].goods_name + '</a>';
                            str += '</div>';
                            str += '<div class="p-comm">';
                            str += '<span class="p-price second-color red">￥' + res[i].price + '</span>';
                            str += '</div>';
                            str += '</li>';
                        }
                    } else {
                        str += '您还没有浏览历史！';
                    }
                    $("#user_like").html(str);
                }
            });
    };
    Goods.getGoodsDesc = function(){
        var goods_id = $('#goods_id').val();
        $.ajax({
                url: __URL(SHOPMAIN + "/goods/getdescription"),
                type: "POST",
                data: {goods_id : goods_id},
                success: function (res) {
                    $(".J-desc").html(res);
                    Goods.addCustomContent();
                }
            });
    };
    Goods.getQrcodeForGoods = function (goods_id) {
        var url = __URL(SHOPMAIN + "/index/getqrcodeforgoods") + 'goods_id=' + goods_id;
        $('.J-goods-qr').find('img').attr('src', url);
    };
    return Goods;
});
