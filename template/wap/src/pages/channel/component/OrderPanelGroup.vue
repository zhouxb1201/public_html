<template>
  <van-cell-group class="item card-group-box">
    <van-cell
      icon="shop-o"
      :value="orderStatusText(items.order_status)"
      value-class="text-maintone"
    >
      <van-row type="flex" slot="title">
        <span>{{$route.params.type == 'output'?'采购代理：'+items.who_purchase+'('+items.who_purchase_grade+')':items.shop_name}}</span>
        <van-icon name="arrow" class="van-cell__right-icon"/>
      </van-row>
    </van-cell>
    <van-cell v-for="(item,index) in items.goods_list" :key="index">
      <GoodsCard
        class="goods-card"
        lazyLoad
        :title="item.goods_name"
        :thumb="item.goods_img"
        :desc="item.sku_name"
        :num="item.num"
        :price="item.price"
        :id="item.goods_id"
      >
        <div
          class="text-right"
          slot="tags"
          v-if="$route.params.type == 'purchase' && item.purchase_to"
        >采购于：{{item.purchase_to}}</div>
      </GoodsCard>
    </van-cell>
    <van-cell>
      <div class="text-right" v-if="items.order_status == 0 && items.pay_status == 0">
        待付款：
        <span class="pay-money letter-price">{{items.pay_money | yuan}}</span>
      </div>
      <div class="text-right" v-else>
        <span class="pay-name">{{paymentTypeText(items.payment_type)}}</span> 实付：
        <span class="pay-money letter-price">{{items.pay_money | yuan}}</span>
      </div>
    </van-cell>
    <van-cell>
      <FootOperation :items="operationItems" :orderid="items.order_id" @callback="onCallback"/>
    </van-cell>
  </van-cell-group>
</template>

<script>
import GoodsCard from "@/components/GoodsCard";
import FootOperation from "./FootOperation";
import { CLOSE_ORDER, CONFIRM_TAKEDELIVERY } from "@/api/order";
export default {
  data() {
    return {};
  },
  computed: {
    operationItems() {
      const type = this.$route.params.type;
      const state = this.items.order_status;
      if (state == undefined) return [];
      let arr = [];
      let pay_obj = {
          no: "pay",
          name: "立即支付"
        },
        close_obj = {
          no: "close",
          name: "关闭订单"
        },
        getdelivery_obj = {
          no: "getdelivery",
          name: "确认收货"
        },
        logistics_obj = {
          no: "logistics",
          name: "查看物流"
        },
        detail_obj = {
          no: "detail",
          name: "订单详情"
        };
      if (type == "purchase") {
        if (state == 0) {
          arr.push(pay_obj);
          arr.push(close_obj);
        }
      } else if (type == "pickupgoods") {
        if (state == 0) {
          arr.push(pay_obj);
          arr.push(close_obj);
        }
        if (state == 2) {
          arr.push(getdelivery_obj);
          arr.push(logistics_obj);
        }
      } else if (type == "output") {
        if (state == 0) {
          arr.push(close_obj);
        }
      } else if (type == "retail") {
        if (state == 2) {
          arr.push(logistics_obj);
        }
      }
      arr.push(detail_obj);
      // console.log(type,state,arr);
      return arr;
    }
  },
  props: {
    items: {
      type: Object
    }
  },
  methods: {
    onCallback(res) {
      this.$emit("callback", res);
    },
    orderStatusText(state) {
      let text = "";
      if (state == 0) {
        text = "待付款";
      } else if (state == 1) {
        text = "待发货";
      } else if (state == 2) {
        text = "已发货";
      } else if (state == 3) {
        text = "已收货";
      } else if (state == 4) {
        text = "已完成";
      } else if (state == 5) {
        text = "已关闭";
      } else if (state == -1) {
        text = "售后中";
      }
      return text;
    },
    paymentTypeText(type) {
      let text = "";
      if (type == 0) {
        text = "在线支付";
      } else if (type == 1) {
        text = "微信支付";
      } else if (type == 2) {
        text = "支付宝支付";
      } else if (type == 3) {
        text = "银联卡支付";
      } else if (type == 4) {
        text = "货到付款";
      } else if (type == 5) {
        text = this.$store.state.member.memberSetText.balance_style + "支付";
      } else if (type == 6) {
        text = "到店支付";
      } else if (type == 10) {
        text = "线下支付";
      }
      return text;
    }
  },
  components: {
    GoodsCard,
    FootOperation
  }
};
</script>
<style scoped>
.pay-name {
  color: #606266;
  padding: 0 10px;
}

.pay-money {
  color: #ff454e;
}

.goods-card {
  padding: 0;
  background: #fff;
}
</style>
