$(function () {
    // 弹出框
    $(".trs").on("click", ".js-select-city", function () {
        showSelectAreaModal($(this));
    });

    //弹出框
    function showSelectAreaModal(e) {
        layer.open({
            type: 1,
            title: ["选择地区", "font-weight:bold"],
            skin: "layui-layer-rim", //加上边框
            area: ['710px', '420px'], //宽高
            content: $('#select-region'),
            btn: ["确定", "取消"],
            btnAlign: "c",
            // 点击确定的回调
            btn1: function (index, layero) {
                setProvinceIdArray(shipping_fee_area_id);
                setCityIdArray(shipping_fee_area_id);
                setDistrictIdArray(shipping_fee_area_id);
                var area_string = getAreas();
                if (area_string.length != 0) {
                    $('#' + shipping_fee_obj).find(".js-region-info").html(area_string);
                } else {
                    $('#shipping_fee_area_id_new_'+ (new_index - 1)).remove();
                }
                new_one = false;
            },
            //modal关闭的事件，包括确定，取消，x
            end: function(){
                if (!getAreas() && new_one){
                    $('#shipping_fee_area_id_new_'+ (new_index - 1)).remove();
                }
                new_one = false;
            },
            // 弹出框出现的回调
            success: function (layero, index) {
                layero.find(".layui-layer-btn").css("text-align", "center");
            }
        });
        window.shipping_fee_obj = e.parent().parent().attr('id');
        window.shipping_fee_area_id = e.parent().parent().attr('data-shipping-fee-area-id');
        if (pre_shipping_fee_area_id != shipping_fee_area_id) {
            clearAreaTreeAttribute();
            //addAreaTreeAttribute(shipping_fee_area_id);
            addAreaTreeAttribute(shipping_fee_area_id);
            pre_shipping_fee_area_id = shipping_fee_area_id;
        }
    }

    //设置弹出地区的位置和宽度
    $('#select-region').css({'top': '45px'});

    /**
     * 新加一个地区
     */
    $("#add-area").click(function () {
        var calculate_type = $("input[name=calculate_type]:checked").val();
        if (calculate_type == 2) {
            var step_html = "step='1'";
            var title = "请填写该项为正整数"
            var int_html = "int='true'";
        } else {
            var step_html = '';
            var title = "请填写该项";
        }
        var html = "<tr id='shipping_fee_area_id_new_" + new_index + "' data-shipping-fee-area-id='new_" + new_index + "'>" +
            "<td class='tdWidth'>" +
            "<span class='js-region-info'></span>" +
            "<a class='text-primary js-select-city' href='javascript:;'><i class='edit icon-edit'></i>编辑</a>" +
            "</td>" +
            "<td class='tdWidth'>" +
            "<input type='number' class='form-control number-form-control' "+ int_html + step_html + " name='main_level_num' placeholder='0' title='" + title + "' required/>" +
            "</td>" +
            "<td class='tdWidth'>" +
            "<input type='number' class='form-control number-form-control' name='main_level_fee' placeholder='0' title='首件运费不能为负数，并且保留两位小数'/>" +
            "</td>" +
            "<td class='tdWidth'>" +
            "<input type='number' class='form-control number-form-control' min='0' "+ int_html + step_html + " name='extra_level_num' placeholder='0' title='" + title + "' required/>" +
            "</td>" +
            "<td class='tdWidth'>" +
            "<input type='number' class='form-control number-form-control' name='per_extra_level_fee' placeholder='0' title='续件运费不能为负数，并且保留两位小数' required/>" +
            "</td>" +
            "<td>" +
            "<div class='text-danger delete-area'>删除</div>" +
            "</td>" +
            "</tr>";
        $(this).parents('tr').before(html);
        //console.log($(this).parent().parent().attr('id'));
        showSelectAreaModal($("#shipping_fee_area_id_new_" + new_index).find(".js-select-city"));
        new_index++;
        new_one = true;
    });

    //切换计费方式
    $("input[name=calculate_type]").change(function () {
        var calculate_type = $(this).val();
        switch (calculate_type) {
            case 1:
            case '1':
                $("#main_level_num").html('首重(kg)');
                $("#main_level_fee").html('首重运费(元)');
                $("#extra_level_num").html('续重(kg)');
                //$("#per_extra_level_fee").html('续费(元)');
                break;
            case 2:
            case '2':
                $("#main_level_num").html('首件(件)');
                $("#main_level_fee").html('首件运费(元)');
                $("#extra_level_num").html('续件(件)');
                //$("#per_extra_level_fee").html('续费(元)');
                break;
            case 3:
            case '3':
                $("#main_level_num").html('首体积(m³)');
                $("#main_level_fee").html('首体积运费(元)');
                $("#extra_level_num").html('续体积(m³)');
                //$("#per_extra_level_fee").html('续费(元)');
                break;
        }
    });

    $("tbody").on('click', '.delete-area', function () {
        var shipping_fee_area_id = $(this).parent().parent().attr('data-shipping-fee-area-id');
        //console.log(shipping_fee_detail['shipping_area']);
        $(this).parent().parent().remove();
        if (shipping_fee_detail['shipping_area'][shipping_fee_area_id]) {
            delete shipping_fee_detail['shipping_area'][shipping_fee_area_id];
            pre_shipping_fee_area_id = 0;//是为了让打开同样地区的modal可以重新渲染
        }
        //console.log(shipping_fee_detail['shipping_area']);
    })

    /**
     * 一级地区（大类）例如：华北、华东、东北、西北、港澳台等
     * 根据当前地区的选中状态对应的改变它的子地区
     */
    $("input[data-first-index]").change(function () {

        if (!$(this).is(":disabled") && !$(this).attr("data-is-disabled")) {

            var curr = $(this);//当前对象
            var area_id = curr.attr("data-area-id");//索引
            var checked = curr.is(":checked");//选中状态

            //省
            if ($("input[data-second-parent-index][data-area-id=" + area_id + "]").length) {
                $("input[data-second-parent-index][data-area-id=" + area_id + "]").each(function () {
                    if (!$(this).is(":disabled") && !$(this).attr("data-is-disabled")) {
                        $(this).prop("checked", checked);
                    }
                });

                //市
                if ($("input[data-third-parent-index][data-area-id=" + area_id + "]").length) {
                    $("input[data-third-parent-index][data-area-id=" + area_id + "]").each(function () {
                        if (!$(this).is(":disabled") && !$(this).attr("data-is-disabled")) {
                            $(this).prop("checked", checked);
                        }
                    });

                    //区县
                    if ($("input[data-four-parent-index][data-area-id=" + area_id + "]").length) {

                        $("input[data-four-parent-index][data-area-id=" + area_id + "]").each(function () {
                            if (!$(this).is(":disabled") && !$(this).attr("data-is-disabled")) {
                                $(this).prop("checked", checked);
                            }
                        });
                    }
                }
            }
        }
    });


    /**
     * 二级地区（省）例如：山西省、山东省、河北省等
     * 根据当前地区的选中状态对应的改变它的子地区
     */
    $("input[data-second-parent-index]").change(function () {

        var curr = $(this);//当前对象
        var checked = curr.is(":checked");//选中状态

        if (curr.parent().find("div input[type='checkbox']").length) {

            curr.parent().find("div input[type='checkbox']").each(function () {
                if (!$(this).is(":disabled") && !$(this).attr("data-is-disabled")) {
                    $(this).prop("checked", checked);
                }
            });
        }

    });


    /**
     * 三级地区（市区）例如：太原市、运城市等
     * 只要改变了三级地区那它的上一级为不选中状态
     */
    $("input[data-third-parent-index]").change(function () {

        var curr = $(this);//当前对象
        var checked = curr.is(":checked");//选中状态
        if (curr.parent().find("div input[type='checkbox']").length) {

            curr.parent().find("div input[type='checkbox']").each(function () {
                if (!$(this).is(":disabled") && !$(this).attr("data-is-disabled")) {
                    $(this).prop("checked", checked);
                }
            });

        }

        //一个没有选择，父级则不选中
        if (curr.parent().parent().children("span").children("input[type='checkbox']:checked").length == 0) {
            curr.parent().parent().parent().children("input").prop("checked", false);
        }
        //选中一个，父类则选中
        if (checked) curr.parent().parent().parent().children("input").prop("checked", true);
    });

    /**
     * 四级地区（区县）选择一个区县，父类（省市就选中）
     *
     */
    $("input[data-four-parent-index]").change(function () {
        var curr = $(this);
        var checked = curr.is(":checked");//选中状态
        var province_id = curr.attr("data-province-id");//父级，省id
        var city_id = curr.attr("data-city-id");//父级,市id

        //一个没有选择，父级city则不选中
        // if (curr.parent().parent().children("span").children("input[type='checkbox']:checked").length == 0) {
        //     curr.parent().parent().parent().children("input").prop("checked", false);
        // }
        if (curr.parent().siblings().children("input[type='checkbox']:checked").length === 0 && checked === false) {
            curr.parent().parent().parent().children("input").prop("checked", false);
        }

        //选中一个，父类则选中
        if (checked) {
            $("[data-second-parent-index][data-province-id='" + province_id + "']").prop("checked", true);
            $("[data-third-parent-index][data-city-id='" + city_id + "']").prop("checked", true);
        }
    });

    //判断是否显示 ok
    $(".drop-down").click(function () {
        var self = $(this);
        var is_visible = self.next().is(":visible");
        var level = $(this).attr("data-level");
        $(".drop-down[data-level='" + level + "']").parent().parent().removeClass("open");
        $(".drop-down[data-level='" + level + "']").next().hide();
        if (!is_visible) {
            self.parent().parent().addClass("open").attr("data-open", 1);
            self.next().show().attr("data-open-children", 1);
        } else {
            self.next().hide().removeAttr("data-open-children");
            self.parent().parent().removeClass("open").removeAttr("data-open");
        }

    });

    // var flag = false;
    // /**
    //  * 保存运费模板
    //  * edit_button为旧界面
    //  * btn-common为新界面
    //  */
    // $(".add").click(function () {
    //     if (flag) {
    //         return;
    //     }
    //     flag = true;
    //     buildData();
    //     //console.log(shipping_fee_detail);
    //     $.ajax({
    //         url: __URL(PLATFORMMAIN + "/express/freighttemplateedit"),
    //         type: "post",
    //         data: {"data": shipping_fee_detail},
    //         success: function (res) {
    //             if (parseInt(res.code)) {
    //                 layer.msg(res.message, {icon: 1, time: 1000}, function () {
    //                     window.location.href = __URL(PLATFORMMAIN + '/Express/freightTemplateList?co_id=' + $("#hidden_co_id").val());
    //                 })
    //             } else {
    //                 layer.msg(res.message, {icon: 2, time: 1000});
    //                 flag = false;
    //             }
    //         }
    //     });
    // });

});

