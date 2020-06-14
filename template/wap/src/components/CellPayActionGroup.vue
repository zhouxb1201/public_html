<template>
  <van-radio-group v-model="type" @change="change">
    <van-cell :value="title" />
    <van-cell-group :border="false" v-if="payAction">
      <template v-for="(item,index) in payAction">
        <van-cell :clickable="!item.disabled">
          <van-icon
            slot="icon"
            class="icon"
            :name="item.icon"
            :color="item.iconcolor"
            :size="item.size"
          />
          <div slot="title" class="text-nowrap" @click="type = !item.disabled ? item.name : type">
            <span>{{item.title}}</span>
            <span class="fs-12 text-regular" v-if="item.tip">({{item.tip}})</span>
          </div>
          <van-radio
            slot="right-icon"
            :name="item.name"
            :label-disabled="item.disabled"
            :disabled="item.disabled"
            @click="type = !item.disabled ? item.name : type"
          />
        </van-cell>
        <CellSelectBankCardGroup
          v-if="item.name=='tlpay'"
          v-show="type=='tlpay'"
          :info="bankCardInfo"
          @select="selectBankCard"
        />
      </template>
    </van-cell-group>
    <van-cell v-else>
      <div class="empty" v-text="'没有可用的支付方式'" />
    </van-cell>
  </van-radio-group>
</template>

<script>
import CellSelectBankCardGroup from "@/components/CellSelectBankCardGroup";
import { isEmpty } from "@/utils/util";
import { yuan, bi } from "@/utils/filter";
const defaultBlockchain = {
  show: false,
  balance: 0,
  money: 0,
  paymoney: 0
};
export default {
  data() {
    return {
      type: this.value
    };
  },
  props: {
    value: String,
    title: {
      type: String,
      default: "支付方式"
    },
    balance: Number,
    /**
     * 显示支付方式(默认为开启,eth/eos默认不开启)
     * bpay ==> 余额支付
     * wpay ==> 微信支付
     * apay ==> 支付宝支付
     * ethpay ==> eth支付
     * eospay ==> eos支付
     * tlpay ==> 通联(银行卡)支付
     * ppay ==> 渠道商货款支付
     */
    bpay: {
      type: Boolean,
      default: true
    },
    wpay: {
      type: Boolean,
      default: true
    },
    apay: {
      type: Boolean,
      default: true
    },
    ethpay: {
      type: [Boolean, String, Object],
      default: () => defaultBlockchain
    },
    eospay: {
      type: [Boolean, String, Object],
      default: () => defaultBlockchain
    },
    tlpay: {
      type: Boolean,
      default: true
    },
    glopay: {
      type: Boolean,
      default: true
    },
    bankCardInfo: [Boolean, String, Object],
    ppay: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    payAction() {
      const {
        ali_pay,
        wechat_pay,
        bpay,
        ethpay,
        eospay,
        tlpay,
        glopay
      } = this.$store.getters.config;
      const { balance } = this.$store.state.member.info;
      const { balance_style } = this.$store.state.member.memberSetText;
      const balanceText = parseFloat(
        !isNaN(this.balance) ? this.balance : balance
      );
      let arr = [];
      if (bpay && this.bpay) {
        arr.push({
          name: "bpay",
          icon: "v-icon-balance3",
          iconcolor: "#ff454e",
          title: `${balance_style}支付`,
          tip: `${balance_style} ${balanceText.toFixed(2)}元`,
          disabled: !balanceText
        });
      }
      if (wechat_pay && this.wpay) {
        arr.push({
          name: "wechat",
          icon: "v-icon-wx-pay",
          iconcolor: "#00c403",
          title: "微信支付",
          tip: "",
          disabled: false
        });
      }
      if (glopay && this.glopay) {
        arr.push({
          name: "glopay",
          icon: "v-icon-wx-pay",
          iconcolor: "#00c403",
          title: "微信支付(跨境)",
          tip: "",
          disabled: false
        });
      }
      if (ali_pay && this.apay) {
        arr.push({
          name: "alipay",
          icon: "v-icon-alipay",
          iconcolor: "#009fe8",
          title: "支付宝支付",
          tip: this.$store.state.isWeixin ? "需使用系统浏览器" : "",
          disabled: false
        });
      }
      if (this.ethpay.show && ethpay) {
        arr.push({
          name: "ethpay",
          icon: "v-icon-eth",
          iconcolor: "#f52929",
          title: "ETH",
          tip:
            this.ethpay.loadingText ||
            "余额 " + this.ethpay.balance + " ETH ≈ " + yuan(this.ethpay.money),
          disabled: !parseFloat(this.ethpay.money)
        });
      }
      if (this.eospay.show && eospay) {
        arr.push({
          name: "eospay",
          icon: "v-icon-eos",
          iconcolor: "#ff8f00",
          title: "EOS",
          tip:
            this.eospay.loadingText ||
            "余额 " + this.eospay.balance + " EOS ≈ " + yuan(this.eospay.money),
          disabled: !parseFloat(this.eospay.money)
        });
      }
      if (this.tlpay && tlpay) {
        arr.push({
          name: "tlpay",
          icon: "v-icon-card",
          iconcolor: "#1a88ff",
          size: "16px",
          title: "银行卡",
          tip: "",
          disabled: false
        });
      }
      if (this.ppay) {
        arr.push({
          name: "ppay",
          icon: "v-icon-balance3",
          iconcolor: "#ff454e",
          title: `货款支付`,
          tip: `${balance_style} ${balanceText.toFixed(2)}元`,
          disabled: !balanceText
        });
      }

      return isEmpty(arr) ? "" : arr;
    }
  },
  methods: {
    change(e) {
      this.$emit("input", e);
      this.$emit("change", e);
    },
    selectBankCard(item) {
      this.$emit("selectBankCard", item);
    }
  },
  components: {
    CellSelectBankCardGroup
  }
};
</script>

<style scoped>
.icon {
  font-size: 20px;
  width: 30px;
  height: 24px;
  text-align: center;
  line-height: 24px;
  margin-right: 4px;
}

.text-nowrap {
  white-space: nowrap;
}

.van-cell.disabled {
  color: #999;
  background-color: #e8e8e8;
}
</style>
