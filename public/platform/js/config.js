var version = +new Date()
require.config({
    baseUrl: PLATFORMJS,
    urlArgs:'v='+(new Date()).getTime(),
    paths:{
        'domReady':'../lib/domReady',
        'paginator':'../lib/paginator/paginator',
        'jquery':'../lib/jquery/js/jquery.min',
        'bootstrap':'../lib/bootstrap/js/bootstrap.min',
        'jconfirm':'../lib/jquery/js/jquery-confirm.min',
        'jquery.validate':'../lib/jquery/js/jquery.validate.min',
        'validate.methods':'validate-methods',
        'moment':'../lib/daterangepicker/moment.min',
        'daterangepicker':'../lib/daterangepicker/daterangepicker',
        'echarts':'../lib/echarts/echarts.common.min',
        'walden':'../lib/echarts/walden',
        'nestable':'../lib/jquery/js/jquery.nestable',
        'treegrid':'../lib/jquery/js/jquery.treegrid.min',
        'ueditor':'../lib/ueditor/ueditor.all.min',
        'ueditor.config':'../lib/ueditor/ueditor.config',
        'ueditor.lang':'../lib/ueditor/lang/zh-cn/zh-cn',
        'ueditor.ZeroClipboard':'../lib/ueditor/third-party/zeroclipboard/ZeroClipboard.min',
        'colorpicker':'../lib/spectrum/spectrum',
        'clipboard':'../lib/chipboard/clipboard.min',
        'wxMenu':'../js/wxMenu/wx_menu',
        'jquery-ui':'../lib/jquery/js/jquery-ui.min',
        'tpl':'../lib/tmodjs',
        'fileupload':'../lib/fileupload/jquery.fileupload',
        'layer':'./indexDecorate/layer',
        'swiper':'../lib/swiper/js/swiper.min',
        'laydate':'../lib/laydate/laydate',
        // 'layer':'../../static/lib/drag/layer',
        'pagination':'../lib/pagination/jquery.pagination',
        'ajax_file_upload':'../lib/ajax_file_upload/ajax_file_upload',
        'contextmenu':'../lib/contextmenu/jquery.contextmenu',
        'jqueryForm':'../lib/jqueryForm/jquery.form',
        'transport_jquery':'./indexDecorate/transport_jquery',
        'jquery.json':'./indexDecorate/jquery.json',
        'jquery-ui1':'./indexDecorate/jquery-ui.min',
        'mousewheel':'../lib/preview-img/jquery.mousewheel.min',
        'pictureViewer':'../lib/preview-img/pictureViewer',
        'videoViewer':'../lib/preview-img/videoViewer',
    },
    waitSeconds: 0,
    map: {
        '*': {
            'css': '../lib/css.min'
        }
    },
    shim:{
        'bootstrap': {
            exports: "$",
            deps: ['jquery']
        },
        'jconfirm':{
            exports: "$",
            deps: ['jquery','bootstrap','css!../lib/jquery/css/jquery-confirm.min.css']
        },
        'daterangepicker':{
            deps:['moment','css!../lib/daterangepicker/daterangepicker.css']
        },
        'ueditor':{
            deps:['ueditor.config','css!../lib/ueditor/themes/default/css/ueditor.min.css']
        },
        'ueditor.ZeroClipboard':{
            deps: ['ueditor']
        },
        'ueditor.lang':{
            deps: ['ueditor']
        },
        'colorpicker':{
            deps:['css!../lib/spectrum/spectrum.css']
        },
        'wxMenu':{
            deps:['../js/wxMenu/drag-arrange']
        },
        'jquery-ui':{
            exports: "$",
            deps:['jquery','css!../lib/jquery/css/jquery-ui.min.css']
        },
        'custom':{
            deps:['css!../css/custom.css']
        },
        'admin_custom':{
            deps:['css!../css/custom.css','css!../css/platform.css']
        },
        'app_custom':{
            deps:['css!../css/custom.css']
        },
        'mp_custom':{
            deps:['css!../css/custom.css']
        },
        'admin_mp_custom':{
            deps:['css!../css/custom.css','css!../css/platform.css']
        },
        'paginator':{
            exports: "$",
            deps: ['jquery']
        },
        'fileupload':{
            deps:['jquery','../lib/fileupload/jquery.ui.widget','../lib/fileupload/jquery.iframe-transport']
        },
        'layer':{
            deps: ['jquery']
        },
        'swiper':{
            deps: ['jquery']
        },
        'walden':{
            deps: ['echarts']
        },
        'pagination':{
            deps: ['jquery','css!../lib/pagination/pagination.css']
        },
        'ajax_file_upload':{
            deps: ['jquery']
        },
        'contextmenu':{
            deps: ['jquery','css!../lib/contextmenu/jquery.contextmenu.css']
        },
        'jqueryForm':{
            deps: ['jquery']
        },
        'transport_jquery':{
            deps: ['jquery','jquery.json']
        },
        'jquery.json':{
            deps: ['jquery']
        },
        'jquery-ui1':{
            deps: ['jquery']
        },

    }

})