var new_index = 1;
var pre_shipping_fee_area_id = 0;
var new_one = false;

//清除地区树的checked disabled属性
function clearAreaTreeAttribute() {
    $('#select-region').find("input[type=checkbox]").removeAttr('checked').attr('disabled', false)
}

//为地区树添加当前shipping_fee_area_id的属性
function addAreaTreeAttribute(shipping_fee_area_id) {

    var possible_disabled_city_id_array = {};
    $.each(shipping_fee_detail['shipping_area'], function (k_shipping_fee_area_id, v) {
        if (shipping_fee_area_id == k_shipping_fee_area_id) {
            //district,city,province checked true
            $.each(v.district_id_array, function (i, district_id) {
                $('#select-region').find("#district_" + district_id).prop('checked', true);
            })
            $.each(v.city_id_array, function (i, city_id) {
                $('#select-region').find("#city_" + city_id).prop('checked', true);
            })
            $.each(v.province_id_array, function (i, province_id) {
                $('#select-region').find("#province_" + province_id).prop('checked', true);
            })
        } else {
            //设置地区district的属性disabled和构建拥有disabled子节点的city
            if (v.district_id_array){
                //console.log(v.district_id_array);
                $.each(v.district_id_array, function (i, district_id) {
                    var district_obj = $('#select-region').find("#district_" + district_id);
                    district_obj.prop('disabled', true);
                    var city_id = district_obj.attr('data-city-id');
                    if (!possible_disabled_city_id_array[city_id]) {
                        possible_disabled_city_id_array[city_id] = []
                    }
                    possible_disabled_city_id_array[city_id].push(district_id);
                })
            }

        }
    })

    //设置地区city的属性disabled和构建拥有disabled子节点的province
    var possible_disabled_province_id_array = {};
    $.each(possible_disabled_city_id_array, function (city_id, district_list) {
        if (area_tree['district'][city_id]) {
            var array = Object.keys(area_tree['district'][city_id]);
            if (district_list.length === array.length) {
                var city_obj = $('#select-region').find("#city_" + city_id);
                city_obj.prop('disabled', true);
                var province_id = city_obj.attr('data-province-id');
                if (!possible_disabled_province_id_array[province_id]) {
                    possible_disabled_province_id_array[province_id] = [];
                }
                possible_disabled_province_id_array[province_id].push(city_id);
            }
        }

    })
    //设置地区province的属性disabled
    $.each(possible_disabled_province_id_array, function (province_id, city_list) {
        if (area_tree['city'][province_id]) {
            var array = Object.keys(area_tree['city'][province_id]);
            if (city_list.length === array.length) {
                $('#select-region').find("#province_" + province_id).prop('disabled', true);
            }
        }
    })
}


