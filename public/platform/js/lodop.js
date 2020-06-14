define(['jquery'], function ($) {
    try{
        var lodop = getCLodop()

        lodop.set_print_pagesize = function(intOrient, PageWidth,PageHeight,strPageName){
            lodop.SET_PRINT_PAGESIZE(intOrient, PageWidth,PageHeight,strPageName)
        }

        // 设置纸张距离，大小
        //from_top 边框离纸顶端 px
        //from_left 边框离纸左端 px
        //width 宽 px
        //height 高 px
        //line_style 0-实线 1-破折线 2-点线 3-点划线 4-双点划线
        //line_width 线宽 px
        lodop.rect = function (from_top, from_left, width, height, line_style, line_width) {
            lodop.ADD_PRINT_RECT(from_top, from_left, width, height, line_style, line_width)
        }

        lodop.print_inita = function (from_top, from_left, width, height, print_name) {
            lodop.PRINT_INITA(from_top, from_left, width, height, print_name)
        }

        lodop.start = function () {
            lodop.PRINT_INIT('print_task');
        }

        // 添加背景图
        lodop.add_bg = function (url) {
            lodop.ADD_PRINT_SETUP_BKIMG("<img border='0' src='" + url + "' />");
        }

        // 预览显示背景图片
        lodop.show_model = function () {
            lodop.SET_SHOW_MODE("BKIMG_PRINT", 1)
        }

        //添加网络图片
        //url 图片资源
        //from_top 边框离纸顶端 px
        //from_left 边框离纸左端 px
        //width 宽 px
        //height 高 px
        lodop.add_url_image = function (url, from_top, from_left, width, height) {
            from_top === undefined ? from_top = 0 : from_top;
            from_left === undefined ? from_left = 0 : from_left;
            width === undefined ? width = '100%' : width;
            height === undefined ? height = '100%' : height;
            console.log(from_top, from_left, width, height)
            lodop.ADD_PRINT_IMAGE(from_top, from_left, width, height, "<img border='0' src='" + url + "' />");
        }

        // 添加本地图片
        //from_top 边框离纸顶端 px
        //from_left 边框离纸左端 px
        //width 宽 px
        //height 高 px
        //src 本地图片路径
        lodop.add_local_image = function (from_top, from_left, width, height, line_style, line_width, src) {
            from_top === undefined ? from_top = 0 : from_top;
            from_left === undefined ? from_left = 0 : from_left;
            width === undefined ? width = '100%' : width;
            height === undefined ? height = '100%' : height;
            lodop.ADD_PRINT_IMAGE(from_top, from_left, width, height, line_style, line_width, src);
        }

        // 设置图片显示
        //from_top 边框离纸顶端 px
        //from_left 边框离纸左端 px
        //width 宽 px
        //height 高 px
        lodop.set_show_mode = function (from_top, from_left, width, height) {
            // LODOP.SET_SHOW_MODE("BKIMG_LEFT",1);
            // LODOP.SET_SHOW_MODE("BKIMG_TOP",1);
            // LODOP.SET_SHOW_MODE("BKIMG_WIDTH","183mm");
            // LODOP.SET_SHOW_MODE("BKIMG_HEIGHT","99mm"); //这句可不加，因宽高比例固定按原图的
            lodop.SET_SHOW_MODE("BKIMG_IN_PREVIEW",1);
        }

        // 添加文本
        //from_top 边框离纸顶端 px
        //from_left 边框离纸左端 px
        //width 宽 px
        //height 高 px
        //text 文案
        lodop.add_item_a = function (item_name, from_top, from_left, width, height, text) {
            lodop.ADD_PRINT_TEXTA(item_name, from_top, from_left, width, height, text);
        }

        // 设置单个文本样式
        // index 第index个文本 序号设0表示最新对象
        // key FontSize,FontName,Alignment,Bold,FontColor
        // value
        //      FontSize:12(单位pt)
        //      FontName:隶书
        //      Alignment:默认左对齐,Alignment:1:两端对齐,Alignment:2:居中对齐,Alignment:3:右端对齐,
        //      Bold:1:加粗
        //      FontColor:#000000 16进制
        lodop.set_print_style_a = function (index, key, value) {
            lodop.SET_PRINT_STYLEA(index, key, value);
        }

        // 一次性设置FontSize,FontName,Alignment,Bold,FontColor
        // {FontSize:12,FontName:'隶书',Alignment:2,Bold:1,FontColor:#000000}
        lodop.set_print_style_a_all = function (index, data) {
            $.each(data, function (k, v) {
                lodop.SET_PRINT_STYLEA(index, k, v);
            })
        }

        // 设置所有文本样式
        lodop.set_print_style = function (key, value) {
            lodop.SET_PRINT_STYLE(key, value);
        }

        lodop.preview = function () {
            lodop.PREVIEW()
        }

        lodop.bg_print = function () {
            lodop.BKIMG_PRINT()
        }

        lodop.print = function () {
            lodop.PRINT();
            lodop.SET_PRINT_MODE("AUTO_CLOSE_PREWINDOW", true);
        }

        lodop.print_design = function(){
            lodop.PRINT_DESIGN();
        }

        lodop.add_print_html = function (html,from_top, from_left, width, height) {
            from_top === undefined ? from_top = 0 : from_top;
            from_left === undefined ? from_left = 0 : from_left;
            width === undefined ? width = '100%' : width;
            height === undefined ? height = '100%' : height;
            lodop.ADD_PRINT_HTML(from_top, from_left, width, height, html)
        }


        return lodop;
    }catch (e) {
        console.log(e)
    }
})