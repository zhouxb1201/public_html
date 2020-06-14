<template>
  <Layout ref="load" class="channel-finance bg-f8">
    <Navbar />
    <van-cell-group class="cell-group card-group-box">
      <van-cell class="cell">
        <div>可用{{$store.state.member.memberSetText.balance_style}}</div>
        <van-row type="flex" justify="space-between" class="box">
          <span class="text">{{can_use_money | yuan}}</span>
          <van-button size="small" :to="logRoute">查看明细</van-button>
        </van-row>
      </van-cell>
    </van-cell-group>
    <van-cell-group class="cell-group card-group-box">
      <van-cell
        :title="'总'+$store.state.member.memberSetText.balance_style"
        :label="$store.state.member.memberSetText.balance_style+'总数'"
        :value="balance | yuan"
        class="text"
      />
      <van-cell
        :title="'冻结'+$store.state.member.memberSetText.balance_style"
        :label="'已经冻结'+$store.state.member.memberSetText.balance_style"
        :value="freezing_balance | yuan"
        class="text"
      />
      <van-cell title="我的利润" label="全部利润收入" :value="my_profit | yuan" class="text" />
      <van-cell title="我的奖金" label="全部奖金收入" :value="my_bonus | yuan" class="text" />
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button size="normal" block round type="danger" :to="rechargeRoute">充值</van-button>
      <van-button
        v-if="isWithdraw"
        size="normal"
        block
        round
        type="danger"
        plain
        :disabled="disWithdraw"
        to="/property/withdraw"
      >{{withdrawBtnText}}</van-button>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_FINANCEINFO } from "@/api/channel";
export default sfc({
  name: "channel-finance",
  data() {
    return {
      is_use: 0,
      can_use_money: 0,
      balance: 0,
      freezing_balance: 0,
      my_profit: 0,
      my_bonus: 0,
      is_proceeds: false
    };
  },
  computed: {
    isWithdraw() {
      return this.is_use && this.is_use == 1 && !this.is_proceeds;
    },
    disWithdraw() {
      return !(this.can_use_money && this.can_use_money > 0);
    },
    withdrawBtnText() {
      return this.disWithdraw
        ? "可用" +
            this.$store.state.member.memberSetText.balance_style +
            "为0，不可提现"
        : "提现";
    },
    logRoute() {
      return this.is_proceeds ? "/property/log#proceeds" : "/property/log";
    },
    rechargeRoute() {
      return this.is_proceeds
        ? "/property/recharge#proceeds"
        : "/property/recharge";
    }
  },
  activated() {
    GET_FINANCEINFO()
      .then(({ data }) => {
        this.is_use = data.is_use;
        this.can_use_money = data.can_use_money;
        this.balance = data.balance;
        this.freezing_balance = data.freezing_balance;
        this.my_profit = data.my_profit;
        this.my_bonus = data.my_bonus;
        this.is_proceeds = !!data.is_proceeds;
        this.$refs.load.success();
      })
      .catch(() => {
        this.$refs.load.fail();
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

.foot-btn-group .btn {
  margin-bottom: 15px;
}
</style>
