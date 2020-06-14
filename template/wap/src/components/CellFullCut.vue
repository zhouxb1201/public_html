<template>
  <van-cell class="discount-group">
    <div class="item" v-if="items.man_song_id&&parseFloat(items.discount)>0">
      <div class="box">
        <van-tag type="danger">满减</van-tag>
        <span class="text-nowrap">订单满{{items.price | toNumber}}减{{items.discount | toNumber}}</span>
      </div>
    </div>
    <div class="item" v-if="items.free_shipping == 1">
      <div class="box">
        <van-tag type="success">包邮</van-tag>
        <span class="text text-nowrap">订单满{{items.price | toNumber}}包邮</span>
      </div>
    </div>
    <div class="item" v-if="items.coupon_type_id">
      <div class="box">
        <van-tag type="primary">满送</van-tag>
        <span class="text text-nowrap">订单满{{items.price | toNumber}}送优惠券({{items.coupon_type_name}})</span>
      </div>
    </div>
    <div class="item" v-if="items.gift_card_id">
      <div class="box">
        <van-tag type="primary">满送</van-tag>
        <span
          class="text text-nowrap"
        >订单满{{items.price | toNumber}}送礼品券({{items.gift_voucher_name}})</span>
      </div>
    </div>
    <div class="item" v-if="items.gift_id">
      <div class="box">
        <van-tag type="primary">满送</van-tag>
        <span class="text text-nowrap">订单满{{items.price | toNumber}}送赠品({{items.gift_name}})</span>
      </div>
    </div>
  </van-cell>
</template>

<script>
export default {
  props: {
    items: Object
  },
  filters: {
    toNumber(value) {
      return parseFloat(value);
    }
  },
  methods: {
    discount(value) {
      return parseFloat(value) > 0;
    }
  }
};
</script>

<style scoped>
.discount-group {
  padding: 6px 15px;
}

.item .box {
  display: flex;
  font-size: 12px;
  color: #909399;
  align-items: center;
  line-height: 18px;
  margin: 4px 0;
}

.item .box span {
  padding-left: 6px;
}
.item .box .text {
  flex: 1;
}
</style>
