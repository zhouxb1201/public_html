<template>
  <Layout ref="load" class="property-withdraw bg-f8">
    <Navbar :title="navbarTitle" />
    <van-cell-group>
      <van-cell class="cell-panel" :title="'可提现'+$store.state.member.memberSetText.balance_style">
        <div class="text-left text-maintone">{{can_use_money | yuan}}</div>
      </van-cell>
      <CellWithdrawAccount
        :account-id="params.bank_account_id"
        @select="onSelectAccount"
        :withdraw-type="$store.getters.config.withdraw_conf.withdraw_message"
      />
      <van-field
        :label="'提现'+$store.state.member.memberSetText.balance_style"
        type="number"
        :placeholder="'请输入提现'+$store.state.member.memberSetText.balance_style"
        v-model.number="params.cash"
      />
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button
        size="normal"
        block
        round
        type="danger"
        :disabled="isWithdraw"
        :loading="isLoading"
        @click="onWithdraw"
      >{{withdrawBtnText}}</van-button>
    </div>
    <DialogPayPassword
      ref="DialogPayPassword"
      type="2"
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
import { GET_ASSETWITHDRAWINFO, APPLY_ASSETWITHDRAW } from "@/api/property";
import { payPassword } from "@/mixins";
export default sfc({
  name: "property-withdraw",
  data() {
    return {
      isLoading: false,

      can_use_money: 0,
      withdraw_cash_min: 0,
      is_start: 0,
      wx_openid: "",
      is_alipay: false,
      is_wpy: false,

      params: {
        bank_account_id: "",
        cash: "",
        type: null,
        password: ""
      }
    };
  },
  mixins: [payPassword],
  computed: {
    navbarTitle() {
      const { balance_style } = this.$store.state.member.memberSetText;
      let title = balance_style + "提现";
      document.title = title;
      return title;
    },
    isWithdraw() {
      return this.is_start && this.is_start == 1
        ? this.can_use_money && this.can_use_money > 0
          ? false
          : true
        : true;
    },
    withdrawBtnText() {
      return this.is_start && this.is_start == 1
        ? this.can_use_money && this.can_use_money > 0
          ? "提现"
          : "可用" +
            this.$store.state.member.memberSetText.balance_style +
            "为0，不可提现"
        : "未开启提现";
    },
    payMoney() {
      let money = null;
      this.params.cash && (money = parseFloat(this.params.cash));
      return money;
    }
  },
  mounted() {
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      GET_ASSETWITHDRAWINFO()
        .then(({ data }) => {
          $this.can_use_money = data.balance;
          $this.is_start = data.is_start;
          $this.is_alipay = data.is_alipay == 1 ? true : false;
          $this.is_wpy = data.is_wpy == 1 ? true : false;
          $this.wx_openid = data.wx_openid;
          $this.withdraw_cash_min = parseFloat(
            data.withdraw_cash_min ? data.withdraw_cash_min : 0
          );
          $this.$refs.load.success();
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    onSelectAccount(item) {
      this.params.bank_account_id = item.type == 2 ? "" : item.id;
      this.params.type = item.type;
    },
    onPayPassword(password) {
      this.params.password = password;
      this.onWithdraw();
    },
    onWithdraw() {
      const $this = this;
      if (!$this.params.bank_account_id && $this.params.type != 2) {
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
      if ($this.params.cash < $this.withdraw_cash_min) {
        $this.$Toast("提现金额不可低于最小提现金额！");
        return false;
      }
      if ($this.params.cash > $this.can_use_money) {
        $this.$Toast(
          "提现金额高于可提现" +
            this.$store.state.member.memberSetText.balance_style +
            "！"
        );
        return false;
      }
      // console.log($this.params);
      // return;
      $this.isLoading = true;
      $this
        .validPayPassword($this.params.password)
        .then(() => {
          APPLY_ASSETWITHDRAW($this.params)
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
.password-dialog {
  top: initial;
  bottom: 150px;
}
.password-box {
  margin: 20px 10px;
}
.van-toast {
  z-index: 2100 !important;
}
</style>
