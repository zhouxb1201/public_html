define(['jquery','bootstrap','jconfirm'],function($){

    var util = {};

    /**
     * date日期选择
     * @param    {
     *      element   :  元素id string
     *      isSingle  :  是否为单个日历 默认true 单
     * }
     * @callback
     */
    util.date = function(element,obj,callback){
        require(['daterangepicker'],function(){
            element && element !== undefined ? element : element = '.date-input-group .form-control';
            obj && obj !== undefined ? obj : obj = {};
            minDate = $(element).data('apply_start_date');
            maxDate = $(element).data('apply_end_date');
            minDate && minDate !== undefined ? minDate :'';
            maxDate && maxDate !== undefined ? maxDate :'';

            //定义locale汉化插件
            var locale = {
                "format": 'YYYY-MM-DD',
                "separator": "/",
                "applyLabel": "确定",
                "cancelLabel": "取消",
                "fromLabel": "起始时间",
                "toLabel": "结束时间'",
                "customRangeLabel": "自定义",
                "weekLabel": "W",
                "daysOfWeek": ["日", "一", "二", "三", "四", "五", "六"],
                "monthNames": ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                "firstDay": 1,
            };
            var param = {
                'locale':locale,
                "minDate": minDate,
                "maxDate": maxDate,
                autoUpdateInput:false,
                singleDatePicker:obj.single !== undefined ? obj.single : true,
                "opens": obj.opens ? obj.opens : obj.opens = 'right',
                "timePicker": obj.timePicker ? obj.timePicker : obj.timePicker = false,
                "timePicker24Hour": obj.timePicker24Hour ? obj.timePicker24Hour : obj.timePicker24Hour = false,
                "timePickerSeconds": obj.timePicker24Hour ? obj.timePicker24Hour : obj.timePicker24Hour = false,
            }
            // console.log(param)
            $(element).daterangepicker(param,callback)
        })
    }

    /**
     * 上传文件
     * @DateTime 2018-07-07
     *
     */
    util.fileupload = function(url,dataAlbum,file_url){
        require(['fileupload'],function(){
            var options = {
                url: url,                    //上传地址
                autoUpload: true,                               //是否自动上传
                acceptFileTypes: /(.|\/)(jpe?g|png)$/i,         //文件格式限制
                maxNumberOfFiles: 1,                            //最大上传文件数目
                maxFileSize: 5000000,                           //文件不超过5M
                sequentialUploads: true,                        //是否队列上传
                dataType: 'json',//从服务器返回数据json类型
                formData: dataAlbum,
            };
            $('.fileupload').fileupload(options)
                .bind('fileuploadadd', function (e, data) {
                    $('.upload-box').fadeIn();
                })
                .bind('fileuploadsubmit', function (e, data) {
                    // console.info('submit==>',$.support.fileInput)
                })
                .bind('fileuploaddone', function (e, data) {
                    // 上传完成
                    // console.log('done==>',data.result);
                    // return
                    var flag = data.result.state !== '1' ? 'danger' : 'success';
                    
                   // $('.upload-list').append('<p class="text-'+flag+'"><i class="icon icon-'+flag+'"></i>'+data.result.origin_file_name+'</p>');
                    if(flag == 'success'){
                        file_url(data.result)
                    }else{
                        util.message("上传失败" + data.result.message,'danger');
                    }
                    setTimeout(function(){
                        $('.upload-box').fadeOut(500);
                        removeHtml();
                    },8000)
                    function removeHtml(){
                        setTimeout(function(){
                            $('.upload-box').remove()
                        },1e3)
                    }


                })
                .bind('fileuploadprogressall', function (e, data) {
                    // 上传进度情况
                    var progress = parseInt(data.loaded / data.total * 100, 10);
                    $('.upload-box .progress .progress-bar').css('width',progress+'%');
                    if(progress == 100){
                        setTimeout(function(){
                            $('.upload-box .progress').fadeOut()
                        },1000)
                    }
                })
                .bind('fileuploadfail', function (e, data) {
                    // 提示错误信息
                    // data.errorThrown
                    util.message('上传失败！','danger');
                    // console.warn(data)

                })
                .prop('disabled', !$.support.fileInput).parent().addClass($.support.fileInput ? undefined : 'disabled').click(function(){
                if(!$('div').hasClass('upload-box')){
                  //  var html = '<div class="upload-box"><div class="upload-head">上传情况</div><div class="upload-list"></div><div class="progress"><div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" ></div></div></div>';
                  //  $(document.body).append(html);
                }
            });

        })
    }

    /**
     * 上传附件
     * @DateTime 2018-10-10
     *
     */
    util.attachmentUpload = function(url,file_url){
        require(['fileupload'],function(){
            var options = {
                url: url,                                       //上传地址
                autoUpload: true,                               //是否自动上传
                maxNumberOfFiles: 1,                            //最大上传文件数目
                maxFileSize: 1000000,                           //文件不超过1M
                sequentialUploads: true,                        //是否队列上传
                dataType: 'json'                                //从服务器返回数据json类型
            };
            $('.fileuploads').fileupload(options)
                .bind('fileuploaddone', function (e, data) {
                    var flag = data.result.state == '1' ? 'success' : 'danger';
                    if(flag == 'success'){
                        file_url(data.result)
                        util.message(data.result.message,'success')
                    }else{
                        util.message(data.result.message,'danger')
                    }
                })
        })
    }

    /**
     * 上传视频
     * @DateTime 2019-02-16
     *
     */
    util.fileuploadVideo = function(url,dataAlbum,file_url){
        require(['fileupload'],function(){
            var options = {
                url: url,                    //上传地址
                autoUpload: true,                               //是否自动上传
                acceptFileTypes: /(\.|\/)(3gp|flv|rmvb|mp4|wmv|avi|mkv|mp3|wav)$/i,         //文件格式限制
                maxNumberOfFiles: 1,                            //最大上传文件数目
                maxFileSize: 5 * 1024 * 1024,                           //文件不超过5M
                sequentialUploads: true,                        //是否队列上传
                dataType: 'json', //从服务器返回数据json类型
                formData: dataAlbum,
                processfail: function (e, data) { //根据的定义的校验规则进行文件检验
                 var currentFile = data.files[data.index];
                if (data.files.error && currentFile.error) {
                    console.log(currentFile.error)
                }
               }
            };
            $('.fileupload').fileupload(options)
                .bind('fileuploadadd', function (e, data) {
                    $('.upload-box').fadeIn();
                })
                .bind('fileuploadsubmit', function (e, data) {
                    // console.info('submit==>',$.support.fileInput)
                })
                .bind('fileuploaddone', function (e, data) {
                    // 上传完成
                    // console.log('done==>',data.result);
                    // return
                    var flag = data.result.state !== '1' ? 'danger' : 'success';
                    
                   // $('.upload-list').append('<p class="text-'+flag+'"><i class="icon icon-'+flag+'"></i>'+data.result.origin_file_name+'</p>');
                    if(flag == 'success'){
                        file_url(data.result)
                    }else{
                        util.message(data.result.message,'danger')
                    }
                    setTimeout(function(){
                        $('.upload-box').fadeOut(500);
                        removeHtml();
                    },8000)
                    function removeHtml(){
                        setTimeout(function(){
                            $('.upload-box').remove()
                        },1e3)
                    }


                })
                .bind('fileuploadprogressall', function (e, data) {
                    // 上传进度情况
                    var progress = parseInt(data.loaded / data.total * 100, 10);
                    $('.upload-box .progress .progress-bar').css('width',progress+'%');
                    if(progress == 100){
                        setTimeout(function(){
                            $('.upload-box .progress').fadeOut()
                        },1000)
                    }
                })
                .bind('fileuploadfail', function (e, data) {
                    // 提示错误信息
                    // data.errorThrown
                    util.message('上传失败！','danger');
                    // console.warn(data)

                })
                .prop('disabled', !$.support.fileInput).parent().addClass($.support.fileInput ? undefined : 'disabled').click(function(){
                if(!$('div').hasClass('upload-box')){
                  //  var html = '<div class="upload-box"><div class="upload-head">上传情况</div><div class="upload-list"></div><div class="progress"><div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" ></div></div></div>';
                  //  $(document.body).append(html);
                }
            });

        })
    }

    /**
        message消息提示
        参数：
        `type` ：提示类型 string
        {
            info = 提示
            success = 成功
            warning = 警告
            danger = 失败
        }
        `content`：提示内容
    */
    util.message = function(content,type,callback){
        type ? type : type = 'info'
        var messageHtml = '<div class="alert alert-'+type+'-1 alert-message-dialog fadeInDown" id="msgHtml" role="alert"><i class="icon icon-'+type+'"></i>'+content+'</div>'
        $(document.body).append(messageHtml)
        setTimeout(function(){
            $("#msgHtml").removeClass('fadeInDown').addClass('fadeInOut')
            removeHtml()
        },1500)
        function removeHtml(){
            setTimeout(function(){
                $("#msgHtml").remove();
                var regex = /^http(s)?:\/\/([\w-]+\.)+[\w-]+(\/[\w- ./?%&=#]*)?$/;
                if(callback && typeof callback === "function") {
                    callback();
                }else if(regex.test(callback)){
                    window.location.href=callback;
                }
            },500)
        }
    }


    /**
        alert确认窗口
        callback:点击确认的回调
    */
    util.alert = function(content,callback){
        $.alert({
            title:'提示',
            content:content,
            animation: 'top',
            closeAnimation: 'bottom',
            animateFromElement: false,
            backgroundDismiss: true,
            buttons: {
                '确定': {
                    btnClass: 'btn-primary',
                    action:callback
                },
                '取消': function () {
                    // util.message('danger','你点击了取消')
                }
            }
        })
    }
    
    /**
    alert确认窗口
    callback:点击确认的回调
	*/
	util.alert2 = function(content,callback){
	    $.alert({
	        title:'提示',
	        content:content,
	        animation: 'top',
	        closeAnimation: 'bottom',
	        animateFromElement: false,
	        backgroundDismiss: true,
	        buttons: {
	            '前往设置': {
	                btnClass: 'btn-primary',
	                action:callback
	            },
	            '取消': function () {
	                // util.message('danger','你点击了取消')
	            }
	        }
	    })
	}

    /**
        confirm 对话窗口
        title:标题        string
        content：内容     string | html代码
        callback：确定    function
        isString:定义宽度    选填 string 类型为function则执行 onContentReady
            默认=> medium
            中等=> large
            宽屏=> xlarge
            小屏=> small
            超小=> xsmall
    */
    util.confirm = function(title,content,callback,isString,setContent){
        var columnClass = 'medium';
        var onContentReady = '';
        if(isString && isString !== undefined){
            if(typeof isString == 'string'){
                columnClass = isString
            }else{
                onContentReady = isString;
            }
        }
        if(setContent && setContent !== undefined && typeof setContent == 'function'){
            onContentReady = setContent;
        }
        $.confirm({
            title:title,
            content:content,
            animation:'top',
            closeAnimation:'bottom',
            animateFromElement: false,
            columnClass: columnClass,
            buttons: {
                '确定': {
                    btnClass: 'btn-primary',
                    action:callback
                },
                '取消': function () {
                    // util.message('danger','你点击了取消')
                }
            },
            onContentReady: onContentReady
        })
    }

    util.confirm1 = function(title,content,isString,setContent){
        var columnClass = 'xlarge';
        var onContentReady = '';
        if(isString && isString !== undefined){
            if(typeof isString == 'string'){
                columnClass = isString
            }else{
                onContentReady = isString;
            }
        }
        if(setContent && setContent !== undefined && typeof setContent == 'function'){
            onContentReady = setContent;
        }
        $.dialog({
            cancelButton: true,
            confirmButton: true,
            closeIcon:true,
            title:title,
            content:content,
            animation:'top',
            closeAnimation:'bottom',
            animateFromElement: false,
            columnClass: columnClass,
            onContentReady: onContentReady
        })
    }

    util.confirm2 = function(title,content,isString,setContent){
        var columnClass = 'large';
        var onContentReady = '';
        if(isString && isString !== undefined){
            if(typeof isString == 'string'){
                columnClass = isString
            }else{
                onContentReady = isString;
            }
        }
        if(setContent && setContent !== undefined && typeof setContent == 'function'){
            onContentReady = setContent;
        }
        $.dialog({
            cancelButton: true,
            confirmButton: true,
            closeIcon:true,
            title:title,
            content:content,
            animation:'top',
            closeAnimation:'bottom',
            animateFromElement: false,
            columnClass: columnClass,
            onContentReady: onContentReady
        })
    }

    util.confirm3 = function(title,content,isString,setContent){
        var columnClass = 'medium';
        var onContentReady = '';
        if(isString && isString !== undefined){
            if(typeof isString == 'string'){
                columnClass = isString
            }else{
                onContentReady = isString;
            }
        }
        if(setContent && setContent !== undefined && typeof setContent == 'function'){
            onContentReady = setContent;
        }
        $.dialog({
            cancelButton: true,
            confirmButton:true,
            closeIcon:false,
            title:title,
            content:content,
            animation:'top',
            closeAnimation:'bottom',
            animateFromElement: false,
            columnClass: columnClass,
            onContentReady: onContentReady
        })
    }

    util.confirm_message = function(title,content,callback,isString,setContent){
        var columnClass = 'large';
        var onContentReady = '';
        if(isString && isString !== undefined){
            if(typeof isString == 'string'){
                columnClass = isString
            }else{
                onContentReady = isString;
            }
        }
        if(setContent && setContent !== undefined && typeof setContent == 'function'){
            onContentReady = setContent;
        }
        $.confirm({
            title:title,
            content:content,
            animation:'top',
            closeAnimation:'bottom',
            animateFromElement: false,
            columnClass: columnClass,
            buttons: {
                '确定': {
                    btnClass: 'btn-primary',
                    action:callback
                },
                '取消': function () {
                    // util.message('danger','你点击了取消')
                }
            },
            onContentReady: onContentReady
        })
    }

    util.confirmExpire = function(title,content,callback,isString,setContent){
        var columnClass = 'large';
        var onContentReady = '';
        if(isString && isString !== undefined){
            if(typeof isString == 'string'){
                columnClass = isString
            }else{
                onContentReady = isString;
            }
        }
        if(setContent && setContent !== undefined && typeof setContent == 'function'){
            onContentReady = setContent;
        }
        $.confirm({
            title:title,
            content:content,
            animation:'top',
            closeAnimation:'bottom',
            animateFromElement: false,
            columnClass: columnClass,
            buttons: {
                '联系客服': {
                    btnClass: 'btn-primary',
                    action:callback
                },
                '知道了': function () {
                    // util.message('danger','你点击了取消')
                }
            },
            onContentReady: onContentReady
        })
    }

    /**
        图表
    */
    util.chart = function(element,option){
        require(['echarts','walden'],function(e,walden){
            var dom = document.getElementById(element);
            var myChart = e.init(dom,'walden');
            var app = {};
            // option = null;
            if (option && typeof option === "object") {
                myChart.setOption(option, true);
                window.onresize = myChart.resize;
            }
        })
    }

    /**

        表格树形结构
    */
    util.treegrid = function(element){
        require(['treegrid'],function(){
            $(element).treegrid({
                expanderExpandedClass: 'icon icon-minus',
                expanderCollapsedClass: 'icon icon-plus',
                initialState: "collapsed",
                animate: false
            });
        })
    }


    /**
     * 富文本编辑器
     * @DateTime 2018-06-01
     * @param    {[element]}
     * @param    {[height]}
     */

    util.ueditor = function(element){
        if(typeof(UE)!='undefined'){
            UE.delEditor(element);
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
            UE.registerUI('myinsertimage', function (editor, uiName) {
                editor.registerCommand(uiName, {
                    execCommand: function() {
                        var storage = new util.Storage('session');
                        storage.setItem('multiple','1');
                        util.pictureDialog(uiName,true,function(data){
                            var imgTpl = ''
                            $.each(data.path,function(i,e){
                                imgTpl += '<img style="max-width:100%;" src="'+e+'">'
                            })
                            ue.execCommand('inserthtml', imgTpl);
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
            }, 30);
            var ue = UE.getEditor(element, ueditoroption);
            ue.ready(function() {
                ue.setContent($('#'+element).data('content'));
               ue.addListener('contentChange', function () {
                   var html = ue.getContent();
                   $('#'+element).data('content',html);
               });
                // window.setInterval(function(){
                //     var content = ue.getContent();
                //     $('#'+element).data('content',content);
                // },1000);//自动同步
            })
        })
    }

    /**

    /**
     * 富文本编辑器变动保存在hidden
     * @DateTime 2018-10-10
     */

    util.ueditorChange = function(element){
        require(['ueditor','ueditor.ZeroClipboard','ueditor.lang'],function(u,zcl){
            UE.getEditor(element).addListener("selectionchange",function(){
                changeElement('content');

            });
            function changeElement(name){
                var dir = $("#dir").attr('dir');
                  if(name == 'content'){
                    var content = UE.getEditor(element).getContent();
                    $("#"+name+dir).val(content);
                }
            }
        })
    }

    /**

        取色器
    */
    util.colorpicker = function(element, callback) {
        require(['colorpicker'], function(){
            $(element).spectrum({
                className : "",
                cancelText:'取消',
                chooseText:'确定',
                togglePaletteMoreText:'更多',
                togglePaletteLessText:'收缩',
                showInput: true,
                showInitial: true,
                showPalette: true,
                maxPaletteSize: 10,
                togglePaletteOnly:true,
                showAlpha: true,
                preferredFormat: "hex",
                change: function(color) {
                    if($.isFunction(callback)) {
                        callback(color);
                    }
                },
                palette: [
                    ["rgb(0, 0, 0)", "rgb(67, 67, 67)", "rgb(102, 102, 102)", "rgb(153, 153, 153)","rgb(183, 183, 183)",
                    "rgb(204, 204, 204)", "rgb(217, 217, 217)","rgb(239, 239, 239)", "rgb(243, 243, 243)", "rgb(255, 255, 255)"],
                    ["rgb(152, 0, 0)", "rgb(255, 0, 0)", "rgb(255, 153, 0)", "rgb(255, 255, 0)", "rgb(0, 255, 0)",
                    "rgb(0, 255, 255)", "rgb(74, 134, 232)", "rgb(0, 0, 255)", "rgb(153, 0, 255)", "rgb(255, 0, 255)"],
                    ["rgb(230, 184, 175)", "rgb(244, 204, 204)", "rgb(252, 229, 205)", "rgb(255, 242, 204)", "rgb(217, 234, 211)",
                    "rgb(208, 224, 227)", "rgb(201, 218, 248)", "rgb(207, 226, 243)", "rgb(217, 210, 233)", "rgb(234, 209, 220)",
                    "rgb(221, 126, 107)", "rgb(234, 153, 153)", "rgb(249, 203, 156)", "rgb(255, 229, 153)", "rgb(182, 215, 168)",
                    "rgb(162, 196, 201)", "rgb(164, 194, 244)", "rgb(159, 197, 232)", "rgb(180, 167, 214)", "rgb(213, 166, 189)",
                    "rgb(204, 65, 37)", "rgb(224, 102, 102)", "rgb(246, 178, 107)", "rgb(255, 217, 102)", "rgb(147, 196, 125)",
                    "rgb(118, 165, 175)", "rgb(109, 158, 235)", "rgb(111, 168, 220)", "rgb(142, 124, 195)", "rgb(194, 123, 160)",
                    "rgb(166, 28, 0)", "rgb(204, 0, 0)", "rgb(230, 145, 56)", "rgb(241, 194, 50)", "rgb(106, 168, 79)",
                    "rgb(69, 129, 142)", "rgb(60, 120, 216)", "rgb(61, 133, 198)", "rgb(103, 78, 167)", "rgb(166, 77, 121)",
                    "rgb(133, 32, 12)", "rgb(153, 0, 0)", "rgb(180, 95, 6)", "rgb(191, 144, 0)", "rgb(56, 118, 29)",
                    "rgb(19, 79, 92)", "rgb(17, 85, 204)", "rgb(11, 83, 148)", "rgb(53, 28, 117)", "rgb(116, 27, 71)",
                    "rgb(91, 15, 0)", "rgb(102, 0, 0)", "rgb(120, 63, 4)", "rgb(127, 96, 0)", "rgb(39, 78, 19)",
                    "rgb(12, 52, 61)", "rgb(28, 69, 135)", "rgb(7, 55, 99)", "rgb(32, 18, 77)", "rgb(76, 17, 48)"]
                ]
            });
        });
    }

    /**

        取色器(黑白)
    */
    util.colorpicker2 = function(element, callback) {
        require(['colorpicker'], function(){
            $(element).spectrum({
                className : "",
                cancelText:'取消',
                chooseText:'确定',
                togglePaletteMoreText:'更多',
                togglePaletteLessText:'收缩',
                showInput: true,
                showInitial: true,
                showPalette: true,
                maxPaletteSize: 10,
                // togglePaletteOnly:true,
                showAlpha: true,
                preferredFormat: "hex",
                // flat:true,
                showPaletteOnly:true,
                togglePaletteOnly:false,
                hideAfterPaletteSelect:true,
                change: function(color) {
                    if($.isFunction(callback)) {
                        callback(color);
                    }
                },

                palette: [
                    ["rgb(0, 0, 0)","rgb(255, 255, 255)"],
                ]
            });
        });
    }

    /**
     * 复制粘贴插件
     */
    util.copy = function(){
        require(['clipboard'],function(ClipboardJS){
            var clipboard = new ClipboardJS('.copy');
            clipboard.on('success', function(e) {
                util.message("复制成功!",'success');
                e.clearSelection();
            });
            clipboard.on('error', function(e) {
                util.message("复制失败!",'danger');
            });
        })
    }
    /**
     * 复制粘贴插件2
     */
    util.copy1 = function(){
        require(['clipboard'],function(ClipboardJS){
            var dom=$('body .jconfirm');
            var clipboard = new ClipboardJS('.copy',{container:document.getElementsByClassName('jconfirm1')[0]});
            clipboard.on('success', function(e) {
                util.message("复制成功!",'success');
                e.clearSelection();
            });
            clipboard.on('error', function(e) {
                util.message("复制失败!",'danger');
            });
        })
    }


    /**
        icons选择图标
    */
    util.iconsDialog = function(callback){
        $.confirm({
            title: '请选择图标',
            content: 'url:'+__URL(PLATFORMMAIN + '/shop/modalIcons?t='+(new Date()).getTime()),
            animation: 'top',
            columnClass: 'col-md-10 col-md-offset-1',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data().icon;
                        if(util.isEmpty(content)){
                            util.message('请选择图标')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            },
            // onContentReady: function () {
            //     var _this = this;
            //     this.$content.find('#iconSelected').on('click', function (e) {
            //         _this.$$confirm.trigger('click'); // reference the button and click it
            //     });
            // }

        });
    }
    /**
    小程序装修底部选择图标
    */
    util.mpFooterDialog = function(callback){
        $.confirm({
            title: '请选择图标',
            content: 'url:'+__URL(PLATFORMMAIN + '/shop/modalFooterIcons?t='+(new Date()).getTime()),
            animation: 'top',
            columnClass: 'col-md-10 col-md-offset-1',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data().icon;
                        if(util.isEmpty(content)){
                            util.message('请选择图标')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            },
            // onContentReady: function () {
            //     var _this = this;
            //     this.$content.find('#iconSelected').on('click', function (e) {
            //         _this.$$confirm.trigger('click'); // reference the button and click it
            //     });
            // }

        });
    }

    util.wap_iconsDialog = function(callback){
        $.confirm({
            title: '请选择图标',
            content: 'url:'+__URL(PLATFORMMAIN + '/shop/modalWapIcons?t='+(new Date()).getTime()),
            animation: 'top',
            columnClass: 'col-md-10 col-md-offset-1',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data().icon;
                        if(util.isEmpty(content)){
                            util.message('请选择图标')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){}
                }
            }
        });
    }

    // 选择表情
    util.emojiDialog = function(callback){
        $.confirm({
            title:'选择表情',
            content:'url:../../../template/platform/Wchat/emoji.html',
            animation: 'top',
            columnClass: 'col-md-10 col-md-offset-1',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data();
                        if(util.isEmpty(content)){
                            util.message('请选择表情')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){}
                }
            }
        })
    }

    /**
     * 选择图片
     * @DateTime 2018-05-08
     * @param    {
     *      _this       :    当前元素this   (必须)
     *      isMulti     :    是否多选  默认false (可选)
     *      callback    :       (可选)
     * }
     */
    util.pictureDialog = function(_this,isMulti,callback){
        isMulti && isMulti !== undefined ? isMulti : isMulti = false
        $.confirm({
            title: '素材空间',
            content: 'url:'+__URL(PLATFORMMAIN + '/goods/pic_space'),
            animation: 'top',
            columnClass: 'col-md-11',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data();
                        if(util.isEmpty(content) || util.isEmpty(content['id'])){
                            util.message('请选择图片')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }else{
                            if(isMulti){
                                for (var i = 0; i < content.path.length; i++) {
                                    $(_this).parent().prepend('<a href="javascript:;" id="goods_pic_list" style="margin-right:10px;"><i class="icon icon-danger"  style="right:-10px;" title="删除"></i><img src='+content.path[i]+'></a><input type="hidden" name="upload_img_id" value="'+content.id[i]+'">')
                                }
                            }else{
                                
                                var img = '<a href="javascript:;" class="close-box"><i class="icon icon-danger" data-id="'+content.id[0]+'" title="删除"></i><img src='+content.path[0]+'></a>';
                                $(_this).parent().html(img);
                            }
                        }

                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            },
            // contentLoaded:function(data, status, xhr){
            //     console.log(data, status, xhr)
            // },
            onClose:function(){
                var storage = new util.Storage('session');
                if(storage.getKey('multiple')){
                    storage.removeItem('multiple')
                }
            },
            onContentReady: function () {

            }
        });
    }

    /**
     * 选择视频
     * @DateTime 2018-05-08
     * @param    {
     *      _this       :    当前元素this   (必须)
     *      isMulti     :    是否多选  默认false (可选)
     *      callback    :       (可选)
     * }
     */
    util.videoDialog = function(_this,isMulti,callback){
        isMulti && isMulti !== undefined ? isMulti : isMulti = false
        $.confirm({
            title: '素材空间',
            content: 'url:'+__URL(PLATFORMMAIN + '/goods/video_space'),
            animation: 'top',
            columnClass: 'col-md-11',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data();
                        if(util.isEmpty(content) || util.isEmpty(content['id'])){
                            util.message('请选择素材')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }else{
                            if(isMulti){
                                for (var i = 0; i < content.path.length; i++) {
                                    $(_this).parent().prepend('<a href="javascript:;"  style="margin-right:10px;"><i class="icon icon-danger"  style="right:-15px;" title="删除"></i><video width="80px" height="80px" src='+content.path[i]+'></video></a><input type="hidden" name="upload_video_id" value="'+content.id[i]+'">')
                                }
                            }else{
                                
                                var img = '<a href="javascript:;" class="close-box"><i class="icon icon-danger" data-id="'+content.id[0]+'" style="margin-right:10px;" title="删除"></i><video  width="80px" height="80px" src='+content.path[0]+'></video></a><input type="hidden" id="video_id" name="upload_video_id" value="'+content.id[0]+'">';
                                $(_this).parent().html(img);
                            }
                        }

                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            },
            // contentLoaded:function(data, status, xhr){
            //     console.log(data, status, xhr)
            // },
            onClose:function(){
                var storage = new util.Storage('session');
                if(storage.getKey('multiple')){
                    storage.removeItem('multiple')
                }
            },
            onContentReady: function () {

            }
        });
    }

    /**
     * 选择图片视频
     * @DateTime 2019-11-06
     * @param    {
     *      _this       :    当前元素this   (必须)
     *      isMulti     :    是否多选  默认false (可选)
     *      callback    :       (可选)
     * }
     */
    util.picVideoDialog = function(_this,isMulti,callback){
        isMulti && isMulti !== undefined ? isMulti : isMulti = false
        $.confirm({
            title: '素材空间',
            content: 'url:'+__URL(PLATFORMMAIN + '/goods/picvideo_space'),
            animation: 'top',
            columnClass: 'col-md-11',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data();
                        if(util.isEmpty(content) || util.isEmpty(content['id'])){
                            util.message('请选择素材')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }else{
                            if(isMulti){
                                for (var i = 0; i < content.path.length; i++) {
                                    $(_this).parent().prepend('<a href="javascript:;"  style="margin-right:10px;"><i class="icon icon-danger"  style="right:-15px;" title="删除"></i><video width="80px" height="80px" src='+content.path[i]+'></video></a><input type="hidden" name="upload_video_id" value="'+content.id[i]+'">')
                                }
                            }else{
                                
                                var img = '<a href="javascript:;" class="close-box"><i class="icon icon-danger" data-id="'+content.id[0]+'" style="margin-right:10px;" title="删除"></i><video  width="80px" height="80px" src='+content.path[0]+'></video></a><input type="hidden" id="video_id" name="upload_video_id" value="'+content.id[0]+'">';
                                $(_this).parent().html(img);
                            }
                        }

                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            },
            // contentLoaded:function(data, status, xhr){
            //     console.log(data, status, xhr)
            // },
            onClose:function(){
                var storage = new util.Storage('session');
                if(storage.getKey('multiple')){
                    storage.removeItem('multiple')
                }
            },
            onContentReady: function () {

            }
        });
    }



    /**
     * 规格图片上传
     * **/
    util.spec_picDialog = function(_this,callback){
        $.confirm({
            title: '素材空间',
            content: 'url:'+__URL(PLATFORMMAIN + '/goods/pic_space'),
            animation: 'top',
            columnClass: 'col-md-11',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data();
                        if(util.isEmpty(content) || util.isEmpty(content['id'])){
                            util.message('请选择图片')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }else{
                            // var img = '<input type="hidden" name="spec_img_id" value="'+content.id[0]+'"><a href="javascript:;" class="close-box"><img style="width:40px;margin-right:10px;" src='+content.path[0]+'></a>';
                            // $(_this).parent().find('.pic_info').html(img);
                            var img=content.path[0];
                            $(_this).find('img').attr('src',img);
                            $(_this).parent().find('.pic_info').html('<input type="hidden" name="spec_img_id" value="'+content.id[0]+'">');
                            var spec_id = $(_this).parent().find('.specItemValue').attr('data-spec_value_id');
                            $(_this).parent().find('.specItemValue').attr("data-spec_value_data",content.id[0]);

                     

                        }

                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            },
            // contentLoaded:function(data, status, xhr){
            //     console.log(data, status, xhr)
            // },
            onClose:function(){
                var storage = new util.Storage('session');
                if(storage.getKey('multiple')){
                    storage.removeItem('multiple')
                }
            },
            onContentReady: function () {

            }
        });
    }

    /**
     * 公众号图文素材
     * @DateTime 2018-05-10
     * @param {}
     * @return   {}
     */
    util.materialDialog = function(){
        $.confirm({
            title: '选择素材',
            content: 'url:../../../template/platform/materialDialog.html?'+(new Date()).getTime(),
            animation: 'top',
            columnClass: 'large',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){


                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            }

        });
    }
    /**
     * 商品选择
     *
     */
    util.goodsDialog = function(url,callback,onContentReady){
        // console.log(url);
        $.confirm({
            title: '选择商品',
            content: url,
            animation: 'top',
            columnClass: 'large',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data();
                        if(util.isEmpty(content)){
                            util.message('请选择商品')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            },
            onContentReady: onContentReady

        });
    }
    /**
     * 小程序直播商品选择
     *
     */
    util.mpliveGoodsDialog = function(url,callback,onContentReady){
        // console.log(url);
        $.confirm({
            title: '选择商品',
            content: url,
            animation: 'top',
            columnClass: 'large',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary-diy',
                    action:function(){
                        var content = this.$content.find('#selectedData').data();
                        if(util.isEmpty(content)){
                            util.message('请选择商品')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default-diy',
                    action:function(){

                    }
                }
            },
            onContentReady: onContentReady

        });
    }
    /**
     * CPS商品选择
     *
     */
    util.cpsGoodsDialog = function(url,callback,onContentReady){
        // console.log(url);
        $.confirm({
            title: '选择商品',
            content: url,
            animation: 'top',
            columnClass: 'large',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data();
                        if(util.isEmpty(content)){
                            util.message('请选择商品')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){
                    }
                }
            },
            onContentReady: onContentReady
        });
    }
    /**
     * 用户选择
     *
     */
    util.userDialog = function(url,callback,onContentReady){
        // console.log(url);
        $.confirm({
            title: '选择会员',
            content: url,
            animation: 'top',
            columnClass: 'large',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data();
                        if(util.isEmpty(content)){
                            util.message('请选择会员')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            },
            onContentReady: onContentReady

        });
    }
    /**
     * 积分商品选择
     *
     */
    util.activityDialog = function(url,callback){
        // console.log(url);
        $.confirm({
            title: '选择',
            content: url,
            animation: 'top',
            columnClass: 'large',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary1',
                    action:function(){
                        var content = this.$content.find('#selectedData').data();
                        if(util.isEmpty(content)){
                            util.message('请选择商品')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default1',
                    action:function(){
 
                    }
                }
            }

        });
    }
    /**
     * 秒杀活动某个商品sku查看
     *
     */
    util.seckillGoodsSkuDialog = function(url,goods_name,callback){
        $.confirm({
            title: goods_name,
            content: url,
            animation: 'top',
            columnClass: 'large',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: false,
            buttons: {
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            }

        });
    }
    /**
     * 记录秒杀活动删除某个商品 信息的弹框
     *
     */
    util.seckillDelGoodsInfoDialog = function(url,callback){
        $.confirm({
            title: false,
            content: url,
            animation: 'top',
            columnClass: 'large',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: false,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        content = $('#del_goods_info').val()
                        if(content == ''){
                            util.message('请填写理由')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback();
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){
                    }
                }
            }

        });
    }

    /**
     * 店铺申请秒杀报名条件展示
     *
     */
    util.seckillRequirementsDialog = function(url,callback){
        $.confirm({
            title: '店铺秒杀报名条件',
            content: url,
            animation: 'top',
            columnClass: 'large',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: false,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        content = $('#del_goods_info').val()
                        if(content == ''){
                            util.message('报名条件')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback();
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            }

        });
    }
    /**
     * 店铺选择
     *
     */
    util.shopDialog = function(url,callback,onContentReady){
        $.confirm({
            title: '选择店铺',
            content: url,
            animation: 'top',
            columnClass: 'large',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data();
                        if(util.isEmpty(content)){
                            util.message('请选择店铺')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            },
            onContentReady: onContentReady

        });
    }
    /**
     * 链接选择
     *
     */
    util.linksDialog = function(callback,type){
        $.confirm({
            title: '选择链接',
            content: 'url:'+__URL(PLATFORMMAIN+ '/shop/modalLinkList?t='+(new Date()).getTime()+'&type='+type),
            animation: 'top',
            columnClass: 'large',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data();
                        if(util.isEmpty(content)){
                            util.message('请选择链接')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            }

        });
    }
    /**
     * 链接选择_pc装修
     *
     */
    util.linksDialogPc = function(callback,type){
        $.confirm({
            title: '选择链接',
            content: 'url:'+__URL(PLATFORMMAIN+ '/shop/modalLinkListPc?t='+(new Date()).getTime()+'&type='+type),
            animation: 'top',
            columnClass: 'large',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data();
                        if(util.isEmpty(content)){
                            util.message('请选择链接')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            }

        });
    }
    /**
     * 链接选择_pc装修
     *
     */

    /**
     * 链接选择_小程序
     *
     */
    util.linksMinDialog = function(callback,type){
        $.confirm({
            title: '选择链接',
            content: 'url:'+__URL(PLATFORMMAIN+ '/shop/modalLinkListMin?t='+(new Date()).getTime()+'&type='+type),
            animation: 'top',
            columnClass: 'large',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData').data();
                        if(util.isEmpty(content)){
                            util.message('请选择链接')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){

                    }
                }
            }

        });
    }

    /**
     * 链接选择_小程序 - 订阅消息 - 商城变量
     *
     */
    util.minSubMessageDialog = function(callback){
        $.confirm({
            title: '选择链接',
            content: 'url:'+__URL(PLATFORMMAIN+ '/shop/modalMinSubMessage'),
            animation: 'top',
            columnClass: 'large',
            closeAnimation: 'bottom',
            backgroundDismiss: true,
            animateFromElement: false,
            closeIcon: true,
            buttons: {
                confirm: {
                    text:'确定',
                    btnClass:'btn-primary',
                    action:function(){
                        var content = this.$content.find('#selectedData2').data('params');
                        if(util.isEmpty(content)){
                            util.message('请选择变量')
                            return false;
                        }
                        if(callback && callback !== undefined && typeof(callback) === 'function'){
                            callback(content);
                        }
                    }
                },
                cancel: {
                    text:'取消',
                    btnClass:'btn-default',
                    action:function(){
                    }
                }
            }
        });
    }

    /**
     * 表单验证
     * @DateTime 2018-06-13
     * @param {elm}         表单元素
     */
    util.validate = function(elm,callback){
        require(['domReady','jquery.validate','validate.methods'],function(){
            var msg = {
                required: " ",
                remote: "请修正该字段",
                email: "请输入正确格式的电子邮件",
                url: "请输入正确的网址",
                date: "请输入正确的日期",
                dateISO: "请输入合法的日期 (ISO).",
                number: "此项为数字格式",
                digits: "此项必须为数字",
                creditcard: "请输入合法的信用卡号",
                equalTo: "请再次输入相同的值",
                accept: "请输入拥有合法后缀名的字符串",
                maxlength: $.validator.format("请输入一个长度最多是 {0} 的字符串"),
                minlength: $.validator.format("请输入一个长度最少是 {0} 的字符串"),
                rangelength: $.validator.format("请输入一个长度介于 {0} 和 {1} 之间的字符串"),
                range: $.validator.format("请输入一个介于 {0} 和 {1} 之间的值"),
                max: $.validator.format("请输入一个最大为 {0} 的值"),
                min: $.validator.format("请输入一个最小为 {0} 的值")
            };
            var validate_rule = {
                ignore:'.ignore',
                errorElement: 'span',
                errorClass: 'help-block-error',
                focusInvalid: true,
                debug:true,
                // highlight: function(element) {
                //     var parent = $(element).data('parent') || '';
                //     if (parent) {
                //         $(parent).addClass('has-error')
                //     } else {
                //         $(element).closest('.form-group').addClass('has-error')
                //     }
                // },
                highlight: function (element) {
                    // console.log($(element));
                    var parent = $(element).data('parent') || '';
                    var visiType = $(element).data('visi-type');
                    if (parent) {
                        $(parent).addClass('has-error')
                    } else {
                        if (visiType == 'prices_1') {
                            $(element).closest('.w15').addClass('has-error');
                        }
                        else{
                            $(element).closest('.form-group').addClass('has-error')
                        }
                        
                    }
                },
                onkeyup: function(element) {
                    $(element).valid()
                },
                onfocusout: function(element) {
                    $(element).valid()
                },
                // success: function(element) {
                //     // console.log($(element))
                //     var parent = $(element).data('parent') || '';
                //     if (parent) {
                //         $(parent).removeClass('has-error')
                //     } else {
                //         $(element).closest('.form-group').removeClass('has-error')
                //     }
                    
                // },
                success: function (element) {
                    // console.log($(element));
                    // console.log('success');
                    var visiType=$(element).siblings('input').data('visi-type');
                    var parent = $(element).data('parent') || '';
                            if (parent) {
                                $(parent).removeClass('has-error')
                            } else {
                                if(visiType == 'prices_1'){
                                    $(element).closest('.w15').removeClass('has-error');
                                }
                                else{
                                    $(element).closest('.form-group').removeClass('has-error');
                                }
                                    
                            }


                },
                errorPlacement: function(error, element) {
                    // 单选复选框
                    if (element.is(':radio') || element.is(':checkbox')){
                        // console.log(element)
                        var group = element.parent().parent();
                        group.length > 0 ? group.after(error) : element.after(error)

                    }else if(element.attr('class') == 'visibility'){
                        var visiType = $(element).data('visi-type')
                        if(visiType == 'singlePicture' || visiType == 'singleVideo'){
                            // 单选图片
                            var pElement = $(element).parents('.picture-list')
                            pElement.find('.plus-box').addClass('validate-border')
                            pElement.after(error)
                            pElement.bind('DOMNodeInserted', function(e) {
                                if(e.target){
                                    $(e.target).parent().siblings('.help-block-error').remove()
                                    $(e.target).parents('.form-group').removeClass('has-error')
                                }
                            });
                        }else if(visiType == 'multiPicture'){
                            // 多选图片
                            if($(element).prev().find('input[name="upload_img_id"]').length == 0){
                                $(element).prev().find('.plus-box').addClass('validate-border')
                                $(element).after(error)
                                $(element).prev().find('.picture-list').bind('DOMNodeInserted', function(e) {
                                    if(e.target){
                                        var pElement = $(e.target).parents('.form-group')
                                        pElement.removeClass('has-error')
                                        pElement.find('.validate-border').removeClass('validate-border')
                                        pElement.find('.help-block-error').remove()
                                    }
                                });
                            }else{
                                $(element).parents('.form-group').removeClass('has-error')
                                // $(element).remove()
                                $(element).removeAttr('required');
                            }
                        }else if(visiType == 'UE'){
                            console.log($(element).prev().find('.edui-editor').addClass('validate-border'))
                            // $(element).prev().addClass('validate-border')
                            $(element).after(error)
                        }
                    }else{
                        // 普通input框
                        var group = element.parents(".input-group");
                        group.length > 0 ? group.after(error) : element.after(error)
                    }
                },
                submitHandler: function(form) {
                    callback(form)
                },
                invalidHandler:function(form, validator){
                    if($('.tab-1').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(0) a').tab('show');
                    }
                    else if($('.tab-2').find('.w15').hasClass('has-error')){
                        $('.add_tab1 li:eq(1) a').tab('show');
                    }
                    else if($('.tab-3').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(2) a').tab('show');
                    }
                    else if($('.tab-4').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(3) a').tab('show');
                    }
                    else if($('.tab-5').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(4) a').tab('show');
                    }
                    else if($('.tab-6').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(5) a').tab('show');
                    }
                    else{
                        $('.add_tab1 li:eq(0) a').tab('show');
                    }
                    return false;

                },
            };
            $.extend($.validator.messages, msg);
            elm.validate(validate_rule)
        })
    }

    /**
     * 大转盘表单验证
     * @DateTime 2018-06-13
     * @param {elm}         表单元素
     */
    util.validate2 = function(elm,callback){
        require(['domReady','jquery.validate','validate.methods'],function(){
            var msg = {
                required: " ",
                remote: "请修正该字段",
                email: "请输入正确格式的电子邮件",
                url: "请输入正确的网址",
                date: "请输入正确的日期",
                dateISO: "请输入合法的日期 (ISO).",
                number: "此项为数字格式",
                digits: "此项必须为数字",
                creditcard: "请输入合法的信用卡号",
                equalTo: "请再次输入相同的值",
                accept: "请输入拥有合法后缀名的字符串",
                maxlength: $.validator.format("请输入一个长度最多是 {0} 的字符串"),
                minlength: $.validator.format("请输入一个长度最少是 {0} 的字符串"),
                rangelength: $.validator.format("请输入一个长度介于 {0} 和 {1} 之间的字符串"),
                range: $.validator.format("请输入一个介于 {0} 和 {1} 之间的值"),
                max: $.validator.format("请输入一个最大为 {0} 的值"),
                min: $.validator.format("请输入一个最小为 {0} 的值")
            };
            var validate_rule = {
                ignore:'.ignore',
                errorElement: 'span',
                errorClass: 'help-block-error',
                focusInvalid: true,
                debug:true,
                highlight: function(element) {
                    var parent = $(element).data('parent') || '';
                    if (parent) {
                        $(parent).addClass('has-error')
                    } else {
                        $(element).closest('.form-group').addClass('has-error')
                    }
                },
                onkeyup: function(element) {
                    $(element).valid()
                },
                onfocusout: function(element) {
                    $(element).valid()
                },
                success: function(element) {
                    // console.log($(element))
                    var parent = $(element).data('parent') || '';
                    if (parent) {
                        $(parent).removeClass('has-error')
                    } else {
                        $(element).closest('.form-group').removeClass('has-error')
                    }
                    
                },
                errorPlacement: function(error, element) {
                    // 单选复选框
                    if (element.is(':radio') || element.is(':checkbox')){
                        // console.log(element)
                        var group = element.parent().parent();
                        group.length > 0 ? group.after(error) : element.after(error)

                    }else{
                        // 普通input框
                        var group = element.parents(".input-group");
                        group.length > 0 ? group.after(error) : element.after(error)
                    }
                },
                submitHandler: function(form) {
                    callback(form)
                },
                invalidHandler:function(form, validator){
                    if($('.tab-1').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(0) a').tab('show');
                    }
                    else if($('.tab-2').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(1) a').tab('show');
                    }
                    else if($('.tab-3').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(2) a').tab('show');
                    }
                    else if($('.tab-4').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(3) a').tab('show');
                    }
                    else if($('.tab-5').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(4) a').tab('show');
                    }
                    else if($('.tab-6').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(5) a').tab('show');
                    }
                    else if($('.tab-7').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(6) a').tab('show');
                    }
                    else if($('.tab-8').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(7) a').tab('show');
                    }
                    else if($('.tab-9').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(8) a').tab('show');
                    }
                    else if($('.tab-10').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(9) a').tab('show');
                    }
                    else if($('.tab-11').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(10) a').tab('show');
                    }
                    else if($('.tab-12').find('.form-group').hasClass('has-error')){
                        $('.add_tab1 li:eq(11) a').tab('show');
                    }
                    else{
                        $('.add_tab1 li:eq(0) a').tab('show');
                    }
                    return false;

                },
            };
            $.extend($.validator.messages, msg);
            elm.validate(validate_rule)
        })
    }

    /**
     * 分页器
     *
     * @DateTime 2018-06-25
     * @param    {function}         callback          回调
     */
    util.initPage = function(callback,elm){
        require(['paginator'],function(){
            // 初始化
            if(!elm && elm == undefined) elm = 'page'
            $.paginator($('#'+elm), {
                totalCounts: 1,
                pageSize: 20,
                currentPage: 1,
                onPageChange: callback
            });
        })
    }

    /**
     * 判断对象object及数组array是否为空对象或空数组
     *
     * @DateTime 2018-05-08
     * @param    {obj}
     * @return   {Boolean}
     */
    util.isEmpty = function (obj) {
        if (!obj && obj !== 0 && obj !== '') {
            return true;
        }
        if (Array.prototype.isPrototypeOf(obj) && obj.length === 0) {
            return true;
        }
        if (Object.prototype.isPrototypeOf(obj) && Object.keys(obj).length === 0) {
            return true;
        }
        return false;
    }

    /**
     * 本地临时存储   实例化对象 new Storage
     * @param {
     *      type     存储方式，指定是session或local存储
     * }
     * @DateTime 2018-05-08
     * @return   {
     *      setItem('key','data')       存储数据
     *      getItem('key')              获取存储数据
     *      removeItem('key')           删除键名为key的存储数据
     *      getKey('key')               判断是否存在某一键的数据
     *      clear('key')                清空存储
     * }
     */
    util.Storage = function(type){
        this.map = {
            'session' : window.sessionStorage,
            'local' : window.localStorage
        },
        this.getItem = function( key ){
            return this.map[type].getItem( key );
        },
        this.setItem = function( key, value ){
            this.map[type].setItem( key,value )
        },
        this.removeItem = function( key ){
            this.map[type].removeItem( key );
        },
        this.clear = function(){
            this.map[type].clear();
        },
        this.getKey = function( key ){
            //var len = map.type.length;
            return key in this.map[type];
        }
    }
    /**
     * layDate日期 
     * @param {
     *      element     触发的dom
     *      range       Boolean true为双日历
     *      callback    点击确定的回调
     * }
     * @DateTime 2018-11-15
     */
    util.layDate=function(element,ranges,callback){
        require(['laydate'],function(laydate){
            ranges && ranges !== undefined ? ranges : false;
            var minDate = $(element).attr("data-mindate");
            var maxDate = $(element).attr("data-maxdate");
            var types = $(element).attr("data-types");
            minDate=minDate && minDate !== undefined ? minDate :'1900-1-1';
            maxDate=maxDate && maxDate !== undefined ? maxDate :'2099-12-31';
            types=types && types !== undefined ? types :'date';
            laydate.render({
            elem: element,
            btns: ['clear', 'confirm'],
            type:types,
            theme: '#2c9cf0',
            range:ranges,
            min:minDate,
            max:maxDate,
            zIndex: 99999999999,
            done: function(value, date, endDate){
                callback && callback(value, date, endDate);
            }
            });
        })
    }
    /**
     * layDate日期 选择时间时分
     * @param {
     *      element     触发的dom
     *      range       Boolean true为双日历
     *      callback    点击确定的回调
     * }
     * @DateTime 2018-11-15
     */
    util.layDate1=function(element,ranges,callback){
        require(['laydate'],function(laydate){
            ranges && ranges !== undefined ? ranges : false;
            var types = $(element).attr("data-types");
            types=types && types !== undefined ? types :'date';
                laydate.render({
                elem: element,
                type:types,
                theme: '#2c9cf0',
                range:ranges,
                format: 'HH:mm',
                zIndex: 99999999999,
                ready: formatminutes,
                done: function(value, date, endDate){
                    callback && callback(value, date, endDate);
                }
            });
           function  formatminutes(date) {
            $($(".laydate-time-list li ol")[2]).find("li").remove();  //清空秒
           }   
        })
    }
    // 日期不能选择今天
    util.layDate2=function(element,ranges,callback){
        require(['laydate'],function(laydate){
            ranges && ranges !== undefined ? ranges : false;
            var minDate = $(element).attr("data-mindate");
            var maxDate = $(element).attr("data-maxdate");
            var types = $(element).attr("data-types");
            minDate=minDate && minDate !== undefined ? minDate :'1900-1-1';
            maxDate=maxDate && maxDate !== undefined ? maxDate :'2099-12-31';
            types=types && types !== undefined ? types :'date';
            laydate.render({
            elem: element,
            btns:['clear', 'confirm'],
            type:types,
            theme: '#2c9cf0',
            range:ranges,
            min:minDate,
            max:maxDate,
            zIndex: 99999999999,
            done: function(value, date, endDate){
                callback && callback(value, date, endDate);
            }
            });
        })
    }

    //时间戳转时间类型date
    util.timeStampTurnDate=function(timeStamp){
        if(timeStamp > 0){
            var date = new Date();
            date.setTime(timeStamp * 1000);
            var y = date.getFullYear();
            var m = date.getMonth() + 1;
            m = m < 10 ? ('0' + m) : m;
            var d = date.getDate();
            d = d < 10 ? ('0' + d) : d;
            return y + '-' + m + '-' + d;
        }else{
            return "";
        }
    };

    util.DateTurnTime = function(date){
        //console.log(date)
        var time = new Date(date);
        return time.getTime()
    }

    util.copy2=function(element,element2){
        $('body').on('click',element,function(){
            var Url2=document.getElementById(element2);
            Url2.select(); // 选择对象
            document.execCommand("Copy"); // 执行浏览器复制命令
            util.message("复制成功!",'success');
        })
    }
    util.tips = function(){
            // 初始化
        $('[data-toggle="tooltip"]').tooltip("destroy").tooltip({
            container: $(document.body)
        });
    }
    //取最大值
    util.arrayMax=function(array){
        return Math.max.apply(Math,array);
    }


    /**
     * 初始化插件
     */
    // 日期插件
    // if($('div').hasClass('date-input-group')){
    //     util.date();
    // }
    // 复制粘贴
    if($('a').hasClass('copy')){
        util.copy();
    }
    // 表单验证
    // if($('form').hasClass('form-validate')){
    //     if($('.form-validate').length > 0){
    //         $('.form-validate').each(function(i,e){
    //             util.validate($(this))
    //         })
    //     }
    // }
    // 多选图片
    $('body').on('click','[data-toggle="multiPicture"]',function(){
        var storage = new util.Storage('session');
        var _this = this;
        storage.setItem('multiple','1');
        util.pictureDialog(_this,true);
        var count1 = $(this).siblings("input[name='upload_img_id']").length;
        storage.setItem('count1',count1);
    })
    // 单选图片
    $('body').on('click','[data-toggle="singlePicture"]',function(){
        var _this = this;
        util.pictureDialog(_this)
    })
    // 单选视频
    $('body').on('click','[data-toggle="singleVideo"]',function(){
        var _this = this;
        util.videoDialog(_this)
    })


    // 移除单个图片视频
    $('body').on('click','.close-box .icon',function(e){
        e && e.stopPropagation ? e.stopPropagation() : window.event.cancelBubble = true;
        var isRequired = $(this).parents('.picture-list').attr('required')
        var pictureName = $(this).parents('.picture-list').attr('id')
        var type = 'singlePicture';
        if(pictureName === 'pc_video_adv'){
            type = 'singleVideo';
        }
        var visibility = '<input type="text" class="visibility" required data-visi-type="'+type+'" name="picture-'+pictureName+'">'
        var img = '<a href="javascript:;" class="plus-box" data-toggle="'+type+'"><i class="icon icon-plus"></i></a>'+(isRequired?visibility:'');
        $(this).parents('.picture-list').html(img);
    })




    // 移除多个图片
    $('body').on('click','#goods_pic_list .icon',function(e){
        e && e.stopPropagation ? e.stopPropagation() : window.event.cancelBubble = true;
        $(this).parent().next().remove();
        $(this).parent().remove();
    })
    //图片移除事件(分销分红等级设置)
    $('body').on('click','.close-box1 .icon',function(e){
        e && e.stopPropagation ? e.stopPropagation() : window.event.cancelBubble = true;
        var domCheckbox = $(this).parents('.control-group').siblings('.control-label').find('input');
        if(domCheckbox.is(':checked')){
           var img = '<a href="javascript:;" class="plus-box search_goods" ><i class="icon icon-plus"></i></a><input required="required" type="text" class="visibility" data-visi-type="singlePicture" name="picture-Logo">';
        }else{
           var img = '<a href="javascript:;" class="plus-box search_goods" ><i class="icon icon-plus"></i></a><input type="text" class="visibility" data-visi-type="singlePicture" name="picture-Logo">';
        }
        
        $(this).parents('.picture-list').siblings('#selectgoods_id').val('');
        $(this).parents('.picture-list').siblings('.selectid').html('');
        $(this).parents('.picture-list').html(img);
    })

    // 富文本编辑器
    if($("div[id^='UE-']").length > 0){
        $("div[id^='UE-']").each(function(i,e){
            var elm = $(this).attr('id');
            util.ueditor(elm);
        })
    }

    // 微信卡券单选图片
    $('body').on('click','[data-toggle="wxCard_singlePicture"]',function(){
        var _this = this;
        util.pictureDialog(_this,false,function(content){
            var img = '<a href="javascript:;" class="close-box2"><i class="icon icon-danger" style="margin-right:10px;" title="删除"></i><img src='+content.path[0]+'></a><input type="hidden" name="upload_wxCard_img_id" value="'+content.id[0]+'">';
            $(_this).parent().html(img);
            $('.wxCard_imagesTexts').find('img').attr('src',content.path[0]);
        })
    })
    //微信卡券移除图片
    $('body').on('click','.close-box2 .icon',function(e){
        e && e.stopPropagation ? e.stopPropagation() : window.event.cancelBubble = true;
        var isRequired = $(this).parents('.picture-list').attr('required')
        var pictureName = $(this).parents('.picture-list').attr('id')
        var visibility = '<input type="text" class="visibility" required data-visi-type="singlePicture" name="picture-'+pictureName+'">'
        var img = '<a href="javascript:;" class="plus-box" data-toggle="wxCard_singlePicture"><i class="icon icon-plus"></i></a>'+(isRequired?visibility:'');
        $(this).parents('.picture-list').html(img);
        $('.wxCard_imagesTexts').find('img').attr('src','http://iph.href.lu/850x350');
    });

    util.alert1 = function(titles,content){
        $.alert({
            title: titles,
            content: content,
            animation: 'top',
            closeAnimation: 'bottom',
            animateFromElement: false,
            backgroundDismiss: true,
            buttons: {
                '关闭': function () {
                    // util.message('danger','你点击了取消')
                }
            }
        })
    }

    $(document).on("keypress", ":input:not(textarea)", function(event) { 
        if (event.keyCode == 13) {
            event.preventDefault();
        }
    });
    $(document).on('click','.switch-inline input[type="checkbox"]',function(e){
        var async = $(this).attr('async')
        if(async) return false
    })

    // 两个确定按钮
    /**
     alert确认窗口
     callback:点击确认的回调
     */
    util.alert2buttons = function(content,callback){
        $.alert({
            title:'提示',
            content:content,
            animation: 'top',
            closeAnimation: 'bottom',
            animateFromElement: false,
            backgroundDismiss: true,
            buttons: {
                '发布直播版本': {
                    btnClass: 'btn-primary',
                    action: function () {
                        callback(1)
                    }
                },
                '发布无直播版本': {
                    btnClass: 'btn-primary',
                    action: function () {
                        callback(2)
                    }
                },
                '取消': function () {
                    // util.message('danger','你点击了取消')
                }
            }
        })
    }

    return util;
})

