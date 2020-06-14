<template>
  <Layout ref="load" class="property-recharge bg-f8">
    <Navbar />
    <van-cell-group>
      <van-field
        v-model.number="params.recharge_money"
        label="充值金额"
        type="number"
        placeholder="请输入充值金额"
      />
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button
        size="normal"
        block
        round
        type="danger"
        @click="onRecharge"
        :loading="isLoading"
      >充值</van-button>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import {
  RECHARGE_ASSETBALANCELOG,
  CREATE_ASSETRECHARORDER
} from "@/api/property";
import { isIos } from "@/utils/util";
export default sfc({
  name: "property-recharge",
  data() {
    return {
      isLoading: false,
      params: {
        recharge_money: "",
        out_trade_no: ""
      }
    };
  },
  mounted() {
    const $this = this;
    RECHARGE_ASSETBALANCELOG()
      .then(res => {
        $this.params.out_trade_no = res.data.out_trade_no;
        $this.$refs.load.success();
      })
      .catch(() => {
        $this.$refs.load.fail();
      });
  },
  methods: {
    onRecharge() {
      const $this = this;
      const value = $this.params.recharge_money;
      const isProceeds = $this.$route.hash == "#proceeds";
      if (isProceeds) {
        $this.params.type = 5; //渠道商货款充值类型
      }
      if (isNaN(parseFloat(value))) {
        $this.$Toast("请输入充值金额！");
        return false;
      }
      if (value <= 0) {
        $this.$Toast("充值金额不能低于0！");
        return false;
      }
      $this.isLoading = true;
      CREATE_ASSETRECHARORDER($this.params)
        .then(({ data }) => {
          if (isIos()) {
            location.assign(
              `${$this.$store.state.domain}/wap/pay/payment?out_trade_no=${data.out_trade_no}#recharge`
            );
          } else {
            $this.$router.push({
              name: "pay-payment",
              query: { out_trade_no: data.out_trade_no },
              hash: "#recharge"
            });
          }
          $this.isLoading = false;
        })
        .catch(() => {
          $this.isLoading = false;
        });
    }
  }
});
</script>

<style scoped>
</style>
