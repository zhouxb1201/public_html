<template>
  <Layout ref="load" class="blockchain-exchange bg-f8">
    <Navbar :title="navbarTitle" />
    <ExchangeGroup
      :type="$route.params.type"
      :info="info"
      :params="params"
      :poundage="poundage"
      @ex-change="onExChange"
      @sd-change="onSdChange"
    />
    <div class="foot-btn-group">
      <van-button
        size="normal"
        round
        type="danger"
        block
        :disabled="isDisabled"
        :loading="isLoading"
        @click="onExchange"
      >{{exchangeBtnText}}</van-button>
    </div>
    <DialogPayPassword
      ref="DialogPayPassword"
      :type="feeType"
      :money="payMoney"
      @confirm="onPayPassword"
      @cancel="isLoading=false"
      :load-data="initBlockchainSetData"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import ExchangeGroup from "./component/ExchangeGroup";
import DialogPayPassword from "@/components/DialogPayPassword";
import blockchain from "./mixin";
import { payPassword } from "@/mixins";
import { GET_BLOCKCHAINETHGAS, EXCHANGE_BLOCKCHAIN } from "@/api/blockchain";
import { yuan, bi } from "@/utils/filter";
export default sfc({
  name: "blockchain-exchange",
  data() {
    return {
      info: {},
      exchangeFlag: false, // 是否开启兑换
      // lowPoint: 0, // 最低兑换积分
      params: {
        exchange_type: 1,
        password: ""
      },
      poundage: {},
      isLoading: false
    };
  },
  mixins: [blockchain, payPassword],
  computed: {
    navbarTitle() {
      const type = this.$route.params.type
        ? this.$route.params.type.toUpperCase()
        : "";
      let title = type + "兑换";
      if (title) document.title = title;
      return title;
    },
    pointText() {
      return this.$store.state.member.memberSetText.point_style;
    },
    isDisabled() {
      const type = this.$route.params.type;
      if (!this.exchangeFlag) {
        return true;
      }
      if (
        this.params.exchange_type == 1 &&
        (this.$store.state.member.info.point <= 0 ||
          this.$store.state.member.info.point < this.info.lowPoint)
      ) {
        return true;
      }
      if (
        this.params.exchange_type == 2 &&
        parseFloat(this.info.balance) <= 0
      ) {
        return true;
      }
      return false;
    },
    exchangeBtnText() {
      const type = this.$route.params.type;
      let text = "";
      if (!this.exchangeFlag) {
        text = "未开启" + this.pointText + "与" + type.toUpperCase() + "互换";
      } else if (
        this.params.exchange_type == 1 &&
        (this.$store.state.member.info.point <= 0 ||
          this.$store.state.member.info.point < this.info.lowPoint)
      ) {
        text = "可用" + this.pointText + "不足，无法兑换！";
      } else if (
        this.params.exchange_type == 2 &&
        parseFloat(this.info.balance) <= 0
      ) {
        text = "可用" + type.toUpperCase() + "不足，无法兑换！";
      } else {
        text = "兑换";
      }
      return text;
    },
    feeType() {
      const pageType = this.$route.params.type;
      let type = null;
      if (pageType == "eth") {
        type = this.params.exchange_type == 1 ? 11 : 10;
      } else {
        type = this.params.exchange_type == 1 ? 15 : 14;
      }
      return type;
    },
    payMoney() {
      const pageType = this.$route.params.type;
      let money = null;
      money =
        this.params.exchange_type == 1
          ? this.params.point
          : this.params[pageType];
      return money;
    }
  },
  methods: {
    // 支付密码设置完成重新获取相关设置
    initBlockchainSetData() {
      this.$store.dispatch("getBlockchainSet", true);
    },
    loadData(data) {
      const type = this.$route.params.type;
      this.exchangeFlag =
        this.$store.state.blockchain.config[type + "_point"] == 1
          ? true
          : false;
      this.info = data;
      this.info.lowPoint = this.$store.state.blockchain.config[
        type + "_point_low"
      ];
      if (type == "eth") {
        this.params.gas = 1;
        this.getGas();
      }
    },
    onExChange(e) {
      if (e == 1) {
        const type = this.$route.params.type;
        delete this.params[type];
      }
      if (e == 2) {
        delete this.params.point;
      }
      this.params.exchange_type = e;
      this.getGas();
    },
    onSdChange(e) {
      this.params.gas = e;
      this.getGas();
    },
    // 获取gas费用
    getGas() {
      const type = this.$route.params.type;
      if (type == "eth") {
        this.initPoundage();
        GET_BLOCKCHAINETHGAS({
          gas: this.params.gas,
          type: this.params.exchange_type
        })
          .then(({ data }) => {
            this.poundage = data;
            this.poundage.loadingFlag = true;
          })
          .catch(() => {
            this.initPoundage();
          });
      }
    },
    // 初始化手续费
    initPoundage() {
      this.poundage = {};
      this.poundage.loadingFlag = false;
    },
    // 验证密码
    onPayPassword(password) {
      this.params.password = password;
      this.onExchange();
    },
    // 确定兑换
    onExchange() {
      const type = this.$route.params.type;
      const params = this.validator(type);
      if (params) {
        this.isLoading = true;
        this.validPayPassword(params.password, true)
          .then(() => {
            EXCHANGE_BLOCKCHAIN(type, params)
              .then(({ message }) => {
                this.$Toast.success(message);
                this.$router.replace({
                  name: "blockchain-centre",
                  params: { type }
                });
              })
              .catch(() => {
                this.isLoading = false;
              });
          })
          .catch(() => {
            this.isLoading = false;
            this.params.password = "";
          });
      }
    },
    /**
     * 验证
     * type  ==> eth/eos
     */
    validator(type) {
      const params = this.params;
      const point = parseInt(this.$store.state.member.info.point);
      const balance = parseFloat(this.info.balance);
      if (params.exchange_type == 1) {
        if (!point) {
          this.$Toast("可用" + this.pointText + "不足！");
          return false;
        }
        if (parseInt(params.point) < 1) {
          this.$Toast("兑换" + this.pointText + "不可小于1！");
          return false;
        }
        if (!params.point) {
          this.$Toast("请输入兑换" + this.pointText + "！");
          return false;
        }
        if (params.point < this.info.lowPoint) {
          this.$Toast(
            "兑换" + this.pointText + "不可小于最低兑换" + this.pointText + "！"
          );
          return false;
        }
        if (params.point > point) {
          this.$Toast(
            "兑换" + this.pointText + "不可大于可用" + this.pointText + "！"
          );
          return false;
        }
      }
      if (params.exchange_type == 2) {
        if (balance <= 0) {
          this.$Toast("可用" + type.toUpperCase() + "不足！");
          return false;
        }
        if (!params[type]) {
          this.$Toast("请输入兑换" + type.toUpperCase() + "！");
          return false;
        }
        if (parseFloat(params[type]) <= 0) {
          this.$Toast("兑换" + type.toUpperCase() + "不可小于0！");
          return false;
        }
        if (params[type] > balance) {
          this.$Toast(
            "兑换" +
              type.toUpperCase() +
              "不可大于可用" +
              type.toUpperCase() +
              "！"
          );
          return false;
        }
        params[type] = bi(params[type], type == "eth" ? 6 : 4);
      }
      return params;
    }
  },
  components: {
    ExchangeGroup,
    DialogPayPassword
  }
});
</script>

<style scoped>
</style>
