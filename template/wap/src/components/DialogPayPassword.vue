<template>
  <div>
    <van-dialog
      v-model="isShowDialog"
      show-cancel-button
      :title="dialogTitle"
      :before-close="onCloseDialog"
      class="dialog-box"
      getContainer="body"
    >
      <div class="password-box">
        <div class="cell-title" v-if="moneyTitle">{{moneyTitle}}</div>
        <div class="cell-money" v-if="moneyText">{{moneyText}}</div>
        <div class="cell-fee-group">
          <van-cell
            :border="false"
            title="手续费"
            class="cell-fee"
            v-if="chargeService"
            :value="chargeService"
          />
          <van-cell
            :border="false"
            title="损耗cpu"
            class="cell-fee"
            v-if="chargeCpu"
            :value="chargeCpu"
          />
          <van-cell
            :border="false"
            title="损耗net"
            class="cell-fee"
            v-if="chargeNet"
            :value="chargeNet"
          />
        </div>
        <van-cell-group>
          <van-field
            v-model="password"
            label="支付密码"
            size="large"
            type="password"
            placeholder="请输入支付密码"
            autofocus
            clearable
          />
        </van-cell-group>
        <div class="tip">由9-20个字母、数字、普通字符组成</div>
        <div class="forget-text">
          <span @click="isShowPopupPayPassword = true">忘记密码？</span>
        </div>
      </div>
    </van-dialog>
    <PopupSetPaymentPassword v-model="isShowPopupPayPassword" :load-data="loadData" />
  </div>
</template>

<script>
import { validPaymentPassword } from "@/utils/validator";
import PopupSetPaymentPassword from "./popup-set-pay-password/PopupSetPaymentPassword";
import { focusout } from "@/mixins";
import { yuan } from "@/utils/filter";
export default {
  data() {
    return {
      password: "",
      isShowDialog: false,
      isShowPopupPayPassword: false,
      chargeService: null, // 手续费
      chargeCpu: null, //损耗cpu
      chargeNet: null //损耗net
    };
  },
  props: {
    dialogTitle: {
      type: String,
      default: "请输入支付密码"
    },
    type: [Number, String],
    money: [Number, String],
    chargeServiceMoney: [Number, String], //虚拟币支付时需要用余额金额计算手续费
    address: String, //虚拟币转账时需要转账地址
    loadData: Function
  },
  watch: {
    isShowDialog(e) {
      if (e && this.type && this.money) {
        this.getChargeService();
      }
    }
  },
  mixins: [focusout],
  computed: {
    moneyTitle() {
      return this.typeTextObj(this.type).text;
    },
    moneyText() {
      const symbol = this.typeTextObj(this.type).symbol;
      const type = this.typeTextObj(this.type).type;
      let text = null;
      symbol && this.money && (text = this.addSymbol(symbol, this.money));
      if (symbol && this.money) {
        text =
          type == "point" ? this.money : this.addSymbol(symbol, this.money);
      }
      return text;
    }
  },
  methods: {
    typeTextObj(type) {
      const {
        balance_style,
        point_style
      } = this.$store.state.member.memberSetText;
      const { commission } = this.$store.state.member.commissionSetText;
      let item = {
        text: null
      };
      const obj = {
        1: {
          text: balance_style + "支付",
          symbol: "yuan"
        },
        2: {
          text: balance_style + "提现",
          symbol: "yuan"
        },
        3: {
          text: balance_style + "转账",
          symbol: "yuan"
        },
        4: {
          text: balance_style + "兑换" + point_style,
          symbol: "yuan"
        },
        5: {
          text: point_style + "兑换" + balance_style,
          symbol: "yuan",
          type: "point"
        },
        6: {
          text: commission + "提现",
          symbol: "yuan"
        },
        7: {
          text: "收益提现",
          symbol: "yuan"
        },
        8: {
          text: "ETH支付",
          symbol: "ETH"
        },
        9: {
          text: "ETH转账",
          symbol: "ETH"
        },
        10: {
          text: "ETH兑换" + point_style,
          symbol: "ETH"
        },
        11: {
          text: point_style + "兑换ETH",
          symbol: "ETH",
          type: "point"
        },
        12: {
          text: "EOS支付",
          symbol: "EOS"
        },
        13: {
          text: "EOS转账",
          symbol: "EOS"
        },
        14: {
          text: "EOS兑换" + point_style,
          symbol: "EOS"
        },
        15: {
          text: point_style + "兑换EOS",
          symbol: "EOS",
          type: "point"
        },
        16: {
          text: commission + "提现",
          symbol: "yuan"
        }
      };
      type && (item = obj[type]);
      return item;
    },
    onClearPassword() {
      this.password = "";
    },
    onCloseDialog(action, done) {
      const $this = this;
      if (action === "confirm") {
        if (!validPaymentPassword($this.password)) {
          done(false);
          return false;
        }
        done();
        $this.$emit("confirm", $this.password);
      } else {
        done();
        $this.$emit("cancel");
      }
    },
    /**
     * 添加余额符号或虚拟币符号
     * type ==> 类型 yuan/bi
     * num  ==> 金额
     */
    addSymbol(type, num) {
      return type == "yuan" ? yuan(num) : num + type;
    },
    getChargeService() {
      let params = {
        types: this.type,
        money: this.money || 0
      };
      if (this.chargeServiceMoney) {
        params.money = this.chargeServiceMoney || 0;
      }
      if (this.address) {
        params.address = this.address;
      }
      const symbol = this.typeTextObj(this.type).symbol;
      this.chargeService = "获取中";
      if (symbol == "EOS") {
        this.chargeCpu = "获取中";
        this.chargeNet = "获取中";
      }
      this.$store
        .dispatch("getPropertyChargeService", params)
        .then(data => {
          this.chargeService = this.addSymbol(symbol, data.charge);
          if (data.cpucharge) {
            this.chargeCpu = this.addSymbol("ms", data.cpucharge);
          }
          if (data.netcharge) {
            this.chargeNet = this.addSymbol("kb", data.netcharge);
          }
        })
        .catch(() => {
          this.chargeService = "获取失败";
          if (symbol == "EOS") {
            this.chargeCpu = "获取失败";
            this.chargeNet = "获取失败";
          }
        });
    }
  },
  beforeDestroy() {
    this.isShowDialog = false;
    this.isShowPopupPayPassword = false;
  },
  deactivated() {
    this.isShowDialog = false;
    this.isShowPopupPayPassword = false;
  },
  components: {
    PopupSetPaymentPassword
  }
};
</script>

<style scoped>
.popup-pay-password {
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
}

.dialog-box {
  position: fixed;
  top: 50%;
  left: 50%;
}

.password-box {
  margin: 10px 0px 10px;
}

.cell-title {
  text-align: center;
  font-size: 12px;
  color: #606266;
}

.cell-money {
  text-align: center;
  color: #ff454e;
  font-weight: 800;
  font-size: 16px;
  padding: 5px 0;
  line-height: 24px;
}

.cell-fee-group {
  margin: 5px 0;
}

.cell-fee {
  font-size: 12px;
  color: #909399;
  padding: 2px 15px;
  line-height: 1;
}

.tip {
  color: #909399;
  text-align: center;
  font-size: 12px;
  margin-top: 10px;
}

.van-toast {
  z-index: 2100 !important;
}

.forget-text {
  margin-top: 10px;
  font-size: 12px;
  text-align: center;
  color: #1989fa;
}
</style>
