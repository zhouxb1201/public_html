<template>
  <van-row type="flex" justify="end" class="btn-group">
    <van-button
      class="btn"
      :type="item.no === 'pay' || item.no === 'last_money' ? 'danger' : 'default'"
      size="small"
      v-for="(item,index) in list"
      :key="index"
      @click="onOperation(item.no)"
    >{{item | operationText(info.is_evaluate)}}</van-button>
    <PopupTakeCode
      v-model="takeCodeShow"
      :code="info.verification_code"
      :qrcode="info.verification_qrcode"
      v-if="info.verification_code && info.order_status == 1"
    />
  </van-row>
</template>

<script>
import {
  CLOSE_ORDER,
  CONFIRM_TAKEDELIVERY,
  EDLETE_ORDER,
  ADD_BUYAGAIN,
  GET_TAILMONEYNO
} from "@/api/order";
import { _encode } from "@/utils/base64";
import PopupTakeCode from "./PopupTakeCode";
import { isIos } from "@/utils/util";
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
export default {
  data() {
    return {
      takeCodeShow: false
    };
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
    afterSaleBtnText(value, status) {
      let text = "";
      if (status === 0) {
        if (value === 1) {
          text = "退款";
        } else if (value === 2 || value === 3) {
          text = "退货/退款";
        } else if (value === -1) {
          text = "售后情况";
        }
      } else {
        text = "售后情况";
      }
      return text;
    },
    operationText(value, is_evaluate) {
      let name = value.name;
      if (value.no === "evaluation" && is_evaluate == 1) name = "追加评价";
      if (value.no === "evaluation" && is_evaluate == 2) name = "已追加评价";
      if (value.no === "pay") name = "立即付款";
      if (value.no === "last_money") name = "付尾款";
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
          name: "order-detail",
          params: {
            orderid
          }
        });
      } else if (type === "evaluation") {
        // 评价
        $this.onEvaluation();
      } else if (type === "delete_order") {
        // 删除订单
        $this.onDeleteOrder(orderid);
      } else if (type === "buy_again") {
        // 再次购买
        $this.onBuyAgain(orderid);
      } else if (
        type === "refund" ||
        type === "return" ||
        type === "refund_detail"
      ) {
        // 退款/退货/售后情况
        const { unrefund, unrefund_reason } = $this.info;
        if (unrefund == 1)
          return $this.$Dialog.alert({
            message: unrefund_reason
          });
        $this.onResult(orderid);
      } else if (type == "last_money") {
        const { can_presell_pay, can_presell_pay_reason } = $this.info;
        if (!can_presell_pay)
          return $this.$Dialog.alert({
            message: can_presell_pay_reason
          });
        $this.onPayTailMoney(orderid);
      } else if (type == "pickup") {
        $this.onTakeCode();
      } else if (type == "use_card") {
        $this.$router.push("/consumercard/list");
      } else {
        $this.$Toast("暂无后续逻辑");
      }
    },
    onPay(order_id) {
      const $this = this;
      if (isIos()) {
        location.assign(
          `${
            $this.$store.state.domain
          }/wap/pay/payment?order_id=${order_id}#order`
        );
      } else {
        $this.$router.push({
          name: "pay-payment",
          query: { order_id },
          hash: "#order"
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
          CLOSE_ORDER(order_id).then(res => {
            $this.$emit("callback", res);
          });
        });
    },
    onDeleteOrder(order_id) {
      const $this = this;
      $this.$Dialog
        .confirm({
          message: "确定删除该订单吗？"
        })
        .then(() => {
          EDLETE_ORDER(order_id).then(res => {
            $this.$emit("callback", res);
          });
        });
    },
    onEvaluation() {
      const $this = this;
      let obj = {};
      obj.shop = {
        shop_id: $this.info.shop_id,
        shop_name: $this.info.shop_name
      };
      obj.goods = [];
      $this.info.order_goods.forEach(e => {
        let goodsObj = {};
        goodsObj.id = e.order_goods_id;
        goodsObj.img = e.pic_cover;
        goodsObj.name = e.goods_name;
        goodsObj.score = 5;
        goodsObj.evaluate = "";
        goodsObj.arrImg = [];
        obj.goods.push(goodsObj);
      });
      if ($this.info.store_id || $this.info.card_store_id) {
        obj.store_id = $this.info.store_id
          ? $this.info.store_id
          : $this.info.card_store_id;
      }
      $this.$router.push({
        name: "order-evaluate",
        params: {
          orderid: $this.info.order_id
        },
        query: {
          order_info: _encode(JSON.stringify(obj))
        },
        hash: $this.info.is_evaluate == 1 ? "#again" : ""
      });
    },
    onBuyAgain(order_id) {
      const $this = this;
      const cart = [];
      $this.info.order_goods.forEach(e => {
        let obj = {};
        obj.sku_id = e.sku_id;
        obj.num = e.num;
        cart.push(obj);
      });
      ADD_BUYAGAIN({
        cart
      }).then(res => {
        $this.$Toast("添加成功，请到购物车结算");
        setTimeout(() => {
          $this.$router.push("/mall/cart");
        }, 1000);
      });
    },
    onResult(order_id) {
      const $this = this;
      $this.$router.push({
        name: "order-post",
        query: { order_id }
      });
    },
    onPayTailMoney(order_id) {
      const $this = this;
      GET_TAILMONEYNO({ order_id }).then(({ data }) => {
        if (isIos()) {
          location.assign(
            `${$this.$store.state.domain}/wap/pay/payment?out_trade_no=${
              data.out_trade_no
            }`
          );
        } else {
          $this.$router.push({
            name: "pay-payment",
            query: { out_trade_no: data.out_trade_no }
          });
        }
      });
    },
    onTakeCode() {
      this.takeCodeShow = true;
    }
  },
  components: {
    PopupTakeCode
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
