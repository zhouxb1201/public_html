
import { throttle } from "lodash";
let p = 0,
  t = 0;
export default {
  data() {
    return {

    };
  },

  mounted() {
    this.initScroll()
  },
  methods: {
    initScroll() {
      this.container = document;
      this.throttledScrollHandler = throttle(this.scroll, 300);
      this.container.addEventListener("scroll", this.throttledScrollHandler);
    },
    scroll() {
      const scrollTop =
        document.documentElement.scrollTop || document.body.scrollTop;
      p = scrollTop < 0 ? 0 : scrollTop;// 兼容ios端下拉滚动为负数时
      if (t <= p) {
        // 上拉
        this.pullDir('up', { p, t })
      } else {
        // 下拉
        this.pullDir('down', { p, t })
      }
      setTimeout(function () {
        t = p;
      }, 0);
    },
    pullDir(dir, { p, t }) {
    }
  },
  beforeDestroy() {
    this.container.removeEventListener("scroll", this.throttledScrollHandler);
  },
  deactivated() {
    this.container.removeEventListener("scroll", this.throttledScrollHandler);
  },

};
