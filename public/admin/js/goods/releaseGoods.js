var ueditoroption = {
    'autoClearinitialContent': false,
    'toolbars': [['fullscreen', 'source', 'preview', '|', 'bold', 'italic', 'underline', 'strikethrough', 'forecolor', 'backcolor', '|',
            'justifyleft', 'justifycenter', 'justifyright', '|', 'insertorderedlist', 'insertunorderedlist', 'blockquote', 'emotion', 'insertvideo',
            'link', 'removeformat', '|', 'rowspacingtop', 'rowspacingbottom', 'lineheight', 'indent', 'paragraph', 'fontsize', '|',
            'inserttable', 'deletetable', 'insertparagraphbeforetable', 'insertrow', 'deleterow', 'insertcol', 'deletecol',
            'mergecells', 'mergeright', 'mergedown', 'splittocells', 'splittorows', 'splittocols']],
    'elementPathEnabled': false,
    'focus': false,
    'maximumWords': 9999999999999
};
UE.getEditor("editor", ueditoroption);
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
            var span = $(elm).parents('.goods-sku-item').children('span');
            var spec = {
                flag: span.hasClass("selected"),
                spec_id: span.attr("data-spec-id"),
                spec_name: span.attr("data-spec-name"),
                spec_value_id: span.attr("data-spec-value-id"),
                spec_value_data: $(elm).val()
            };
            editSpecValueData(spec);
        });
    });
}
/**
 * 返回当前添加完成后，生成的规格值HTML代码
 * 2017年6月7日 14:48:27
 */
function getCurrentSpecValueHTML(spec_value) {
    var html = '<article class="goods-sku-item">';
    html += '<span data-spec-name="' + spec_value.spec_name + '"';
    html += 'data-spec-id="' + spec_value.spec_id + '" ';
    if (parseInt(spec_value.show_type) == 2 && spec_value.spec_value_data == "") {
        spec_value.spec_value_data = "#000000";
    }
    html += ' data-spec-value-data="' + spec_value.spec_value_data + '"';
    html += ' data-spec-show-type="' + spec_value.show_type + '"';
    html += 'data-spec-value-id="-1">';
    html += spec_value.spec_value_name + '</span>';
    switch (parseInt(spec_value.show_type)) {
        case 1:
            //文字
            break;
        case 2:
            //颜色
            html += '<i></i>';
            html += '<div class="inputColor">';
            html += '<input class="colorpicker J-' + spec_value.spec_id + '"  value="' + spec_value.spec_value_data + '">';
            html += '</div>';
            break;
        case 3:
            //图片
            var time = spec_value.spec_id + getDate();
            var onclickpic = "showAlbum(this, 1, 'selectpicspec(this)');";
            html += '<i></i>';
            html += '<div class="js-goods-spec-value-img sku-img-check inputImg">';
            html += '<a href="javascript:void(0)" onclick="' + onclickpic + '">';
            html += '<img src="' + spec_value.spec_pic + '" alt="" id="imgspec_value' + time + '_add">';
            html += '<input type="hidden" id="spec_value' + time + '_add" class="J-pic J-sku_img_id" value="">';
            html += '</a>';
            html += '</div>';
            break;
    }
    html += '</article>';
    html += getAddSpecValueHtml(spec_value);

    return html;
}

/**
 * 添加商品规格值
 * @param spec 规格对象
 * @param callBack 回调函数
 */
function addGoodsSpecValue(spec, callBack) {
    $.ajax({
        url: __URL(ADMINMAIN + "/goods/addGoodsSpecValue"),
        type: "post",
        data: {"spec_id": spec.spec_id, "spec_value_name": spec.spec_value_name, "spec_value_data": spec.spec_value_data},
        success: function (res) {
            if (res.code > 0) {
                // layer.msg(res.message, {icon: 1, time: 1000}, function () {
                //     callBack();//执行回调函数
                //     $("span[data-spec-value-id='-1']").attr("data-spec-value-id", res.code);
                // });
                message(res.message,'success',function(){
                    callBack();//执行回调函数
                    $("span[data-spec-value-id='-1']").attr("data-spec-value-id", res.code);
                });
            } else {
                // layer.msg(res.message, {icon: 2, time: 1000});
                message(res.message,'danger');
            }
        }
    });
}
/*
 * 查询运费模板
 */
