<template>
  <van-cell-group>
    <van-cell icon="shop-o">
      <van-row type="flex" slot="title">
        <span>{{detail.shop_name}}</span>
        <van-icon name="arrow" class="van-cell__right-icon"/>
      </van-row>
    </van-cell>
    <van-cell v-for="(item,index) in detail.order_goods" :key="index">
      <GoodsCard
        class="goods-card"
        :thumb="item.pic_cover"
        :title="item.goods_name"
        :desc="item.sku_name"
        :num="item.num"
        :price="item.price"
        :id="item.goods_id"
      >
        <div
          slot="tags"
          class="text-right"
          v-if="$route.params.type == 'purchase' && item.purchase_to"
        >
          采购于：
          <span>{{item.purchase_to}}</span>
        </div>
      </GoodsCard>
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

</style>
