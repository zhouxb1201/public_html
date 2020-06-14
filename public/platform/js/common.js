
 function trDel(callback) {
    $(".trs").on("click", ".del", function(e) {
      var that = $(this);
      layer.confirm(
        "确认要删除吗？",
        {
          btn: ["确定", "取消"],
          title: "系统提示"
        },
        function() {
          time: 500,
            layer.msg("删除成功", {
              time: 500,
              offset: ["50%", "50%"]
            }),
            e.preventDefault();
          that
            .parent()
            .parent()
            .remove();
          callback && callback();
        }
      );
    });
  }
  //时间戳转时间类型
function timeStampTurnTime(timeStamp){
	if(timeStamp > 0){
		var date = new Date();  
		date.setTime(timeStamp * 1000);  
		var y = date.getFullYear();      
		var m = date.getMonth() + 1;      
		m = m < 10 ? ('0' + m) : m;      
		var d = date.getDate();      
		d = d < 10 ? ('0' + d) : d;      
		var h = date.getHours();    
		h = h < 10 ? ('0' + h) : h;    
		var minute = date.getMinutes();    
		var second = date.getSeconds();    
		minute = minute < 10 ? ('0' + minute) : minute;      
		second = second < 10 ? ('0' + second) : second;     
		return y + '-' + m + '-' + d+' '+h+':'+minute+':'+second;  		
	}else{
		return "";
	}
	    
	//return new Date(parseInt(time_stamp) * 1000).toLocaleString().replace(/年|月/g, "/").replace(/日/g, " ");
}

//函数名：CheckDateTime
//功能介绍：检查是否为日期时间
function CheckDateTime(str){
	var reg = /^(\d+)-(\d{1,2})-(\d{1,2}) (\d{1,2}):(\d{1,2}):(\d{1,2})$/;
	var r = str.match(reg);
	if(r==null)return false;
	r[2]=r[2]-1;
	var d= new Date(r[1], r[2],r[3], r[4],r[5], r[6]);
	if(d.getFullYear()!=r[1]) return false;
	if(d.getMonth()!=r[2]) return false;
	if(d.getDate()!=r[3]) return false;
	if(d.getHours()!=r[4]) return false;
	if(d.getMinutes()!=r[5]) return false;
	if(d.getSeconds()!=r[6]) return false;
	return true;
}

function submitPassword() {
	var pwd0 = $("#pwd0").val();
	var pwd1 = $("#pwd1").val();
	var pwd2 = $("#pwd2").val();
	if (pwd0 == '') {
		$("#pwd0").focus();
		$("#pwd0").siblings("span").html("原密码不能为空");
		return;
	} else {
		$("#pwd0").siblings("span").html("");
	}

	if (pwd1 == '') {
		$("#pwd1").focus();
		$("#pwd1").siblings("span").html("密码不能为空");
		return;
	} else if ($("#pwd1").val().length < 6) {
		$("#pwd1").focus();
		$("#pwd1").siblings("span").html("密码不能少于6位数");
		return;
	} else {
		$("#pwd1").siblings("span").html("");
	}
	if (pwd2 == '') {
		$("#pwd2").focus();
		$("#pwd2").siblings("span").html("密码不能为空");
		return;
	} else if ($("#pwd2").val().length < 6) {
		$("#pwd2").focus();
		$("#pwd2").siblings("span").html("密码不能少于6位数");
		return;
	} else {
		$("#pwd2").siblings("span").html("");
	}
	if (pwd1 != pwd2) {
		$("#pwd2").focus();
		$("#pwd2").siblings("span").html("两次密码输入不一样，请重新输入");
		return;
	}
	$.ajax({
		url : __URL(PASSMAIN+"/index/modifypassword"),
		type : "post",
		data : {
			"old_pass" : $("#pwd0").val(),
			"new_pass" : $("#pwd1").val()
		},
		dataType : "json",
		success : function(data) {
			if (data['code'] > 0) {
                            layer.msg("密码修改成功！", {time: 1000}, function () {
                                location.href= __URL(PASSMAIN+"/login/logout");
                            });
			} else {
                            layer.msg(data['message'], {time: 1000});
                            return false;
			}
		}
	});
}

function page(select,totalData,pageCount,current,callbacks){
    $(select).pagination({
        totalData:totalData,
        pageCount: pageCount,
        current:current,
        jump: true,
        coping: true,
        homePage: "首页",
        endPage: "末页",
        prevContent: "上页",
        nextContent: "下页",
        callback: function (api) {
            callbacks && callbacks(api.getCurrent());
        }
    });
}
function CheckInputIntFloat(oInput)
{
    if('' != oInput.value.replace(/\d{1,}\.{0,1}\d{0,}/,''))
    {
        oInput.value = oInput.value.match(/\d{1,}\.{0,1}\d{0,}/) == null ? '' :oInput.value.match(/\d{1,}\.{0,1}\d{0,}/);
    }
}
//判断是否为数字（整数、小数）
function IsNum(obj) {
    var val = $.trim($(obj).val());
    var r = /^\d+(\.\d+|\d*)$/g;
    if (r.test(val) == false) {
        return false;
    }
    return true;
}
//判断是否为正整数
function IsPositiveNum(obj) {
    var val = $.trim($(obj).val());
    var r = /^[0-9]*[1-9][0-9]*$/ ;
    if (r.test(val) == false) {
        return false;
    }
    return true;
}
 //取最大值
 Array.max = function(array ){
     return Math.max.apply(Math,array);
 };
 //取最小值
 Array.min = function(array){
     return Math.min.apply(Math,array);
 };