/* !
 * 验证及设置支付密码
 * 搭配公共组件DialogPayPassword使用；
 * 判断是否需要进行绑定手机状态isBingFlag，false情况则不需要进行支付密码的输入
 */
const payPassword = {
  data() {
    return {
    }
  },
  methods: {
    // 设置支付密码
    setPayPassword() {
      return new Promise((resolve, reject) => {
        this.$Dialog
          .confirm({
            message: "您还没有设置支付密码！",
            confirmButtonText: "设置"
          })
          .then(() => {
            resolve()
          })
          .catch(() => {
            reject()
            console.log("取消");
          });
      })
    },
    /**
     * 验证支付密码
     * @param {String} password 支付密码
     * @param {Boolean} valid 为true时则必须验证支付密码，不管isBingFlag
     */
    validPayPassword(password = '', valid) {
      const $this = this;
      return new Promise((resolve, reject) => {
        if (!$this.$store.getters.isBingFlag && !valid) {
          resolve(null)
        } else {
          let flag = true;
          if (!$this.$store.state.member.info.is_password_set) {
            $this.setPayPassword().then(() => {
              $this.$refs.DialogPayPassword.isShowPopupPayPassword = true;
              reject()
            }).catch(() => {
              reject()
            })
            flag = false;
          } else {
            if (!password) {
              $this.$refs.DialogPayPassword.isShowDialog = true;
              flag = false;
            }
            if (flag) {
              $this.$store.dispatch('checkPayPassword', password).then(() => {
                resolve()
                $this.$refs.DialogPayPassword.onClearPassword();
              }).catch(() => {
                reject()
                $this.$refs.DialogPayPassword.onClearPassword();
              })
            }
          }
        }
      })
    }
  }
}

export default payPassword
