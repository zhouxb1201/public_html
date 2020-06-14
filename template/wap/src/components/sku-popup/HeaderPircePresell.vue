<template>
  <div class="van-sku__goods-price">
    <div>
      <span>定金：</span>
      <span>{{goodsInfo.frontMoney | yuan}}</span>
    </div>
    <div>
      <span>预售价：</span>
      <span>{{goodsInfo.allMoney | yuan}}</span>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    // 商品基本信息
    goodsInfo: Object
  }
};
</script>

<style scoped>
</style>