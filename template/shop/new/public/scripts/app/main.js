require.config({
  baseUrl: "../../template/shop/new/public/scripts/app",
  paths: {
    jquery: "../lib/jquery.min",
    "jquery.pagination": "../lib/jquery.pagination",
    layer: "../lib/layer",
    tabPlugin:"../lib/jquery.tabPlugin",
    "distpicker.data":"../lib/distpicker.data",
    distpicker:"../lib/distpicker",
    starScore:"../lib/starScore",
    migrate:"../lib/jquery-migrate-1.2.1.min",
    cropper:"../lib/cropper",
    sitelogo:"../lib/sitelogo",
    html2canvas:"../lib/html2canvas.min",
    boostrap:"../lib/bootstrap-3.3.4",
    jquery2:"../lib/jquery-2.0.0.min",
    swiper:"../lib/swiper.min",
    ljsGlasses:"../lib/ljsGlasses",
    commentImg:"../lib/commentImg",
    // Validator:"../lib/bootstrapValidator.min",
    'jquery.validate':'../lib/jquery.validate.min',
    'domReady':'../lib/yanzheng/domReady',
    'validate.methods':'../lib/yanzheng/validate-methods',
    messages_zh:'../lib/messages_zh',
    laydate:'../lib/laydate',
    lazyload:'../lib/jquery.lazyload.min',
    map:'https://api.map.baidu.com/api?v=2.0&ak=t16W0CsDyfV8QjlSgS17lgsI',
    linq:"../lib/linq.min",
  },
    waitSeconds: 0,
    map: {
        '*': {
            'css': '../lib/css.min'
        }
    },
  shim: {
    "jquery.pagination": {
      deps: ["jquery",'css!../../css/pagination.css']
    },
    layer: {
      deps: ["jquery",'css!../../css/layer.css']
    },
    tabPlugin: {
      deps: ["jquery"]
    },
    starScore: {
      deps: ["jquery"]
    },
    migrate: {
      deps: ["jquery"]
    },
    cropper: {
      deps: ["jquery2",'css!../../css/cropper.min.css']
    },
    boostrap: {
      deps: ["jquery2"]
    },
    sitelogo: {
      deps: ["jquery2","cropper","html2canvas"]
    },
    html2canvas: {
      deps: ["jquery2"]
    },
    distpicker: {
      deps: ["jquery","distpicker.data"]
    },
    swiper: {
      deps: ["jquery",'css!../../css/swiper.min.css']
    },
    ljsGlasses: {
      deps: ["jquery"]
    },
    commentImg: {
      deps: ["jquery"]
    },
    // Validator: {
    //   deps: ["jquery","boostrap"]
    // },
    "jquery.validate": {
      deps: ["jquery"]
    },
    messages_zh: {
      deps: ["jquery","jquery.validate"]
    },
    lazyload: {
      deps: ["jquery"]
    },
    map: {
      deps: ["jquery"]
    }
  }
});





