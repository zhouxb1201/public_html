import { mapGetters } from 'vuex'
import { setSession } from "@/utils/storage";
const bindMobile = {
  computed: {
    ...mapGetters(['token', 'isBingFlag', 'isBindMobile'])
  },
  methods: {
    /**
     * 验证是否绑定手机
     * @param {Function/Undefined} method 方法名
     * @param {Any} params 传递的参数
     * 
     * 若传入method则验证完是否绑定手机，后执行method方法；
     * 若不传method则验证完是否绑定手机，后返回promise
     */
    bindMobile(method, params) {
      let flag = true
      if (!this.token) {
        this.$Toast("您未登录，请先登录！");
        setSession("toPath", this.$route.fullPath);
        this.$router.push("/login");
        flag = false
      }
      if (this.token && this.isBingFlag && !this.isBindMobile) {
        this.$BindMobile.open()
        flag = false
      }
      if (typeof this[method] == 'function') {
        flag && this[method](params)
      } else {
        return new Promise((resolve, reject) => {
          flag && resolve()
        })
      }
    }
  }
};

export default bindMobile
