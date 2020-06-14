define(['utilAdmin','layer','jquery-ui1','transport_jquery','jqueryForm'], function (utilAdmin,layer) {
    var modal = {};
    modal.init=function(){

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
        var banner = $('.J-banner').html();
        if(banner!=='undefined'){
            $("*[data-homehtml='shopBanner']").find('.banner-box').html(banner);
        }
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
                } else if (ban) {
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
    });
    //头部广告删除
    $(document).on("click", "*[ectype='model_delete']", function () {
        var _this = $(this);
        var suffix = $('#code').val();
        var type = $('#type').val();
        if (confirm("确定删除此广告么？删除后前台不显示只能后台编辑，且不可找回！")) {
            $.ajax({
                type: "post",
                url: __URL(MAIN + "/shop/deletepccustomtemplatetop"),
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
        if (confirm("确定删除此图片么？删除后前台不显示只能后台编辑，且不可找回！")) {
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
                    url: __URL(MAIN + "/shop/downloadmodal"),
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
                    url: __URL(MAIN + "/shop/backmodal"),
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

        switch (purebox) {
            case "hot":
                //首页广告模块编辑
                masterTitle = lyrow.find('.spec').data('title');
                spec_attr = JSON.stringify(spec_attr);
                Ajax.call(__URL(MAIN + "/shop/hot"), "mode=" + mode + '&spec_attr=' + encodeURIComponent(spec_attr) + "&masterTitle=" + escape(masterTitle) + "&diff=" + diff, hotResponse, 'POST', 'text');

                break;

            case "homeFloor":
                //楼层模块编辑
                spec_attr = JSON.stringify(spec_attr);
                var reg = /&/g;
                if (spec_attr) {
                    spec_attr = spec_attr.replace(reg, '＆');
                }
                Ajax.call(__URL(MAIN + "/shop/homefloor"), 'act=homeFloor' + "&mode=" + mode + '&spec_attr=' + spec_attr + "&diff=" + diff + "&lift=" + lift + "&hierarchy=" + hierarchy, floorResponse, 'POST', 'text');
                break;

            case "cust":
                //自定义模块编辑
                range.find('.ui-box-display').remove();
                var custom_content = encodeURIComponent(range.html());
                Ajax.call(__URL(MAIN + "/shop/custom"), 'mode=' + mode + '&custom_content=' + custom_content + "&diff=" + diff + "&lift=" + lift, customResponse, 'POST', 'text');
                break;

            case "adv":
                //广告模块编辑
                pic_number = lyrow.data("length");
                spec_attr = JSON.stringify(spec_attr);
                var reg = /&/g;
                if (spec_attr) {
                    spec_attr = spec_attr.replace(reg, '＆');
                }
                Ajax.call(__URL(MAIN + "/shop/banner"), "spec_attr=" + spec_attr + "&pic_number=" + pic_number + "&mode=" + mode + "&diff=" + diff, query_banner, 'POST', 'text');
                break;
            case "singleBanner":
                //广告模块编辑
                pic_number = lyrow.data("length");
                spec_attr = JSON.stringify(spec_attr);
                var reg = /&/g;
                if (spec_attr) {
                    spec_attr = spec_attr.replace(reg, '＆');
                }
                Ajax.call(__URL(MAIN + "/shop/singlebanner"), "spec_attr=" + spec_attr + "&pic_number=" + pic_number + "&mode=" + mode + "&diff=" + diff, single_banner, 'POST', 'text');
                break;

            case "nav_mode":
                //导航模板编辑
                spec_attr = JSON.stringify(spec_attr);
                Ajax.call(__URL(MAIN + "/shop/navmode"), 'mode=' + mode + '&template_type=' + temp_type + '&spec_attr=' + encodeURIComponent(spec_attr), navigatorResponse, 'POST', 'text');
                break;
            case "help_mode":
                //导航模板编辑
                spec_attr = JSON.stringify(spec_attr);

                Ajax.call(__URL(MAIN + "/shop/helpmode"), 'mode=' + mode + '&spec_attr=' + encodeURIComponent(spec_attr) + where, helpResponse, 'POST', 'text');
                break;
            case "right_mode":
                //导航模板编辑
                spec_attr = JSON.stringify(spec_attr);

                Ajax.call(__URL(MAIN + "/shop/rightmode"), 'mode=' + mode + '&spec_attr=' + encodeURIComponent(spec_attr) + where, rightResponse, 'POST', 'text');
                break;
            case "link_mode":
                //导航模板编辑
                spec_attr = JSON.stringify(spec_attr);

                Ajax.call(__URL(MAIN + "/shop/linkmode"), 'mode=' + mode + '&spec_attr=' + encodeURIComponent(spec_attr) + where, linkResponse, 'POST', 'text');
                break;
            case "copy_mode":
                //导航模板编辑
                spec_attr = JSON.stringify(spec_attr);

                Ajax.call(__URL(MAIN + "/shop/copymode"), 'mode=' + mode + '&spec_attr=' + encodeURIComponent(spec_attr) + where, copyResponse, 'POST', 'text');
                break;

            case "goods":
                //商品模块编辑
                spec_attr = JSON.stringify(spec_attr);
                Ajax.call(__URL(MAIN + "/shop/goodsinfo"), "mode=" + mode + "&diff=" + diff + "&spec_attr=" + encodeURIComponent(spec_attr) + "&lift=" + lift, query_goods, 'POST', 'text');
                break;

            case "header":
                //店铺可视化头部
                spec_attr = JSON.stringify(spec_attr);
                var custom_content = encodeURIComponent(lyrow.find('.spec').html());
                Ajax.call(__URL(MAIN + "/shop/shopheadermode"), 'mode=' + mode + "&spec_attr=" + encodeURIComponent(spec_attr) + "&custom_content=" + custom_content + "&suffix=" + suffix, headerResponse, 'POST', 'text');
                break;

            case "nav":
                //店铺可视化导航
                spec_attr = JSON.stringify(spec_attr);
                Ajax.call(__URL(MAIN + "/shop/navigator"), 'act=navigator' + '&mode=' + mode + '&spec_attr=' + encodeURIComponent(spec_attr) + "&topic_type=" + topic_type, navigatorResponse, 'POST', 'text');
                break;
            case "service":
                //客服中心编辑
                count = lyrow.data("length");
                spec_attr = JSON.stringify(spec_attr);
                Ajax.call(__URL(MAIN + "/shop/servicemode"), 'mode=' + mode + "&diff=" + diff + '&spec_attr=' + encodeURIComponent(spec_attr) + '&count=' + count, serviceResponse, 'POST', 'text');
                break;
        }
    });
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
            Ajax.call(__URL(MAIN + "/shop/changedgoods"), "temp=goodsRecom&spec_attr=" + $.toJSON(spec_attr) + "&diff=" + diff + "&mode=" + mode + "&lift=" + lift, replaceResponse, 'POST', 'text');
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
    headerResponse = function (result) {
        //店铺可视化头部编辑弹窗
        dialogs("店招", result, "950px", "", function () {
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
    hotResponse = function (result) {
        //热门活动弹出窗口
        dialogs("内容编辑", result, "950px", "", function () {
            var res = $.trim($('.h-result-json').html());
            res = JSON.parse(res);
            var obj = $("#" + res.mode), required = obj.find("*[ectype='required']");
            if (validation(required) == true) {
                if (responseInsert(res.mode, res.diff) !== false) {
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
        function responseInsert(mode, diff) {
            var obj = '', t = '';
            $("#" + mode + "Insert").ajaxSubmit({
                type: "POST",
                dataType: "text",
                url: __URL(MAIN + "/shop/hotinsert"),
                data: {"action": "TemporaryImage"},
                success: function (data) {
                    if (data.error == 1) {
                        layer.msg(data.massege, {icon: 2, time: 1000});
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
                la = [], laOpennew = [], laUrlLink = [], laLink = [];
            var obj = $("#mainFloor");
            var floor_title = obj.find("input[name=floor_title]").val();
            var typeColor = obj.find("input[name='typeColor']").val();
            var floorMode = obj.find("input[name='floorMode']:checked").val();
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
            Ajax.call(__URL(MAIN + "/shop/homefloorresponse"), "diff=" + diff + "&mode=" + mode + "&spec_attr=" + $.toJSON(spec_attr), homefloorResponse, 'POST', 'text');
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
    query_banner = function (result) {
        dialogs("多图轮播", result, "960px", "", function () {
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
                if (psrc == '' && $(this).parents('td').find("input[name='pic_link[]']").val()=='') {
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
            //图片链接
            obj.find("input[name='pic_link[]']").each(function () {
                var piclink = $(this).val();
                pic_link.push(piclink);
            });
            obj.find("input[name='opennew[]']").each(function () {
                if ($(this)[0].checked === true) {
                    opennew.push(1);
                } else {
                    opennew.push(0);
                }
            });
            if (pic_src.length < 2 || pic_src.length > 8) {
                layer.msg('轮播图最少上传两张图片，最多八张', {icon: 2, time: 1000});
                return false;
            }
            if ($("*[data-mode=" + mode + "]").data('li')) {
                spec_attr.is_li = $("*[data-mode=" + mode + "]").data('li');
            } else {
                spec_attr.is_li = 0;
            }
            spec_attr.pic_src = pic_src;
            spec_attr.link = encodeURIComponent(link);
            spec_attr.opennew = opennew;
            spec_attr.pic_link = encodeURIComponent(pic_link);
            Ajax.call(__URL(MAIN + "/shop/addmodule"), "diff=" + diff + "&mode=" + mode + "&spec_attr=" + $.toJSON(spec_attr), addmoduleResponse, 'POST', 'text');
        }

        function addmoduleResponse(data) {
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
            if (pic_src === '' && pic_link === '') {
                layer.msg('请选择图片', {icon: 2, time: 1000});
                return false;
            }
            spec_attr.pic_src = pic_src?pic_src:encodeURIComponent(pic_link);;
            spec_attr.link = encodeURIComponent(link);
            spec_attr.opennew = opennew;
            spec_attr.pic_link = encodeURIComponent(pic_link);
            Ajax.call(__URL(MAIN + "/shop/addsinglebanner"), "diff=" + diff + "&mode=" + mode + "&spec_attr=" + $.toJSON(spec_attr), addSingleBannerResponse, 'POST', 'text');
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

            //图片链接
            obj.find("input[name='name[]']").each(function () {
                var name = $(this).val();
                names.push(name);
            });

            obj.find("input[name='servicepic[]']").each(function () {
                var psrc = $(this).val();
                pic_src.push(psrc);
            });
            var n = true;
            obj.find("input[name='qq[]']").each(function () {
                var qq = $(this).val();
                if (qq === '') {
                    n = false;
                }
                qqs.push(qq);
            });
            if (n === false) {
                layer.msg('请检查客服qq是否全部填写', {icon: 2, time: 1000});
                return false;
            }
            //图片路径
            spec_attr.name = names;
            spec_attr.servicepic = pic_src;
            spec_attr.qq = qqs;
            spec_attr.title = obj.find("input[name='title']").val();
            spec_attr.subtitle = obj.find("input[name='subtitle']").val();
            spec_attr.time = obj.find("input[name='time']").val();
            Ajax.call(__URL(MAIN + "/shop/servicemodeback"), "spec_attr=" + encodeURIComponent($.toJSON(spec_attr)) + "&mode=" + mode + "&diff=" + diff, addserviceResponse, 'POST', 'text');
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
    customResponse = function (result) {
        dialogs("自定义区", result, "1000px", "80%", function () {
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
    navigatorResponse = function (result) {
                //导航编辑弹出窗口
                dialogs("店铺导航", result, "1000px", "", function () {
                    var res = $('.nav-mode-json').html();
                    res = JSON.parse($.trim(res));
                    if (navigator_back(res.mode)!== false) {
                        $('.nav-mode-json').remove();
                        return true;
                    } else {
                        return false;
                    }
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
                    spec_attr.name = navname;
                    spec_attr.url = encodeURIComponent(navurl);
                    spec_attr.opennew = opennew;
                    spec_attr.id = navid;
                    Ajax.call(__URL(MAIN + "/shop/navmodeback"), "spec_attr=" + encodeURIComponent($.toJSON(spec_attr)) + "&mode=" + mode + "&code=" + suffix + "&template_type=" +temp_type, addnavigatorResponse, 'POST', 'text');
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
            };
    /* 生成缓存文件 */
    function visual(temp) {
        var content = $(".pc-page").html(),
                content_html = '',
                preview = '',
                nav_content = $("*[ectype='nav']").html(),
                topBanner_content = $("*[data-homehtml='topBanner']").html(),
                topBanner = '',
                shopBanner_content = $("*[data-homehtml='shopBanner']").html(),
                shopBanner = '',
                bottom_content = $("*[data-homehtml='bottom']").html(),
                bottom = '',
                navlayout = '',
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
            preview.find(".ui-box-display").removeClass("ui-box-display");
            preview.find(".custom .default").removeClass("default");
            preview.find(".lunbotu").removeClass("lunbotu");
            preview.find(".J-home").removeClass().addClass("content").addClass("J-home");
            preview.find(".demo").removeClass().addClass("content");
            preview.find(".spec").attr("data-spec", '');
            preview.find("*[data-homehtml='bottom']").remove();
            preview.find("*[data-homehtml='shopBanner']").remove();
            preview.find(".J-home").parent().remove();
            preview.find(".nav").remove();
            preview.find(".hd_main").remove();
            preview.find(".setup_box").remove();
            content_html = preview.html();
        }
        Ajax.call(__URL(MAIN + "/shop/fileputvisual"), "content=" + encodeURIComponent(content) + "&content_html=" + encodeURIComponent(content_html) + "&bottom_html= " + encodeURIComponent(bottom_html) + "&shopbanner_html= " + encodeURIComponent(shopBanner_html) + "&suffix=" + suffix + "&temp=" + temp + "&temp_type=" + temp_type, file_put_visualResponse, 'POST', 'JSON');
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
                if (!callback || callback() !== false) {
                    layer.close(index);
                }
            },
            // 弹出框出现的回调
            success: function (layero, index) {
                // layero.find('.layui-layer-btn').css('text-align', 'center')
                callbackopen && callbackopen();
            }
        });
    }


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







        // 选择链接
        $('body').on('click','[data-toggle="selectUrlPc"]',function(){
            var _this = $(this);
            var elm = _this.siblings('input');
            utilAdmin.linksDialog(function(data){
                elm.val(data.params).change();
            })
        });

        // B端装修图片空间1
        $('body').on('click','[data-toggle="decoPicture1"]',function(){
            var _this = $(this);
            var cimg=_this.siblings('.ipt80');
            var pimg=_this.closest('.form-horizontal').find('.pic_src0');
            var input=_this.children('.J-pic');
            utilAdmin.pictureDialog(_this,false,function(data){
                var path = data.path[0];
                cimg.val(path);
                pimg.attr('src',path);
                input.val(path);
            })
        });

        // B端装修图片空间2表格1
        $('body').on('click','[data-toggle="decoPicture2"]',function(){
            var _this = $(this);
            var cimg=_this.siblings('.ipt80');
            var pimg=_this.parents('td').prev('td').find('img');
            var input=_this.children('.J-pic');
            utilAdmin.pictureDialog(_this,false,function(data){
                var path = data.path[0];
                cimg.val(path);
                pimg.attr('src',path);
                input.val(path);
            })
        });

        // B端装修图片空间2表格4
        $('body').on('click','[data-toggle="decoPicture4"]',function(){
            var _this = $(this);
            var cimg=_this.siblings('.ipt80');
            var pimg=_this.closest('div').prev().find('img');
            var input=_this.children('.J-pic');
            utilAdmin.pictureDialog(_this,false,function(data){
                var path = data.path[0];
                cimg.val(path);
                pimg.attr('src',path);
                input.val(path);
            })
        });

        // B端装修图片空间2店招
        $('body').on('click','[data-toggle="decoPicture5"]',function(){
            var _this = $(this);
            var pimg=_this.find('img');
            var input=_this.find('.J-pic');
            utilAdmin.pictureDialog(_this,false,function(data){
                var path = data.path[0];
                pimg.attr('src',path);
                input.val(path);
            })
        });

    };
    return modal;
})