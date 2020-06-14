<template>
  <van-goods-action class="goods-action-group" safe-area-inset-bottom>
    <van-goods-action-mini-btn
      v-if="$store.state.config.addons.shop"
      icon="shop-o"
      text="店铺"
      :to="'/shop/home/'+goodsInfo.shopId"
    />
    <ServiceBtn :goods-info="goodsInfo" v-if="goodsInfo.id" />
    <CollectionBtn :flag="goodsInfo.isCollect" :seckill_id="seckill_id" />
    <ActionBtn
      class="van-hairline--left"
      :goods-info="goodsInfo"
      :promote-type="promoteType"
      :promote-params="promoteParams"
      direct-click
      @action="action"
    />
  </van-goods-action>
</template>

<script>
import { GoodsAction, GoodsActionMiniBtn } from "vant";
import ServiceBtn from "./ServiceBtn";
import CollectionBtn from "./CollectionBtn";
import ActionBtn from "@/components/sku-popup/ActionBtn";
export default {
  data() {
    return {};
  },
  props: {
    goodsInfo: Object,
    promoteType: {
      type: String,
      default: "normal"
    },
    // 活动相关参数
    promoteParams: Object
  },
  computed: {
    seckill_id() {
      let id = null;
      if (this.promoteType == "seckill") {
        id = this.promoteParams.seckill_id || null;
      }
      return id;
    }
  },
  methods: {
    action(action, data) {
      this.$emit("action", action, data);
    }
  },
  components: {
    [GoodsAction.name]: GoodsAction,
    [GoodsActionMiniBtn.name]: GoodsActionMiniBtn,
    ServiceBtn,
    CollectionBtn,
    ActionBtn
  }
};
</script>

<style scoped>
.goods-action-group {
  z-index: 999;
  box-shadow: 0 -2px 10px 0px rgba(0, 0, 0, 0.05);
}

.goods-action-group .action-group {
  padding: 0 5px;
}
</style>


