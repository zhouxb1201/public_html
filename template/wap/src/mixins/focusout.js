// 处理ios端键盘抬起视图上移问题
const focusout = {
  data() {
    return {
    };
  },

  mounted() {
    // 监听键盘事件抬起关闭
    window.addEventListener("focusout", this._focusout);
  },

  methods: {
    _focusout() {
      const scrollHeight =
        document.documentElement.scrollTop || document.body.scrollTop || 0;
      window.scrollTo(0, Math.max(scrollHeight - 1, 0));
    },
  },
  destroyed() {
    window.removeEventListener("focusout", this._focusout);
  },
  deactivated() {
    window.removeEventListener("focusout", this._focusout);
  }
};

export default focusout
