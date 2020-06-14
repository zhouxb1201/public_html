const pushState = {
  created() {
    // 是否支持popstate
    if (window.history && window.history.pushState) {
      history.pushState(null, null, document.URL);
      window.addEventListener("popstate", this.goBack, false);
    }
  },
  destroyed() {
    window.removeEventListener("popstate", this.goBack, false);
  },
  methods: {
    goBack() {

    }
  }
}
export default pushState
