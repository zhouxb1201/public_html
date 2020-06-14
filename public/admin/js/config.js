var version = +new Date()
require.config({
    baseUrl: '/public/admin/js',
    // urlArgs:'v='+(new Date()).getTime(),
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
        'layer':'../../static/lib/drag/layer',
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
            deps:['../js/wxMenu/drag-arrange','css!../css/wx_menu.css']
        },
        'jquery-ui':{
            exports: "$",
            deps:['jquery','css!../lib/jquery/css/jquery-ui.min.css']
        },
        'custom':{
            deps:['css!../css/custom.css']
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

    }

})