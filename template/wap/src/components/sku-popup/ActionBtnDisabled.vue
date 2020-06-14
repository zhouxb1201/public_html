<template>
  <div class="sku-action-group">
    <van-button class="action-btn" bottom-action disabled>{{goodsInfo.goodsStateText}}</van-button>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    // 活动相关参数
    params: Object,
    // 商品基本信息
    goodsInfo: Object,
    buyBtnText: {
      type: String,
      default: "立即购买"
    }
  },
  methods: {}
};
</script>

<style scoped>
</style>