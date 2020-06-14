<template>
  <Layout ref="load" class="microshop-detail bg-f8">
    <Navbar />
    <van-cell-group class="cell-group card-group-box">
      <van-cell class="cell">
        <div>可提现收益（元）</div>
        <van-row type="flex" justify="space-between" class="box">
          <span class="text">{{profit | yuan}}</span>
          <van-button size="small" @click="$router.push('/microshop/profit/log')">查看明细</van-button>
        </van-row>
      </van-cell>
    </van-cell-group>
    <van-cell-group class="cell-group card-group-box">
      <van-cell title="累计收益" label="累计获得收益" :value="total_money | yuan" class="text" />
      <van-cell title="已提现收益" label="提现成功的收益" :value="withdrawals | yuan" class="text" />
      <van-cell title="冻结收益" label="待发放的收益" :value="freezing_profit | yuan" class="text" />
      <van-cell title="提现中" label="提现中的收益" :value="apply_withdraw | yuan" class="text" />
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button
        size="normal"
        block
        round
        type="danger"
        :disabled="isDisabled"
        @click="$router.push('/microshop/profit/withdraw')"
      >{{btnText}}</van-button>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_MICROSHOPDETAIL } from "@/api/microshop";
export default sfc({
  name: "microshop-detail",
  data() {
    return {
      profit: 0,
      total_money: 0,
      withdrawals: 0,
      apply_withdraw: 0,
      freezing_profit: 0
    };
  },
  computed: {
    isDisabled() {
      return this.profit <= 0 ? true : false;
    },
    btnText() {
      return this.profit <= 0 ? "提现金额为0，不可提现" : "提现";
    }
  },
  activated() {
    const $this = this;
    GET_MICROSHOPDETAIL()
      .then(({ data }) => {
        $this.profit = parseFloat(data.profit);
        $this.total_money = data.total_money;
        $this.withdrawals = data.withdrawals;
        $this.apply_withdraw = data.apply_withdraw;
        $this.freezing_profit = data.freezing_profit;
        $this.$refs.load.success();
      })
      .catch(() => {
        $this.$refs.load.fail();
      });
  }
});
</script>

<style scoped>
.cell {
  padding: 20px 15px;
}

.cell-group .box {
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #eee;
}

.cell-group .box .text {
  color: #ff454e;
  font-size: 20px;
}

.cell-group .text >>> .van-cell__value {
  color: #ff454e;
  font-size: 20px;
  line-height: 38px;
}
</style>
