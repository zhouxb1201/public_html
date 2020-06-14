$(function () {
    onloadGoodsCount();//商品数量
    onloadOrderCount();//订单数量
    onloadWithdrawCount();
    getOrderMovementCharts();
    getGoodsList(-1);
    getQuickList();
    getTransactionStatus(1);
    $('.times').on('click',function(){
        getGoodsList(7);
    })
    $('.timestatus').on('click',function(){
        getTransactionStatus(7);
    })
    $('.times_today').on('click',function(){
        getGoodsList(1);
    })
    $('.times_yestoday').on('click',function(){
        getGoodsList(-1);
    })
    $('.timestatus_today').on('click',function(){
        getTransactionStatus(1);
    })
    var type_module =  $("#entry_ids").val();
    if(type_module){
        var type_module_array = type_module.split(',');
        for (var i = 0; i < type_module_array.length; i++) {
            $('input[type="checkbox"][value="' + type_module_array[i] + '"]').prop("checked", true);
        }
    }
    ToadaySevenToggle();
});

function onloadGoodsCount() {
    $.ajax({
        type: "post",
        url: __URL(ADMINMAIN + "/index/getgoodscount"),
        success: function (data) {
            everyTimePut("#selling_goods_amount", parseInt(data['sale']));
            everyTimePut("#warehouse_goods_amount", parseInt(data['store']));
            everyTimePut("#warehouse_alert", parseInt(data['warning']));
        }
    });
}

