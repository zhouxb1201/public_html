<template>
  <van-row type="flex" justify="end" class="btn-group">
    <van-button
      class="btn"
      :type="item.no == 'pay' ? 'danger' : 'default'"
      size="small"
      v-for="(item,index) in items"
      :key="index"
      @click="onOperation(item.no,orderid)"
    >{{item.name}}</van-button>
  </van-row>
</template>

<script>
import { CLOSE_ORDER } from "@/api/channel";
import { CONFIRM_TAKEDELIVERY } from "@/api/order";
import { isIos } from "@/utils/util";
export default {
  data() {
    return {};
  },
  props: {
    items: {
      type: Array
    },
    orderid: {
      type: [String, Number]
    }
  },
  methods: {
    onOperation(type, orderid) {
      const $this = this;
      if (type === "pay") {
        // 支付
        $this.onPay(orderid);
      } else if (type === "getdelivery") {
        // 确认收货
        $this.onTakeDelivery(orderid);
      } else if (type === "logistics") {
        // 查看物流信息
        $this.$router.push({
          name: "order-logistics",
          params: {
            orderid
          }
        });
      } else if (type === "close") {
        // 关闭订单
        $this.onCloseOrder(orderid);
      } else if (type === "detail") {
        // 订单详情
        $this.$router.push({
          name: "channel-order-detail",
          params: {
            type: $this.$route.params.type,
            orderid
          }
        });
      }
    },
    onPay(order_id) {
      const $this = this;
      let hash = $this.$route.params.type == "purchase" ? "#channel" : "#order"; // 采购订单支付hash为channel
      if (isIos()) {
        location.assign(
          `${
            $this.$store.state.domain
          }/wap/pay/payment?order_id=${order_id}${hash}`
        );
      } else {
        $this.$router.push({
          name: "pay-payment",
          query: { order_id },
          hash
        });
      }
    },
    onTakeDelivery(order_id) {
      const $this = this;
      $this.$Dialog
        .confirm({
          message: "确定收货吗？"
        })
        .then(() => {
          CONFIRM_TAKEDELIVERY(order_id).then(res => {
            $this.$emit("callback", res);
          });
        });
    },
    onCloseOrder(order_id) {
      const $this = this;
      $this.$Dialog
        .confirm({
          message: "确定关闭该订单吗？"
        })
        .then(() => {
          CLOSE_ORDER({
            order_id,
            order_type: $this.$route.params.type
          }).then(res => {
            $this.$emit("callback", res);
          });
        });
    }
  }
};
</script>

<style scoped>
.btn-group {
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  font-size: 14px;
  -webkit-box-align: center;
  -ms-flex-align: center;
  align-items: center;
  background-color: #fff;
  width: 100%;
  height: 100%;
}

.btn-group .btn {
  margin-left: 6px;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
  padding: 0 4px;
}
</style>
