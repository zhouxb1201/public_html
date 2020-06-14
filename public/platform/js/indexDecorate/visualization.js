$(function () {
    var suffix_obj = $("input[name='suffix']"), suffix = suffix_obj.val(), temp_type = suffix_obj.data("type");
    var visualShell = $("*[ectype='visualShell']");
    $(document).ready(function (e) {
        var arrLunbotu = [];
        var arrBanner = [];
        $(".module-list .lyrow").sortable({
            connectWith: ".demo",
            opacity: .35,
            handle: ".drag",
            placeholder: "ui-state-highlight"
        });
        $(".demo").sortable();
        $(".demo").disableSelection();
        //风格切换
        $(".shopStyle").on("click", ".style-item", function() {
          $(this).addClass("selected").siblings().removeClass("selected");
        });
        //左侧模块拖动到右侧
        $(".module-list .lyrow").draggable({connectToSortable: ".demo", helper: "clone", handle: ".drag",
            drag: function (e, t) {
                t.helper.width(63);
            },
            stop: function (e, t) {
                var zhi = $(this).hasClass('lunbotu');
                var ban = $(this).hasClass('J-topBanner');
                var mode = $(this).data("mode");
                var allow = true;
                var noin = true;
                $(".demo *[data-mode=" + mode + "]").each(function (index, element) {
                    if(typeof($(this).attr("data-diff"))=="undefined"){//拖动不成功，不往下执行
                        noin = false;
                    }
                    $(this).attr("data-diff", index);
                    if (!zhi) {
                        $(this).find("*[data-type='range']").attr("id", mode + "_" + index);
                    }
                });
                if(noin){
                    return;
                }
                $(".demo[type='goodst'] *[data-mode=" + mode + "]").each(function (index, element) {

                    $(this).css("width", '100%');

                    if(mode === 'singleBanner'){
                        $(this).find('.sBanner').removeClass('w1200');
                    }

                    if(mode === 'goodsRecom'){
                        $(".demo[type='goodst']").find("*[data-mode='goodsRecom']").remove();
                        allow = false;
                    }
                });
                $(".demo[type='J-top'] *[data-mode=" + mode + "]").each(function (index, element) {
                    if(mode != 'topBanner' ){
                        $(".demo[type='J-top']").find(".visual-item[data-mode!='topBanner']").remove();
                        allow = false;
                    }
                });
                $(".demo[type='default'] *[data-mode=" + mode + "]").each(function (index, element) {
                    if(mode === 'topBanner' ){
                        $(".demo[type='default']").find("*[data-mode='topBanner']").remove();
                        allow = false;
                    }
                });
                if (zhi) {
                    var lunbotu = $(".demo").find('.lunbotu');
                    arrLunbotu.push(t.position.top);

                    if (arrLunbotu.length == 1) {
                        if (arrLunbotu[0] > arrLunbotu[1]) {
                            lunbotu.eq(0).remove();
                        } else {
                            lunbotu.eq(1).remove();
                        }
                        arrLunbotu.pop();
                    }
                    if (lunbotu.length > 1) {
                        layer.msg('此模块只能添加一次', {icon: 3, time: 500, offset: ["50%", "50%"]});
                    } else {
                        if (t.position.left > 200) {
                            layer.msg('添加成功', {icon: 1, time: 500, offset: ["50%", "50%"]});
                            disabled();
                        }
                    }
                } else if (ban && allow===true) {
                    var topBanner = $(".demo[type='J-top']").find('.J-topBanner');
                    arrBanner.push(t.position.top);
                    if (arrBanner.length == 1) {
                        if (arrBanner[0] > arrBanner[1]) {
                            topBanner.eq(0).remove();
                        } else {
                            topBanner.eq(1).remove();
                        }
                        arrBanner.pop();
                    }

                    if (topBanner.length > 1) {
                        layer.msg('此模块只能添加一次', {icon: 3, time: 500, offset: ["50%", "50%"]});
                    } else {
                        if (t.position.left > 200) {
                            layer.msg('添加成功', {icon: 1, time: 500, offset: ["50%", "50%"]});
                            disabled();
                        }
                    }
                } else if (t.position.left > 200 && allow===true) {
                    layer.msg('添加成功', {time: 500, offset: ["50%", "50%"]});
                    disabled();
                }else{
                    layer.msg('此模块不支持放入此处', {icon: 3, time: 500, offset: ["50%", "50%"]});
                }
                $(".demo .view").show();
                visual();
            }
        });

        //模块上移
        $(document).on("click", ".move-up", function () {
            var _this = $(this);
            var _div = _this.parents(".visual-item");
            var prev_div = _div.prev();

            var clone = _div.clone();
            if (!_this.hasClass("disabled")) {
                _div.remove();
                prev_div.before(clone);
                visual();
                disabled();
            }
        });

        //模块下移
        $(document).on("click", ".move-down", function () {
            var _this = $(this);
            var _div = _this.parents(".visual-item");
            var next_div = _div.next();

            var clone = _div.clone();
            if (!_this.hasClass("disabled")) {
                _div.remove();
                next_div.after(clone);
                visual();
                disabled();
            }
        });

        //判断模块是顶部模块或底部模块
        function disabled() {
            var demo = $(".demo");
            demo.find(".visual-item .move-up").removeClass("disabled");
            demo.find(".visual-item:first .move-up").addClass("disabled");

            demo.find(".visual-item .move-down").removeClass("disabled");
            demo.find(".visual-item:last .move-down").addClass("disabled");
        }

        //删除模块
        function removeElm() {
            $(".demo").delegate(".move-del", "click", function (e) {
                that = $(this);
                if (that.attr('ectype') == 'model_delete') {
                    return;
                }
                layer.confirm('您确定要删除这个模块吗？', {
                    btn: ['确定', '取消'],
                }, function () {
                    time: 500,
                        layer.msg('删除成功', {icon: 1, time: 500, offset: ["50%", "50%"]}),
                        e.preventDefault();
                    that.parents(".visual-item").remove();
                    disabled();
                    visual();
                    if (!$(".demo .lyrow").length > 0 && temp_type != 'goods_templates') {
                        clearDemo();
                    }
                });
            })
        }
        removeElm();

        $(document).on("show.bs.modal", ".modal", function () {
            $(this).draggable({
                handle: ".modal-header" // 只能点击头部拖动
            });
            $(this).css("overflow", "hidden"); // 防止出现滚动条，出现的话，你会把滚动条一起拖着走的
        });

        $(".layoutItem").on("click", function () {
            $(this).siblings().removeClass("selected"); //siblings是循环遍历
            $(this).addClass("selected");
            $(this).children(".checkbox_item").children("input[type=radio]").prop("checked", "checked");
            $(this).siblings().children("input[type=radio]").removeAttr("checked");
        });

        //  商品推荐单选框Tab栏
        $("input[name=recomMethod]").click(function () {
            var index = $("input[name=recomMethod]").index($(this));
            if (index == 1) {
                $(".recomTab").show();
                $(".auto").hide();
            } else {
                $(".recomTab").hide();
                $(".auto").show();
            }
        });
    });
    //头部广告删除
    $(document).on("click", "*[ectype='model_delete']", function () {
        var _this = $(this);
        var suffix = $('#code').val();
        var type = $('#type').val();
        if (layer.confirm("确定删除此广告么？删除后前台不显示只能后台编辑，且不可找回！")) {
            $.ajax({
                type: "post",
                url: __URL(MAIN + "/config/deletepccustomtemplatetop"),
                data: {"suffix": suffix, "type": type},
                success: function (res) {
                    if (res.error == 1) {
                        showTip(res.message, "error");
                    } else {
                        var obj = _this.parents('*[data-mode="topBanner"]');
                        //初始化默认值
//                            obj.find('[data-type="range"]').parent().css({"background": "#dbe0e4"});
//                            obj.find('[data-type="range"] a').attr("href", "#");
//                            obj.find('[data-type="range"] img').attr("src", "../data/gallery_album/visualDefault/homeIndex_011.jpg");
//                            obj.find(".spec").remove();
                        obj.remove();
                        visual();
                    }
                }
            });
        }
    });
    //底部广告删除
    $(document).on("click", "*[ectype='model_delete_footer']", function () {
        var _this = $(this);
        if (layer.confirm("确定删除此图片么？删除后前台不显示只能后台编辑，且不可找回！")) {
            var obj = _this.parents('*[data-mode="bottomBanner"]');
            //初始化默认值
            obj.find('[data-type="range"]').empty();
            obj.find(".spec").remove();
            visual(3);
        }
    });

    //判断模块是否存在
    function clearDemo() {
        if ($(".demo").html() == "") {
            layer.msg('当前没有任何模板哦', {time: 500, offset: ["50%", "50%"]})
        } else {
            layer.confirm('您确定要清空所有模块吗？', {
                btn: ['确定', '取消'],
            }, function () {
                time: 500,
                    layer.msg('清空成功', {time: 500, offset: ["50%", "50%"]}),
                    $(".demo").empty();
            });
        }
    }
    //可视化操作：确认发布、还原、预览、信息编辑、选择模板
    function visualOperation() { //店铺设为默认

        /* 确认发布 */
        $('.J-download').on("click", function () {
            layer.confirm('确定发布？', {
                btn: ['确定', '取消']//按钮
            }, function (index) {
                layer.close(index);
                $.ajax({
                    type: "post",
                    url: __URL(MAIN + "/config/downloadmodal"),
                    data: {"suffix": suffix, "template_type": temp_type},
                    dataType: "json",
                    success: function (data) {
                        if (data['code'] > 0) {
                            layer.msg('发布成功', {icon: 1, time: 1000}, function () {
                                $('.J-back').hide();
                            });
                        } else {
                            layer.msg('操作失败，请稍后重试', {icon: 2, time: 1000});
                        }
                    }
                });
            });
        });
        /* 还原编辑前的模板 */
        $('.J-back').on("click", function () {
            layer.confirm('还原只能还原到你最后一次确认发布后的版本，还原后当前未保存的数据将丢失，不可找回，确定还原吗？', {
                btn: ['确定', '取消']//按钮
            }, function (index) {
                layer.close(index);
                $.ajax({
                    type: "post",
                    url: __URL(MAIN + "/config/backmodal"),
                    data: {"suffix": suffix, "template_type": temp_type},
                    dataType: "json",
                    success: function (data) {
                        if (data['code'] > 0) {
                            layer.msg('操作成功', {icon: 1, time: 1000}, function () {
                                location.href = location;
                            });
                        } else {
                            layer.msg('操作失败，请稍后重试', {icon: 2, time: 1000});
                        }
                    }
                });
            });
        });
    }
    visualOperation();
    //可视化区域编辑
    visualShell.on("click", "*[ectype='model_edit']", function () {
        var $this = $(this),
            lyrow = $this.parents(".lyrow"),
            mode = lyrow.attr("data-mode"),
            purebox = lyrow.attr("data-purebox"),
            diff = lyrow.attr("data-diff"),
            range = lyrow.find("*[data-type='range']"),
            lift = range.attr("data-lift");
        var hierarchy = '',
            masterTitle = '',
            spec_attr = '',
            pic_number = 0,
            where = '',
            count = 0;

        if (!lift) {
            lift = '';
        }

        spec_attr = lyrow.find('.spec').data('spec');

        if (purebox == "banner") {
            purebox = "adv";
        }
        switch (purebox) {
            case "hot":
                //热门活动模块编辑
                masterTitle = lyrow.find('.spec').data('title');
                spec_attr = JSON.stringify(spec_attr);
                Ajax.call(__URL(MAIN + "/config/hot"), "mode=" + mode + '&spec_attr=' + encodeURIComponent(spec_attr) + "&masterTitle=" + escape(masterTitle) + "&lift=" + lift + "&diff=" + diff, hotResponse, 'POST', 'text');

                break;
            case "homeAdv":
                //精选好店模块编辑
                masterTitle = lyrow.find('.spec').data('title');
                spec_attr = JSON.stringify(spec_attr);
                Ajax.call(__URL(MAIN + "/config/homeAdv"), "mode=" + mode + '&spec_attr=' + encodeURIComponent(spec_attr) + "&masterTitle=" + escape(masterTitle) + "&lift=" + lift + "&diff=" + diff,shopResponse, 'POST', 'text');

                break;
            case "homeFloor":
                //楼层模块编辑
                if (mode == 'homeFloorModule') {
                    hierarchy = $this.parents("*[ectype='module']").find(".view").data('hierarchy');
                    spec_attr = $this.parents("*[ectype='module']").find(".spec").data('spec');
                }
                spec_attr = JSON.stringify(spec_attr);
                var reg = /&/g;
                if (spec_attr) {
                    spec_attr = spec_attr.replace(reg, '＆');
                }
                Ajax.call(__URL(MAIN + "/config/homefloor"), 'act=homeFloor' + "&mode=" + mode + '&spec_attr=' + spec_attr + "&diff=" + diff + "&lift=" + lift + "&hierarchy=" + hierarchy, floorResponse, 'POST', 'text');
                break;
            case "cust":
                //自定义模块编辑
                range.find('.ui-box-display').remove();
                var custom_content = encodeURIComponent(range.html());
                Ajax.call(__URL(MAIN + "/config/custom"), 'mode=' + mode + '&custom_content=' + custom_content + "&diff=" + diff + "&lift=" + lift, customResponse, 'POST', 'text');
                break;
            case "shop_adv":
                //首页轮播模块编辑
                pic_number = lyrow.data("length");
                spec_attr = JSON.stringify(spec_attr);
                var reg = /&/g;
                if (spec_attr) {
                    spec_attr = spec_attr.replace(reg, '＆');
                }
                Ajax.call(__URL(MAIN + "/config/homeBanner"), 'act=homeBanner' +"&pic_number=" + pic_number +  "&mode=" + mode + '&spec_attr=' + spec_attr + "&diff=" + diff + "&lift=" + lift + "&hierarchy=" + hierarchy, shop_query_banner, 'POST', 'text')
                break;
            case "adv":
                //轮播、底部广告、顶部广告模块编辑
                pic_number = lyrow.data("length");
                spec_attr = JSON.stringify(spec_attr);
                var reg = /&/g;
                if (spec_attr) {
                    spec_attr = spec_attr.replace(reg, '＆');
                }
                Ajax.call(__URL(MAIN + "/config/banner"), "spec_attr=" + spec_attr + "&pic_number=" + pic_number + "&mode=" + mode + "&diff=" + diff, query_banner, 'POST', 'text');
                break;
            case "singleBanner":
                //单图广告模块编辑
                pic_number = lyrow.data("length");
                spec_attr = JSON.stringify(spec_attr);
                var reg = /&/g;
                if (spec_attr) {
                    spec_attr = spec_attr.replace(reg, '＆');
                }
                Ajax.call(__URL(MAIN + "/config/singlebanner"), "spec_attr=" + spec_attr + "&pic_number=" + pic_number + "&mode=" + mode + "&diff=" + diff, single_banner, 'POST', 'text');
                break;
            case "nav_mode":
                //导航模板编辑
                spec_attr = JSON.stringify(spec_attr);
                Ajax.call(__URL(MAIN + "/config/navmode"), 'mode=' + mode + '&template_type=' + temp_type + '&spec_attr=' + encodeURIComponent(spec_attr) + where, navigatorResponse, 'POST', 'text');
                break;
            case "home_nav_mode":
                //导航模板编辑
                spec_attr = JSON.stringify(spec_attr);
                mode = 'home_nav_mode';
                Ajax.call(__URL(MAIN + "/config/navmode"), 'mode=' + mode + '&template_type=' + temp_type + '&spec_attr=' + encodeURIComponent(spec_attr) + where, homenavigatorResponse, 'POST', 'text');
                break;
            case "home_header_mode":
                //导航模板编辑
                spec_attr = JSON.stringify(spec_attr);
                mode = 'home_header_mode';
                Ajax.call(__URL(MAIN + "/config/homeheadermode"), 'mode=' + mode + '&template_type=' + temp_type + '&spec_attr=' + encodeURIComponent(spec_attr) + where, homeheaderResponse, 'POST', 'text');
                break;
            case "help_mode":
                //帮助中心编辑
                spec_attr = JSON.stringify(spec_attr);

                Ajax.call(__URL(MAIN + "/config/helpmode"), 'mode=' + mode + '&spec_attr=' + encodeURIComponent(spec_attr) + where, helpResponse, 'POST', 'text');
                break;
            case "right_mode":
                //右侧工具栏编辑
                spec_attr = JSON.stringify(spec_attr);

                Ajax.call(__URL(MAIN + "/config/rightmode"), 'mode=' + mode + '&spec_attr=' + encodeURIComponent(spec_attr) + where, rightResponse, 'POST', 'text');
                break;
            case "link_mode":
                //友情链接编辑
                spec_attr = JSON.stringify(spec_attr);

                Ajax.call(__URL(MAIN + "/config/linkmode"), 'mode=' + mode + '&spec_attr=' + encodeURIComponent(spec_attr) + where, linkResponse, 'POST', 'text');
                break;
            case "copy_mode":
                //版权信息编辑
                spec_attr = JSON.stringify(spec_attr);

                Ajax.call(__URL(MAIN + "/config/copymode"), 'mode=' + mode + '&spec_attr=' + encodeURIComponent(spec_attr) + where, copyResponse, 'POST', 'text');
                break;

            case "goods":
                //商品推荐模块编辑
                spec_attr = JSON.stringify(spec_attr);
                Ajax.call(__URL(MAIN + "/config/goodsinfo"), "mode=" + mode + "&diff=" + diff + "&spec_attr=" + encodeURIComponent(spec_attr) + "&lift=" + lift, query_goods, 'POST', 'text');
                break;

            case "header":
                //店铺可视化头部
                spec_attr = JSON.stringify(spec_attr);
                var custom_content = encodeURIComponent(lyrow.find('.spec').html());
                Ajax.call(__URL(MAIN + "/config/shopheadermode"), 'mode=' + mode + "&spec_attr=" + encodeURIComponent(spec_attr) + "&custom_content=" + custom_content + "&suffix=" + suffix, headerResponse, 'POST', 'text');
                break;
            case "service":
                //客服中心编辑
                count = lyrow.data("length");
                spec_attr = JSON.stringify(spec_attr);
                Ajax.call(__URL(MAIN + "/config/servicemode"), 'mode=' + mode + "&diff=" + diff + '&spec_attr=' + encodeURIComponent(spec_attr) + '&count=' + count, serviceResponse, 'POST', 'text');
                break;
        }
    });
    /*店铺可视化头部编辑弹窗*/
    headerResponse = function (result) {
        dialogs("店招", result, "950px", "auto", function () {
            var res = $('.header-mode-json').html();
            res = JSON.parse($.trim(res));
            header_back(res.mode, $("#header_dialog"));
            $('.header-mode-json').remove();
        });
        /* 店铺可视头部回调函数 */
        function header_back(mode, obj) {
            var header_type = obj.find("input[name='header_type']:checked").val(),
                headerbg_img = obj.find("input[name='bgimg']").val(),
                shopname = obj.find("input[name='shopname']").val(),
                noshow = obj.find("input[name='noshowname']:checked").val(),
                custom_content = obj.find("input[name='custom_content']").val(),
                spec_attr = new Object(),
                range = $("*[data-mode=" + mode + "]").find('[data-type="range"]');
            if (noshow != '1') {
                noshow = '0';
            }
            spec_attr.header_type = header_type;
            spec_attr.headerbg_img = headerbg_img;
            spec_attr.shopname = shopname;
            spec_attr.noshow = noshow;
//            spec_attr.custom_content = custom_content;
            if (header_type == 'defalt_type') {
                var html = "";
                html += "<div class='spec' data-spec='" + $.toJSON(spec_attr) + "' >";
                if (noshow != '1') {
                    html += "<img src='" + headerbg_img + "' alt='' class='imgWidth'><div class='storeName'>" + shopname + "</div>";
                } else {
                    html += "<img src='" + headerbg_img + "' alt='' class='imgWidth'>";
                }
                html += "</div>";
                range.html(html);
            } else {
                range.html("<div class='spec' data-spec='" + $.toJSON(spec_attr) + "'>" + custom_content + "</div>");
            }
            visual(4);
        }
    };
    /* 首页轮播 */
    shop_query_banner = function (result) {
        dialogs("广告图片", result, "960px", "", function () {
            var res = $.trim($('.shop_banner_json').html());
            res = JSON.parse(res);
            var obj = $("#bannerEdit1"), required = obj.find("*[ectype='required']");
            if (validation(required) == true) {
                if (shopBannerInsert(res.mode, res.diff)!== false) {
                    $('.shop_banner_json').remove();
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        });
        //回调函数
        function shopBannerInsert(mode, diff) {
            var spec_attr = new Object(),
                lb = [], lbOpennew = [], lbUrlLink = [], lbLink = [],
                la = [], laOpennew = [], laUrlLink = [], laLink = [];laName=[];
            var obj = $("#bannerEdit1");
            entrance = obj.find("input[name='entrance']:checked").val();
            var n='1';
            var m='1';
            //图片路径
            obj.find("input[name='leftBanner[]']").each(function () {
                if ($(this).val() == '') {
                    n = '0';
                }
                lb.push($(this).val());
            });
            if (lb.length == '0') {
                layer.msg('请选择图片', {icon: 2, time: 1000});
                return false;
            }
            //图片链接
            obj.find("input[name='leftBannerUrlLink[]']").each(function () {
                lbUrlLink.push($(this).val());
            });
            //图片链接
            obj.find("input[name='leftBannerLink[]']").each(function () {
                lbLink.push($(this).val());
            });
            obj.find("input[name='leftBannerOpennew[]']").each(function () {
                if ($(this)[0].checked === true) {
                    lbOpennew.push(1);
                } else {
                    lbOpennew.push(0);
                }
            });
            obj.find("input[name='leftAdv[]']").each(function () {
                if ($(this).val() == '') {
                    m = 0;
                }
                la.push($(this).val());
            });
            //图片链接
            obj.find("input[name='leftAdvUrlLink[]']").each(function () {
                laUrlLink.push($(this).val());
            });
            //图片链接
            obj.find("input[name='leftAdvLink[]']").each(function () {
                laLink.push($(this).val());
            });
            obj.find("input[name='leftAdvName[]']").each(function () {
                laName.push($(this).val());
            });
            obj.find("input[name='leftAdvOpennew[]']").each(function () {
                if ($(this)[0].checked === true) {
                    laOpennew.push(1);
                } else {
                    laOpennew.push(0);
                }
            });
            if (lb.length < 2 || lb.length > 8) {
                layer.msg('轮播图最少上传两张图片，最多八张', {icon: 2, time: 1000});
                return false;
            }
            spec_attr.entrance = entrance;
            spec_attr.leftBanner = lb;
            spec_attr.leftBannerUrlLink = encodeURIComponent(lbUrlLink);
            spec_attr.leftBannerOpennew = lbOpennew;
            spec_attr.leftBannerLink = encodeURIComponent(lbLink);
            spec_attr.leftAdv = la;
            spec_attr.leftAdvUrlLink = encodeURIComponent(laUrlLink);
            spec_attr.leftAdvOpennew = laOpennew;
            spec_attr.leftAdvName = laName;
            spec_attr.leftAdvLink = encodeURIComponent(laLink);
            Ajax.call(__URL(MAIN + "/config/homeBannerResponse"), "diff=" + diff + "&mode=" + mode + "&spec_attr=" + $.toJSON(spec_attr), shopBannerResponse, 'POST', 'text');
        }

        function shopBannerResponse(data){
            $('.yc_text_html').html(data);
            var res = $.trim($('.h-banner-json').html());
            res = JSON.parse(res);
            var obj = $(".demo *[data-mode=" + res.mode + "][data-diff=" + res.diff + "]");
            var t = obj.find('[data-type="range"]');
            t.attr("id", res.mode + "_" + res.diff);
            t.html(data);

            t.find(".spec").remove();
            t.append("<div class='spec' data-spec='" + res.spec_attr + "'></div>");
            $('.h-banner-json').remove();
            visual();
        }


        //根据cookie默认选中图片库筛选方式
    };
    /* 首页导航编辑 */
    homenavigatorResponse = function (result) {
        //平台可视化 导航编辑弹出窗口
        dialogs("分类导航", result, '950px', '', function () {
            var res = $('.nav-mode-json').html();
            res = JSON.parse($.trim(res))
            navigator_homeback(res.mode)
            $('.nav-mode-json').remove()
        });

        /* 首页可视化 导航回调函数 */
        function navigator_homeback(mode) {
            var spec_attr = new Object(), navname = [],navurl = [], opennew = [], navid = [],obj = $("#homemenuNavs");
            showcat = 0;
            slide = 0;
            showcat = obj.find("input[name='showcat']:checked").val();
            slide = obj.find("input[name='slide']:checked").val();
            spec_attr.showcat = showcat;
            spec_attr.slide = slide;
            //图片路径
            var n = '1';
            obj.find("input[name='navname[]']").each(function () {
                var name = $(this).val();
                if (name == '') {
                    n = '0';
                }
                navname.push(name);
            });
            if (n == '0') {
                layer.msg('导航名称必须填写', {icon: 2, time: 1000});
                return false;
            }
            //导航链接
            obj.find("input[name='navurl[]']").each(function () {
                var url = $(this).val();
                navurl.push(url);
            });
            //导航id
            obj.find("input[name='navid[]']").each(function () {
                var id = $(this).val();
                navid.push(id);
            });

            obj.find("input[name='opennew[]']").each(function () {
                if ($(this)[0].checked === true) {
                    opennew.push(1);
                } else {
                    opennew.push(0);
                }
            });
            spec_attr.navname = navname;
            spec_attr.navurl = encodeURIComponent(navurl);
            spec_attr.opennew = opennew;
            spec_attr.navid = navid;
            Ajax.call(__URL(MAIN + "/config/navmodeback"), "spec_attr=" + encodeURIComponent($.toJSON(spec_attr)) + "&mode=" + mode + "&code=" + suffix + "&template_type=" +temp_type, addnavigatorResponse, 'POST', 'text');
        }

        function addnavigatorResponse(result) {
            var data = result;
            $('.yc_text_html').html(data)
            var res = $.trim($('.navigator-home-json').html());
            res = JSON.parse(res);
            var obj = $("*[data-mode=" + res.mode + "]").find('[data-type="range"]');
            obj.html(result);
            obj.find(".spec").remove();
            obj.append("<div class='spec' data-spec='" + res.spec_attr + "'></div>");
            $('.navigator-home-json').remove();
            visual(1);
        }
    },
    /* 首页头部编辑 */
    homeheaderResponse = function (result) {
            //首页头部弹出窗口
           dialogs("首页头部", result, '850px','',function () {
                var res = $('.homeheader-mode-json').html();
               res = JSON.parse(res);
               if(home_header_back(res.mode)==0){
                   return false;
               }
               $('.homeheader-mode-json').remove()

            });
            /* 首页头部回调函数 */
            function home_header_back(mode) {
                var spec_attr = new Object(),obj = $("#homeheaderInsert"),copylink = [],copyname = [],opennew = [];
                var showkeyword = 0;
                showkeyword = obj.find("input[name='showkeyword']:checked").val();
                spec_attr.showkeyword = showkeyword;
                //图片链接
                var n = '1';
                obj.find("input[name='copylink[]']").each(function () {
                    var name = $(this).val();
                    copylink.push(name);
                });

                obj.find("input[name='copyname[]']").each(function () {
                    var psrc = $(this).val();
                    if(psrc==''){
                        n = '0';
                    }
                    copyname.push(psrc);
                });
                if(n=='0'){
                    layer.msg('关键词必须填写');
                    return 0;
                }
                obj.find("input[name='opennew[]']").each(function () {
                    if($(this)[0].checked===true){
                        opennew.push(1);
                    }else{
                        opennew.push(0);
                    }
                });
                //图片路径
                spec_attr.copylink = copylink;
                spec_attr.copyname = copyname;
                spec_attr.opennew = opennew;
                Ajax.call(__URL(MAIN + "/config/homeheadermodeback"), "spec_attr=" + encodeURIComponent($.toJSON(spec_attr)) + "&mode=" + mode, addHomeHeaderResponse, 'POST', 'text');
            }

            function addHomeHeaderResponse(result) {
                var data = result;
                $('.yc_text_html').html(data)
                var res = $.trim($('.homeheader-home-json').html());
                res = JSON.parse(res);
                var obj = $("*[data-mode=" + res.mode + "]").find('.quickSearch');
                obj.html(result);
                obj.find(".spec").remove();
                obj.append("<div class='spec' data-spec='" + res.spec_attr + "'></div>");
                visual(5);
                $('.homeheader-home-json').remove();
            }
        };
    /* 店铺导航编辑 */
    navigatorResponse = function (result) {
        //平台可视化 导航编辑弹出窗口
        dialogs("分类导航", result, '950px', '', function () {
            var res = $('.nav-mode-json').html();
            res = JSON.parse($.trim(res))
            navigator_back(res.mode)
            $('.nav-mode-json').remove()
        });

        /* 首页可视化 导航回调函数 */
        function navigator_back(mode) {
            var spec_attr = new Object(), navname = [], navurl = [], opennew = [], navid = [],obj = $("#menuNavs");
            //图片路径
            var n = '1';
            obj.find("input[name='navname[]']").each(function () {
                var name = $(this).val();
                if (name == '') {
                    n = '0';
                }
                navname.push(name);
            });
            if (n == '0') {
                layer.msg('导航名称必须填写', {icon: 2, time: 1000});
                return false;
            }
            //导航链接
            obj.find("input[name='navurl[]']").each(function () {
                var url = $(this).val();
                navurl.push(url);
            });
            //导航id
            obj.find("input[name='navid[]']").each(function () {
                var id = $(this).val();
                navid.push(id);
            });

            obj.find("input[name='opennew[]']").each(function () {
                if ($(this)[0].checked === true) {
                    opennew.push(1);
                } else {
                    opennew.push(0);
                }
            });
            spec_attr.navname = navname;
            spec_attr.navurl = encodeURIComponent(navurl);
            spec_attr.opennew = opennew;
            spec_attr.navid = navid;
            Ajax.call(__URL(MAIN + "/config/navmodeback"), "spec_attr=" + encodeURIComponent($.toJSON(spec_attr)) + "&mode=" + mode + "&code=" + suffix + "&template_type=" +temp_type, addnavigatorResponse, 'POST', 'text');
        }

        function addnavigatorResponse(result) {
            var data = result;
            $('.yc_text_html').html(data)
            var res = $.trim($('.navigator-home-json').html());
            res = JSON.parse(res);
            var obj = $("*[data-mode=" + res.mode + "]").find('[data-type="range"]');
            obj.html(result);
            obj.find(".spec").remove();
            obj.append("<div class='spec' data-spec='" + res.spec_attr + "'></div>");
            $('.navigator-home-json').remove();
            visual(1);
        }
    },
    /* 多图、底部广告、顶部广告店铺轮播 */
    query_banner = function (result) {
        dialogs("广告图片", result, "960px", "", function () {
            var obj = $("#bannerEdit"), required = obj.find("*[ectype='required']");
            var res = $.trim($('.shop_banner_json').html());
            if (validation(required) == true) {
                res = JSON.parse(res);
                if (addshop_banner("#bannerEdit", res.mode, res.diff) !== false) {
                    $('.shop_banner_json').remove();
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }

        });
        function addshop_banner(obj, mode, diff) {
            var spec_attr = new Object(), pic_src = [], link = [], opennew = [], pic_link = [];
            var obj = $(obj);
            //图片路径
            var n = '1';
            obj.find("input[name='pic_src[]']").each(function () {
                var psrc = $(this).val();
                if (psrc == '') {
                    n = '0';
                }
                pic_src.push(psrc);
            });
            if (n == '0') {
                layer.msg('请选择图片', {icon: 2, time: 1000});
                return false;
            }
            //图片链接
            obj.find("input[name='link[]']").each(function () {
                var plink = $(this).val();
                link.push(plink);
            });
            //图片地址链接
            obj.find("input[name='pic_link[]']").each(function () {
                var piclink = $(this).val();
                pic_link.push(piclink);
            });
            if(mode=='topBanner'){
                if(obj.find("input[name='opennew[]']:checked").val()){
                    opennew.push(1);
                }else{
                    opennew.push(0);
                }
            }else{
                obj.find("input[name='opennew[]']").each(function () {
                    if ($(this)[0].checked === true) {
                        opennew.push(1);
                    } else {
                        opennew.push(0);
                    }
                });
            }

            if(mode=='lunbo'){
                if (pic_src.length < 2 || pic_src.length > 8) {
                    layer.msg('轮播图最少上传两张图片，最多八张', {icon: 2, time: 1000});
                    return false;
                }
            }
            if ($("*[data-mode=" + mode + "]").data('li')) {
                spec_attr.is_li = $("*[data-mode=" + mode + "]").data('li');
            } else {
                spec_attr.is_li = 0;
            }
            var is_show = obj.find("input[name='is_show']:checked").val();
            if(is_show === undefined){
                is_show = 0;
            }
            spec_attr.is_show = is_show;
            spec_attr.pic_src = pic_src;
            spec_attr.link = encodeURIComponent(link);
            spec_attr.opennew = opennew;
            spec_attr.pic_link = encodeURIComponent(pic_link);
            Ajax.call(__URL(MAIN + "/config/addmodule"), "diff=" + diff + "&mode=" + mode + "&spec_attr=" + $.toJSON(spec_attr), addmoduleResponse, 'POST', 'text');
        }

        function addmoduleResponse(data) {
            var type = '', obj = '', range = '';
            var content = data;
            $('.yc_text_html').html(content);
            var res = $.trim($('.img_list_json').html());
            res = JSON.parse(res);
            if (res.mode == "topBanner") {
                obj = $("*[data-mode='topBanner']");
                range = obj.find("*[data-type='range']");
                type = 2;
            } else if (res.mode == "bottomBanner") {
                obj = $("*[data-mode='bottomBanner']");
                range = obj.find("*[data-type='range']");
                type = 3;
            } else {
                obj = $(".demo *[data-mode=" + res.mode + "][data-diff=" + res.diff + "]");
                range = obj.find("*[data-type='range']");

            }
            range.html(content);
            range.siblings(".spec").remove();
            range.after("<div class='spec' data-spec='" + res.spec_attr + "'>");
            $('.img_list_json').remove();
            visual(type);
        }


        //根据cookie默认选中图片库筛选方式
    };
    /* 单图广告 */
    single_banner = function (result) {
        dialogs("单图广告", result, "960px", "", function () {
            var obj = $("#singleBanner"), required = obj.find("*[ectype='required']");
            var res = $.trim($('.single_banner_json').html());
            if (validation(required) == true) {
                res = JSON.parse(res);
                if (addsingle_banner("#singleBanner", res.mode, res.diff) !== false) {
                    $('.single_banner_json').remove();
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }

        });
        function addsingle_banner(obj, mode, diff) {
            var spec_attr = new Object();
            var obj = $(obj);
            var pic_src = obj.find("input[name='pic_src']").val(), link = obj.find("input[name='link']").val(), opennew = obj.find("input[name='opennew']:checked").val(), pic_link = obj.find("input[name='pic_link']").val();
            //图片路径
            if (pic_src === '') {
                layer.msg('请选择图片', {icon: 2, time: 1000});
                return false;
            }
            spec_attr.pic_src = pic_src;
            spec_attr.link = encodeURIComponent(link);
            spec_attr.opennew = opennew;
            spec_attr.pic_link = encodeURIComponent(pic_link);
            Ajax.call(__URL(MAIN + "/config/addsinglebanner"), "diff=" + diff + "&mode=" + mode + "&spec_attr=" + $.toJSON(spec_attr), addSingleBannerResponse, 'POST', 'text');
        }

        function addSingleBannerResponse(data) {
            var type = '', obj = '', range = '';
            var content = data;
            $('.yc_text_html').html(content);
            var res = $.trim($('.img_list_json').html());
            res = JSON.parse(res);
            obj = $(".demo *[data-mode=" + res.mode + "][data-diff=" + res.diff + "]");
            range = obj.find("*[data-type='range']");
            range.html(content);
            range.siblings(".spec").remove();
            range.after("<div class='spec' data-spec='" + res.spec_attr + "'>");
            $('.img_list_json').remove();
            visual(type);
        }



        //根据cookie默认选中图片库筛选方式
    };
    /* 楼层 */
    floorResponse = function (result) {
        //楼层编辑弹出窗口
        dialogs("主推楼层", result, "950px", "", function () {
            var res = $.trim($('.h-result-json').html());
            res = JSON.parse(res);
            var obj = $("#mainFloor"), required = obj.find("*[ectype='required']");
            if (validation(required) == true) {
                if (floorInsert(res.mode, res.diff)!== false) {
                    $('.h-result-json').remove();
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        });

        //回调函数
        function floorInsert(mode, diff) {
            var spec_attr = new Object(),
                lb = [], lbOpennew = [], lbUrlLink = [], lbLink = [],
                lcOpennew = [], lcName = [],lcLink = [],
                la = [], laOpennew = [], laUrlLink = [], laLink = [];
            var obj = $("#mainFloor");
            var floor_title = obj.find("input[name=floor_title]").val();
            var typeColor = obj.find("input[name='typeColor']").val();
            var floorMode = obj.find("input[name='floorMode']:checked").val();
            //分类链接和名称
            obj.find("input[name='lcName[]']").each(function () {
                lcName.push($(this).val());
            });
            obj.find("input[name='lcLink[]']").each(function () {
                lcLink.push($(this).val());
            });
            obj.find("input[name='lcOpennew[]']").each(function () {
                if ($(this)[0].checked === true) {
                    lcOpennew.push(1);
                } else {
                    lcOpennew.push(0);
                }
            });
            //图片路径
            if(floor_title===''){
                layer.msg('请填写楼层标题', {icon: 2, time: 1000},function(){
                    obj.find("input[name=floor_title]").focus();
                });
                return false;
            }
            
            var n = '1';
            obj.find("input[name='leftBanner[]']").each(function () {
                if ($(this).val() === '') {
                    n = '0';
                }
                lb.push($(this).val());
            });
            if (n === '0') {
                layer.msg('请选择轮播图片', {icon: 2, time: 1000});
                return false;
            }
            if(lb.length===0){
                layer.msg('请至少添加一张轮播图片', {icon: 2, time: 1000});
                return false;
            }
            //图片链接
            obj.find("input[name='leftBannerUrlLink[]']").each(function () {
                lbUrlLink.push($(this).val());
            });
            //图片链接
            obj.find("input[name='leftBannerLink[]']").each(function () {
                lbLink.push($(this).val());
            });
            obj.find("input[name='leftBannerOpennew[]']").each(function () {
                if ($(this)[0].checked === true) {
                    lbOpennew.push(1);
                } else {
                    lbOpennew.push(0);
                }
            });
            var m = '1';
            obj.find("input[name='leftAdv[]']").each(function () {
                if ($(this).val() === '') {
                    m = '0';
                }
                la.push($(this).val());
            });
//            if (m === '0') {
//                layer.msg('请完善展位内容图片', {icon: 2, time: 1000});
//                return false;
//            }
            //图片链接
            obj.find("input[name='leftAdvUrlLink[]']").each(function () {
                laUrlLink.push($(this).val());
            });
            //图片链接
            obj.find("input[name='leftAdvLink[]']").each(function () {
                laLink.push($(this).val());
            });
            obj.find("input[name='leftAdvOpennew[]']").each(function () {
                if ($(this)[0].checked === true) {
                    laOpennew.push(1);
                } else {
                    laOpennew.push(0);
                }
            });
            spec_attr.lcLink = encodeURIComponent(lcLink);
            spec_attr.lcName = lcName;
            spec_attr.lcOpennew = lcOpennew;
            spec_attr.floor_title = floor_title;
            spec_attr.typeColor = typeColor;
            spec_attr.floorMode = floorMode;
            spec_attr.leftBanner = lb;
            spec_attr.leftBannerUrlLink = encodeURIComponent(lbUrlLink);
            spec_attr.leftBannerOpennew = lbOpennew;
            spec_attr.leftBannerLink = encodeURIComponent(lbLink);
            spec_attr.leftAdv = la;
            spec_attr.leftAdvUrlLink = encodeURIComponent(laUrlLink);
            spec_attr.leftAdvOpennew = laOpennew;
            spec_attr.leftAdvLink = encodeURIComponent(laLink);
            Ajax.call(__URL(MAIN + "/config/homefloorresponse"), "diff=" + diff + "&mode=" + mode + "&spec_attr=" + $.toJSON(spec_attr), homefloorResponse, 'POST', 'text');
        }
        function homefloorResponse(data){
            $('.yc_text_html').html(data);
            var res = $.trim($('.h-promp-json').html());
            res = JSON.parse(res);
            var obj = $(".demo *[data-mode=" + res.mode + "][data-diff=" + res.diff + "]");
            var t = obj.find('[data-type="range"]');
            t.attr("id", res.mode + "_" + res.diff);
            t.html(data);

            t.find(".spec").remove();
            t.append("<div class='spec' data-spec='" + res.spec_attr + "'></div>");
            $('.h-promp-json').remove();
            visual();
        }
    };
    /* 热门活动 */
    hotResponse = function (result) {
        //热门活动弹出窗口
        dialogs("热门活动", result, "950px", "", function () {
            var res = $.trim($('.h-result-json').html());
            res = JSON.parse(res);
            var obj = $("#" + result.mode),required = obj.find("*[ectype='required']");
            if (validation(required) == true) {
                responseInsert(res.mode, res.diff);
                return true;
            } else {
                return false;
            }
            $('.h-result-json').remove()
        });
        //回调函数
        function responseInsert(mode, diff) {
            var actionUrl = '', obj = '', t = '';
            actionUrl = __URL(MAIN + "/config/homeadvinsert");
            $("#" + mode + "Insert").ajaxSubmit({
                type: "POST",
                dataType: "text",
                url: actionUrl,
                data: {"action": "TemporaryImage"},
                success: function (data) {
                    if (data.error == 1) {
                        layer.msg(data.massege,{icon:2,time:1000});
                        return false;
                    } else {
                        obj = $(".demo *[data-mode=" + mode + "][data-diff=" + diff + "]");
                        t = obj.find('[data-type="range"]');
                        t.attr("id", mode + "_" + diff);
                        t.html(data);
                        if ($('div').is('.h-hot-json')) {
                            var res = $.trim($('.h-hot-json').html());
                        }
                        res = JSON.parse(res);
                        t.find(".spec").remove();
                        t.append("<div class='spec' data-spec='" + $.toJSON(res.spec_attr) + "' data-title='" + res.masterTitle + "'></div>");
                        $('.h-hot-json').remove();
                    }
                    visual();
                },
                async: true
            });
        }
    };
    /* 精选好店 */
    shopResponse = function (result) {
        //精选好店弹出窗口
        dialogs("精选好店", result, "950px", "", function () {
            var res = $.trim($('.h-result-json').html());
            res = JSON.parse(res);
            var obj = $("#" + result.mode),required = obj.find("*[ectype='required']");
            if (validation(required) == true) {
                responseInsert(res.mode, res.diff);
                return true;
            } else {
                return false;
            }
            $('.h-result-json').remove()
        });
        //回调函数
        function responseInsert(mode, diff) {
            var actionUrl = '', obj = '', t = '';
                actionUrl = __URL(MAIN + "/config/homeshop");
            $("#" + mode + "Insert").ajaxSubmit({
                type: "POST",
                dataType: "text",
                url: actionUrl,
                data: {"action": "TemporaryImage"},
                success: function (data) {
                    if (data.error == 1) {
                        layer.msg(data.massege,{icon:2,time:1000});
                        return false;
                    } else {
                        $('.yc_text_html').html(data);
                        obj = $(".demo *[data-mode=" + mode + "][data-diff=" + diff + "]");
                        t = obj.find('[data-type="range"]');
                        t.attr("id", mode + "_" + diff);
                        t.html(data);
                        if ($('div').is('.h-promp-json')) {
                            var res = $.trim($('.h-promp-json').html());
                        }
                        res = JSON.parse(res);
                        t.find(".spec").remove();
                        t.append("<div class='spec' data-spec='" + $.toJSON(res.spec_attr) + "' data-title='" + res.masterTitle + "'></div>");
                        if (res.lift) {
                            obj.find('[data-type="range"]').attr("data-lift", res.lift);
                        }
                        $('.h-promp-json').remove();
                    }
                    visual();
                },
                async: true
            });
        }
    };
    /* 商品推荐 */
    query_goods = function (result) {
        //商品编辑弹出窗口
        dialogs("商品推荐", result, "950px", "", function () {
            var res = $.trim($('.h-result-json').html());
            res = JSON.parse(res);
            var obj = $("#goodsRecom"), required = obj.find("*[ectype='required']");
            if (validation(required) == true) {
                if (replace_goods(res.mode, res.diff, obj) !== false) {
                    $('.h-result-json').remove();
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        });

        function replace_goods(mode, diff, obj) {
            var spec_attr = new Object(),lift = "";
            spec_attr.goods_ids = obj.find("input[name='goods_ids']").val();
            spec_attr.cat_name = obj.find("input[name='cat_name']").val();
            spec_attr.rec_type = obj.find("input[name='rec_type']:checked").val();
            spec_attr.sort = obj.find("select[name='sort']").val();
            spec_attr.count = obj.find("select[name='count']").val();
            lift = obj.find("input[name='lift']").val();
            Ajax.call(__URL(MAIN + "/config/changedgoods"), "temp=goodsRecom&spec_attr=" + $.toJSON(spec_attr) + "&diff=" + diff + "&mode=" + mode + "&lift=" + lift, replaceResponse, 'POST', 'text');
        }

        /* 首页可视化 商品回调 */
        function replaceResponse(data) {
            $('.yc_text_html').html(data);
            var res = $.trim($('.h-guess-json').html());
            res = JSON.parse(res);
            var obj = $(".demo *[data-mode=" + res.mode + "][data-diff=" + res.diff + "]"),
                goodsTitle = obj.find("*[data-goodsTitle='title']"),
                range = obj.find("*[data-type='range']");
            if(res.cat_name!==''){
                goodsTitle.show();
                goodsTitle.html("<h3>" + res.cat_name + "</h3>");
            }else{
                goodsTitle.hide();
            }

            //替换楼层内容
            range.find("ul").html(data);
            range.find(".spec").remove();
            range.append("<div class='spec' data-spec='" + res.spec_attr + "'></div>");

            if (res.lift) {
                range.attr("data-lift", res.lift);
            }
            if(res.rec_type === 1){
                //自动推荐，前端异步加载商品使用的参数
                range.attr("data-rec_type", res.rec_type);
                range.attr("data-count", res.count);
                range.attr("data-sort", res.sort);
            }
            $('.h-guess-json').remove();
            //页面储存商品id，前台异步用
            obj.attr("data-goodsid", res.goods_ids);
            visual();
        }
    };
    /* 客服中心 */
    serviceResponse = function (result) {
        dialogs("客服中心", result, "850px", "", function () {
            var res = $.trim($('.service-mode-json').html());
            res = JSON.parse(res);
            if (service_back(res.mode, res.diff) !== false) {
                $('.service-mode-json').remove();
                return true;
            } else {
                return false;
            }
        });
        /* 客服中心回调函数 */
        function service_back(mode, diff) {
            var spec_attr = new Object(), obj = $("#service"), names = [], pic_src = [], qqs = [];
            var checkService = $("#service").valid();
            if(!checkService){
                $('form').find('.error[aria-required]')[0].focus();
                return false;
            }
            var trLength = obj.find('tbody tr').length;
            if(trLength=='0'){
                $('#subtitle-error').show();
                return false;
            }
            //图片链接
            obj.find("input[name='name[]']").each(function () {
                var name = $(this).val();
                names.push(name);
            });

            obj.find("input[name='servicepic[]']").each(function () {
                var psrc = $(this).val();
                pic_src.push(psrc);
            });
            obj.find(".J-qq").each(function () {
                var qq = $(this).val();
                qqs.push(qq);
            });

            //图片路径
            spec_attr.name = names;
            spec_attr.servicepic = pic_src;
            spec_attr.qq = qqs;
            spec_attr.title = obj.find("input[name='title']").val();
            spec_attr.subtitle = obj.find("input[name='subtitle']").val();
            spec_attr.time = obj.find("input[name='time']").val();
            Ajax.call(__URL(MAIN + "/config/servicemodeback"), "spec_attr=" + encodeURIComponent($.toJSON(spec_attr)) + "&mode=" + mode + "&diff=" + diff, addserviceResponse, 'POST', 'text');
        }

        function addserviceResponse(result) {
            var data = result;
            $('.yc_text_html').html(data);
            var res = $.trim($('.service-home-json').html());
            res = JSON.parse(res);
            var obj = $("*[data-mode=" + res.mode + "][data-diff=" + res.diff + "]").find('[data-type="range"]');
            obj.html(result);
            obj.find(".spec").remove();
            obj.append("<div class='spec' data-spec='" + res.spec_attr + "'></div>");
            $('.service-home-json').remove();
            visual();
        }
    };
    /* 自定义区 */
    customResponse = function (result) {
        dialogs("自定义区", result, "1000px", "", function () {
            var obj = $("#custom");
            var res = $.trim($('.custom-json').html());
            res = JSON.parse(res);
            if (custom_back(res.mode, res.diff, obj) !== false) {
                $('.custom-json').remove();
                return true;
            } else {
                return false;
            }
        });

        function custom_back(mode, diff, obj) {
            var custom_content = obj.find("input[name='custom_content']").val(),
                lift = obj.find("input[name='lift']").val(),
                range = $("*[data-mode=" + mode + "][data-diff=" + diff + "]").find('[data-type="range"]');

            if (lift) {
                range.attr("data-lift", lift);
            }
            range.html(custom_content);
            visual();
        }
    };
    /* 帮助中心区 */
    helpResponse = function (result) {
        //平台可视化 导航编辑弹出窗口
        dialogs("帮助中心", result,'850px','', function () {
            var res = $('.help-mode-json').html();
            res = JSON.parse($.trim(res));
            help_back(res.mode);
            $('.help-mode-json').remove()
        });

        /* 帮助中心回调函数 */
        function help_back(mode) {
            var spec_attr = new Object(),obj = $("#helpInsert"),pic_src = [],articleclass = [],childcount = [],articlesort = [];
            //图片路径
            obj.find("input[name='qrcode[]']").each(function () {
                var psrc = $(this).val();
                pic_src.push(psrc);
            });

            //图片链接
            obj.find("select[name='articleclass[]']").each(function () {
                var class_id = $(this).val();
                articleclass.push(class_id);
            });
            obj.find("input[name='childcount[]']").each(function () {
                var count = $(this).val();
                childcount.push(count);
            });
            obj.find("select[name='articlesort[]']").each(function () {
                var sort = $(this).val();
                articlesort.push(sort);
            });
            //图片路径
            spec_attr.articleclass = articleclass;
            spec_attr.childcount = childcount;
            spec_attr.articlesort = articlesort;
            spec_attr.phone = obj.find("input[name='phone']").val();
            spec_attr.is_show = obj.find("input[name='is_show']:checked").val();
            spec_attr.email = obj.find("input[name='email']").val();
            spec_attr.pic_src = pic_src;
            Ajax.call(__URL(MAIN + "/config/helpmodeback"), "spec_attr=" + encodeURIComponent($.toJSON(spec_attr)) + "&mode=" + mode, addhelpResponse, 'POST', 'text');
        }

        function addhelpResponse(result) {
            var data = result;
            $('.yc_text_html').html(data)
            var res = $.trim($('.help-home-json').html());
            res = JSON.parse(res);
            var obj = $("*[data-mode=" + res.mode + "]").find('[data-type="range"]');
            obj.html(result);
            obj.find(".spec").remove();
            obj.append("<div class='spec' data-spec='" + res.spec_attr + "'></div>");
            visual(3);
            $('.help-home-json').remove();
        }
    };
    /* 友情链接区 */
    linkResponse = function (result) {
            //友情链接弹出窗口
           dialogs("友情链接", result, '850px','',function () {
                var res = $('.link-mode-json').html();
               res = JSON.parse(res);
               if(link_back(res.mode)==0){
                   return false;
               }
               $('.link-mode-json').remove()

            });
            /* 友情链接回调函数 */
            function link_back(mode) {
                var spec_attr = new Object(),obj = $("#linkInsert"),copylink = [],copyname = [],opennew = [];

                //图片链接
                var n = '1';
                obj.find("input[name='copylink[]']").each(function () {
                    var name = $(this).val();
                    copylink.push(name);
                });

                obj.find("input[name='copyname[]']").each(function () {
                    var psrc = $(this).val();
                    if(psrc==''){
                        n = '0';
                    }
                    copyname.push(psrc);
                });
                if(n=='0'){
                    layer.msg('名称必须填写');
                    return 0;
                }
                obj.find("input[name='opennew[]']").each(function () {
                    if($(this)[0].checked===true){
                        opennew.push(1);
                    }else{
                        opennew.push(0);
                    }
                });
                if(copyname.length<1){
                    layer.msg('至少填写一个友情链接');
                    return 0;
                }
                //图片路径
                spec_attr.copylink = copylink;
                spec_attr.copyname = copyname;
                spec_attr.opennew = opennew;
                spec_attr.is_show = obj.find("input[name='is_show']:checked").val();
                Ajax.call(__URL(MAIN + "/config/linkmodeback"), "spec_attr=" + encodeURIComponent($.toJSON(spec_attr)) + "&mode=" + mode, addlinkResponse, 'POST', 'text');
            }

            function addlinkResponse(result) {
                var data = result;
                $('.yc_text_html').html(data)
                var res = $.trim($('.link-home-json').html());
                res = JSON.parse(res);
                var obj = $("*[data-mode=" + res.mode + "]").find('[data-type="range"]');
                obj.html(result);
                obj.find(".spec").remove();
                obj.append("<div class='spec' data-spec='" + res.spec_attr + "'></div>");
                visual(3);
                $('.link-home-json').remove();
            }
        };
    /* 版权信息区 */
    copyResponse = function (result) {
        //版权信息编辑弹出窗口
        dialogs("版权信息", result, '850px', '', function () {
            var res = $('.copy-mode-json').html();
            res = JSON.parse($.trim(res));
            copy_back(res.mode);
            $('.copy-mode-json').remove()
        });

        /* 版权信息回调函数 */
        function copy_back(mode) {
            var spec_attr = new Object(),obj = $("#copyInsert"),copylink = [],pic_src = [],opennew = [];
            //图片链接
            obj.find("input[name='link[]']").each(function () {
                var name = $(this).val();
                copylink.push(name);
            });
            obj.find("input[name='pic_src[]']").each(function () {
                var psrc = $(this).val();
                pic_src.push(psrc);
            });
            obj.find("input[name='opennew[]']").each(function () {
                if($(this)[0].checked===true){
                    opennew.push(1);
                }else{
                    opennew.push(0);
                }
            });
            //图片路径
            spec_attr.is_show = obj.find("input[name='is_show']:checked").val();
            spec_attr.copylink = copylink;
            spec_attr.copypic = pic_src;
            spec_attr.opennew = opennew;
            spec_attr.copyright = obj.find("textarea[name='copyright']").val();
            Ajax.call(__URL(MAIN + "/config/copymodeback"), "spec_attr=" + encodeURIComponent($.toJSON(spec_attr)) + "&mode=" + mode, addcopyResponse, 'POST', 'text');
        }

        function addcopyResponse(result) {
            var data = result;
            $('.yc_text_html').html(data)
            var res = $.trim($('.copy-home-json').html());
            res = JSON.parse(res);
            var obj = $("*[data-mode=" + res.mode + "]").find('[data-type="range"]');
            obj.html(result);
            obj.find(".spec").remove();
            obj.append("<div class='spec' data-spec='" + res.spec_attr + "'></div>");
            visual(3);

            $('.copy-home-json').remove();
        }
    };
    /* 侧边栏区 */
    rightResponse = function (result) {
            //侧边栏编辑弹出窗口
            dialogs("右侧导航栏", result, '850px', '', function () {
                var res = $('.right-mode-json').html();
                res = JSON.parse($.trim(res));
                right_back(res.mode);
                $('.right-mode-json').remove()
            });

            /* 侧边栏回调函数 */
            function right_back(mode) {
                var spec_attr = new Object(),obj = $("#rightInsert"),tools = [];
                //工具栏
                obj.find("input[name='tool']:checked").each(function () {
                    var tool = $(this).val();
                    tools.push(tool);
                });
                spec_attr.tools = tools;
                Ajax.call(__URL(MAIN + "/config/rightmodeback"), "spec_attr=" + encodeURIComponent($.toJSON(spec_attr)) + "&mode=" + mode, addrightResponse, 'POST', 'text');
            }

            function addrightResponse(result) {
                var data = result;
                $('.yc_text_html').html(data)
                var res = $.trim($('.right-home-json').html());
                res = JSON.parse(res);
                var obj = $("*[data-mode=" + res.mode + "]").find('[data-type="range"]');
                obj.html(result);
                obj.find(".spec").remove();
                obj.append("<div class='spec' data-spec='" + res.spec_attr + "'></div>");
                visual(3);
                $('.right-home-json').remove();
            }
        };
    /* 生成缓存文件 */
    function visual(temp) {
        var content = $(".pc-page").html(),
            content_html = '',
            preview = '',
            nav_content = $("*[ectype='nav']").html(),
            homeheader_content = $("*[ectype='homeheader']").find('.quickSearch').html(),
            topBanner_content = $("*[data-homehtml='topBanner']").html(),
            topBanner = '',
            shopBanner_content = $("*[data-homehtml='shopBanner']").html(),
            shopBanner = '',
            bottom_content = $("*[data-homehtml='bottom']").html(),
            bottom = '',
            navlayout = '',
            homeheaderlayout = '',
            where = '',
            bottom_html = '',
            shopBanner_html = '',
            nav_html = '';
        preview = $("#preview-layout");
        preview.html("");
        preview.append(content);
        preview.find("*[data-homehtml='bottom']").remove();
        content = preview.html();
        if (temp == 1) {
            //导航栏html
            navlayout = $("#head-layout");

            navlayout.html("");
            navlayout.append(nav_content);

            navlayout.find(".categorys").remove();
            navlayout.find(".setup_box").remove();
            content_html = navlayout.html();
        } else if (temp == 2) {
            //导航栏html
            topBanner = $("#topBanner-layout");
            topBanner.html("");
            topBanner.append(topBanner_content);
            topBanner.find(".toppic").remove();
            topBanner.find(".categorys").remove();
            topBanner.find(".setup_box").remove();
            topBanner.find("*[data-html='not']").remove();
            topBanner.find(".ui-draggable").removeClass("ui-draggable");
            topBanner.find(".ui-box-display").removeClass("ui-box-display");
            topBanner.find(".lyrow").removeClass("lyrow");
            content_html = topBanner.html();
            topBanner.html("");
        } else if (temp == 3) {
            bottom = $("#bottom-layout");
            bottom.html("<div data-homehtml='bottom'></div>");
            bottom.find("*[data-homehtml='bottom']").append(bottom_content);
            content_html = bottom.html();
            bottom.find(".setup_box").remove();
            bottom.find("*[data-html='not']").remove();
            bottom.find(".lyrow").removeClass("lyrow");
            bottom.find(".ui-draggable").removeClass("ui-draggable");
            bottom.find(".ui-box-display").removeClass("ui-box-display");
            bottom.find(".lunbotu").removeClass("lunbotu");
            bottom.find(".demo").removeClass().addClass("content");
            bottom.find(".spec").attr("data-spec", '');
            bottom_html = bottom.html();
            bottom.html('');
        } else if (temp == 4) {
            //导航栏html
            shopBanner = $("#shopBanner-layout");
            shopBanner.html("");
            shopBanner.append(shopBanner_content);
            content_html = shopBanner.html();
            shopBanner.find(".categorys").remove();
            shopBanner.find(".setup_box").remove();
            shopBanner.find("*[data-html='not']").remove();
            shopBanner.find(".ui-draggable").removeClass("ui-draggable");
            shopBanner.find(".ui-box-display").removeClass("ui-box-display");
            shopBanner.find(".lyrow").removeClass("lyrow");
            shopBanner.find(".spec").attr("data-spec", '');
            shopBanner_html = shopBanner.html();
            shopBanner.html("");
        } else if (temp == 5) {
            //导航栏html
            homeheaderlayout = $("#homeheader-layout");
            homeheaderlayout.html("");
            homeheaderlayout.append(homeheader_content);
            content_html = homeheaderlayout.html();
        } else {
            //全部内容页html(不包括头部和导航)
            preview = $("#preview-layout");
            bottom = $("#bottom-layout");
            bottom.html("<div data-homehtml='bottom'></div>");
            bottom.find("*[data-homehtml='bottom']").append(bottom_content);
            bottom.find(".setup_box").remove();
            bottom.find("*[data-html='not']").remove();
            bottom.find(".lyrow").removeClass("lyrow");
            bottom.find(".ui-draggable").removeClass("ui-draggable");
            bottom.find(".ui-box-display").removeClass("ui-box-display");
            bottom.find(".lunbotu").removeClass("lunbotu");
            bottom.find(".demo").removeClass().addClass("content");
            bottom.find(".spec").attr("data-spec", '');
            bottom_html = bottom.html();
            bottom.html('');
            shopBanner = $("#shopbanner-layout");
            shopBanner.html("<div data-homehtml='shopbanner'></div>");
            shopBanner.find("*[data-homehtml='shopbanner']").append(shopBanner_content);
            shopBanner.find(".setup_box").remove();
            shopBanner.find("*[data-html='not']").remove();
            shopBanner.find(".lyrow").removeClass("lyrow");
            shopBanner.find(".ui-draggable").removeClass("ui-draggable");
            shopBanner.find(".ui-box-display").removeClass("ui-box-display");
            shopBanner.find(".lunbotu").removeClass("lunbotu");
            shopBanner.find(".demo").removeClass().addClass("content");
            shopBanner.find(".spec").attr("data-spec", '');
            shopBanner_html = shopBanner.html();
            shopBanner.html('');
            preview.html("");
            preview.append(content);
            preview.find("*[data-html='not']").remove();
            preview.find(".lyrow").removeClass("lyrow");
            preview.find(".ui-draggable").removeClass("ui-draggable");
            preview.find(".custom .default").removeClass("default");
            preview.find(".ui-box-display").removeClass("ui-box-display");
            preview.find(".lunbotu").removeClass("lunbotu");
            preview.find(".J-home").removeClass().addClass("content").addClass("J-home");
            preview.find(".demo").removeClass().addClass("content");
            preview.find(".spec").attr("data-spec", '');
            preview.find("*[data-homehtml='bottom']").remove();
            preview.find("*[data-homehtml='shopBanner']").remove();
            preview.find(".J-home").parent().remove();
            preview.find(".navs").remove();
            preview.find(".hd_main").remove();
            preview.find(".setup_box").remove();
            content_html = preview.html();
        }
        Ajax.call(__URL(MAIN + "/config/fileputvisual"), "content=" + encodeURIComponent(content) + "&content_html=" + encodeURIComponent(content_html) + "&bottom_html= " + encodeURIComponent(bottom_html) + "&shopbanner_html= " + encodeURIComponent(shopBanner_html) + "&suffix=" + suffix + "&temp=" + temp + "&temp_type=" + temp_type, file_put_visualResponse, 'POST', 'JSON');
        //回调函数
        function file_put_visualResponse(result) {
            if (result.error == 0) {
                suffix_obj.val(result.suffix);
                $(".J-back").show();
                return true;
            } else {
                layer.msg("该模板不存在，请重试");
                return false;
            }
        }
    }
    /**
     * dialogs模态框
     * @param    {
     *      element    :   点击dom string
     *      titles     :   模态框标题
     *      dialogEle  :   模态框的内容：#id
     *      width      ：  模态框的宽
     *      height      ：  模态框的高
     * }
     * @callback
     */
    function dialogs(titles, dialogEle, width, height, callback, callbackopen) {
        layer.open({
            type: 1,
            title: [titles, "font-weight:bold"],
            skin: "layui-layer-rim", //加上边框
            area: [width, height], //宽高
            content: dialogEle,
            btn: ["确定", "取消"],
            btnAlign: "c",
            // 点击确定的回调
            yes: function (index, layero) {
                if(!callback || callback()!==false){
                    layer.close(index);
                }

            },
            // 弹出框出现的回调
            success: function (layero, index) {
                callbackopen && callbackopen();
            }
        });
    }
    ;


    /* 弹窗验证 */
    function validation(required) {
        var val = "";
        var msg = "";
        var flog = true;
        required.each(function () {
            val = $(this).val();
            msg = $(this).data("msg");
            if (val == "") {
                layer.msg(msg, {icon: 2, time: 1000});
                flog = false;
                return false;
            } else {
                flog = true;
            }
        });
        return flog;
    }
});










