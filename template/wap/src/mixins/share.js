// 微信分享
import { filterUriParams,decodeUriParams } from '@/utils/util';
const share = {
  data() {
    return {
    };
  },

  mounted() {
    /**
     * 页面分享类型为自定义分享时
     * 需要手动调用onShare方法，并传入相关参数
     */
    (this.$route.meta.noKeepAlive && this.$route.meta.shareType !== 'diy') && this.onShare();
  },

  activated() {
    (!this.$route.meta.noKeepAlive && this.$route.meta.shareType !== 'diy') && this.onShare();
  },

  methods: {
    /**
    * shareType 分享类型 (为空则分享首页)
    * current ==> 分享当前页面 
    * diy     ==> 自定义分享 
    */
    setShareParam(params) {
      const shareType = this.$route.meta.shareType;
      let options = null;
      if (params) {
        options = Object.assign({}, params);
      } else {
        const baseUrl = `${this.$store.state.domain}/wap`;
        const mallName = this.$store.getters.config.mall_name || '商城首页';
        const fullPath = this.$route.path + filterUriParams(this.$route.query, 'extend_code');
        let link = !shareType ? `${baseUrl}/` : `${baseUrl}${fullPath}`;
        let title = !shareType ? mallName : document.title + ' - ' + mallName;
        options = {
          title,
          desc: '我刚刚发现了一个很不错的商城，赶快来看看吧。',
          imgUrl: this.$store.getters.config.logo,
          link
        }
      }
      return options;
    },
    /**
     * @param {object} params 分享参数
     * title, desc, imgUrl, link
     */
    onShare(params) {
      return new Promise((resolve, reject) => {
        this.$store.dispatch("getExtendCode").then(extend_code => {
          let options = this.setShareParam(params);
          const isQuery = filterUriParams(decodeUriParams(options.link), 'extend_code');
          if (extend_code) {
            options.link = isQuery
              ? `${options.link}&extend_code=${extend_code}`
              : `${options.link}?extend_code=${extend_code}`;
          }
          // console.log(isQuery,options)
          // console.log(window.location.href.split('#')[0], options)
          this.$store.dispatch("wxShare", options).then(() => {
            resolve();
          })
        });
      });
    }
  }
};

export default share
