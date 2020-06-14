<template>
  <van-row class="item" type="flex">
    <van-col span="18" class="info">
      <van-col span="8" class="img">
        <img v-lazy="items.shop_logo" :key="items.shop_logo" pic-type="shop" />
      </van-col>
      <van-col span="16" class="text">
        <div class="name">{{items.coupon_name}}</div>
        <div class="price">
          <span class="letter-price" v-if="items.coupon_genre !== 3">{{items.money | yuan}}</span>
          <span v-else>{{items.discount | discount}}</span>
          <span v-if="items.coupon_genre !== 1">满{{items.at_least}}可用</span>
        </div>
        <div class="time">有限期{{items.start_time | formatDate}} 至 {{items.end_time | formatDate}}</div>
      </van-col>
    </van-col>
    <van-col span="6" class="receive">
      <CircleBox :items="items" :rate="rate" :isDisabled="isDisabled" />
      <van-button
        class="btn"
        size="mini"
        round
        type="danger"
        :disabled="isDisabled"
        @click="bindMobile('onReceive')"
      >立即领取</van-button>
    </van-col>
  </van-row>
</template>

<script>
import CircleBox from "./CircleBox";
import { RECEIVE_COUPON } from "@/api/coupon";
import { bindMobile } from "@/mixins";
export default {
  data() {
    return {};
  },
  props: {
    items: Object,
    loadData: Function
  },
  mixins: [bindMobile],
  filters: {
    discount(value) {
      return parseFloat(value) + "折";
    }
  },
  computed: {
    rate() {
      const count = parseInt(this.items.count);
      const receive_times = parseInt(this.items.receive_times);
      let rate = (receive_times / count) * 100;
      return count > 0 ? rate : 0;
    },
    isDisabled() {
      let flag = false;
      const isLimit = parseInt(this.items.count) <= 0 ? false : true; // 是否限制
      if (isLimit && this.rate >= 100) {
        flag = true;
      }
      return flag;
    }
  },
  methods: {
    onReceive() {
      const $this = this;
      const params = {};
      params.coupon_type_id = $this.items.coupon_type_id;
      params.get_type = 10; // 接口规定领券中心领取标识
      RECEIVE_COUPON(params).then(() => {
        $this.$Toast.success("领取成功");
        $this.loadData("init");
      });
    }
  },
  components: {
    CircleBox
  }
};
</script>
<style scoped>
.item {
  margin: 10px;
  border-radius: 4px;
  overflow: hidden;
}

.item::after {
  content: "";
}

.item .info {
  display: flex;
  align-items: center;
  padding: 10px;
  background: #fff;
  margin-right: 1px;
  border-top-right-radius: 5px;
  border-bottom-right-radius: 5px;
}

.item .info .img {
  margin-right: 8px;
  max-height: 62px;
  overflow: hidden;
}

.item .info .img img {
  width: 100%;
  display: block;
  height: auto;
}

.item .info .text .name {
  color: #333;
}

.item .info .text .price {
  line-height: 24px;
  color: #fd4501;
}

.item .info .text .price span {
  padding-right: 5px;
}

.item .info .text .time {
  font-size: 10px;
  color: #606266;
}

.item .receive {
  padding: 10px;
  background: #fff;
  margin-left: 1px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  border-top-left-radius: 5px;
  border-bottom-left-radius: 5px;
}

.item .receive .btn {
  margin: 8px auto 0;
}
</style>
