<template>
  <CellInfoGroup :columns="columns" text-align="right" />
</template>

<script>
import CellInfoGroup from "../CellInfoGroup";
import { yuan } from "@/utils/filter";
export default {
  props: {
    detail: Object
  },
  computed: {
    pointText() {
      return this.$store.state.member.memberSetText.point_style;
    },
    columns() {
      const {
        order_type,
        goods_money,
        order_money,
        order_real_money,
        order_point,
        shipping_fee,
        invoice,
        promotion_money,

        deduction_money,

        presell_status,
        first_real_money,
        final_real_money
      } = this.detail;
      let arr = [];
      if (order_type == 10) {
        if (goods_money > 0 && order_point) {
          arr = [
            {
              title: "商品总额",
              value: order_point + this.pointText + " + " + yuan(goods_money),
              color: "#ff454e"
            }
          ];
        } else if (order_point > 0) {
          arr = [
            {
              title: "商品总额",
              value: order_point + this.pointText,
              color: "#ff454e"
            }
          ];
        }
      } else {
        arr = [
          { title: "商品总额", value: yuan(goods_money), color: "#ff454e" }
        ];
      }

      if (parseFloat(deduction_money) > 0) {
        arr.push({
          title: this.$store.state.member.memberSetText.point_style + "抵扣",
          value: yuan(deduction_money),
          color: "#ff454e"
        });
      }
      if (order_type == 7) {
        if (presell_status == 0) {
          arr.push({
            title: "待付定金",
            value: first_real_money,
            color: "#ff454e"
          });
        } else if (presell_status == 1) {
          arr.push(
            { title: "已付定金", value: first_real_money, color: "#ff454e" },
            {
              title: "待付尾款(含运费" + (invoice.type ? "税费" : "") + ")",
              value: final_real_money,
              color: "#ff454e"
            }
          );
        } else if (presell_status == 2) {
          arr.push(
            { title: "已付定金", value: first_real_money, color: "#ff454e" },
            {
              title: "已付尾款",
              value: final_real_money,
              color: "#ff454e"
            },
            { title: "运费", value: yuan(shipping_fee), color: "#ff454e" }
          );
          if (invoice.type) {
            arr.push({
              title: "税费",
              value: yuan(invoice.tax),
              color: "#ff454e"
            });
          }
          arr.push({
            title: "实付",
            value: order_real_money,
            color: "#ff454e"
          });
        }
      } else if (order_type == 10) {
        if (order_point > 0 && order_money > 0) {
          arr.push(
            { title: "运费", value: yuan(shipping_fee), color: "#ff454e" },
            {
              title: "实付",
              value: order_point + this.pointText + " + " + yuan(order_money),
              color: "#ff454e"
            }
          );
        } else if (order_point > 0) {
          arr.push(
            { title: "运费", value: yuan(shipping_fee), color: "#ff454e" },
            {
              title: "实付",
              value: order_point + this.pointText,
              color: "#ff454e"
            }
          );
        } else {
          arr.push(
            { title: "运费", value: yuan(shipping_fee), color: "#ff454e" },
            { title: "实付", value: yuan(order_money), color: "#ff454e" }
          );
        }
      } else {
        arr.push(
          { title: "运费", value: yuan(shipping_fee), color: "#ff454e" },
          { title: "优惠金额", value: yuan(promotion_money), color: "#ff454e" }
        );
        if (invoice.type) {
          arr.push({
            title: "税费",
            value: yuan(invoice.tax),
            color: "#ff454e"
          });
        }
        arr.push({
          title: "实付",
          value: order_real_money,
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
