const layout = {
  data() {
    return {
      loading: false,
      error: false,
      post: false,

      errorType: 'network',
      showFoot: true,
      errorText: '哎呀~网络开了小差，重新加载试试',
      errorBtnText: '重新加载',
      errorBtnEvent: true,
      btnLink: '',
    }
  },
  created() {
    this.loading = true;
  },
  methods: {
    init() {
      this.loading = true;
      this.error = false;
      this.post = false;
    },
    success() {
      this.loading = false;
      this.post = true;
      this.error = false;
    },
    fail(error, noShowError) {
      if (this.$store.getters.isBingFlag && !this.$store.getters.isBindMobile && noShowError) {
        this.result();
      } else {
        if (typeof error === 'object') {
          const { errorType, showFoot, errorText, errorBtnText, errorBtnEvent, btnLink } = error
          if (errorType) this.errorType = errorType
          if (errorText) this.errorText = errorText
          if (errorBtnText) this.errorBtnText = errorBtnText
          if (typeof errorBtnEvent === 'boolean') this.errorBtnEvent = errorBtnEvent
          if (typeof showFoot === 'boolean') this.showFoot = showFoot
          if (btnLink) this.btnLink = btnLink
        }
        this.loading = false;
        this.error = true;
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
