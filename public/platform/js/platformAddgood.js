define(["jquery", "util", "colorpicker", 'poster', "bootstrap", "jconfirm",'jquery-ui'], function ($, util, colorpicker, poster) {
    var Addgood = {};
    /**
     * 微商来 - 专业移动应用开发商!
     * ========================================================= Copy right
     * 2018 广州领客信息科技股份有限公司, 保留所有权利。
     * ---------------------------------------------- 官方网址:
     * http://www.vslai.com 
     * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
     * =========================================================
     * 
     */
    Addgood.goodsSkuCreate = function (obj) {
        //默认实物商品
        var goods_type = 1;
        var valid_type = 0;
        var is_wxcard = 0;
        if (obj && obj !== undefined) {//编辑商品，数据初始化
            var goods_spec_format = obj.goods_spec_format;//SKU数据
            var sku_list = eval(obj.sku_list);//SKU数据
            var goods_attribute_list = eval(obj.goods_attribute_list);
            var poster_data = obj.poster_data;
            goods_type = obj.goods_type;
            valid_type = obj.valid_type;
            is_wxcard = obj.is_wxcard;
        }
        /*
         * 选中过颜色以及选中颜色类型的规格id
         * @type Number
         */
        var selectedColor = 0;
        var selectedColorSpecId = 0;
        /*
         * 选中过图片以及选中图片类型的规格id
         * @type Number
         */
        var selectedImage = 0;
        var selectedImageSpecId = 0;
        /**
         *  规格属性选择数组 
         */
        var $specObj = new Array();
        /**
         *  规格属性组拼sku数组
         */
        var $sku_array = new Array();
        /**
         * 临时表  用于存储库存值
         */
        var $temp_Obj = new Object();
        poster.initPoster(poster_data);
        //品类id
        var attr_id = $('#goods_attribute_id').val();
        $("#isgoods_attribute").removeClass("hidden");
        if (parseInt($("#goodsId").val()) > 0) {
            getGoodsSpecListByAttrId(attr_id, function () {
                editSkuData(goods_spec_format, sku_list);
                //加载属性
                $("#attribute_list tr").each(function () {
                    var attr_value_id = $(this).attr('id');
                    var value = $(this).find('.J-attr_name').html();//商品属性名称
                    var value_name = $(this).children("td:last");//具体的属性值
                    if (value != undefined && value != "") {
                        for (var i = 0; i < goods_attribute_list.length; i++) {
                            var curr = goods_attribute_list[i];
                            if (curr['attr_value_id'] == attr_value_id) {
                                if(value != curr['attr_value']){
                                    $(this).find('.J-attr_name').html(curr['attr_value']);
                                }
                                switch (value_name.find("input").attr("type")) {
                                    case "text":
                                        value_name.find("input").val(curr['attr_value_name']);
                                        break;
                                    case "radio":
                                        value_name.find("input").each(function () {
                                            if ($.trim($(this).val()) == $.trim(curr['attr_value_name'])) {
                                                $(this).attr("checked", "checked");
                                                return false;
                                            }
                                        })
                                        break;
                                    case "checkbox":
                                        value_name.find("input").each(function () {
                                            if ($.trim($(this).val()) == $.trim(curr['attr_value_name'])) {
                                                $(this).attr("checked", "checked");
                                            }
                                        })
                                        break;
                                }
                                if (value_name.find("input").attr("type") != "checkbox") {
                                    break;
                                }
                            }
                        }
                    }
                });
            });
        } else {
            getGoodsSpecListByAttrId(attr_id);
        }
        if (goods_type == 0){
            $("#verification_num").attr('required', 'required');
            $("#effective_day").attr('required', 'required');
        }
        if(storeStatus == 1){
        	getStoreList();
        }
        checkShipping();
        /*
         * 查询运费模板
         */
        function checkShipping() {
            
            if ($('input[name=shipping_fee_type]:checked').val() !== '2') {
                return;
            }
            if ($("#shipping_fee_id option:selected").attr('type') == '1') {
               $(".is_shipping_fee_id").removeClass("hidden");
            } else if ($("#shipping_fee_id option:selected").attr('type') == '3') {
                $(".is_shipping_fee_id_volume").removeClass("hidden");
            }  else if ($("#shipping_fee_id option:selected").attr('type') == '2') {
                $(".is_shipping_fee_id_num").removeClass("hidden");
            } 
        }
        /*
         * 根据商品重置规格数据
         * @param {type} spec_obj_str
         * @param {type} sku_data
         * @returns {undefined}
         */
        function editSkuData(spec_obj_str, sku_data) {
            updateSpecObjData(spec_obj_str);
            updateTempObjData(sku_data);
            if ($specObj.length > 0) {
                createTable();
            }
        }
        /*
         * 商品有，规格没有，则创建规格值
         * @param {type} spec_value
         * @returns {String}
         */
        function getGoodsSpecValueHTML(spec_value) {
            var html = '<article class="goods-sku-items"><label class="checkbox-inline">';
            if (parseInt(spec_value.show_type) == 2 && spec_value.spec_value_data == "") {
                spec_value.spec_value_data = "#000000";
            }
            switch (parseInt(spec_value.show_type)) {
                case 1:
                    //文字
                    html += '<input type="checkbox" name="label_list_' + spec_value.spec_id + '" class="specItemValue" value="' + spec_value.spec_value_data + '" data-spec_id="' + spec_value.spec_id + '" data-spec_name="' + spec_value.spec_name + '" data-spec_value_id="' + spec_value.spec_value_id + '" data-spec_value_data="' + spec_value.spec_value_data + '"><span class="goods-sku-items-name" data-status="edit_spec_value"  >' + spec_value.spec_value_name + '</span></label>';
                    break;
                case 2:
                    //颜色
                    html += '<input type="checkbox" name="label_list_' + spec_value.spec_id + '" class="specItemValue" value="' + spec_value.spec_value_data + '" data-spec_id="' + spec_value.spec_id + '" data-spec_name="' + spec_value.spec_name + '" data-spec_value_id="' + spec_value.spec_value_id + '" data-spec_value_data="' + spec_value.spec_value_data + '"><span class="goods-sku-items-name" data-status="edit_spec_value"  >' + spec_value.spec_value_name + '</span></label><input type="color" class="colorpicker" name="color" value="' + spec_value.spec_value_data + '">';
                    colorpicker('.colorpicker');
                    break;
                case 3:
                    //图片
                    html += '<input type="checkbox" name="label_list_' + spec_value.spec_id + '" class="specItemValue J-sku_pic" value="' + spec_value.spec_value_data + '" data-spec_id="' + spec_value.spec_id + '" data-spec_name="' + spec_value.spec_name + '" data-spec_value_id="' + spec_value.spec_value_id + '" data-spec_value_data="' + spec_value.spec_value_data + '"><span class="goods-sku-items-name" data-status="edit_spec_value"  >' + spec_value.spec_value_name + '</span></label>';
                    if (spec_value.spec_value_data_src) {
                        html += '<a href="javascript:void(0);" data-toggle="specPicture" class="spec-img-box"><img src="' + __IMG(spec_value.spec_value_data_src) + '"></a>';
                    } else {
                        html += '<a href="javascript:void(0);" data-toggle="specPicture" class="spec-img-box"><img src="/public/platform/images/goods_sku_add.png"></a>'
                    }
                    break;
                default:
                    //文字
                    html += '<input type="checkbox" name="label_list_' + spec_value.spec_id + '" class="specItemValue" value="' + spec_value.spec_value_data + '" data-spec_id="' + spec_value.spec_id + '" data-spec_name="' + spec_value.spec_name + '" data-spec_value_id="' + spec_value.spec_value_id + '" data-spec_value_data="' + spec_value.spec_value_data + '"><span class="goods-sku-items-name" data-status="edit_spec_value"  >' + spec_value.spec_value_name + '</span></label>';
                    break;
            }
            html += '</article>';
            html += getAddSpecValueHtml(spec_value);
            checkColorPicker();
            return html;
        }
        /*
         * 返回添加规格值html代码
         * @param {type} spec
         * @returns {String}
         */
        function getAddSpecValueHtml(spec) {
            var html = '<a href="javascript:void(0);" class="goods-value-add" data-show-type="' + spec.show_type + '" data-spec_id="' + spec.spec_id + '" data-spec_name="' + spec.spec_name + '"><i class="icon icon-add1 mr-04"></i>添加规格值</a>';
            return html;
        }
        /*
         * 根据规格类型，重置相应规格值
         * @param {type} spec
         * @returns {undefined}
         */
        function setSpecValueType(spec){
            
            if($('#spec_list').find(".goods-sku-block-" + spec.spec_id).length == 0){
                return false;
            }
            $(".goods-sku-block-" + spec.spec_id).find('.goods-sku-items ').each(function(){
                switch (parseInt(spec.show_type)) {
                    case 1:
                        //文字
                            $(this).find('.spec-img-box').remove();
                            $(this).find('.colorpicker').remove();
                            $(this).find('.sp-light').remove();
                        break;
                    case 2:
                        //颜色
                            $(this).find('.spec-img-box').remove();
                            $(this).find('.colorpicker').remove();
                            $(this).find('.sp-light').remove();
                            $(this).find('.specItemValue').attr('data-spec_value_data','#000000');
                            $(this).append('<input type="color" class="colorpicker"  name="color" value="#000000">');
                        break;
                    case 3:
                        //图片
                        $(this).find('.spec-img-box').remove();
                        $(this).find('.colorpicker').remove();
                        $(this).find('.sp-light').remove();
                        if(!$(this).find('.specItemValue').hasClass('J-sku_pic')){
                            $(this).find('.specItemValue').addClass('J-sku_pic');
                        }
                        $(this).append('<a href="javascript:void(0);" data-toggle="specPicture" class="spec-img-box"><img src="/public/platform/images/goods_sku_add.png"></a>');
                        break;
                    default:
                        //文字
                        $(this).find('.spec-img-box').remove();
                        $(this).find('.colorpicker').remove();
                        $(this).find('.sp-light').remove();
                        break;
                }
            })
        }
        //修改商品时 更新$specObj,并编辑页面结构
        function updateSpecObjData(spec_obj_str) {
            if (spec_obj_str != "") {
                $specObj = eval(spec_obj_str);
            }
            for (var i = 0; i < $specObj.length; i++) {
                var show_types = $specObj[i]["show_type"] >= 0 ? $specObj[i]["show_type"] : $specObj[i]["value"][0]["spec_show_type"];
                var text = '';//属性类型
                var spec = {
                    spec_id: $specObj[i]["spec_id"],
                    spec_name: $specObj[i]["spec_name"],
                    show_type: show_types
                }
                setSpecValueType(spec);
                switch (parseInt(show_types)) {
                    case 1:
                        //文字
                        text = '文字';
                        break;
                    case 2:
                        //颜色
                        text = '颜色';
                        if($specObj[i]["value"].length > 0){
                            selectedColor = 1;
                            selectedColorSpecId = $specObj[i]["spec_id"];
                        }
                        break;
                    case 3:
                        //图片
                        text = '图片';
                        if($specObj[i]["value"].length > 0){
                            selectedImage = 1;
                            selectedImageSpecId = $specObj[i]["spec_id"];
                        }
                        break;
                    default:
                        //文字
                        text = '文字';
                        break;
                }
                if ($(".goods-sku-block-" + $specObj[i]["spec_id"]).length == 0) {
                    
                    var html = '<tr class="spec_list goods-sku-block-' + $specObj[i]["spec_id"] + '">';
                    html += '<td><span class="goods-sku-items-name" data-status="edit_spec" data-spec_id="' + $specObj[i]["spec_id"] + '">' + $specObj[i]["spec_name"] + '</span></td>';
                    html += '<td><a class="text-primary shows_type" data-show_type="' + show_types + '" data-spec_id="' + $specObj[i]["spec_id"] + '" href="javascript:void(0);">' + text + '<i class="icon icon-drop-down ml-2"></i></a></td>';
                    html += '<td class="text-left">';
                    html += getAddSpecValueHtml(spec);
                    html += '</td>';
                    html += '</tr>';
                    html += '<tr class="last-tr"><td colspan="3" class="text-left"><a href="javascript:void(0);" class="goods-value-add" data-show-type="10"><i class="icon icon-add1 mr-04"></i>添加规格</a></td></tr>';
                    $("#spec_list tbody tr:last").remove();
                    $("#spec_list tbody").append(html);
                } else {
                    $(".goods-sku-block-" + $specObj[i]["spec_id"]).children('td:first').children('.goods-sku-items-name').html($specObj[i]["spec_name"]);
                    $(".goods-sku-block-" + $specObj[i]["spec_id"]).find('.goods-value-add').attr('data-show-type',show_types);
                    $(".goods-sku-block-" + $specObj[i]["spec_id"]).find('.shows_type').attr('data-show_type', show_types).html(text + '<i class="icon icon-drop-down ml-2"></i>');
                }
                for (var m = 0; m < $specObj[i]["value"].length; m++) {
                    var selected_obj = $("#spec_list tbody article .specItemValue[data-spec_id=" + $specObj[i]['spec_id'] + "][data-spec_value_id=" + $specObj[i]["value"][m]['spec_value_id'] + "]");

                    //如果没有此规格值 创建他
                    if (selected_obj.length == 0) {
                        $(".goods-sku-block-" + $specObj[i]["spec_id"]).find(".goods-value-add").remove();//删除当前的添加按钮
                        var spec_value = {
                            "spec_name": $specObj[i]["value"][m]['spec_name'],
                            "spec_value_data": $specObj[i]["value"][m]['spec_value_data'],
                            "show_type": show_types,
                            "spec_value_name": $specObj[i]["value"][m]['spec_value_name'],
                            "spec_id": $specObj[i]["value"][m]['spec_id'],
                            "spec_value_id": $specObj[i]["value"][m]['spec_value_id']
                        };
                        $(".goods-sku-block-" + $specObj[i]["spec_id"]).find("td:last").append(getGoodsSpecValueHTML(spec_value));//加载当前添加的规格值、以及最后那个添加按钮
                        var selected_obj = $("#spec_list tbody article .specItemValue[data-spec_id=" + $specObj[i]['spec_id'] + "][data-spec_value_id=" + $specObj[i]["value"][m]['spec_value_id'] + "]");
                    }
                    selected_obj.attr('checked', true);
                    selected_obj.siblings('span').text($specObj[i]["value"][m]["spec_value_name"]);
                    selected_obj.attr("data-spec_value_data", $specObj[i]["value"][m]['spec_value_data']);
                    selected_obj.attr("data-spec_name", $specObj[i]["value"][m]["spec_name"]);
                    selected_obj.parents('td').find('.goods-value-add').attr("data-spec_name", $specObj[i]["value"][m]["spec_name"]);
                    if (show_types == 2) {
                        //颜色
                        selected_obj.parents('.goods-sku-items').find(".colorpicker").val(selected_obj.data("spec_value_data") == "" ? "#000000" : selected_obj.data("spec_value_data"));
                    } else if (show_types == 3) {
                        //图片
                        var src = $specObj[i]["value"][m]["spec_value_data_src"];
                        if (src == "" || src == undefined) {
                            src = PLATFORMIMG + "/goods_sku_add.png";
                        } else {
                            src = __IMG(src);
                        }
                        selected_obj.parents('.goods-sku-items').find("img").attr("src", src);
                    }
                }
            }
        }
        //修改商品时 更新temp_obj
        function updateTempObjData(sku_data) {
            if ($specObj.length > 0) {
                $.each(sku_data, function (c, v) {
                    $temp_Obj[v.attr_value_items] = new Object();
                    $temp_Obj[v.attr_value_items]["sku_price"] = v.price;
                    $temp_Obj[v.attr_value_items]["market_price"] = v.market_price;
                    $temp_Obj[v.attr_value_items]["cost_price"] = v.cost_price;
                    $temp_Obj[v.attr_value_items]["stock_num"] = v.stock;
                    $temp_Obj[v.attr_value_items]["code"] = v.code;
                });
                $("#txtProductCount").attr("readonly", "readonly").prop('required', false);
                $("#txtProductSalePrice").attr("readonly", "readonly").prop('required', false);
            }

        }
        // 颜色选择器
        function colorpicker(element, callback) {
            $(element).spectrum({
                className: "",
                cancelText: "取消",
                chooseText: "确定",
                togglePaletteMoreText: "更多",
                togglePaletteLessText: "收缩",
                showInput: true,
                showInitial: true,
                showPalette: true,
                maxPaletteSize: 10,
                togglePaletteOnly: true,
                showAlpha: true,
                preferredFormat: "hex",
                hide: function (color) {
                    if ($.isFunction(callback)) {
                        callback(color);
                    }
                },
                palette: [
                    [
                        "rgb(0, 0, 0)",
                        "rgb(67, 67, 67)",
                        "rgb(102, 102, 102)",
                        "rgb(153, 153, 153)",
                        "rgb(183, 183, 183)",
                        "rgb(204, 204, 204)",
                        "rgb(217, 217, 217)",
                        "rgb(239, 239, 239)",
                        "rgb(243, 243, 243)",
                        "rgb(255, 255, 255)"
                    ],
                    [
                        "rgb(152, 0, 0)",
                        "rgb(255, 0, 0)",
                        "rgb(255, 153, 0)",
                        "rgb(255, 255, 0)",
                        "rgb(0, 255, 0)",
                        "rgb(0, 255, 255)",
                        "rgb(74, 134, 232)",
                        "rgb(0, 0, 255)",
                        "rgb(153, 0, 255)",
                        "rgb(255, 0, 255)"
                    ],
                    [
                        "rgb(230, 184, 175)",
                        "rgb(244, 204, 204)",
                        "rgb(252, 229, 205)",
                        "rgb(255, 242, 204)",
                        "rgb(217, 234, 211)",
                        "rgb(208, 224, 227)",
                        "rgb(201, 218, 248)",
                        "rgb(207, 226, 243)",
                        "rgb(217, 210, 233)",
                        "rgb(234, 209, 220)",
                        "rgb(221, 126, 107)",
                        "rgb(234, 153, 153)",
                        "rgb(249, 203, 156)",
                        "rgb(255, 229, 153)",
                        "rgb(182, 215, 168)",
                        "rgb(162, 196, 201)",
                        "rgb(164, 194, 244)",
                        "rgb(159, 197, 232)",
                        "rgb(180, 167, 214)",
                        "rgb(213, 166, 189)",
                        "rgb(204, 65, 37)",
                        "rgb(224, 102, 102)",
                        "rgb(246, 178, 107)",
                        "rgb(255, 217, 102)",
                        "rgb(147, 196, 125)",
                        "rgb(118, 165, 175)",
                        "rgb(109, 158, 235)",
                        "rgb(111, 168, 220)",
                        "rgb(142, 124, 195)",
                        "rgb(194, 123, 160)",
                        "rgb(166, 28, 0)",
                        "rgb(204, 0, 0)",
                        "rgb(230, 145, 56)",
                        "rgb(241, 194, 50)",
                        "rgb(106, 168, 79)",
                        "rgb(69, 129, 142)",
                        "rgb(60, 120, 216)",
                        "rgb(61, 133, 198)",
                        "rgb(103, 78, 167)",
                        "rgb(166, 77, 121)",
                        "rgb(133, 32, 12)",
                        "rgb(153, 0, 0)",
                        "rgb(180, 95, 6)",
                        "rgb(191, 144, 0)",
                        "rgb(56, 118, 29)",
                        "rgb(19, 79, 92)",
                        "rgb(17, 85, 204)",
                        "rgb(11, 83, 148)",
                        "rgb(53, 28, 117)",
                        "rgb(116, 27, 71)",
                        "rgb(91, 15, 0)",
                        "rgb(102, 0, 0)",
                        "rgb(120, 63, 4)",
                        "rgb(127, 96, 0)",
                        "rgb(39, 78, 19)",
                        "rgb(12, 52, 61)",
                        "rgb(28, 69, 135)",
                        "rgb(7, 55, 99)",
                        "rgb(32, 18, 77)",
                        "rgb(76, 17, 48)"
                    ]
                ]
            });
        }
        function checkColorPicker() {
            $(".colorpicker").each(function () {
                var elm = this;
                colorpicker(elm, function (color) {
                    var span = $(elm).parents('.goods-sku-items').find('.specItemValue');
                    span.attr('data-spec_value_data',$(elm).val());
                    var spec = {
                        flag: span.is(":checked"),
                        spec_id: span.attr("data-spec_id"),
                        spec_name: span.attr("data-spec_name"),
                        spec_value_id: span.attr("data-spec_value_id"),
                        spec_value_data: $(elm).val()
                    };
                    editSpecValueData(spec);
                });
            });
        }
        // 获取规格属性
        function getGoodsSpecListByAttrId(attr_id, callBack) {
            $.ajax({
                type: "post",
                url: __URL(PLATFORMMAIN + "/goods/getGoodsSpecListByAttrId"),
                async: true,
                data: {
                    "attr_id": attr_id
                },
                success: function (data) {
                    var spec_list = data.spec_list                  //规格列表
                    var attribute_list = data.attribute_list;       //属性列表
                    var brand_list = data.brand_list;
                    var spec_list_html = '';
                    var attribute_list_html = '';
                    // 规格
                    $("#isgoods_attribute").attr("style", "display:block");
                    $('#isgoods_attribute #spec_list tbody .spec_list').empty();
                    $('#isgoods_attribute #attribute_list tbody .attrbute_list').empty();
                    if (spec_list.length > 0) {
                        $('#spec_list tbody .spec_list').remove();
                        spec_list.forEach(function (item, i) {
                            spec_list_html += '<tr class="spec_list goods-sku-block-' + item.spec_id + '"><td><span class="goods-sku-items-name" data-status="edit_spec" data-spec_id="' + item.spec_id + '">' + item.spec_name + '</span></td>';
                            switch (parseInt(item.show_type)) {
                                case 1:
                                    //文字
                                    spec_list_html += '<td><a class="text-primary shows_type" data-show_type="' + item.show_type + '" data-spec_id="' + item.spec_id + '" href="javascript:void(0);">文字<i class="icon icon-drop-down ml-2"></i></a></td>';
                                    spec_list_html += '<td class="text-left">';
//                                    spec_list_html += '<div class="inline-block spec-item">';
                                    item.values.forEach(function (child, j) {
                                        spec_list_html += '<article class="goods-sku-items "><label class="checkbox-inline"><input type="checkbox" name="label_list_' + item.spec_id + '" class="specItemValue" value="' + child.spec_value_data + '" data-spec_id="' + item.spec_id + '" data-spec_name="' + item.spec_name + '" data-spec_value_id="' + child.spec_value_id + '" data-spec_value_data="' + child.spec_value_data + '"><span class="goods-sku-items-name" data-status="edit_spec_value"  >' + child.spec_value_name + '</span></label></article>';
                                    });
//                                    spec_list_html += '</div>';
                                    spec_list_html += '<a href="javascript:void(0);" class="goods-value-add" data-show-type="' + item.show_type + '" data-spec_id="' + item.spec_id + '" data-spec_name="' + item.spec_name + '"><i class="icon icon-add1 mr-04"></i>添加规格值</a>';
                                    spec_list_html += '</td>';
                                    break;
                                case 2:
                                    //颜色
                                    spec_list_html += '<td><a class="text-primary shows_type" data-show_type="' + item.show_type + '" data-spec_id="' + item.spec_id + '" href="javascript:void(0);">颜色<i class="icon icon-drop-down ml-2"></i></a></td>'
                                    spec_list_html += '<td class="text-left">';
//                                    spec_list_html += '<div class="inline-block spec-item">'
                                    item.values.forEach(function (child, j) {
                                        spec_list_html += '<article class="goods-sku-items "><label class="checkbox-inline"><input type="checkbox" name="label_list_' + item.spec_id + '" class="specItemValue" value="' + child.spec_value_data + '" data-spec_id="' + item.spec_id + '" data-spec_name="' + item.spec_name + '" data-spec_value_id="' + child.spec_value_id + '" data-spec_value_data="' + child.spec_value_data + '"><span class="goods-sku-items-name" data-status="edit_spec_value"  >' + child.spec_value_name + '</span></label><input type="color" class="colorpicker"  name="color" value="' + child.spec_value_data + '"></article>';
                                    });
//                                    spec_list_html += '</div>'
                                    spec_list_html += '<a href="javascript:void(0);" class="goods-value-add" data-show-type="' + item.show_type + '" data-spec_id="' + item.spec_id + '" data-spec_name="' + item.spec_name + '"><i class="icon icon-add1 mr-04"></i>添加规格值</a>';
                                    spec_list_html += '</td>';
                                    break;
                                case 3:
                                    //图片
                                    spec_list_html += '<td><a class="text-primary shows_type" data-show_type="' + item.show_type + '" data-spec_id="' + item.spec_id + '" href="javascript:void(0);">图片<i class="icon icon-drop-down ml-2"></i></a></td>'
                                    spec_list_html += '<td class="text-left">';
                                    item.values.forEach(function (child, j) {
                                        spec_list_html += '<article class="goods-sku-items "><label class="checkbox-inline"><input type="checkbox" name="label_list_' + item.spec_id + '" class="specItemValue J-sku_pic" value="' + child.spec_value_data + '" data-spec_id="' + item.spec_id + '" data-spec_name="' + item.spec_name + '" data-spec_value_id="' + child.spec_value_id + '" data-spec_value_data="' + child.spec_value_data + '"><span class="goods-sku-items-name" data-status="edit_spec_value"  >' + child.spec_value_name + '</span></label>';
                                        if (child.pic) {
                                            spec_list_html += '<a href="javascript:void(0);" data-toggle="specPicture" class="spec-img-box"><img src="' + __IMG(child.pic) + '"></a>';
                                        } else {
                                            spec_list_html += '<a href="javascript:void(0);" data-toggle="specPicture" class="spec-img-box"><img src="/public/platform/images/goods_sku_add.png"></a>'
                                        }
                                        spec_list_html += '</article>';
                                    });
                                    spec_list_html += '<a href="javascript:void(0);" class="goods-value-add" data-show-type="' + item.show_type + '" data-spec_id="' + item.spec_id + '" data-spec_name="' + item.spec_name + '"><i class="icon icon-add1 mr-04"></i>添加规格值</a>';
                                    spec_list_html += '</td>';
                                    break;
                                default:
                                    //文字
                                    spec_list_html += '<td><a class="text-primary shows_type" data-show_type="' + item.show_type + '" data-spec_id="' + item.spec_id + '" href="javascript:void(0);">文字<i class="icon icon-drop-down ml-2"></i></a></td>';
                                    spec_list_html += '<td class="text-left">';
//                                    spec_list_html += '<div class="inline-block spec-item">';
                                    item.values.forEach(function (child, j) {
                                        spec_list_html += '<article class="goods-sku-items "><label class="checkbox-inline"><input type="checkbox" name="label_list_' + item.spec_id + '" class="specItemValue" value="' + child.spec_value_data + '" data-spec_id="' + item.spec_id + '" data-spec_name="' + item.spec_name + '" data-spec_value_id="' + child.spec_value_id + '" data-spec_value_data="' + child.spec_value_data + '"><span class="goods-sku-items-name" data-status="edit_spec_value"  >' + child.spec_value_name + '</span></label></article>';
                                    });
//                                    spec_list_html += '</div>';
                                    spec_list_html += '<a href="javascript:void(0);" class="goods-value-add" data-show-type="' + item.show_type + '" data-spec_id="' + item.spec_id + '" data-spec_name="' + item.spec_name + '"><i class="icon icon-add1 mr-04"></i>添加规格值</a>';
                                    spec_list_html += '</td>';
                                    break;
                            }
                            spec_list_html += '</td></tr>'
                        })
                        $('#spec_list .last-tr').before(spec_list_html);
                    }
                    // 属性
                    if (attribute_list.length > 0) {
                        $('#attribute_list tbody .attrbute_list').remove();
                        attribute_list.forEach(function (item, i) {
                            attribute_list_html += '<tr class="attrbute_list" id="' + item.attr_value_id + '" type="' + item.type + '"><input type="hidden" name="attr_sort" value="' + item.sort + '"><td><span class="goods-sku-items-name J-attr_name" data-status="edit_attr">' + item.attr_value_name + '</span></td><td class="text-left">'
                            switch (parseInt(item.type)) {
                                case 1:
                                    //输入框
                                    attribute_list_html += '<article class="goods-sku-items"><input type="text" name="attr_value" class="form-control w-200 valid js-attribute-text" aria-invalid="false" data-attribute-value-id="' + item.attr_value_id + '" data-attribute-value="' + item.attr_value_name + '" data-attribute-sort="' + item.sort + '"></article>';
                                    break;
                                case 2:
                                    //单选框
                                    item.value_items.forEach(function (child, j) {
                                        attribute_list_html += '<article class="goods-sku-items"><label class="radio-inline"><input type="radio" class="js-attribute-radio" value="' + child + '" data-attribute-value-id="' + item.attr_value_id + '" data-attribute-value="' + item.attr_value_name + '"  name="attrvalue_' + item.attr_value_id + '" data-attribute-sort="' + item.sort + '"><span class="goods-sku-items-name" data-status="edit_attr_value">' + child + '</span></label></article>';
                                    });
//                                    attribute_list_html += '<a href="javascript:void(0);" class="goods-value-add" data-show-type="12"><i class="icon icon-add1 mr-04"></i>添加属性值</a>';
                                    break;
                                case 3:
                                    //复选框
                                    item.value_items.forEach(function (child, j) {
                                        attribute_list_html += '<article class="goods-sku-items"><label class="checkbox-inline"><input class="js-attribute-checkbox" type="checkbox" name="attr_value" value="' + child + '" data-attribute-value-id="' + item.attr_value_id + '" data-attribute-value="' + item.attr_value_name + '"  name="attr_value" data-attribute-sort="' + item.sort + '"><span class="goods-sku-items-name" data-status="edit_attr_value">' + child + '</span></label></article>';
                                    });
//                                    attribute_list_html += '<a href="javascript:void(0);" class="goods-value-add" data-show-type="13"><i class="icon icon-add1 mr-04"></i>添加属性值</a>';
                                    break;
                            }
                            attribute_list_html += '</td></tr>';
                        })
                        $('#attribute_list .last-tr').before(attribute_list_html);
                    }

                    //获取绑定品牌
                    var brand_html = '<option value="">请选择商品品牌</option>';
                    if (brand_list.length > 0) {
                        var brand_id = $('#select_brand_hidden').val();
                        
                        brand_list.forEach(function (v, k) {
                            var select = '';
                            if (v['brand_id'] == brand_id) {
                                select = 'selected';
                            }
                            brand_html += '<option value="' + v['brand_id'] + '" ' + select + '>' + v['brand_name'] + '</option>';
                        })
                    }
                    $("#brand_id").html(brand_html);
                    if (callBack != undefined) {
                        callBack();
                    }
                    checkColorPicker();
                },
                error: function () {
                    $("#goods_attribute_id").val(0);
                }
            })
        }
        function editSpecValueData(spec) {
            if (spec.flag) {
                var spec_id = spec.spec_id;
                var spec_value_id = spec.spec_value_id;
                var spec_name = spec.spec_name;
                var spec_value_data = spec.spec_value_data;
                var spec_value_data_src = spec.spec_value_data_src;
                var is_continue = false;
                for (var i = 0; i < $specObj.length; i++) {
                    if ($specObj[i].spec_id == spec_id) {
                        $.each($specObj[i]["value"], function (t, m) {
                            if (m["spec_value_id"] == spec_value_id) {
                                $specObj[i]["value"][t]["spec_value_data"] = spec_value_data;
                                $specObj[i]["value"][t]["spec_value_data_src"] = spec_value_data_src;
                            }
                        });
                    }
                    if (is_continue) {
                        break;
                    }

                }
            }
        }
        //同步$sku_array,临时表$temp_Obj的数据
        function synchroSkuValueData() {
            var sku_str = "";
            for (var i = 0; i < $sku_array.length; i++) {
                var sku_id = $sku_array[i]["id"];
                var value_array = new Array();
                $.each($sku_array, function (w, q) {
                    value_array = $temp_Obj[sku_id];

                })
                if (sku_str == "") {
                    sku_str = sku_id + "¦" + value_array["sku_price"] + "¦" + value_array["market_price"] + "¦" + value_array["cost_price"] + "¦" + value_array["stock_num"] + "¦" + value_array["code"];
                } else {
                    sku_str += "§" + sku_id + "¦" + value_array["sku_price"] + "¦" + value_array["market_price"] + "¦" + value_array["cost_price"] + "¦" + value_array["stock_num"] + "¦" + value_array["code"];
                }
            }
            return sku_str;
        }
        //通过分类获取绑定品类
        function getBindAttr(cid) {
            $.ajax({
                type: "post",
                data: {
                    "cid": cid,
                },
                url: __URL(PLATFORMMAIN + '/goods/getbindingattr'),
                success: function (data) {
                    var result = data.data;
                    $("#goods_attribute_id").val(result.attr_id);
                    getGoodsSpecListByAttrId(result.attr_id);
                    createTable();
                }
            });
        }
        //构建表格
        function createTable() {
            if ($specObj.length == 0) {
                $("#stock_table thead").empty();
                $("#stock_table tbody").empty();
                $("#stock_table").hide();
                $('input[name="txtProductSalePrice"]').removeAttr('readonly');
                $('input[name="txtProductMarketPrice"]').removeAttr('readonly');
                $('input[name="txtProductCostPrice"]').removeAttr('readonly');
                $('input[name="txtProductCount"]').removeAttr('readonly');
                $('input[name="item_no"]').removeAttr('readonly');
            } else {
                $("#stock_table").show();
                if ($('input[name="txtProductSalePrice"]').attr("readonly") != "readonly") {
                    $('input[name="txtProductSalePrice"]').attr("readonly", "readonly");
                }
                if ($('input[name="txtProductMarketPrice"]').attr("readonly") != "readonly") {
                    $('input[name="txtProductMarketPrice"]').attr("readonly", "readonly");
                }
                if ($('input[name="txtProductCostPrice"]').attr("readonly") != "readonly") {
                    $('input[name="txtProductCostPrice"]').attr("readonly", "readonly");
                }
                if ($('input[name="txtProductCount"]').attr("readonly") != "readonly") {
                    $('input[name="txtProductCount"]').attr("readonly", "readonly");
                }
                if ($('input[name="item_no"]').attr("readonly") != "readonly") {
                    $('input[name="item_no"]').attr("readonly", "readonly");
                }
            }

            var specArray = new Array();
            var each_num = 0;

            $.each($specObj, function (i, v) {
                var arr_length = v.value.length;
                var each_spec_name = v.spec_name;
                var spec_name_obj = {"each_length": arr_length, "spec_name": each_spec_name, "value": v.value}
                specArray.push(spec_name_obj);
                if (each_num == 0) {
                    each_num = arr_length;
                } else {
                    each_num = each_num * arr_length;
                }
            });

            //将规格数据 转化成sku数据
            createSkuData(specArray);

            var th_html = "<tr>";
            for (var q = 0; q < specArray.length; q++) {
                //给表头添加所选规格
                th_html += "<th class='vertical-middle'>" + specArray[q].spec_name + "</th>";
            }
            //表格表头
            th_html += '<th class="vertical-middle th-price">销售价（元）</th>';
            th_html += '<th class="vertical-middle th-price">市场价（元）</th>';
            th_html += '<th class="vertical-middle th-price">成本价（元）</th>';
            th_html += '<th class="vertical-middle th-stock">库存（件）</th>';
            th_html += '<th class="vertical-middle th-code">商品货号</th>';
            th_html += '</tr>';
            $("#stock_table thead").html(th_html);
            //建立表格
            var html = "";
            for (var i = 0; i < $sku_array.length; i++) {
                var child_id_string = $sku_array[i]["id"].toString();
                var child_name_string = $sku_array[i]["name"].toString();

                if (child_id_string.indexOf(";")) {
                    var child_id_array = child_id_string.split(";");

                } else {
                    var child_id_array = new Array(child_id_string);
                }
                if (child_name_string.indexOf(";")) {
                    var child_name_array = child_name_string.split(";");

                } else {
                    var child_name_array = new Array(child_name_string);
                }
                //将规格,规格值处理成 spec_id,spec_value_id;spec_id,spec_value_id 格式
                if ($temp_Obj[child_id_string] == undefined) {
                    $temp_Obj[child_id_string] = new Object();
                    $temp_Obj[child_id_string]["sku_price"] = "0";
                    $temp_Obj[child_id_string]["market_price"] = "0";
                    $temp_Obj[child_id_string]["cost_price"] = "0";
                    $temp_Obj[child_id_string]["stock_num"] = "0";
                    $temp_Obj[child_id_string]["code"] = "";
                }
                html += "<tr skuid='" + child_id_string + "'>";
                //循环属性
                $.each(child_name_array, function (m, t) {
                    //为属性添加唯一值
                    var start_index = 0;
                    var substr_str = "";
                    while (start_index <= m) {
                        if (child_id_array[start_index] != '') {
                            if (substr_str == "") {
                                substr_str = child_id_array[start_index];

                            } else {
                                substr_str += ";" + child_id_array[start_index]
                            }
                        }
                        start_index++;
                    }
                    html += '<td rowspan="1"  skuchild = "' + substr_str + '">' + t + '</td>';

                });

                html += '<td class="w15"><input type="number" min="0" step="0.01"  required data-visi-type="prices_1" name="sku_price" class="onblur form-control" value="' + $temp_Obj[child_id_string]["sku_price"] + '" ></td>';
                html += '<td class="w15"><input type="number" min="0" step="0.01"  required data-visi-type="prices_1" name="market_price"  class="onblur form-control" value="' + $temp_Obj[child_id_string]["market_price"] + '"></td>';
                html += '<td><input type="number" min="0" step="0.01" name="cost_price" class="form-control onblur" value="' + $temp_Obj[child_id_string]["cost_price"] + '"></td>';
                html += '<td class="w15"><input type="number" min="0"  autocomplete="off" mustNum="true" required data-visi-type="prices_1" name="stock_num" class="onblur form-control" value="' + $temp_Obj[child_id_string]["stock_num"] + '"/></td>';
                html += '<td><input type="text" name="code" class="form-control" maxlength="15" value="' + $temp_Obj[child_id_string]["code"] + '"/></td>';
                html += "</tr>";
            }
            var newArray = new Array();
            $.each(specArray, function (z, x) {
                newArray = newArray.concat(x.value);
            });
            $("#stock_table tbody").html(html);
            // //合并单元格
            mergeTable();
        }
        //将对象处理成表格数据
        function createSkuData($specArray) {
            var $length = $specArray.length;
            $sku_array = new Array();
            if ($length > 0) {
                var $spec_value_obj = $specArray[0]["value"];
                $.each($spec_value_obj, function (i, v) {
                    var $spec_id = v.spec_id
                    var $spec_value_id = v.spec_value_id;
                    var $spec_value = v.spec_value_name;
                    var $sku_obj = new Object();
                    $sku_obj.id = $spec_id + ":" + $spec_value_id;
                    $sku_obj.name = $spec_value;
                    $sku_array.push($sku_obj);
                });
            }
            for (var $i = 1; $i < $length; $i++) {
                var $spec_val_obj = $specArray[$i]["value"];
                var $length_val = $spec_val_obj.length;
                var $sku_copy_array = new Array();
                $.each($sku_array, function (i, v) {
                    var $old_id = v.id;
                    var $old_name = v.name;
                    for (var $y = 0; $y < $length_val; $y++) {
                        var $spec_id = $spec_val_obj[$y].spec_id;
                        var $id = $spec_val_obj[$y].spec_value_id;
                        var $name = $spec_val_obj[$y].spec_value_name;
                        var $copy_obj = new Object();
                        $copy_obj.id = $old_id + ";" + $spec_id + ":" + $id;
                        $copy_obj.name = $old_name + ";" + $name;
                        $sku_copy_array.push($copy_obj);
                    }
                });
                $sku_array = $sku_copy_array;
            }
        }
        function specObj(spec_name, spec_id, spec_value_name, spec_value_id, spec_show_type, spec_value_data, is_selected, spec_value_data_src) {
            var is_have = 0;
            if(is_selected){
                if(spec_show_type == '3'){
                    selectedImage = 1;
                    selectedImageSpecId = spec_id;
                }else if(spec_show_type == '2'){
                    selectedColor = 1;
                    selectedColorSpecId = spec_id;
                }
            }
            for (var i = 0; i < $specObj.length; i++) {
                if ($specObj[i].spec_id == spec_id) {
                    if (is_selected == 1) {
                        $specObj[i]["value"].push({"spec_value_name": spec_value_name, "spec_name": spec_name, "spec_id": spec_id, "spec_value_id": spec_value_id, "spec_value_data": spec_value_data, "spec_value_data_src": spec_value_data_src});
                        is_have = 1;
                    } else {
                        SpliceArrayItem($specObj[i].value, spec_value_id);
                        if ($specObj[i].value.length == 0) {
                            if(spec_show_type == '3'){
                                selectedImage = 0;
                                selectedImageSpecId = 0;
                            }else if(spec_show_type == '2'){
                                selectedColor = 0;
                                selectedColorSpecId = 0;
                            }
                            $specObj.splice(i, 1);
                        }
                    }
                }
            }
            if (is_selected == 1) {
                //第一次选此规格
                if (is_have == 0) {
                    //给此规格添加对象内部空间 并添加此属性
                    var obj_length = $specObj.length;
                    $specObj[obj_length] = new Object();
                    $specObj[obj_length].spec_name = spec_name;
                    $specObj[obj_length].spec_id = spec_id;
                    $specObj[obj_length].show_type = spec_show_type;
                    $specObj[obj_length]["value"] = new Array();
                    $specObj[obj_length]["value"].push({"spec_value_name": spec_value_name, "spec_name": spec_name, "spec_id": spec_id, "spec_value_id": spec_value_id, "spec_value_data": spec_value_data});
                }
            }
        }
        // 删除数组中的指定元素
        function SpliceArrayItem(arr, spec_value_id) {
            for (var i = 0; i < arr.length; i++) {
                if (arr[i]["spec_value_id"] == spec_value_id) {
                    arr.splice(i, 1);
                    break;
                }
            }
        }
        //规格展示类型修改
        function editSpecShowType(event) {
            var spec_id = event.spec_id;
            var spec_show_type = event.spec_show_type;
            for (var i = 0; i < $specObj.length; i++) {
                if ($specObj[i].spec_id == spec_id) {
                    $specObj[i]['show_type'] = spec_show_type;
                }
            }
        }
        //规格展示名称修改
        function editSpecName(event) {
            var spec_id = event.spec_id;
            var spec_name = event.spec_name;
            for (var i = 0; i < $specObj.length; i++) {
                if ($specObj[i].spec_id == spec_id) {
                    $specObj[i]['spec_name'] = spec_name;
                }
            }
        }
        function contains(arrays, obj) {
            var i = arrays.length;
            while (i--) {
                if (arrays[i] == obj) {
                    return i;
                }
            }
            return false;
        }
        //合并单元格
        function mergeTable() {
            for (var i = 0; i < $sku_array.length; i++) {
                var child_id_string = $sku_array[i]["id"].toString();
                var child_id_array = child_id_string.split(";");
                var sear_str = "";
                $.each(child_id_array, function (w, q) {
                    if (sear_str == "") {
                        sear_str += q;
                    } else {
                        sear_str += ";" + q;
                    }
                    if ($("td[skuchild = '" + sear_str + "']").length > 1) {
                        var check_array = $("td[skuchild = '" + sear_str + "']");
                        for (var $i = 0; $i < check_array.length; $i++) {
                            $check_obj = $(check_array[$i]);
                            if ($i == 0) {
                                $check_obj.attr("rowspan", check_array.length);
                            } else {
                                $check_obj.remove();
                            }

                        }
                    }
                });
            }
        }
        function PackageProductInfo() {
            // 初始化一个实体 将页面所需的数据存放到对象中
            var shop_type = $("#shop_type").val();
            checkColorPicker();//处理颜色类型的规格
            var productViewObj = new Object();
            productViewObj.goodsId = $("#goodsId").val();// 商品id 11号目前为死值 0
            productViewObj.title = $("#txtProductTitle").val();// 商品标题
            productViewObj.introduction = $("#txtIntroduction").val();// 商品简介，促销语
            productViewObj.categoryId = $("#tbcNameCategory").attr("cid");// 商品类目 
            var category_extend_id = "";
            $(".J-extend-category").each(function () {
                if (category_extend_id == "") {
                    category_extend_id = $(this).data("cid");
                } else {
                    category_extend_id += "," + $(this).data("cid");
                }
            });
            productViewObj.categoryExtendId = category_extend_id;// 商品扩展类目
            // 12号 商品类目；
            productViewObj.market_price = $("#txtProductMarketPrice").val() == "" ? 0 : $("#txtProductMarketPrice").val();// 市场价
            productViewObj.price = $("#txtProductSalePrice").val() == "" ? 0 : $("#txtProductSalePrice").val();// 销售价
            productViewObj.cost_price = $("#txtProductCostPrice").val() == "" ? 0 : $("#txtProductCostPrice").val();// 成本价
            productViewObj.libiary_goodsid = $("#libiary_goodsid").val(); // 商品库id
            productViewObj.base_sales = $("#BasicSales").val() == '' ? 0 : $("#BasicSales").val();// 基础销量
            productViewObj.base_good = $("#BasicPraise").val() == '' ? 0 : $("#BasicPraise").val();// 基础点赞数
            productViewObj.base_share = $("#BasicShare").val() == '' ? 0 : $("#BasicShare").val();// 基础分享数
            productViewObj.code = $("#txtProductCodeA").val();// 商品编码
            productViewObj.item_no = $("#item_no").val();// 商品货号
            productViewObj.state = $("input[name='state']:checked").val();// 上下架标记
            productViewObj.display_stock = $('.controls input[name="stock"]:checked ').val();// 是否显示库存
            productViewObj.stock = $('#txtProductCount').val();// 总库存
            productViewObj.minstock = $("#txtMinStockLaram").val();// 库存预警数
            productViewObj.min_buy = $("#minBuy").val() == "" ? 0 : $("#minBuy").val();// 最少购买数
            productViewObj.key_words = $("#txtKeyWords").val();//商品关键词
            // productViewObj.description = UE.getEditor('editor').getContent();// 商品详情描述
            productViewObj.description = $("#UE-detail").data("content");// 商品详情描述
            productViewObj.shipping_fee = $("#shipping_fee").val();// 统一运费
            productViewObj.shipping_fee_id = $("#shipping_fee_id").val();
            productViewObj.video_id = $("#video_id").val();
            productViewObj.point_deduction_max = parseInt($("#point_deduction_max").val());
            productViewObj.point_return_max = parseInt($("#point_return_max").val());
            productViewObj.max_buy = $("#maxLimitBuy").val() == "" ? 0 : parseInt($("#maxLimitBuy").val());// 最大限购
            productViewObj.single_limit_buy = $("#singleLimitBuy").val() == '' ? 0 : parseInt($("#singleLimitBuy").val());//单次限购
            var shopCategoryText = "";
            $(".goods-group-line .goods-gruop-select").each(function () {
                if ($(this).val() > 0) {
                    shopCategoryText += $(this).val() + ",";
                }
            })
            if (shopCategoryText != "") {
                shopCategoryText = shopCategoryText.substring(0, shopCategoryText.length - 1);
                var goodsgroup_array = shopCategoryText.split(",");
                var goodsgroup_array = undulpicate(goodsgroup_array);
                shopCategoryText = goodsgroup_array.join(",");
            }
            productViewObj.groupArray = shopCategoryText;
            productViewObj.supplierId = $("#supplierSelect").val();//供货商
            productViewObj.brandId = $("#brand_id").val();//品牌id
            var img_id_arr = "";// 商品主图
            // var img_obj = $(".upload_img_id");
            var img_obj = $("input[name='upload_img_id']");
            for (var $i = 0; $i < img_obj.length; $i++) {
                var $checkObj = $(img_obj[$i]);
                if (img_id_arr == "") {
                    img_id_arr = $checkObj.val();
                } else {
                    img_id_arr += "," + $checkObj.val();
                }
            }
            productViewObj.picture = img_id_arr.split(",")[0];
            var imageVals = img_id_arr;// 在页面中获取的
            productViewObj.imageArray = imageVals;// 商品图片分组
            //sku规格图片
            var sku_img_obj = $('#spec_list').find(".J-sku_pic");
            var sku_picture_obj = new Array();
            for (var $i = 0; $i < sku_img_obj.length; $i++) {
                var $checkObj = $(sku_img_obj[$i]);
                if(!$checkObj.is(":checked")){
                    continue;
                }
                var spec_id = $checkObj.parents('.goods-sku-items').find('.J-sku_pic').attr("data-spec_id");
                var spec_value_id = $checkObj.parents('.goods-sku-items').find('.J-sku_pic').attr("data-spec_value_id");
                var img_id = $checkObj.attr("data-spec_value_data");
                var is_have = 0;
                for (var i = 0; i < sku_picture_obj.length; i++) {
                    if (sku_picture_obj[i].spec_id == spec_id && sku_picture_obj[i].spec_value_id == spec_value_id) {
                        sku_picture_obj[i]["img_ids"] = sku_picture_obj[i]["img_ids"] + "," + img_id;
                        is_have = 1;
                    }
                }
                if (is_have == 0) {
                    //给此规格添加对象内部空间 并添加此属性
                    var obj_length = sku_picture_obj.length;
                    sku_picture_obj[obj_length] = new Object();
                    sku_picture_obj[obj_length].spec_id = spec_id;
                    sku_picture_obj[obj_length].spec_value_id = spec_value_id;
                    sku_picture_obj[obj_length]["img_ids"] = img_id;

                }
            }
            productViewObj.sku_picture_vlaues = JSON.stringify(sku_picture_obj);
            productViewObj.skuArray = synchroSkuValueData();
            productViewObj.goods_spec_format = JSON.stringify($specObj);
            productViewObj.goods_attribute_id = $("#goods_attribute_id").val();
            productViewObj.sort = $("#goodsSort").val();
            var goods_attribute_arr = new Array();
            $(".js-attribute-text").each(function () {
                if ($(this).val()) {
                    var goods_attribute = {
                        attr_value_id: $(this).attr("data-attribute-value-id"),
                        attr_value: $(this).attr("data-attribute-value"),
                        attr_value_name: $(this).val(),
                        sort: $(this).attr("data-attribute-sort")
                    };
                    goods_attribute_arr.push(goods_attribute);
                }
            });

            $(".js-attribute-radio").each(function () {
                if ($(this).is(":checked")) {
                    var goods_attribute = {
                        attr_value_id: $(this).attr("data-attribute-value-id"),
                        attr_value: $(this).attr("data-attribute-value"),
                        attr_value_name: $(this).val(),
                        sort: $(this).attr("data-attribute-sort")
                    };
                    goods_attribute_arr.push(goods_attribute);
                }
            });

            $(".js-attribute-checkbox").each(function () {

                if ($(this).is(":checked")) {
                    var goods_attribute = {
                        attr_value_id: $(this).attr("data-attribute-value-id"),
                        attr_value: $(this).attr("data-attribute-value"),
                        attr_value_name: $(this).val(),
                        sort: $(this).attr("data-attribute-sort")
                    };
                    goods_attribute_arr.push(goods_attribute);
                }
            });
            productViewObj.goods_attribute = "";
            if (goods_attribute_arr.length > 0) {
                productViewObj.goods_attribute = JSON.stringify(goods_attribute_arr);
            }
            // 积分购买设置
            productViewObj.integration_available_use = $("#integration_available_use").val() == '' ? 0 : $("#integration_available_use").val();
            productViewObj.integration_available_give = $("#integration_available_give").val() == '' ? 0 : $("#integration_available_give").val();
            productViewObj.goods_class = $("#class_tbname").attr("cid") == '' ? 0 : $("#class_tbname").attr("cid");
            productViewObj.goods_returnRate = $("#txtGoodsReturnRate").val() == '' ? 0 : $("#txtGoodsReturnRate").val();
            if (shop_type == 1) {
                productViewObj.sup_shopid = $("#sup_shopidselect").val();
                productViewObj.sale_area = $("#txtGoodsAreasid").val();
                productViewObj.sup_price = $("#txtProductSupplyPrice").val();
                productViewObj.cb_cost_price = $("#txtProductCBCostPrice").val();
            } else {
                productViewObj.sup_shopid = 0;
                productViewObj.sale_area = "";
                productViewObj.sup_price = 0;
                productViewObj.cb_cost_price = 0;
            }
            //productViewObj.point_exchange_type = $("#integralSelect").val();
            productViewObj.point_exchange_type = $("input[name='integralSelect']:checked").val();
            productViewObj.province_id = $("#provinceSelect").val();// 商品所在地：省
            productViewObj.city_id = $("#citySelect").val();// 商品所在地：市
            productViewObj.qrcode = $("#hidden_qrcode").val();
            //物流信息
            productViewObj.goods_weight = $("#goods_weight").val();
            productViewObj.goods_volume = $("#goods_volume").val();
            productViewObj.goods_count = $("#goods_count").val();
            productViewObj.shipping_fee_type = $("input[name='shipping_fee_type']:checked").val();

            //核销门店
            var store_list = [];
            if(storeStatus == 1){
                $("input[name ='select_store_id[]']:checked").each(function () {
                    store_list.push($(this).val());
                });
                if (store_list == '' && goods_type == 0) {
                	productViewObj.code = -1;
                	productViewObj.message = '请选择核销门店';
                    return productViewObj;
                }
            }
            productViewObj.store_list = store_list;
            //核销信息
            var verificationinfo = new Object();
            if (goods_type == 0) {
                verificationinfo.verification_num = $("#verification_num").val(); //核销次数
                verificationinfo.card_type = $("input[name='card_models']:checked").val(); //卡包模式
                verificationinfo.valid_type = $("input[name='effective_type']:checked").val(); //有效类型
                verificationinfo.valid_days = $("#effective_day").val(); //有效天数
                verificationinfo.end_time = $("input[name='expiration_time']").val(); //有效期截止时间
            }
            //卡券信息
            var cardinfo = new Object();
            var store_service = [];
            $("input[name ='store_service']:checked").each(function () {
                store_service.push($(this).val());
            })
            var card_switch = $("input[name='card_switch']").is(':checked')?1:2;
            productViewObj.is_wxcard = card_switch;
            cardinfo.card_switch = card_switch;
            if (card_switch == 1) {
                var card_color = $("#cart_color").val();
                cardinfo.card_title = $("#wxCard_setTitle").val();    //卡券标题
                cardinfo.card_descript = $("#wxCard_des").val();    //卡券详情
                cardinfo.card_pic_id = $("input[name='upload_wxCard_img_id']").val();    //卡券图片ID
                cardinfo.op_tips = $("#op_tips").val();             //操作提示
                cardinfo.send_set = $("input[name='make_money']:checked").val();   //赠送开关
                cardinfo.store_service = store_service;    //商户服务
                cardinfo.card_color = card_color;   //卡券颜色
            }
            //标签信息
            var is_new = '';
            var is_recommend = '';
            var is_hot = '';
            var is_promotion = '';
            var is_shipping_free = '';
            productViewObj.is_new = is_new;

            if ($("#is_new").is(':checked') == true) {
                is_new = 1;
            }
            if ($("#is_recommend").is(':checked') == true) {
                is_recommend = 1;
            }
            if ($("#is_hot").is(':checked') == true) {
                is_hot = 1;
            }
            if ($("#is_promotion").is(':checked') == true) {
                is_promotion = 1;
            }
            if ($("#is_shipping_free").is(':checked') == true) {
                is_shipping_free = 1;
            }
            productViewObj.is_new = is_new;
            productViewObj.is_recommend = is_recommend;
            productViewObj.is_hot = is_hot;
            productViewObj.is_promotion = is_promotion;
            productViewObj.is_shipping_free = is_shipping_free;
            productViewObj.card_switch = card_switch;
            productViewObj.cardinfo = cardinfo;
            productViewObj.verificationinfo = verificationinfo;
            productViewObj.goods_type = goods_type;
            productViewObj.video_id = $("input[name='upload_video_id']").val();
            productViewObj.point_deduction_max = parseInt($("#point_deduction_max").val());
            productViewObj.point_return_max = parseInt($("#point_return_max").val());
            productViewObj.base_sales = $("#BasicSales").val() == '' ? 0 : $("#BasicSales").val();// 基础销量
            var data_val = {};
            //是否参与分销
            var distribution_obj = {};
            var is_distribution = $('input[name=is_distribution]:checked').val();
            var distribution_rule = $('input[name=distribution_rule]').is(':checked') ? 1 : 2;
            var recommend_type = $('input[name=recommend_type]:checked').val();
            var level_rule = $('input[name=level_rule]').is(':checked') ? 1 : 2;
            data_val.is_distribution = is_distribution;
            data_val.distribution_rule = distribution_rule;
            //分销复购独立设置
            var buyagain_distribution_obj = {};
            var buyagain = $('input[name=buyagain]').is(':checked') ? 1 : 2; //是否开启复购
            var buyagain_level_rule = $('input[name=buyagain_level_rule]').is(':checked') ? 1 : 2; //是否开启等级复购
            var buyagain_recommend_type = $('input[name=buyagain_recommend_type]:checked').val(); //复购返佣类型
            data_val.buyagain = buyagain;
            data_val.buyagain_level_rule = buyagain_level_rule;
            data_val.buyagain_recommend_type = buyagain_recommend_type;
            if(buyagain == "1" && buyagain_recommend_type && buyagain_level_rule == 2){
                if (buyagain_recommend_type == 1) {
                    var buyagain_first_rebate = $('#buyagain_first_rebate').val() == '' ? '0' : $('#buyagain_first_rebate').val();
                    var buyagain_second_rebate = $('#buyagain_second_rebate').val() == '' ? '0' : $('#buyagain_second_rebate').val();
                    var buyagain_third_rebate = $('#buyagain_third_rebate').val() == '' ? '0' : $('#buyagain_third_rebate').val();
                    var buyagain_first_point = $('#buyagain_first_point').val() == '' ? '0' : $('#buyagain_first_point').val();
                    var buyagain_second_point = $('#buyagain_second_point').val() == '' ? '0' : $('#buyagain_second_point').val();
                    var buyagain_third_point = $('#buyagain_third_point').val() == '' ? '0' : $('#buyagain_third_point').val();
                } else {
                    var buyagain_first_rebate1 = $('#buyagain_first_rebate1').val() == '' ? '0' : $('#buyagain_first_rebate1').val();
                    var buyagain_second_rebate1 = $('#buyagain_second_rebate1').val() == '' ? '0' : $('#buyagain_second_rebate1').val();
                    var buyagain_third_rebate1 = $('#buyagain_third_rebate1').val() == '' ? '0' : $('#buyagain_third_rebate1').val();
                    var buyagain_first_point1 = $('#buyagain_first_point1').val() == '' ? '0' : $('#buyagain_first_point1').val();
                    var buyagain_second_point1 = $('#buyagain_second_point1').val() == '' ? '0' : $('#buyagain_second_point1').val();
                    var buyagain_third_point1 = $('#buyagain_third_point1').val() == '' ? '0' : $('#buyagain_third_point1').val();
                }
                buyagain_distribution_obj.buyagain_recommend_type = buyagain_recommend_type;
                buyagain_distribution_obj.buyagain_level_rule = buyagain_level_rule;
                buyagain_distribution_obj.buyagain_first_rebate = buyagain_first_rebate;
                buyagain_distribution_obj.buyagain_second_rebate = buyagain_second_rebate;
                buyagain_distribution_obj.buyagain_third_rebate = buyagain_third_rebate;
                buyagain_distribution_obj.buyagain_first_point = buyagain_first_point;
                buyagain_distribution_obj.buyagain_second_point = buyagain_second_point;
                buyagain_distribution_obj.buyagain_third_point = buyagain_third_point;
                buyagain_distribution_obj.buyagain_first_rebate1 = buyagain_first_rebate1;
                buyagain_distribution_obj.buyagain_second_rebate1 = buyagain_second_rebate1;
                buyagain_distribution_obj.buyagain_third_rebate1 = buyagain_third_rebate1;
                buyagain_distribution_obj.buyagain_first_point1 = buyagain_first_point1;
                buyagain_distribution_obj.buyagain_second_point1 = buyagain_second_point1;
                buyagain_distribution_obj.buyagain_third_point1 = buyagain_third_point1;
                data_val.buyagain_distribution_val = JSON.stringify(buyagain_distribution_obj);
            }else if(buyagain == "1" && buyagain_recommend_type && buyagain_level_rule == 1){
                var buyagain_first_rebate = [];
                var buyagain_second_rebate = [];
                var buyagain_third_rebate = [];
                var buyagain_first_point = [];
                var buyagain_second_point = [];
                var buyagain_third_point = [];
                var buyagain_first_rebate1 = [];
                var buyagain_second_rebate1 = [];
                var buyagain_third_rebate1 = [];
                var buyagain_first_point1 = [];
                var buyagain_second_point1 = [];
                var buyagain_third_point1 = [];
                var levelids = $("#level_ids").val();
                var arr = levelids.split(',');
                if (buyagain_recommend_type == 1) {
                    for (var jj = 0; jj < arr.length; jj++) {
                        if ($('#buyagain_level_first_rebate_' + arr[jj]).val() == '') {
                            buyagain_first_rebate.push('0');
                        } else {
                            buyagain_first_rebate.push($('#buyagain_level_first_rebate_' + arr[jj]).val());
                        }
                        if ($('#buyagain_level_second_rebate_' + arr[jj]).val() == '') {
                            buyagain_second_rebate.push('0');
                        } else {
                            buyagain_second_rebate.push($('#buyagain_level_second_rebate_' + arr[jj]).val());
                        }
                        if ($('#buyagain_level_third_rebate_' + arr[jj]).val() == '') {
                            buyagain_third_rebate.push('0');
                        } else {
                            buyagain_third_rebate.push($('#buyagain_level_third_rebate_' + arr[jj]).val());
                        }
                        if ($('#buyagain_level_first_point_' + arr[jj]).val() == '') {
                            buyagain_first_point.push('0');
                        } else {
                            buyagain_first_point.push($('#buyagain_level_first_point_' + arr[jj]).val());
                        }
                        if ($('#buyagain_level_second_point_' + arr[jj]).val() == '') {
                            buyagain_second_point.push('0');
                        } else {
                            buyagain_second_point.push($('#buyagain_level_second_point_' + arr[jj]).val());
                        }
                        if ($('#buyagain_level_third_point_' + arr[jj]).val() == '') {
                            buyagain_third_point.push('0');
                        } else {
                            buyagain_third_point.push($('#buyagain_level_third_point_' + arr[jj]).val());
                        }

                    }
                    
                } else {
                    for (var g = 0; g < arr.length; g++) {
                        
                        if ($('#buyagain_level_first_rebate1_' + arr[g]).val() == "") {
                            buyagain_first_rebate1.push('0');
                        } else {
                            buyagain_first_rebate1.push($('#buyagain_level_first_rebate1_' + arr[g]).val());
                        }
                        if ($('#buyagain_level_second_rebate1_' + arr[g]).val() == '') {
                            buyagain_second_rebate1.push('0');
                        } else {
                            buyagain_second_rebate1.push($('#buyagain_level_second_rebate1_' + arr[g]).val());
                        }
                        if ($('#buyagain_level_third_rebate1_' + arr[g]).val() == '') {
                            buyagain_third_rebate1.push('0');
                        } else {
                            buyagain_third_rebate1.push($('#buyagain_level_third_rebate1_' + arr[g]).val());
                        }
                        if ($('#buyagain_level_first_point1_' + arr[g]).val() == '') {
                            buyagain_first_point1.push('0');
                        } else {
                            buyagain_first_point1.push($('#buyagain_level_first_point1_' + arr[g]).val());
                        }
                        if ($('#buyagain_level_second_point1_' + arr[g]).val() == '') {
                            buyagain_second_point1.push('0');
                        } else {
                            buyagain_second_point1.push($('#buyagain_level_second_point1_' + arr[g]).val());
                        }
                        if ($('#buyagain_level_third_point1_' + arr[g]).val() == '') {
                            buyagain_third_point1.push('0');
                        } else {
                            buyagain_third_point1.push($('#buyagain_level_third_point1_' + arr[g]).val());
                        }
                    }
                }
                    buyagain_distribution_obj.buyagain_recommend_type = buyagain_recommend_type;
                    buyagain_distribution_obj.buyagain_level_rule = buyagain_level_rule;
                    buyagain_distribution_obj.level_ids = arr;
                    buyagain_distribution_obj.buyagain_first_rebate = buyagain_first_rebate;
                    buyagain_distribution_obj.buyagain_second_rebate = buyagain_second_rebate;
                    buyagain_distribution_obj.buyagain_third_rebate = buyagain_third_rebate;
                    buyagain_distribution_obj.buyagain_first_point = buyagain_first_point;
                    buyagain_distribution_obj.buyagain_second_point = buyagain_second_point;
                    buyagain_distribution_obj.buyagain_third_point = buyagain_third_point;
                    buyagain_distribution_obj.buyagain_first_rebate1 = buyagain_first_rebate1;
                    buyagain_distribution_obj.buyagain_second_rebate1 = buyagain_second_rebate1;
                    buyagain_distribution_obj.buyagain_third_rebate1 = buyagain_third_rebate1;
                    buyagain_distribution_obj.buyagain_first_point1 = buyagain_first_point1;
                    buyagain_distribution_obj.buyagain_second_point1 = buyagain_second_point1;
                    buyagain_distribution_obj.buyagain_third_point1 = buyagain_third_point1;
                    data_val.buyagain_distribution_val = JSON.stringify(buyagain_distribution_obj);
            }else{
                data_val.buyagain_distribution_val = '';
            }
            if (distribution_rule == '1' && recommend_type && level_rule == 2) {
                if (recommend_type == 1) {
                    var first_rebate = $('#first_rebate').val() == '' ? '0' : $('#first_rebate').val();
                    var second_rebate = $('#second_rebate').val() == '' ? '0' : $('#second_rebate').val();
                    var third_rebate = $('#third_rebate').val() == '' ? '0' : $('#third_rebate').val();
                    var first_point = $('#first_point').val() == '' ? '0' : $('#first_point').val();
                    var second_point = $('#second_point').val() == '' ? '0' : $('#second_point').val();
                    var third_point = $('#third_point').val() == '' ? '0' : $('#third_point').val();
                } else {
                    var first_rebate1 = $('#first_rebate1').val() == '' ? '0' : $('#first_rebate1').val();
                    var second_rebate1 = $('#second_rebate1').val() == '' ? '0' : $('#second_rebate1').val();
                    var third_rebate1 = $('#third_rebate1').val() == '' ? '0' : $('#third_rebate1').val();
                    var first_point1 = $('#first_point1').val() == '' ? '0' : $('#first_point1').val();
                    var second_point1 = $('#second_point1').val() == '' ? '0' : $('#second_point1').val();
                    var third_point1 = $('#third_point1').val() == '' ? '0' : $('#third_point1').val();
                }
                distribution_obj.recommend_type = recommend_type;
                distribution_obj.level_rule = level_rule;
                distribution_obj.first_rebate = first_rebate;
                distribution_obj.second_rebate = second_rebate;
                distribution_obj.third_rebate = third_rebate;
                distribution_obj.first_point = first_point;
                distribution_obj.second_point = second_point;
                distribution_obj.third_point = third_point;
                distribution_obj.first_rebate1 = first_rebate1;
                distribution_obj.second_rebate1 = second_rebate1;
                distribution_obj.third_rebate1 = third_rebate1;
                distribution_obj.first_point1 = first_point1;
                distribution_obj.second_point1 = second_point1;
                distribution_obj.third_point1 = third_point1;
                data_val.distribution_val = JSON.stringify(distribution_obj);
            } else if (distribution_rule == '1' && recommend_type && level_rule == 1) {
                var first_rebate = [];
                var second_rebate = [];
                var third_rebate = [];
                var first_point = [];
                var second_point = [];
                var third_point = [];
                var first_rebate1 = [];
                var second_rebate1 = [];
                var third_rebate1 = [];
                var first_point1 = [];
                var second_point1 = [];
                var third_point1 = [];
                var levelids = $("#level_ids").val();
                var arr = levelids.split(',');
                if (recommend_type == 1) {
                    for (var j = 0; j < arr.length; j++) {
                        if ($('#level_first_rebate_' + arr[j]).val() == '') {
                            first_rebate.push('0');
                        } else {
                            first_rebate.push($('#level_first_rebate_' + arr[j]).val());
                        }
                        if ($('#level_second_rebate_' + arr[j]).val() == '') {
                            second_rebate.push('0');
                        } else {
                            second_rebate.push($('#level_second_rebate_' + arr[j]).val());
                        }
                        if ($('#level_third_rebate_' + arr[j]).val() == '') {
                            third_rebate.push('0');
                        } else {
                            third_rebate.push($('#level_third_rebate_' + arr[j]).val());
                        }
                        if ($('#level_first_point_' + arr[j]).val() == '') {
                            first_point.push('0');
                        } else {
                            first_point.push($('#level_first_point_' + arr[j]).val());
                        }
                        if ($('#level_second_point_' + arr[j]).val() == '') {
                            second_point.push('0');
                        } else {
                            second_point.push($('#level_second_point_' + arr[j]).val());
                        }
                        if ($('#level_third_point_' + arr[j]).val() == '') {
                            third_point.push('0');
                        } else {
                            third_point.push($('#level_third_point_' + arr[j]).val());
                        }

                    }
                } else {
                    for (var g = 0; g < arr.length; g++) {
                        if ($('#level_first_rebate1_' + arr[g]).val() == "") {
                            first_rebate1.push('0');
                        } else {
                            first_rebate1.push($('#level_first_rebate1_' + arr[g]).val());
                        }
                        if ($('#level_second_rebate1_' + arr[g]).val() == '') {
                            second_rebate1.push('0');
                        } else {
                            second_rebate1.push($('#level_second_rebate1_' + arr[g]).val());
                        }
                        if ($('#level_third_rebate1_' + arr[g]).val() == '') {
                            third_rebate1.push('0');
                        } else {
                            third_rebate1.push($('#level_third_rebate1_' + arr[g]).val());
                        }
                        if ($('#level_first_point1_' + arr[g]).val() == '') {
                            first_point1.push('0');
                        } else {
                            first_point1.push($('#level_first_point1_' + arr[g]).val());
                        }
                        if ($('#level_second_point1_' + arr[g]).val() == '') {
                            second_point1.push('0');
                        } else {
                            second_point1.push($('#level_second_point1_' + arr[g]).val());
                        }
                        if ($('#level_third_point1_' + arr[g]).val() == '') {
                            third_point1.push('0');
                        } else {
                            third_point1.push($('#level_third_point1_' + arr[g]).val());
                        }







                    }
                }
                distribution_obj.recommend_type = recommend_type;
                distribution_obj.level_rule = level_rule;
                distribution_obj.level_ids = arr;
                distribution_obj.first_rebate = first_rebate;
                distribution_obj.second_rebate = second_rebate;
                distribution_obj.third_rebate = third_rebate;
                distribution_obj.first_point = first_point;
                distribution_obj.second_point = second_point;
                distribution_obj.third_point = third_point;
                distribution_obj.first_rebate1 = first_rebate1;
                distribution_obj.second_rebate1 = second_rebate1;
                distribution_obj.third_rebate1 = third_rebate1;
                distribution_obj.first_point1 = first_point1;
                distribution_obj.second_point1 = second_point1;
                distribution_obj.third_point1 = third_point1;
                data_val.distribution_val = JSON.stringify(distribution_obj);
            } else {
                data_val.distribution_val = '';
            }
            //是否参与分红
            var bonus_obj = {};
            var is_global_bonus = $('input[name=is_bonus_global]:checked').val();
            var is_area_bonus = $('input[name=is_bonus_area]:checked').val();
            var is_team_bonus = $('input[name=is_bonus_team]:checked').val();
            var bonus_rule = $('input[name=bonus_rule]').is(':checked') ? 1 : 2;
            data_val.is_global_bonus = is_global_bonus;
            data_val.is_area_bonus = is_area_bonus;
            data_val.is_team_bonus = is_team_bonus;
            data_val.bonus_rule = bonus_rule;
            if (bonus_rule == '1') {
                //全球分红
                var global_bonus = $('#global_bonus').val();
                //区域分红
                var province_bonus = $('#province_bonus').val();
                var city_bonus = $('#city_bonus').val();
                var district_bonus = $('#district_bonus').val();
                //团队分红
                var team_bonus = $('#team_bonus').val();
                bonus_obj.global_bonus = global_bonus;
                bonus_obj.province_bonus = province_bonus;
                bonus_obj.city_bonus = city_bonus;
                bonus_obj.district_bonus = district_bonus;
                bonus_obj.team_bonus = team_bonus;
                data_val.bonus_val = JSON.stringify(bonus_obj);
            } else {
                data_val.bonus_val = '';
            }
            productViewObj.distribution_bonus = data_val;
            productViewObj.goods_attribute_id = $("#goods_attribute_id").val() ? $("#goods_attribute_id").val() : 0;
            var member_level_id =[];
            var distributor_level_id =[];
            var user_group_level_id =[];
            $('input[name="member_level_id"]:checked').each(function(){
                member_level_id.push(parseInt($(this).val()));
            });
            $('input[name="distributor_level_id"]:checked').each(function(){
                distributor_level_id.push(parseInt($(this).val()));
            });
            $('input[name="user_group_level_id"]:checked').each(function(){
                user_group_level_id.push(parseInt($(this).val()));
            });
            var member_level_id2 =[];
            var distributor_level_id2 =[];
            var user_group_level_id2 =[];
            $('input[name="member_level_id2"]:checked').each(function(){
                member_level_id2.push(parseInt($(this).val()));
            });
            $('input[name="distributor_level_id2"]:checked').each(function(){
                distributor_level_id2.push(parseInt($(this).val()));
            });
            $('input[name="user_group_level_id2"]:checked').each(function(){
                user_group_level_id2.push(parseInt($(this).val()));
            });
            /* 渠道商权限 */
            var channel_level_id =[];
            $('input[name="channel_level_id"]:checked').each(function(){
                channel_level_id.push(parseInt($(this).val()));
            });
            var discount_data_val = {};
            var discount_look_obj = {};
            var discount_buy_obj = {};
            var discount_channel_obj = {};
            discount_look_obj.member_level_id = member_level_id.length > 0 ? member_level_id : [0];
            discount_look_obj.distributor_level_id = distributor_level_id.length >0 ? distributor_level_id: [0];
            discount_look_obj.user_group_level_id = user_group_level_id.length > 0 ? user_group_level_id : [0];
            discount_buy_obj.member_level_id2 = member_level_id2.length > 0 ? member_level_id2 : [0];
            discount_buy_obj.distributor_level_id2 = distributor_level_id2.length > 0 ? distributor_level_id2 : [0];
            discount_buy_obj.user_group_level_id2 = user_group_level_id2.length > 0 ? user_group_level_id2 : [0];
            //渠道商权限
            discount_channel_obj.channel_level_id = channel_level_id.length > 0 ? channel_level_id : [0];
            discount_data_val.discount_look_obj = discount_look_obj;//浏览权限
            discount_data_val.discount_buy_obj = discount_buy_obj;//购买权限
            discount_data_val.discount_channel_obj = discount_channel_obj;//渠道商权限
            var is_member_discount = $('input[name=is_member_discount]').is(':checked') ? 1 : 2; // 会员折扣1开， 2关
            if (is_member_discount == 1) {
                var u_level_ids = [];//等级
                var u_level_vals = [];//等级
                var u_level_names = [];//等级
                var u_discount_choice = 1;//折扣方式 1打折 2固定金额
                var u_is_label = 0;//小数取整 1取
                var d_level_ids = [];//等级
                var d_level_vals = [];//等级
                var d_level_names = [];//等级
                var d_discount_choice = 1;//折扣方式 1打折 2固定金额
                var d_is_label = 0;//小数取整 1取
                var distributor_independent = $('input[name=distributor_independent]').is(':checked') ? 1 : 2; //分销商独立折扣 1开， 2关
                var user_independent = $('input[name=user_independent]').is(':checked') ? 1 : 2; // 会员独立折扣1开， 2关
                var distributor_obj = {};
                var user_obj = {};
                if(user_independent == 1) {//会员
                    u_discount_choice = $('input[name=user_discount_choice]:checked').val();
                    u_is_label = $('input[name=is_label_user]').is(':checked') ? 1 : 0;
                    var u_level_data = {};
                    var flag_2 = true;
                    if (u_discount_choice == 1) {//折扣
                        $('input[name="user_independent_level"]').each(function(){
                            var u_name = $(this).data('uname');
                            var data_uid = $(this).data('uid') ? $(this).data('uid'): 0;
                            var u_data = {};
                            u_data['name'] = u_name;
                            u_data['val'] = $(this).val();
                            u_level_data[data_uid] = u_data;
                        });
                    } else {
                        $('input[name="user_independent_level2"]').each(function(){
                            if ($(this).val() == '' || $(this).val() == 0) {
                                flag_2 = false;
                            }
                            var u_name = $(this).data('uname');
                            var data_uid = $(this).data('uid') ? $(this).data('uid'): 0;
                            var u_data = {};
                            u_data['name'] = u_name;
                            u_data['val'] = $(this).val();
                            u_level_data[data_uid] = u_data;
                        });
                    }
                    user_obj.u_level_data = u_level_data;
                    user_obj.u_discount_choice = parseInt(u_discount_choice);
                    user_obj.u_is_label = u_is_label;
                }
                if (distributor_independent == 1) {//分销商
                    d_discount_choice = $('input[name=distributor_discount_choice]:checked').val();
                    d_is_label = $('input[name=is_label_distributor]').is(':checked') ? 1 : 0;
                    var d_level_data = {};
                    var flag_1 = true;
                    if (d_discount_choice == 1) {//折扣
                        $('input[name="distributor_independent_level"]').each(function(){
                            if ($(this).val() == '' || $(this).val() == 0) {
                                flag_1 = false;
                            }
                            var u_name = $(this).data('uname');
                            var data_uid = $(this).data('uid') ? $(this).data('uid'): 0;
                            var u_data = {};
                            u_data['name'] = u_name;
                            u_data['val'] = $(this).val();
                            d_level_data[data_uid] = u_data;
                        });
                    } else {
                        $('input[name="distributor_independent_level2"]').each(function(){
                            if ($(this).val() == '' || $(this).val() == 0) {
                                flag_1 = false;
                            }
                            var u_name = $(this).data('uname');
                            var data_uid = $(this).data('uid') ? $(this).data('uid'): 0;
                            var u_data = {};
                            u_data['name'] = u_name;
                            u_data['val'] = $(this).val();
                            d_level_data[data_uid] = u_data;
                        });
                    }
                    distributor_obj.d_level_data = d_level_data;
                    distributor_obj.d_discount_choice = parseInt(d_discount_choice);
                    distributor_obj.d_is_label = d_is_label;
                }
            }
            discount_data_val.is_member_discount_open = is_member_discount;//是否开启会员折扣 1开 2关
            discount_data_val.is_user_obj_open = user_independent;//是否开启会员折扣 1开 2关
            discount_data_val.user_obj = user_obj;
            discount_data_val.is_distributor_obj_open = distributor_independent;//是否开启分销商折扣 1开 2关
            discount_data_val.distributor_obj = distributor_obj;
            productViewObj.discount_bonus = discount_data_val;

            // 付费内容
            if(goods_type==4){
                var pcArray = []
                $('input[name="payContentName"]').each(function(i,e){
                    var pcObj={};
                    pcObj.name = $(this).val();
                    pcObj.content = $(this).data('content');
                    pcObj.type = $(this).data('type');
                    pcObj.is_see = $(this).data('is_see');
                    pcArray.push(pcObj);
                });
                productViewObj.payment_content = pcArray;
            }
            //海报元素
            productViewObj.is_goods_poster_open = $('input[name=is_goods_poster_open]:checked').val() == 'on' ? 1 : 0;
            productViewObj.px_type = $('input[name=pxselect]:checked').val();
            productViewObj.poster_data = poster.posterData();
            return productViewObj;
        }
        function setCategory() {
            var url = __URL(PLATFORMMAIN + "/goods/selectcategory");
            util.confirm('选择分类', 'url:' + url, function () {
                var cid1 = this.$content.find('#selected_c1').val();
                var cid2 = this.$content.find('#selected_c2').val();
                var cid3 = this.$content.find('#selected_c3').val();
                var cname = this.$content.find('#selected_cn').val();
                $("#category_id_1").val(cid1);
                $("#category_id_2").val(cid2);
                $("#category_id_3").val(cid3);
                $("#select_cname").val(cname);
                $("#select_name_hidden").val(cname);
                $specObj = new Array();
                $sku_array = new Array();
                $temp_Obj = new Object();
                getBindAttr(cid3 ? cid3 : cid2 ? cid2 : cid1);
                $("#tbcNameCategory").attr("cid", cid3 ? cid3 : cid2 ? cid2 : cid1);
            }, 'large');
        }
        /*
         * 检测是否已经选中了颜色或者图片
         * @param {type} spec
         * @returns {undefined}
         */
        function checkHasChooseShowType(spec){
            if(parseInt(spec.spec_show_type) == '2' && spec.spec_id != selectedColorSpecId && selectedColor == 1){
                return false;
            }
            if(parseInt(spec.spec_show_type) == '3' && spec.spec_id != selectedImageSpecId && selectedImage == 1){
                return false;
            }
            return true;
        }
        //获取应用状态
        function goodsAddons(cb){
            $.ajax({
                url: __URL(PLATFORMMAIN + '/goods/goodsAddons'),
                type: "post",
                success: function (data) {
                	typeof cb == "function" && cb(data);
                }
            });
        }
        //切换商品类型
        $('body').on('click', '.goodType1', function () {
            goods_type = 1;//实物商品
            $('.spec_attribute').removeClass('hovers');
            $('.timing_rule').addClass('hovers');
            $('.wechat_card').addClass('hovers');
            $('.pay_content').addClass('hovers');
            $(this).addClass('active').siblings().removeClass('active');
            $("#verification_num").removeAttr('required');
            $("#effective_day").removeAttr('required');

            $("#wxCard_setTitle").removeAttr('required');
            $("#wxCard_stock").removeAttr('required');
            $("#wxCard_des").removeAttr('required');
            $("#cart_color").removeAttr('required');
            $("#picture-wx_des_pic").removeAttr('required');
            $("#op_tips").removeAttr('required');
            $("#shipping_fee_data").addClass('show').removeClass('hidden');
            $("#shipping_fee_title").html('物流/其他');
            $('.cancel_store').removeClass('hovers');
        });
        $('body').on('click', '.goodType2', function () {
            goods_type = 0;//计时计次商品
            $('.spec_attribute').addClass('hovers');
            $('.timing_rule').removeClass('hovers');
            $('.wechat_card').removeClass('hovers');
            $('.cancel_store').removeClass('hovers');
            $('.pay_content').addClass('hovers');
            $(this).addClass('active').siblings().removeClass('active');
            $("#verification_num").attr('required', 'required');
            $("#effective_day").attr('required', 'required');

            var card_switch = $("input[name='card_switch']").is(':checked') ? 1 : 2;
            if (card_switch == 1) {
                $("#wxCard_setTitle").attr('required', 'required');
                $("#wxCard_stock").attr('required', 'required');
                $("#wxCard_des").attr('required', 'required');
                $("#cart_color").attr('required', 'required');
                $("#picture-wx_des_pic").attr('required', 'required');
                $("#op_tips").attr('required', 'required');
            }
            $("#shipping_fee_data").addClass('hidden').removeClass('show');
            $("#shipping_fee_title").html('其他');
        });
        $('body').on('click', '.goodType3', function () {
            goods_type = 3;//虚拟商品
            $('.spec_attribute').removeClass('hovers');
            $('.timing_rule').addClass('hovers');
            $('.wechat_card').addClass('hovers');
            $('.pay_content').addClass('hovers');
            $('.cancel_store').addClass('hovers');
            $(this).addClass('active').siblings().removeClass('active');
            $("#verification_num").removeAttr('required');
            $("#effective_day").removeAttr('required');

            $("#wxCard_setTitle").removeAttr('required');
            $("#wxCard_stock").removeAttr('required');
            $("#wxCard_des").removeAttr('required');
            $("#cart_color").removeAttr('required');
            $("#picture-wx_des_pic").removeAttr('required');
            $("#op_tips").removeAttr('required');
            $("#shipping_fee_data").addClass('hidden').removeClass('show');
            $("#shipping_fee_title").html('其他');
        });
        $('body').on('click', '.goodType4', function () {
            goods_type = 4;//知识付费
            $(this).addClass('active').siblings().removeClass('active');
            $('.pay_content').removeClass('hovers');
            $('.spec_attribute').addClass('hovers');
            $('.cancel_store').addClass('hovers');
            $("#shipping_fee_data").addClass('hidden').removeClass('show');
        });
        // 云盘选择dialog
        $('.cloudDisk').on('click',function(){
            var html = '';
            html+='<form class="form-horizontal padding-15">';
            html+='<div class="form-group"><label class="col-md-3 control-label">内容类型</label>';
            html+='<div class="col-md-8"><label class="radio-inline"><input type="radio" name="content_type" value="1" checked> 视频</label><label class="radio-inline"><input type="radio" name="content_type" id="" value="2"> 音频</label><label class="radio-inline"><input type="radio" name="content_type" id="" value="3">图片</label>';
            html+='<div class="help-block mb-0">请如实选择类型，否则可能会导致内容无法浏览，类型选中后无法编辑</div></div>';
            html+='</div>';
            html+='<div class="form-group"><label class="col-md-3 control-label">内容地址</label><div class="col-md-8"><input type="text" class="form-control" id="contentText"><div class="help-block mb-0">提取地址必须是提前上传至云空间的，例上传至阿里云、腾讯云、七牛云等</div></div></div>';

            html+='</form>';
            util.confirm('云盘选择',html,function(){
                var viewType = this.$content.find('input[name="content_type"]:checked').val();
                var contentText = this.$content.find('#contentText').val();
                var viewTypeText;
                var is_see = '-1';
                if(contentText.trim() == ''){
                    util.message('请填写内容地址');
                    return false;
                }
                if(viewType==1){
                    viewTypeText = '视频';
                }else if(viewType==2){
                    viewTypeText = '音频';
                }else{
                    viewTypeText = '图片';
                }
                var html1 ='<div class="divTr">'+
                                '<div class="divTd divTd1"><input type="text" class="form-control" name="payContentName" data-content="'+contentText+'" data-type="'+viewType+'" data-is_see="'+is_see+'"></div>'+
                                '<div class="divTd">'+contentText+'</div>'+
                                '<div class="divTd">'+viewTypeText+'</div>'+
                                '<div class="divTd divTd4">不支持</div>'+
                                '<div class="divTd divTd5 operationLeft fs-0">';
                    if(viewType != 3){
                        html1 +='<a href="javascript:void(0);" class="btn-operation tryView" data-type="0">试学</a>';
                    }else{
                        html1 +='<a href="javascript:void(0);" class="btn-operation tryView" data-type="0" data-pic="pic">试学</a>';
                    }              
                    html1 +='<a href="javascript:void(0);" class="viewDel btn-operation text-red1">删除</a>'+
                            '<a href="javascript:void(0);" class="btn-operation moveItem ui-sortable-handle">拖动排序</a>'+
                            '</div>'+
                            '</div>';
                 $('#curr-list').append(html1);
            },'large')
        });
        // 知识付费试学dialog
        $('#curr-list').on('click', '.tryView', function () {
            var that = $(this);
            var tvType = that.data('type');
            var tvPic = that.data('pic');
            if(tvType == 0){
                if( tvPic && tvPic=='pic'){
                    var html = '';
                    html+='<form class="form-horizontal padding-15">';
                    html+='<div class="form-group"><label class="col-md-3 control-label">试学类型</label>';
                    html+='<div class="col-md-8"><label class="radio-inline"><input type="radio" name="tryview_type" value="0" checked> 完全试学</label>';
                    html+='</div>';
                    html+='</div>';
                    html+='<div class="form-group hide" id="viewTime"><label class="col-md-3 control-label">试学时间</label><div class="col-md-8"><div class="input-group w-200"><input type="Number" class="form-control" id="tryMin" min="0"><span class="input-group-addon">分钟</span></div><div class="help-block mb-0">前端会员仅能查看设置时间片段</div></div></div>';
                }else{
                    var html = '';
                    html+='<form class="form-horizontal padding-15">';
                    html+='<div class="form-group"><label class="col-md-3 control-label">试学类型</label>';
                    html+='<div class="col-md-8"><label class="radio-inline"><input type="radio" name="tryview_type" value="0" checked> 完全试学</label><label class="radio-inline"><input type="radio" name="tryview_type" value="1"> 部分试学</label>';
                    html+='</div>';
                    html+='</div>';
                    html+='<div class="form-group hide" id="viewTime"><label class="col-md-3 control-label">试学时间</label><div class="col-md-8"><div class="input-group w-200"><input type="Number" class="form-control" id="tryMin" min="0"><span class="input-group-addon">分钟</span></div><div class="help-block mb-0">前端会员仅能查看设置时间片段</div></div></div>';
                }
            


                html+='</form>';
                util.confirm('试学',html,function(){
                    var tryview_type = this.$content.find('input[name="tryview_type"]:checked').val();
                    var tryView_text;
                    if(tryview_type==0){
                        tryView_text ="完全试学";
                        that.html('取消试学');
                        that.data('type','1');
                        that.parents('.divTd5').siblings('.divTd4').html(tryView_text);
                        that.parents('.divTd5').siblings('.divTd1').find('input[name="payContentName"]').data('is_see','0');
                    }else{
                        var min = this.$content.find('#tryMin').val();
                        if(min.trim()=='' || min < 0.1 || min.indexOf('.') >= 0){
                            util.message('请正确输入试学时间')
                            return false;
                        }
                        tryView_text ="试学"+min+"分钟";
                        that.html('取消试学');
                        that.data('type','1');
                        that.parents('.divTd5').siblings('.divTd4').html(tryView_text);
                        that.parents('.divTd5').siblings('.divTd1').find('input[name="payContentName"]').data('is_see',min);
                    }
                })
            }else{
                util.alert('确定取消试学吗？',function(){
                    that.html('试学');
                    that.data('type','0');
                    that.parents('.divTd5').siblings('.divTd1').find('input[name="payContentName"]').data('is_see','-1');
                    that.parents('.divTd5').siblings('.divTd4').html('不支持');
                })
            }
        })
        // 付费内容删除
        $('#curr-list').on('click','.viewDel',function(){
            var that = $(this);
            util.alert('确定要删除该内容吗？',function(){
                that.parents('.divTr').remove();
            })
            
        });
        // 试学类型切换
        $('body').on('change', 'input[name="tryview_type"]', function () {
            var val = $(this).val();
            if(val == 0){
                $("#viewTime").addClass('hide');
            }else{
                $("#viewTime").removeClass('hide');
            }
        });
        // 拖动排序
        $("#curr-list").sortable({
            opacity: 0.8,
            placeholder: "highlight",
            handle:'.moveItem',
            revert: 100,
            start: function (event, ui) {
                var height = ui.item.height();
                $(".highlight").css({"height": height + "px"});
                $(".highlight").html('<div class="text-center" style="line-height:'+height+'px;"><i class="icon icon-add1"></i> 拖动到此处</div>');
            },
            update: function (event, ui) {}
        })
        // 单选图片视频
        $('body').on('click','[data-toggle="singlePicVideo"]',function(){
            var _this = this;
            util.picVideoDialog(_this,false,function(data){
                if(data.type[0] == 1){
                    var viewTypeText ='视频';
                    var viewType ='1';
                }else{
                    var viewTypeText ='图片';
                    var viewType ='3';
                }
                var html1 ='<div class="divTr">'+
                                '<div class="divTd divTd1"><input type="text" class="form-control" name="payContentName" data-content="'+data.path[0]+'" data-type="'+viewType+'" data-is_see="-1"></div>'+
                                '<div class="divTd">'+data.path[0]+'</div>'+
                                '<div class="divTd">'+viewTypeText+'</div>'+
                                '<div class="divTd divTd4">不支持</div>'+
                                '<div class="divTd divTd5 operationLeft fs-0">';
                    if(viewType != 3){
                        html1 +='<a href="javascript:void(0);" class="btn-operation tryView" data-type="0">试学</a>';
                    }else{
                        html1 +='<a href="javascript:void(0);" class="btn-operation tryView" data-type="0" data-pic="pic">试学</a>';
                    }
                               
                        html1 +='<a href="javascript:void(0);" class="viewDel btn-operation text-red1">删除</a>'+
                                '<a href="javascript:void(0);" class="btn-operation moveItem ui-sortable-handle">拖动排序</a>'+
                                '</div>'+
                                '</div>';
                 $('#curr-list').append(html1);
            });
        })
        //同步库存
        $('body').on('blur', '.onblur', function () {
            var obj = $("#stock_table tbody tr");
            //var attr_array = $();
            if (obj.length > 0) {
                var a_price = [];
                var a_costprice = [];
                var a_marketsprice = [];
                $.each(obj.find('input[name="sku_price"]'), function (i, item) {
                    a_price.push($(item).val());
                });
                $.each(obj.find('input[name="cost_price"]'), function (i, item) {
                    a_costprice.push($(item).val());
                });
                $.each(obj.find('input[name="market_price"]'), function (i, item) {
                    a_marketsprice.push($(item).val());
                });
                var min_price = Math.min.apply(null, a_price);
                var min_key = contains(a_price, min_price);
                var min_marketprice = a_marketsprice[min_key];
                var min_costprice = a_costprice[min_key];
                var all_stock_num = 0;
                obj.each(function (v, i) {
                    var stock_num = parseInt($(this).find("input[name='stock_num']").val());
                    if (v == 0) {
                        all_stock_num = stock_num;
                    }
                    if (v > 0) {
                        all_stock_num = stock_num + all_stock_num;
                    }
                });
                $("#txtProductSalePrice").val(min_price);
                $("#txtProductMarketPrice").val(min_marketprice);
                $("#txtProductCostPrice").val(min_costprice);
                $('#txtProductCount').val(all_stock_num);
                $("input[name='card_stock']").val(all_stock_num);
            }
        });
        $("input[name='is_distribution']").on("change", function () {
            var val = $(this).val();
            
            if (val == 1) {
            	goodsAddons(function (data) {
            		var distributionStatus = data.distributionStatus;
            		if(distributionStatus==1){
                        $(".distribution_rules").removeClass("hide");
                        $("#distribution_rule").removeAttr("checked");
                        $('#buyagain_rules').removeClass('hide');
            		}else{
            			$("input[name='is_distribution'][value='2']").click();
                        util.alert2('分销应用未开启，请先前往开启后再设置', function () {
                        	window.open(__URL(ADDONSMAIN+'distributionSetting'));
                            util.alert('是否已设置完成', function () {
                            })
                        })
            		}
            	});
            } else {
                
                $('#buyagain_rules').addClass('hide');

                $(".distribution_rules").addClass("hide");
                $("#level_rules").addClass("hide");
                $("#recommend_type").addClass("hide");
                $("#distribution_input").addClass("hide");
                $("#distribution_input2").addClass("hide");
                $("#distribution_input3").addClass("hide");

            }
        });
        $("input[name='recommend_type']").click(function () {
            if ($(this).is(':checked') && $("input[name='recommend_type']:checked").val() == 1) {
                var level_rule = $("input[name='level_rule']").is(':checked') ? 1 : 2;
                if (level_rule == 1) {
                    $('#distribution_input2').removeClass('hide');
                    $('#distribution_input3').addClass('hide');
                } else {
                    $('#distribution_input').removeClass('hide');
                    $('#distribution_input1').addClass('hide');
                }
            } else if ($(this).is(':checked') && $("input[name='recommend_type']:checked").val() == 2) {
                var level_rule = $("input[name='level_rule']").is(':checked') ? 1 : 2;
                if (level_rule == 1) {
                    $('#distribution_input3').removeClass('hide');
                    $('#distribution_input2').addClass('hide');
                } else {
                    $('#distribution_input1').removeClass('hide');
                    $('#distribution_input').addClass('hide');
                }
            }
        });
        $('input[name=level_rule]').change(function () {
            var level_rule = $(this).is(':checked') ? 1 : 2;
            if (level_rule == 1) {
                $('#distribution_input').addClass('hide');
                $('#distribution_input1').addClass('hide');
                $('.recommend_type').removeAttr('checked');
                $("input[name=recommend_type]:eq(0)").attr("checked", 'checked');
                $('#distribution_input2').removeClass('hide');
                $('#distribution_input input').attr('required', false);
                $('#distribution_input1 input').attr('required', false);
            } else {
                $('#distribution_input2').addClass('hide');
                $('#distribution_input3').addClass('hide');
                $('.recommend_type').removeAttr('checked');
                $('#distribution_input2 input').attr('required', false);
                $('#distribution_input3 input').attr('required', false);
                $("input[name=recommend_type]:eq(0)").attr("checked", 'checked');
                $('#distribution_input').removeClass('hide');
            }
        });
        $('input[name=distribution_rule]').change(function () {
            var distribution_rule = $(this).is(':checked') ? 1 : 2;
            if (distribution_rule == 1) {
                $('#recommend_type').show();
                
                $('#level_rules').removeClass('hide');
                $('#recommend_type').find('input').attr('required', true);
                $('#recommend_type').removeClass('hide');
                $("input[name=recommend_type]:eq(0)").attr("checked", 'checked');
                $('#distribution_input').removeClass('hide');
            } else {
                $('#recommend_type').addClass('hide');
                
                $('#level_rules').addClass('hide');
                $('#level_rule').removeAttr('checked');
                $('#distribution_input').addClass('hide');
                $('#distribution_input1').addClass('hide');
                $('#distribution_input2').addClass('hide');
                $('#distribution_input3').addClass('hide');
                $('.recommend_type').removeAttr('checked');
                $('#recommend_type').find('input').attr('required', false);
                $('#distribution_input input').attr('required', false);
                $('#distribution_input1 input').attr('required', false);
                $('#distribution_input2 input').attr('required', false);
                $('#distribution_input3 input').attr('required', false);
            }
        });
        $('input[name=bonus_rule]').change(function () {
            var bonus_rule = $(this).is(':checked') ? 1 : 2;
            if (bonus_rule == 1) {
                $('#bonus_input').removeClass('hide');
                if ($('input[name=is_bonus_global]:checked').val() == 1) {
                    $('.global_bonus').attr('required', true);
                }
                if ($('input[name=is_bonus_area]:checked').val() == 1) {
                    $('.area_bonus').attr('required', true);
                }
                if ($('input[name=is_bonus_team]:checked').val() == 1) {
                    $('.team_bonus').attr('required', true);
                }
            } else {
                $('#bonus_input').addClass('hide');
                $('#bonus_input input').attr('required', false);
            }
        });
        //复购设置
        $("#buyagain-switch").change(function () {
            var buyagain = $("input[name='buyagain']:checked").val();
			if(buyagain == 1){
                $('.buyagain_commission').show();
                $('.buyagain_commission').removeClass('hide');
                //默认开启
                $('#buyagain_distribution_input').removeClass('hide');
			}else{
                $('.buyagain_commission').hide();
                $('.buyagain_commission').addClass('hide');
			}
        })
        $("input[name='buyagain_recommend_type']").click(function () {
            if ($(this).is(':checked') && $("input[name='buyagain_recommend_type']:checked").val() == 1) {
                var buyagain_level_rule = $("input[name='buyagain_level_rule']").is(':checked') ? 1 : 2;
                if (buyagain_level_rule == 1) {
                    $('#buyagain_distribution_input2').removeClass('hide');
                    $('#buyagain_distribution_input3').addClass('hide');
                } else {
                    $('#buyagain_distribution_input').removeClass('hide');
                    $('#buyagain_distribution_input1').addClass('hide');
                }
            } else if ($(this).is(':checked') && $("input[name='buyagain_recommend_type']:checked").val() == 2) {
                var buyagain_level_rule = $("input[name='buyagain_level_rule']").is(':checked') ? 1 : 2;
                if (buyagain_level_rule == 1) {
                    $('#buyagain_distribution_input3').removeClass('hide');
                    $('#buyagain_distribution_input2').addClass('hide');
                } else {
                    $('#buyagain_distribution_input1').removeClass('hide');
                    $('#buyagain_distribution_input').addClass('hide');
                }
            }
        });
        //等级复购
        $('input[name=buyagain_level_rule]').change(function () {
            var buyagain_level_rule = $(this).is(':checked') ? 1 : 2;
            if (buyagain_level_rule == 1) {
                $('#buyagain_distribution_input').addClass('hide');
                $('#buyagain_distribution_input1').addClass('hide');
                $('.buyagain_recommend_type').removeAttr('checked');
                $("input[name=buyagain_recommend_type]:eq(0)").attr("checked", 'checked');
                $('#buyagain_distribution_input2').removeClass('hide');
                $('#buyagain_distribution_input input').attr('required', false);
                $('#buyagain_distribution_input1 input').attr('required', false);
            } else {
                $('#buyagain_distribution_input2').addClass('hide');
                $('#buyagain_distribution_input3').addClass('hide');
                $('.buyagain_recommend_type').removeAttr('checked');
                $('#buyagain_distribution_input2 input').attr('required', false);
                $('#buyagain_distribution_input3 input').attr('required', false);
                $("input[name=buyagain_recommend_type]:eq(0)").attr("checked", 'checked');
                $('#buyagain_distribution_input').removeClass('hide');
            }
        });
        //切换商品类型
        $('[data-goods_type]').on('click', function () {
            goods_type = $(this).data('goods_type');
            $(this).addClass('active').siblings().removeClass('active');
            if (goods_type == 1 || goods_type == 3) {
                $('.isgoods_type_1').addClass('show');
                $('.isgoods_type_1').removeClass('hidden');
                $('.isgoods_type_0').addClass('hidden');
                $('.isgoods_type_0').removeClass('show');
                $('.list-group-item[data-id=t6]').toggleClass('show').removeClass('hidden');
                if (goods_type == 3) {
                    $('#shipping_fee_data').addClass('hidden');
                    $('#shipping_fee_data').removeClass('show');
                } else {
                    $('#shipping_fee_data').addClass('show');
                    $('#shipping_fee_data').removeClass('hidden');
                }
            } else {
                $('.isgoods_type_1').addClass('hidden');
                $('.isgoods_type_1').removeClass('show');
                $('.isgoods_type_0').addClass('show');
                $('.isgoods_type_0').removeClass('hidden');
                $('.list-group-item[data-id=t6]').addClass('hidden').removeClass('show');
            }
        });
        //显示隐藏更多设置
        $('.fold-btn').click(function(){
            var  status=$(this).attr('data-status');
            if(status==1){
                $('.fold-field').addClass('hide');
                $(".icon-drop-down").addClass('rotate');
                $(".set-more").text("更多设置");
                $(this).attr('data-status','0');
            }else{
                $('.fold-field').removeClass('hide');
                $(".icon-drop-down").removeClass('rotate');
                $(".set-more").text("收起更多设置");
                $(this).attr('data-status','1');
            }
        });
        $('#select_cname').on('keyup', function () {
            $(this).val($('#select_name_hidden').val());
        });
        //选择分类
        $('body').on('click', '.J-selectCategory', function () {
            if ($('#category_id_1').val() > 0) {
                util.alert('切换分类后会重置规格与属性，是否继续操作？', function () {
                    setCategory()
                });
            } else {
                setCategory();
            }

        });
        // 其他分类
        var promotion_type = $("#promotion_type").val() ? $("#promotion_type").val() : 0;
        $('.other-category').on('click',function(){
            if (promotion_type !=0 && promotion_type != '' && promotion_type != undefined) {
                return;
            }
            var arr = [];
            var _this = $(this);
            $('.J-extend-category').each(function(){
                arr.push($(this).data('cid'));
            });
            var url = __URL(PLATFORMMAIN + "/goods/selectcategory");
            util.confirm('选择分类', 'url:' + url, function () {
                var cid1 = this.$content.find('#selected_c1').val();
                var cid2 = this.$content.find('#selected_c2').val();
                var cid3 = this.$content.find('#selected_c3').val();
                var cname = this.$content.find('#selected_cn').val();
                var id=cid3 ? cid3 : cid2 ? cid2 : cid1;
                for(var i=0;i<arr.length;i++){
                    if(arr[i]==id){
                         util.message('该分类已被选择','danger');
                         return false;
                    }
                }

                var html = '<div class="field-item field-item-remove J-extend-category" data-cid="'+id+'">'+cname+'<span><i class="fa fa-remove icon-danger icon"></i></span></div>';
                _this.append(html);
            }, 'large'); 
        })
        $('.other-category').on('click','.field-item-remove span',function(e){
            if (promotion_type !=0 && promotion_type != '' && promotion_type != undefined) {
                return;
            }
            e.stopPropagation();
            $(this).parents('.field-item-remove').remove();

        })
        //增加留言字段
        $('#addMessage').on('click', function () {
            var html = '<input type="text" class="form-control mt-15" name="message" placeholder="留言字段名" >';
            $('.messageBox').append(html);
        });
        //刷新运费模板
        $('body').on('click', '.J-refresh', function () {
            var shipping_id = $(this).data('shipping');
            $.ajax({
                url: __URL(PLATFORMMAIN + '/goods/getShippingFeeList'),
                type: "post",
                success: function (data) {
                    if (data.length > 0) {
                        var html = '<option value="0">默认模板</option>';
                        data.forEach(function (v, k) {
                            var select = '';
                            if (v['shipping_fee_id'] == shipping_id) {
                                select = 'selected';
                            }
                            html += '<option value="' + v['shipping_fee_id'] + '" ' + select + '>' + v['shipping_fee_name'] + '</option>';
                        })
                        $('#shipping_fee_id').html(html);
                    }

                }
            });
        });
        //运费设置
        $('body').on("change", "input[name='shipping_fee_type']", function () {
            var val = $(this).val();
            if (val == 1) {
                $('input[name="shipping_fee"]').removeAttr('disabled');
                $('#shipping_fee').prop("required", true);
            } else {
                $('input[name="shipping_fee"]').attr('disabled', true);
                $('#shipping_fee').prop("required", false);
                $("#shipping_fee").parents(".w15").removeClass("has-error");
            }
            if (val == 2) {
                $('select[name="shipping_fee_id"]').removeAttr('disabled')
            } else {
                $('select[name="shipping_fee_id"]').attr('disabled', true);
                $('select[name="shipping_fee_id"]').val('0');
                $('.is_shipping_fee_id').addClass('hidden');
            }
            checkShipping();
        });
        //选择运费模板
        $('body').on("change", "select[name='shipping_fee_id']", function () {
            var val = $(this).val();
            $('select[name="shipping_fee_id"]').find('option[value=' + val + ']').attr("selected", true);
            var type = $("#shipping_fee_id").find("option:selected").attr("type");
            if (type == 1) {
                $('.is_shipping_fee_id').removeClass('hidden');
                $('.is_shipping_fee_id_volume').addClass('hidden');
                $('.is_shipping_fee_id_num').addClass('hidden');
            } else if (type == 2) {
                $('.is_shipping_fee_id').addClass('hidden');
                $('.is_shipping_fee_id_volume').addClass('hidden');
                $('.is_shipping_fee_id_num').removeClass('hidden');
            } else if (type == 3) {
                $('.is_shipping_fee_id').addClass('hidden');
                $('.is_shipping_fee_id_volume').removeClass('hidden');
                $('.is_shipping_fee_id_num').addClass('hidden');
            }
        });
        //有图片则开启验证
        $('#J-goodspic').bind('DOMNodeInserted', function (e) {
            var lengths = $(this).find("input[name='upload_img_id']").length;
            if (lengths > 0) {
                $('#visibility1').removeAttr('required');
            }
            if (lengths > 4) {
                $(this).find(".plus-box").fadeOut();
            }
        });
        $('#J-goodspic').bind('DOMNodeRemoved', function (e) {
            var lengths = $(this).find("input[name='upload_img_id']").length;
            if (lengths < 1) {
                $('#visibility1').attr('required', 'required');
            }
            if (lengths < 5) {
                $(this).find(".plus-box").show();
            }
        });
        if ($('#J-goodspic').find("input[name='upload_img_id']").length > 4) {
            $('#J-goodspic').find(".plus-box").hide();
        }
        //门店选择
        var storeSelect=[];  //已选门店的集合
        var storeUnselect=[];  //未选门店的集合
        function getStoreList(){
            var excepted_store_id = [];
            $("#store_select input[name='select_store_id[]']").each(function () {
            	excepted_store_id.push($(this).val());
            });
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: storeListUrl,
                data: {
                	'status': 1,
                    'excepted_store_id': excepted_store_id,
                    'page_size': 0
                },
                success: function (data) {
                    $("#store_unselect").empty();
                    if (data.total_count > 0 && data.addon_status.is_store==1) {
                   		var store_data = [];
                   		
                   		if (typeof (obj) == "object" && typeof (obj.store_list) == "object" && obj.store_list) {
       	                    $.each(data.data, function (k1, v1) {
       	                    	$.each(obj.store_list, function (k2, v2) {
        	                    	if(v1.store_id==v2){
        	                    		store_data.push(v1.store_id);
        			                	addStore(v1.store_id, 'select_store_id[]', true, '#store_select', v1.store_name);
        			                    var obj ={};
        			                    obj.value = v1.store_id;
        			                    obj.name = v1.store_name;
        			                    storeSelect.push(obj);
        	                    	}
       	                    	})
       	                    })
                   		}
                       $.each(data.data, function (k, v) {
	                       if(store_data.indexOf(v.store_id)==-1){
		                       addStore(v.store_id, 'unselect_store_id[]', false, '#store_unselect', v.store_name);
		                       var obj ={};
		                       obj.value = v.store_id;
		                       obj.name = v.store_name;
		                       storeUnselect.push(obj);
	                       }
                       })
                    }
                	if(data.code==-1005){
                		$("#store_unselect").html('<div style="margin-top:100px;text-align: center;"><label class="prompt">无法添加，店铺应用未授权</label></div>');
                	}else if((data.total_count == 0 || storeUnselect =='') && data.addon_status.is_store==1){
                		$("#store_unselect").html('<div style="margin-top:100px;text-align: center;"><label class="prompt">没有营业中的门店，前往<a href="'+__URL(ADDONSMAIN+'addStore')+'" class="text-primary addPrompt" data-type="store" target="_blank">创建</a></label></div>');
                	}else if(data.addon_status.is_store==0){
                		$("#store_unselect").html('<div style="margin-top:100px;text-align: center;"><label class="prompt">店铺应用未开启</label></div>');
                	}
               }
            });
        }
        function addStore(value, to_name, checked, selected_id, label) {
            var tmp;
            if (checked) {
                tmp = '<div class="checkbox line-1-ellipsis"><label><input type="checkbox" name="' + to_name + '" value="' + value + '" checked>' + label + '</label></div>'
            } else {
                tmp = '<div class="checkbox line-1-ellipsis"><label><input type="checkbox" name="' + to_name + '" value="' + value + '">' + label + '</label></div>'
            }
            $(selected_id).append(tmp);
        }
        function removeStore(value, from_name) {
            $("input[name='" + from_name + "']input[value='" + value + "']").parent().parent().remove();
        }
        $('body').on('click','.addPrompt',function(){
        	var type = $(this).data('type');
            util.alert('是否已添加完成？', function () {
            	if(type=='store'){
            		getStoreList();
            	}
            })
        })
        $("#store_unselect").on("click", "input[name='unselect_store_id[]']", function () {
        	addStore($(this).val(), 'select_store_id[]', true, '#store_select', $(this).parent().text());
        	removeStore($(this).val(), 'unselect_store_id[]');
            // 已选优惠券数组增加
            var val=$(this).val();
            var obj={};
            obj.value = $(this).val();
            obj.name = $(this).parent().text();
            storeSelect.push(obj);
            //未选优惠券数组减少
            for(var i=0;i<storeUnselect.length;i++){
                if(val==storeUnselect[i].value){
                	storeUnselect.splice(i,1);
                }
            }
			if($("#store_unselect label").length==0){
				$("#store_unselect").html('<div style="margin-top:100px;text-align: center;"><label class="prompt">没有营业中的门店，前往<a href="'+__URL(ADDONSMAIN+'addStore')+'" class="text-primary addPrompt" data-type="store" target="_blank">创建</a></label></div>');
			}
        });

        $("#store_select").on('click', "input[name='select_store_id[]']", function () {
            if ($("#store_unselect label").hasClass("prompt")) {
                $("#store_unselect").empty();
            }
            addStore($(this).val(), 'unselect_store_id[]', false, '#store_unselect', $(this).parent().text());
            removeStore($(this).val(), 'select_store_id[]');
            // 未选优惠券数组增加
            var val=$(this).val();
            var obj={};
            obj.value = $(this).val();
            obj.name = $(this).parent().text();
            storeUnselect.push(obj);
            //已选优惠券数组减少
            for(var i=0;i<storeSelect.length;i++){
                if(val==storeSelect[i].value){
                	storeSelect.splice(i,1);
                }
            }

        });
        // 门店全选
        $('input[name="store_unselect_all"]').on('change',function(){
            if($(this).is(':checked')) {
                 $("input[name='unselect_store_id[]']").each(function(){
                	addStore($(this).val(), 'select_store_id[]', true, '#store_select', $(this).parent().text());
                    removeStore($(this).val(), 'unselect_store_id[]');
                    // 已选门店数组增加
                    var val=$(this).val();
                    var obj={};
                    obj.value = $(this).val();
                    obj.name = $(this).parent().text();
                    storeSelect.push(obj);
                    //未选门店数组减少
                    for(var i=0;i<storeUnselect.length;i++){
                        if(val==storeUnselect[i].value){
                        	storeUnselect.splice(i,1);
                        }
                    }

                 })
                 $("#store_unselect").html('<div style="margin-top:100px;text-align: center;"><label class="prompt">没有营业中的门店，前往<a href="'+__URL(ADDONSMAIN+'addStore')+'" class="text-primary addPrompt" data-type="store" target="_blank">创建</a></label></div>');
            }else{
                if ($("#store_unselect label").hasClass("prompt")) {
                    $("#store_unselect").empty();
                }
                $("input[name='select_store_id[]']").each(function(){
                	addStore($(this).val(), 'unselect_store_id[]', false, '#store_unselect', $(this).parent().text());
                    removeStore($(this).val(), 'select_store_id[]');
                    // 未选门店数组增加
                    var val=$(this).val();
                    var obj={};
                    obj.value = $(this).val();
                    obj.name = $(this).parent().text();
                    storeUnselect.push(obj);
                    //已选门店数组减少
                    for(var i=0;i<storeSelect.length;i++){
                        if(val==storeSelect[i].value){
                        	storeSelect.splice(i,1);
                        }
                    }
                })

            }
        })
        // 未选中门店搜索
        $("#store_unselect_search").on('keyup',function(){
             var val=$(this).val();
             var html='';
             for(var i=0;i<storeUnselect.length;i++){
                 var names = storeUnselect[i].name;
                 if(names.indexOf(val)!=-1){
                      html += '<div class="checkbox line-1-ellipsis"><label><input type="checkbox" name="unselect_store_id[]" value="'+storeUnselect[i].value+'">'+storeUnselect[i].name+'</label></div>';
                 }
             }
             $("#store_unselect").html(html);
             if(storeUnselect.length>0){
                 $("input[name='store_unselect_all']").attr('checked',false);
             }
             else{
                 $("input[name='store_unselect_all']").attr('checked',true);
             }
        })
        // 已选中门店搜索
        $("#store_select_search").on('keyup',function(){
             var val=$(this).val();
             var html='';
             for(var i=0;i<storeSelect.length;i++){
                 var names = storeSelect[i].name;
                 if(names.indexOf(val)!=-1){
                     html+='<div class="checkbox line-1-ellipsis"><label><input type="checkbox" name="select_store_id[]" value="'+storeSelect[i].value+'" checked>'+storeSelect[i].name+'</label></div>';
                 }
             }
             $("#store_select").html(html);
        })
        //规格改版js
        $('body').on('click', '.goods-value-add', function (e) {
            e.stopPropagation();
            $(this).hide();
            var show_type = $(this).attr("data-show-type");//显示方式
            var html = '<div class="inline-block value-add-div"><input type="text" class="goods-value-add-input"><span class="goods-value-sure" data-show-type="' + show_type + '"><i class="icon icon-correct"></i></span></div>';
            $(this).after(html);
        });
        // 点击外部修改框消失
        $('body').on('click', function () {
            $('.value-add-div').siblings('.goods-value-add').show();
            $('.value-add-div').siblings('.goods-sku-items-name').show();
            $('.value-add-div').remove();
        });
        $('body').on('click', '.value-add-div', function (e) {
            e.stopPropagation();
        });
        // 增加规格，规格值，属性，属性值
        $("body").on('click', '.goods-value-sure', function () {
            var _this = $(this);
            var val = _this.siblings('.goods-value-add-input').val();
            var show_type = _this.attr("data-show-type");
            if (val == '') {
                if (show_type < 10){
                    util.message('请填写规格值名称', 'danger');
                }else if(show_type == 10){
                    util.message('请填写规格名称', 'danger');
                }else if(show_type == 11){
                    util.message('请填写属性名称', 'danger');
                }else if(show_type == 12 || show_type == 13){
                    util.message('请填写属性值名称', 'danger');
                }else if(show_type == 'edit'){
                    util.message('名称不能为空', 'danger');
                }
                return false;
            }
            var addDom = _this.parents('.value-add-div').siblings('.goods-value-add');
            if (show_type < 10) {
                var spec_id = addDom.data('spec_id');
                var spec_name = addDom.data('spec_name');
                $.ajax({
                    type: "post",
                    url: __URL(PLATFORMMAIN + '/goods/addgoodsspecvalue'),
                    data: {
                        'spec_id': spec_id,
                        'spec_value_name': val,
                        'is_visible': 1
                    },
                    success: function (data) {
                        if (data['code'] < 0) {
                            util.message(data['message'], 'danger');
                            return false;
                        } else {
                            var html = '';
                            switch (parseInt(show_type)) {
                                case 1:
                                    //文字
                                    html += '<article class="goods-sku-items "><label class="checkbox-inline"><input type="checkbox" name="label_list_' + spec_id + '" class="specItemValue" value="" data-spec_id="' + spec_id + '" data-spec_name="' + spec_name + '" data-spec_value_id="' + data.code + '" data-spec_value_data=""><span class="goods-sku-items-name" data-status="edit_spec_value"  >' + val + '</span></label></article>'
                                    break;
                                case 2:
                                    //颜色
                                    html += '<article class="goods-sku-items"><label class="checkbox-inline"><input type="checkbox" name="label_list_' + spec_id + '" value="" class="specItemValue" data-spec_id="' + spec_id + '" data-spec_name="' + spec_name + '" data-spec_value_id="' + data.code + '" data-spec_value_data="#000000"><span class="goods-sku-items-name" data-status="edit_spec_value">' + val + '</span></label> <span><input type="color"  name="color" class="colorpicker"></span></article>';
                                    break;
                                case 3:
                                    //图片
                                    html += '<article class="goods-sku-items"><label class="checkbox-inline"><input type="checkbox" name="label_list_' + spec_id + '" value="" class="specItemValue J-sku_pic" data-spec_id="' + spec_id + '" data-spec_name="' + spec_name + '" data-spec_value_id="' + data.code + '" data-spec_value_data=""><span class="goods-sku-items-name" data-status="edit_spec_value">' + val + '</span></label> <a href="javascript:void(0);" data-toggle="specPicture" class="spec-img-box"><img src="/public/platform/images/goods_sku_add.png"></a></article>';
                                    break;
                                default:
                                    //文字
                                    html += '<article class="goods-sku-items "><label class="checkbox-inline"><input type="checkbox" name="label_list_' + spec_id + '" class="specItemValue" value="" data-spec_id="' + spec_id + '" data-spec_name="' + spec_name + '" data-spec_value_id="' + data.code + '" data-spec_value_data=""><span class="goods-sku-items-name" data-status="edit_spec_value"  >' + val + '</span></label></article>'
                                    break;
                            }
                            addDom.before(html);
                            addDom.show();
                            _this.parents('.value-add-div').remove();
                            checkColorPicker();
                        }
                    }
                });

            } else if (show_type == 10) {
                var attr_id = $("#goods_attribute_id").val();
                //添加规格
                $.ajax({
                    type: "post",
                    url: __URL(PLATFORMMAIN + '/goods/addgoodsspec'),
                    data: {
                        'spec_name': val,
                        'is_visible': 1,
                        'show_type': 1,
                        "attr_id": attr_id
                    },
                    success: function (data) {
                        if (data["code"] > 0) {
                            var html = '<tr class="spec_list goods-sku-block-'+data["code"]+'"><td><span class="goods-sku-items-name" data-status="edit_spec" data-spec_id="' + data.code + '">' + val + '</span></td><td><a class="text-primary shows_type" data-show_type="1" data-spec_id="' + data.code + '" href="javascript:void(0);">文字<i class="icon icon-drop-down ml-2"></i></a></td><td class="text-left"><a href="javascript:void(0);" class="goods-value-add" data-show-type="1" data-spec_id="' + data.code + '" data-spec_name="' + val + '"><i class="icon icon-add1 mr-04"></i>添加规格值</a></td></tr>';
                            _this.parents('.last-tr').before(html);
                            addDom.show();
                            _this.parents('.value-add-div').remove();
                        }else{
                            util.message(data['message'], 'danger');
                        }
                    }
                });

            } else if (show_type == 11) {
                //添加属性
                var attr_id = $("#goods_attribute_id").val();
                $.ajax({
                    type: "post",
                    url: __URL(PLATFORMMAIN + '/goods/addattributeservicevalue'),
                    data: {
                        'attr_value_name': val,
                        'attr_id': attr_id
                    },
                    success: function (data) {
                        if (data["code"] > 0) {
                            var html = '<tr class="attrbute_list"  type="1" id="' + data['code'] + '"><input type="hidden" name="attr_sort" value="1"><td><span class="goods-sku-items-name J-attr_name" data-status="edit_attr">' + val + '</span></td><td class="text-left"><article class="goods-sku-items"><input type="text" class="form-control w-200 js-attribute-text" name="attr_value" data-attribute-value="' + val + '" data-attribute-value-id="' + data['code'] + '"></article></td></tr>';
                            _this.parents('.last-tr').before(html);
                            addDom.show();
                            _this.parents('.value-add-div').remove();
                        } else {
                            util.message("添加失败", 'danger');
                        }
                    }
                });

            } else if (show_type == 12) {
                //属性值 单选按钮
                var attrbute_id = $(this).parents('tr').attr('id');
                var attrbute_name = $(this).parents('tr').find('.J-attr_name').html();
                $.ajax({
                    type: "post",
                    url: __URL(PLATFORMMAIN + '/goods/addattributevaluename'),
                    data: {
                        'attr_value_name': val,
                        'attr_value_id': attrbute_id
                    },
                    success: function (data) {
                        if (data["code"] > 0) {
                            var html = '<article class="goods-sku-items"><label class="radio-inline"><input type="radio" class="js-attribute-radio" data-attribute-value-id="' + attrbute_id + '" name="attrvalue_' + attrbute_id + '" value="' + val + '" data-attribute-value="' + attrbute_name + '"><span class="goods-sku-items-name" data-status="edit_attr_value">' + val + '</span></label></article>';
                            addDom.before(html);
                            addDom.show();
                            _this.parents('.value-add-div').remove();
                        } else {
                            util.message(data['message'], 'danger');
                        }
                    }
                });

            } else if (show_type == 13) {
                //复选框
                var attrbute_id = $(this).parents('tr').attr('id');
                var attrbute_name = $(this).parents('tr').find('.J-attr_name').html();
                $.ajax({
                    type: "post",
                    url: __URL(PLATFORMMAIN + '/goods/addattributevaluename'),
                    data: {
                        'attr_value_name': val,
                        'attr_value_id': attrbute_id
                    },
                    success: function (data) {
                        if (data["code"] > 0) {
                            var html = '<article class="goods-sku-items"><label class="checkbox-inline"><input type="checkbox" data-attribute-value-id="' + attrbute_id + '" name="attr_value" value="' + val + '" class="js-attribute-checkbox" data-attribute-value="' + attrbute_name + '"><span class="goods-sku-items-name" data-status="edit_attr_value">' + val + '</span></label></article>';
                            addDom.before(html);
                            addDom.show();
                            _this.parents('.value-add-div').remove();
                        } else {
                            util.message(data['message'], 'danger');
                        }
                    }
                });
            } else if (show_type == 'edit_spec' || show_type == 'edit_spec_value' || show_type == 'edit_attr' || show_type == 'edit_attr_value') {
                // 修改的规格值
                _this.parents('.value-add-div').siblings('.goods-sku-items-name').text(val).show();
                
                if(show_type == 'edit_spec'){
                    _this.parents('td').siblings('.text-left').find('.specItemValue').attr('data-spec_name', val);
                    var spec = {
                        spec_id: _this.parents('.value-add-div').siblings('.goods-sku-items-name').data('spec_id'),
                        spec_name: val
                    };
                    editSpecName(spec);
                    createTable();
                }else if(show_type == 'edit_spec_value'){
                    
                }else if(show_type == 'edit_attr'){
                    _this.parents('td').siblings('.text-left').find('input').attr('data-attribute-value',val);
                }else if(show_type == 'edit_attr_value'){
                    _this.parents('.value-add-div').siblings('input').val(val);
                }
                _this.parents('.value-add-div').remove();
            } else {

            }

        });
        // 双击规格修改
        $('body').on('dblclick', '.goods-sku-items-name', function (e) {
            var status = $(this).attr('data-status');
            if(status == 'edit_attr_value'){
                return;
            }
            e.stopPropagation();
            $(this).hide();
            var text = $(this).text();
            var html = '<div class="inline-block value-add-div"><input type="text" class="goods-value-add-input" value="' + text + '"><span class="goods-value-sure" data-show-type="' + status + '"><i class="icon icon-correct"></i></span></div>';
            $(this).after(html);
        });
        if($("#goodsId").val() > 0) {
            if (valid_type == 1) {
                $('.effectiveTime').removeClass('hovers');
                $('.expirationTime').addClass('hovers');
                $('#effective_day').attr('required', 'required');
                $('#expiration_time').removeAttr('required');
            } else {
                $('.effectiveTime').addClass('hovers');
                $('.expirationTime').removeClass('hovers');
                $('#expiration_time').attr('required', 'required');
                $('#effective_day').removeAttr('required');
            }
        }
        
        $("input[name='effective_type']").on('change', function () {
            var val = $(this).val();
            if (val == 1) {
                $('.effectiveTime').removeClass('hovers');
                $('.expirationTime').addClass('hovers');
                $('#effective_day').attr('required', 'required');
                $('#expiration_time').removeAttr('required');
            }
            if (val == 2) {
                $('.effectiveTime').addClass('hovers');
                $('.expirationTime').removeClass('hovers');
                $('#expiration_time').attr('required', 'required');
                $('#effective_day').removeAttr('required');
            }
        });
        $("input[name='card_switch']").on('change', function () {
            var val = $(this).is(':checked') ? 1 : 2;
            if (val == 1) {
                $('.wxCard-container').removeClass('hovers');
                $("#wxCard_setTitle").attr('required', 'required');
                $("#wxCard_stock").attr('required', 'required');
                $("#wxCard_des").attr('required', 'required');
                $("#cart_color").attr('required', 'required');
                $("#picture-wx_des_pic").attr('required', 'required');
                $("#op_tips").attr('required', 'required');
            }
            if (val == 2) {
                $('.wxCard-container').addClass('hovers');
                $("#wxCard_setTitle").removeAttr('required');
                $("#wxCard_stock").removeAttr('required');
                $("#wxCard_des").removeAttr('required');
                $("#cart_color").removeAttr('required');
                $("#picture-wx_des_pic").removeAttr('required');
                $("#op_tips").removeAttr('required');
            }
        });
        if($("#goodsId").val() > 0) {
            if (is_wxcard == 1) {
                $('.wxCard-container').removeClass('hovers');
                $("#wxCard_setTitle").attr('required', 'required');
                $("#wxCard_stock").attr('required', 'required');
                $("#wxCard_des").attr('required', 'required');
                $("#op_tips").attr('required', 'required');
                //初始化颜色
                var wx_color = '{$ticket_info.card_color}';
                $('.wxCard-view .view-main').css('background', wx_color);
                $('.wxCard_used .btn').css('background', wx_color);
            } else {
                $('.wxCard-container').addClass('hovers');
                $("#wxCard_setTitle").removeAttr('required');
                $("#wxCard_stock").removeAttr('required');
                $("#wxCard_des").removeAttr('required');
                $("#op_tips").removeAttr('required');
            }
        }
        $("input[name='card_model']").on('change', function () {
            var val = $(this).val();
            if (val == 0) {
                $('.wxCard-container').removeClass('hovers');
                $("#wxCard_setTitle").attr('required', 'required');
                $("#wxCard_stock").attr('required', 'required');
                $("#wxCard_des").attr('required', 'required');
                $("#op_tips").attr('required', 'required');
            }
            if (val == 1) {
                $('.wxCard-container').addClass('hovers');
                $("#wxCard_setTitle").removeAttr('required');
                $("#wxCard_stock").removeAttr('required');
                $("#wxCard_des").removeAttr('required');
                $("#op_tips").removeAttr('required');
            }
        })
        $("input[name='is_bonus_global']").on('change', function () {
            var val = $(this).val();
            if (val == 1) {
            	goodsAddons(function (data) {
            		var globalStatus = data.globalStatus;
            		if(globalStatus==1){
            			$('.bonus-td1').removeClass('hide');
            		}else{
            			$("input[name='is_bonus_global'][value='2']").click();
                        util.alert2('全球分红应用未开启，请先前往开启后再设置', function () {
                        	window.open(__URL(ADDONSMAIN+'globalBonusSetting'));
                            util.alert('是否已设置完成', function () {
                            })
                        })
            		}
            	});
            }
            if (val == 2) {
                // $('.bonus-td1').hide();
                $('.bonus-td1').addClass('hide');
            }

            if ($("#bonus_rule").is(':checked')) {
                if (val == 1) {
                    $('.bonus-td1').find('input').attr('required', true);
                } else {
                    $('.bonus-td1').find('input').attr('required', false);
                }
            }
            
            if ($("input[name='is_bonus_global']:checked").val() == 2 && $("input[name='is_bonus_area']:checked").val() == 2 && $("input[name='is_bonus_team']:checked").val() == 2) {
                $("#bounsRules").addClass("hide");
            } else {
                $("#bounsRules").removeClass("hide");
            }
        });
        $("input[name='is_bonus_area']").on('change', function () {
            var val = $(this).val();
            if (val == 1) {
            	goodsAddons(function (data) {
            		var areaStatus = data.areaStatus;
            		if(areaStatus==1){
            			$('.bonus-td2').removeClass('hide');
            		}else{
            			$("input[name='is_bonus_area'][value='2']").click();
                        util.alert2('区域分红应用未开启，请先前往开启后再设置', function () {
                        	window.open(__URL(ADDONSMAIN+'areaBonusSetting'));
                            util.alert('是否已设置完成', function () {
                            })
                        })
            		}
            	});
            }
            if (val == 2) {
                // $('.bonus-td2').hide();
                $('.bonus-td2').addClass('hide');
            }

            if ($("#bonus_rule").is(':checked')) {
                if (val == 1) {
                    $('.bonus-td2').find('input').attr('required', true);
                } else {
                    $('.bonus-td2').find('input').attr('required', false);
                }
            }

            if ($("input[name='is_bonus_global']:checked").val() == 2 && $("input[name='is_bonus_area']:checked").val() == 2 && $("input[name='is_bonus_team']:checked").val() == 2) {
                $("#bounsRules").addClass("hide");
            } else {
                $("#bounsRules").removeClass("hide");
            }
        });
        $("input[name='is_bonus_team']").on('change', function () {
            var val = $(this).val();
            if (val == 1) {
            	goodsAddons(function (data) {
            		var teamStatus = data.teamStatus;
            		if(teamStatus==1){
            			$('.bonus-td3').removeClass('hide');
            		}else{
            			$("input[name='is_bonus_team'][value='2']").click();
                        util.alert2('团队分红应用未开启，请先前往开启后再设置', function () {
                        	window.open(__URL(ADDONSMAIN+'teamBonusSetting'));
                            util.alert('是否已设置完成', function () {
                            })
                        })
            		}
            	});
            }
            if (val == 2) {
                // $('.bonus-td3').hide();
                $('.bonus-td3').addClass('hide');
            }

            if ($("#bonus_rule").is(':checked')) {
                if (val == 1) {
                    $('.bonus-td3').find('input').attr('required', true);
                } else {
                    $('.bonus-td3').find('input').attr('required', false);
                }
            }

            if ($("input[name='is_bonus_global']:checked").val() == 2 && $("input[name='is_bonus_area']:checked").val() == 2 && $("input[name='is_bonus_team']:checked").val() == 2) {
                $("#bounsRules").addClass("hide");
            } else {
                $("#bounsRules").removeClass("hide");
            }
        });
        //批量设置
        $('.batchSet').on('click', function () {
            var batch_text = $(this).text();
            var batch_type = $(this).data('batch_type');
            var html = '<form class="form-horizontal padding-15">';
            html += '<div class="form-group"><label class="col-md-3 control-label">' + batch_text + '</label><div class="col-md-8">'
            if (batch_type == 'code') {
                html += '<input type="text" name="batch_' + batch_type + '" maxlength="15" class="form-control">'
            } else {
                html += '<input type="number" min="0" oninput="if(value.length>9)value=value.slice(0,9)" name="batch_' + batch_type + '" class="form-control">'
            }
            html += '</div></div></form>'
            util.confirm('批量修改' + batch_text, html, function () {
                var val;
                var maxNum = 9999999.99;
                var currInput = $('#stock_table input[name="' + batch_type + '"]');
                if (batch_type !== 'code') {
                    val = parseFloat(this.$content.find('input[name="batch_' + batch_type + '"]').val());
                } else {
                    val = this.$content.find('input[name="batch_' + batch_type + '"]').val()
                }
                if (!val || val == '') {
                    util.message(batch_text + '不能为空');
                    return false
                } else if (val > maxNum && batch_type !== 'code') {
                    util.message('价格最大为 ' + maxNum);
                    return false
                }

                //同步数据
                if (batch_type == 'sku_price') {
                    $('input[name="txtProductSalePrice"]').val(val);
                    $.each($temp_Obj, function (c, v) {
                        v["sku_price"] = val;
                    });
                }
                if (batch_type == 'market_price') {
                    $('input[name="txtProductMarketPrice"]').val(val);
                    $.each($temp_Obj, function (c, v) {
                        v["market_price"] = val;
                    });
                }
                if (batch_type == 'cost_price') {
                    $('input[name="txtProductCostPrice"]').val(val);
                    $.each($temp_Obj, function (c, v) {
                        v["cost_price"] = val;
                    });
                }
                if (batch_type == 'stock_num') {
                    var length = $('input[name="stock_num"]').length;
                    $('input[name="txtProductCount"]').val(val * length);
                    $.each($temp_Obj, function (c, v) {
                        v["stock_num"] = val;
                    });
                }
                if (batch_type == 'code') {
                    $('input[name="item_no"]').val(val);
                    $.each($temp_Obj, function (c, v) {
                        v["code"] = val;
                    });
                }
                currInput.each(function (i, e) {
                    e.value = val;
                });
            });

        });
        //同步微信卡券库存
        $("#txtProductCount").on('keyup',function(){
            $("input[name='card_stock']").val($(this).val());
        })
        // 双向绑定数据
        $('#wxCard_setTitle').bind('input propertychange change', function () {
            var val = $(this).val();
            $('.wxCard_title').html(val);
        });
        $('#wxCard_des').bind('input propertychange change', function () {
            var val = $(this).val();
            $('.wxCard_describe').html(val);
        });
        // 点击选择卡券颜色使颜色变化
        $('.wx_colorBox').on('click', '.wx_colorBox_item', function () {
            var color = $(this).attr('data-colornmme');
            $('#cart_color').val(color);
            $('#cart_color').attr('style', 'color:' + color);
            $('.cart_color').css('background', color);
            $('.wxCard-view .view-main').css('background', color);
            $('.wxCard_used .btn').css('background', color);
            $('.wx_colorBox').hide();
        });
        $('.cart_color').on('click', function () {
            $('.wx_colorBox').show();
        });
        $(document).click(function (e) {
            var target = $(e.target);
            if (target.closest(".cart_color").length == 0) {
                $('.wx_colorBox').hide();
            }
        });
        //选中规格
        $('#spec_list').on('click', '.specItemValue', function () {
            var $this = $(this);
            var spec_show_type = $this.parents('.spec_list').find('.shows_type').attr('data-show_type');
            var spec_id = $this.data('spec_id');
            var spec_name = $this.data("spec_name");
            var spec_value_id = $this.data('spec_value_id');
            var spec_value_name = $(this).siblings('span').text();
            var spec_value_data = $this.data('spec_value_data');
            var spec_value_data_src = '';
            var spec = {
                spec_id: spec_id,
                spec_show_type: spec_show_type
            };
            //选中规格时，验证是否有同类规格被选中
            if(parseInt(spec_show_type) == '2' || parseInt(spec_show_type) == '3'){
                if(!checkHasChooseShowType(spec)){
                    if(parseInt(spec_show_type) == '3'){
                        util.message('图片类型只能选择一个','danger');
                        return false;
                    }
                    if(parseInt(spec_show_type) == '2'){
                        util.message('颜色类型只能选择一个','danger');
                        return false;
                    }
                }//颜色图片只能各自选择一个
            }
            if(spec_show_type == '3'){
                spec_value_data_src = $this.parents('.goods-sku-items').find('img').attr('src');
            }
            if ($this.is(":checked")) {
                specObj(spec_name, spec_id, spec_value_name, spec_value_id, spec_show_type, spec_value_data, 1, spec_value_data_src);
            } else {
                specObj(spec_name, spec_id, spec_value_name, spec_value_id, spec_show_type, spec_value_data, 0, spec_value_data_src);
            }
            createTable(spec_id);
        });
        //切换规格展示类型
        $('body').on('click', '.shows_type', function () {
            var index = $(this).parents('tr').index();//获取当前行数
            var clicked = $(this);
            var now_shop_type = clicked.attr('data-show_type');
            var html = '<form class="form-horizontal padding-15>';
            html += '<div class="form-group"><label class="col-md-3 control-label">展示类型</label>';
            html += '<div class="col-md-9">';
            html += '<label class="radio-inline"><input type="radio" name="push_type" value="1" '; 
            if(now_shop_type == 1 || !now_shop_type){
                html +='checked=""'; 
            }
            html += 'data-name="文字"> 文字</label>';
            html += '<label class="radio-inline"><input type="radio" name="push_type" value="2" '; 
            if(now_shop_type == 2){
                html +='checked=""'; 
            }
            html += 'data-name="颜色">颜色</label>';
            html += '<label class="radio-inline"><input type="radio" name="push_type" value="3" ';
            if(now_shop_type == 3){
                html +='checked=""'; 
            }
            html += 'data-name="图片">图片</label>';
            html += '<div class="help-block mb-0">当商品选择多个规格时，只能选择一个是图片类型的规格</div>';
            html += '</div></div></form>';
            var typeFlag = true;
            util.confirm('设置展示类型', html, function () {
                if(typeFlag){
                    typeFlag = false;
                    var show_type = this.$content.find('input[name=push_type]:checked').val();
                    var word = this.$content.find('input[name=push_type]:checked').data('name');
                    
                    if (now_shop_type == show_type) {
                        return true;
                    }
                     var spec = {
                        spec_id: clicked.data('spec_id'),
                        spec_show_type: show_type
                    };
                    //切换类型时，当前规格有选中规格值的话就要验证
                    var hasSelected = $('.goods-sku-block-' + clicked.data('spec_id')).find('.specItemValue:checked');
                    if((parseInt(show_type) == '2' || parseInt(show_type) == '3') && hasSelected.length > 0 ){
                        if(!checkHasChooseShowType(spec)){
                            if(parseInt(show_type) == '3'){
                                util.message('图片类型只能选择一个','danger');
                                return false;
                            }
                            if(parseInt(show_type) == '2'){
                                util.message('颜色类型只能选择一个','danger');
                                return false;
                            }
                        }//颜色图片只能各自选择一个
                        if(parseInt(show_type) == '3'){
                            selectedImage = 1;
                            selectedImageSpecId = clicked.data('spec_id');
                        }else if(parseInt(show_type) == '2'){
                            selectedColor = 1;
                            selectedColorSpecId = clicked.data('spec_id');
                        }
                    }
                    clicked.attr('data-show_type', show_type);
                    var goods_spec_items = $('#spec_list tbody').find('tr').eq(index).find('.goods-sku-items');
                    $('#spec_list tbody').find('tr').eq(index).find('.goods-value-add').attr('data-show-type', show_type);//改变添加规格值按钮类型
                    clicked.html(word + '<i class="icon icon-drop-down ml-2"></i>');
                    if (goods_spec_items.length < 1) {
                        return true;
                    }
                    switch (parseInt(show_type)) {//改变规格值展示类型
                        case 1:
                            //文字
                            goods_spec_items.each(function () {
                                $(this).children('.spec-img-box').remove();
                                $(this).children('.colorpicker').remove();
                                $(this).children('.sp-light').remove();
                            });
                            break;
                        case 2:
                            //颜色
                            goods_spec_items.each(function () {
                                var input = $(this).find('.specItemValue');
                                $(this).children('.spec-img-box').remove();
                                input.attr('data-spec_value_data','#000000');
                                $(this).append('<input type="color" class="colorpicker"  name="color" value="#000000">');
                                if($(this).find('.specItemValue').is(':checked')){
                                    var spec = {
                                        flag: input.is(":checked"),
                                        spec_id: input.attr("data-spec_id"),
                                        spec_name: input.attr("data-spec_name"),
                                        spec_value_id: input.attr("data-spec_value_id"),
                                        spec_value_data: '#000000'
                                    };
                                    editSpecValueData(spec);
                                }
                            });
                            break;
                        case 3:
                            //图片
                            goods_spec_items.each(function () {
                                $(this).children('.colorpicker').remove();
                                $(this).children('.sp-light').remove();
                                $(this).find('.specItemValue').addClass('J-sku_pic');
                                $(this).append('<a href="javascript:void(0);" data-toggle="specPicture" class="spec-img-box"><img src="/public/platform/images/goods_sku_add.png"></a>')
                            });
                            break;
                    }
                    if(parseInt(show_type)==2){
                        checkColorPicker();
                    }
                    editSpecShowType(spec);
                }
            });
        });
        // 规格图片
        $('body').on('click', '[data-toggle="specPicture"]', function () {
            var _this = this;
            util.spec_picDialog(_this, function (content) {
                var chooseId = content.id[0];
                var chooseImg = content.path[0];
                var img = '<img src=' + content.path[0] + ' data-id=' + content.id + '>';
                $(_this).html(img);

                var obj = $(_this).parents('.goods-sku-items').find('.J-sku_pic');
                obj.attr("data-spec_value_data", chooseId);
                var spec = {
                    flag: obj.is(":checked"),
                    spec_id: obj.attr("data-spec_id"),
                    spec_name: obj.attr("data-spec_name"),
                    spec_value_id: obj.attr("data-spec_value_id"),
                    spec_value_data: chooseId,
                    spec_value_data_src: chooseImg

                };
                editSpecValueData(spec);
            });
            
        });
        //单个修改sku价格
        $('body').on('keyup', "#stock_table tbody tr td input", function () {
            var outer_key = $(this).parents('tr').attr("skuid");
            var key = $(this).attr("name");
            var value = $(this).val();
            $temp_Obj[outer_key][key] = value;
        });
        $("#singleLimitBuy").on("blur", function(){
            if (parseInt($("#singleLimitBuy").val()) > parseInt($("#txtProductCount").val())) {
                util.message('单次限购不能大于总库存', 'danger');
                $("#singleLimitBuy").val(0);return false;
            }
        })
        $("#maxLimitBuy").on("blur", function(){
            if (parseInt($("#maxLimitBuy").val()) > parseInt($("#txtProductCount").val())) {
                util.message('最大限购不能大于总库存', 'danger');
                $("#maxLimitBuy").val(0);return false;
            }
        })
        // $('input[name=user_independent_level]').on('blur', function(){
        //     if($(this).val() == '') {
        //         util.message('会员等级：'+ $(this).data('uname') + '不能为空', 'danger');
        //         return false;
        //     }
        // });
        // $('input[name=user_independent_level2]').on('blur', function(){
        //     if($(this).val() == '') {
        //         util.message('会员等级：'+ $(this).data('uname') + '不能为空', 'danger');
        //         return false;
        //     }
        // });
        // $('input[name=distributor_independent_level]').on('blur', function(){
        //     if($(this).val() == '') {
        //         util.message('分销商等级：'+ $(this).data('uname') + '不能为空', 'danger');
        //         return false;
        //     }
        // });
        // $('input[name=distributor_independent_level2]').on('blur', function(){
        //     if($(this).val() == '') {
        //         util.message('分销商等级：'+ $(this).data('uname') + '不能为空', 'danger');
        //         return false;
        //     }
        // });

        $('input[name=is_member_discount]').change(function () {
            var user_independent =  $('input[name=user_independent]').is(':checked') ? 1 : 2;
            var distributor_independent =  $('input[name=distributor_independent]').is(':checked') ? 1 : 2;
            var is_member_discount = $(this).is(':checked') ? 1 : 2;
            var user_choice = $("input[name='user_discount_choice']:checked").val();
            var distributor_choice = $('input[name=distributor_discount_choice]:checked').val();
            if (is_member_discount == 1) {//会员折扣
                $('#user_independents').removeClass('hide');// 会员
                $('#distributor_independents').removeClass('hide');//分销商
                if (user_independent == 1) {
                    $('#user_discount_choices').removeClass('hide');
                    $('#user_independent_switchs').removeClass('hide');
                    if (user_choice == 2) {
                        $("#user_independent_levels").addClass('hide');
                        $("#user_independent_levels2").removeClass('hide');
                    } else {
                        $("#user_independent_levels").removeClass('hide');
                        $("#user_independent_levels2").addClass('hide');
                    }
                } else {
                    $('#user_discount_choices').addClass('hide');
                    $('#user_independent_levels').addClass('hide');
                    $('#user_independent_levels2').addClass('hide');
                    $('#user_independent_switchs').addClass('hide');
                }
                if (distributor_independent == 1) {
                    $('#distributor_discount_choices').removeClass('hide');
                    $('#distributor_independent_switchs').removeClass('hide');
                    if (distributor_choice == 2) {
                        $("#distributor_independent_levels").addClass('hide');
                        $("#distributor_independent_levels2").removeClass('hide');
                    } else {
                        $("#distributor_independent_levels").removeClass('hide');
                        $("#distributor_independent_levels2").addClass('hide');
                    }
                } else {
                    $('#distributor_discount_choices').addClass('hide');
                    $('#distributor_independent_levels').addClass('hide');
                    $('#distributor_independent_levels2').addClass('hide');
                    $('#distributor_independent_switchs').addClass('hide');
                }
            } else {
                $('#user_independents').addClass('hide');// 会员
                $('#user_discount_choices').addClass('hide');
                $('#user_independent_levels').addClass('hide');
                $('#user_independent_levels2').addClass('hide');
                $('#user_independent_switchs').addClass('hide');
                $('#distributor_independents').addClass('hide');//分销商
                $('#distributor_discount_choices').addClass('hide');//分销商
                $('#distributor_independent_levels').addClass('hide');
                $('#distributor_independent_levels2').addClass('hide');
                $('#distributor_independent_switchs').addClass('hide');
            }
        });
        $('input[name=user_independent]').change(function () {
            var user_independents = $(this).is(':checked') ? 1 : 2;
            var user_choice = $("input[name='user_discount_choice']:checked").val();
            if (user_independents == 1) {
                $('#user_discount_choices').removeClass('hide');
                $('#user_independent_switchs').removeClass('hide');
                if (user_choice == 1) {//打折
                    $('#user_independent_levels').removeClass('hide');
                    $('#user_independent_levels2').addClass('hide');
                } else {
                    $('#user_independent_levels').addClass('hide');
                    $('#user_independent_levels2').removeClass('hide');
                }
            } else {
                $('#user_discount_choices').addClass('hide');
                $('#user_independent_levels').addClass('hide');
                $('#user_independent_levels2').addClass('hide');
                $('#user_independent_switchs').addClass('hide');
                $('#is_label_user').addClass('hide');
            }
        });
        $('input[name=user_discount_choice]').change(function (){
            var user_choice = $('input[name=user_discount_choice]:checked').val();
            if (user_choice == 2) {
                $("#user_independent_levels").addClass('hide');
                $("#user_independent_levels2").removeClass('hide');
                $('#user_independent_switchs').addClass('hide');
            } else {
                $("#user_independent_levels").removeClass('hide');
                $("#user_independent_levels2").addClass('hide');
                $('#user_independent_switchs').removeClass('hide');
            }
        });
        $('input[name=distributor_independent]').change(function () {
            var distributor_independents = $(this).is(':checked') ? 1 : 2;
            var distributor_choice = $('input[name=distributor_discount_choice]:checked').val();
            if (distributor_independents == 1) {
                $('#distributor_discount_choices').removeClass('hide');
                $('#distributor_independent_levels').removeClass('hide');
                $('#distributor_independent_switchs').removeClass('hide');
                if (distributor_choice == 1) {//打折
                    $('#distributor_independent_levels').removeClass('hide');
                    $('#distributor_independent_levels2').addClass('hide');
                } else {
                    $('#distributor_independent_levels').addClass('hide');
                    $('#distributor_independent_levels2').removeClass('hide');
                }
            } else {
                $('#distributor_discount_choices').addClass('hide');
                $('#distributor_independent_levels').addClass('hide');
                $('#distributor_independent_levels2').addClass('hide');
                $('#distributor_independent_switchs').addClass('hide');
            }
        });
        $('input[name=distributor_discount_choice]').change(function (){
            var distributor_choice = $('input[name=distributor_discount_choice]:checked').val();
            if (distributor_choice == 2) {
                $("#distributor_independent_levels").addClass('hide');
                $("#distributor_independent_levels2").removeClass('hide');
                $('#distributor_independent_switchs').addClass('hide');
            } else {
                $("#distributor_independent_levels").removeClass('hide');
                $("#distributor_independent_levels2").addClass('hide');
                $('#distributor_independent_switchs').removeClass('hide');
            }
        });
        var promotion_type = $("#promotion_type").val() ? $("#promotion_type").val() : 0;
        window.onload = function () {
            if (promotion_type !=0 && promotion_type != '' && promotion_type != undefined){
                $("#goods_attribute").addClass('disable_spec');
                $(".promotion_type").attr("disabled",true);
            }
        }
        $(function () {
            if ($("#member_level_all").is(":checked")) {
                $(".member_level :checkbox").prop("checked", true);//全选
            }
            $("#member_level_all").click(function () {
                if(this.checked){
                    $(".member_level :checkbox").prop("checked", true);//全选
                }else{
                    $(".member_level :checkbox").prop("checked", false);//全部选
                }
            })
            $(".member_level :checkbox").click(function(){
                allchk($(".member_level :checkbox"), $("#member_level_all"));
            });
        });
        $(function () {
            if ($("#distributor_level_all").is(":checked")) {
                $(".distributor_level :checkbox").prop("checked", true);//全选
            }
            $("#distributor_level_all").click(function () {
                if(this.checked){
                    $(".distributor_level :checkbox").prop("checked", true);//全选
                }else{
                    $(".distributor_level :checkbox").prop("checked", false);//全部选
                }
            })
            $(".distributor_level :checkbox").click(function(){
                allchk($(".distributor_level :checkbox"), $("#distributor_level_all"));
            });
        });
        $(function () {
            if ($("#user_group_level_all").is(":checked")) {
                $(".user_group_level :checkbox").prop("checked", true);//全选
            }
            $("#user_group_level_all").click(function () {
                if(this.checked){
                    $(".user_group_level :checkbox").prop("checked", true);//全选
                }else{
                    $(".user_group_level :checkbox").prop("checked", false);//全部选
                }
            })
            $(".user_group_level :checkbox").click(function(){
                allchk($(".user_group_level :checkbox"), $("#user_group_level_all"));
            });
        });
        $(function () {
            if ($("#member_level_all2").is(":checked")) {
                $(".member_level2 :checkbox").prop("checked", true);//全选
            }
            $("#member_level_all2").click(function () {
                if(this.checked){
                    $(".member_level2 :checkbox").prop("checked", true);//全选
                }else{
                    $(".member_level2 :checkbox").prop("checked", false);//全部选
                }
            })
            $(".member_level2 :checkbox").click(function(){
                allchk($(".member_level2 :checkbox"), $("#member_level_all2"));
            });
        });
        $(function () {
            if ($("#distributor_level_all2").is(":checked")) {
                $(".distributor_level2 :checkbox").prop("checked", true);//全选
            }
            $("#distributor_level_all2").click(function () {
                if(this.checked){
                    $(".distributor_level2 :checkbox").prop("checked", true);//全选
                }else{
                    $(".distributor_level2 :checkbox").prop("checked", false);//全部选
                }
            })
            $(".distributor_level2 :checkbox").click(function(){
                allchk($(".distributor_level2 :checkbox"), $("#distributor_level_all2"));
            });
        });
        $(function () {
            if ($("#user_group_level_all2").is(":checked")) {
                $(".user_group_level2 :checkbox").prop("checked", true);//全选
            }
            $("#user_group_level_all2").click(function () {
                if(this.checked){
                    $(".user_group_level2 :checkbox").prop("checked", true);//全选
                }else{
                    $(".user_group_level2 :checkbox").prop("checked", false);//全部选
                }
            })
            $(".user_group_level2 :checkbox").click(function(){
                allchk($(".user_group_level2 :checkbox"), $("#user_group_level_all2"));
            });
        });
        // 渠道商权限- 渠道商采购等级
        $(function () {
            if ($("#channel_level_all").is(":checked")) {
                $(".channel_level :checkbox").prop("checked", true);//全选
            }
            $("#channel_level_all").click(function () {
                if(this.checked){
                    $(".channel_level :checkbox").prop("checked", true);//全选
                }else{
                    $(".channel_level :checkbox").prop("checked", false);//全部选
                }
            })
            //设置全选复选框
            $(".channel_level :checkbox").click(function(){
                allchk($(".channel_level :checkbox"), $("#channel_level_all"));
            });
        });
        function allchk(obj, all){
            var chknum = obj.size();//选项总个数
            var chk = 0;
            obj.each(function () {
                if($(this).prop("checked")==true){
                    chk++;
                }
            });
            if (chknum == chk) {
                all.prop("checked",true);
            }else{
                all.prop("checked",false);
            }
        }
        //提交数据
        $("#submitsData").on('click', function () {
            //  验证规格
            try {
                $('input[name="sku_price"]').each(function () {
                    var val = $(this).val();
                    if (val == '') {
                        throw '1';
                        return false;
                    }
                    if (val < 0.01) {
                        throw '4';
                        return false;
                    }
                });
                $('input[name="market_price"]').each(function () {
                    var val = $(this).val();
                    if (val == '') {
                        throw '2';
                        return false;
                    }
                    if (val < 0.01) {
                        throw '5';
                        return false;
                    }
                });
                $('input[name="stock_num"]').each(function () {
                    var val = $(this).val();
                    if (val == '') {
                        throw '3';
                        return false;
                    }
                    if (val < 0.01) {
                        throw '6';
                        return false;
                    }
                });
            } catch (err) {
                if (err == '1') {
                    util.message('请填写全部销售价', 'danger');
                    $('.add_tab1 li:eq(1) a').tab('show');
                    return false;
                }
                if (err == '2') {
                    util.message('请填写全部市场价', 'danger');
                    $('.add_tab1 li:eq(1) a').tab('show');
                    return false;
                }
                if (err == '3') {
                    util.message('请填写全部库存', 'danger');
                    $('.add_tab1 li:eq(1) a').tab('show');
                    return false;
                }

            }

            // 验证付费内容
            if(goods_type==4){
                var lengths = $('#curr-list').find('.divTr').length;
                if(lengths == 0){
                    util.message('请添加一条付费选择内容');
                    $('.add_tab1 li a.pay_content').tab('show');
                    return false;
                }else{
                    try {
                        $('input[name="payContentName"]').each(function () {
                            var val = $(this).val();
                            if (val == '') {
                                throw '1';
                                return false;
                            }
                        });
                    }catch(err){
                            if (err == '1') {
                                util.message('请填写全部付费内容名称', 'danger');
                                $('.add_tab1 li a.pay_content').tab('show');
                                return false;
                            }
                    }
                }

            }
        });
        var flag = false;
        /*-------------------------提交表单-------------------*/
        util.validate($('.form-validate'), function (form) {
            if ((parseInt($("#maxLimitBuy").val()) != 0) && (parseInt($("#singleLimitBuy").val()) > parseInt($("#maxLimitBuy").val()))) {
                $("#singleLimitBuy").focus();
                util.message('单次限购不能大于最大限购', 'danger');
                return false;
            }
            //分类id
            //checkColorPicker();
            var productViewObj = PackageProductInfo();
            $("#submitsData").attr("disabled", "disabled");
            if(productViewObj.code==-1){
            	$("#submitsData").removeAttr("disabled");
            	util.message(productViewObj.message, 'danger');
            	return false;
            }
            if (flag) {
                return false;
            }
            flag = true;
            // 修改前端数据格式
            if (productViewObj.goods_type == 0 && productViewObj.cardinfo.card_switch == 1) {
                $("#saveTips").show();
            }
            $.ajax({
                type: "post",
                url: __URL(PLATFORMMAIN + '/goods/goodsCreateOrUpdate'),
                data: {"product": JSON.stringify(productViewObj)},
                success: function (data) {
                    $("#saveTips").hide();
                    if (data['code'] > 0) {
                        util.message(data['message'], 'success', __URL(PLATFORMMAIN + '/goods/selfgoodslist'));
                    } else {
                        flag = false;
                        $("#submitsData").removeAttr("disabled");
                        util.message(data['message'], 'danger');
                    }
                }

            })

        });
    };
    return Addgood;
});  