<template>
  <van-cell-group>
    <van-cell>
      <GoodsCard :id="detail.goods_id" :thumb="detail.goods_picture" :title="detail.goods_name" :desc="detail.card_title"/>
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
    detail: Object
  },
  components: {
    GoodsCard
  }
};
</script>

