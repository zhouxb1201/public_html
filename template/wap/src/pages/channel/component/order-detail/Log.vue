<template>
  <CellInfoGroup :columns="columns" text-align="right"/>
</template>

<script>
import CellInfoGroup from "../../../order/component/CellInfoGroup";
import { yuan } from "@/utils/filter";
export default {
  props: {
    detail: Object
  },
  computed: {
    columns() {
      const type = this.$route.params.type;
      const {
        goods_money,
        shipping_fee,
        order_money,
        order_status,
        pay_status
      } = this.detail;
      let arr = [
        {
          title: order_status == 0 && pay_status == 0 ? "待付款" : "实付",
          value: yuan(order_money),
          color: "#ff454e"
        }
      ];
      if (type === "pickupgoods" || type == "retail") {
        arr.unshift(
          { title: "商品总价", value: yuan(goods_money), color: "#ff454e" },
          { title: "运费", value: yuan(shipping_fee), color: "#ff454e" }
        );
      }
      return arr;
    }
  },
  components: {
    CellInfoGroup
  }
};
</script>

