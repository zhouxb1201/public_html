<template>
  <div class="success-box">
    <div class="text">
      <span>恭喜你，成功领取</span>
      <span class="name">{{name}}</span>
    </div>
    <div class="list-box">
      <div class="list-group">
        <div class="item" v-for="(item,index) in couponList" :key="'c_'+index">
          <div class="info">
            <div class="title">
              <span class="letter-price price" v-if="item.coupon_genre !== 3">{{item.money | yuan}}</span>
              <span class="price" v-else>{{item.discount | discount}}</span>
              <span v-if="item.coupon_genre !== 1">满{{item.at_least}}可用</span>
            </div>
            <div class="name">
              <span>{{item.shop_name}}</span>
              <span class="fs-12 text-regular">{{item.goods_range}}</span>
            </div>
            <div class="time">
              <span>{{item.start_time | formatDate}}</span>~
              <span>{{item.end_time | formatDate}}</span>
            </div>
          </div>
        </div>
        <div class="item" v-for="(item,index) in giftList" :key="'g_'+index">
          <div class="info">
            <div class="title">
              <span class="letter-price price">{{item.gift_voucher_name}}</span>
            </div>
            <div class="name">
              <span>{{item.shop_name}}</span>
            </div>
            <div class="time">
              <span>{{item.start_time | formatDate}}</span>~
              <span>{{item.end_time | formatDate}}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  filters: {
    discount(value) {
      return parseFloat(value) + "折";
    }
  },
  props: {
    name: String,
    couponList: {
      type: Array,
      default: []
    },
    giftList: {
      type: Array,
      default: []
    }
  }
};
</script>
<style scoped>
.success-box {
  width: 84%;
  margin: 30px auto;
}

.success-box .text {
  display: flex;
  flex-direction: column;
  line-height: 1.6;
  text-align: center;
  color: #fff;
}

.success-box .text .name {
  font-weight: 800;
  font-size: 20px;
  color: #feda6f;
}

.list-box {
  min-height: 200px;
  max-height: 400px;
  overflow-y: auto;
  margin: 30px 0;
  background: #f8f8f8;
}

.list-group {
  border-radius: 6px;
  overflow: hidden;
}

.item {
  display: flex;
  align-items: center;
  background: #ffffff;
  border-radius: 4px;
  margin: 15px;
  padding: 10px 15px;
  position: relative;
  overflow: hidden;
}

.item::after,
.item::before {
  content: "";
  display: block;
  position: absolute;
  width: 16px;
  height: 16px;
  background: #f8f8f8;
  border-radius: 50%;
  top: 50%;
  margin-top: -8px;
}

.item::after {
  right: -8px;
}

.item::before {
  left: -8px;
}

.item .info {
  line-height: 1.6;
}

.item .title {
  color: #f54244;
}

.item .title .price {
  font-weight: 800;
  font-size: 16px;
  padding-right: 4px;
}

.item .time {
  font-size: 12px;
  color: #909399;
  display: flex;
  margin: 0 -4px;
}

.item .time span {
  padding: 0 4px;
}
</style>
