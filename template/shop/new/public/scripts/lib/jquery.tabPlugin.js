;(function ($, win, undefined) {
      $.fn.tab = function (options) {

        var defaults = {
          currentClass: 'active',
          tabNav: '.tabs>li',
          tabContent: '.tab_container>div',
          eventType: 'click'
        };

        var options = $.extend({}, defaults, options);

        this.each(function () {
          var _this = $(this);
          _this.find(options.tabNav).on(options.eventType, function () {
            $(this).addClass(options.currentClass).siblings().removeClass(options.currentClass);
            var index = $(this).index();
            _this.find(options.tabContent).eq(index).show().siblings().hide();
          })
        });

        return this;
      }
    })(jQuery, window);