/**
 * 获取选中的地区（只显示省），逗号隔开
 */
function getAreas() {
    var regions_arr = new Array();

    if ($(".js-regions input[data-second-parent-index]:checked").length) {
        $(".js-regions input[data-second-parent-index]:checked").each(function () {
            regions_arr.push($(this).attr("data-province-name"));
        });
    }
    return regions_arr.toString();//.replace(",","&nbsp;,&nbsp;");
}

/**
 * 保存选中的省id组
 * @param id_arr 省id组
 */
function setProvinceIdArray(shipping_fee_area_id) {

    var id_arr = [];

    if ($(".js-regions input[data-second-parent-index]:checked").length) {
        $(".js-regions input[data-second-parent-index]:checked").each(function () {
            if (!$(this).is(":disabled") && !$(this).attr("data-is-disabled")) {
                id_arr.push($(this).val());
            }
        });
    }

    if (!shipping_fee_detail['shipping_area'][shipping_fee_area_id]) {
        shipping_fee_detail['shipping_area'][shipping_fee_area_id] = {};
    }
    if (shipping_fee_detail['shipping_area'][shipping_fee_area_id].length == 0) {
        shipping_fee_detail['shipping_area'][shipping_fee_area_id]['province_id_array'] = [];
    }
    shipping_fee_detail['shipping_area'][shipping_fee_area_id]['province_id_array'] = id_arr;
}

