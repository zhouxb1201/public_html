// 微信分享
import { isEmpty } from "@/utils/util";
const share = {
  data() {
    return {
    };
  },

  mounted() {
  },

  methods: {
    /**
     * 
     * @param {object} params 分享参数
     * title, desc, imgUrl, link
     */
    onShare(params) {
      return new Promise((resolve, reject) => {
        const $this = this;
        $this.$store.dispatch("getExtendCode").then(extend_code => {
          let { title, desc, imgUrl, link } = Object.assign({}, params);
          if (extend_code) {
            link = isEmpty($this.$route.query)
              ? `${link}?extend_code=${extend_code}`
              : `${link}&extend_code=${extend_code}`;
          }
          console.log(link);
          $this.$store.dispatch("wxShare", { title, desc, imgUrl, link });
          resolve();
        });
      });
    }
  }
};

export default share
