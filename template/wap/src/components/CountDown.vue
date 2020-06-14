<template>
  <div class="count-down">
    <span class="box" v-html="str"></span>
    <span ref="tpl" v-if="showTpl">
      <slot></slot>
    </span>
  </div>
</template>

<script>
import { getServerTime, formatDate } from "@/utils/util";
export default {
  data() {
    return {
      str: "",
      timer: null,
      tempFormat: "",
      showTpl: true,
      flag: true
    };
  },
  props: {
    time: {
      type: [String, Number, Date]
    },
    format: {
      type: String,
      default: "{%d}天{%h}时{%m}分{%s}秒"
    },
    timetype: {
      validator(value) {
        return ["datetime", "second", "timestamp"].indexOf(value) > -1;
      },
      default: "datetime"
    },
    // callback: {
    //   type: Function
    // },
    doneText: {
      type: String,
      default: "已结束"
    }
  },
  watch: {
    time(val) {
      clearInterval(this.timer);
      val && this.run();
    }
  },
  methods: {
    run() {
      if (!this.time) return;
      if (this.timetype === "second") {
        this.lastTime = Math.floor(new Date() / 1000) + ~~this.time;
      } else if (this.timetype === "timestamp") {
        this.lastTime = Math.floor(new Date(this.time).getTime());
      } else {
        this.lastTime = Math.floor(new Date(this.time).getTime() / 1000);
      }
      getServerTime().then(serverTime => {
        // this.$Toast({message:formatDate(serverTime.getTime(),'s'),duration:8000})
        this.dateDiff =
          Math.floor(serverTime / 1000) - Math.floor(new Date() / 1000); //请求时间戳与本地时间戳 时间差
        if (this.dateDiff < 0) {
          this.dateDiff = Math.abs(this.dateDiff);
        }
        if (this.time instanceof Date) {
          this.lastTime = Math.floor(this.time.getTime() / 1000);
        }
        this.doRun();
        this.timer = setInterval(this.doRun, 1000);
      });
    },
    doRun() {
      let leftTime =
        this.lastTime - Math.floor(new Date().getTime() / 1000) + this.dateDiff;
      if (leftTime > 0) {
        this.str = this.timestampTotime(leftTime);
      } else {
        if (this.flag) {
          this.flag = false;
          this.$emit("callback");
          this.str = this.doneText;
          clearInterval(this.timer);
        }
      }
    },
    timestampTotime(time) {
      let format = this.tempFormat;
      let t = {};
      t.s = time % 60;
      time = Math.floor(time / 60);
      t.m = time % 60;
      time = Math.floor(time / 60);
      t.h = time % 24;
      t.d = Math.floor(time / 24);
      const ment = function(a) {
        if (a <= 0) return "00";
        return a < 10 ? "0" + a : a;
      };
      const arr = ["d", "h", "m", "s"];
      arr.forEach(val => {
        const day = ment(t[val])
          .toString()
          .split("");
        format = format.replace("{%" + val + "}", ment(t[val]));
        format = format.replace(
          "{%" + val + "0}",
          ~~day[day.length - 3] !== 0 ? day[day.length - 3] : ""
        );
        format = format.replace("{%" + val + "1}", ~~day[day.length - 2]);
        format = format.replace("{%" + val + "2}", ~~day[day.length - 1]);
      });
      return format;
    }
  },
  mounted() {
    this.$nextTick(() => {
      this.tempFormat = !!this.$slots.default
        ? this.$refs.tpl.innerHTML
        : this.format;
      this.showTpl = false;
      this.run();
    });
  },
  beforeDestroy() {
    clearInterval(this.timer);
  }
};
</script>

<style scoped>
.count-down {
  display: inline;
}
</style>