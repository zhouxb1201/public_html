define(['util','jquery-ui'], function (util) {
    var modal = {
        id: 0,
        type: 1,            //  页面类型    默认1首页
        navs: {},
        initnav: [],
        is_shop: 0,
        data: {},
        selected: 'page',
        addonsIsUse:''
    };
    
    // 初始化
    modal.init = function (params) {
        modal.addonsIsUse = params.addonsIsUse
        modal.initData(params);
        modal.initTpl();
        modal.initPage();
        modal.initItems();
        modal.initNavs();
        modal.initSortable();
        $("#page").unbind('click').click(function () {
            if (modal.selected == 'page') {
                return
            };
            modal.selected = 'page';
            modal.initPage()
        });
        // $('#tabbar').unbind('click').click(function(){
        //     if (modal.selected == 'tabbar') {
        //         return
        //     };
        //     modal.selected = 'tabbar';
        //     $("#view").find(".drag").removeClass("selected");
        //     $("#copyright").removeClass("selected");
        //     $("#tabbar").addClass("selected");
        //     modal.initEditor();
        // });
        // $('#copyright').unbind('click').click(function(){
        //     if (modal.selected == 'copyright') {
        //         return
        //     };
        //     modal.selected = 'copyright';
        //     $("#view").find(".drag").removeClass("selected");
        //     $("#tabbar").removeClass("selected");
        //     $("#copyright").addClass("selected");
        //     modal.initEditor();
        // });
        $(".btn-save").unbind('click').click(function () {
            var status = $(this).data('status');
            var type = $(this).data('type');
            if (status) {
                util.message("正在保存，请稍候。。。");
                return
            }
            if (type == 'save') {
                modal.save()
            } else if (type == 'preview') {
                modal.save(true)
            }else if(type == 'recovery'){
                modal.initData(params);
                modal.initItems();
                $("#page").trigger('click');
            }
        });
    };
    // 初始化数据
    modal.initData = function(data){
        var params = $.extend(true,{},data);
        window.tpl = params.tpl;
        modal.type = params.type;
        modal.id = params.id;
        modal.is_shop = params.is_shop,
        modal.attachurl = params.attachurl;
        modal.data = params.data;
        //console.log(modal.data)
        if (modal.data) {
            modal.page = modal.data.page;
            modal.items = modal.data.items;
            modal.initDefaultItems()
        }else{
            modal.items = modal.defaultItems()
        }
        
    };
    // 初始化页面设置
    modal.initPage = function (initE) {
        if (typeof(initE) === 'undefined') {
            initE = true
        }
        if (!modal.page) {
            modal.page = {
                type: modal.type,
                title: '请输入页面标题',
                background: '#f8f8f8',
                readonly:false
            };
            if (modal.type == 1) {
                modal.page.title = "首页"
            }
            if (modal.type == 2) {
                modal.page.title = "店铺首页"
            }
            if (modal.type == 3) {
                modal.page.readonly = true
                modal.page.title = "商品详情标题默认读取商品名称"
            }
            if (modal.type == 4) {
                modal.page.title = "会员中心"
            }
            if (modal.type == 5) {
                modal.page.title = "分销中心"
            }
            
        }

        $("#page").text(modal.page.title);
        $("#view").css({'background-color': modal.page.background});
        $("#foot").css({'background-color': modal.page.background});
        $("#view").find(".drag").removeClass("selected");
        $("#foot").find(".drag").removeClass("selected");
        if (initE) {
            modal.initEditor()
        }
    };
    // 初始化默认数据（处理新增功能没有默认数据问题，防止报错）
    modal.initDefaultItems = function(){
        if(modal.type == 2){
            $.each(modal.items, function (itemid, item) {
                if(item.id == 'shop_head' && (!item.params || !item.params.styletype)){
                    item.params = {}
                    item.params.styletype = '1'
                }
            })
        }
    };
    // 加载默认数据
    modal.defaultItems = function(){
        var items = {}
        if(modal.type === 1){
            items = {}
        }else if(modal.type === 2){
            items = modal.defaultShopItems()
        }else if(modal.type === 3){
            items = modal.defaultDetailItems()
        }else if(modal.type === 4){
            items = modal.defaultMemberItems()
        }else if(modal.type === 5){
            items = modal.defaultCommissionItems()
        }else if(modal.type === 6){
            items = {}
        }
        return items
    }
    // 初始化模板
    modal.initTpl = function () {
        tpl.helper("imgsrc", function (src) {
            if (typeof src != 'string') {
                return ''
            }
            if (src.indexOf('http://') == 0 || src.indexOf('https://') == 0) {
                return src
            } else if (src.indexOf('images/') == 0 || src.indexOf('audios/') == 0) {
                return modal.attachurl + src
            }
        });
        tpl.helper("decode", function (content) {
            return $.base64.decode(content)
        });
        tpl.helper("count", function (data) {
            return modal.length(data)
        });
        tpl.helper("toArray", function (data) {
            var oldArray = $.makeArray(data);
            var newArray = [];
            $.each(data, function (itemid, item) {
                newArray.push(item)
            });
            return newArray
        });
        tpl.helper("strexists", function (str, tag) {
            if (!str || !tag) {
                return false
            }
            if (str.indexOf(tag) != -1) {
                return true
            }
            return false
        });
        tpl.helper("inArray", function (str, tag) {
            if (!str || !tag) {
                return false
            }
            if(typeof(str)=='string'){
                var arr = str.split(",");
                if($.inArray(tag, arr)>-1){
                    return true;
                }
            }
            return false
        });
        tpl.helper("define", function (str) {
            var str
        })
    };
    // 初始化编辑
    modal.initEditor = function (scroll) {
        if (typeof(scroll) === 'undefined') {
            scroll = true
        }
        var itemid = modal.selected;
        // var top = 0;
        // if (itemid != 'page' && itemid != 'tabbar' && itemid != 'copyright') {
        //     var stop = $(".selected").position().top;
        //     top = stop + 64;
        //     $("#foot").find(".drag").removeClass("selected");
        // }
        // if(itemid == 'tabbar'){
        //     top = $('#tabbar').offset().top - 88
        // }
        // if(itemid == 'copyright'){
        //     top = $('#copyright').offset().top - 88
        // }
        // if (scroll) {
        //     $("#editor").css({"margin-top": top - 0 + "px"});
        // }
        
        if (itemid) {
            if (itemid == 'page') {
                var html = tpl("tpl_edit_page", modal)
                $("#editor .editor-inner").html(html)

            } else if(modal.selected == 'tabbar'){
                var html = tpl("tpl_edit_tabbar", modal.tabbar)
                $("#editor .editor-inner").html(html)

            } else if(modal.selected == 'copyright'){
                var html = tpl("tpl_edit_copyright", modal.copyright)
                $("#editor .editor-inner").html(html)

            } else {
                var item = $.extend(true, {}, modal.items[modal.selected]);
                item.itemid = modal.selected;
                item.merch = modal.merch;
                item.plugins = modal.plugins;
                var html = tpl("tpl_edit_" + item.id, item);
                $("#editor .editor-inner").html(html)
            }
            $("#editor").attr("data-editid", modal.selected).show()
        }
        // 初始化取色器
        $(".colorpicker").each(function(){
            var elm = this;
            util.colorpicker(elm, function(color){});
        });
        // 重置颜色
        $('.btn-reset').unbind('click').click(function(){
            var color = $(this).data('color')
            $(this).parent().find('.sp-preview-inner,.colorpicker').css('backgroundColor',color).val(color).trigger('propertychange')
        })
        // 滑块
        var sliderlength = $("#editor .slider").length;
        if (sliderlength > 0) {
            $("#editor .slider").each(function () {
                var decimal = $(this).data('decimal');
                var multiply = $(this).data('multiply');
                var defaultValue = $(this).data("value");
                if (decimal) {
                    defaultValue = defaultValue * decimal
                }
                $(this).slider({
                    slide: function (event, ui) {
                        var sliderValue = ui.value;
                        if (decimal) {
                            sliderValue = sliderValue / decimal
                        }
                        $(this).siblings(".input").val(sliderValue).trigger("propertychange");
                        $(this).siblings(".count").find("span").text(sliderValue)
                    }, 
                    value: defaultValue, 
                    min: $(this).data("min"), 
                    max: $(this).data("max"),
                    step: $(this).data('step')
                })
            })
        }

        // 选择图片
        $('[data-toggle="selectImg"]').unbind('click').click(function(){
            var _this = $(this);
            if(_this.data('disabled') == 'disabled'){
                return false
            }
            var cimg = _this.parents('.item').find('input'+_this.data('input'));
            var pimg = _this.parents('.item').find('img'+_this.data('img'));
            util.pictureDialog(_this,false,function(data){
                var path = data.path[0];
                cimg.val(path).change();
                pimg.attr('src',path);
            })
        })
        // 选择链接
        $('[data-toggle="selectUrl"]').unbind('click').click(function(){
            var _this = $(this);
            if(_this.data('disabled') == 'disabled'){
                return false
            }
            var curlId = _this.data('input');
            var elm = _this.parents('.item').find('input'+curlId);

            util.linksDialog(function(data){
                elm.data('link-type', data.type)
                elm.data('name', data.params);

                elm.val(data.name).change();
            },'app&template_type=' + modal.type)//by sgw 为了判断B端的店铺首页type（用来带自营店shop_id=0链接）
        })
        
        // 选择图标
        $('[data-toggle="selectIcon"]').unbind('click').click(function () {
            var _this = $(this);
            var curlId = _this.data('input');
            var elm = _this.parents('.item').find('input'+curlId);
            util.wap_iconsDialog(function(data){
                elm.val(data).change();
            })
        })

        // 添加子列表
        var childitems = $("#editor .form-items").length;
        if (childitems > 0) {
            modal.addChild()
        }

        // 选择商品
        $('#selectGoods').unbind('click').click(function () {
            var itemid = modal.selected;
            var session = new util.Storage('session');
            var goods_type = modal.items[itemid].params.goodstype;
            var type = modal.type;
            session.setItem('goods_data_itemid',itemid);

            util.goodsDialog('url:'+__URL(PLATFORMMAIN+ '/shop/modalGoodsList&t='+(new Date()).getTime()+'&goods_type='+goods_type  + '&type=' + type),function(data){
                modal.items[itemid].data = data.data;
                modal.initItems(itemid);
                modal.initEditor(false);
            },function(){
                this.$content.addClass('goods_data_itemid_'+itemid)
                this.$content.data('goods_data',modal.items[itemid].data)
            })
        })

        // 选择店铺
        $('#selectShop').unbind('click').click(function () {
            var itemid = modal.selected;
            var session = new util.Storage('session');

            session.setItem('shop_data_itemid',itemid);

            util.shopDialog('url:'+__URL('/platform/addons/execute/addons/shop/controller/shop/action/modalshoplist&t='+(new Date()).getTime()),function(data){
                modal.items[itemid].data = data.data;
                modal.initItems(itemid);
                modal.initEditor(false);
            },function(){
                this.$content.addClass('shop_data_itemid_'+itemid)
                this.$content.data('shop_data',modal.items[itemid].data)
            })
        })
        
        // 富文本
        if($("div[id^='UE_']").length > 0){
            $("div[id^='UE_']").each(function(i,e){
                var elm = $(this).attr('id');
                if (typeof(UE) != 'undefined') {
                    UE.delEditor(elm)
                }
                require(['ueditor','ueditor.ZeroClipboard','ueditor.lang'],function(u,zcl){
                    window.ZeroClipboard = zcl;
                    var ueditoroption = {
                        'autoClearinitialContent': false,
                        'toolbars': [['fullscreen', 'source', 'preview', '|', 'bold', 'italic', 'underline', 'strikethrough', 'forecolor', 'backcolor', '|', 'justifyleft', 'justifycenter', 'justifyright', '|', 'insertorderedlist', 'insertunorderedlist', 'blockquote', 'emotion','insertvideo', 'removeformat', '|', 'rowspacingtop', 'rowspacingbottom', 'lineheight', 'indent', 'paragraph', 'fontsize', '|', 'inserttable', 'deletetable', 'insertparagraphbeforetable', 'insertrow', 'deleterow', 'insertcol', 'deletecol', 'mergecells', 'mergeright', 'mergedown', 'splittocells', 'splittorows', 'splittocols', '|', 'anchor', 'map', 'print', 'drafts', '|', 'link']],
                        'elementPathEnabled': false,
                        'initialFrameHeight': 300,
                        'focus': false,
                        'maximumWords': 9999999999999
                    };
                    var ue = UE.getEditor(elm, ueditoroption);
                    UE.registerUI('myinsertimage', function (editor, uiName) {
                        editor.registerCommand(uiName, {
                            execCommand: function() {
                                var storage = new util.Storage('session');
                                storage.setItem('multiple','1');
                                util.pictureDialog(uiName,true,function(data){
                                    console.log(data)
                                })
                            }
                        });
                        var btn = new UE.ui.Button({
                            name: 'selectImg',
                            title: '插入图片',
                            cssRules: 'background-position: -726px -77px',
                            onclick: function() {
                                editor.execCommand(uiName)
                            }
                        });
                        editor.addListener('selectionchange', function() {
                            var state = editor.queryCommandState(uiName);
                            if (state == -1) {
                                btn.setDisabled(true);
                                btn.setChecked(false);
                            } else {
                                btn.setDisabled(false);
                                btn.setChecked(state);
                            }
                        });
                        return btn;
                    }, 48);
                    ue.ready(function() {
                        var thisitem = modal.items[itemid];
                        var richContent = thisitem.params.content;
                        richContent = $.base64.decode(richContent); 
                        ue.setContent(richContent);
                        ue.addListener('contentChange', function () {
                            var newContent = ue.getContent();
                            newContent = $.base64.encode(newContent);
                            $('#get_'+elm).val(newContent).trigger('change')
                        })
                    })
                })
            })
        }

        // 双向绑定数据
        $("#editor").find(".diy-bind").bind('input propertychange change', function () {
            var _this = $(this);
            var bind = _this.data("bind");
            var name = _this.data("name");
            var link_type = _this.data("link-type")
            var bindchild = _this.data('bind-child');
            var bindparent = _this.data('bind-parent');
            var initEditor = _this.data('bind-init');
            var value = '';
            var tag = this.tagName;
            if (!itemid) {
                modal.selectedItem('page ')
            }
            if (tag == 'INPUT') {
                var type = _this.attr('type');
                if (type == 'checkbox') {
                    value = [];
                    _this.closest('.form-group').find('input[type=checkbox]').each(function () {
                        var checked = this.checked;
                        var valname = $(this).val();
                        if (checked) {
                            value.push(valname)
                        }
                    })
                } else {
                    var placeholder = _this.data('placeholder');
                    value = _this.val();
                    value = value == '' ? placeholder : value
                }
                
            } else if (tag == 'SELECT') {
                value = _this.find('option:selected').val()
            } else if (tag == 'TEXTAREA') {
                value = _this.val()
            }
            value = $.trim(value);
           
            if (itemid == 'page') {
                if (bindchild) {
                    if (!modal.page[bindchild]) {
                        modal.page[bindchild] = {}
                    }
                    modal.page[bindchild][bind] = value
                } else {
                    modal.page[bind] = value
                }
                modal.initPage(false);

            } else {
                if (bindchild) {
                    if (bindparent) {
                        modal.items[itemid][bindparent][bindchild][bind] = value
                        if (name) {
                            modal.items[itemid][bindparent][bindchild]['name'] = name
                        }
                        if (link_type){
                            modal.items[itemid][bindparent][bindchild]['link_type'] = link_type
                        }
                        // console.log(itemid,bindparent,bindchild,bind)
                    } else {
                        modal.items[itemid][bindchild][bind] = value
                    }
                } else {
                    modal.items[itemid][bind] = value
                }
                modal.initItems(itemid)
            }
            if (initEditor) {
                modal.initEditor(false)
            }
        })
    };
    // 初始化视图
    modal.initItems = function (selected) {
        var view = $("#view");
        if (!modal.items) {
            modal.items = {};
            return
        }
        view.empty();
        $.each(modal.items, function (itemid, item) {
            if (typeof(item.id) !== 'undefined') {
                var newItem = $.extend(true, {}, item);
                newItem.itemid = itemid;
                var html = tpl("tpl_show_" + item.id, newItem);
                $("#view").append(html)
            }
        });
        
        var btnhtml = $("#edit-del").html();
        $("#view .drag").append(btnhtml);
        // 删除组件
        $("#view .drag .btn-edit-del .btn-del").unbind('click').click(function (e) {
            e.stopPropagation();
            var drag = $(this).closest(".drag");
            var itemid = drag.data('itemid');
            var nodelete = $(this).closest(".drag").hasClass("nodelete");
            if (nodelete) {
                util.message("此元素禁止删除",'danger');
                return
            }
            util.alert('确定删除吗？',function(){
                var nearid = modal.getNear(itemid);
                delete modal.items[itemid];
                modal.initItems();
                if (nearid) {
                    $(document).find(".drag[data-itemid='" + nearid + "']").trigger('mousedown')
                } else {
                    $("#page").trigger('click')
                }
            })
        }); 
        if (selected) {
            modal.selectedItem(selected)
        }
    };
    // 初始化组件导航
    modal.initNavs = function () {
        modal.getNavs();
        // navgroup[0]  为公用组件
        // navgroup[1]  为首页组件
        // navgroup[2]  为店铺组件
        // navgroup[3]  为商品详情组件
        // navgroup[4]  为会员中心组件
        // navgroup[5]  为分销中心组件
        // navgroup[6]  为自定义页面组件
        // navgroup[7]  为底部版权组件
        // navgroup[8]  为底部tabbar组件
        // navgroup[9]  为积分商城组件
        var navgroup = {
            0: ['title','line','blank','picture','goods','richtext'],
            1: ['search','menu','notice','banner','picturew'],
            2: ['search','menu','notice','banner','picturew'],
            3: [],
            4: ['menu','notice','listmenu'],
            5: ['menu','notice','listmenu'],
            6: ['banner','menu','notice','listmenu'],
			7: [],
			8: [],
			9: ['search', 'menu', 'notice', 'banner', 'picturew','goodsIntegral'],
        };
        
        // 根据应用开启情况显示应用组件

       //积分商城装修删除原来的商品组 组件
	   if (modal.type == 9) {
		   var index = navgroup[0].indexOf("goods");
			if (index > -1) {
				navgroup[0].splice(index, 1);
			}

	   }

        if (modal.type == 1 && modal.addonsIsUse.seckill) {
            // navgroup[modal.type].push('seckill')
        }
        if ((modal.type == 1 || modal.type == 2 || modal.type == 9) && modal.addonsIsUse.shop) {
            navgroup[modal.type].push('shop')
        }

        var navpage = navgroup[modal.type];
        if (navpage) {
            navpage = $.merge(navpage, navgroup[0])
        } else {
            navpage = navgroup[0]
        }

        $.each(navpage, function (index, val) {
            var params = modal.navs[val];
            if (params) {
                params.id = val;
                modal.initnav.push(params)
            }
        });
        
        var html = tpl("tpl_navs", modal);
        //$("#navs").html(html).show();

        $("#navs .item").unbind('click').click(function () {
            var id = $(this).data('id');
            View(id)
        })
        function View(id){
            if (id === 'page') {
                $("#page").trigger("click");
                return
            }
            var inArray = $.inArray(id, navpage);
            if (inArray < 0) {
                util.message("此页面类型禁止添加此元素！",'danger');
                return
            }
            var item = $.extend(true, {}, modal.navs[id]);
            delete item.name;
            if (!item) {
                util.message("未找到此元素！",'danger');
                return
            }
            var itemTplShow = $("#tpl_show_" + id).length;
            var itemTplEdit = $("#tpl_edit_" + id).length;

            if (itemTplShow == 0 || itemTplEdit == 0) {
                util.message("添加失败！模板错误，请刷新页面重试",'danger');
                return
            }
            
            var itemid = modal.getId("M", 0);
            //将默认数据id更新最新id
            if (item.data) {
                var itemData = $.extend(true, {}, item.data);
                var newData = {};
                var index = 0;
                $.each(itemData, function (id, data) {
                    var childid = modal.getId("C", index);
                    newData[childid] = data;
                    delete childid;
                    index++
                });
                item.data = newData
            }
            if (item.max && item.max > 0) {
                var itemNum = modal.getItemNum(id);
                if (itemNum > 0 && itemNum >= item.max) {
                    util.message("此元素最多允许添加 " + item.max + " 个");
                    return
                }
            }
            
            var append = true;
            
            // 判断
            if (modal.selected && modal.selected != 'page' && modal.selected != 'tabbar' && modal.selected != 'copyright') {
                var thisitem = modal.items[modal.selected]; 
                if (thisitem.id == 'tabbar' || id == 'tabbar') {
                    append = false
                }
            }
            
            // 判断是否固定顶部 
            if (item.istop) {
                // var newItems = {};
                // newItems[itemid] = item;
                // $.each(modal.items, function (id, eachitem) {
                //     newItems[id] = eachitem
                // });
                // modal.items = newItems
            } else if (modal.selected && modal.selected != 'page' && modal.selected != 'tabbar' && modal.selected != 'copyright' && append) {
                var newItems = {};
                $.each(modal.items, function (id, eachitem) {
                    newItems[id] = eachitem;
                    if (id == modal.selected) {
                        newItems[itemid] = item
                    }
                });
                modal.items = newItems

            } else {
                modal.items[itemid] = item
            };
            
            modal.initItems();
            $(".drag[data-itemid='" + itemid + "']").trigger('mousedown').trigger('click');
            modal.selected = itemid
        }
    };
    // 初始化排序
    modal.initSortable = function () {
        $("#view").sortable({
            opacity: 0.9,
            placeholder: "highlight",
            items: '.drag:not(.fixed)',
            revert: 100,
            scroll: false,
            start: function (event, ui) {
                var height = ui.item.height();
                $(".highlight").css({"height": height + "px"});
                $(".highlight").html('<div><i class="icon icon-add1"></i> 放置此处</div>');
                $(".highlight div").css({"line-height": height - 4 + "px"})
            },
            stop: function (event, ui) {
                modal.initEditor()
            },
            update: function (event, ui) {
                modal.sortItems()
            }
        });
        $("#view").disableSelection();
        $(document).on('mousedown', "#view .drag", function () {
            if ($(this).hasClass("selected")) {
                return
            }
            modal.selected = $(this).data('itemid');
            $("#view").find(".drag").removeClass("selected");
            $(this).addClass("selected");
            modal.selected = $(this).data('itemid');
            modal.initEditor()
        })
    };
    modal.selectedItem = function (itemid) {

        if (!itemid) {
            return
        }
        modal.selected = itemid;
        if (itemid == 'page') {
            $("#page").trigger('click')
        } else {
            $(".drag[data-itemid='" + itemid + "']").addClass('selected');
        }
    };
    modal.sortItems = function () {
        var newItems = $.extend(true,{},modal.defaultItems());
        $("#view .drag").each(function () {
            var thisid = $(this).data('itemid');
            newItems[thisid] = modal.items[thisid]
        });
        modal.items = newItems
    };
    // 添加子列表
    modal.addChild = function(){
        modal.initSortableChild();  
        var itemid = modal.selected;
        if(itemid == 'tabbar'){
            modal.tabbarAddChild()
        }else{
            $("#addChild").unbind('click').click(function () {
                
                var type = modal.items[itemid].id;
                var temp = modal.navs[type].data;
                var max = $(this).closest(".form-items").data('max');
                if (max) {
                    var length = modal.length(modal.items[itemid].data);
                    if (length >= max) {
                        util.message("最多添加 " + max + " 个！");
                        return
                    }
                }
                var newChild = {};
                var index = 0;
                $.each(temp, function (i, t) {
                    if (index == 0) {
                        newChild = t;
                        index++
                    }
                });

                if (newChild) {
                    var childName = modal.getId("C", 0);
                    if (typeof(modal.items[itemid].data) === 'undefined') {
                        modal.items[itemid].data = {}
                    }
                    newChild = $.extend(true, {}, newChild);
                    modal.items[itemid].data[childName] = newChild;
                }
                modal.initItems(itemid);
                modal.initEditor(false)
            });
            $("#editor .form-items .item .btn-del").unbind('click').click(function () {
                var childid = $(this).closest(".item").data('id');
                var itemid = modal.selected;
                var min = $(this).closest(".form-items").data("min");
                
                if (min) {
                    var length = modal.length(modal.items[itemid].data);
                    if (length <= min) {
                        util.message("至少保留 " + min + " 个！");
                        return
                    }
                }
                util.alert("确定删除吗", function () {
                    delete modal.items[itemid].data[childid];
                    modal.initItems(itemid);
                    modal.initEditor(false)
                })
            })
        }
    };
    // 添加tabbar子列表
    modal.tabbarAddChild = function(){   
        $("#addChild").unbind('click').click(function () {
            var temp = modal.tabbar.data;
            var max = $(this).closest(".form-items").data('max');
            if (max) {
                var length = modal.length(modal.tabbar.data);
                if (length >= max) {
                    util.message("最多添加 " + max + " 个！");
                    return
                }
            }
            var newChild = {};
            var index = 0;
            $.each(temp, function (i, t) {
                if (index == 0) {
                    newChild = t;
                    index++
                }
            });

            if (newChild) {
                var childName = modal.getId("C", 0);
                if (typeof(modal.tabbar.data) === 'undefined') {
                    modal.tabbar.data = {}
                }
                newChild = $.extend(true, {}, newChild);
                modal.tabbar.data[childName] = newChild;
            }
            modal.initEditor(false)
        });
        $("#editor .form-items .item .btn-del").unbind('click').click(function () {
            var childid = $(this).closest(".item").data('id');
            var min = $(this).closest(".form-items").data("min");
            if (min) {
                var length = modal.length(modal.tabbar.data);
                if (length <= min) {
                    util.message("至少保留 " + min + " 个！");
                    return
                }
            }
            util.alert("确定删除吗", function () {
                delete modal.tabbar.data[childid];
                modal.initEditor(false)
            })
        })
    };
    // 排序子列表
    modal.initSortableChild = function () {
        $("#editor .editor-inner").sortable({
            opacity: 0.8,
            placeholder: "highlight",
            items: '.item',
            revert: 100,
            scroll: false,
            cancel: '.goods-selector,input,select,.btn,btn-del',
            start: function (event, ui) {
                var height = ui.item.height();
                $(".highlight").css({"height": height + 22 + "px"});
                $(".highlight").html('<div><i class="icon icon-add1"></i> 放置此处</div>');
                $(".highlight div").css({"line-height": height + 16 + "px"})
            },
            update: function (event, ui) {
                modal.sortChildItems()
            }
        })
    };
    modal.sortChildItems = function () {
        var newChild = {};
        var itemid = modal.selected;
        if(itemid == 'tabbar'){
            $("#editor .form-items .item").each(function () {
                var thisid = $(this).data('id');
                newChild[thisid] = modal.tabbar.data[thisid];
            });
            modal.tabbar.data = newChild;
        }else{
            $("#editor .form-items .item").each(function () {
                var thisid = $(this).data('id');
                newChild[thisid] = modal.items[itemid].data[thisid];
            });
            modal.items[itemid].data = newChild;
            modal.initItems(itemid)
        }
    };
    // 获取id
    modal.getId = function (S, N) {
        var date = +new Date();
        var id = S + (date + N);
        return id
    };
    modal.getNear = function (itemid) {
        var newarr = [];
        var index = 0;
        var prev = 0;
        var next = 0;
        $.each(modal.items, function (id, obj) {
            newarr[index] = id;
            if (id == itemid) {
                prev = index - 1;
                next = index + 1
            }
            index++
        });
        var pervid = newarr[prev];
        var nextid = newarr[next];
        if (nextid) {
            return nextid
        }
        if (pervid) {
            return pervid
        }
        return false
    };
    // 获取item的length
    modal.length = function (json) {
        if (typeof(json) === 'undefined') {
            return 0
        }
        var jsonlen = 0;
        for (var item in json) {
            jsonlen++
        }
        return jsonlen
    };
    // 获取item数值
    modal.getItemNum = function (id) {
        if (!id || !modal.items) {
            return -1
        }
        var itemNum = 0;
        $.each(modal.items, function (itemid, eachitem) {
            if (eachitem.id == id) {
                itemNum++
            }
        });
        return itemNum
    };
    // 保存/预览
    modal.save = function (preview) {
        if (typeof(preview) === 'undefined') {
            preview = false
        }
        modal.data = {};
        modal.data = {
            page: modal.page, 
            items: modal.items
        };
        
        // if (!modal.page.title) {
        //     util.message("页面标题不能为空",'danger');
        //     $("#page").trigger("click");
        //     return
        // }

        // $(".btn-save").data('status', 1).text("保存中...");
        // console.log('提交数据==>',JSON.stringify(modal.data))
        // console.log(modal.data)
        // return

        $.ajax({
            type :'post',
            url : __URL(PLATFORMMAIN + '/addons/execute/addons/appshop/controller/appshop/action/saveDecoration'),
            data : {
                'id':modal.id,
                'template_data':modal.data,
            },
            success : function(res){
                if(res.code>0){
                    util.message(res.message,'success');

                }else{
                    util.message(res.message,'error');
                }
            }
        });  
    };
    // 默认tabbar数据
    modal.defaultTabbar = function(){
        return {
            data:{
                C0123456789101:{
                    text:'首页',
                    path:'/mall/index',
                    normal:modal.attachurl + 'home-normal.png',
                    active:modal.attachurl + 'home-active.png'
                },
                C0123456789102:{
                    text:'分类',
                    path:'/goods/category',
                    normal:modal.attachurl + 'category-normal.png',
                    active:modal.attachurl + 'category-active.png'
                },
                C0123456789103:{
                    text:'店铺街',
                    path:'/shop/list',
                    normal:modal.attachurl + 'shop-normal.png',
                    active:modal.attachurl + 'shop-active.png'
                },
                C0123456789104:{
                    text:'购物车',
                    path:'/mall/cart',
                    normal:modal.attachurl + 'cart-normal.png',
                    active:modal.attachurl + 'cart-active.png'
                },
                C0123456789105:{
                    text:'会员中心',
                    path:'/member/centre',
                    normal:modal.attachurl + 'member-normal.png',
                    active:modal.attachurl + 'member-active.png'
                }
            }
        }
    };
    // 默认copyright数据
    modal.defaultCopyright = function(){
        return {
            style: {
                showtype: '1'
            },
            params: {
                is_show: '1',
                showlogo: '1',
                text: '请填写版权说明',
                src: modal.attachurl + 'copyright.png',
                linkurl: ''
            }
        }
    };
    // 店铺默认数据
    modal.defaultShopItems = function(){
        return {
            "M012345678901":{
                "id":"shop_head",
                "style":{
                    "backgroundimage":""
                },
                "params": {
                    "styletype":'1'
                },
            }   
        }
    };
    // 详情默认数据
    modal.defaultDetailItems = function(){
        return {
            "M012345678901":{
                "id":"detail_fixed"
            }
        }
    };
    // 详情会员中心数据
    modal.defaultMemberItems = function(){
        return {
            "M012345678901":{
                "id":"member_fixed"
            }
        }
    };
    // 详情分销中心数据
    modal.defaultCommissionItems = function(){
        return {
            "M012345678901":{
                "id":"commission_fixed"
            }
        }
    };
    // 默认数据
    modal.getNavs = function () {
        modal.navs = {
            search: {
                name: '搜索框',
                params: {'placeholder': '请输入关键字进行搜索'},
                style: {
                    // 'inputbackground': '#ffffff',
                    'background': '#f8f8f8',
                    // 'iconcolor': '#b4b4b4',
                    // 'color': '#999999',
                    'paddingtop': '10',
                    'paddingleft': '10',
                    // 'textalign': 'left'
                }
            },
            banner: {
                name: '图片轮播',
                params: {},
                style: {},
                data: {
                    C0123456789101: {
                        imgurl: modal.attachurl+'banner-1.jpg',
                        linkurl: '',
                    },
                    C0123456789102: {
                        imgurl: modal.attachurl+'banner-2.jpg',
                        linkurl: '',
                    }
                }
            },
            menu: {
                name: '按钮组',
                params: {},
                style: {
                    'background': '#ffffff',
                    'rownum': '4'
                },
                data: {
                    C0123456789101: {
                        imgurl: modal.attachurl+'icon-1.png',
                        linkurl: '',
                        text: '按钮文字1',
                        color: '#323232'
                    },
                    C0123456789102: {
                        imgurl: modal.attachurl+'icon-2.png',
                        linkurl: '',
                        text: '按钮文字2',
                        color: '#323232'
                    },
                    C0123456789103: {
                        imgurl: modal.attachurl+'icon-3.png',
                        linkurl: '',
                        text: '按钮文字3',
                        color: '#323232'
                    },
                    C0123456789104: {
                        imgurl: modal.attachurl+'icon-4.png',
                        linkurl: '',
                        text: '按钮文字4',
                        color: '#323232'
                    }
                }
            },
            notice: {
                name: '公告',
                params: {
                    'text':'这个一条公告内容。',
                    'leftIcon':modal.attachurl+'notice-icon.png'
                },
                style: {
                    'background': '#fff7cc',
                    'color': '#f60'
                }
            },
            picture: {
                name: '图片广告',
                params: {},
                style: {'paddingtop': '0', 'paddingleft': '0','background':'#ffffff'},
                data: {
                    C0123456789101: {
                        imgurl: modal.attachurl+'banner-1.jpg',
                        linkurl: '',
                    }
                }
            },
            title: {
                name: '标题栏',
                params: {'title': ''},
                style: {
                    'background': '#f8f8f8',
                    'color': '#323232',
                    'textalign': 'center',
                    'fontsize': '14',
                    'paddingtop': '5',
                    'paddingleft': '5'
                }
            },
            line: {
                name: '辅助线',
                params: {},
                style: {
                    'height': '2',
                    'background': '#f8f8f8',
                    "border": "#000000",
                    'padding': '10',
                    'linestyle': 'solid'
                }
            },
            blank: {
                name: '辅助空白', 
                params: {}, 
                style: {height: '20', background: '#f8f8f8'}
            },
            picturew: {
                name: '图片橱窗',
                params: {row: '1'},
                style: {paddingtop: '0', paddingleft: '0',background:'#ffffff'},
                data: {
                    C0123456789101: {
                        imgurl: modal.attachurl+'cube-1.jpg',
                        linkurl: '',
                    },
                    C0123456789102: {
                        imgurl: modal.attachurl+'cube-2.jpg',
                        linkurl: '',
                    },
                    C0123456789103: {
                        imgurl: modal.attachurl+'cube-3.jpg',
                        linkurl: '',
                    },
                    C0123456789104: {
                        imgurl: modal.attachurl+'cube-4.jpg',
                        linkurl: '',
                    }
                }
            },
            goods: {
                name: '商品组',
                shoptype: modal.type,
                params: {
                    'showtype':'2',
                    'recommendtype':'0',
                    'goodstype': '0',
                    'goodssort':'0',
                    'recommendnum': '4'
                },
                style: {
                    'background': '#f8f8f8'
                },
                data: {}
            },
			goodsIntegral: {
				name: '商品组',
				shoptype: modal.type,
				params: {
					'showtype':'2',
					'recommendtype': '0',
					// 'goodstype': '1',
					'goodssort': '0',
					'recommendnum': '4'
				},
				style: {
					'background': '#f8f8f8'
				},
				data: {}
			},
            shop:{
                name: '精选店铺',
                params:{
                    'title':'精选店铺',
                    'recommendtype':'0',
                    'recommendcondi':'0',
                    'recommendnum':'6',
                },
                style:{},
                data:{}
            },
            listmenu: {
                name: '列表导航',
                params: {},
                style: {
                    'margintop': '10',
                    'background': '#ffffff',
                    'iconcolor': '#323232',
                    'textcolor': '#323232',
                    'remarkcolor': '#909399'
                },
                data: {
                    C0123456789101: {text: '文字1', linkurl: '', iconclass: 'v-icon-home', remark: '查看', dotnum: ''},
                    C0123456789102: {text: '文字2', linkurl: '', iconclass: 'v-icon-set', remark: '', dotnum: ''},
                    C0123456789103: {text: '文字3', linkurl: '', iconclass: 'v-icon-mail', remark: '查看', dotnum: ''}
                }
            },
            richtext: {
                name: '自定义区', 
                params: {
                    content: ''
                }, 
                style: {
                    'padding': '10'
                }
            },
            shop_head:{
                name: '店铺顶部',
                style: {
                    'backgroundimage': ''
                },
                params: {
                    styletype:'1'
                }
            },
            seckill:{
                name: '秒杀',
                params: {
                    title: "超值秒杀",
                    goodssort: "0"
                }
            }
            
        }
    };
    jQuery.base64 = (function ($) {
        var _PADCHAR = "=", _ALPHA = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/", _VERSION = "1.1";

        function _getbyte64(s, i) {
            var idx = _ALPHA.indexOf(s.charAt(i));
            if (idx === -1) {
                throw"Cannot decode base64"
            }
            return idx
        }

        function _decode_chars(y, x) {
            while (y.length > 0) {
                var ch = y[0];
                if (ch < 0x80) {
                    y.shift();
                    x.push(String.fromCharCode(ch))
                } else if ((ch & 0x80) == 0xc0) {
                    if (y.length < 2)break;
                    ch = y.shift();
                    var ch1 = y.shift();
                    x.push(String.fromCharCode(((ch & 0x1f) << 6) + (ch1 & 0x3f)))
                } else {
                    if (y.length < 3)break;
                    ch = y.shift();
                    var ch1 = y.shift();
                    var ch2 = y.shift();
                    x.push(String.fromCharCode(((ch & 0x0f) << 12) + ((ch1 & 0x3f) << 6) + (ch2 & 0x3f)))
                }
            }
        }

        function _decode(s) {
            var pads = 0, i, b10, imax = s.length, x = [], y = [];
            s = String(s);
            if (imax === 0) {
                return s
            }
            if (imax % 4 !== 0) {
                throw"Cannot decode base64"
            }
            if (s.charAt(imax - 1) === _PADCHAR) {
                pads = 1;
                if (s.charAt(imax - 2) === _PADCHAR) {
                    pads = 2
                }
                imax -= 4
            }
            for (i = 0; i < imax; i += 4) {
                var ch1 = _getbyte64(s, i);
                var ch2 = _getbyte64(s, i + 1);
                var ch3 = _getbyte64(s, i + 2);
                var ch4 = _getbyte64(s, i + 3);
                b10 = (_getbyte64(s, i) << 18) | (_getbyte64(s, i + 1) << 12) | (_getbyte64(s, i + 2) << 6) | _getbyte64(s, i + 3);
                y.push(b10 >> 16);
                y.push((b10 >> 8) & 0xff);
                y.push(b10 & 0xff);
                _decode_chars(y, x)
            }
            switch (pads) {
                case 1:
                    b10 = (_getbyte64(s, i) << 18) | (_getbyte64(s, i + 1) << 12) | (_getbyte64(s, i + 2) << 6);
                    y.push(b10 >> 16);
                    y.push((b10 >> 8) & 0xff);
                    break;
                case 2:
                    b10 = (_getbyte64(s, i) << 18) | (_getbyte64(s, i + 1) << 12);
                    y.push(b10 >> 16);
                    break
            }
            _decode_chars(y, x);
            if (y.length > 0)throw"Cannot decode base64";
            return x.join("")
        }

        function _get_chars(ch, y) {
            if (ch < 0x80)y.push(ch); else if (ch < 0x800) {
                y.push(0xc0 + ((ch >> 6) & 0x1f));
                y.push(0x80 + (ch & 0x3f))
            } else {
                y.push(0xe0 + ((ch >> 12) & 0xf));
                y.push(0x80 + ((ch >> 6) & 0x3f));
                y.push(0x80 + (ch & 0x3f))
            }
        }

        function _encode(s) {
            if (arguments.length !== 1) {
                throw"SyntaxError: exactly one argument required"
            }
            s = String(s);
            if (s.length === 0) {
                return s
            }
            var i, b10, y = [], x = [], len = s.length;
            i = 0;
            while (i < len) {
                _get_chars(s.charCodeAt(i), y);
                while (y.length >= 3) {
                    var ch1 = y.shift();
                    var ch2 = y.shift();
                    var ch3 = y.shift();
                    b10 = (ch1 << 16) | (ch2 << 8) | ch3;
                    x.push(_ALPHA.charAt(b10 >> 18));
                    x.push(_ALPHA.charAt((b10 >> 12) & 0x3F));
                    x.push(_ALPHA.charAt((b10 >> 6) & 0x3f));
                    x.push(_ALPHA.charAt(b10 & 0x3f))
                }
                i++
            }
            switch (y.length) {
                case 1:
                    var ch = y.shift();
                    b10 = ch << 16;
                    x.push(_ALPHA.charAt(b10 >> 18) + _ALPHA.charAt((b10 >> 12) & 0x3F) + _PADCHAR + _PADCHAR);
                    break;
                case 2:
                    var ch1 = y.shift();
                    var ch2 = y.shift();
                    b10 = (ch1 << 16) | (ch2 << 8);
                    x.push(_ALPHA.charAt(b10 >> 18) + _ALPHA.charAt((b10 >> 12) & 0x3F) + _ALPHA.charAt((b10 >> 6) & 0x3f) + _PADCHAR);
                    break
            }
            return x.join("")
        }

        return {decode: _decode, encode: _encode, VERSION: _VERSION}
    }(jQuery));
    return modal
});