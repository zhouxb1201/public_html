<template>
  <CellInfoGroup :columns="columns" text-align="right"/>
</template>

<script>
import CellInfoGroup from "../CellInfoGroup";
import { yuan } from "@/utils/filter";
export default {
  props: {
    detail: Object
  },
  computed: {
    columns() {
      const {
        goods_money,
        order_money,
        shipping_fee,
        promotion_money,
        deduction_money
      } = this.detail;
      let arr = [
        { title: "商品总额", value: yuan(goods_money), color: "#ff454e" }
      ];
      if (parseFloat(deduction_money) > 0) {
        arr.push({
          title: "积分抵扣",
          value: yuan(deduction_money),
          color: "#ff454e"
        });
      }
      arr.push(
        { title: "运费", value: yuan(shipping_fee), color: "#ff454e" },
        { title: "优惠金额", value: yuan(promotion_money), color: "#ff454e" },
        { title: "实付", value: yuan(order_money), color: "#ff454e" }
      );
      return arr;
    }
  },
  components: {
    CellInfoGroup
  }
};
</script>

