$(function () {
    LoadingInfo(1);

    $("#list").on('click', '.disabled-company', function () {
        $.ajax({
            type: "POST",
            url: __URL(ADMINMAIN + "/express/setUnused"),
            data: {
                "co_id": $(this).attr('data-co-id')
            },
            success: function (data) {
                if (data['code'] > 0) {
                    layer.msg(data['message'], {icon: 1, time: 1000}, LoadingInfo($("#page_index").val()));
                } else {
                    layer.msg(data['message'], {icon: 2, time: 1000});
                }
            }
        })
    })

    $("#list").on('click', '.able-company', function () {
        $.ajax({
            type: "POST",
            url: __URL(ADMINMAIN + "/express/setUse"),
            data: {
                "co_id": $(this).attr('data-co-id')
            },
            success: function (data) {
                if (data['code'] > 0) {
                    layer.msg(data['message'], {icon: 1, time: 1000}, LoadingInfo($("#page_index").val()));
                } else {
                    layer.msg(data['message'], {icon: 2, time: 1000});
                }
            }
        })
    })
})
var shop_id = $("#shop_id").val();
var website_id = $("#website_id").val()

function LoadingInfo(page_index) {
    var search_text = $("#search_text").val();
    $("#page_index").val(page_index);
    $.ajax({
        type: "post",
        url: __URL(ADMINMAIN + "/express/expresscompany"),
        data: {
            "page_index": page_index,
            "page_size": $("#showNumber").val(),
            "search_text": search_text
        },
        success: function (data) {
            var html = '';

            //     <th>物流公司</th>
            //     <th>物流编号</th>
            //     <th>是否启用</th>
            //     <th>操作</th>
            if (data["data"].length > 0) {
                for (var i = 0; i < data["data"].length; i++) {
                    var curr = data['data'][i];
                    html += '<tr>';
                    html += '<td>' + curr.company_name + '</td>';
                    html += '<td>' + curr.express_no + '</td>';
                    // html += '<td>' + curr.phone + '</td>';
                    // if (curr.is_default == 1) {
                    //     html += '<td>是</td>';
                    // } else {
                    //     html += '<td>否</td>';
                    // }
                    // html += '<td>' + curr.orders + '</td>';
                    if (curr.shop_id == shop_id) {
                        html += '<td>是</td>';
                    } else {
                        html += '<td>否</td>';
                    }

                    html += '<td>';
                    // html += '<a href="' + __URL(ADMINMAIN + '/express/expresstemplate?co_id=' + data["data"][i]["co_id"]) + '">打印模板</a>&nbsp;&nbsp;';
                    // html += '<a href="' + __URL(ADMINMAIN + '/express/updateexpresscompany?co_id=' + data["data"][i]["co_id"]) + '">修改</a><br/>';
                    if (curr.shop_id == shop_id && curr.website_id == website_id) {
                        html += '<a class="text-primary disabled-company" href="javascript:;"data-co-id="' + curr.co_id + '">禁用</a> ';
                        html += '<a class="text-primary" href="' + __URL(ADMINMAIN + '/express/freighttemplatelist?co_id=' + curr.co_id) + '">运费模板</a> ';
                    } else {
                        html += '<a class="text-primary able-company" href="javascript:;" data-co-id="'+ curr.co_id+'">启用</a> ';
                    }
                    html += '</tr>';
                }
            } else {
                html += '<tr align="center"><td colspan="8">暂无符合条件的数据记录</td></tr>';
            }
            $("#list").html(html);
            page(".M-box3", data['total_count'], data["page_count"], page_index, LoadingInfo);
        }
    });
}

//全选
function CheckAll(event) {
    var checked = event.checked;
    $(".style0list tbody input[type = 'checkbox']").prop("checked", checked);
}