function checkShipping() {
    if ($('input[name=shipping_fee_type]:checked').val() !== '2') {
        return;
    }
    if ($("#shipping_fee_id option:selected").data('type') === 1) {
        $('.J-weight').show();
    } else if ($("#shipping_fee_id option:selected").data('type') === 3) {
        $('.J-volume').show();
    }
}
$(function () {
    /**
     * 添加规格值：确定操作
     */
    $('.js-goods-spec-block').on("click", ".js-goods-spec-value-add>span:first", function () {
        var curr_obj = $(this).parent();
        var spec_value_name = curr_obj.children("input[type='text']").val();
        if (spec_value_name.length != 0) {

            var show_type = curr_obj.attr("data-show-type");
            var spec_value_data = "";//附加值
            var spec_pic = "";
            switch (parseInt(show_type)) {
                case 1:
                    //文字
                    break;
                case 2:
                    //获取颜色
                    spec_value_data = curr_obj.children(".inputColor").children('input').val();
                    break;
                case 3:
                    //获取图片路径
                    spec_value_data = curr_obj.find(".J-pic").val();
                    spec_pic = curr_obj.find("img").attr('src');
                    break;
            }
            var spec_value = {
                spec_id: curr_obj.attr("data-spec-id"), //规格id
                spec_name: curr_obj.attr("data-spec-name"), //规格名称
                show_type: show_type, //展示方式
                spec_value_name: spec_value_name, //规格值 
                spec_value_data: spec_value_data, //附加值
                spec_pic: spec_pic
            };
            addGoodsSpecValue(spec_value, function () {
                curr_obj.parent().append(getCurrentSpecValueHTML(spec_value));//加载当前添加的规格值、以及最后那个添加按钮
                curr_obj.remove();//删除当前的添加按钮
                checkColorPicker();
            });

        } else {
            // layer.msg("请输入规格值", {
            //     time: 1000
            // });
            message('请输入规格值');
        }
        return false;//防止事件冒泡
    })

    /**
     * 添加规格值：取消操作
     */
    $('.js-goods-spec-block').on("click", ".js-goods-spec-value-add>span:last", function () {
        $(this).parent().removeAttr("data-flag").html("添加规格值");
        return false;//防止事件冒泡
    });

    $('body').on("change", "select[name='shipping_fee_id']", function () {
        if ($(this).val() > 0) {
            $('#shipping_fee_id-error').hide();
            $('#shipping_fee_id').css('border-color', '');
        }
        if ($(this).find('option:selected').data('type') === 1) {
            $('.J-weight').show();
            $('.J-volume').hide();
        } else if ($(this).find('option:selected').data('type') === 3) {
            $('.J-weight').hide();
            $('.J-volume').show();
        }
    });
    $('body').on("change", "input[name='shipping_fee_type']", function () {
        $('.J-shipping_fee').find('.error').hide();
        $('#shipping_fee').css('border-color', '');
        $('#shipping_fee_id').css('border-color', '');
        if ($(this).val() < 2) {
            // $('#shipping_fee_id').hide();
            $('.J-volume').hide();
            $('.J-weight').hide();
        } else {
            // $('#shipping_fee_id').show();
            checkShipping();
        }
        if($(this).val()==1){
            $(this).parent('label').siblings('input').removeAttr("disabled");
        }
        else{
            $("#shipping_fee").attr('disabled',true);
        }
        if($(this).val()==2){
            $(this).parent('label').siblings('.inline-block').children('select').removeAttr("disabled");
        }
        else{
            $("#shipping_fee_id").attr('disabled',true);
            $("#shipping_fee_id").val('0');
        }
    });
    $('body').on("click", ".goods-sku .goods-sku-item span", function () {
        if (timeoutID != null) {
            clearTimeout(timeoutID);
        }
        var $this = $(this);
        timeoutID = setTimeout(function () {
            var spec_id = $this.data("spec-id");
            var spec_value_id = $this.data("spec-value-id");
            var spec_value_name = $this.text();
            var spec_name = $this.data("spec-name");
            var spec_value_data = $this.data("spec-value-data");
            var spec_show_type = $this.data("spec-show-type");
            if ($this.hasClass("selected")) {
                $this.removeClass("selected");
                /**
                 * 取消选中属性值时 删掉数组中的属性信息
                 */
                addOrDeleteSpecObj(spec_name, spec_id, spec_value_name, spec_value_id, spec_show_type, spec_value_data, 0);

            } else {
                $this.addClass("selected");
                /**
                 * 选中属性值时  将属性值 添加到数组中
                 */
                addOrDeleteSpecObj(spec_name, spec_id, spec_value_name, spec_value_id, spec_show_type, spec_value_data, 1);
            }
            /**
             * 根据规格数组拜访数据 创建表格
             */
            createTable();
        }, 200);
    });
    $('body').on("click", ".js-goods-spec-value-add", function () {
        if ($(this).attr("data-flag") == undefined) {
            $(this).html("添加规格值").removeAttr("data-flag");
            var spec_id = $(this).attr("data-spec-id");
            var show_type = $(this).attr("data-show-type");//显示方式
            var html = '<input type="text" placeholder="请输入规格值" style="margin-bottom:0px;">';
            var length = $(this).parent().children("article").length;//当前规格的规格值数量，用于设置图片上传的id，不冲突
            switch (parseInt(show_type)) {
                case 1:
                    //文字
                    break;
                case 2:
                    //颜色
                    html += '<div class="inputColor"><input class="colorpicker" value="#ccc"></div>';
                    break;
                case 3:
                    //图片
                    var time = spec_id + getDate();
                    var onclickpic = "showAlbum(this, 1, 'selectpicspec(this)');";
                    html += '<div class="inputImg" style="margin-left: 4px"><a href="javascript:void(0)" onclick="' + onclickpic + '"><input id="goods_spec_value' + time + '_add" type="hidden" class="J-pic" style="margin-bottom:0px;"><img src="' + ADMINIMG + '/goods_sku_add.png" id="imggoods_spec_value' + time + '_add"></a></div>';
                    break;
            }
            html += '<span class="goods-sku-add" style="margin:0 10px;">确定</span>';
            html += '<span class="goods-sku-cancle">取消</span>';
            $(this).attr("data-flag", 1);
            $(this).html(html);
            $(this).children("input[type='text']").focus();
            checkColorPicker();
        }
    });
    $('body').on("click", ".js-goods-spec-value-add>span:last", function () {
        $(this).parent().removeAttr("data-flag").html("添加规格值");
        return false;
    });
    //删除图片
    $('body').on("click", ".J-deleteImg", function () {
        $(this).parent().remove();

        if ($('.J-imgs').find('.goods_picture').length == '0') {
            $('.J-imgs').html('<div class="goods_picture"><img src="' + ADMINIMG + '/aa.png" alt=""></div>');
        }
        imgReady();
    });
    /**
     * 修改商品规格信息
     * 
     */
    $('body').on("dblclick", ".goods-sku-item span", function () {
        var text = $(this).text();
        if (text != "") {
            $(this).empty();//清空当前规格值的文本内容
            var html = '<input style="color:#000;z-index:99999" type="text" value="' + text + '" data-flag="update_sku_text" data-old-html="' + text + '" />';
            $(html).appendTo($(this));//添加输入框
            $(this).css("padding", "7px 10px");//调整样式
            $(this).children("input[type='text']").focus();
        }

        if (timeoutID != null) {
            clearTimeout(timeoutID);
        }
    });
    /**
     * 更新规格值
     */
    $("input[data-flag='update_sku_text']").on("keyup", function (event) {
        var curr_obj = $(this);
        var spec_value_name = $.trim(curr_obj.val());
        if (event.keyCode == 13) {

            if (spec_value_name.length != 0) {
                var spec_value_id = curr_obj.parent().attr("data-spec-value-id");
                //输入框的内容与之间的规格值不一等，进行修改，否则关闭输入框
                if (spec_value_name != curr_obj.attr("data-old-html")) {

                    var spec = {
                        flag: curr_obj.parent().hasClass("selected"),
                        spec_id: curr_obj.parent().attr("data-spec-id"),
                        spec_name: curr_obj.parent().attr("data-spec-name"),
                        spec_value_id: spec_value_id,
                        spec_value_name: spec_value_name,
                        spec_show_type: curr_obj.parent().attr("data-spec-show-type")
                    };
                    curr_obj.parent().html(spec_value_name).css("padding", "7px 20px");//给规格值文本赋值
                    editSpecValueName(spec);

                } else {
                    curr_obj.parent().html(spec_value_name).css("padding", "7px 20px");//给规格值文本赋值
                }

            } else {
                // layer.msg("请输入规格值");
                message('请输入规格值');
            }
        }
        return false;//防止重复提交
    }).on("click", function () {
        return false;//防止重复提交
    }).on("blur", function () {
        var curr_obj = $(this);
        var spec_value_name = $.trim(curr_obj.val());
        var spec_value_id = curr_obj.parent().attr("data-spec-value-id");
        if (spec_value_name.length == 0) {
            // layer.msg("请输入规格值");
            message('请输入规格值');
            return false;
        }
        if (spec_value_name != curr_obj.attr("data-old-html")) {

            var spec = {
                flag: curr_obj.parent().hasClass("selected"),
                spec_id: curr_obj.parent().attr("data-spec-id"),
                spec_name: curr_obj.parent().attr("data-spec-name"),
                spec_value_id: spec_value_id,
                spec_value_name: spec_value_name,
                spec_show_type: curr_obj.parent().attr("data-spec-show-type")
            };
            curr_obj.parent().html(spec_value_name).css("padding", "7px 20px");//给规格值文本赋值
            editSpecValueName(spec);
        } else {
            curr_obj.parent().html(spec_value_name).css("padding", "7px 20px");//给规格值文本赋值
        }
    });
    $('body').on("keyup", ".js-goods-spec-value-add>input", function (event) {
        var curr_obj = $(this).parent();
        var spec_value_name = curr_obj.children("input[type='text']").val();
        if (event.keyCode == 13) {
            if (spec_value_name.length != 0) {
                var show_type = curr_obj.attr("data-show-type");
                var spec_value_data = "";//附加值
                switch (parseInt(show_type)) {
                    case 1:
                        //文字
                        break;
                    case 2:
                        //获取颜色
                        spec_value_data = curr_obj.children(".inputColor").children('input').val();
                        break;
                    case 3:
                        //获取图片路径
                        spec_value_data = curr_obj.children(".js-goods-spec-value-img").children("input[type='hidden']").val();
                        break;
                }
                var spec_value = {
                    spec_id: curr_obj.attr("data-spec-id"), //规格id
                    spec_name: curr_obj.attr("data-spec-name"), //规格名称
                    show_type: show_type, //展示方式
                    spec_value_name: spec_value_name, //规格值 
                    spec_value_data: spec_value_data  //附加值
                };
                //删除当前的添加按钮
                addGoodsSpecValue(spec_value, function () {
                    curr_obj.parent().append(getCurrentSpecValueHTML(spec_value));//加载当前添加的规格值、以及最后那个添加按钮
                    curr_obj.remove();
                });

            } else {
                // layer.msg("请输入规格值", {
                //     time: 1000
                // });
                message('请输入规格值');
            }
        }
        return false;//防止事件冒泡
    });

    /**
     * 循环处理价格 不让价格为空
     */
    $('body').on('keyup', 'input[name="sku_price"],input[name="market_price"],input[name="cost_price"],input[name="stock_num"],input[name="code"]', function () {
        var $this = $(this);
        var reg = /^\d+(.{0,1})\d{0,2}$/;
        if ($this.val().length > 0) {
            if (reg.test($this.val())) {
                if ($this.val().replace(/(^\s*)|(\s*$)/g, "") == "" || $this.val().replace(/(^\s*)|(\s*$)/g, "") == "0.00") {
                    if ($this.attr("name") == "stock_num") {
                        $this.val("0");
                    } else {
                        // $this.val("0.00");

                    }
                    $this.css("border-color", "#b94a48");
                    $this.parent().find(".help-inline").show();
                } else {
                    num = parseInt($this.val());
                    $this.css("border-color", "");
                    $this.parent().find(".help-inline").hide();
                }
                switch ($this.attr("name")) {
                    case "sku_price":
                        eachPrice();
                        break;
                    case "market_price":
                        eachMarketPrice();
                        break;
                    case "cost_price":
                        eachCostPrice();
                        break;
                    case "stock_num":
                        eachInput();
                        break;
                    case "code":
                        eachMerchantCode();
                        break;
                }
            } else {
                if ($this.attr("name") == "stock_num") {
                    $this.val("0");
                } else {
                    // $this.val("0.00");

                }
            }
        } else {
            if ($this.attr("name") == "stock_num") {
                $this.val("0");
            } else {
                // $this.val("0.00");

            }
        }

    });
    /**
     * 循环 处理库存
     */
    $('body').on('keyup', 'input[name="stock_num"]', function () {
        $stock = $(this);
        if ($stock.val().replace(/(^\s*)|(\s*$)/g, "") == "") {
            $stock.css("border-color", "#b94a48");
            $stock.parent().find(".help-inline").show();
        } else {
            $stock.css("border-color", "");
            $stock.parent().find(".help-inline").hide();
        }
        eachInput();
    });
    $(".brick.small").on("mouseover", function () {
        $(this).children().next().show();
    }).on("mouseout", function () {
        $(this).children().next().hide();
    });
    // 批量设置
    var js_batch_type = '';
    var shop_type = $("#shop_type").val();
    $('body').on('click', '.js-batch-price', function () {
        if (shop_type == 2 || (shop_type == 1 && goodsid == 0)) {
            js_batch_type = 'price';
            $('.js-batch-form').show();
            $('.js-batch-type').hide();
            $('.js-batch-txt').attr('placeholder', '请输入价格');
            $('.js-batch-txt').focus();
        }
    });
    $('body').on("click", ".js-batch-market_price", function () {
        if (shop_type == 2 || (shop_type == 1 && goodsid == 0)) {
            js_batch_type = 'market_price';
            $('.js-batch-form').show();
            $('.js-batch-type').hide();
            $('.js-batch-txt').attr('placeholder', '请输入市场价');
            $('.js-batch-txt').focus();
        }
    });
    $('body').on("click", ".js-batch-cost_price", function () {
        if (shop_type == 2 || (shop_type == 1 && goodsid == 0)) {
            js_batch_type = 'cost_price';
            $('.js-batch-form').show();
            $('.js-batch-type').hide();
            $('.js-batch-txt').attr('placeholder', '请输入成本价');
            $('.js-batch-txt').focus();
        }
    });
    $('body').on('click', '.js-batch-stock', function () {
        if (shop_type == 2 || (shop_type == 1 && goodsid == 0)) {
            js_batch_type = 'stock';
            $('.js-batch-form').show();
            $('.js-batch-type').hide();
            $('.js-batch-txt').attr('placeholder', '请输入库存');
            $('.js-batch-txt').focus();
        }
    });
    /**
     * 批量设置商家编码
     * 创建时间：2017年9月29日 10:09:40
     */
    $('body').on("click", ".js-batch-merchant-code", function () {
        if (shop_type == 2 || (shop_type == 1 && goodsid == 0)) {
            js_batch_type = 'code';
            $('.js-batch-form').show();
            $('.js-batch-type').hide();
            $('.js-batch-txt').attr('placeholder', '请输入商品货号');
            $('.js-batch-txt').focus();
        }
    });
    $('body').on('click', '.js-batch-save', function () {
        var batch_txt = $('.js-batch-txt');
        if (batch_txt.val() != null && batch_txt.val() != '') {
            var float_val = parseFloat(batch_txt.val());
            if (js_batch_type == 'price') {
                if (float_val > 9999999.99) {
                    // layer.msg('价格最大为 9999999.99');
                    message('价格最大为 9999999.99');
                    batch_txt.focus();
                    return false;
                } else if (!/^\d+(\.\d+)?$/.test(batch_txt.val())) {
                    // layer.msg('请输入合法的价格');
                    message('请输入合法的价格');
                    batch_txt.focus();
                    return false;
                } else {
                    batch_txt.val(float_val.toFixed(2));
                }
                $('.js-goods-stock .js-price').val(batch_txt.val());
                batch_txt.val('');
                // 商品价格
                $("input[name='price']").val(float_val.toFixed(2));
                $.each($temp_Obj, function (c, v) {
                    v["sku_price"] = float_val.toFixed(2);
                });
                $("input[name='price']").attr('readonly', true);
                eachPrice();
            } else if (js_batch_type == 'market_price') {// 市场价
                if (float_val > 9999999.99) {
                    message('价格最大为 9999999.99');
                    batch_txt.focus();
                    return false;
                } else if (!/^\d+(\.\d+)?$/.test(batch_txt.val())) {
                    message('请输入合法的价格');
                    batch_txt.focus();
                    return false;
                } else {
                    batch_txt.val(float_val.toFixed(2));
                }
                $('.js-goods-stock .js-market-price').val(batch_txt.val());
                $.each($temp_Obj, function (c, v) {
                    v["market_price"] = float_val.toFixed(2);
                });
                batch_txt.val('');
                eachMarketPrice();
            } else if (js_batch_type == 'cost_price') {// 成本价
                if (float_val > 9999999.99) {
                    message('价格最大为 9999999.99');
                    batch_txt.focus();
                    return false;
                } else if (!/^\d+(\.\d+)?$/.test(batch_txt.val())) {
                    message('请输入合法的价格');
                    batch_txt.focus();
                    return false;
                } else {
                    batch_txt.val(float_val.toFixed(2));
                }
                $('.js-goods-stock .js-cost-price').val(batch_txt.val());
                batch_txt.val('');
                // 商品价格
                $("input[name='price']").val(float_val.toFixed(2));
                $("input[name='price']").attr('readonly', true);
                $.each($temp_Obj, function (c, v) {
                    v["cost_price"] = float_val.toFixed(2);
                });
                eachCostPrice();
            } else if (js_batch_type == 'code') {// 商家编码
                $('.js-goods-stock .js-code').val(batch_txt.val());
                // 商品价格
                $("input[name='code']").val(batch_txt.val());
                $.each($temp_Obj, function (c, v) {
                    v["code"] = batch_txt.val();
                });
                batch_txt.val('');
                eachMerchantCode();
            } else {
                if (!/^\d+$/.test(batch_txt.val())) {
                    message('请输入合法的数字');
                    batch_txt.focus();
                    return false;
                }
                $('.js-goods-stock .js-stock-num').val(batch_txt.val());
                eachInput();
                $.each($temp_Obj, function (c, v) {
                    v["stock_num"] = float_val.toFixed(2);
                });
                $('input[name="total_stock"]').val(parseInt(batch_txt.val()) * $('.js-stock-num').size());
                batch_txt.val('');
            }
            $('.js-batch-form').hide();
            $('.js-batch-type').show();
        } else {
            message(batch_txt.attr("placeholder"), 'warning');
            batch_txt.focus();
        }
    });
    $('body').on('click', '.js-batch-cancel', function () {
        $('.js-batch-form').hide();
        $('.js-batch-type').show();
    });
    $('body').on('change', ".js-spec-table tbody tr td input", function () {
        var outer_key = $(this).parent().parent().attr("skuid");
        var key = $(this).attr("name");
        var value = $(this).val();
        $temp_Obj[outer_key][key] = value;
    });
    checkColorPicker();
    checkShipping();
    if (parseInt($("#goodsId").val()) > 0) {
        //初始化规格图片记录数组
        if ($.trim(sku_picture_array_str) != "" && $.trim(sku_picture_array_str) != undefined) {
            $sku_goods_picture = eval(sku_picture_array_str);
        }

        $("#goodsType").attr("data-flag", 2);//标识 0：表示添加商品，1：表示编辑商品，商品分类为0,2：表示编辑商品，商品分类不为0

        getGoodsSpecListByAttrId($("#goodsType").val(), function () {
            editSkuData(goods_spec_format, sku_list);
            //加载属性
            $(".js-goods-sku-attribute tr").each(function () {
                var value = $(this).children("td:first").attr("data-value");//商品属性名称
                var value_name = $(this).children("td:last");//具体的属性值
                if (value != undefined && value != "") {
                    for (var i = 0; i < goods_attribute_list.length; i++) {
                        var curr = goods_attribute_list[i];
                        if (curr['attr_value'] == value) {
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
        $("#goodsType").attr("data-flag", 0);//标识 0：表示添加商品，1：表示编辑商品，商品分类为0,2：表示编辑商品，商品分类不为0
        getGoodsSpecListByAttrId($("#goodsType").val());
    }
});


var img_id_arr;
var flag = false;//防止重复提交
//保存商品
//默认实物商品
var goods_type = 1;

$("input[name ='store_list']").prop("checked", this.checked);
var store_service = [];


function SubmitProductInfo(type) {
    var isFlag = $("#form1").valid();
    if (!isFlag) {
        return;
    }
    img_id_arr = "";// 商品主图
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


    $("#lastPage,#btnSave,#btnSave2").attr("disabled", "disabled");
    var productViewObj = PackageProductInfo();
    var $qrcode = $("#hidQRcode").val();
    if (flag) {
        return;
    }
    flag = true;
    $.ajax({
        url: __URL(ADMINMAIN + "/goods/GoodsCreateOrUpdate"),
        type: "post",
        async: false,
        data: {"product": JSON.stringify(productViewObj), "is_qrcode": $qrcode},
        dateType: "json",
        success: function (res) {

        }
    });

}
/**
 * 创建时间：2015年6月11日18:07:10 创建人：高伟 功能说明：获取数据已对象方式存储
 */
function PackageProductInfo() {
    // 初始化一个实体 将页面所需的数据存放到对象中
    var shop_type = $("#shop_type").val();
    var productViewObj = new Object();
    productViewObj.goodsId = $("#goodsId").val();// 商品id 11号目前为死值 0
    productViewObj.title = $("#txtProductTitle").val();// 商品标题
    productViewObj.introduction = $("#txtIntroduction").val();// 商品简介，促销语
    productViewObj.categoryId = $("#tbcNameCategory").attr("cid");// 商品类目 
    var category_extend_id = "";
    $(".extend-name-category").each(function () {
        if (category_extend_id == "") {
            category_extend_id = $(this).attr("cid");
        } else {
            category_extend_id += "," + $(this).attr("cid");
        }
    })
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
    productViewObj.is_sale = $("input[name='shelves']:checked").val();// 上下架标记
    productViewObj.display_stock = $('.controls input[name="stock"]:checked ').val();// 是否显示库存
    productViewObj.stock = $("#txtProductCount").val();// 总库存
    productViewObj.minstock = $("#txtMinStockLaram").val();// 库存预警数
    productViewObj.max_buy = $("#PurchaseSum").val() == "" ? 0 : $("#PurchaseSum").val();// 每人限购
    productViewObj.min_buy = $("#minBuy").val() == "" ? 0 : $("#minBuy").val();// 最少购买数
    productViewObj.key_words = $("#txtKeyWords").val();//商品关键词
    productViewObj.description = UE.getEditor('editor').getContent();// 商品详情描述
    productViewObj.shipping_fee = $("#shipping_fee").val();// 统一运费
    productViewObj.shipping_fee_id = $("#shipping_fee_id").val();
    /*productViewObj.shipping_fee_id = $("input[name='fare']:checked").val() != 1 ? 0 : $("#deliverySelect").val();*/// 运费模板编号
    //alert(JSON.stringify(productViewObj));
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
    productViewObj.picture = img_id_arr.split(",")[0];
    var imageVals = img_id_arr;// 在页面中获取的
    productViewObj.imageArray = imageVals;// 商品图片分组
    //sku规格图片
    var sku_img_obj = $(".J-sku_img_id");
    var sku_picture_obj = new Array();
    for (var $i = 0; $i < sku_img_obj.length; $i++) {
        var $checkObj = $(sku_img_obj[$i]);
        var spec_id = $checkObj.parents('.goods-sku-item').find('span').data("spec_id");
        var spec_value_id = $checkObj.parents('.goods-sku-item').find('span').data("spec_value_id");
        var img_id = $checkObj.val();
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
    productViewObj.goods_attribute_id = $("#goodsType").val();
    productViewObj.sort = $("#goodsSort").val();
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
    productViewObj.shipping_fee_type = $("input[name='shipping_fee_type']:checked").val();
    //核销信息
    var verificationinfo = new Object();
    if(goods_type==0){
        verificationinfo.verification_num = $("#verification_num").val(); //核销次数
        verificationinfo.card_type = $("input[name='card_models']:checked").val(); //卡包模式
        verificationinfo.valid_type = $("input[name='effective_type']:checked").val(); //有效类型
        verificationinfo.valid_days = $("#effective_day").val(); //有效天数
        verificationinfo.end_time = $("input[name='expiration_time']").val(); //有效期截止时间
        verificationinfo.store_list = store_list;                //门店
    }
    //卡券信息
    var cardinfo = new Object();
    $("input[name ='store_service']:checked").each(function(){
        store_service.push($(this).val());
    })
    var card_switch = $("input[name='card_switch']:checked").val();
    if(card_switch==1){
        var card_color = $("#cart_color").val();
        cardinfo.card_title = $("#wxCard_setTitle").val();    //卡券标题
        cardinfo.card_descript = $("#wxCard_des").val();    //卡券详情
        cardinfo.card_pic_id = $("input[name='upload_wxCard_img_id']").val();    //卡券图片ID
        cardinfo.op_tips = $("#op_tips").val();             //操作提示
        cardinfo.send_set = $("input[name='make_money']:checked").val();   //赠送开关
        cardinfo.store_service = store_service;    //商户服务
        cardinfo.card_color = card_color;   //卡券颜色
    }
    productViewObj.cardinfo = cardinfo;
    productViewObj.verificationinfo = verificationinfo;
    ;

    return productViewObj;
}
/**
 * 根据选择的商品类型，查询规格属性
 */
$('body').find("#goodsType").change(function () {
    goodsTypeChangeData();
    getGoodsSpecListByAttrId($(this).val());
});

/**
 * 商品类型id为空时查询商品规格信息
 * @param attr_id 规格属性id
 */
function getGoodsSpecListByZero(callBack) {
    $.ajax({
        url: __URL(ADMINMAIN + "/goods/getGoodsSpecListByAttrId"),
        type: "post",
        data: {"attr_id": 0},
        success: function (res) {
            if (res != -1) {
                $(".js-goods-spec-block").show();
                var html = '';
                var spec_length = res.spec_list.length;
                var attribute_length = res.attribute_list.length;
                //商品规格集合
                if (spec_length > 0) {

                    for (var i = 0; i < spec_length; i++) {
                        var type = '';

                        var curr_spec = res.spec_list[i];
                        switch (parseInt(curr_spec.show_type)) {
                            case 1:
                                type = 'size';//文字
                                break;
                            case 2:
                                type = 'colorTd';//颜色
                                break;
                            case 3:
                                type = 'imgColor';//图片
                                break;
                        }
                        html += '<tr class="js-spec-item goods-sku-block-' + curr_spec.spec_id + '">';
                        html += '<td>' + curr_spec.spec_name + '</td>';
                        html += '<td class="tdWidth ' + type + '">';
                        for (var j = 0; j < curr_spec.values.length; j++) {
                            var curr_spec_value = curr_spec.values[j];
                            html += '<article class="goods-sku-item">';
                            html += '<span data-spec-name="' + curr_spec.spec_name + '" data-spec-id="' + curr_spec.spec_id + '"';
                            if (parseInt(curr_spec.show_type) == 2 && curr_spec_value.spec_value_data == "") {
                                curr_spec_value.spec_value_data = "#000000";
                            }
                            html += ' data-spec-value-data="' + curr_spec_value.spec_value_data + '"';
                            html += ' data-spec-show-type="' + curr_spec.show_type + '"';
                            html += ' data-spec-value-id="' + curr_spec_value.spec_value_id + '">';
                            html += curr_spec_value.spec_value_name + '</span>';
                            //显示方式
                            switch (parseInt(curr_spec.show_type)) {
                                case 1:
                                    //文字
                                    break;
                                case 2:
                                    //颜色
                                    html += '<i></i>';
                                    html += '<div class="inputColor">';
                                    html += '<input class="colorpicker" name="goods_spec_value' + (i + j) + '" value="' + curr_spec_value.spec_value_data + '">';
                                    html += '</div>';
                                    break;
                                case 3:
                                    //图片
                                    var index = curr_spec.spec_id + curr_spec_value.spec_value_id;
                                    var onclickpic = "showAlbum(this, 1, 'selectpicspec(this)');";
                                    html += '<i></i>';
                                    html += '<div class="inputImg">';
                                    html += '<a href="javascript:void(0)" onclick="' + onclickpic + '">';
                                    if (curr_spec_value.spec_value_data != "") {
                                        html += '<img src="' + __IMG(curr_spec_value.pic) + '"  id="imggoods_sku' + index + '">';
                                    } else {
                                        html += '<img src="' + ADMINIMG + '/goods_sku_add.png"  id="imggoods_sku' + index + '"/>';
                                    }
                                    html += '<input type="hidden" id="goods_sku' + index + '" class="J-pic J-sku_img_id" value="' + curr_spec_value.spec_value_data + '">';
                                    html += '</a>';
                                    html += '</div>';
                                    break;
                            }
                            html += '</article>';
                        }

                        var spec = {
                            spec_id: curr_spec.spec_id,
                            spec_name: curr_spec.spec_name,
                            show_type: parseInt(curr_spec.show_type)
                        };
                        html += getAddSpecValueHtml(spec);
                        html += '</td>';
                        html += '</tr>';
                    }

                    html += '<tr>';
                    html += '<td colspan="2">' + getAddSpecHtml() + '</td>';//规格添加
                    html += '</tr>';
                    $("#goods-sku").html(html);
                } else {
                    html = '<tr><td colspan="2">' + getAddSpecHtml() + '</td></tr>';
                    $(".js-goods-sku").html(html);
                }
                $(".js-goods-spec-block").show();
                //商品属性集合
                if (attribute_length > 0) {
                    var html = "";
                    for (var i = 0; i < attribute_length; i++) {
                        var curr = res.attribute_list[i];
                        if ($.trim(curr.value_items) == "" && parseInt(curr.type) != 1) {
                            continue;
                        }
                        if ($.trim(curr.attr_value_name) != "") {
                            html += '<tr>';
                            html += '<td data-value="' + curr.attr_value_name + '">' + curr.attr_value_name + '</td>';
                            html += '<td class="tdWidth">';
                            switch (parseInt(curr.type)) {
                                case 1:
                                    //输入框
                                    html += '<input type="text" class="js-attribute-text" id="input-text-' + curr.attr_value_id + '-' + curr.attr_value_id + '"data-attribute-value-id="' + curr.attr_value_id + '" data-attribute-value="' + curr.attr_value_name + '" data-attribute-sort="' + curr.sort + '"/>';
                                    break;
                                case 2:
                                    //单选框
                                    for (var j = 0; j < curr.value_items.length; j++) {
                                        var value = curr.value_items[j];
                                        if ($.trim(value) != "") {
                                            html += '<div class="goods-sku-attribute-item-radio">';
                                            html += '<input type="radio" value="' + value + '" class="js-attribute-radio" id="radio_value_item' + curr.attr_value_id + '-' + j + '" data-attribute-value-id="' + curr.attr_value_id + '" data-attribute-value="' + curr.attr_value_name + '"  name="radio_value' + i + '" data-attribute-sort="' + curr.sort + '"/>&nbsp;';
                                            html += '<label for="radio_value_item' + curr.attr_value_id + '-' + j + '">' + value + '</label>';
                                            html += '</div>';
                                        }
                                    }
                                    break;
                                case 3:
                                    //复选框
                                    for (var j = 0; j < curr.value_items.length; j++) {
                                        var value = curr.value_items[j];
                                        if ($.trim(value) != "") {
                                            html += '<div class="goods-sku-attribute-item-checkbox">';
                                            html += '<input type="checkbox" value="' + value + '" class="js-attribute-checkbox" id="checkbox_value_item' + curr.attr_value_id + '-' + j + '" data-attribute-value-id="' + curr.attr_value_id + '" data-attribute-value="' + curr.attr_value_name + '"  name="checkbox_value_item' + i + '" data-attribute-sort="' + curr.sort + '"/>&nbsp;';
                                            html += '<label for="checkbox_value_item' + curr.attr_value_id + '-' + j + '">' + value + '</label>';
                                            html += '</div>';
                                        }
                                    }
                                    break;
                            }
                            html += '</td>';
                            html += '</tr>';
                        }
                    }
                    html += '<tr><td colspan="2">' + getAddAttrValueHtml() + '</td></tr>';
                    $(".js-goods-sku-attribute").html(html);
                } else {
                    html = '<tr><td colspan="2">' + getAddAttrValueHtml() + '</td></tr>';
                    $(".js-goods-sku-attribute").html(html);
                }
                if (callBack != undefined) {
                    callBack();
                }
                $(".js-goods-attribute-block").show();
            }
            checkColorPicker();
        }
    });
}
/**
 * 根据商品类型id，查询商品规格信息
 * @param attr_id 规格属性id
 */
function getGoodsSpecListByAttrId(attr_id, callBack) {
    if (!isNaN(attr_id) && attr_id > 0) {
        $.ajax({
            url: __URL(ADMINMAIN + "/goods/getGoodsSpecListByAttrId"),
            type: "post",
            data: {"attr_id": parseInt(attr_id)},
            success: function (res) {

                if (res != -1) {
                    $(".js-goods-spec-block").show();
                    var html = '';
                    var spec_length = res.spec_list.length;
                    var attribute_length = res.attribute_list.length;
                    //商品规格集合
                    if (spec_length > 0) {

                        for (var i = 0; i < spec_length; i++) {
                            var type = '';

                            var curr_spec = res.spec_list[i];
                            switch (parseInt(curr_spec.show_type)) {
                                case 1:
                                    type = 'size';//文字
                                    break;
                                case 2:
                                    type = 'colorTd';//颜色
                                    break;
                                case 3:
                                    type = 'imgColor';//图片
                                    break;
                            }
                            html += '<tr class="js-spec-item goods-sku-block-' + curr_spec.spec_id + '">';
                            html += '<td>' + curr_spec.spec_name + '</td>';
                            html += '<td class="tdWidth ' + type + '">';
                            for (var j = 0; j < curr_spec.values.length; j++) {
                                var curr_spec_value = curr_spec.values[j];
                                html += '<article class="goods-sku-item">';
                                html += '<span data-spec-name="' + curr_spec.spec_name + '" data-spec-id="' + curr_spec.spec_id + '"';
                                if (parseInt(curr_spec.show_type) == 2 && curr_spec_value.spec_value_data == "") {
                                    curr_spec_value.spec_value_data = "#000000";
                                }
                                html += ' data-spec-value-data="' + curr_spec_value.spec_value_data + '"';
                                html += ' data-spec-show-type="' + curr_spec.show_type + '"';
                                html += ' data-spec-value-id="' + curr_spec_value.spec_value_id + '">';
                                html += curr_spec_value.spec_value_name + '</span>';
                                //显示方式
                                switch (parseInt(curr_spec.show_type)) {
                                    case 1:
                                        //文字
                                        break;
                                    case 2:
                                        //颜色
                                        html += '<i></i>';
                                        html += '<div class="inputColor">';
                                        html += '<input class="colorpicker" name="goods_spec_value' + (i + j) + '" value="' + curr_spec_value.spec_value_data + '">';
                                        html += '</div>';
                                        break;
                                    case 3:
                                        //图片
                                        var index = curr_spec.spec_id + curr_spec_value.spec_value_id;
                                        var onclickpic = "showAlbum(this, 1, 'selectpicspec(this)');";
                                        html += '<i></i>';
                                        html += '<div class="inputImg">';
                                        html += '<a href="javascript:void(0)" onclick="' + onclickpic + '">';
                                        if (curr_spec_value.spec_value_data != "") {
                                            html += '<img src="' + __IMG(curr_spec_value.pic) + '"  id="imggoods_sku' + index + '">';
                                        } else {
                                            html += '<img src="' + ADMINIMG + '/goods_sku_add.png"  id="imggoods_sku' + index + '"/>';
                                        }
                                        html += '<input type="hidden" id="goods_sku' + index + '" class="J-pic J-sku_img_id" value="' + curr_spec_value.spec_value_data + '">';
                                        html += '</a>';
                                        html += '</div>';
                                        break;
                                }
                                html += '</article>';
                            }

                            var spec = {
                                spec_id: curr_spec.spec_id,
                                spec_name: curr_spec.spec_name,
                                show_type: parseInt(curr_spec.show_type)
                            };
                            html += getAddSpecValueHtml(spec);
                            html += '</td>';
                            html += '</tr>';
                        }

                        html += '<tr>';
                        html += '<td colspan="2">' + getAddSpecHtml() + '</td>';//规格添加
                        html += '</tr>';
                        $("#goods-sku").html(html);
                        $(".js-goods-spec-block").show();
                    } else {
                        
                        html = '<tr><td colspan="2">' + getAddSpecHtml() + '</td></tr>';
                        $(".js-goods-sku").html(html);
                    }
                    //商品属性集合
                    if (attribute_length > 0) {
                        var html = "";
                        for (var i = 0; i < attribute_length; i++) {
                            var curr = res.attribute_list[i];
                            if ($.trim(curr.value_items) == "" && parseInt(curr.type) != 1) {
                                continue;
                            }
                            if ($.trim(curr.attr_value_name) != "") {


                                html += '<tr>';
                                html += '<td data-value="' + curr.attr_value_name + '">' + curr.attr_value_name + '</td>';
                                html += '<td class="tdWidth">';
                                switch (parseInt(curr.type)) {
                                    case 1:
                                        //输入框
                                        html += '<input type="text" class="js-attribute-text" id="input-text-' + curr.attr_value_id + '-' + curr.attr_value_id + '"data-attribute-value-id="' + curr.attr_value_id + '" data-attribute-value="' + curr.attr_value_name + '" data-attribute-sort="' + curr.sort + '"/>';
                                        break;
                                    case 2:
                                        //单选框
                                        for (var j = 0; j < curr.value_items.length; j++) {
                                            var value = curr.value_items[j];
                                            if ($.trim(value) != "") {
                                                html += '<div class="goods-sku-attribute-item-radio">';
                                                html += '<input type="radio" value="' + value + '" class="js-attribute-radio" id="radio_value_item' + curr.attr_value_id + '-' + j + '" data-attribute-value-id="' + curr.attr_value_id + '" data-attribute-value="' + curr.attr_value_name + '"  name="radio_value' + i + '" data-attribute-sort="' + curr.sort + '"/>&nbsp;';
                                                html += '<label for="radio_value_item' + curr.attr_value_id + '-' + j + '">' + value + '</label>';
                                                html += '</div>';
                                            }
                                        }
                                        break;
                                    case 3:
                                        //复选框
                                        for (var j = 0; j < curr.value_items.length; j++) {
                                            var value = curr.value_items[j];
                                            if ($.trim(value) != "") {
                                                html += '<div class="goods-sku-attribute-item-checkbox">';
                                                html += '<input type="checkbox" value="' + value + '" class="js-attribute-checkbox" id="checkbox_value_item' + curr.attr_value_id + '-' + j + '" data-attribute-value-id="' + curr.attr_value_id + '" data-attribute-value="' + curr.attr_value_name + '"  name="checkbox_value_item' + i + '" data-attribute-sort="' + curr.sort + '"/>&nbsp;';
                                                html += '<label for="checkbox_value_item' + curr.attr_value_id + '-' + j + '">' + value + '</label>';
                                                html += '</div>';
                                            }
                                        }
                                        break;
                                }
                                html += '</td>';
                                html += '</tr>';
                            }
                        }
                        html += '<tr><td colspan="2">' + getAddAttrValueHtml() + '</td></tr>';
                        $(".js-goods-sku-attribute").html(html);
                    } else {
                        html = '<tr><td colspan="2">' + getAddAttrValueHtml() + '</td></tr>';
                        $(".js-goods-sku-attribute").html(html);
                    }
                    if (callBack != undefined) {
                        callBack();
                    }

                    $(".js-goods-attribute-block").show();

                }
                checkColorPicker();
            }
        });
    } else {
        //标识 0：表示添加商品，1：表示编辑商品，商品分类为0,2：表示编辑商品，商品分类不为0
        switch (parseInt($("#goodsType").attr("data-flag"))) {
            case 0:
//                var html = '';
//                html += '<tr>';
//                html += '<td class="js-spec-add">' + getAddSpecHtml() + '</td>';//规格添加
//                html += '</tr>';
//                $(".js-goods-sku").html(html);
//                $(".js-goods-spec-block").show();
//                var html_attribute = '<tr><td colspan="2">' + getAddAttrValueHtml() + '</td></tr>';
//                $(".js-goods-sku-attribute").html(html_attribute);
//                $(".js-goods-attribute-block").show();
                getGoodsSpecListByZero(callBack);
                break;
            case 1:
                //如果当前商品的商品类型为0，则不根据商品类型id加载数据
                getGoodsSpecListByZero(callBack);
                break;
            case 2:
                getGoodsSpecListByZero(callBack);
                break;
        }
    }
}


function getGoodsSpecListNotAttrId() {
    if (goods_spec_format == "") {
        return;
    }
    goods_spec_format = eval(goods_spec_format);
    var spec_length = goods_spec_format.length;
    var spec_list = goods_spec_format;
    var html = '';
    for (var i = 0; i < spec_length; i++) {

        var curr_spec = spec_list[i];
        html += '<tr class="js-spec-item goods-sku-block-' + curr_spec.spec_id + '">';
        html += '<td>' + curr_spec.spec_name + "</td>";
        html += '<td class="tdWidth">';

        for (var j = 0; j < curr_spec.value.length; j++) {
            var curr_spec_value = curr_spec.value[j];
            html += '<article class="goods-sku-item">';

            html += '<span data-spec-name="' + curr_spec.spec_name + '"';
            html += ' data-spec-id="' + curr_spec.spec_id + '"';
            if (parseInt(curr_spec.show_type) == 2 && curr_spec_value.spec_value_data == "") {
                curr_spec_value.spec_value_data = "#000000";
            }
            html += ' data-spec-value-data="' + curr_spec_value.spec_value_data + '"';
            html += ' data-spec-show-type="' + curr_spec_value.spec_show_type + '"';
            if (curr_spec_value.spec_show_type == 3) {
                html += ' data-spec-value-data-src="' + curr_spec_value.spec_value_data_src + '"';
            }
            html += ' data-spec-value-id="' + curr_spec_value.spec_value_id + '">';
            html += curr_spec_value.spec_value_name + "</span>";


            //显示方式
            switch (parseInt(curr_spec_value.spec_show_type)) {
                case 1:
                    //文字
                    break;
                case 2:
                    //颜色
                    html += '<i></i>';
                    html += '<div class="inputColor">';
                    html += '<input class="colorpicker" name="goods_spec_value' + (i + j) + '" value="' + curr_spec_value.spec_value_data + '">';
                    html += '</div>';
                    break;
                case 3:
                    //图片
                    var index = curr_spec.spec_id + curr_spec_value.spec_value_id;
                    var onclickpic = "showAlbum(this, 1, 'selectpicspec(this)');";
                    html += '<i></i>';
                    html += '<div class="inputImg">';
                    html += '<a href="javascript:void(0)" onclick="' + onclickpic + '">';
                    if (curr_spec_value.spec_value_data != "") {
                        html += '<img src="' + __IMG(curr_spec_value.spec_value_data_src) + '"  id="imggoods_sku' + index + '">';
                    } else {
                        html += '<img src="' + ADMINIMG + '/goods_sku_add.png"  id="imggoods_sku' + index + '"/>';
                    }
                    html += '<input type="hidden" id="goods_sku' + index + '" class="J-pic J-sku_img_id" value="' + curr_spec_value.spec_value_data + '">';
                    html += '</a>';
                    html += '</div>';
                    break;
            }

            html += '</article>';

        }
        var spec = {
            spec_id: curr_spec.spec_id,
            spec_name: curr_spec.spec_name,
            show_type: curr_spec.value[0]["spec_show_type"]
        };
        html += getAddSpecValueHtml(spec);//添加规格值按钮
        html += '</td>';
        html += '</tr>';
    }

    html += '<tr>';
    if (spec_length == 0) {
        html += '<td class="js-spec-add">' + getAddSpecHtml() + '</td>';//规格添加
    } else {
        html += '<td class="js-spec-add" colspan="2">' + getAddSpecHtml() + '</td>';//规格添加
    }
    html += '</tr>';

    $(".js-goods-spec-block").show();
    $(".js-goods-sku").html(html);
    editSkuData(goods_spec_format, sku_list);
}
/**
 * 返回添加规格值THML代码
 */
function getAddSpecValueHtml(spec) {
    var html = '<a href="javascript:void(0);" class="blue js-goods-spec-value-add goods-sku-add-text" style="display:inline-block" data-spec-name="' + spec.spec_name + '" data-spec-id="' + spec.spec_id + '" data-show-type="' + spec.show_type + '">添加规格值</a>';
    return html;
}
/**
 * 返回添加规格HTML代码
 */
function getAddSpecHtml() {
    var html = '<a href="javascript:;" class="blue" data-toggle="modal" data-target="#addModal">添加规格</a>';
    return html;
}
/**
 * 返回添加属性HTML代码
 */
function getAddAttrValueHtml() {
    var html = '<a href="javascript:;" class="blue" data-toggle="modal" data-target="#addAttrValue">添加属性</a>';
    return html;
}
/*
 * 商品图片相册确认
 */
function selectpic() {
    $('#licenseImg').modal('hide');
    var chooseId = $('#J-choose').val();
    var chooseImg = $('#J-choose_img').val();
    if (chooseId === '') {
        $('.J-imgs').html('');
        return;
    }
    var aPicId = chooseId.split(',');
    var aPicImg = chooseImg.split(',');
    var html = '';
    for (var i = 0; i < aPicId.length; i++) {
        html += '<div class="goods_picture">';
        html += '<span class="J-deleteImg" style="position: absolute;top: 0px;right: 0px;display: block;line-height: 10px;font-size: 14px;cursor: pointer;padding: 5px;color: #fff;background: rgba(0,0,0,0.3);">X</span>';
        html += '<img src="' + aPicImg[i] + '">';
        html += '<input class="upload_img_id" type="hidden" value="' + aPicId[i] + '">';
        html += '</div>';
    }
    $('.J-imgs').html(html);
}
/*
 * 商品图片重置
 */
function imgReady() {
    var imgId = '';
    var imgUrl = '';
    $('.goods_picture').each(function () {

        if ($(this).find('.upload_img_id').length > 0) {
            imgId += $(this).find('.upload_img_id').val() + ',';
            imgUrl += $(this).find('img').attr('src') + ',';
        }
    });
    $('#J-choose').val(imgId.substr(0, imgId.length - 1));
    $('#J-choose_img').val(imgUrl.substr(0, imgUrl.length - 1));
}
/*
 * 规格图片相册确认
 */
function selectpicspec(event) {
    $('#licenseImg').modal('hide');
    var chooseId = $('#J-choose').val();
    var chooseImg = $('#J-choose_img').val();
    if (chooseId === '') {
        return;
    }
    var dom = $(event).attr('dom');
    $('#' + dom).val(chooseId);
    $('#img' + dom).attr('src', chooseImg);
    var obj = $('#' + dom).parents('.goods-sku-item').children('span');
    $.ajax({
        url: __URL(ADMINMAIN + "/goods/modifyGoodsSpecValueField"),
        type: "post",
        data: {"spec_value_id": obj.attr("data-spec-value-id"), "field_name": 'spec_value_data', "field_value": chooseId},
        success: function () {
            var spec = {
                flag: obj.hasClass("selected"),
                spec_id: obj.attr("data-spec-id"),
                spec_name: obj.attr("data-spec-name"),
                spec_value_id: obj.attr("data-spec-value-id"),
                spec_value_data: chooseId,
                spec_value_data_src: chooseImg

            };
            editSpecValueData(spec);
        }
    });

}
/**
 * 循环价格
 */
function eachPrice() {
    var $price = 0;
    $.each($('input[name="sku_price"]'), function (i, item) {
        var $this = $(item);
        var num = $this.val();
        var numint = parseFloat(num);
        var priceint = parseFloat($price);
        if ($price == 0 || numint < priceint)
            $price = num;
    });
    $("#txtProductSalePrice").val($price);
}
/**
 * 循环市场价 2016年12月2日 11:55:30
 */
function eachMarketPrice() {
    var $price = 0;
    $.each($('input[name="market_price"]'), function (i, item) {
        var $this = $(item);
        var num = $this.val();
        var numint = parseFloat(num);
        var priceint = parseFloat($price);
        if ($price == 0 || numint < priceint)
            $price = num;
    });
    $("#txtProductMarketPrice").val($price);
}
/**
 * 循环成本价 2016年12月2日 12:14:27
 */
function eachCostPrice() {
    var $price = 0;
    $.each($('input[name="cost_price"]'), function (i, item) {
        var $this = $(item);
        var num = $this.val();
        var numint = parseFloat(num);
        var priceint = parseFloat($price);
        if ($price == 0 || numint < priceint)
            $price = num;
    });
    $("#txtProductCostPrice").val($price);
}

/**
 * 循环商家编码，取第一个
 * 创建时间：2017年9月29日 11:44:05
 */
function eachMerchantCode() {
//	if($('input[name="code"]:last').val() != undefined && $('input[name="code"]:last').val() != ""){
//		$("#txtProductCodeA").val($('input[name="code"]:last').val());
//	}
}

/**
 * 循环库存
 */
function eachInput() {
    var $stockTotal = 0;
    $.each($('input[name="stock_num"]'), function (i, item) {
        var $this = $(item);
        var num = 0;
        num = parseInt($this.val());
        $stockTotal = $stockTotal + num;
    });
    $("#txtProductCount").val($stockTotal);
}

//分类
$(".goods_sort_inline").on('change',function(){
    var cid1 = '';
    var cid2 = '';
    var cid3 = '';

    var cid = $(this).val();
    var type = $(this).attr('type');
    if(type=='cate_1'){
        cid1 = cid;
    }
    if(type=='cate_2'){
        cid2 = cid;
    }
    if(type=='cate_3'){
        cid3 = cid;
    }
    //var pid = $("#category_id_" + type).find("option:selected").val();
    $.ajax({
        type: "post",
        url: __URL(ADMINMAIN + '/goods/get_binding_brand'),
        data: {
            'cid1': cid1,
            'cid2': cid2,
            'cid3': cid3,
        },
        async: true,
        success: function (data) {
            var html = '';

            if(data.length>0){
                html +='<option value="">请选择</option>';
                for (var i = 0; i < data.length; i++) {
                    html += '<option value="' + data[i]['brand_id'] + '">' + data[i]['brand_name'] + '</option>';
                }
            }else{
                html +='<option value="">请选择</option>';
            }
            $("#brand_id").html(html);
        }
    });
})

function category() {
    $("#goods_sort_1").change(function () {
        var parentId = $(this).val();
        var html = '<option value="" attr_id="0" cname="二级分类">二级分类</option>';
        $("#goods_sort_2").html(html);
        var html2 = '<option value="" attr_id="0" cname="三级分类">三级分类</option>';
        $("#goods_sort_3").html(html2);
        if(!parentId){
            return;
        }
        $.ajax({
            type: 'post',
            url: __URL(ADMINMAIN + '/goods/getcategorybyparentajax'),
            data: {"parentId": parentId},
            async: true,
            success: function (data) {
                if (data.length > 0) {
                    var html = '<option value="" attr_id="0" cname="二级分类">二级分类</option>';
                    for (var i = 0; i < data.length; i++) {
                        html += '<option value="' + data[i]['category_id'] + '" attr_id ="' + data[i]['attr_id'] + '" cname="' + data[i]['category_name'] + '">' + data[i]['category_name'] + '</option>';
                    }
                    $("#goods_sort_2").html(html);
                } else {
                    var html = '<option value="" attr_id="0" cname="二级分类">二级分类</option>';
                    $("#goods_sort_2").html(html);

                }
            }
        });
        return false;
    });
    $("#goods_sort_2").change(function () {
        var parentId = $(this).val();
        if(!parentId){
            return;
        }
        $("#category_id_2").val(parentId);
        $("#category_id_3").val('');
        $.ajax({
            type: 'post',
            url: __URL(ADMINMAIN + '/goods/getcategorybyparentajax'),
            data: {"parentId": parentId},
            success: function (data) {
                if (data.length > 0) {
                    var html = '<option value="" attr_id="0" cname="三级分类">三级分类</option>';
                    for (var i = 0; i < data.length; i++) {
                        html += '<option value="' + data[i]['category_id'] + '" attr_id ="' + data[i]['attr_id'] + '" cname="' + data[i]['category_name'] + '">' + data[i]['category_name'] + '</option>'
                        //html += '<li onclick="goodsCategoryThree(this);" category_id="' + data[i]['category_id'] + '" attr_id ="' + data[i]['attr_id'] + '" cname="' + data[i]['category_name'] + '">' + data[i]['category_name'] + '<i class="fa fa-angle-right fa-lg"></i></li>';
                    }
                    $("#goods_sort_3").html(html);
                } else {
                    var html = '<option value="" attr_id="0" cname="三级分类">三级分类</option>';
                    $("#goods_sort_3").html(html);
                }
            }
        })
    });
    $('.J-category').on('change', function () {
        var parentId = $(this).val();
        var attrId = $(this).find('option:selected').attr("attr_id");
        var category_name = $(this).find('option:selected').attr("cname");
        var goodsid = $("#tbcNameCategory").data('goods-id');
        $("#tbcNameCategory").attr("cid", parentId);
        $("#tbcNameCategory").attr("cname", category_name);
        if (goodsid == 0) {
            $("#goodsType").val(attrId);
            goodsTypeChangeData();
            getGoodsSpecListByAttrId($("#goodsType").val());
        }
        var cate_level = $(this).parent().attr('id');
        if (cate_level == 'goods_sort_1') {
            $("#category_id_1").val(parentId);
            $("#category_id_2").val('');
            $("#category_id_3").val('');
        } else if (cate_level == 'goods_sort_2') {
            $("#category_id_2").val(parentId);
            $("#category_id_3").val('');
        } else {
            $("#category_id_3").val(parentId);
        }
        
    });
}

function goodsCategoryThree(obj) {
    var parentId = $(obj).attr("category_id");
    var attrId = $(obj).attr("attr_id");
    var category_name = $(obj).attr("cname");
    var goodsid = $("#tbcNameCategory").data('goods-id');
    $("#tbcNameCategory").attr("cid", parentId);
    $("#tbcNameCategory").attr("cname", category_name);
    if (goodsid == 0) {
        $("#goodsType").val(attrId);
        goodsTypeChangeData();
        getGoodsSpecListByAttrId($("#goodsType").val());
        if (parseInt($("#goodsType").val()) == 0) {
            //				//如果没有选择商品类型，则清空属性信息
            $(".js-goods-attribute-block").hide();
            $(".js-goods-sku-attribute").html("");
        }
    }
    var category_name = $(obj).text();
    $(".three ul li").not($(obj)).removeClass("selected");
    $(obj).addClass("selected");
    var goodsCategoryOne = $("#goodsCategoryOne").val();
    $("#goodsCategoryOne").val(goodsCategoryOne + '' + category_name);
    $("#category_id_3").val(parentId);
    $(".one").hide();
    $(".two").hide();
    $(".three").hide();
    $(".selectGoodsCategory").hide();

    $(".selectGoodsCategory").css({
        'width': 218,
        'right': 580
    });
    $("#goodsCategoryOne").attr('is_show', 'false');
}

/*
 * 弹窗内增加规格值
 */
function addSpecValue(e) {
    var show_type = $("input[name='show_type']:checked").val();
    var html = '<tr class="spec_data new_data">';
    if (show_type == 2) {
        html += '<td class="w50"><input type="text" name="spec_value" class="w50"><input type="color" name="spec_value_data" style="width:60px;margin-top:5px;" class="input-common"/></td>';
    } else {
        html += '<td class="w50"><input type="text" name="spec_value" class="w50"></td>';
    }
    html += '<td class="w50"><a href="javascript:void(0);" onclick="delNewSpecValue(this)" class="del">删除</a></td>';
    html += '</tr>';
    $(".tbContent").append(html);
}
/*
 * 弹窗内删除规格值
 */
function delNewSpecValue(e) {
    // layer.confirm('确定要删除吗？', {
    //     btn: ['确定', '取消']//按钮
    // }, function (index) {
    //     layer.close(index);
    //     $(e).parents('tr').remove();
    // });
    alerts('确定要删除吗？',function(){
        $(e).parents('tr').remove();
    });
}
/*
 * 弹窗内规格值确定
 */
var flag = false;
function addGoodsSpec() {
    var spec_name = $.trim($("#spec_name").val());
    var attr_id = $('select[name="goodsType"]').val();
    var sort = $("#sku_sort").val();
    var show_type = $("input[name='show_type']:checked").val();
    if ($("#is_visible").prop("checked")) {
        var is_visible = 1;
    } else {
        var is_visible = 0;
    }

    if (show_type == 2) {
        var data_obj = $(".spec_data");
        var spec_value_str = '';
        data_obj.each(function (i) {
            if (data_obj.eq(i) != '') {
                var spec_value_name = $.trim(data_obj.eq(i).find("input[name='spec_value']").val());
                var spec_value_data = data_obj.eq(i).find("input[name='spec_value_data']").val();
                var new_str = '';
                new_str = spec_value_name + ':' + spec_value_data;
                spec_value_str = spec_value_str + ',' + new_str;
            }
        });
        spec_value_str = spec_value_str.substr(1);
    } else {
        var spec_value_obj = $("input[name='spec_value']");
        var spec_value_str = '';
        spec_value_obj.each(function (i) {
            if ($.trim(spec_value_obj.eq(i).val()) != '') {
                spec_value_str += ',' + $.trim(spec_value_obj.eq(i).val());
            }
        });
        spec_value_str = spec_value_str.substr(1);
    }
    if (spec_name == '') {
        message('规格名称不能为空！');
        return false;
    }
    if (spec_value_str == '') {
        message('属性名称不能为空！');
        return false;
    }
    if (flag) {
        return;
    }
    flag = true;
    $.ajax({
        type: "post",
        url: __URL(ADMINMAIN + '/goods/addgoodsspec'),
        data: {
            'spec_name': spec_name,
            'sort': sort,
            'is_visible': is_visible,
            'show_type': show_type,
            'spec_value_str': spec_value_str,
            'attr_id': attr_id
        },
        dataType: "json",
        success: function (data) {
            if (data["code"] > 0) {
                // layer.msg("添加成功", {icon: 1, time: 1000}, function () {
                //     getGoodsSpecListByAttrId(attr_id);
                //     $('#addModal').modal('hide');
                // });
                message('添加成功','success',function(){
                    getGoodsSpecListByAttrId(attr_id);
                    $('#addModal').modal('hide');
                })
            } else {
                // layer.msg('添加失败！', {icon: 2, time: 1000});
                message('添加失败！','danger');
                flag = false;
            }
        }
    });
}
/*
 * 添加属性
 */
function addGoodsAttr() {
    var attr_name = $.trim($("#attr_name").val());
    var attr_id = $('select[name="goodsType"]').val();
    var attr_sort = $("#attr_sort").val();
    if (attr_name == '') {
        message('属性名称不能为空！');
        return false;
    }
    $.ajax({
        type: "post",
        url: __URL(ADMINMAIN + '/goods/addattributeservicevalue'),
        data: {
            'attr_name': attr_name,
            'attr_sort': attr_sort,
            'attr_id': attr_id
        },
        dataType: "json",
        success: function (data) {
            if (data["code"] > 0) {
                // layer.msg("添加成功", {icon: 1, time: 1000}, function () {
                //     getGoodsSpecListByAttrId(attr_id);
                //     $('#addAttrValue').modal('hide');
                // });
                message('添加成功','success',function(){
                    getGoodsSpecListByAttrId(attr_id);
                    $('#addAttrValue').modal('hide');
                })
            } else {
                // layer.msg('添加失败！', {icon: 2, time: 1000});
                message('添加失败！','danger');
            }
        }
    });
}
//改变展示方式
function change_show_type(type) {
    if (type == 2) {
        $("input[name='spec_value']").after('&nbsp;<input type="color" style="width:60px;margin-top:5px;" name="spec_value_data" class="input-common" />');
    } else {
        $("input[name='spec_value']").next("input[type='color']").remove();
    }
}

//获取一级分类
function getCategoryOne() {
    $.ajax({
        type: 'post',
        url: __URL(ADMINMAIN + '/goods/getcategoryone'),
        data: {},
        success: function (data) {
            if (data.length > 0) {
                var html = '';
                for (var i = 0; i < data.length; i++) {
                    html += '<li class="js-category-one" category_id="' + data[i]['category_id'] + '">';
                    html += '<span>' + data[i]['category_name'] + '</span>';
                    if (data[i]['is_parent'] == 1) {
                        html += '<i class="fa fa-angle-right fa-lg"></i>';
                    }
                    html += '</li>';
                }
                $(".J-cateone").html(html);
                category();
            }
            return false;
        }
    });
}

// 侧边锚点
function sideAnchor() {
    $(window).ready(function () {
        $('.side-catalog a').on('click', function (e) {
            var top = $('.screen-title[data-id="' + $(this).data('id') + '"]').offset().top - 56;
            $('html').animate({scrollTop: top}, 300);
        });
    });
}
/**
 * 获取当前时间随机数
 * @returns
 */
function getDate() {
    var date = new Date();
    var time = date.getSeconds().toString() + date.getMilliseconds().toString();
    return time;
}
sideAnchor();



