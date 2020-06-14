define(["jquery", "bootstrap"], function($) {
    var util = {};
    util.decorate = function() {
        require([
            "ueditor",
            "ueditor.ZeroClipboard",
            "ueditor.lang",
            "jquery-ui",
            "layer"
        ], function(u, zcl) {
            $(document).ready(function(e) {
                $(".module-list .lyrow").sortable({
                    connectWith: ".demo",
                    opacity: 0.35,
                    handle: ".drag",
                    placeholder: "ui-state-highlight"
                });
                $(".demo").sortable();
                $(".demo").disableSelection();

                //左侧模块拖动到右侧
                $(".module-list .lyrow").draggable({
                    connectToSortable: ".demo",
                    helper: "clone",
                    handle: ".drag",
                    drag: function(e, t) {
                        t.helper.width("auto");
                        t.helper.height("auto");
                    },
                    stop: function(e, t) {
                        var mode = $(this).data("mode");

                        $(".demo *[data-mode=" + mode + "]").each(function(index, element) {
                            $(this).attr("data-diff", index);
                            $(this)
                                .find("*[data-type='range']")
                                .attr("id", mode + "_" + index);
                        });
                        if (t.position.left > 200) {
                            layer.msg("添加成功", { time: 500, offset: ["50%", "50%"] });
                            $(".demo .pic").hide();
                            $(".demo .view").show();
                        }
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
                    $(".demo").delegate(".move-del", "click", function(e) {
                        that = $(this);
                        layer.confirm(
                            "您确定要删除这个模块吗？",
                            {
                                btn: ["确定", "取消"]
                            },
                            function() {
                                time: 500,
                                    layer.msg("删除成功", { time: 500, offset: ["50%", "50%"] }),
                                    e.preventDefault();
                                that
                                    .parent()
                                    .parent()
                                    .remove();
                                disabled();
                                if ($(".demo").html() == "") {
                                    layer.msg("当前没有任何模板哦", { time: 500, offset: ["50%", "50%"] });
                                }
                            }
                        );
                    });
                }
                removeElm();

                // $(".layoutItem").on("click", function() {
                //   $(this)
                //     .siblings()
                //     .removeClass("selected"); //siblings是循环遍历
                //   $(this).addClass("selected");
                //   $(this)
                //     .children(".checkbox_item")
                //     .children("input[type=radio]")
                //     .prop("checked", "checked");
                //   $(this)
                //     .siblings()
                //     .children("input[type=radio]")
                //     .removeAttr("checked");
                // });
                //颜色切换
                $(".colorItems").on("click", ".color-item", function() {
                    $(this)
                        .addClass("selected")
                        .siblings()
                        .removeClass("selected");
                });

                //  商品推荐单选框Tab栏
                $("input[name=recomMethod]").click(function() {
                    var index = $("input[name=recomMethod]").index($(this));
                    if (index == 1) {
                        $(".recomTab").show();
                        $(".auto").hide();
                    } else {
                        $(".recomTab").hide();
                        $(".auto").show();
                    }
                });

                var ue = UE.getEditor("container");
            });
        });
    };
    //   弹出框
    util.dialogs = function(titles, dialogEle, width, height, callback) {
        require(["layer"],function(layer){
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
                        layero.find(".layui-layer-btn").css("text-align", "center");
                    }
                });
        })
    };
//   轮播图
    util.lbt=function(){
        require(["swiper"],function(Swiper){

            $(document).ready(function () {
                var mySwiper3 = new Swiper ('.swiper-container3', {
                    loop:true,
                    mode:"vertical",
                    autoplay: true,//可选选项，自动滑动
                    // 如果需要分页器
                    pagination: {
                        el: '.swiper-pagination',
                        clickable:true,
                    },
                });

                var mySwiper2 = new Swiper ('.swiper-container2', {
                    loop:true,
                    mode:"vertical",
                    autoplay: true,//可选选项，自动滑动
                    // 如果需要分页器
                    pagination: {
                        el: '.swiper-pagination',
                        clickable:true,
                    },
                });

                var mySwiper1 = new Swiper ('.swiper-container1', {
                    observer:true,//修改swiper自己或子元素时，自动初始化swiper
                    observeParents:true,//修改swiper的父元素时，自动初始化swiper
                    loop:true,
                    mode:"vertical",
                    autoplay: true,//可选选项，自动滑动
                    // 如果需要分页器
                    pagination: {
                        el: '.swiper-pagination',
                        clickable:true,
                    },
                    effect:"fade",
                });

                var mySwiper4 = new Swiper ('.swiper-container4', {
                    loop:true,
                    mode:"vertical",
                    observer:true,//修改swiper自己或子元素时，自动初始化swiper
                    observeParents:true,//修改swiper的父元素时，自动初始化swiper
                    autoplay: true,//可选选项，自动滑动
                    // 如果需要分页器
                    pagination: {
                        el: '.swiper-pagination',
                        clickable:true,
                    },
                });

                var mySwiper5 = new Swiper ('.swiper-container5', {
                    observer:true,//修改swiper自己或子元素时，自动初始化swiper
                    observeParents:true,//修改swiper的父元素时，自动初始化swiper
                    loop:true,
                    mode:"vertical",
                    autoplay: true,//可选选项，自动滑动
                    // 如果需要分页器
                    pagination: {
                        el: '.swiper-pagination',
                        clickable:true,
                    },
                    effect:"fade",
                });

            });

        })

    }
    return util;
});
