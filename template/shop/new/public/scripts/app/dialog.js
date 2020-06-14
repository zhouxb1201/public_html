define(["jquery", "layer", "distpicker","fileupload"], function ($, layer, dist, filesa) {
    var Dialog = {};
    //是否选择过规格
    Dialog.cart_id_arr = new Array();//购物车中的商品Id array
    Dialog.cart_num = new Array(); //商品的数量 array
    // 上传头像图片
    Dialog.AvatarUpload = function () {
        require(["cropper", "sitelogo", "html2canvas", "boostrap"], function () {
            //做个下简易的验证  大小 格式
            $("#avatarInput").on("change", function (e) {
                var filemaxsize = 1024 * 5; //5M
                var target = $(e.target);
                var Size = target[0].files[0].size / 1024;
                if (Size > filemaxsize) {
                    layer.msg("图片过大，请重新选择!");
                    $(".avatar-wrapper").childre().remove;
                    return false;
                }
                if (!this.files[0].type.match(/image.*/)) {
                    layer.msg("请选择正确的图片!");
                } else {
                    var filename = document.querySelector("#avatar-name");
                    var texts = document.querySelector("#avatarInput").value;
                    var teststr = texts; //你这里的路径写错了
                    testend = teststr.match(/[^\\]+\.[^\(]+/i); //直接完整文件名的
                    filename.innerHTML = testend;
                }
            });

            $(".avatar-save").on("click", function () {
                var img_lg = document.getElementById("imageHead");
                // 截图小的显示框内的内容
                html2canvas(img_lg, {
                    allowTaint: true,
                    taintTest: false,
                    onrendered: function (canvas) {
                        canvas.id = "mycanvas";
                        //生成base64图片数据
                        var dataUrl = canvas.toDataURL("image/jpeg");
                        var newImg = document.createElement("img");
                        newImg.src = dataUrl;
                        imagesAjax(dataUrl);
                    }
                });
            });

            function imagesAjax(dataUrl) {
                $.ajax({
                    url: __URL(SHOPMAIN + "/member/upLoads"),
                    data: {'data':dataUrl},
                    type: "POST",
                    dataType: "json",
                    success: function (re) {
                        if (re['code'] > 0) {
                            $(".user_heading").attr("src", re['data']);
                            window.location.reload();
                        } else {
                            layer.msg(re['message']);
                        }
                    }
                });
            }
        });
    };
    //  添加地址添加账号弹出框
    Dialog.addAddress = function (element, titles, dialogEle, callback) {
        $('body').on("click",element, function (event) {
            event.stopPropagation();
            layer.open({
                type: 1,
                title: [titles, "font-weight:bold"],
                skin: "layui-layer-rim", //加上边框
                area: ["520px"], //宽高
                content: $(dialogEle),
                btn: ["确定", "取消"],
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
                    // $.ajax({
                    //   url:"http://api.douban.com/v2/movie/top250",
                    //   dataType:"jsonp",
                    //   success:function(data){
                    //     console.log(data);
                    //     console.log(data.title);
                    //     $("#addr-name").val(data.title);
                    //   }
                    // })
                }
            });
        });
    };
    //  登录弹出框
    Dialog.loginMember = function (titles, dialogEle, callback) {
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
                // console.log(layero, index);
                layero.find(".layui-layer-btn").css("text-align", "center");
                // $.ajax({
                //   url:"http://api.douban.com/v2/movie/top250",
                //   dataType:"jsonp",
                //   success:function(data){
                //     console.log(data);
                //     console.log(data.title);
                //     $("#addr-name").val(data.title);
                //   }
                // })
            }
        });
    };
    //  修改地址修改账号弹出框
    Dialog.updateAddress = function (titles, dialogEle, callback,width) {
        if(width==''){
            width = '520px';
        }
        layer.open({
            type: 1,
            title: [titles, "font-weight:bold"],
            skin: "layui-layer-rim", //加上边框
            area: [width], //宽高
            content: $(dialogEle),
            btn: ["确定", "取消"],
            btnAlign: "c",
            // 点击确定的回调
            yes: function (index, layero) {
                if (!callback || callback() !== false) {
                    layer.close(index);
                }
            },
            // 弹出框出现的回调
            success: function (layero, index) {
                // console.log(layero, index);
                layero.find(".layui-layer-btn").css("text-align", "center");
                // $.ajax({
                //   url:"http://api.douban.com/v2/movie/top250",
                //   dataType:"jsonp",
                //   success:function(data){
                //     console.log(data);
                //     console.log(data.title);
                //     $("#addr-name").val(data.title);
                //   }
                // })
            }
        });
    };
    // 删除地址
    Dialog.delAddress = function (delEle, callback) {
        $(delEle).on("click", ".delAddress", function (e) {
            var that = $(this);
            layer.confirm(
                    "确认要删除吗？",
                    {
                        btn: ["确定","取消"],
                        title: "系统提示"
                    },
                    function () {
                        time: 500,
                                layer.msg("删除成功2334", {
                                    time: 500,
                                    offset: ["50%", "50%"]
                                }),
                                e.preventDefault();
                        that
                                .parent()
                                .parent()
                                .remove();
                        callback && callback();
                    }
            );
        });
    };
    // alert提示框
    Dialog.alert = function (contents) {
        layer.open({
        title: '系统提示',
        content:contents,
        });  
    };
    // 选定默认地址
    Dialog.toggleDefault = function () {
        $(".address-item").on("click", ".default", function () {
            $(this)
                    .addClass("selected")
                    .children("a")
                    .text("默认地址");
            $(this)
                    .parent()
                    .siblings()
                    .children(".default")
                    .removeClass("selected")
                    .children("a")
                    .text("设为默认");
        });
    };
    // 取消商品收藏
    Dialog.cancelGoodsColl = function (callback) {
        $(".pic-info").on("click", ".cancel", function () {
            $(this)
                    .parent()
                    .parent()
                    .remove();
            layer.msg("取消成功", {
                time: 500,
                offset: ["50%", "50%"]
            });
        });
    };
    // 取消退货提示框
    Dialog.cancelReturn = function () {
        $(".cancelReturn").on("click", function () {
            layer.confirm(
                    "确认取消退款?",
                    {
                        btn: ["确定", "取消"],
                        title: "取消退款"
                    },
                    function (index) {
                        //do something
                        layer.close(index);
                    }
            );
        });
    };
    // 多级菜单
    Dialog.menus = function() {
//    $("#firstpane .menu_body:eq(0)").show();
    $("#firstpane p.menu_head").click(function() {
      if($(this).hasClass("current")){
        $(this).removeClass("current");
        $(this).nextAll("div.menu_body").eq(0).slideToggle(300).slideUp("slow");
      }
      else{
        $(this).addClass("current").next("div.menu_body").slideToggle(300).siblings("div.menu_body").slideUp("slow");
        $(this).siblings().removeClass("current");
      }

    });
  };
    // 选定提取账号
    Dialog.withAccount = function (element, dom, toggles) {
        $(element).on("click", dom, function () {
            $(this).addClass(toggles).siblings().removeClass(toggles);
        });
    };
    // 星星客户评分
    Dialog.scores = function () {
        require(["starScore"], function () {
            scoreFun($("#startone"), {
                fen_d: 32, //每一个a的宽度
                ScoreGrade: 5 //a的个数5
            });
            scoreFun($("#starttwo"), {
                fen_d: 32, //每一个a的宽度
                ScoreGrade: 5 //a的个数5
            });
            scoreFun($("#startthree"), {
                fen_d: 32, //每一个a的宽度
                ScoreGrade: 5 //a的个数5
            });
            scoreFun($("#startfour"), {
                fen_d: 32, //每一个a的宽度
                ScoreGrade: 5 //a的个数5
            });
        });
    };
    // 星星评分展示
    Dialog.scoreShow = function () {
        require(["starScore"], function () {
            //显示分数
            $(".show_number li p").each(function (index, element) {
                var num = $(this).attr("tip");
                var www = num * 2 * 16;
                $(this).css("width", www);
                $(this).parent(".atar_Show").siblings("span").text(num + "分");
            });
        });
    };
    // swiper轮播图
    Dialog.lbt = function () {
        require(["swiper"], function (Swiper) {
            $(document).ready(function () {
                var mySwiper5 = new Swiper(".swiper-container5", {
                    loop: true,
                    mode: "vertical",
                    autoplay: true, //可选选项，自动滑动
                    // 如果需要分页器
                    pagination: {
                        el: ".swiper-pagination",
                        clickable: true
                    }
                });
                var mySwiper4 = new Swiper(".swiper-container4", {
                    loop: true,
                    mode: "vertical",
                    autoplay: true, //可选选项，自动滑动
                    // 如果需要分页器
                    pagination: {
                        el: ".swiper-pagination",
                        clickable: true
                    }
                });
                var mySwiper3 = new Swiper(".swiper-container3", {
                    loop: true,
                    mode: "vertical",
                    autoplay: true, //可选选项，自动滑动
                    // 如果需要分页器
                    pagination: {
                        el: ".swiper-pagination",
                        clickable: true
                    }
                });

                var mySwiper2 = new Swiper(".swiper-container2", {
                    loop: true,
                    mode: "vertical",
                    autoplay: true, //可选选项，自动滑动
                    // 如果需要分页器
                    pagination: {
                        el: ".swiper-pagination",
                        clickable: true
                    }
                });

                var mySwiper1 = new Swiper(".swiper-container1", {
                    observer: true, //修改swiper自己或子元素时，自动初始化swiper
                    observeParents: true, //修改swiper的父元素时，自动初始化swiper
                    loop: true,
                    mode: "vertical",
                    autoplay: true, //可选选项，自动滑动
                    // 如果需要分页器
                    pagination: {
                        el: ".swiper-pagination",
                        clickable: true
                    },
                    // 如果需要前进后退按钮
                    navigation: {
                        nextEl: ".swiper-button-next",
                        prevEl: ".swiper-button-prev"
                    },
                    effect: "fade"
                });
            });
        });
    };
    // 表单验证
    Dialog.validator = function () {
        require(["boostrap", "Validator"], function () {
            $("#form-list")
                    .bootstrapValidator({
                        //		提示的图标
                        feedbackIcons: {
                            valid: "glyphicon glyphicon-ok", // 有效的
                            invalid: "glyphicon glyphicon-remove", // 无效的
                            validating: "glyphicon glyphicon-refresh" // 刷新的
                        },
                        //		属性对应的是表单元素的名字
                        fields: {
                            //			匹配校验规则
                            shopsName: {
                                // 规则
                                validators: {
                                    message: "店铺名称无效", // 默认提示信息
                                    notEmpty: {
                                        message: "店铺名称不能为空"
                                    },
                                    stringLength: {
                                        min: 2,
                                        max: 8,
                                        message: "请输入2-8的文字"
                                    },
                                    /*设置错误信息 和规则无关 和后台校验有关系*/
                                    callback: {
                                        message: "用户名错误"
                                    },
                                    fun: {
                                        message: "fun函数无效的示例"
                                    }
                                }
                            },
                            qq: {
                                validators: {
                                    message: "qq无效",
                                    notEmpty: {
                                        message: "qq不能为空"
                                    },
                                    regexp: {
                                        regexp: "[1-9][0-9]{4,14}",
                                        message: "请输入正确的QQ号"
                                    },
                                    callback: {
                                        message: "密码不正确"
                                    }
                                }
                            }
                        }
                    })
                    .on("success.form.bv", function (e) {
                        // 表单校验成功
                        /*禁用默认提交的事件 因为要使用ajax提交而不是默认的提交方式*/
                        e.preventDefault();
                        /*获取当前的表单*/
                        var form = $(e.target); // 可以通过选择器直接选择
                        $.ajax({
                            type: "post",
                            url: "/employee/employeeLogin",
                            data: form.serialize(),
                            dataType: "json",
                            success: function (response) {
                                if (response.success) {
                                    /*登录成功之后的跳转*/
                                    location.href = "index.html";
                                } else {
                                    // 登录失败
                                    //              	登录按钮点击后,默认不允许再次点击;登录失败要恢复登录按钮的点击
                                    //					form.data('bootstrapValidator').disableSubmitButtons(false);
                                    form.bootstrapValidator("disableSubmitButtons", false);
                                    //					指定触发某一个表单元素的的错误提示函数
                                    if (response.error == 1000) {
                                        // 后台接口如果返回error=1000表示name错误
                                        //						form.data('bootstrapValidator').updateStatus('username', 'INVALID', 'fun'); // 不能触发
                                        // 						可以触发
                                        form
                                                .data("bootstrapValidator")
                                                .updateStatus("username", "INVALID", "callback");
                                        //						form.data('bootstrapValidator').updateStatus('username', 'INVALID').validateField('username');
                                        //						form.data('bootstrapValidator').updateStatus('username', 'INVALID', 'notEmpty');
                                    } else if (response.error == 1001) {
                                        // 后台接口如果返回error=1001表示密码错误
                                        form
                                                .data("bootstrapValidator")
                                                .updateStatus("password", "INVALID", "callback");
                                    }
                                }
                            }
                        });
                    });
            //	重置功能
            // $(".pull-left[type='reset']").on('click', function () {
            // 	$('#shopInfos').data('bootstrapValidator').resetForm();
            // });
        });
    };
    // 右侧固定栏js
    Dialog.right = function () {
        $(document).ready(function () {
            var html = '';
            html += '<div class="right-sidebar-panels sidebar-cartbox animate-out" style="z-index: 1;">';
            html += '<div class="sidebar-cart-box">';
            html += '<h3 class="sidebar-panel-header">';
            html += '<a href="javascript:void(0);" class="title" target="_blank">';
            html += '<i class="cart-icon"></i>';
            html += '<em class="title js-sidebar-title">购物车</em>';
            html += '</a>';
            html += '<span class="close-panel"></span>';
            html += '</h3>';
            html += '<div class="sidebar-cartbox-goods-list">';
            html += '<div class="cart-panel-main">';
            html += '<div class="cart-panel-content" >';
            html += '<div class="cart-list js-cart-list"></div>';
            html += '<div class="cart-list js-collections-shops"></div>';
            html += '<div class="cart-list js-collections-goods"></div>';
            html += '<div class="cart-list js-love-history"></div>';
            html += '<div class="tip-box js-tip-box" style="display:none;">';
            html += '<i class="tip-icon"></i>';
            html += '<div class="tip-text js-tip-text">';
            html += '您的购物车里什么都没有哦<br>';
            html += '<a class="color" href="' + __URL(SHOPMAIN) + '" title="再去看看吧" target="_blank">再去看看吧</a>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '<div class="cart-panel-footer" style="position: absolute;bottom: 0;width: 100%;">';
            html += '<div class="cart-footer-checkout">';
            html += '<div class="js-footer-cart" >';
            html += '<div class="number">共<strong class="count second-color js-count">0</strong>种商品</div>';
            html += '<div class="sum">';
            html += '共计：<strong class="total second-color js-total">￥0</strong>';
            html += '</div>';
            html += '<a class="btn bg-color" href="' + __URL(SHOPMAIN + '/goods/cart') + '" target="_blank">去购物车结算</a>';
            html += '</div>';
            html += '<a class="btn bg-color" style="width:100%;display:none;" id="refreshMore" href="" target="_blank">查看更多</a>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            if ($('.right-sidebar-main').find('.sidebar-cartbox').length === 0) {
                $('.right-sidebar-main').prepend(html);
            }
        });
        // 右侧边栏js

        $(window).scroll(function () {
            if ($(this).scrollTop() > $(window).height()) {
                $(".returnTop").show();
            } else {
                $(".returnTop").hide();
            }
        });

        $(".returnTop").click(function () {
            $("body,html").animate(
                    {
                        scrollTop: 0
                    },
                    800
                    );
            return false;
        });

        // 点击用户图标弹出登录框
        $(".quick-login .quick-links-a,.quick-login .quick-login-a,.customer-service-online a").click(function () {
            $(".pop-login,.pop-mask").show();
        });
        $(".quick-area").mouseover(function () {
            $(this).find(".quick-sidebar").show();
        });
        $(".quick-area").mouseout(function () {
            $(this).find(".quick-sidebar").hide();
        });
        // 移动图标出现文字
        $(".right-sidebar-panel li").mouseenter(function () {
            $(this).children(".popup").stop().animate({
                left: -92,
                queue: true
            });
            $(this).children(".popup").css("visibility", "visible");
            $(this).children(".ibar_login_box").css("display", "block");
        });
        $(".right-sidebar-panel li").mouseleave(function () {
            $(this).children(".popup").css("visibility", "hidden");
            $(this).children(".popup").stop().animate({
                left: -121,
                queue: true
            });
            $(this).children(".ibar_login_box").css("display", "none");
        });
        // 点击购物车、用户信息以及浏览历史事件
        $(".sidebar-tabs").click(function () {
            //$(this).find("div[class='span']").text() 购物车
            var title = "";
            switch ($(this).attr("data-ns-flag")) {
                case "shopping_cart":
                    //点击了购物车
                    title = "购物车";
                    $(".js-sidebar-title").prev().css("visibility", "visible");
                    Dialog.refreshCart();
                    break;
                case "collections_goods":
                    //点击了我收藏的商品
                    title = "我收藏的商品";
                    $(".js-sidebar-title").prev().css("visibility", "hidden");
                    $("#refreshMore").text("查看更多收藏商品");
                    $("#refreshMore").attr("href", __URL(SHOPMAIN + "/member/goodscollectionlist"));
                    Dialog.refreshShopOrGoodsCollections("goods");
                    break;
                case "love_history":
                    //我看过的，浏览历史
                    title = "我看过的";
                    $(".js-sidebar-title").prev().css("visibility", "hidden");
                    Dialog.refreshHistory();
                    break;
            }
            $(".sidebar-cartbox").find(".cart-panel-content").height($(window).height() - 90);
            $(".sidebar-cartbox").find(".bonus-panel-content").height($(window).height() - 40);
            $(".js-sidebar-title").text(title);
            if ($(".right-sidebar-main").hasClass("right-sidebar-main-open") && $(this).hasClass("current")) {
                $(".right-sidebar-main").removeClass("right-sidebar-main-open");
                $(this).removeClass("current");
            } else {
                $(this).addClass("current").siblings(".sidebar-tabs").removeClass("current");
                $(".right-sidebar-main").addClass("right-sidebar-main-open");
                if (title == "购物车") {
                    if (parseInt($(".js-cart-count").text()) > 0) {
                        $(".cart-panel-footer").show();
                    } else {
                        $(".cart-panel-footer").hide();
                    }
                } else {
                    $(".cart-panel-footer").show();
                }
            }
        });
        $(".right-sidebar-panels").on("click", ".close-panel", function () {
            $(".sidebar-tabs").removeClass("current");
            $(".right-sidebar-main").removeClass("right-sidebar-main-open");
            $(".right-sidebar-panels").removeClass("animate-out");
        });
        $(document).click(function (e) {
            var target = $(e.target);
            if (target.closest(".right-sidebar-con").length == 0) {
                $(".right-sidebar-main").removeClass("right-sidebar-main-open");
                $(".sidebar-tabs").removeClass("current");
                //				$('.right-sidebar-panels').removeClass('animate-in').addClass('animate-out').css('z-index', 1);
                $(".right-sidebar-panels").removeClass("animate-in").css("z-index", 1);
            }
        });
        $('body').on('click','.J-cancelCollect',function(){
            var fav_id = $(this).data('id');
            var  fav_type = $(this).data('type');
            $.ajax({
                url : __URL(SHOPMAIN + "/components/cancelCollGoodsOrShop"),
                type : "post",
                data : {
                    "fav_id" : fav_id,
                    "fav_type" : fav_type
                },
                success : function(data) {
                    if (data["code"] > 0) {
                        Dialog.refreshShopOrGoodsCollections("goods");
                    }else{
                        layer.msg('取消失败');
                    }
                }
            });
        });
        // 右侧边栏js
    };
    // 分类侧边栏的显示隐藏
    Dialog.sortShow = function () {
//    $(".normal-nav").on("mouseover", "li", function(event) {
//      $(".index-sort-detail").show();
//    });
        $(".nav-con").on("mouseout", function (event) {
            var s = event.toElement || event.relatedTarget;
            if (!this.contains(s)) {
                $(".index-sort-detail").hide();
            }
        });
    };
    // 入驻协议"下一步"信息提示
    Dialog.nextTips = function () {
        layer.msg("请先阅读并同意协议", {
            time: 1000
        });
    };
    // 单选框切换
    Dialog.radioTab = function (Element, tab1, tab2) {
        $(Element).click(function () {
            var index = $(Element).index($(this));
            if (index == 1) {
                $(tab1).show();
                $(tab2).hide();
            } else {
                $(tab1).hide();
                $(tab2).show();
            }
        });
    };

    Dialog.refreshCart = function () {
        Dialog.cart_id_arr = new Array();
        Dialog.cart_num = new Array();
        $.ajax({
            url: __URL(SHOPMAIN + "/goods/getshoppingcart"),
            type: "POST",
            dataType: "json",
            success: function (data) {
                var rightStr = "";

                var str = "";
                var total = 0;
                if (data.length > 0) {//没登录会返回首页html代码
                    for (var i = 0; i < data.length; i++) {
                        Dialog.cart_id_arr.push(data[i].goods_id);
                        Dialog.cart_num.push(data[i].num);
                        var delete_cart_id = 0;
                        rightStr += '<div class="cart-item">';
                        rightStr += '<div class="item-goods">';
                        rightStr += '<span class="p-img">';
                        rightStr += '<a href="' + __URL(SHOPMAIN + '/goods/goodsinfo?goodsid=' + data[i].goods_id) + '">';
                        if (data[i]["picture_info"] != null) {
                            rightStr += '<img src="' + __IMG(data[i]["picture_info"]["pic_cover_micro"]) + '" width="50" height="50" alt="' + data[i].goods_name + '"></a></span>';
                        } else {
                            rightStr += '<img src="http://iph.href.lu/50x50" width="50" height="50" alt="' + data[i].goods_name + '"></a></span>';
                        }
                        rightStr += '<div class="p-name">';
                        rightStr += '<a href="' + __URL(SHOPMAIN + '/goods/goodsinfo?goodsid=' + data[i].goods_id) + '" title="' + data[i].goods_name + '">' + data[i].goods_name + '&nbsp;' + data[i].sku_name + '</a></div>';
                        rightStr += '<div class="p-price">';
                        if (data[i].point_exchange_type == 1) {
                            rightStr += '<strong>¥' + data[i].price + '+积分' + data[i].point_exchange + '</strong>x' + data[i].num + '</div>';
                        } else {
                            rightStr += '<strong>¥' + data[i].price + '</strong>×' + data[i].num + '</div>';
                        }
                        rightStr += '<a href="javascript:void(0);" class="p-del J-deleteCart" data-id="' + data[i].cart_id + '">删除</a>';
                        rightStr += '</div></div>';
                        str += '<div class="dorpdown-layer-item clearfix">';
                        str += '<div class="dorpdown-layer-left">';
                        str += '<a href="' + __URL(SHOPMAIN + '/goods/goodsinfo?goodsid=' + data[i].goods_id) + '">';
                        if (data[i]["picture_info"] != null) {
                            str += '<img src="' + __IMG(data[i]["picture_info"]["pic_cover_micro"]) + '" width="50" height="50" alt="' + data[i].goods_name + '">';
                        } else {
                            str += '<img src="http://iph.href.lu/50x50" alt="">';
                        }
                        str += '</a>';
                        str += '</div>';
                        str += '<div class="dorpdown-layer-right">';
                        str += '<div class="goods-title clearfix">';
                        str += '<a href="' + __URL(SHOPMAIN + '/goods/goodsinfo?goodsid=' + data[i].goods_id) + '" title="' + data[i].goods_name + '">' + data[i].goods_name + '</a>';
                        str += '<span class="red">¥' + data[i].price + '</strong>×' + data[i].num + '</span>';
                        str += '</div>';
                        str += '<div class="goods-price clearfix">';
                        str += '<span class="specs fl">' + data[i].sku_name + '</span>';
                        str += '<a class="goods-del fr J-deleteCart" data-id="' + data[i].cart_id + '" href="javascript:void(0);">删除</a>';
                        str += '</div>';
                        str += '</div>';
                        str += '</div>';
                        total += data[i].price * data[i].num;
                    }
                    $(".cart-panel-footer").show();
                    $(".js-tip-box").hide();
                    $('.js-footer-cart').show();
                } else {
                    str += '<p>购物车暂无商品</p>';
                    $(".cart-panel-footer").hide();
                    $(".js-tip-box").show();
                    var tip_str = '您的购物车里什么都没有哦<br>';
                    tip_str += '<a class="color" href="' + __URL(SHOPMAIN) + '" title="去逛逛" target="_blank">去逛逛</a>';
                    $(".js-tip-text").html(tip_str);
                    $('.js-footer-cart').hide();
                }
                if (data.length > 100) {
                    $(".js-cart-count").text("99+");//购物车中的数量
                    $(".J-cart").next('.circle').text("99+");//购物车中的数量
                } else {
                    $(".J-cart").next('.circle').text(data.length);//购物车中的数量
                    $(".js-cart-count").text(data.length);//购物车中的数量
                }
                total = total.toFixed(2);
                $(".J-allMoney").html('共' + data.length + '种商品，总金额' + total + '元');
                $(".J-cartList").html(str);
                $(".js-count").text(data.length);
                $(".js-total").text("￥" + total);
                $('body').find(".js-cart-list").html(rightStr);
            }
        });
    };
    Dialog.refreshShopOrGoodsCollections = function (type) {
        $("#refreshMore").hide();
        $('.js-footer-cart').hide();
        $(".js-cart-list").html("");
        if (Dialog.isLogin(1)) {
            $.ajax({
                url: __URL(SHOPMAIN + "/member/queryshoporgoodscollections"),
                type: "POST",
                data: {"type": type},
                success: function (res) {
                    var str = "";
                    if (res.length > 0) {
                        for (var i = 0; i < res.length; i++) {
                            
                            if (type == "shop") {
                                str += '<div class="cart-item"><div class="item-goods"><span class="p-img">';
                                str += '<a href="' + __URL(SHOPMAIN + '/shop/shopindex?shop_id=' + res[i].fav_id) + '">';
                                str += '<img src="' + __IMG(res[i].shop_avatar) + '" width="50" height="50" alt="' + res[i].shop_name + '"></a></span>';

                                str += '<div class="p-name">';
                                str += '<a href="' + __URL(SHOPMAIN + '/shop/shopindex?shop_id=' + res[i].fav_id) + '">' + res[i].shop_name + '</a></div>';

                                str += '<div class="p-price"><strong>' + res[i].shop_company_name + '</strong></div>';
                                str += '<a href="javascript:void(0);" class="p-del J-cancelCollect" data-type="shop" data-id="' + res[i].fav_id + '" style="width:62px;">取消收藏</a></div></div></div>';
                            } else {
                                var bg = '';
                                var url = __URL(SHOPMAIN + '/goods/goodsinfo?goodsid=' + res[i].fav_id);
                                var btn = '<a href="javascript:void(0);" class="p-del J-cancelCollect" data-type="goods" data-id="' + res[i].fav_id + '" style="width:62px;">取消收藏</a>';
                                if(res[i].status === 0){
                                    bg = 'style="background:#dddbdb"';
                                    btn = '<a href="javascript:void(0);" class="p-del" style="width:62px; color:red;">已删除</a>';
                                    url = 'javascript:void(0);';
                                }
                                str += '<div class="cart-item" '+bg+'><div class="item-goods"><span class="p-img">';
                                str += '<a href="' + url + '">';
                                str += '<img src="' + __IMG(res[i].goods_image) + '" width="50" height="50" alt="' + res[i].goods_name + '"></a></span>';

                                str += '<div class="p-name">';
                                str += '<a href="' + url + '">' + res[i].goods_name + '</a></div>';

                                str += '<div class="p-price"><strong>¥' + res[i].log_price + '</strong></div>';
                                str += btn+'</div></div></div>';
                            }
                        }
                        $("#refreshMore").show();
                        $(".js-tip-box").hide();
                    } else {
                        var str_type = type == "shop" ? "店铺" : "商品";
                        $("#refreshMore").hide();
                        $(".js-tip-box").show();
                        var tip_str = '这里空空的，赶快去收藏' + str_type + '吧！<br/>';
                        tip_str += '<a class="color" href="' + __URL(SHOPMAIN) + '" title="去逛逛" target="_blank">去逛逛</a>';
                        $(".js-tip-text").html(tip_str);
                    }
                    $(".js-cart-list").html(str);
                }
            });
        }else{
            Dialog.loginMember("会员登录", ".login-dialog");
        }
    };
    //右侧边栏-->"我看过的"
    Dialog.refreshHistory = function () {

        $("#refreshMore").hide();
        $('.js-footer-cart').hide();
        $(".js-cart-list").html("");
        //是否登录
        $.ajax({
            url: __URL(SHOPMAIN + "/goods/getmemberhistories"),
            type: "POST",
            success: function (res) {
                var str = "";
                if (res.length > 0) {
                    for (var i = 0; i < res.length; i++) {
                        str += '<div class="cart-item"><div class="item-goods"><span class="p-img">';
                        str += '<a href="' + __URL(SHOPMAIN + '/goods/goodsinfo?goodsid=' + res[i].goods_id) + '">';
                        str += '<img src="' + __IMG(res[i].picture_info.pic_cover_mid) + '" width="50" height="50" alt="' + res[i].goods_name + '"></a></span>';

                        str += '<div class="p-name">';
                        str += '<a href="' + __URL(SHOPMAIN + '/goods/goodsinfo?goodsid=' + res[i].goods_id) + '">' + res[i].goods_name + '</a></div>';

                        str += '<div class="p-price"><strong>¥' + res[i].price + '</strong></div>';
                        str += '</div></div></div>';
                    }

                    $(".js-tip-box").hide();
                } else {
                    $(".js-tip-box").show();
                    var tip_str = '您还没有浏览历史，去逛逛吧！<br/>';
                    tip_str += '<a class="color" href="' + __URL(SHOPMAIN) + '" title="去逛逛" target="_blank">去逛逛</a>';
                    $(".js-tip-text").html(tip_str);
                }
                $(".js-cart-list").html(str);
            }
        });
    };
    Dialog.getQrcode = function () {
        var url = __URL(SHOPMAIN + "/index/getqrcode");
        $('.sidebar-code').find('img').attr('src', url);
    };
    Dialog.getQrcodeForShop = function (shop_id) {
        var url = __URL(SHOPMAIN + "/index/getqrcodeforshop") + '&shop_id=' + shop_id;
        $('.qr-codes').find('img').attr('src', url);
    };
    Dialog.collectionShopOperation = function (fav_id, fav_type, log_msg, even) {
        if (Dialog.isLogin(1))
        {
            var evtext = $(even).find('.J-collectWord').text();
            var ajaxUrl = __URL(SHOPMAIN + '/components/collectiongoodsorshop');
            var updateText = '取消收藏';
            var op = false;
            if (evtext == updateText) {
                op = true;
                ajaxUrl = __URL(SHOPMAIN + '/components/cancelcollgoodsorshop');
                updateText = '收藏店铺';
            }
            $.ajax({
                type: "post",
                url: ajaxUrl,
                async: true,
                data: {'fav_id': fav_id,
                    'fav_type': fav_type,
                    'log_msg': log_msg
                },
                success: function (res) {
                    if (res['code'] > 0) {
                        layer.msg(evtext + '成功！', {icon: 1, time: 1000}, function () {
   
                            if (op === true) {
                                $('#is_member_fav_shop').val('0');
                                $(even).find('.icon').removeClass('icon-collection').addClass('icon-collectioned');
                                $(even).removeClass('J-cancelCollectShop').addClass('J-collectShop');
                            } else {
                                $('#is_member_fav_shop').val('1');
                                $(even).find('.icon').removeClass('icon-collectioned').addClass('icon-collection');
                                $(even).removeClass('J-collectShop').addClass('J-cancelCollectShop');
                            }
                            $(even).find('.J-collectWord').text(updateText);
                            $('body').find('.collect-shop').html(updateText);
                        });
                    } else {
                        layer.msg(evtext + '失败！');
                    }

                }
            });
        }else{
            Dialog.loginMember("会员登录", ".login-dialog");
        }
    };
    Dialog.isLogin = function (type) {
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
    };
    Dialog.addcart = function (id, number, options) {
        var defaults = {
            // 是否为SKU商品
            is_sku: true,
            // 图片路径
            image_url: undefined,
            // 点击事件
            event: undefined,
            // 回调函数
            callback: undefined
        };

        options = $.extend(true, defaults, options);
        if (options.is_sku) {
            //立即购买
            if (options.tag == "buy_now") {
                if (Dialog.isLogin(1)) {
                    //防止用户恶意操作
                    if ($(".add-cart").hasClass("js-disabled")) {
                        return;
                    }
                    if ($(".js-buy-now").hasClass("js-disabled")) {
                        return;
                    }
                    $(".js-buy-now").addClass("js-disabled");
                    $(".add-cart").addClass("js-disabled");

                    var sku_id = $("#hidden_skuid").val();
                    //没有SKU商品，获取第一个
                    if (sku_id == null || sku_id == "") {
                        sku_id = $("#goods_sku0").attr("skuid");
                    }
                    $.ajax({
                        url: __URL(SHOPMAIN + "/member/ordercreatesession"),
                        type: "post",
                        data: {"tag": "buy_now", "sku_id": sku_id, "num": $("#num").val(), "seckill_id": $('#seckill_id').val()},
                        success: function (res) {
                            if (res['code'] > 0) {
                                location.href = __URL(SHOPMAIN + "/member/paymentorder");
                            } else {
                                layer.msg(res['message']);
                                $(".js-buy-now").removeClass("js-disabled");
                                $(".add-cart").removeClass("js-disabled");
                            }
                        }
                    });
                }else{
                    Dialog.loginMember("会员登录", ".login-dialog");
                }
            } else {
                // 加入购物车，飞入购物车动画特效
                var cart_detail = new Object();
                cart_detail.goods_id = id;
                cart_detail.count = $("#num").val();
                cart_detail.goods_name = $(".js-goods-name").text();
                cart_detail.sku_id = $("#hidden_skuid").val();
                //没有SKU商品，获取第一个
                if (cart_detail.sku_id == null || cart_detail.sku_id == "")
                {
                    cart_detail.sku_id = $("#goods_sku0").attr("skuid");
                }
                cart_detail.sku_name = $("#hidden_skuname").val();
                cart_detail.price = $("#hidden_sku_price").val();
                cart_detail.picture_id = $("#hidden_default_img_id").val();
                cart_detail.cost_price = $("#hidden_sku_price").val();//成本价
                $.ajax({
                    url: __URL(SHOPMAIN + "/goods/addcart"),
                    type: "post",
                    data: {"cart_detail": JSON.stringify(cart_detail), "seckill_id": $('#seckill_id').val()},
                    success: function (res) {
                        if (res == -1)
                        {
                            layer.msg("添加购物车失败");
                        }else if(res == -2){
                            layer.msg("数量超出范围");
                        } else {
                            $(".add-cart").removeClass("js-disabled");
                            $(".js-buy-now").removeClass("js-disabled");
                            //加入购物车
                            Dialog.refreshCart();//里边会加载购物车中的数量
                            layer.msg("添加购物车成功");
                        }
                    }
                });
            }
        } else {
            // 添加商品
            $.post(__URL(SHOPMAIN + '/cart/add'), {
                goods_id: id,
                number: number
            }, function (result) {
                if (result.code == 0) {
                    layer.msg(result.message, {icon: 1, time: 1000}, function () {
                        Dialog.refreshCart();
                    });
                    // 刷新购物车数量
                } else if (result.code == 98) {
                    $("body").append(result.data);
                } else {
                    layer.msg(result.message, {
                        time: 5000
                    });
                }
                // 回调函数
                if ($.isFunction(options.callback)) {
                    options.callback.call($.cart, result);
                }
            }, "json");
        }

    };
    Dialog.applyOpration = function(){
            var html = $('.J-header').html();
            $('.shopHeader').html(html);
            $('.navs').remove();
            var selCity = $("#seleAreaNext");
            var selCityCommpany= $("#seleAreaCommpanyNext");
            // 添加省
            $.ajax({
                    type : "post",
                    url : __URL(SHOPMAIN + '/index/getProvince'),
                    dataType : "json",
                    success : function(data) {
                            if (data != null && data.length > 0) {
                                    for (var i = 0; i < data.length; i++) {
                                            selCity.append("<option value='"+data[i].province_id+"'>"+data[i].province_name+"</option>");
                                            selCityCommpany.append("<option value='"+data[i].province_id+"'>"+data[i].province_name+"</option>");
                                    }
                            }
                    }
            });
    };
    Dialog.formCheck=function(obj){
        require(["jquery.validate","messages_zh"], function() {
            $.validator.addMethod("checkIdCard",function(value,element,params){
                var checkIdCard = /^(^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[Xx])$)$/;
                return this.optional(element)||(checkIdCard.test(value));
            },"*请输入正确的身份证号码");
            $.validator.addMethod("checkPhone",function(value,element,params){
                var checkPhone = /(^1[3|4|5|7|8]\d{9}$)|(^09\d{8}$)/;
                return this.optional(element)||(checkPhone.test(value));
            },"*请输入正确的手机号码");

            $(obj).validate({
                rules: {
                    email: {
                      required: true,
                      email: true
                    },
                    id_card_no:{
                      required: true,
                      checkIdCard:true
                    },
                    mobile:{
                      checkPhone:true,
                      required: true
                    },
                    bank_mobile:{
                      checkPhone:true,
                      required: true
                    }
                },
                messages:{
                   email: "请输入一个正确的邮箱"
                }, 
            });
        });
    };
    /**
    * 上传文件
    * @param fileid 当前input file类型
    * @param data 传输的数据 file_path属性必传
    * @source admin pc sourcel
    */
    Dialog.uploadFile = function(fileid,data,callBack, source){
           var dom = document.getElementById(fileid);
           var file =  dom.files[0];//File对象;
           if(Dialog.validationFile(file, source)){
                   filesa.ajaxFileUpload({
                           url : __URL(SHOPMAIN + '/uploader/uploadFile'), //用于文件上传的服务器端请求地址
                           secureuri : false, //一般设置为false
                           fileElementId : fileid, //文件上传空间的id属性  <input type="file" id="file" name="file" />
                           dataType : 'json', //返回值类型 一般设置为json
                           data : data,
                           async : false,
                           contentType : "text/json;charset=utf-8",
                           success : function(res){ //服务器成功响应处理函数
                                   callBack.call(this,res);
                           }
                   });
           }
   };

    /**
     * 验证文件是否可以上传
     * @param file JS DOM文件对象
     * @source admin pc sourcel
     */

    Dialog.validationFile = function(file, source) {
            var fileTypeArr = ['application/php','text/html','application/javascript','application/msword','application/x-msdownload','text/plain'];
            if(null == file) return false;

            if(!file.type){
                    if(source == 1) layer.msg("文件类型不合法");

                    else if(source == "pc" )  layer.msg("文件类型不合法");

                    else layer.msg("文件类型不合法");

                    return false;
            }

            var flag = false;
            for(var i=0;i<fileTypeArr.length;i++){
                    if(file.type == fileTypeArr[i]){
                            flag = true;
                            break;
                    }
            }

            if(flag){
                    if(source == 1) layer.msg("文件类型不合法");

                    else if(source == "pc" )  layer.msg("文件类型不合法");

                    else  layer.msg("文件类型不合法");

                    return false;
            }

            return true;
    };

    /**
     * 删除文件，支付批量删除，逗号隔开
     *

     * @param filename
     */
    Dialog.removeFile = function(filename){
            $.ajax({
                    url : __URL(ADMINMAIN + "/uploader/removefile"),
                    type : "post",
                    data : { "filename" : filename },
                    success : function(res){
                            layer.msg("本次操作共删除"+res.success_count+"个文件,"+res.error_count+"个文件失败");
                    }
            });
    };
    //删除购物车中的商品  flag:是否刷新当前页面，
    Dialog.deleteShoppingCartById = function(id,flag)
    {
        layer.confirm('您确实要把该商品移出购物车吗？', {
            btn : [ '确定', '取消' ],//按钮
            title: "删除商品"
        }, function(index) {
            layer.close(index);
            $.ajax({
                url : __URL(SHOPMAIN+"/goods/deleteshoppingcartbyid"),
                type : "POST",
                data : {"cart_id_array": id},
                success : function(data){
                    if(data["code"]>0)
                    {
                        layer.msg("操作成功", { time: 1000 });
                        Dialog.refreshCart();//刷新购物车
                        if(flag)
                        {
                            location.reload();
                        }
                    }
                }
            });
        }); 
    }
    // 支付密码
    Dialog.payPwd = function() {
        var payPassword = $("#payPassword_container"),
            _this = payPassword.find("i"),
            k = 0,
            j = 0,
            password = "",
            _cardwrap = $("#cardwrap");
        //点击隐藏的input密码框,在6个显示的密码框的第一个框显示光标
        payPassword.on("focus", "input[name='payPassword_rsainput']", function() {
            var _this = payPassword.find("i");
            if (payPassword.attr("data-busy") === "0") {
                //在第一个密码框中添加光标样式
                _this.eq(k).addClass("active");
                _cardwrap.css("visibility", "visible");
                payPassword.attr("data-busy", "1");
            }
        });
        //change时去除输入框的高亮，用户再次输入密码时需再次点击
        payPassword
            .on("change", "input[name='payPassword_rsainput']", function() {
                _cardwrap.css("visibility", "hidden");
                _this.eq(k).removeClass("active");
                payPassword.attr("data-busy", "0");
            })
            .on("blur", "input[name='payPassword_rsainput']", function() {
                _cardwrap.css("visibility", "hidden");
                _this.eq(k).removeClass("active");
                payPassword.attr("data-busy", "0");
            });

        //使用keyup事件，绑定键盘上的数字按键和backspace按键
        payPassword.on("keyup", "input[name='payPassword_rsainput']", function(e) {
            var e = e ? e : window.event;

            //键盘上的数字键按下才可以输入
            if (
                e.keyCode == 8 ||
                (e.keyCode >= 48 && e.keyCode <= 57) ||
                (e.keyCode >= 96 && e.keyCode <= 105)
            ) {
                k = this.value.length; //输入框里面的密码长度
                l = _this.size(); //6

                for (; l--; ) {
                    //输入到第几个密码框，第几个密码框就显示高亮和光标（在输入框内有2个数字密码，第三个密码框要显示高亮和光标，之前的显示黑点后面的显示空白，输入和删除都一样）
                    if (l === k) {
                        _this.eq(l).addClass("active");
                        _this
                            .eq(l)
                            .find("b")
                            .css("visibility", "hidden");
                    } else {
                        _this.eq(l).removeClass("active");
                        _this
                            .eq(l)
                            .find("b")
                            .css("visibility", l < k ? "visible" : "hidden");
                    }

                    if (k === 6) {
                        j = 5;
                    } else {
                        j = k;
                    }
                    $("#cardwrap").css("left", j * 30 + "px");
                }
            } else {
                //输入其他字符，直接清空
                var _val = this.value;
                this.value = _val.replace(/\D/g, "");
            }
        });
    };
    // 日期选择插件
    // Dialog.aaa = function(){
    //     require(["laydate"], function(laydate) {
    //         laydate.render({
    //             elem: '#birthday', //指定元素
    //             theme: '#87CEEB'
    //         });
    //     })
    // };
    /**
     * layDate日期 
     * @param {
     *      element     触发的dom
     *      range       Boolean true为双日历
     *      callback    点击确定的回调
     * }
     * @DateTime 2018-11-15
     */
    Dialog.layDate=function(element,ranges,callback){
        require(['laydate'],function(laydate){
            ranges && ranges !== undefined ? ranges : false;
            var minDate = $(element).attr("data-mindate");
            var maxDate = $(element).attr("data-maxdate");
            var types = $(element).attr("data-types");
            minDate=minDate && minDate !== undefined ? minDate :'1900-1-1';
            maxDate=maxDate && maxDate !== undefined ? maxDate :'2099-12-31';
            types=types && types !== undefined ? types :'date';
            laydate.render({
            elem: element,
            type:types,
            theme: '#2c9cf0',
            range:ranges,
            min:minDate,
            max:maxDate,
            zIndex: 99999999999,
            done: function(value, date, endDate){
                callback && callback(value, date, endDate);
            }
            });
        })
    }
    Dialog.footerImg = function(){
        $('.J-shopbottom').find("*[data-is_show]").each(function(){
            var is_show = $(this).data('is_show');
            if(!is_show){
                $(this).parents('[data-mode]').hide();
            }
        });
    };

  // 图片懒加载
  Dialog.lazyLoad=function(){
    require(["lazyload"], function() {
      $("img").lazyload({
        threshold : 200,
        effect: "fadeIn", // 加载效果
        placeholder : "/template/shop/new//public/image/loading1.gif", //占位图
        failurelimit : 20 // 发生混乱时的hack手段
      });
    })
  };

  //自定义表单验证
    /**
     * 表单验证
     * @DateTime 2019-05-13
     * @param {elm}         表单元素
     */
    Dialog.validate = function(elm,callback){
        require(['domReady','jquery.validate','validate.methods'],function(){
            var msg = {
                required: "此项为必填项",
                remote: "请修正该字段",
                email: "请输入正确格式的电子邮件",
                url: "请输入正确的网址",
                date: "请输入正确的日期",
                dateISO: "请输入合法的日期 (ISO).",
                number: "此项为数字格式",
                digits: "此项必须为数字",
                creditcard: "请输入合法的信用卡号",
                equalTo: "请再次输入相同的值",
                accept: "请输入拥有合法后缀名的字符串",
                maxlength: $.validator.format("请输入一个长度最多是 {0} 的字符串"),
                minlength: $.validator.format("请输入一个长度最少是 {0} 的字符串"),
                rangelength: $.validator.format("请输入一个长度介于 {0} 和 {1} 之间的字符串"),
                range: $.validator.format("请输入一个介于 {0} 和 {1} 之间的值"),
                max: $.validator.format("请输入一个最大为 {0} 的值"),
                min: $.validator.format("请输入一个最小为 {0} 的值")
            };
            var validate_rule = {
                ignore:'.ignore',
                errorElement: 'span',
                errorClass: 'help-block-error',
                focusInvalid: true,
                debug:true,
                highlight: function (element) {
                    var parent = $(element).data('parent') || '';
                    if (parent) {
                        $(element).closest('.controls').addClass('has-error')
                    } else {
                        
                        $(element).addClass('has-error')
                    }
                },
                unhighlight: function (element) {
                    var parent = $(element).data('parent') || '';
                    if (parent) {
                        $(element).closest('.controls').removeClass('has-error')
                    } else {
                        $(element).removeClass('has-error')
                    }
                },
                // success: function (element) {
                //     $(element).removeClass('has-error');
                // },
                onkeyup: function(element) {
                    $(element).valid()
                },
                onfocusout: function(element) {
                    $(element).valid()
                },
                errorPlacement: function(error, element) {
                    // 单选复选框
                    if (element.is(':radio') || element.is(':checkbox')){
                        var group = element.parent().parent();
                        group.length > 0 ? group.after(error) : element.after(error)

                    }else{
                        // 普通input框
                        var group = element.parents(".input-group");
                        group.length > 0 ? group.after(error) : element.after(error)
                    }
                },
                submitHandler: function(form) {
                    callback(form)
                },
            };
            $.extend($.validator.messages, msg);
            elm.validate(validate_rule)
        })
    };

  // 13位时间戳转换成yyyy-MM-dd
    /**
     * 
     * @DateTime 2019-05-13
     * @param {str}         类型:Number
     */
    Dialog.turnDate=function(str){
        var date = new Date(str);
        var year = date.getFullYear();
        var month = date.getMonth()+1;
        var day = date.getDate();
        month = month < 10 ? "0"+month:month;
        day = day < 10 ? "0"+day:day;
        return year+'-'+month+'-'+day;
    }

    return Dialog;
});
