<template>
  <van-cell-group>
    <van-cell icon="shop-o">
      <van-row type="flex" slot="title">
        <span>{{detail.shop_name}}</span>
        <van-icon name="arrow" class="van-cell__right-icon"/>
      </van-row>
    </van-cell>
    <van-cell>
      <GoodsCard
        class="goods-card"
        :num="item.num"
        :price="item.price"
        :desc="item.sku_name"
        :title="item.goods_name"
        :thumb="item.pic_cover"
        v-for="(item,index) in detail.order_goods"
        :key="index"
      />
    </van-cell>
  </van-cell-group>
</template>

<script>
import GoodsCard from "@/components/GoodsCard";
export default {
  data() {
    return {};
  },
  props: {
    detail: {
      type: Object
    }
  },
  components: {
    GoodsCard
  }
};
</script>

<style scoped>
.van-card {
  margin-top: 0;
}

.goods-card {
  padding: 0;
  background: #ffffff;
}

.btn {
  margin-left: 5px;
  width: auto;
  padding: 0 6px;
}
</style>
