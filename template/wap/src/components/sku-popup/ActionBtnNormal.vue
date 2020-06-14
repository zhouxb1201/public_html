<template>
  <ActionBtnConfirm v-if="action" :action="action" :btn-type="btnType" @click="click" />
  <div class="sku-action-group" v-else>
    <van-button class="action-btn" bottom-action @click="addCart" v-if="showCart">{{cartBtnText}}</van-button>
    <van-button class="action-btn" bottom-action type="primary" @click="buy">{{buyBtnText}}</van-button>
  </div>
</template>

<script>
import ActionBtnConfirm from "./ActionBtnConfirm";
export default {
  data() {
    return {};
  },
  props: {
    // 活动相关参数
    params: Object,
    // 商品基本信息
    goodsInfo: Object,
    action: String,
    cartBtnText: {
      type: String,
      default: "加入购物车"
    },
    buyBtnText: {
      type: String,
      default: "立即购买"
    }
  },
  computed: {
    btnType() {
      return this.action == "addCart" ? "default" : "primary";
    },
    showCart() {
      return !(
        this.goodsInfo.goodsType == 0 ||
        this.goodsInfo.goodsType == 3 ||
        this.goodsInfo.goodsType == 4
      );
    }
  },
  methods: {
    click(action) {
      this[action]();
    },
    addCart() {
      this.$emit("click", "addCart");
    },
    buy() {
      this.$emit("click", "buy");
    }
  },
  components: {
    ActionBtnConfirm
  }
};
</script>

<style scoped>
</style>