<template>
  <van-row type="flex" justify="end" class="btn-group">
    <van-button
      class="btn"
      :type="item.no === 'pay' ? 'danger' : 'default'"
      size="small"
      v-for="(item,index) in list"
      :key="index"
      @click="onOperation(item.no)"
    >{{item | operationText}}</van-button>
  </van-row>
</template>

<script>
const defaultInfo = {
  order_id: null,
  order_status: null,
  promotion_status: null,
  order_refund_status: null,
  is_evaluate: null,
  member_operation: [],
  order_goods: [],
  unrefund: null,
  unrefund_reason: null
};
import { VERIFY_ORDER } from "@/api/verify";
export default {
  data() {
    return {};
  },
  props: {
    /**
     * 所需pros ==> info
     * order_id ==> 订单id
     * order_status ==> 订单状态
     * promotion_status ==> 优惠状态
     * order_refund_status ==> 订单售后状态
     * member_operation ==> 订单操作列表
     * order_goods ==> 订单商品列表
     * is_evaluate ==> 订单评价状态
     * unrefund ==> 订单是否可以退款标识 1为不能退款 其他可以
     * unrefund_reason ==> 订单不能退款原因
     */
    info: {
      type: Object,
      required: true,
      default: defaultInfo
    }
  },
  filters: {
    operationText(value) {
      let name = value.name;
      if (value.no === "pickup") name = "提货";
      return name;
    }
  },
  computed: {
    list() {
      const member_operation = this.info.member_operation;
      const list = member_operation.filter(e => {
        if (this.info.is_evaluate == 2) {
          if (e.no !== "evaluation") return e;
        } else {
          return e;
        }
      });
      return list;
    }
  },
  methods: {
    onOperation(type) {
      const $this = this;
      const orderid = $this.info.order_id;
      if (type === "detail") {
        // 订单详情
        $this.$router.push({
          name: "order-detail",
          params: {
            orderid
          }
        });
      } else if (type == "pickup") {
        $this.onVerify(orderid);
      } else {
        $this.$Toast("暂无后续逻辑");
      }
    },
    onVerify(order_id) {
      const $this = this;
      $this.$Dialog
        .confirm({
          message: "确定提货吗？"
        })
        .then(() => {
          VERIFY_ORDER(order_id).then(res => {
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
