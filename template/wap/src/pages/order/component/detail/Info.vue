<template>
  <CellInfoGroup :columns="columns">
    <van-cell slot="head" icon="orders-o" title="订单信息" />
    <van-cell slot="foot" v-if="showService">
      <div class="cell-service" id="WS-SHOW-CHAT" @click="openKefu">
        <van-icon name="v-icon-kf1" />
        <span>联系客服</span>
      </div>
    </van-cell>
  </CellInfoGroup>
</template>

<script>
import CellInfoGroup from "../CellInfoGroup";
import { formatDate } from "@/utils/util";
import { qlkefu } from "@/mixins";
export default {
  data() {
    return {
      showService: false
    };
  },
  props: {
    detail: Object
  },
  mixins: [qlkefu],
  mounted() {
    const $this = this;
    $this
      .getKefu($this.detail.shop_id)
      .then(data => {
        if ($this.$store.getters.token) {
          $this.loadKefu(data.domain).then(() => {
            $this.showService = true;
            $this.$nextTick(() => {
              $this.serverFlag = true;
              const {
                uid,
                username,
                member_img,
                reg_time
              } = $this.$store.state.member.info;
              qlkefuChat.init({
                uid,
                uName: username,
                avatar: member_img,
                regTime: reg_time || "",
                goods: {
                  goods_id: "",
                  goods_name: "",
                  price: "",
                  pic_cover: ""
                }
              });
            });
          });
        } else {
          $this.showService = true;
        }
      })
      .catch(() => {});
  },
  computed: {
    columns() {
      const {
        shop_name,
        order_status,
        payment_type_name,
        order_no,
        create_time,
        pay_time,
        consign_time,
        finish_time
      } = this.detail;
      let arr = [
        { title: "商家店铺", value: shop_name },
        { title: "订单编号", value: order_no },
        { title: "创建时间", value: formatDate(create_time, "s") }
      ];
      if (order_status !== 0 && order_status !== 5) {
        arr.splice(1, 0, {
          title: "支付方式",
          value: payment_type_name,
          color: "#ff454e"
        });
      }
      if (pay_time) {
        arr.push({ title: "付款时间", value: formatDate(pay_time, "s") });
      }
      if (consign_time) {
        arr.push({ title: "发货时间", value: formatDate(consign_time, "s") });
      }
      if (finish_time) {
        arr.push({ title: "成交时间", value: formatDate(finish_time, "s") });
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
.cell-service {
  display: inline-flex;
  align-items: center;
  font-size: 0.8em;
}
</style>
