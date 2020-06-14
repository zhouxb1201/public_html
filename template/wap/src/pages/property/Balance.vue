<template>
  <Layout ref="load" class="property-balance bg-f8">
    <Navbar :title="navbarTitle" />
    <div>
      <van-cell-group class="cell-group card-group-box">
        <van-cell class="cell">
          <div>可用{{balanceText}}</div>
          <van-row type="flex" justify="space-between" class="box">
            <span class="big-balance first-letter">{{can_use_money | yuan}}</span>
            <van-button size="small" to="/property/log">查看明细</van-button>
          </van-row>
        </van-cell>
      </van-cell-group>
      <van-cell-group class="cell-group card-group-box">
        <van-cell :title="'总'+balanceText" :label="balanceText+'总数'" class="text">
          <div class="big-balance first-letter">{{balance | yuan}}</div>
        </van-cell>
        <van-cell :title="'冻结'+balanceText" :label="'已经冻结'+balanceText" class="text">
          <div class="big-balance first-letter">{{freezing_balance | yuan}}</div>
        </van-cell>
      </van-cell-group>
      <div class="btn-group" :class="'btn-group-'+btnGroup.length">
        <van-button
          size="normal"
          block
          round
          :plain="btn.isPlain"
          type="danger"
          :to="btn.route"
          class="btn"
          v-for="(btn,b) in btnGroup"
          :key="b"
        >{{btn.text}}</van-button>
      </div>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_ASSETBALANCE } from "@/api/property";
export default sfc({
  name: "property-balance",
  data() {
    return {
      balance: "",
      can_use_money: 0,
      freezing_balance: ""
    };
  },
  computed: {
    navbarTitle() {
      let title = this.balanceText;
      document.title = title;
      return title;
    },
    balanceText() {
      return this.$store.state.member.memberSetText.balance_style;
    },
    btnGroup() {
      const {
        is_point_transfer,
        is_transfer,
        withdraw_conf
      } = this.$store.getters.config;
      let arr = [];
      arr.push({
        text: "充值",
        route: "/property/recharge",
        disabled: false,
        isPlain: false
      });
      if (withdraw_conf.is_withdraw_start) {
        arr.push({
          text: "提现",
          route: "/property/withdraw",
          disabled: !this.can_use_money,
          isPlain: false
        });
      }
      if (is_transfer == 1) {
        arr.push({
          text: "转账",
          route: "/property/transfer",
          disabled: false,
          isPlain: false
        });
      }
      if (is_point_transfer == 1) {
        arr.push({
          text: "兑换",
          route: "/property/exchange",
          disabled: false,
          isPlain: false
        });
      }
      arr.forEach((e, i) => (e.isPlain = i + 1 > 2));
      arr.length == 2 && (arr[arr.length - 1].isPlain = true);
      return arr;
    }
  },
  activated() {
    const $this = this;
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
    GET_ASSETBALANCE()
      .then(({ data }) => {
        $this.balance = data.balance || 0;
        $this.can_use_money = data.can_use_money
          ? parseFloat(data.can_use_money)
          : 0;
        $this.freezing_balance = data.freezing_balance || 0;
        $this.$refs.load.success();
      })
      .catch(() => {
        $this.$refs.load.fail();
      });
  },
  methods: {
    isPlain(len) {
      let flag = false;
      if (len >= 3) {
        flag = true;
      } else {
        flag = false;
      }
      return flag;
    }
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

.big-balance {
  color: #ff454e;
  font-size: 20px;
  line-height: 38px;
}

.btn-group {
  display: flex;
  padding: 5px;
  flex-flow: wrap;
}

.btn-group .btn {
  width: calc(50% - 10px);
  margin: 5px 5px;
}
.btn-group-1 .btn,
.btn-group-2 .btn {
  width: 100%;
}
</style>
