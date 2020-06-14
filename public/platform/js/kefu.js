require(['util'],function(util) {

$(function(){
	add_help_search();

    function add_help_search(){
        var html_CS = "<div class='lligent-CS rb' ><div class='lligent-wrp' id='lligent'><img src='/public/platform/static/images/znkf/lligent.png' alt='智能客服'><div class='CS-tips'>我是智能客服<br>点我进行咨询吧！</div></div></div>";
        	$("body").append(html_CS);
    }
    function oSend(){
        
        var ft = "/public/platform/static/images/znkf/lligent-avater.png";    //客服头像
        var fn = "智能客服";    //客服名称
        var ut = "/public/platform/static/images/znkf/user-avater.png";    //用户头像
        var i = $('.CS-input').val();   //用户输入内容
        if(null != i && "" != i && i.trim()!=''){
    		var html = kefu_do_search(i);
            var u = "<div class='dialog dialog-user'><div class='avater'><img src='"+ut+"'></div><div class='info'><p>"+i+"</p></div></div>";   //用户
            var r = "<div class='dialog dialog-robot fadeInUp'><div class='avater'><img src='"+ft+"'><span class='name'>"+fn+"</span></div><div class='info'><p>"+html+"</p></div></div>";
            // var t = "<div class='manual-tips'>没有解决您的问题？你可以联系<a href='' target='_blank'>人工客服</a></div>";
        	($(".dialog-list").append(u+r), $(".CS-content").scrollTop($(".dialog-list").height()), $(".CS-input").val(""));
        }else{
        	util.message("关键字不能为空！");
        }
    }
    
    function getDialog(){
        var html_dialog = "<div class='CS-wrp' id='wrp'>";
            html_dialog += "<div class='CS-container'>";
            html_dialog += "<div class='CS-header'>";
            html_dialog += "<div class='robot-logo'>";
            html_dialog += "<img src='/public/platform/static/images/znkf/lligent-avater.png'>";
            html_dialog += "</div>";
            html_dialog += "<div class='robot-title'>";
            html_dialog += "<p>亲，您好！我是智能客服。有问题，请问我~</p>";
            html_dialog += "</div>";
            html_dialog += "<div class='robot-close'></div>";
            html_dialog += "</div>";
            html_dialog += "<div class='CS-content'>";
            html_dialog += "<div class='dialog-list'>";
            html_dialog += "<div class='dialog dialog-robot'>";
            html_dialog += "<div class='avater'>";
            html_dialog += "<img src='/public/platform/static/images/znkf/lligent-avater.png' >";
            html_dialog += "<span class='name'>智能客服</span>";
            html_dialog += "</div>";
            html_dialog += "<div class='info'>";
            html_dialog += "亲，我是智能客服，您可以输入问题向我提问。只输入关键字就好了哦~~";
            html_dialog += "</div>";
            html_dialog += "</div>";
            html_dialog += "</div>";
            html_dialog += "</div>";
            html_dialog += "<div class='CS-footer'>";
            html_dialog += "<div class='CS-input-wrp'>";
            html_dialog += "<textarea class='CS-input' maxlength='80' placeholder='亲，请在这里输入关键词，我会快速回答您哒~~'></textarea>";
            html_dialog += "</div>";
            html_dialog += "<div class='CS-send'>";
            html_dialog += "<button class='send-btn' id='send_to_bbs' >发送</button>";
            html_dialog += "</div>";
            html_dialog += "</div>";
            html_dialog += "</div>";
            html_dialog += "</div>";

            $(".lligent-wrp").append(html_dialog);
            $('.CS-input').keydown(function(e){
                var b = document.all ? window.event : e;
                
                return 13 == b.keyCode ? (oSend(), !1) : void 0;
            });  
            $('#send_to_bbs').click(oSend);
            $('.robot-close').click(function(){
                $('.CS-wrp').fadeOut()
            });
            
            $('.CS-header').mousedown(function(ev) {

                var oevent = ev || event;
                oevent.preventDefault();
                oevent.stopPropagation();
                var than = document.getElementById("lligent");
                　　　　
                var distanceX = oevent.clientX - than.offsetLeft;　　　　
                var distanceY = oevent.clientY - than.offsetTop;
                
                document.onmousemove = function(ev) {　　　　　　
                    var oevent = ev || event;
                    // $('.lligent-CS').removeClass('rb')
                    than.style.left = oevent.clientX - distanceX + 'px';
                    than.style.top = oevent.clientY - distanceY + 'px';　
                   　
                };
                document.onmouseup = function() {　　　　　　
                    document.onmousemove = null;　　　　　　
                    document.onmouseup = null;　　　　
                };
            })
            
    }

    
    
	$('.lligent-CS img').mousedown(function(ev) {
        var oevent = ev || event;
        oevent.preventDefault();
        var t = $(this);
        var than = document.getElementById("lligent");
        　　　　
        var distanceX = oevent.clientX - than.offsetLeft;　　　　
        var distanceY = oevent.clientY - than.offsetTop;
        var ismove;
        var tLeft=than.style.left
        document.onmousemove = function(ev) {　　　　　　
            var oevent = ev || event;
            // t.removeClass('rb')
            than.style.left = oevent.clientX - distanceX + 'px';
            than.style.top = oevent.clientY - distanceY + 'px';　　
          
        };
        document.onmouseup = function(e) {　　　　　　
            document.onmousemove = null;　　　　　　
            document.onmouseup = null;　

            if(tLeft == than.style.left){
                $('.CS-wrp').length < 1 ? getDialog() : $('.CS-wrp').fadeIn()
            }
        };
    })

	var times_ai = "a";
	function kefu_do_search(srchtxt){
		var protocolStr = document.location.protocol;
		if(protocolStr == "https:"){
			protocolStr = "https://";
		}else{
			protocolStr = "http://";
		}
		var html = "";
        var t = "<div class='manual-tips'>没有解决您的问题？你可以联系<a href='http://pkt.zoosnet.net/LR/Chatpre.aspx?id=PKT84941002&lng=cn' target='_blank'>人工客服</a></div>";
		$.ajax({
		type : "post",
		data : {external_search_keyword:srchtxt},
		url : protocolStr + "bbs.lingkee.cn/search.php?mod=forum&external_search=1",
		dataType : "json",
		async:false,
		success : function(res){
    		if(res.count==0){
				if(times_ai == "a"){
					html = "亲，没有找到您问题的答案哦，请换一个关键词吧~"+t;
					times_ai = "b";
				}
				else{
					html = "亲，还是没有找到问题的答案哦~~<br>去提问专区把问题告诉我们吧，会有专人回复哦~~<a href='http://bbs.vslai.com.cn/' target='_blank' >我要去提问>></a>"+t;
				}
                
    		}else{
    			var data = res.data;
                html = '亲，您是不是要找：<br>';
    			for (var ii = 0; ii < res.count; ii++){
    				var iii = ii+1;

    				html += "<a href='"+data[ii].url+"' target='view_window' >"+iii+"、"+data[ii].subject+"</a><br>" ;

    			}
                if( data.length <= 3 ){
                    html += t;
                }
    		}
    			
    		},
    		error:function(){
    			html = "维护中。。。"
    		}
    	});
	
        return html;
		
	}

    
});

})