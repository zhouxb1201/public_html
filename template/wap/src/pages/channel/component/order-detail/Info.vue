<template>
  <CellInfoGroup :columns="columns" />
</template>

<script>
import CellInfoGroup from "../../../order/component/CellInfoGroup";
import { formatDate } from "@/utils/util";
export default {
  props: {
    detail: Object
  },
  computed: {
    columns() {
      const type = this.$route.params.type;
      const {
        who_purchase,
        who_purchase_grade,
        shop_name,
        order_no,
        order_status,
        payment_name,
        create_time
      } = this.detail;
      let arr = [
        { title: "订单编号", value: order_no },
        { title: "订单状态", value: this.orderStatus(order_status) },
        { title: "创建时间", value: formatDate(create_time, "s") }
      ];
      if (type == "output") {
        arr.unshift({
          title: "采购代理",
          value: who_purchase + "(" + who_purchase_grade + ")"
        });
      } else {
        arr.unshift({ title: "商家店铺", value: shop_name });
      }
      if (
        (type == "purchase" || type == "output") &&
        order_status &&
        payment_name
      ) {
        arr.push({
          title: "支付方式",
          value: payment_name,
          color: "#ff454e"
        });
      }
      return arr;
    }
  },
  methods: {
    orderStatus(state) {
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
    }
  },
  components: {
    CellInfoGroup
  }
};
</script>
