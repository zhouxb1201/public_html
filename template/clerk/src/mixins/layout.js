const layout = {
  data() {
    return {
      loading: false,
      error: false,
      post: false,

      showFoot: true,
      errorText: '数据加载失败，请稍后再试！',
      errorBtnText: '刷新',
      errorBtnEvent: true,
      btnLink: '',
    }
  },
  created() {
    this.loading = true;
  },
  methods: {
    success() {
      this.loading = false;
      this.post = true;
    },
    fail(error) {
      if (this.$store.getters.isBindMobile) {
        if (typeof error === 'object') {
          const { showFoot, errorText, errorBtnText, errorBtnEvent, btnLink } = error
          if (errorText) this.errorText = errorText
          if (errorBtnText) this.errorBtnText = errorBtnText
          if (typeof errorBtnEvent === 'boolean') this.errorBtnEvent = errorBtnEvent
          if (typeof showFoot === 'boolean') this.showFoot = showFoot
          if (btnLink) this.btnLink = btnLink
        }
        this.loading = false;
        this.error = true;
      } else {
        this.result();
      }

    },
    result(error) {
      this.loading = false;
      this.error = false;
      this.post = false;
    }
  }
}
export default layout
