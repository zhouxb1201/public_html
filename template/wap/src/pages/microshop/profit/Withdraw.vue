<template>
  <Layout ref="load" class="microshop-withdraw bg-f8">
    <Navbar />
    <van-cell-group>
      <van-cell class="cell-panel" title="可提现金额">
        <div class="text-left text-maintone">{{profit | yuan}}</div>
      </van-cell>
      <van-cell class="cell-panel" title="提现方式">
        <div class="text-left">
          <van-radio-group class="cell-radio-group" v-model="active">
            <van-radio :name="index" v-for="(item,index) in typeList" :key="index">{{item.text}}</van-radio>
          </van-radio-group>
        </div>
      </van-cell>
      <CellWithdrawAccount
        v-show="showAuccount"
        :account-id="params.account_id"
        :withdraw-type="withdrawals_type"
        @select="onSelectAccount"
      />
      <van-field label="提现金额" type="number" placeholder="请输入提现金额" v-model.number="params.cash" />
    </van-cell-group>
    <van-cell title="提现明细" to="/microshop/profit/log" is-link class="cell" />
    <div class="foot-btn-group">
      <van-button
        size="normal"
        block
        round
        type="danger"
        :disabled="isDisabled"
        @click="onWithdraw"
        :loading="isLoading"
      >{{btnText}}</van-button>
    </div>
    <DialogPayPassword
      ref="DialogPayPassword"
      type="7"
      :money="payMoney"
      @confirm="onPayPassword"
      @cancel="isLoading=false"
      :load-data="loadData"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import CellWithdrawAccount from "@/components/CellWithdrawAccount";
import DialogPayPassword from "@/components/DialogPayPassword";
import { GET_WITHDRAWSINFO, APPLY_WITHDRAW } from "@/api/microshop";
import { payPassword } from "@/mixins";
export default sfc({
  name: "microshop-withdraw",
  data() {
    return {
      isLoading: false,
      profit: 0,
      withdrawals_min: 0,
      active: 0,
      params: {
        cash: "",
        account_id: "",
        password: ""
      },
      wx_openid: null,
      withdrawals_type: []
    };
  },
  mixins: [payPassword],
  computed: {
    showAuccount() {
      const flag = this.typeList[this.active]
        ? this.typeList[this.active].showAuccount
        : false;
      // 余额提现account_id为-1
      this.params.account_id = !flag ? "-1" : "";
      return flag;
    },
    typeList() {
      const withdrawTypeArr = this.withdrawals_type
        ? this.withdrawals_type
        : [];
      const arr = [];
      if (withdrawTypeArr.some(e => e == 5)) {
        arr.push({
          showAuccount: false,
          text: this.$store.state.member.memberSetText.balance_style
        });
      }
      if (withdrawTypeArr.some(e => e == 1 || e == 2 || e == 3 || e == 4)) {
        arr.push({
          showAuccount: true,
          text: "第三方"
        });
      }
      return arr;
    },
    isDisabled() {
      return this.profit <= 0 ? true : false;
    },
    btnText() {
      return this.profit <= 0 ? "提现金额为0，不可提现" : "提现";
    },
    payMoney() {
      let money = null;
      this.params.cash && (money = parseFloat(this.params.cash));
      return money;
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      GET_WITHDRAWSINFO()
        .then(({ data }) => {
          $this.profit = parseFloat(data.profit);
          $this.withdrawals_min = data.withdrawals_min
            ? parseFloat(data.withdrawals_min)
            : 0;
          $this.wx_openid = data.wx_openid;
          $this.withdrawals_type = data.withdrawals_type;
          $this.$refs.load.success();
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    // 获取选中账户id
    onSelectAccount({ id, type }) {
      // 微信提现account_id为-2
      this.params.account_id = type == 2 ? "-2" : id;
    },
    // 获取支付密码
    onPayPassword(password) {
      this.params.password = password;
      this.onWithdraw();
    },
    onWithdraw() {
      const $this = this;
      if (!$this.params.account_id && this.showAuccount) {
        $this.$Toast("请选择提现账户！");
        return false;
      }
      if (isNaN(parseFloat($this.params.cash))) {
        $this.$Toast("请输入提现金额！");
        return false;
      }
      if ($this.params.cash <= 0) {
        $this.$Toast("提现金额不能低于0！");
        return false;
      }
      if ($this.params.cash < $this.withdrawals_min) {
        $this.$Toast("提现金额不能低于最低提现额度！");
        return false;
      }
      if ($this.params.cash > $this.profit) {
        $this.$Toast("提现金额不可高于可提现金额！");
        return false;
      }
      // console.log($this.params);
      // return;
      $this.isLoading = true;
      $this
        .validPayPassword($this.params.password)
        .then(() => {
          APPLY_WITHDRAW($this.params)
            .then(({ message }) => {
              $this.$Toast.success(message);
              $this.$router.back();
            })
            .catch(() => {
              $this.isLoading = false;
            });
        })
        .catch(() => {
          $this.isLoading = false;
          $this.params.password = "";
        });
    }
  },
  components: {
    CellWithdrawAccount,
    DialogPayPassword
  }
});
</script>

<style scoped>
.cell {
  margin: 10px 0;
}
</style>
