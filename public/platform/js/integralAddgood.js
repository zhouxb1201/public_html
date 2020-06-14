define(["jquery", "util", "colorpicker", "bootstrap", "jconfirm"], function ($, util, colorpicker) {
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
            goods_type = obj.goods_type;
            valid_type = obj.valid_type;
            is_wxcard = obj.is_wxcard;
        }
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
        
        //品类id
        var attr_id = $('#goods_attribute_id').val();
        $("#isgoods_attribute").removeClass("hidden");
        if (parseInt($("#goods_id").val()) > 0) {
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
            $(".store_list").attr('required', 'required');
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
                        break;
                    case 3:
                        //图片
                        text = '图片';
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
                    $temp_Obj[v.attr_value_items]["exchange_point"] = v.exchange_point;
                    $temp_Obj[v.attr_value_items]["cost_price"] = v.cost_price;
                    $temp_Obj[v.attr_value_items]["stock_num"] = v.stock;
                    $temp_Obj[v.attr_value_items]["code"] = v.code;
                });
                // $("#txtProductCount").attr("readonly", "readonly").prop('required', false);
                // $("#txtProductSalePrice").attr("readonly", "readonly").prop('required', false);
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
                                    attribute_list_html += '<a href="javascript:void(0);" class="goods-value-add" data-show-type="12"><i class="icon icon-add1 mr-04"></i>添加属性值</a>';
                                    break;
                                case 3:
                                    //复选框
                                    item.value_items.forEach(function (child, j) {
                                        attribute_list_html += '<article class="goods-sku-items"><label class="checkbox-inline"><input class="js-attribute-checkbox" type="checkbox" name="attr_value" value="' + child + '" data-attribute-value-id="' + item.attr_value_id + '" data-attribute-value="' + item.attr_value_name + '"  name="attr_value" data-attribute-sort="' + item.sort + '"><span class="goods-sku-items-name" data-status="edit_attr_value">' + child + '</span></label></article>';
                                    });
                                    attribute_list_html += '<a href="javascript:void(0);" class="goods-value-add" data-show-type="13"><i class="icon icon-add1 mr-04"></i>添加属性值</a>';
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
                        var select = '';
                        brand_list.forEach(function (v, k) {
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

        //通过分类获取绑定品类
        function getBindAttr(cid) {
            var confirmPinleiByCateUrl = $('#confirmPinleiByCate').val();
            $.ajax({
                type: "post",
                data: {
                    "cate_id": cid,
                },
                url: confirmPinleiByCateUrl,
                success: function (data) {
                    getGoodsSpecListByAttrId(data.attr_id);
                    $("#goods_attribute_id").val(data.attr_id);
                    createTable();
                }
            });
        }
        //构建表格
        function createTable() {
            if($specObj.length == 0){
                $("#stock_table thead").empty();
                $("#stock_table tbody").empty();
                $("#stock_table").hide();
                $('input[name="market_price"]').removeAttr('readonly');
                $('input[name="exchange_point"]').removeAttr('readonly');
                $('input[name="txtProductCount"]').removeAttr('readonly');
                $('input[name="stock"]').removeAttr('readonly');
                $('input[name="item_no"]').removeAttr('readonly');
                $('input[name="conversion_point"]').removeAttr('readonly');
                $('input[name="conversion_price"]').removeAttr('readonly');
            }else{
                $("#stock_table").show();
                if($('input[name="market_price1"]').attr("readonly") != "readonly"){
                    $('input[name="market_price1"]').attr("readonly","readonly");
                    $('input[name="market_price1"]').parents(".form-group").removeClass("has-error");
                }
                if($('input[name="exchange_point"]').attr("readonly") != "readonly"){
                    $('input[name="exchange_point"]').attr("readonly","readonly");
                    $('input[name="exchange_point"]').parents(".form-group").removeClass("has-error");
                }
                if($('input[name="stock"]').attr("readonly") != "readonly"){
                    $('input[name="stock"]').attr("readonly","readonly");
                    $('input[name="stock"]').parents(".form-group").removeClass("has-error");
                }
                if($('input[name="item_no"]').attr("readonly") != "readonly"){
                    $('input[name="item_no"]').attr("readonly","readonly");
                    $('input[name="item_no"]').parents(".form-group").removeClass("has-error");
                }
                if($('input[name="conversion_point"]').attr("readonly") != "readonly"){
                    $('input[name="conversion_point"]').attr("required",false);
                    $('input[name="conversion_point"]').attr("readonly","readonly");
                    $('input[name="conversion_point"]').parents(".form-group").removeClass("has-error");
                }
                if($('input[name="conversion_price"]').attr("readonly") != "readonly"){
                    $('input[name="conversion_price"]').attr("readonly","readonly");
                    $('input[name="conversion_price"]').parents(".form-group").removeClass("has-error");
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
            th_html += '<th class="vertical-middle th-price">兑换价</th>';
            th_html += '<th class="vertical-middle th-price">市场价</th>';
            th_html += '<th class="vertical-middle th-price">兑换积分</th>';
            th_html += '<th class="vertical-middle th-stock">库存</th>';
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
                if($temp_Obj[child_id_string]["sku_price"] && $temp_Obj[child_id_string]["sku_price"] != '0'){
                    html += '<td class="w15"><input type="number" step="0.01" id="sku_price" name="sku_price" class="onblur form-control" value="' + $temp_Obj[child_id_string]["sku_price"]  + '" ></td>';
                }else{
                    html += '<td class="w15"><input type="number" step="0.01" id="sku_price" name="sku_price" class="onblur form-control" value="" ></td>';
                }
                if($temp_Obj[child_id_string]["market_price"] && $temp_Obj[child_id_string]["market_price"] != '0'){
                    html += '<td class="w15"><input type="number" step="0.01" id="market_price" required data-visi-type="prices_1" name="market_price"  class="onblur form-control" value="'+ $temp_Obj[child_id_string]["market_price"] +'"></td>';
                }else{
                    html += '<td class="w15"><input type="number" step="0.01" id="market_price" required data-visi-type="prices_1" name="market_price"  class="onblur form-control" value=""></td>';
                }
                if($temp_Obj[child_id_string]["exchange_point"] && $temp_Obj[child_id_string]["exchange_point"] != '0'){
                    html += '<td><input type="number" min="1" step="0.01" id="exchange_point" name="exchange_point" class="onblur form-control" value="'+ $temp_Obj[child_id_string]["exchange_point"] +'"></td>';
                }else{
                    html += '<td><input type="number" min="1" step="0.01" id="exchange_point" name="exchange_point" class="onblur form-control" value=""></td>';
                }
                if($temp_Obj[child_id_string]["stock_num"] && $temp_Obj[child_id_string]["stock_num"] != '0'){
                    html += '<td class="w15"><input type="number" min="0"  id="stock_num" required data-visi-type="prices_1" name="stock_num" class="onblur form-control" value="'+ $temp_Obj[child_id_string]["stock_num"] +'"/></td>';
                }else{
                    html += '<td class="w15"><input type="number" min="0"  id="stock_num" required data-visi-type="prices_1" name="stock_num" class="onblur form-control" value=""/></td>';
                }
                if($temp_Obj[child_id_string]["code"]){
                    html += '<td><input type="text" name="goods_code"  id="goods_code" class="form-control" maxlength="15" value="'+ $temp_Obj[child_id_string]["code"] +'"/></td>';
                }else{
                    html += '<td><input type="text" name="goods_code"  id="goods_code" class="form-control" maxlength="15" value=""/></td>';
                }
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
                    $old_id = v.id;
                    $old_name = v.name;
                    for ($y = 0; $y < $length_val; $y++) {
                        var $spec_id = $spec_val_obj[$y].spec_id;
                        var $id = $spec_val_obj[$y].spec_value_id;
                        var $name = $spec_val_obj[$y].spec_value_name;
                        $copy_obj = new Object();
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
            for (var i = 0; i < $specObj.length; i++) {
                if ($specObj[i].spec_id == spec_id) {
                    if (is_selected == 1) {
                        $specObj[i]["value"].push({"spec_value_name": spec_value_name, "spec_name": spec_name, "spec_id": spec_id, "spec_value_id": spec_value_id, "spec_value_data": spec_value_data, "spec_value_data_src": spec_value_data_src});
                        is_have = 1;
                    } else {
                        SpliceArrayItem($specObj[i].value, spec_value_id);
                        if ($specObj[i].value.length == 0) {
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
        var less_sku_price = 0;
        var less_market_price = 0;
        var less_exchange_point = 0;
        var all_stock_num = 0;
        $('body').on('change', '.onblur', function () {
            var obj = $("#stock_table tbody tr");
            if(obj.length>0) {
                obj.each(function (v,i) {
                    var sku_price = parseFloat($(this).find("input[name='sku_price']").val());
                    var market_price = parseFloat($(this).find("input[name='market_price']").val());
                    var exchange_point = parseFloat($(this).find("input[name='exchange_point']").val());
                    var stock_num = parseInt($(this).find("input[name='stock_num']").val());
                    //筛选规格最小的值
                    if(v==0){
                        less_sku_price = sku_price;
                        less_market_price = market_price;
                        less_exchange_point = exchange_point;
                        all_stock_num = stock_num;
                    }

                    if(sku_price<less_sku_price && less_sku_price!=0){
                        less_sku_price = sku_price;
                    }
                    if(market_price<less_market_price && less_market_price!=0){
                        less_market_price = market_price;
                    }
                    if(exchange_point<less_exchange_point && less_exchange_point!=0){
                        less_exchange_point = exchange_point;
                    }

                    if(v>0){
                        all_stock_num = stock_num + all_stock_num;
                    }
                });
                if(less_sku_price){
                    $("#price").val(less_sku_price);
                    $("#conversion_price").val(less_sku_price);
                }
                if(less_market_price){
                    $("#market_price1").val(less_market_price);
                }
                if(less_exchange_point){
                    $("#conversion_point").val(less_exchange_point);
                }
                if(all_stock_num){
                    $('input[name="stock"]').val(all_stock_num);
                }
            }
        })

        //选择分类
        $('body').on('click', '#category_id_1', function () {
            var cid = $(this).val();
            $specObj = new Array();
            $sku_array = new Array();
            $temp_Obj = new Object();
            getBindAttr(cid);
        });
        //增加留言字段
        $('#addMessage').on('click', function () {
            var html = '<input type="text" class="form-control mt-15" name="message" placeholder="留言字段名" >';
            $('.messageBox').append(html);
        });

        $('body').on('click', '.refresh', function () {
            var refreshCate = $('#refreshCate').val();
            $.ajax({
                url: refreshCate,
                type: "post",
                success: function (data) {
                    if (data.length > 0) {
                        var html = '<option value="0">请选择</option>';
                        data.forEach(function (v, k) {
                            html += '<option value="' + v['integral_category_id'] + '">' + v['category_name'] + '</option>';
                        })
                        $('#category_id_1').html(html);
                    }

                }
            });
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
                $('.visibility').removeAttr('required');
            }
            if (lengths > 4) {
                $(this).find(".plus-box").fadeOut();
            }
        });
        $('#J-goodspic').bind('DOMNodeRemoved', function (e) {
            var lengths = $(this).find("input[name='upload_img_id']").length;
            if (lengths < 1) {
                $('.visibility').attr('required', 'required');
            }
            if (lengths < 5) {
                $(this).find(".plus-box").show();
            }
        });
        if ($('#J-goodspic').find("input[name='upload_img_id']").length > 4) {
            $('#J-goodspic').find(".plus-box").hide();
        }
        //门店选择
        $("#all_store").on('click', function () {
            $("input[name ='store_list']").prop("checked", this.checked);
        });
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
                    console.log(_this.parents('.value-add-div').siblings('input').val());
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
                    $('#conversion_price').val(val);
                    $.each($temp_Obj, function (c, v) {
                        v["sku_price"] = val;
                    });
                }
                if (batch_type == 'market_price') {
                    $('input[name="market_price1"]').val(val);
                    $('input[name="market_price"]').val(val);
                    $.each($temp_Obj, function (c, v) {
                        v["market_price"] = val;
                    });
                }
                if (batch_type == 'stock_num') {
                    var length = $('input[name="stock_num"]').length;
                    $('input[name="stock"]').val(val * length);
                    $.each($temp_Obj, function (c, v) {
                        v["stock_num"] = val;
                    });
                }
                if(batch_type=='exchange_point'){
                    $('input[name="exchange_point"]').val(val);
                    $('#conversion_point').val(val);
                }
                if (batch_type == 'goods_code') {
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

        //选中规格
        $('#spec_list').on('click', '.specItemValue', function () {
            var $this = $(this);
            var spec_show_type = $this.parents('.spec_list').find('.shows_type').data('show_type');
            var spec_id = $this.data('spec_id');
            var spec_name = $this.data("spec_name");
            var spec_value_id = $this.data('spec_value_id');
            var spec_value_name = $(this).siblings('span').text();
            var spec_value_data = $this.data('spec_value_data');
            var spec_value_data_src = '';
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
            util.confirm('设置展示类型', html, function () {
                var show_type = this.$content.find('input[name=push_type]:checked').val();
                var word = this.$content.find('input[name=push_type]:checked').data('name');
                clicked.attr('data-show_type', show_type);
                if (now_shop_type == show_type) {
                    return true;
                }
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
                            $(this).children('.spec-img-box').remove();
                            $(this).find('.specItemValue').attr('data-spec_value_data','#000000');
                            $(this).append('<input type="color" class="colorpicker"  name="color" value="#000000">')
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
                checkColorPicker();
                var spec = {
                    spec_id: clicked.data('spec_id'),
                    spec_show_type: show_type
                };
                editSpecShowType(spec);
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

        $('body').on('click', '.goodType1', function () {
            $('.goods_spec').removeClass('hovers');
            $(this).addClass('active').siblings().removeClass('active');
        })
        $('body').on('click', '.goodType2', function () {
            $('.goods_spec').addClass('hovers');
            $(this).addClass('active').siblings().removeClass('active');
        })
        //点击对应商品类型显示对应的标签
        $('.type-select-radio li').click(function () {
            var goods_type = $(this).data('goods_type');
            console.log(goods_type);
            var len = $('.goods_type').length;
            for (var i = 0; i < len; i++) {
                var goods_li_type = $('.goods_' + i).data('goods_type');
                if (goods_type == goods_li_type) {
                    $('.goods_' + i).removeClass('hide');
                    $('.goods_' + i).addClass('show');
                    if(goods_li_type == 'balance'){
                        $('#balance_setting').attr('min','0.01');
                        $('input[name=conversion_point]').attr('required', false);
                        $('.balance').css('display','');
                        $('.balance-point').css('display','');
                        $('.no-balance').css('display','none');
                        $('.no-balance-point').css('display','none');
                    }else{
                        $('input[name=conversion_point1]').attr('required', false);
                        $('.no-balance').css('display','');
                        $('.no-balance-point').css('display','');
                        $('.balance').css('display','none');
                        $('.balance-point').css('display','none');
                    }
                    if (goods_li_type == 'goods') {
                        //    $('#conversion_price').attr('disabled',true);
                        $('.goods_type input[type=text]').val('商品名称');
                    }else if(i == 1){
                        $('.goods_type input[type=text]').val('优惠券名称');
                    }else if(i == 2){
                        $('.goods_type input[type=text]').val('礼品券名称');
                    }
                    if(goods_li_type != 'goods'){
                        $('.isgoods_type_1').addClass('hide');
                        $('.isgoods_type_1').removeClass('show');
                    }else{
                        $('.isgoods_type_1').addClass('show');
                        $('.isgoods_type_1').removeClass('hide');
                    }
                } else {
                    $('.goods_' + i).removeClass('show');
                    $('.goods_' + i).addClass('hide');
                }
            }
        });
        //选择商品
        $('#selectGoods').click(function () {
            var modalIntegralGoodsList = $('#modalIntegralGoodsList').val();
            //0为商家店铺商品 1为全平台的商品
            var goods_type = 1;
            var integral_goods_id = $('#integralGoodsId').val() ? $('#integralGoodsId').val() : $('#goods_id').val();
            var url = modalIntegralGoodsList +'&t=' + (new Date()).getTime() + '&goods_type=' + goods_type;
            if(integral_goods_id){
                url += '&integral_goods_id='+integral_goods_id;
            }
            util.activityDialog('url:' + url, function (data) {
            })
        })
        //选择优惠券
        $('#selectCoupon').click(function () {
            var modalIntegralCouponList = $('#modalIntegralCouponList').val();
            //0为商家店铺商品 1为全平台的商品
            var coupon_type = 1;
            var integral_goods_id = $('#integralGoodsId').val() ? $('#integralGoodsId').val() : $('#goods_id').val();
            var url = modalIntegralCouponList + '&t=' + (new Date()).getTime() + '&coupon_type=' + coupon_type;
            if(integral_goods_id){
                url += '&integral_goods_id='+integral_goods_id;
            }
            util.activityDialog('url:' + url, function (data) {
            })
        })
        //选择礼品券
        $('#selectGift').click(function () {
            modalIntegralGiftList = $('#modalIntegralGiftList').val();
            //0为商家店铺商品 1为全平台的商品
            var gift_type = 1;
            var integral_goods_id = $('#integralGoodsId').val() ? $('#integralGoodsId').val() : $('#goods_id').val();
            var url = modalIntegralGiftList + '&t=' + (new Date()).getTime() + '&gift_type=' + gift_type;
            if(integral_goods_id){
                url += '&integral_goods_id='+integral_goods_id;
            }
            util.activityDialog('url:' + url, function (data) {
            })
        })
        //循环选中标签
        for (var i = 0; i < $('.type-select-radio li').length; i++) {
            var goods_type = $('#goods_type').val();
            var idx = 0;
            if (goods_type == 'coupon') {
                idx = 1;
                $('.goods_spec').addClass('hovers');
                $('#balance_setting').attr('required', false);
                $('.isgoods_type_1').addClass('hidden');
                $('.isgoods_type_1').removeClass('show');
                // $('.isgoods_type_0').addClass('show');
                // $('.isgoods_type_0').removeClass('hidden');
                $('.list-group-item[data-id=t6]').addClass('hidden').removeClass('show');
            } else if (goods_type == 'gift') {
                $('.goods_spec').addClass('hovers');
                $('#balance_setting').attr('required', false);
                $('.isgoods_type_1').addClass('hidden');
                $('.isgoods_type_1').removeClass('show');
                // $('.isgoods_type_0').addClass('show');
                // $('.isgoods_type_0').removeClass('hidden');
                $('.list-group-item[data-id=t6]').addClass('hidden').removeClass('show');
                idx = 2;
            } else if (goods_type == 'balance') {
                $('.goods_spec').addClass('hovers');
                $('.isgoods_type_1').addClass('hidden');
                $('.isgoods_type_1').removeClass('show');
                $('.isgoods_type_0').addClass('show');
                $('.isgoods_type_0').removeClass('hidden');
                $('.list-group-item[data-id=t6]').addClass('hidden').removeClass('show');
                idx = 3;
            }
            if (i == idx) {
                $('.type-select-radio li').eq(i).removeClass('hide');
                $('.type-select-radio li').eq(i).addClass('active');
                $('.goods_' + i).removeClass('hide');
                $('.goods_' + i).addClass('show');
                if(i == 3){
                    $('#balance_setting').attr('min','0.01');
                    $('input[name=conversion_point]').attr('required', false);
                    $('.balance').css('display','');
                    $('.balance-point').attr('disabled',false);
                    $('.no-balance').css('display','none');
                    $('.no-balance-point').attr('disabled',true);
                }else{
                    $('input[name=conversion_point1]').attr('required', false);
                    $('.no-balance').css('display','');
                    $('.no-balance-point').attr('disabled',false);
                    $('.balance').css('display','none');
                    // $('.balance-point').attr('disabled',true);
                }
            } else {
                var goods_id = $('#goods_id').val();
                var integral_goods_id = $('#integral_goods_id').val();
                if(integral_goods_id || (goods_id && goods_id != '0')){
                    $('.type-select-radio li').eq(i).addClass('hide');
                }else{
                    $('.type-select-radio li').removeClass('hide');
                    $('.type-select-radio li').addClass('show');
                }
                $('.type-select-radio li').eq(i).removeClass('active');
                $('.goods_' + i).removeClass('show');
                $('.goods_' + i).addClass('hide');
            }
        }
        //判断当前如果是优惠券和礼品券，则看看库存是否满足当前设置
        $('#stock').blur(function(){
            // alert($('.type-select-radio li.active').data('goods_type'));
            var goods_type = $('.type-select-radio li.active').data('goods_type');
            //判断当前是优惠券、礼品券
            var id;
            var isCardStockUrl = $('#isCardStock').val();
            var num = $(this).val();
            if(goods_type == 'coupon'){
                id = $('#coupon_type_id').val();
            }else if(goods_type == 'gift'){
                id = $('#gift_voucher_id').val();
            }else{
                return;
            }
            $.ajax({
                'url': isCardStockUrl,
                'type': 'post',
                'data':{goods_type:goods_type, id : id, num: num},
                'success':function(data){
                    console.log(data);
                    if(data['code'] == -1){
                        util.message(data['message']);
                    }
                }
            })
        })
        //拼接提交的数据
        function packageProductInfo(){
            //商品id
            var goods_id = $('#goods_id').val();
            if(goods_id == 0 && '{$integral_goods_id}'){
                goods_id = '{$integral_goods_id}';
            }
            if ($("#category_id_1").val() > 0) {
                var category_id = $("#category_id_1").val();
            }
            //拼接属性值
            var attr = '';
            var attr_obj = $("#attribute_list tbody tr");
            if (attr_obj.length > 0) {
                attr_obj.each(function (i) {
                    var type = $(this).attr('type');
                    var sort = $(this).find("input[name='attr_sort']").val();
                    var attr_value_id = $(this).attr('id');
                    var attr_name = $(this).find("#attr_name").html();
                    if (type == '1') {
                        var attr_value = $(this).find("input[name='attr_value']").val();
                    } else if (type == '2') {
                        var attr_value = $(this).find("input[type='radio']:checked").val();
                    } else if (type == '3') {
                        var attr_value = '';
                        var check_str = $(this).find("input[type='checkbox']:checked");
                        check_str.each(function (i, v) {
                            attr_value += ":" + $(this).val();
                        })
                    }
                    attr += "§" + sort + "," + attr_value_id + "," + attr_name + "," + attr_value;
                });
            }

            //拼接规格
            var sku_str = '';
            var obj = $("#stock_table tbody tr");
            if (obj.length > 0) {
                obj.each(function (i) {
                    var sku_price = $(this).find("input[name='sku_price']").val();
                    var market_price = $(this).find("input[name='market_price']").val();
                    var exchange_point = $(this).find("input[name='exchange_point']").val();
                    var stock_num = $(this).find("input[name='stock_num']").val();
                    var goods_code = $(this).find("input[name='goods_code']").val();
                    var sku_id = $(this).attr('skuid');
                    sku_str += "§" + sku_id + "¦" + sku_price + "¦" + market_price + "¦" + exchange_point + "¦" + stock_num + "¦" + goods_code;
                });
            }
            //拼接规格 字符串
            var spec_format = '';
            var spec_obj = $("#spec_list tbody tr .selected");
            if (spec_obj.length > 0) {
                spec_obj.each(function (v, i) {
                    //拿到所有选中的规格数据
                    var spec_name = $(this).attr("data-spec_name"); //规格名
                    var spec_id = $(this).attr("data-spec_id"); //规格ID
                    var spec_value_name = $(this).html(); //规格ID
                    var spec_value_id = $(this).attr("data-spec_value_id"); //规格值ID
                    var show_type = $(this).attr("data-show_type"); //规格值ID
                    var spec_value_data = $(this).attr("data-spec_value_data"); //规格值ID
                    spec_format += "§" + spec_name + "¦" + spec_id + "¦" + spec_value_name + "¦" + spec_name + "¦" + spec_id + "¦" + spec_value_id + "¦" + show_type + "¦" + spec_value_data;
                })
            }
            var item_no = $("#item_no").val();
            var goods_attr_id = $("#goods_attribute_id").val() ? $("#goods_attribute_id").val() : 0;
            var formdata = $('.form-validate').serializeArray();
            var img_id = [];
            $("input[name='upload_img_id']").each(function () {
                img_id.push($(this).val());
            })
            //商品类型
            var goods_type = $('.type-select-radio .active').data('goods_type');
            var shipping_fee = $("#shipping_fee").val();
            // 修改前端数据格式
            var productViewObj = new Object();
            productViewObj.goods_id = goods_id ? goods_id : 0;
            productViewObj.goods_type = goods_type;
            productViewObj.imageArray = img_id;
            productViewObj.data = formdata;
            productViewObj.sku_str = sku_str;
            productViewObj.attr = attr;
            productViewObj.spec_format = JSON.stringify($specObj);
            productViewObj.category_id = category_id;
            productViewObj.goods_attr_id = goods_attr_id;
            productViewObj.item_no = item_no;
            productViewObj.less_sku_price = less_sku_price;
            productViewObj.less_market_price = less_market_price;
            productViewObj.less_exchange_point = less_exchange_point;
            productViewObj.all_stock_num = all_stock_num;
            productViewObj.shipping_fee = shipping_fee;
            //物流信息
            productViewObj.goods_weight = $("#goods_weight").val();
            productViewObj.goods_volume = $("#goods_volume").val();
            productViewObj.goods_count = $("#goods_count").val();
            productViewObj.shipping_fee_type = $("input[name='shipping_fee_type']:checked").val();

            productViewObj.goods_spec_format = JSON.stringify($specObj);

            var goods_attribute_arr = new Array();
            $(".js-attribute-text").each(function () {
                var goods_attribute = {
                    attr_value_id: $(this).attr("data-attribute-value-id"),
                    attr_value: $(this).attr("data-attribute-value"),
                    attr_value_name: $(this).val(),
                    sort: $(this).attr("data-attribute-sort")
                };
                goods_attribute_arr.push(goods_attribute);
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
            var video_id = $("input[name='upload_video_id']").val();
            productViewObj.video_id = video_id;
            return productViewObj;
        }
        var flag = false;
        /*-------------------------提交表单-------------------*/
        util.validate($('.form-validate'), function (form) {
            //判断优惠券、礼品券
            var goods_type = $('.type-select-radio .active').data('goods_type');
            var coupon_type_id = $('#coupon_type_id').val();
            var gift_voucher_id = $('#gift_voucher_id').val();
            if (goods_type == 'coupon') {
                if (coupon_type_id == '') {
                    util.message("请选择优惠券");
                    return false;
                }
            } else if (goods_type == 'gift') {
                if (gift_voucher_id == '') {
                    util.message("请选择礼品券");
                    return false;
                }
            }else if(goods_type == 'balance'){
                var balance = $('#balance_setting').val();
                if(balance == '' || balance == 0){
                    util.message("请输入兑换余额");
                    return false;
                }
            }
            //每人限领要小于总库存， 每天提供要小于每人限领
            var limit_num = parseInt($('#limit_num').val());
            var stock = parseInt($('#stock').val());
            if(limit_num > stock){
                util.message("每人限领数量要小于总库存");
                return false;
            }
            //分类id
            var pic_length = $(".picture-list #goods_pic_list")
            var length = pic_length.size();

            if (length > 5) {
                util.message("商品图片不能超过5张");
                return false;
            }
            var obj = $("#stock_table tbody tr");
            if (obj.length <= 0) {
                //积分判断必须大于零
                if (goods_type == 'balance') {
                    var conversion_point = $('input[name=conversion_point1]').val();
                }else{
                    var conversion_point = $('#conversion_point').val();
                }
                if (conversion_point <= 0) {
                    util.message("兑换积分必须大于零");
                    return false;
                }
            }
            //获取提交数据
            var productViewObj = packageProductInfo();
            var integralGoodsCreateOrUpdate = $('#integralGoodsCreateOrUpdate').val();
            if(flag){
                return false;
            }
            flag = true;
            $.ajax({
                type: "post",
                url: integralGoodsCreateOrUpdate,
                data: productViewObj,
                success: function (data) {
                    if (data['code'] > 0) {
                        util.message("添加成功", 'success', __URL("/platform/Menu/addonmenu?addons=integralGoodsList"));
                    } else {
                        util.message("添加失败", 'danger');
                    }
                }
            })
        });
    };
    return Addgood;
});  