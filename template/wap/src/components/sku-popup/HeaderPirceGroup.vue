<template>
  <div class="van-sku__goods-price">
    <span>{{goodsInfo.groupGoodsPrice | yuan}}</span>
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