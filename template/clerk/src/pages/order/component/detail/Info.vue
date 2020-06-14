<template>
  <CellInfoGroup :columns="columns">
    <van-cell slot="head" icon="orders-o" title="订单信息"/>
  </CellInfoGroup>
</template>

<script>
import CellInfoGroup from "../CellInfoGroup";
import { formatDate } from "@/utils/util";
export default {
  props: {
    detail: Object
  },
  computed: {
    columns() {
      const {
        shop_name,
        order_status,
        payment_type_name,
        order_no
      } = this.detail;
      let arr = [
        { title: "商家店铺", value: shop_name },
        { title: "订单编号", value: order_no }
      ];
      if (order_status !== 0 && order_status !== 5) {
        arr.push({
          title: "支付方式",
          value: payment_type_name,
          color: "#ff454e"
        });
      }
      return arr;
    }
  },
  components: {
    CellInfoGroup
  }
};
</script>

<style scoped>
</style>
