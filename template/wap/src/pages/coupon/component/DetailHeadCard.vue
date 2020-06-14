<template>
  <div class="head-card card-group-box">
    <van-cell
      icon="shop-o"
      :title="detail.shop_name"
      class="cell-head"
      :border="false"
      center
      :to="'/shop/home/'+detail.shop_id"
    >
      <div slot="right-icon">
        <span class="flex-auto-center">
          进店
          <van-icon slot="right-icon" name="arrow"/>
        </span>
      </div>
    </van-cell>
    <van-cell class="cell-text" :border="false" value-class="fff">
      <div>以下商品可使用以下优惠</div>
      <div class="info">
        <van-tag class="tag" type="primary">店铺 | 优惠券</van-tag>
        <span class="name">{{couponName}}</span>
      </div>
    </van-cell>
    <van-cell class="cell-text cell-time" :border="false" value-class="fff">
      <span>{{detail.start_time | formatDate('s')}}</span>~
      <span>{{detail.end_time | formatDate('s')}}</span>
    </van-cell>
  </div>
</template>

<script>
export default {
  props: {
    detail: Object
  },
  computed: {
    couponName() {
      const detail = this.detail;
      let name = "";
      var money = parseFloat(detail.money),
        at_least = parseFloat(detail.at_least),
        discount = parseFloat(detail.discount);
      if (detail.coupon_genre == 1) {
        name = "无门槛" + money + "元";
      } else if (detail.coupon_genre == 2) {
        name = "满" + at_least + "元减" + money + "元";
      } else if (detail.coupon_genre == 3) {
        name = "满" + at_least + "元" + discount + "折";
      }
      return name;
    }
  }
};
</script>

<style scoped>
.head-card {
  background: #ffffff;
}

.cell-head {
  background: #ff9900;
  color: #ffffff;
}

.cell-text {
  color: #ffffff;
  background: #ff454e;
}

.info .name {
  padding-left: 10px;
}

.fff {
  color: #ffffff;
}

.cell-time {
  border-top: 1px dashed #fff;
}
</style>