function onloadOrderCount() {
    $.ajax({
        type: "post",
        url: __URL(ADMINMAIN + "/index/getordercount"),
        success: function (data) {
            everyTimePut("#willSend_order_amount", data['daifahuo']);
            everyTimePut("#sold_order_amount", data['tuikuanzhong']);
        }
    });
}
function onloadWithdrawCount() {
    $.ajax({
        type: "post",
        url: __URL(ADMINMAIN + "/index/getWithdrawCount"),
        success: function (data) {
            everyTimePut("#deal_order_amount", data['withdraw']);
        }
    });
}
function getOrderMovementCharts() {
    $.ajax({
        type: "post",
        url: __URL(ADMINMAIN + '/index/getordermovementschartcount'),
        async: true,
        success: function (datas) {
            var data = eval(datas);
            //基于准备好的dom，初始化echarts实例
            var myChart = echarts.init(document.getElementById('website_create'),'walden');

            // 指定图表的配置项和数据
            var option = {
                title: {
                    subtext:'近7日自营订单走势',
                    left: 'center',
                    top:'20'
                },
                // tooltip: {
                //     trigger: 'item',
                //     formatter: '{a} <br/>{b} : {c}'
                // },
                  tooltip: {
                      trigger: 'axis'
                  },
                legend: {
                    left: 'center',
                    // top: '30px',
                    data: data.ordertype,
                },
                xAxis: {
                    type: 'category',
                    data: data.day,
                    boundaryGap: false,
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true
                },
                yAxis: {
                    type: 'value',
                    name:'数量',
                },
                series: data.all
            };

            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);
            window.onresize = myChart.resize;
        }
    });
}
$('body').on('click','.save', function () {
        var select_box='';
        $("input[name='module']:checked").each(function(){
            select_box = select_box+','+$(this).val();
        });
        var length = $("input[name='module']:checked").length;
        if(length>5){
            message('快捷入口最多设置5个');
            return false;
        }
        select_box = select_box.substring(1);
        $.ajax({
            type:"post",
            url : __URL(ADMINMAIN+'/index/setQuick'),
            async : true,
            data : {
                "entry_ids":select_box,
            },
            success : function(data) {
                if (data["code"] > 0) {
                    message(data["message"],'success');
                    $("#sets").modal('hide');
                    getQuickList();
                }else{
                    message(data["message"],'danger');
                }
            }
        });

})
function getGoodsList(time){
    $.ajax({
        type:"post",
        url : __URL(ADMINMAIN+'/index/goodsAnalysis'),
        async : true,
        data :{'times':time},
        success : function(data) {
            var html = '';
            if (data['data']['data'].length > 6) {
                for (var g = 0; g < data["data"].length; g++) {
                    if(g<=6){
                        html +='<tr>';
                        html +='<td>'+(g+1)+'</td>';
                        html +='<td>';
                        html +='<div class="line1-1">'+data["data"]['data'][g]["goods_name"]+'</div>';
                        html +='</td>';
                        html +='<td> '+ data["data"]['data'][g]['sumCount'] +' </td>';
                        html +='<td class="orange">'+ data["data"]['data'][g]['sumMoney'] +'</td>';
                        html +='</tr>';
                    }
                }
            }else if( data['data']['data'].length < 6 && data['data']['data'].length > 0){
                for (var i = 0; i < data["data"]['data'].length; i++) {
                    html += '<tr>';
                    html += '<td>' + (i + 1) + '</td>';
                    html += '<td>';
                    html += '<div class="line1-1">' + data["data"]['data'][i]["goods_name"] + '</div>';
                    html += '</td>';
                    html += '<td> ' + data["data"]['data'][i]['sumCount'] + ' </td>';
                    html += '<td class="orange">' + data["data"]['data'][i]['sumMoney'] + '</td>';
                    html += '</tr>';
                }
                var real_length = 8 - data['data']['data'].length;
                for (var j = 1; j < data["data1"]['data'].length; j++) {
                    if(j<real_length){
                        html += '<tr>';
                        html += '<td>' + (j + 1) + '</td>';
                        html += '<td>';
                        html += '<div class="line1-1">' + data["data1"]['data'][j]["goods_name"] + '</div>';
                        html += '</td>';
                        html += '<td> 0 </td>';
                        html += '<td> 0 </td>';
                        html += '</tr>';
                    }
                }
            }else if(data["data"]['data'].length==0 && data["data1"]['data'].length>0){
                for (var k = 0; k < data["data1"]['data'].length; k++) {
                    if(k<=6){
                        html += '<tr>';
                        html += '<td>' + (k + 1) + '</td>';
                        html += '<td>';
                        html += '<div class="line1-1">' + data["data1"]['data'][k]["goods_name"] + '</div>';
                        html += '</td>';
                        html += '<td> 0 </td>';
                        html += '<td> 0 </td>';
                        html += '</tr>';
                    }
                }
            }else{
                html += '<tr><th colspan="4">暂无商品售出</th></tr>';
            }
            $("#goods_list").html(html);
        }
    });
}
function getQuickList() {
    $.ajax({
        type:"get",
        url : __URL(ADMINMAIN+'/index/getQuickList'),
        async : true,
        success : function(data) {
            var html = '';
            if (data.length > 0) {
                for (var i = 0; i < data.length; i++) {
                    var url =  __URL(ADMINMAIN +'/'+ data[i].url);
                    html += '<a href="'+url+'" class="text-primary mr-20">'+data[i].module_name+'</a>';
                }
            } else {
                html += '暂无快捷入口';
            }
            $("#quick_set").html(html);
        }
    });
}
function getTransactionStatus(time) {
    $.ajax({
        type:"post",
        url : __URL(ADMINMAIN+'/index/getTransactionStatus'),
        async : true,
        data :{'times':time},
        success : function(data) {
            $("#vistor").html(data['visitor_num']);
            $("#visitor_num").html(data['visitor_num']);
            $("#order_num").html(data['order_num']);
            $("#order_money").html(data['order_money']);
            $("#pay_num").html(data['pay_num']);
            $("#pay_money").html(data['pay_money']);
            $("#unit_price").html(data['unit_price']);
            $("#order_conversion").html(data['order_conversion']);
            $("#orderpay_conversion").html(data['orderpay_conversion']);
            $("#pay_conversion").html(data['pay_conversion']);
        }
    });
}
//定时器
function everyTimePut(tag_name, num) {
    if (num > 0) {
        var number = 1;
        $('body').everyTime('0.01s', 'B', function () {
            $(tag_name).text(number);
            number++;
            if (number > 100) {
                $(tag_name).text(num);
                return false;
            }
        }, parseInt(num));
    } else {
        $(tag_name).text(0);
    }
}

// 首页'今日七日'类名的切换
function ToadaySevenToggle(){
    $(".panel-heading").on("click",".sort-tab",function(){
        $(this).addClass("active").siblings(".sort-tab").removeClass("active");
    })
}