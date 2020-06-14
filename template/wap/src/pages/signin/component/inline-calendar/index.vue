<template>
  <div class="inline-calendar" :class="{'is-weekend-highlight': highlightWeekend}">
    <div class="calendar-header" v-show="!hideHeader">
      <div class="calendar-year">
        <span @click="go(year - 1, month)">
          <a class="year-prev vux-prev-icon" href="javascript:"></a>
        </span>
        <a class="calendar-year-txt calendar-title" href="javascript:">{{year}}</a>
        <span class="calendar-header-right-arrow" @click="go(year + 1, month)">
          <a class="year-next vux-next-icon" href="javascript:"></a>
        </span>
      </div>

      <div class="calendar-month">
        <span @click="prev">
          <a class="month-prev vux-prev-icon" href="javascript:"></a>
        </span>
        <a class="calendar-month-txt calendar-title" href="javascript:">{{months[month]}}</a>
        <span @click="next" class="calendar-header-right-arrow">
          <a class="month-next vux-next-icon" href="javascript:"></a>
        </span>
      </div>
    </div>

    <table>
      <thead v-show="!hideWeekList">
        <tr>
          <th
            v-for="(week, index) in _weeksList"
            class="week"
            :class="`is-week-list-${index}`"
          >{{ week }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(day,k1) in days">
          <td
            v-for="(child,k2) in day"
            :data-date="formatDate(year, month, child)"
            :data-current="currentValue"
            :class="buildClass(k2, child)"
            @click="select(k1, k2, child)"
          >
            <slot
              :year="year"
              :month="month"
              :child="processDateItem(child)"
              :date="processDateItem(child)"
              class-name="vux-calendar-each-date"
              :row="k1"
              :col="k2"
              :raw-date="formatDate(year, month, child)"
              :show-date="replaceText(child.day, formatDate(year, month, child))"
              :is-show="showChild(year, month, child)"
              name="each-day"
            >
              <span
                class="vux-calendar-each-date"
                :style="getMarkStyle(child)"
                v-show="showChild(year, month, child)"
              >
                <span
                  class="vux-calendar-each-date-text"
                >{{ replaceText(child.day, formatDate(year, month, child)) }}</span>
                <span
                  class="vux-calendar-top-tip"
                  v-if="isShowTopTip(child)"
                  :style="isShowTopTip(child, 'style')"
                >
                  <span>{{ isShowTopTip(child, 'text') }}</span>
                </span>
                <div v-html="renderFunction(k1, k2, child)" v-show="showChild(year, month, child)"></div>
              </span>
              <span class="vux-calendar-dot" v-if="isShowBottomDot(child)"></span>
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import format from "./format";
import { getDays, zero, isBetween } from "./util";
import props from "./props";
import calendarMarksMixin from "./calendar-marks";

export default {
  name: "inline-calendar",
  mixins: [calendarMarksMixin],
  props: props(),
  data() {
    return {
      multi: false,
      year: 0,
      month: 0,
      days: [],
      today: format(new Date(), "YYYY-MM-DD"),
      months: ["1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12"],
      currentValue: "",
      viewChangeEventCount: -1
    };
  },
  created() {
    this.currentValue = this.value;
    this.multi =
      Object.prototype.toString.call(this.currentValue) === "[object Array]";

    if (this.multi) {
      for (let i = 0; i < this.currentValue.length; i++) {
        this.$set(this.currentValue, i, this.convertDate(this.currentValue[i]));
      }
    } else {
      this.currentValue = this.convertDate(this.currentValue);
    }

    this.render(this.renderMonth[0], this.renderMonth[1] - 1);
  },
  computed: {
    _weeksList() {
      if (this.weeksList && this.weeksList.length) {
        return this.weeksList;
      }
      if (!this.weeksList || !this.weeksList.length) {
        // tip for older vux-loader
        return ["日", "一", "二", "三", "四", "五", "六"];
      }
    },
    _replaceTextList() {
      const rs = {};
      for (let i in this.replaceTextList) {
        rs[this.convertDate(i)] = this.replaceTextList[i];
      }
      return rs;
    },
    currentYearMonth() {
      return this.year + this.month;
    }
  },
  watch: {
    value(val) {
      this.currentValue = this.multi ? val : this.convertDate(val);
    },
    currentValue(val, oldVal) {
      this.$emit("input", this.currentValue);
      this.$emit("on-change", this.currentValue);

      if (this.renderOnValueChange) {
        // if on the same year+month, stay quiet
        if (val && oldVal && val.slice(0, 7) === oldVal.slice(0, 7)) {
          return;
        }
        this.render(null, null, "value change");
      }
    },
    renderFunction() {
      this.render(this.year, this.month, this.currentValue);
    },
    renderMonth(val) {
      if (val && val.length === 2) {
        this.render(val[0], val[1] - 1);
      }
    },
    returnSixRows(val) {
      this.render(this.year, this.month);
    },
    startDate(val) {
      this.render(this.year, this.month);
    },
    endDate(val) {
      this.render(this.year, this.month);
    },
    disablePast() {
      this.render(this.year, this.month);
    },
    disableFuture() {
      this.render(this.year, this.month);
    },
    currentYearMonth() {
      const lastLine = this.days[this.days.length - 1];
      const lastDate = lastLine[lastLine.length - 1];

      let days = [];
      this.days.forEach(line => {
        days = days.concat(line);
      });
      days = days.filter(date => {
        return !date.isLastMonth && !date.isNextMonth;
      });
      this.viewChangeEventCount++;
      this.$emit(
        "on-view-change",
        {
          year: this.year,
          month: this.month + 1,
          firstDate: this.days[0][0].formatedDate,
          lastDate: lastDate.formatedDate,
          firstCurrentMonthDate: days[0].formatedDate,
          lastCurrentMonthDate: days[days.length - 1].formatedDate,
          allDates: this.days
        },
        this.viewChangeEventCount
      );
    }
  },
  methods: {
    processDateItem(item) {
      const temp = JSON.parse(JSON.stringify(item));
      temp.isDisabled = this.isDisabled(item);
      temp.isBetween = this.isBetween(item.formatedDate);
      return temp;
    },
    isBetween(formatedDate) {
      return isBetween(
        formatedDate,
        this.disablePast,
        this.disableFuture,
        this.startDate,
        this.endDate
      );
    },
    isDisabled(date) {
      let disabled = !this.isBetween(date.formatedDate);
      disabled = disabled || (date.isWeekend && this.disableWeekend);
      disabled = disabled || date.isNextMonth || date.isLastMonth;

      if (!this.disableDateFunction) {
        return disabled;
      } else {
        const value = this.disableDateFunction(date);
        if (typeof value === "undefined") {
          return disabled;
        } else {
          return value;
        }
      }
    },
    switchViewToToday() {
      const today = new Date();
      this.render(today.getFullYear(), today.getMonth());
    },
    switchViewToCurrentValue() {
      if (!this.currentValue || (this.multi && !this.currentValue.length)) {
        return;
      }

      let value;
      let year;
      let month;
      if (typeof this.currentValue === "string") {
        value = this.currentValue;
      } else {
        value = this.currentValue[0];
      }
      const splitList = value.split("-");
      year = parseInt(splitList[0], 10);
      month = parseInt(splitList[1], 10);
      this.switchViewToMonth(year, month);
    },
    switchViewToMonth(year, month) {
      if (!year || !month) {
        return this.switchViewToToday();
      }
      this.render(year, month - 1);
    },
    getDates() {
      return this.days;
    },
    replaceText(day, formatDay) {
      let text = this._replaceTextList[formatDay];
      if (!text && typeof text === "undefined") {
        return day;
      } else {
        return text;
      }
    },
    convertDate(date) {
      return date === "TODAY" ? this.today : date;
    },
    buildClass(index, child) {
      let isCurrent = false;
      if (!child.isLastMonth && !child.isNextMonth) {
        if (this.multi && this.currentValue.length > 0) {
          isCurrent =
            this.currentValue.indexOf(
              this.formatDate(this.year, this.month, child)
            ) > -1;
        } else {
          isCurrent =
            this.currentValue === this.formatDate(this.year, this.month, child);
        }
      }
      const className = {
        current: isCurrent,
        "is-disabled": this.isDisabled(child),
        "is-today": child.isToday,
        [`is-week-${index}`]: true
      };
      return className;
    },
    render(year, month, force = false) {
      let data = null;
      const value = this.multi
        ? this.currentValue[this.currentValue.length - 1]
        : this.currentValue;
      data = getDays({
        year: year,
        month: month,
        value,
        rangeBegin: this.convertDate(this.startDate),
        rangeEnd: this.convertDate(this.endDate),
        returnSixRows: this.returnSixRows,
        disablePast: this.disablePast,
        disableFuture: this.disableFuture
      });

      if (this.year === data.year && this.month === data.month && !force) {
        return;
      }
      this.year = data.year;
      this.month = data.month;
      this.days = data.days;
    },
    formatDate: (year, month, child) => {
      return [year, zero(child.month + 1), zero(child.day)].join("-");
    },
    prev() {
      if (this.month === 0) {
        this.month = 11;
        this.year = this.year - 1;
      } else {
        this.month = this.month - 1;
      }
      this.render(this.year, this.month, true);
    },
    next() {
      if (this.month === 11) {
        this.month = 0;
        this.year = this.year + 1;
      } else {
        this.month = this.month + 1;
      }
      this.render(this.year, this.month, true);
    },
    go(year, month) {
      this.render(year, month, true);
    },
    select(k1, k2, data) {
      if (this.disableSelect) {
        return;
      }
      if (data.isLastMonth && !this.showLastMonth) {
        return;
      }
      if (data.isNextMonth && !this.showNextMonth) {
        return;
      }
      if (!this.isBetween(data.formatedDate)) {
        return;
      }

      if (this.isDisabled(data)) {
        // not in range
        if (!this.isBetween(data.formatedDate)) {
          return;
        } else {
          // in range but disabled by disableDateFunction
          if (this.disableDateFunction && this.disableDateFunction(data)) {
            return;
          }
          if (data.isWeekend && this.disableWeekend) {
            return;
          }
        }
      }
      let _currentValue = null;
      if (!data.isLastMonth && !data.isNextMonth) {
        this.days[k1][k2].current = true;
        _currentValue = [
          this.year,
          zero(this.month + 1),
          zero(this.days[k1][k2].day)
        ].join("-");
      } else {
        _currentValue = [data.year, zero(data.month + 1), zero(data.day)].join(
          "-"
        );
      }
      if (this.multi) {
        let index = this.currentValue.indexOf(_currentValue);
        if (index > -1) {
          this.currentValue.splice(index, 1);
        } else {
          this.currentValue.push(_currentValue);
        }
      } else {
        this.currentValue = _currentValue;
        this.$emit("on-select-single-date", this.currentValue);
      }

      if (this.multi) {
        for (let i = 0; i < this.currentValue.length; i++) {
          this.$set(
            this.currentValue,
            i,
            this.convertDate(this.currentValue[i])
          );
        }
      } else {
        this.currentValue = this.convertDate(this.currentValue);
      }

      if (this.renderOnValueChange) {
        this.render(null, null);
      }
    },
    showChild(year, month, child) {
      if (this.replaceText(child.day, this.formatDate(year, month, child))) {
        return (
          (!child.isLastMonth && !child.isNextMonth) ||
          (child.isLastMonth && this.showLastMonth) ||
          (child.isNextMonth && this.showNextMonth)
        );
      } else {
        return false;
      }
    }
  }
};
</script>

<style scope>
/**
* actionsheet
*/

/**
* en: primary type text color of menu item
* zh-CN: 菜单项primary类型的文本颜色
*/

/**
* en: warn type text color of menu item
* zh-CN: 菜单项warn类型的文本颜色
*/

/**
* en: default type text color of menu item
* zh-CN: 菜单项default类型的文本颜色
*/

/**
* en: disabled type text color of menu item
* zh-CN: 菜单项disabled类型的文本颜色
*/

/**
* datetime
*/

/**
* tabbar
*/

/**
* tab
*/

/**
* dialog
*/

/**
* en: title and content's padding-left and padding-right
* zh-CN: 标题及内容区域的 padding-left 和 padding-right
*/

/**
* x-number
*/

/**
* checkbox
*/

/**
* check-icon
*/

/**
* Cell
*/

/**
* Mask
*/

/**
* Range
*/

/**
* Tabbar
*/

/**
* Header
*/

/**
* Timeline
*/

/**
* Switch
*/

/**
* Button
*/

/**
* en: border radius
* zh-CN: 圆角边框
*/

/**
* en: font color
* zh-CN: 字体颜色
*/

/**
* en: margin-top value between previous button, not works when there is only one button
* zh-CN: 与相邻按钮的 margin-top 间隙，只有一个按钮时不生效
*/

/**
* en: button height
* zh-CN: 按钮高度
*/

/**
* en: the font color in disabled
* zh-CN: disabled状态下的字体颜色
*/

/**
* en: the font color in disabled
* zh-CN: disabled状态下的字体颜色
*/

/**
* en: font size
* zh-CN: 字体大小
*/

/**
* en: the font size of the mini type
* zh-CN: mini类型的字体大小
*/

/**
* en: the line height of the mini type
* zh-CN: mini类型的行高
*/

/**
* en: the background color of the warn type
* zh-CN: warn类型的背景颜色
*/

/**
* en: the background color of the warn type in active
* zh-CN: active状态下，warn类型的背景颜色
*/

/**
* en: the background color of the warn type in disabled
* zh-CN: disabled状态下，warn类型的背景颜色
*/

/**
* en: the background color of the default type
* zh-CN: default类型的背景颜色
*/

/**
* en: the font color of the default type
* zh-CN: default类型的字体颜色
*/

/**
* en: the background color of the default type in active
* zh-CN: active状态下，default类型的背景颜色
*/

/**
* en: the font color of the default type in disabled
* zh-CN: disabled状态下，default类型的字体颜色
*/

/**
* en: the background color of the default type in disabled
* zh-CN: disabled状态下，default类型的背景颜色
*/

/**
* en: the font color of the default type in active
* zh-CN: active状态下，default类型的字体颜色
*/

/**
* en: the background color of the primary type
* zh-CN: primary类型的背景颜色
*/

/**
* en: the background color of the primary type in active
* zh-CN: active状态下，primary类型的背景颜色
*/

/**
* en: the background color of the primary type in disabled
* zh-CN: disabled状态下，primary类型的背景颜色
*/

/**
* en: the font color of the plain primary type
* zh-CN: plain的primary类型的字体颜色
*/

/**
* en: the border color of the plain primary type
* zh-CN: plain的primary类型的边框颜色
*/

/**
* en: the font color of the plain primary type in active
* zh-CN: active状态下，plain的primary类型的字体颜色
*/

/**
* en: the border color of the plain primary type in active
* zh-CN: active状态下，plain的primary类型的边框颜色
*/

/**
* en: the font color of the plain default type
* zh-CN: plain的default类型的字体颜色
*/

/**
* en: the border color of the plain default type
* zh-CN: plain的default类型的边框颜色
*/

/**
* en: the font color of the plain default type in active
* zh-CN: active状态下，plain的default类型的字体颜色
*/

/**
* en: the border color of the plain default type in active
* zh-CN: active状态下，plain的default类型的边框颜色
*/

/**
* en: the font color of the plain warn type
* zh-CN: plain的warn类型的字体颜色
*/

/**
* en: the border color of the plain warn type
* zh-CN: plain的warn类型的边框颜色
*/

/**
* en: the font color of the plain warn type in active
* zh-CN: active状态下，plain的warn类型的字体颜色
*/

/**
* en: the border color of the plain warn type in active
* zh-CN: active状态下，plain的warn类型的边框颜色
*/

/**
* swipeout
*/

/**
* Cell
*/

/**
* Badge
*/

/**
* en: badge background color
* zh-CN: badge的背景颜色
*/

/**
* Popover
*/

/**
* Button tab
*/

/**
* en: not used
* zh-CN: 未被使用
*/

/**
* en: border radius color
* zh-CN: 圆角边框的半径
*/

/**
* en: border color
* zh-CN: 边框的颜色
*/

/**
* en: not used
* zh-CN: 默认状态下圆角边框的颜色
*/

/**
* en: not used
* zh-CN: 未被使用
*/

/**
* en: default background color
* zh-CN: 默认状态下的背景颜色
*/

/**
* en: selected background color
* zh-CN: 选中状态下的背景颜色
*/

/**
* en: not used
* zh-CN: 未被使用
*/

/* alias */

/**
* en: not used
* zh-CN: 未被使用
*/

/**
* en: default text color
* zh-CN: 默认状态下的文本颜色
*/

/**
* en: height
* zh-CN: 元素高度
*/

/**
* en: line height
* zh-CN: 元素行高
*/

/**
* Swiper
*/

/**
* checklist
*/

/**
* popup-picker
*/

/**
* popup
*/

/**
* popup-header
*/

/**
* form-preview
*/

/**
* sticky
*/

/**
* group
*/

/**
* en: margin-top of title
* zh-CN: 标题的margin-top
*/

/**
* en: margin-bottom of title
* zh-CN: 标题的margin-bottom
*/

/**
* en: margin-top of footer title
* zh-CN: 底部标题的margin-top
*/

/**
* en: margin-bottom of footer title
* zh-CN: 底部标题的margin-bottom
*/

/**
* toast
*/

/**
* en: text color of content
* zh-CN: 内容文本颜色
*/

/**
* en: default top
* zh-CN: 默认状态下距离顶部的高度
*/

/**
* en: position top
* zh-CN: 顶部显示的高度
*/

/**
* en: position bottom
* zh-CN: 底部显示的高度
*/

/**
* en: z-index
* zh-CN: z-index
*/

/**
* icon
*/

/**
* calendar
*/

/**
* en: forward and backward arrows color
* zh-CN: 前进后退的箭头颜色
*/

/**
* en: text color of week highlight
* zh-CN: 周末高亮的文本颜色
*/

/**
* en: background color when selected
* zh-CN: 选中时的背景颜色
*/

/**
* en: text color when disabled
* zh-CN: 禁用时的文本颜色
*/

/**
* en: text color of today
* zh-CN: 今天的文本颜色
*/

/**
* en: font size of cell
* zh-CN: 单元格的字号
*/

/**
* en: background color
* zh-CN: 背景颜色
*/

/**
* en: size of date cell
* zh-CN: 日期单元格尺寸大小
*/

/**
* en: line height of date cell
* zh-CN: 日期单元格的行高
*/

/**
* en: text color of header
* zh-CN: 头部的文本颜色
*/

/**
* week-calendar
*/

/**
* search
*/

/**
* en: text color of cancel button
* zh-CN: 取消按钮文本颜色
*/

/**
* en: background color
* zh-CN: 背景颜色
*/

/**
* en: text color of placeholder
* zh-CN: placeholder文本颜色
*/

/**
* radio
*/

/**
* en: checked icon color
* zh-CN: 选中状态的图标颜色
*/

/**
* loadmore
*/

/**
* en: not used
* zh-CN: 未被使用
*/

/**
* loading
*/

/**
* en: z-index
* zh-CN: z-index
*/

.calendar-year > span,
.calendar-month > span {
  position: relative;
}

.calendar-year > span.calendar-header-right-arrow,
.calendar-month > span.calendar-header-right-arrow {
  left: auto;
  right: 0;
}

.vux-prev-icon,
.vux-next-icon {
  position: absolute;
  left: 0;
  top: 15px;
  display: inline-block;
  width: 12px;
  height: 12px;
  border: 1px solid #c0c0c0;
  border-radius: 0;
  border-top: none;
  border-right: none;
  -webkit-transform: rotate(45deg);
  transform: rotate(45deg);
  margin-left: 15px;
  line-height: 40px;
}

.vux-next-icon {
  -webkit-transform: rotate(-135deg);
  transform: rotate(-135deg);
  left: auto;
  top: 14px;
  right: 15px;
}

.is-weekend-highlight td.is-week-list-0,
.is-weekend-highlight td.is-week-list-6,
.is-weekend-highlight td.is-week-0,
.is-weekend-highlight td.is-week-6 {
  color: #e59313;
}

.inline-calendar a {
  text-decoration: none;
}

.calendar-year,
.calendar-month {
  position: relative;
}

.calendar-header {
  line-height: 40px;
  font-size: 1.2em;
  overflow: hidden;
}

.calendar-header > div {
  float: left;
  width: 50%;
  overflow: hidden;
  display: flex;
  justify-content: space-between;
}

.calendar-header span:last-of-type {
  float: right;
  vertical-align: bottom;
}

.switch-btn,
.calendar-title {
  display: inline-block;
  border-radius: 4px;
}

.switch-btn {
  width: 30px;
  margin: 5px;
  color: #39b5b8;
  font-family: "SimSun";
}

.calendar-title {
  padding: 0 6%;
  color: #333;
}

.switch-btn:active,
.calendar-title:active,
.calendar-header a.active {
  background-color: #39b5b8;
  color: #fff;
}

.calendar-week {
  overflow: hidden;
}

.calendar-week span {
  float: left;
  width: 14.28%;
  font-size: 1.6em;
  line-height: 34px;
  text-align: center;
}

.inline-calendar {
  width: 100%;
  background-color: #fff;
  border-radius: 2px;
  -webkit-transition: all 0.5s ease;
  transition: all 0.5s ease;
}

/* .inline-calendar td.is-today,
.inline-calendar td.is-today.is-disabled {
  color: #008aff;
  border: 1px solid #008aff;
} */

.calendar-enter,
.calendar-leave-active {
  opacity: 0;
  -webkit-transform: translate3d(0, -10px, 0);
  transform: translate3d(0, -10px, 0);
}

.calendar:before {
  position: absolute;
  left: 30px;
  top: -10px;
  content: "";
  border: 5px solid rgba(0, 0, 0, 0);
  border-bottom-color: #dedede;
}

.calendar:after {
  position: absolute;
  left: 30px;
  top: -9px;
  content: "";
  border: 5px solid rgba(0, 0, 0, 0);
  border-bottom-color: #fff;
}

.inline-calendar table {
  clear: both;
  width: 100%;
  border-collapse: collapse;
  color: #444444;
}

.inline-calendar td {
  padding: 5px 0;
  text-align: center;
  vertical-align: middle;
  font-size: 16px;
  position: relative;
}

.inline-calendar td.week {
  pointer-events: none !important;
  cursor: default !important;
}

.inline-calendar td.is-disabled {
  color: #c0c0c0;
}

.inline-calendar td > span.vux-calendar-each-date {
  position: relative;
  display: inline-block;
  width: 40px;
  height: 40px;
  text-align: left;
  border: 1px solid transparent;
  box-sizing: border-box;
}

.inline-calendar td > span.vux-calendar-each-date .vux-calendar-each-date-text {
  line-height: 1.2;
  padding: 0 2px;
}

.inline-calendar td.current > span.vux-calendar-each-date {
  border: 1px solid #008aff;
  color: #008aff !important;
}

.inline-calendar td.is-today > span.vux-calendar-each-date {
  border: 1px solid #008aff;
  color: #008aff;
}

.inline-calendar th {
  color: #000;
  font-weight: normal;
  padding: 10px 0;
}

/** same as week-calendar style**/

.vux-calendar-top-tip {
  position: absolute;
  left: -10px;
  top: 0;
  font-size: 20px;
  -webkit-transform: scale(0.5);
  transform: scale(0.5);
  -webkit-transform-origin: top left;
  transform-origin: top left;
}

.vux-calendar-dot {
  display: block;
  text-align: center;
  width: 5px;
  height: 5px;
  position: absolute;
  left: 50%;
  bottom: 0px;
  margin-left: -2.5px;
  background-color: #f74c31;
  border-radius: 50%;
}
</style>
