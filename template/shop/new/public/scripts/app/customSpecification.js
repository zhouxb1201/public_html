define(["jquery", "linq", "layer", "dialog"], function ($, l, layer, dialog) {
    var util = {};
    util.init = function (sku_list, spec_list) {
        var list = [];
        for (var i = 0; i < sku_list.length; i++) {
            list.push(sku_list[i]);
        }
//    var Combination1 = {
//      sku_id: 1,
//      goods_id: 21,
//      attr_value_items: ",9,13,19,21,27,",
//      stock: 10,
//      price: 99
//    };
//    var Combination2 = {
//      sku_id: 2,
//      goods_id: 21,
//      attr_value_items: ",9,14,20,23,27,",
//      stock: 10,
//      price: 199
//    };
//    var Combination3 = {
//      sku_id: 3,
//      goods_id: 21,
//      attr_value_items: ",10,16,19,25,27,",
//      stock: 10,
//      price: 299
//    };
//    var Combination4 = {
//      sku_id: 4,
//      goods_id: 21,
//      attr_value_items: ",10,17,20,24,27,",
//      stock: 10,
//      price: 299
//    };
//    var Combination5 = {
//      sku_id: 5,
//      goods_id: 21,
//      attr_value_items: ",11,17,20,24,26,",
//      stock: 10,
//      price: 299
//    };
//    var Combination6 = {
//      sku_id: 6,
//      goods_id: 21,
//      attr_value_items: ",12,14,19,22,26,",
//      stock: 10,
//      price: 299
//    };
//    //list:来自数据查询出来的商品组合属性json数据
//    var list = [];
//    list.push(Combination1);
//    list.push(Combination2);
//    list.push(Combination3);
//    list.push(Combination4);
//    list.push(Combination5);
//    list.push(Combination6);
        //SKU_TYPET:来自数据库中商品属性json数据
        var SKU_TYPET = spec_list;
//    [
//      {
//        spec_id: 7,
//        spec_name: "颜色",
//        value: [
//          { spec_value_id: 9, spec_value_name: "金色" },
//          { spec_value_id: 10, spec_value_name: "黑色" },
//          { spec_value_id: 11, spec_value_name: "银色" },
//          { spec_value_id: 12, spec_value_name: "红色" }
//        ]
//      },
//      {
//        spec_id: 8,
//        spec_name: "版本",
//        value: [
//          { spec_value_id: 13, spec_value_name: "公开版" },
//          { spec_value_id: 14, spec_value_name: "原厂延保版" },
//          { spec_value_id: 15, spec_value_name: "双网通版" },
//          { spec_value_id: 16, spec_value_name: "无线充套装" },
//          { spec_value_id: 17, spec_value_name: "AirPods套装" },
//          { spec_value_id: 18, spec_value_name: "分期用版" }
//        ]
//      },
//      {
//        spec_id: 9,
//        spec_name: "内存",
//        value: [
//          { spec_value_id: 19, spec_value_name: "64G" },
//          { spec_value_id: 20, spec_value_name: "256G" }
//        ]
//      },
//      {
//        spec_id: 10,
//        spec_name: "套装",
//        value: [
//          { spec_value_id: 21, spec_value_name: "优惠套装1" },
//          { spec_value_id: 22, spec_value_name: "优惠套装2" },
//          { spec_value_id: 23, spec_value_name: "优惠套装3" },
//          { spec_value_id: 24, spec_value_name: "优惠套装4" },
//          { spec_value_id: 25, spec_value_name: "优惠套装5" }
//        ]
//      },
//      {
//        spec_id: 11,
//        spec_name: "图片",
//        value: [
//          { spec_value_id: 26, spec_value_name: "图片1",spec_value_data_src:'../../public/image/40x40.jpg' },
//          { spec_value_id: 27, spec_value_name: "图片2",spec_value_data_src:'../../public/image/40x40.jpg' },
//        ]
//      },
//    ];
        $(function () {
            var goods_id = $('#goods_id').val();
            var min_buy = parseInt($(".amount-input").attr("data-min"));
            function ishas(spec_value_ids) {
                var newlist = list.concat();
                var newspec_value_ids = spec_value_ids;
                for (var i = 0; i < newspec_value_ids.length; i++) {
                    newlist = Enumerable.From(newlist)
                            .Where(function (x) {
                                return x.attr_value_items.indexOf(newspec_value_ids[i]) > -1;
                            })
                            .ToArray();
                }
                if (newlist.length > 0) {
                    return true;
                } else {
                    return false;
                }
            }
            function trimSpace(array) {
                for (var i = 0; i < array.length; i++)
                {
                    if (array[i] == "" || typeof (array[i]) == "undefined")
                    {
                        array.splice(i, 1);
                        i = i - 1;

                    }
                }
                return array;
            }
            function getSku(select_spec) {
                var price = '';
                var market_price = '';
                var stock = '';
                var sku_id = 0;
                var sku_name = '';

                for (var i = 0; i < list.length; i++) {
                    var sku_arr = trimSpace(list[i]['attr_value_items'].split(','));

                    if (sku_arr.sort().toString() == select_spec.sort().toString()) {
                        price = list[i]['price'];
                        market_price = list[i]['market_price'];
                        stock = list[i]['stock'];
                        sku_id = list[i]['sku_id'];
                        sku_name = list[i]['sku_name'];
                        break;
                    }
                }
                if (!sku_id) {
                    return;
                }
                if (stock == 0) {
                    $(".js-buy-now").addClass("disabled");
                    $(".add-cart").addClass("disabled");
                    $('.action .btn-buy').addClass('disabled');
                } else {
                    //当最小购买数大于总库存时,不可购买
                    if (min_buy > stock) {
                        $(".js-buy-now").addClass("disabled");
                        $(".add-cart").addClass("disabled");
                        $('.action .btn-buy').addClass('disabled');
                    } else {
                        $(".js-buy-now").removeClass("disabled");
                        $(".add-cart").removeClass("disabled");
                        $('.action .btn-buy').removeClass('disabled');
                    }
                }
                $("#hidden_skuid").val(sku_id);
                $("#hidden_skuname").val(sku_name);
                $(".J-price").text("￥" + price);
                $(".J-marketprice").text("￥" + market_price);
                $("#hidden_sku_price").val(price);
                $(".js-goods-number").text("库存:" + stock + "件");
                if ($("#hidden_max_buy").val() == 0)
                {
                    $(".amount-input").attr("data-max", stock);
                } else {
                    $(".amount-input").attr("data-max", $("#hidden_max_buy").val());
                }
                //最小购买数为0时,购买数默认为1
                if (min_buy > 0) {
                    $(".amount-input").val(min_buy);
                } else {
                    $(".amount-input").val(1);
                }
            }
            init(SKU_TYPET);
            //init:绑定商品属性数据
            function init(SKU_TYPET) {
                var SKU_TYPE = "";
                $.each(SKU_TYPET, function (index, item) {
                    SKU_TYPE += '<ul class="SKU_TYPE fl"> <li class="sku_name" sku-type-name="' + item.spec_name + '">' + item.spec_name + "</li></ul>";
                    SKU_TYPE += "<ul>";
                    $.each(item.value, function (i, childitem) {
                        var spec_value_idsArry = [];
                        spec_value_idsArry.push("," + childitem.spec_value_id + ",");
                        if (item.show_type==2) {
                            if (!ishas(spec_value_idsArry)) {
                                SKU_TYPE += '<li class=" sku spec_value_name sku_pic disabled" data-spec_id="' + item.spec_id + '" data-spec_value_id="' + childitem.spec_value_id + '" style="background:' + childitem.spec_value_data + '"><label></label></li>';
                            } else {
                                SKU_TYPE += '<li class="sku spec_value_name sku_pic available" data-spec_id="' + item.spec_id + '" data-spec_value_id="' + childitem.spec_value_id + '" style="background:' + childitem.spec_value_data + '"><label></label></li>';
                            }
                        }
                       else if (item.show_type==3) {
                           if(childitem.spec_value_data){
                               if (!ishas(spec_value_idsArry)) {
                                    SKU_TYPE += '<li class=" sku spec_value_name sku_pic disabled" data-spec_id="' + item.spec_id + '" data-spec_value_id="' + childitem.spec_value_id + '" style="background-image:url(' + childitem.spec_value_data + ')"><label></label></li>';
                                } else {
                                    SKU_TYPE += '<li class="sku spec_value_name sku_pic available SkuPic" data-spec_id="' + item.spec_id + '" data-spec_value_id="' + childitem.spec_value_id + '" style="background-image:url(' + childitem.spec_value_data + ')" data-pic="' + childitem.spec_value_data_big_src + '"><label></label></li>';
                                }
                           }else{
                               if (!ishas(spec_value_idsArry)) {
                                    SKU_TYPE += '<li class="sku spec_value_name  disabled" data-spec_id="' + item.spec_id + '" data-spec_value_id="' + childitem.spec_value_id + '"><label>' + childitem.spec_value_name + "</label></li>";
                                } else {
                                    SKU_TYPE +='<li class="sku spec_value_name  available" data-spec_id="' + item.spec_id + '" data-spec_value_id="' + childitem.spec_value_id + '"><label>' + childitem.spec_value_name + "</label></li>";
                                }
                           }
                            
                        } else {
                            if (!ishas(spec_value_idsArry)) {
                                SKU_TYPE += '<li class="sku spec_value_name  disabled" data-spec_id="' + item.spec_id + '" data-spec_value_id="' + childitem.spec_value_id + '"><label>' + childitem.spec_value_name + "</label></li>";
                            } else {
                                SKU_TYPE +='<li class="sku spec_value_name  available" data-spec_id="' + item.spec_id + '" data-spec_value_id="' + childitem.spec_value_id + '"><label>' + childitem.spec_value_name + "</label></li>";
                            }
                        }

                    });
                    SKU_TYPE += "</ul>";
                    SKU_TYPE += '<div class="clear"></div>';
                });

                $(".customSpecification").html(SKU_TYPE);
                //11111111111111111111111111
				if($(".disabled")){ //判断disabled是否存在
					var aButeValueId = [],
						aButeId = [];
					$(".disabled").each(function(i,j){
						aButeId.push($(j).attr("data-spec_id"));
						aButeValueId.push($(j).attr("data-spec_value_id"));
						
					})
					localStorage.setItem("aButeId",JSON.stringify(aButeId));
					localStorage.setItem("aButeValueId",JSON.stringify(aButeValueId));
					
				}
                //11111111111111111111111111
            }
            // 点击规格图片，出现在商品图上
            $('body').on('click','.SkuPic',function(){
                if(!$(this).hasClass('choices')){
                    var pic=$(this).attr('data-pic');
                    $("#showbox b").find('img').attr("src", pic);
                    $("#showbox p").find('img').attr("src", pic);
                }
                if($(this).hasClass('choices')){
                    var pic=$("#showsum p").find(".sel").find('img').attr("src");
                    $("#showbox b").find('img').attr("src", pic);
                    $("#showbox p").find('img').attr("src", pic);
                }
            })
            //spec_name:已选择的商品属性集合[{ spec_id: 7, spec_value_id: 9 }]
            var spec_name = [];
            var select_spec = [];
            //取消已选择属性点击事件
            $("body").on("click", ".choices", function (event) {
                //11111111111111111111111111
				if($(this).hasClass("disabled")){
				   return false;
				}
                //11111111111111111111111111
                $(this).removeClass("choices");
                $(this).addClass("available");
                var spec_id = $(this).attr("data-spec_id");
                var spec_value_id = $(this).attr("data-spec_value_id");
                //从spec_name删除已选择属性
                var itemIndex = 0;
                var itemSku = 0;
                $.each(spec_name, function (index, item) {
                    if (item.spec_id == parseInt(spec_id)) {
                        itemIndex = index;
                    }
                });
                $.each(select_spec, function (index, item) {
                    if (item == parseInt(spec_value_id)) {
                        itemSku = index;
                    }
                });
                spec_name.splice(itemIndex, 1);
                select_spec.splice(itemSku, 1);
                //重新绑定
                $.each(SKU_TYPET, function (index, item) {
                    $.each(item.value, function (i, childitem) {
                        var newspec_value_ids = Enumerable.From(spec_name)
                                .Select(function (x) {
                                    return x.spec_value_id;
                                })
                                .ToArray();
                        var spec_value_idsArry = [];
                        $.each(spec_name, function (i, it) {
                            spec_value_idsArry.push("," + it.spec_value_id + ",");
                        });
                        spec_value_idsArry.push("," + childitem.spec_value_id + ",");
                        if (!ishas(spec_value_idsArry)) {
                            // $("[data-spec_value_id='" + childitem.spec_value_id + "']").addClass("disabled");
                            // $("[data-spec_value_id='" + childitem.spec_value_id + "']").removeClass("available");
							if(item.spec_id != spec_id){
                                // $("[data-spec_value_id='" + childitem.spec_value_id + "']").addClass("disabled");
                                // $("[data-spec_value_id='" + childitem.spec_value_id + "']").removeClass("available");
							}
                        } else {
                            $("[data-spec_value_id='" + childitem.spec_value_id + "']").removeClass("disabled");
                            if (!$("[data-spec_value_id='" + childitem.spec_value_id + "']").hasClass("choices")) {
                                $("[data-spec_value_id='" + childitem.spec_value_id + "']").addClass("available");
                            }
                        }
                    });
                });

                //11111111111111111111111111
				if($(".choices").length == 1){
					var num = [];//存储
					var cAttrId =  $(".choices").attr("data-spec_id"),
						cAttrValId =  $(".choices").attr("data-spec_value_id");
					var aButeValueId = JSON.parse(localStorage.getItem("aButeValueId")),
							 aButeId = JSON.parse(localStorage.getItem("aButeId"));
					$.each(aButeValueId,function(index,item){
						if(aButeId[index] == cAttrId){
							num.push(aButeValueId[index]);
							$(".choices").siblings("[data-spec_id='" + aButeId[index] + "']").removeClass("disabled").addClass("available");
						}
					})
					$.each(num,function(index,item){
						$("[data-spec_value_id='" + num[index] + "']").addClass("disabled").removeClass("available");
					})
					
				}
                //11111111111111111111111111

            });
            //选择属性点击事件
            $("body").on("click", ".available", function () {
                //11111111111111111111111111
				if($(this).hasClass("disabled")){
				   return false;
				}
                //11111111111111111111111111
                if($(this).siblings('li').hasClass('choices')){
                   var that = $(this).siblings('li.choices');
                   
                    var spec_id = that.attr("data-spec_id");
                    var spec_value_id = that.attr("data-spec_value_id");
                    //从spec_name删除已选择属性
                    var itemIndex = 0;
                    var itemSku = 0;
                    $.each(spec_name, function (index, item) {
                        if (item.spec_id == parseInt(spec_id)) {
                            itemIndex = index;
                        }
                    });
                    $.each(select_spec, function (index, item) {
                        if (item == parseInt(spec_value_id)) {
                            itemSku = index;
                        }
                    });
                    spec_name.splice(itemIndex, 1);
                    select_spec.splice(itemSku, 1);

                }
                var spec_id = $(this).attr("data-spec_id");
                var spec_value_id = $(this).attr("data-spec_value_id");
                //先判断spec_name是否存在该属性，
                if (Enumerable.From(spec_name).ToLookup("$.spec_id").Contains(parseInt(spec_id))) {
                    $.each(spec_name, function (index, item) {
                        //存在更新其值
                        if (item.spec_id == parseInt(spec_id)) {
                            item.spec_value_id = parseInt(spec_value_id);
                        }
                    });
                } else {
                    //不存在则添加
                    spec_name.push({
                        spec_id: parseInt(spec_id),
                        spec_value_id: parseInt(spec_value_id)
                    });
                    select_spec.push(parseInt(spec_value_id));
                }
                //循环每一项属性值并查询
                $.each(SKU_TYPET, function (index, item) {
                    $.each(item.value, function (i, childitem) {
                        var newspec_value_ids = Enumerable.From(spec_name).Select(
                                function (x) {
                                    return x.spec_value_id;
                                }).ToArray();
                        var spec_value_idsArry = [];
                        $.each(spec_name, function (i, it) {
                            spec_value_idsArry.push("," + it.spec_value_id + ",");
                        });
                        spec_value_idsArry.push("," + childitem.spec_value_id + ",");
                        if (!ishas(spec_value_idsArry)) {
                            // $("[data-spec_value_id='" + childitem.spec_value_id + "']").addClass("disabled");
                            // $("[data-spec_value_id='" + childitem.spec_value_id + "']").removeClass("available");
							//如果是同级就不用disabled
                            // 11111111111111111
							if(item.spec_id != spec_id){
								// $("[data-spec_value_id='" + childitem.spec_value_id + "']").addClass("disabled");
								// $("[data-spec_value_id='" + childitem.spec_value_id + "']").removeClass("available");
							}
                            // 11111111111111111
                        } else {
                            $("[data-spec_value_id='" + childitem.spec_value_id + "']").removeClass("disabled");
                            if (!$("[data-spec_value_id='" + childitem.spec_value_id + "']").hasClass("choices")) {
                                $("[data-spec_value_id='" + childitem.spec_value_id + "']").addClass("available");
                            }
                        }
                    });
                });
                // $(this).removeClass("available");
                // $(this).addClass("choices");
                //切换tab属性
                // 11111111111111111
				if(!$(this).hasClass("choices")){
				   $(this).removeClass("available").addClass("choices").siblings().removeClass("choices").addClass("available");
				}
                // 11111111111111111

                if (spec_name.length == SKU_TYPET.length) {
                    getSku(select_spec);
                }
            });
            $(".add-cart,.js-buy-now").click(function (event) {
                if ($(this).hasClass("disabled")) {
                    return;
                }
                if (spec_name.length != SKU_TYPET.length) {
                    layer.msg("请选择规格");
                    return;
                }
                if ($(".amount-input").val() < min_buy) {
                    layer.msg("少于最低购买量");
                    return;
                }
                var tag = $(this).attr("data-tag");
                var image_url = $("#showbox b").find("img").attr("src");
                dialog.addcart(goods_id, $(".amount-input").val(), {
                    is_sku: true,
                    image_url: image_url,
                    event: event,
                    tag: tag
                });
            });

        });
    };

    return util;
});
