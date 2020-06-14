const payPassword = {
  data() {
    return {
      isPayPassword: false
    }
  },
  methods: {
    // 设置支付密码
    setPayPassword() {
      const $this = this;
      $this.$Dialog
        .confirm({
          message: "您还没有设置支付密码！",
          confirmButtonText: "设置"
        })
        .then(() => {
          $this.$refs.DialogPayPassword.isShowPopupPayPassword = true;
        })
        .catch(() => {
          console.log("取消");
        });
    },
  }
}

export default payPassword
