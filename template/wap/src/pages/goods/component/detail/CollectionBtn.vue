<template>
  <van-goods-action-mini-btn
    :icon="collection ? 'like' : 'like-o'"
    text="收藏"
    :is_collection="is_collection"
    @click="bindMobile('goodsCollect',collection)"
  />
</template>

<script>
import { GoodsActionMiniBtn } from "vant";
import { SET_GOODSCOLLECT, CANCEL_GOODSCOLLECT } from "@/api/goods";
import { bindMobile } from "@/mixins";
export default {
  data() {
    return {
      collection: this.flag
    };
  },
  props: {
    flag: Boolean,
    seckill_id: {
      default: false
    }
  },
  mixins: [bindMobile],
  computed: {
    is_collection() {
      return (this.collection = this.flag);
    }
  },
  methods: {
    // 商品 收藏/取消
    goodsCollect(flag) {
      const $this = this;
      const goodsid = $this.$route.params.goodsid;
      const seckill_id = $this.seckill_id ? $this.seckill_id : null;
      if (flag) {
        // 取消收藏
        CANCEL_GOODSCOLLECT(goodsid)
          .then(res => {
            $this.$Toast.success("取消成功");
            $this.collection = false;
          })
          .catch(() => {});
      } else {
        // 确定收藏
        SET_GOODSCOLLECT(goodsid, seckill_id)
          .then(res => {
            $this.$Toast.success("收藏成功");
            $this.collection = true;
          })
          .catch(() => {});
      }
    }
  },
  components: {
    [GoodsActionMiniBtn.name]: GoodsActionMiniBtn
  }
};
</script>

<style scoped>
</style>
