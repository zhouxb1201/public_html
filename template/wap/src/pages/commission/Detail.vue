<template>
  <Layout ref="load" class="commission-detail bg-f8">
    <Navbar :title="navbarTitle" />
    <van-cell-group class="cell-group card-group-box">
      <van-cell class="cell">
        <div>{{$store.state.member.commissionSetText.withdrawable_commission}}（元）</div>
        <van-row type="flex" justify="space-between" class="box">
          <span class="text">{{commission | yuan}}</span>
          <van-button size="small" @click="$router.push('/commission/log')">查看明细</van-button>
        </van-row>
      </van-cell>
    </van-cell-group>
    <van-cell-group class="cell-group card-group-box">
      <van-cell
        :title="$store.state.member.commissionSetText.total_commission"
        :label="'累计获得'+$store.state.member.commissionSetText.commission"
        :value="total_money | yuan"
        class="text"
      />
      <van-cell
        :title="$store.state.member.commissionSetText.withdrawals_commission"
        :label="'提现成功的'+$store.state.member.commissionSetText.commission"
        :value="withdrawals | yuan"
        class="text"
      />
      <van-cell
        :title="$store.state.member.commissionSetText.frozen_commission"
        :label="'待发放的'+$store.state.member.commissionSetText.commission"
        :value="freezing_commission | yuan"
        class="text"
      />
      <van-cell
        :title="$store.state.member.commissionSetText.withdrawal"
        :label="'提现中的'+$store.state.member.commissionSetText.commission"
        :value="apply_withdraw | yuan"
        class="text"
      />
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button
        size="normal"
        block
        round
        type="danger"
        :disabled="isDisabled"
        @click="$router.push('/commission/withdraw')"
      >{{btnText}}</van-button>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_COMMISSIONDETAIL } from "@/api/commission";
export default sfc({
  name: "commission-detail",
  data() {
    return {
      commission: 0,
      total_money: 0,
      withdrawals: 0,
      apply_withdraw: 0,
      freezing_commission: 0
    };
  },
  computed: {
    navbarTitle() {
      const {
        distribution_commission
      } = this.$store.state.member.commissionSetText;
      let title = distribution_commission;
      document.title = title;
      return title;
    },
    isDisabled() {
      return this.commission <= 0 ? true : false;
    },
    btnText() {
      return this.commission <= 0
        ? "提现" +
            this.$store.state.member.commissionSetText.commission +
            "为0，不可提现"
        : "提现";
    }
  },
  activated() {
    const $this = this;
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
    GET_COMMISSIONDETAIL()
      .then(({ data }) => {
        $this.commission = parseFloat(data.commission);
        $this.total_money = data.total_money;
        $this.withdrawals = data.withdrawals;
        $this.apply_withdraw = data.apply_withdraw;
        $this.freezing_commission = data.freezing_commission;
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
