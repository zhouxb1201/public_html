<template>
  <transition name="van-fade">
    <div
      v-if="visible"
      @click.stop="handleClick"
      :style="{
        'right': styleRight,
        'bottom': styleBottom
      }"
      class="box"
    >
      <slot>
        <van-icon name="arrow-up" />
      </slot>
    </div>
  </transition>
</template>

<script>
import { throttle } from "lodash";
export default {
  props: {
    visibilityHeight: {
      type: Number,
      default: 200
    },
    target: [String],
    right: {
      type: Number,
      default: 15
    },
    bottom: {
      type: Number,
      default: 75
    },
    onShow: Function
  },
  data() {
    return {
      el: null,
      container: null,
      visible: false
    };
  },
  watch: {
    visible(e) {
      this.onShow(e);
    }
  },
  computed: {
    styleBottom() {
      return `${this.bottom}px`;
    },
    styleRight() {
      return `${this.right}px`;
    }
  },
  mounted() {
    this.init();
    this.throttledScrollHandler = throttle(this.onScroll, 300);
    this.container.addEventListener("scroll", this.throttledScrollHandler);
  },
  methods: {
    init() {
      this.container = document;
      this.el = document.documentElement;
      if (this.target) {
        this.el = document.querySelector(this.target);
        if (!this.el) {
          throw new Error(`target is not existed: ${this.target}`);
        }
        this.container = this.el;
      }
    },
    onScroll() {
      const scrollTop =
        document.documentElement.scrollTop || document.body.scrollTop;
      this.visible = scrollTop >= this.visibilityHeight;
    },
    handleClick(e) {
      this.scrollToTop();
      this.$emit("click", e);
    },
    scrollToTop() {
      let el = document.documentElement.scrollTop
        ? document.documentElement
        : document.body;
      let step = 0;
      let interval = setInterval(() => {
        if (el.scrollTop <= 0) {
          clearInterval(interval);
          return;
        }
        step += 10;
        el.scrollTop -= step;
      }, 20);
    }
  },
  beforeDestroy() {
    this.container.removeEventListener("scroll", this.throttledScrollHandler);
  },
  deactivated() {
    this.container.removeEventListener("scroll", this.throttledScrollHandler);
  }
};
</script>

<style scoped>
.box {
  position: fixed;
  background-color: #fff;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  color: #606266;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  box-shadow: 0 0 6px rgba(0, 0, 0, 0.12);
  cursor: pointer;
  z-index: 999;
  opacity: 0.7;
}
</style>
