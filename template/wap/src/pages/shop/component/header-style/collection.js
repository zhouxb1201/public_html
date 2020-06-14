// 点击收藏
import { SET_COLLECTSHOP, CANCEL_COLLECTSHOP } from "@/api/shop";
import { bindMobile } from "@/mixins";
const collection = {
  data() {
    return {
      isLoading: false
    };
  },
  mixins: [bindMobile],
  computed: {
    btnIconName() {
      return this.info.is_collection ? 'like' : 'like-o'
    }
  },
  methods: {
    onCollect(flag) {
      const $this = this;
      $this.bindMobile().then(() => {
        const shopid = $this.$route.params.shopid;
        $this.isLoading = true;
        $this.info.is_collection = !$this.info.is_collection;
        if ($this.info.is_collection) {
          SET_COLLECTSHOP(shopid).then(res => {
            $this.$Toast.success("收藏成功");
            $this.isLoading = false;
          }).catch(() => {
            $this.isLoading = false;
          })
        } else {
          CANCEL_COLLECTSHOP(shopid).then(res => {
            $this.$Toast.success("取消成功");
            $this.isLoading = false;
          }).catch(() => {
            $this.isLoading = false;
          })
        }
      }).catch(()=>{})
    }
  }
};

export default collection