/**
 * 保存选中的市id组
 * @param id_arr
 */
function setCityIdArray(shipping_fee_area_id) {

    var id_arr = [];
    if ($(".js-regions input[data-third-parent-index]:checked").length) {
        $(".js-regions input[data-third-parent-index]:checked").each(function () {
            if (!$(this).is(":disabled") && !$(this).attr("data-is-disabled")) {
                id_arr.push($(this).val());
            }
        });
    }
    if (!shipping_fee_detail['shipping_area'][shipping_fee_area_id]) {
        shipping_fee_detail['shipping_area'][shipping_fee_area_id] = {};
    }
    if (shipping_fee_detail['shipping_area'][shipping_fee_area_id].length == 0) {
        shipping_fee_detail['shipping_area'][shipping_fee_area_id]['city_id_array'] = [];
    }
    shipping_fee_detail['shipping_area'][shipping_fee_area_id]['city_id_array'] = id_arr;
}

/**
 * 保存选中的区县id组
 * @param id_arr
 */
function setDistrictIdArray(shipping_fee_area_id) {

    var id_arr = [];
    if ($(".js-regions input[data-four-parent-index]:checked").length) {
        $(".js-regions input[data-four-parent-index]:checked").each(function () {
            if (!$(this).is(":disabled") && !$(this).attr("data-is-disabled")) {
                id_arr.push($(this).val());
            }
        });
    }

    if (!shipping_fee_detail['shipping_area'][shipping_fee_area_id]) {
        shipping_fee_detail['shipping_area'][shipping_fee_area_id] = {};
    }

    if (shipping_fee_detail['shipping_area'][shipping_fee_area_id].length == 0) {
        shipping_fee_detail['shipping_area'][shipping_fee_area_id]['district_id_array'] = [];
    }
    shipping_fee_detail['shipping_area'][shipping_fee_area_id]['district_id_array'] = id_arr;
}

