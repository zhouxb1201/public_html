
define(["jquery","layer"], function ($) {
    var common = {};
    // 分页
    common.Pages = function (select, totalData, pageCount, current, callbacks) {
        require(["jquery.pagination"], function () {
            $(".M-box3").pagination({
                totalData: totalData,
                current: current,
                pageCount: pageCount,
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
        });
    };
    // tab切换
    common.tabToggle = function () {
        require(["jquery.pagination"], function () {
            $(".tab_content").hide();
            $("ul.tabs li:first")
                    .addClass("active")
                    .show();
            $(".tab_content:first").show();
            $("ul.tabs li").click(function () {
                $("ul.tabs li").removeClass("active");
                $(this).addClass("active");
                $(".tab_content").hide();
                var activeTab = $(this)
                        .find("a")
                        .attr("href");
                $(activeTab).fadeIn();
                return false;
            });
        });
    };
    common.timeStampTurnTime = function (timeStamp) {
        if (timeStamp > 0) {
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
            return y + '-' + m + '-' + d + ' ' + h + ':' + minute + ':' + second;
        } else {
            return "";
        }
    };
    return common;
});
