<template>
  <div class="van-list">
    <slot v-if="!showEmpty"/>
    <Empty
      :page-type="emptyObj.pageType"
      :message="emptyObj.message"
      :show-foot="emptyObj.showFoot"
      :btn-link="emptyObj.btnLink"
      :btn-text="emptyObj.btnText"
      :event="emptyObj.event"
      :top="emptyObj.top?emptyObj.top:$el.offsetTop"
      :bottom="emptyObj.bottom"
      @click="emptyBtnClick"
      v-else
    />
    <div v-if="loading" class="van-list__loading">
      <slot name="loading">
        <van-loading class="van-list__loading-icon"/>
        <span class="van-list__loading-text">{{ loadingText }}</span>
      </slot>
    </div>
    <div v-if="finished && finishedText" class="van-list__finished-text">{{ finishedText }}</div>
    <div
      v-if="error && errorText"
      class="van-list__error-text"
      @click="clickErrorText"
    >{{ errorText }}</div>
  </div>
</template>

<script>
import { Loading } from "vant";
import Empty from "./Empty";
import utils from "@/utils/scroll";
import { on, off } from "@/utils/event";
const emptyDefaultObj = {
  pageType: "data", // 为空情况的类型
  message: "没有相关数据", //提示信息
  showFoot: false, //是否显示底部按钮
  btnLink: "", //按钮跳转链接 可为object
  btnText: "", // 按钮文字
  event: false, // 是否自定义点击事件 为true则btnLink无效
  top: "", //组件距离顶部距离
  bottom: "" //组件距离低部距离
};
export default {
  data() {
    return {
      emptyObj: Object.assign({ ...emptyDefaultObj }, { ...this.empty }),
      showEmpty: false
    };
  },
  model: {
    prop: "loading"
  },
  props: {
    loading: Boolean,
    finished: Boolean,
    immediateCheck: {
      type: Boolean,
      default: false
    },
    offset: {
      type: Number,
      default: 100
    },
    loadingText: {
      type: String,
      default: "加载中..."
    },
    error: Boolean,
    finishedText: {
      type: String,
      default: "没有更多了"
    },
    errorText: {
      type: String,
      default: "请求失败，点击重新加载"
    },
    // 列表是否为空
    isEmpty: Boolean, // 需要显示空页面时必传
    empty: {
      type: Object,
      default: () => ({ ...emptyDefaultObj })
    }
  },

  watch: {
    loading() {
      this.$nextTick(this.check);
    },

    finished(isFinished) {
      if (this.isEmpty != undefined) {
        this.showEmpty = isFinished && this.isEmpty;
      }
      this.$nextTick(this.check);
    }
  },

  mounted() {
    this.scroller = utils.getScrollEventTarget(this.$el);
    this.handler(true);

    if (this.immediateCheck) {
      this.$nextTick(this.check);
    }
  },

  destroyed() {
    this.handler(false);
  },

  activated() {
    this.handler(true);
  },

  deactivated() {
    this.handler(false);
  },

  methods: {
    check() {
      if (this.loading || this.finished || this.error) {
        return;
      }

      const el = this.$el;
      const { scroller } = this;
      const scrollerHeight = utils.getVisibleHeight(scroller);

      /* istanbul ignore next */
      if (
        !scrollerHeight ||
        window.getComputedStyle(el).display === "none" ||
        el.offsetParent === null
      ) {
        return;
      }

      const scrollTop = utils.getScrollTop(scroller);
      const targetBottom = scrollTop + scrollerHeight;

      let reachBottom = false;

      /* istanbul ignore next */
      if (el === scroller) {
        reachBottom = scroller.scrollHeight - targetBottom < this.offset;
      } else {
        const elBottom =
          utils.getElementTop(el) -
          utils.getElementTop(scroller) +
          utils.getVisibleHeight(el);
        reachBottom = elBottom - scrollerHeight < this.offset;
      }

      /* istanbul ignore else */
      if (reachBottom) {
        this.$emit("input", true);
        this.$emit("load");
      }
    },

    clickErrorText() {
      this.$emit("update:error", false);
      this.$nextTick(this.check);
    },

    handler(bind) {
      /* istanbul ignore else */
      if (this.binded !== bind) {
        this.binded = bind;
        (bind ? on : off)(this.scroller, "scroll", this.check);
      }
    },

    emptyBtnClick() {
      this.$emit("empty-btn-click");
    }
  },
  components: {
    [Loading.name]: Loading,
    Empty
  }
};
</script>

<style scoped>
@import url("vant/lib/list");
</style>
