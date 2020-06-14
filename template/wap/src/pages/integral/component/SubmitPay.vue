<template>
  <div class="submit-pay-wrap">
    <div class="btn-submit-pay">
      <div class="btn-submit-pay-wrap">
        <van-button class="action-btn" square size="large" type="danger" @click="toPopup">立即支付</van-button>
      </div>
    </div>
  </div>
</template>
<script>
import { PAY_INTEGRALPAY } from "@/api/integral";
import { _encode } from "@/utils/base64";
import { isIos } from "@/utils/util";
export default {
  props: {
    items: [Object, Array],
    goodsType: [String, Number],
    shippingType: [String, Number],
    address: [Object, Array]
  },
  data() {
    return {
      type: null,

      pay_type: 0, //支付方式 0-在线支付 1-微信支付 2-支付宝 3-银联卡 4-货到付款 5-余额支付

      isLoading: false
    };
  },
  watch: {
    goodsType(id) {
      return this.id;
    }
  },
  computed: {
    // 计算结算金额
    pay_total_amount() {
      let total = 0;
      const items = this.items ? this.items : [];
      items.forEach(({ total_amount }) => {
        total += total_amount <= 0 ? 0 : total_amount;
      });
      return total.toFixed(2);
    },
    //计算结算积分
    pay_total_point() {
      let total = 0;
      const items = this.items ? this.items : [];
      items.forEach(({ total_point }) => {
        total += total_point <= 0 ? 0 : total_point;
      });
      return total;
    },
    order_data() {
      const items = this.items;
      const obj = {};
      obj.order_data = {};
      let orderData = obj.order_data;

      orderData.custom_order = "";
      orderData.type =
        this.$store.state.isWeixin && this.$store.getters.config.is_wchat
          ? 1
          : 2;

      orderData.goods_type = this.goodsType;
      orderData.pay_type = this.pay_type;
      if (this.goodsType == 0) {
        orderData.address_id = this.address.id;
      }
      orderData.leave_message = "";

      orderData.shipping_type = this.shippingType;
      if (this.pay_total_point && this.pay_total_amount > 0) {
        orderData.point_exchange_type = 2; //兑换方式 1-只能积分兑换 2-积分和金钱兑换
      } else {
        orderData.point_exchange_type = 1;
      }
      orderData.goods_list = {};
      orderData.goods_list.exchange_point = this.pay_total_point
        ? this.pay_total_point
        : "";

      if (!items) return {};
      this.items.forEach(e => {
        e.goods_list.forEach(g => {
          orderData.goods_list.goods_id = g.goods_id;
          orderData.goods_list.sku_id = g.sku_id ? g.sku_id : "";
          orderData.goods_list.num = g.num;
          orderData.goods_list.price = g.price ? g.price : "";
        });
      });
      return obj;
    }
  },
  mounted() {},
  methods: {
    toPopup() {
      const $this = this;
      if ($this.goodsType == 0 && !$this.order_data.order_data.address_id) {
        $this.$Toast("请添加地址！");
        return false;
      }

      if ($this.pay_total_point && $this.pay_total_amount > 0) {
        //判断支付方式
        let enCodeOrderData = _encode(JSON.stringify($this.order_data));
        if (isIos()) {
          location.replace(
            `${$this.$store.state.domain}/wap/pay/payment?order_data=${enCodeOrderData}&pay_money=${$this.pay_total_amount}#integral`
          );
        } else {
          $this.$router.push({
            name: "pay-payment",
            query: {
              order_data: enCodeOrderData,
              pay_money: $this.pay_total_amount
            },
            hash: "#integral"
          });
        }
      } else {
        //在线支付
        $this.pay_type = 0;
        $this.createPayOrder();
      }
    },
    createPayOrder() {
      //创建支付订单
      const $this = this;
      const payType = $this.type;
      PAY_INTEGRALPAY($this.order_data).then(res => {
        $this.$router.replace({
          name: "pay-result",
          query: {
            is_integral_order: 1,
            pay_status: 2
          }
        });
      });
    }
  }
};
</script>
<style scoped>
.btn-submit-pay {
  left: 0;
  bottom: 0;
  width: 100%;
  z-index: 100;
  position: fixed;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}
.btn-submit-pay-wrap {
  height: 50px;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  font-size: 14px;
  -webkit-box-align: center;
  -ms-flex-align: center;
  align-items: center;
  background-color: #fff;
}
.action-btn {
  font-size: 14px;
  height: 40px;
  line-height: 38px;
  margin: 5px 10px;
  border-radius: 8px;
}
.icon-balance3 >>> .van-icon-v-icon-balance3 {
  color: #ff454e;
  font-size: 20px;
}

.icon-wx-pay >>> .van-icon-v-icon-wx-pay {
  color: #00c403;
  font-size: 20px;
}

.icon-alipay >>> .van-icon-v-icon-alipay {
  color: #009fe8;
  font-size: 20px;
}
</style>