function buildData() {
    shipping_fee_detail['shipping_fee_name'] = $("#shipping_fee_name").val();
    shipping_fee_detail['calculate_type'] = $("input[name=calculate_type]:checked").val();
    shipping_fee_detail['is_default'] =  $("input[name=is_default]").length ? ($("input[name=is_default]:checked").val() ? 1 : 0) : 1;
    shipping_fee_detail['is_enabled'] = $("input[name=is_enabled]:checked").val();
    shipping_fee_detail['co_id'] = $("select[name=co_id]").val();
    $("tr[id^=shipping_fee_area_id_]").each(function () {
        var curr_shipping_fee_area_id = $(this).attr('data-shipping-fee-area-id');
        var shipping_fee_area_is_default = $(this).attr('data-is-default') || 0;
        var main_level = $(this).find("input[name=main_level_num]").val()
        var main_level_fee = $(this).find("input[name=main_level_fee]").val()
        var extra_level_num = $(this).find("input[name=extra_level_num]").val()
        var per_extra_level_fee = $(this).find("input[name=per_extra_level_fee]").val()
        if (shipping_fee_detail['shipping_area'][curr_shipping_fee_area_id]) {
            if (shipping_fee_detail['calculate_type'] == 2) {
                if (main_level % 1 != 0) {
                    legal = false
                }
                if (main_level_fee % 1 != 0) {
                    legal = false
                }
            }
            if (main_level == '' || main_level_fee == '' || extra_level_num == '' || per_extra_level_fee == '') {
                legal = false
            }
            if (main_level <= 0 || main_level_fee < 0 || extra_level_num <= 0 || per_extra_level_fee < 0) {
                legal = false
            }
            shipping_fee_detail['shipping_area'][curr_shipping_fee_area_id]['main_level_num'] = main_level;
            shipping_fee_detail['shipping_area'][curr_shipping_fee_area_id]['main_level_fee'] = main_level_fee;
            shipping_fee_detail['shipping_area'][curr_shipping_fee_area_id]['extra_level_num'] = extra_level_num;
            shipping_fee_detail['shipping_area'][curr_shipping_fee_area_id]['per_extra_level_fee'] = per_extra_level_fee;
            shipping_fee_detail['shipping_area'][curr_shipping_fee_area_id]['is_default_area'] = shipping_fee_area_is_default;
        } else {
            //请选择地区
            //showTip('非默认地区请选择地区', "warning");
        }

    })
}