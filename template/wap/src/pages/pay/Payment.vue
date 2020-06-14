<template>
  <Layout ref="load" class="pay-payment bg-f8">
    <Navbar :isMenu="false" :isShowLeft="false" />
    <div>
      <div class="payment-info">
        <div>应付金额</div>
        <div class="money-text">{{ payMoneyText }}</div>
        <div class="limit-time" v-if="isShow">
          请在
          <CountDown :time="endTime" @callback="onTimeEnd" done-text="00:00:00">
            <div class="time-end">
              <span>{%h}</span>
              <i>:</i>
              <span>{%m}</span>
              <i>:</i>
              <span>{%s}</span>
            </div> </CountDown
          >内完成支付
        </div>
      </div>
      <div class="payment-type">
        <CellPayActionGroup
          v-model="payType"
          @change="onPayTypeChange"
          :balance="balance"
          :bpay="isShowBPay"
          :wpay="isShowWPay"
          :apay="isShowAPay"
          :tlpay="isShowTlPay"
          :ethpay="ethpay"
          :eospay="eospay"
          :ppay="isShowPPay"
          :bankCardInfo="bankCardInfo"
          @selectBankCard="onSelectBankCard"
        />
      </div>
    </div>
    <DialogPayPassword
      @confirm="onPayPassword"
      @cancel="isLoading = false"
      ref="DialogPayPassword"
      :type="feeType"
      :money="payMoney"
      :charge-service-money="chargeServiceMoney"
      :load-data="loadData"
    />
    <PopupBankCardSms
      v-model="showBankCardSms"
      :params="bankCardParams"
      type="pay"
      @success="bankCardPaySuccess"
      @close="bankCardPayClose"
    />
    <div class="fixed-foot-btn-group">
      <van-button
        size="normal"
        block
        round
        type="primary"
        @click="onPay"
        :disabled="disabledPay"
        :loading="isLoading"
        >确认付款 ({{ payMoneyText }})</van-button
      >
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import CountDown from "@/components/CountDown";
import CellPayActionGroup from "@/components/CellPayActionGroup";
import DialogPayPassword from "@/components/DialogPayPassword";
import PopupBankCardSms from "@/components/PopupBankCardSms";
import {
  GET_PAYINFO,
  PAY_BALANCE,
  PAY_ALIPAY,
  PAY_BLOCKCHAIN,
  APPLY_BANKCARDSMS,
  PAY_BANKCARD,
  PAY_PROCEEDS,
  PAY_GLOPAY
} from "@/api/pay";
import { GET_ORDERPAYINFO } from "@/api/order";
import { GET_CHANNELORDERPAYINFO } from "@/api/channel";
// import { GET_AGENTORDERPAYINFO } from "@/api/agent";
import { PAY_INTEGRALPAY, GET_INTEGRALPAYINFO } from "@/api/integral";
import { payPassword } from "@/mixins";
import { isEmpty } from "@/utils/util";
import { _decode, _encode } from "@/utils/base64";
import { yuan, bi } from "@/utils/filter";

export default sfc({
  name: "pay-payment",
  data() {
    return {
      out_trade_no: null,
      payType: null,
      pay_money: 0,
      endTime: "",
      bpayPassword: null,
      balance: 0,
      point: 0,
      isLoading: false,
      isShow: true,
      integral_data: null, //获取积分商城配置的数据
      ethpay: {
        show: false,
        balance: 0,
        money: 0,
        paymoney: 0,
        loadingText: null
      },
      eospay: {
        show: false,
        balance: 0,
        money: 0,
        paymoney: 0,
        loadingText: null
      },
      bankCardInfo: null, //银行卡信息
      bankCardParams: {},
      showBankCardSms: false,
      chargeServiceMoney: 0,
      is_proceeds_pay: false //是否货款支付
    };
  },
  mixins: [payPassword],
  computed: {
    pageType() {
      let type = "buy";
      const hash = this.$route.hash;
      if (hash == "#order") {
        type = "order";
      } else if (hash == "#recharge") {
        type = "recharge";
      } else if (hash == "#channel") {
        type = "channel";
      } else if (hash == "#agent") {
        type = "agent";
      } else if (hash == "#integral") {
        type = "integral";
      }
      return type;
    },
    // 支付密码弹窗手续费类型
    feeType() {
      let type = null;
      if (this.payType == "bpay") {
        type = 1;
      } else if (this.payType == "ethpay") {
        type = 8;
      } else if (this.payType == "eospay") {
        type = 12;
      }
      return type;
    },
    // 支付密码弹窗显示支付金额
    payMoney() {
      let money = parseFloat(this.pay_money);
      if (this.payType == "ethpay" || this.payType == "eospay") {
        this.chargeServiceMoney = parseFloat(this.pay_money);
        money = this[this.payType].paymoney;
      }
      return money;
    },
    // 是否显示余额支付
    isShowBPay() {
      return this.pageType != "recharge" && !this.is_proceeds_pay;
    },
    // 是否显示微信支付
    isShowWPay() {
      return !this.is_proceeds_pay;
    },
    // 是否显示支付宝支付
    isShowAPay() {
      return !this.is_proceeds_pay;
    },
    // 是否显示银行卡支付
    isShowTlPay() {
      return !this.is_proceeds_pay;
    },
    // 是否显示货款支付
    isShowPPay() {
      return !!this.is_proceeds_pay;
    },
    disabledPay() {
      return !this.payType;
    },
    payMoneyText() {
      let text = yuan(this.pay_money);
      if (this.payType == "ethpay" || this.payType == "eospay") {
        text = this[this.payType].paymoney + " " + this[this.payType].name;
      }
      return text;
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      // hash 为order时，表示从订单列表或者详情进来支付
      if ($this.$route.query.order_id && $this.pageType == "order") {
        GET_ORDERPAYINFO($this.$route.query.order_id)
          .then(res => {
            $this.getPayInfo(
              res,
              res.data && res.data.out_trade_no ? res.data.out_trade_no : ""
            );
          })
          .catch(error => {
            $this.$refs.load.fail();
          });
      } else if (
        $this.$route.query.order_id &&
        ($this.pageType == "channel" || $this.pageType == "agent")
      ) {
        // 渠道商采购订单支付逻辑
        const apiFn =
          $this.pageType == "agent"
            ? GET_AGENTORDERPAYINFO
            : GET_CHANNELORDERPAYINFO;
        apiFn($this.$route.query.order_id)
          .then(res => {
            $this.getPayInfo(
              res,
              res.data && res.data.out_trade_no ? res.data.out_trade_no : ""
            );
          })
          .catch(error => {
            $this.$refs.load.fail();
          });
      } else if (
        $this.$route.query.order_data &&
        $this.pageType == "integral"
      ) {
        //从积分商城订单进行支付逻辑
        $this.integral_data = JSON.parse(
          _decode($this.$route.query.order_data)
        );
        $this.pay_money = $this.$route.query.pay_money;
        $this.isShow = false;
        GET_INTEGRALPAYINFO().then(res => {
          $this.balance = parseFloat(res.data.balance);
          $this.point = res.data.point;
          $this.$refs.load.success();
        });
      } else {
        $this.out_trade_no = $this.$route.query.out_trade_no;
        GET_PAYINFO($this.out_trade_no)
          .then(res => {
            $this.getPayInfo(res, $this.out_trade_no);
          })
          .catch(error => {
            $this.$refs.load.fail();
            $this.$router.back();
          });
      }
    },
    onPayTypeChange(e) {
      if (e == "bpay" && this.bpayPassword) {
        this.bpayPassword = null;
      }
    },
    getPayInfo(res, out_trade_no) {
      const $this = this;
      if (res.code === 1) {
        $this.endTime = parseFloat(res.data.end_time) * 1000;
        $this.pay_money = res.data.pay_money;
        $this.out_trade_no = out_trade_no;
        $this.balance = parseFloat(res.data.balance);
        $this.is_proceeds_pay = !!res.data.is_proceeds;
        $this.$refs.load.success();
        $this.getBlockchainPayInfo(out_trade_no);
      } else if (res.code == 2) {
        $this.$router.replace({
          name: "pay-result",
          query: {
            out_trade_no: out_trade_no
          }
        });
      } else {
        $this.$Toast(res.message);
        $this.$router.back();
      }
    },
    // 获取虚拟币相关支付信息
    getBlockchainPayInfo(out_trade_no) {
      if (
        this.$store.state.config.addons.blockchain &&
        !this.is_proceeds_pay &&
        (this.$store.getters.config.ethpay || this.$store.getters.config.eospay)
      ) {
        this.ethpay.show = !!this.$store.getters.config.ethpay;
        this.eospay.show = !!this.$store.getters.config.eospay;
        this.ethpay.loadingText = "ETH余额加载中...";
        this.eospay.loadingText = "EOS余额加载中...";
        this.$store
          .dispatch("getBlockchainPayInfo", out_trade_no)
          .then(data => {
            this.ethpay.loadingText = null;
            this.ethpay.balance = data.eth_balance;
            this.ethpay.money = data.eth_money;
            this.ethpay.paymoney = data.eth_paymoney;
            this.ethpay.name = "ETH";

            this.eospay.loadingText = null;
            this.eospay.balance = data.eos_balance;
            this.eospay.money = data.eos_money;
            this.eospay.paymoney = data.eos_paymoney;
            this.eospay.name = "EOS";
          });
      }
    },
    onTimeEnd() {
      const $this = this;
      $this.$Toast("支付有效时间已过！");
      setTimeout(() => {
        $this.$router.back();
      }, 1000);
    },
    onPayPassword(password) {
      this.bpayPassword = password;
      if (this.pageType == "integral") {
        this.onBpayIntegral();
      } else {
        if (this.payType == "bpay") {
          this.onBpay();
        } else if (this.payType == "ethpay" || this.payType == "eospay") {
          this.onBlockchainPay();
        } else if (this.payType == "ppay") {
          this.onPpay();
        }
      }
    },
    onSelectBankCard(info) {
      this.bankCardInfo = info;
    },
    onPay() {
      const $this = this;
      const payType = $this.payType;
      if (payType === "wechat") {
        $this.isLoading = true;
        if ($this.pageType == "integral") {
          $this.integral_data.order_data.pay_type = 1;
          $this.$store
            .dispatch("wxIntegralPay", $this.integral_data)
            .then(({ type, out_trade_no }) => {
              if (type == "wechat") {
                $this.$router.replace({
                  name: "pay-result",
                  query: {
                    out_trade_no: out_trade_no
                  }
                });
              } else {
                $this.isLoading = false;
              }
            });
        } else {
          $this.$store
            .dispatch("wxPay", $this.out_trade_no)
            .then(({ type }) => {
              if (type == "wechat") {
                $this.$router.replace({
                  name: "pay-result",
                  query: {
                    out_trade_no: $this.out_trade_no
                  }
                });
              } else {
                $this.isLoading = false;
              }
            })
            .catch(error => {
              $this.isLoading = false;
            });
        }
      } else if (payType === "alipay") {
        $this.isLoading = true;
        let baseUrl = document.location.href;
        let real = baseUrl.split("?")[1];
        if ($this.pageType == "integral") {
          $this.integral_data.order_data.pay_type = 2;
          PAY_INTEGRALPAY($this.integral_data)
            .then(({ data }) => {
              if ($this.$store.state.isWeixin) {
                let param = _encode(JSON.stringify(data));
                $this.$router.replace({
                  name: "pay-guide",
                  query: {
                    param: param,
                    real: real
                  }
                });
              } else {
                const div = document.createElement("div");
                div.innerHTML = data;
                document.body.appendChild(div);
                document.forms[0].submit();
              }
            })
            .catch(() => {
              $this.isLoading = false;
            });
        } else {
          PAY_ALIPAY($this.out_trade_no)
            .then(({ data }) => {
              if ($this.$store.state.isWeixin) {
                let param = _encode(JSON.stringify(data));
                $this.$router.replace({
                  name: "pay-guide",
                  query: {
                    param: param,
                    real: real
                  }
                });
              } else {
                const div = document.createElement("div");
                div.innerHTML = data;
                document.body.appendChild(div);
                document.forms[0].submit();
              }
            })
            .catch(() => {
              $this.isLoading = false;
            });
        }
      } else if (payType === "bpay") {
        if ($this.pageType == "integral") {
          $this.onBpayIntegral();
        } else {
          $this.onBpay();
        }
      } else if (payType === "ethpay" || payType === "eospay") {
        $this.onBlockchainPay();
      } else if (payType === "tlpay") {
        $this.onTlpay();
      } else if (payType === "ppay") {
        this.onPpay();
      } else if (payType === "glopay") {
        this.onGlopay();
      }
    },
    onBpay() {
      const $this = this;
      const payType = $this.payType;
      if ($this.balance < parseFloat($this.pay_money)) {
        $this.$Toast(
          this.$store.state.member.memberSetText.balance_style +
            "不足，请选择其他支付方式！"
        );
        return false;
      }
      if ($this.bpayPassword) {
        $this.isLoading = true;
      }
      $this
        .validPayPassword($this.bpayPassword)
        .then(() => {
          PAY_BALANCE({
            out_trade_no: $this.out_trade_no,
            pay_money: $this.pay_money
          })
            .then(res => {
              if (res.code == 0) {
                $this.$Toast.success("支付成功");
                $this.$router.replace({
                  name: "pay-result",
                  query: {
                    out_trade_no: $this.out_trade_no
                  }
                });
              } else {
                $this.$Toast.fail(res.message);
                $this.isLoading = false;
                $this.bpayPassword = null;
              }
            })
            .catch(() => {
              $this.isLoading = false;
              $this.bpayPassword = null;
            });
        })
        .catch(() => {
          $this.$Toast.clear();
          $this.isLoading = false;
          $this.bpayPassword = null;
        });
    },
    onBpayIntegral() {
      const $this = this;
      if ($this.bpayPassword) {
        $this.isLoading = true;
      }
      $this
        .validPayPassword($this.bpayPassword)
        .then(() => {
          $this.integral_data.order_data.pay_type = 5;
          $this.integral_data.password = $this.bpayPassword;
          PAY_INTEGRALPAY($this.integral_data)
            .then(res => {
              $this.out_trade_no = res.data.out_trade_no;
              if (res.code == 0) {
                $this.$Toast.success("支付成功");
                $this.$router.replace({
                  name: "pay-result",
                  query: {
                    out_trade_no: $this.out_trade_no
                  }
                });
              } else {
                $this.$Toast.fail(res.message);
                $this.isLoading = false;
                $this.bpayPassword = null;
              }
            })
            .catch(() => {
              $this.isLoading = false;
              $this.bpayPassword = null;
            });
        })
        .catch(() => {
          $this.$Toast.clear();
          $this.isLoading = false;
          $this.bpayPassword = null;
        });
    },
    // 货款支付
    onPpay() {
      const $this = this;
      const payType = $this.payType;
      if ($this.balance < parseFloat($this.pay_money)) {
        $this.$Toast(
          this.$store.state.member.memberSetText.balance_style +
            "不足，请选择其他支付方式！"
        );
        return false;
      }
      if ($this.bpayPassword) {
        $this.isLoading = true;
      }
      $this
        .validPayPassword($this.bpayPassword)
        .then(() => {
          PAY_PROCEEDS({
            out_trade_no: $this.out_trade_no,
            pay_money: $this.pay_money
          })
            .then(res => {
              if (res.code == 0) {
                $this.$Toast.success("支付成功");
                $this.$router.replace({
                  name: "pay-result",
                  query: {
                    out_trade_no: $this.out_trade_no
                  }
                });
              } else {
                $this.$Toast.fail(res.message);
                $this.isLoading = false;
                $this.bpayPassword = null;
              }
            })
            .catch(() => {
              $this.isLoading = false;
              $this.bpayPassword = null;
            });
        })
        .catch(() => {
          $this.$Toast.clear();
          $this.isLoading = false;
          $this.bpayPassword = null;
        });
    },
    // 数字资产钱包相关支付
    onBlockchainPay() {
      const $this = this;
      const payType = $this.payType;
      const type = payType == "ethpay" ? "eth" : "eos";
      const balance = parseFloat($this[payType].balance);
      const pay_money = parseFloat($this[payType].paymoney);
      if (balance < pay_money) {
        $this.$Toast(type + "余额不足，请选择其他支付方式！");
        return false;
      }
      if ($this.bpayPassword) {
        $this.isLoading = true;
      }
      $this
        .validPayPassword($this.bpayPassword, true)
        .then(() => {
          let params = {
            out_trade_no: $this.out_trade_no,
            password: $this.bpayPassword
          };
          PAY_BLOCKCHAIN(type, params)
            .then(res => {
              $this.$router.replace({
                name: "pay-result",
                query: {
                  out_trade_no: $this.out_trade_no,
                  blockchain_order: 1
                }
              });
            })
            .catch(() => {
              $this.isLoading = false;
              $this.bpayPassword = null;
            });
        })
        .catch(() => {
          $this.$Toast.clear();
          $this.isLoading = false;
          $this.bpayPassword = null;
        });
    },
    // 银行卡支付
    onTlpay() {
      const $this = this;
      const payType = $this.payType;
      if (!$this.bankCardInfo) {
        $this.$Toast("请选择银行卡！");
        return false;
      }
      $this.bankCardParams.out_trade_no = $this.out_trade_no;
      $this.bankCardParams.id = $this.bankCardInfo.id;
      $this.bankCardParams.mobile = $this.bankCardInfo.mobile;
      $this.isLoading = true;
      APPLY_BANKCARDSMS($this.bankCardParams)
        .then(({ code, data, message }) => {
          if (code == 1) {
            if (data.thpinfo) {
              $this.bankCardParams.thpinfo = data.thpinfo;
              $this.showBankCardSms = true;
            } else {
              $this.$Toast(message);
              $this.isLoading = true;
            }
          } else {
            $this.$Toast(message);
            $this.$router.replace("/order/list");
          }
        })
        .catch(() => {
          $this.isLoading = false;
        });
    },
    // 银行卡支付成功
    bankCardPaySuccess() {
      this.$router.replace({
        name: "pay-result",
        query: {
          out_trade_no: this.out_trade_no
        }
      });
    },
    // 银行卡支付关闭
    bankCardPayClose() {
      this.bankCardParams = {};
      this.isLoading = false;
    },
    // glopay 跨境支付
    onGlopay() {
      const $this = this;
      $this.isLoading = true;
      PAY_GLOPAY({
        out_trade_no: $this.out_trade_no,
        type:
          $this.$store.state.isWeixin && $this.$store.getters.config.is_wchat
            ? 1
            : 2
      })
        .then(({ data }) => {
          window.location.href = data.pay_url;
        })
        .catch(() => {
          $this.isLoading = false;
        });
    }
  },
  components: {
    CountDown,
    CellPayActionGroup,
    DialogPayPassword,
    PopupBankCardSms
  }
});
</script>

<style scoped>
.pay-payment {
  padding-bottom: 80px;
}

.payment-info {
  text-align: center;
  padding: 20px 0;
  font-size: 16px;
}

.payment-info > div {
  line-height: 1.6;
}

.payment-info .money-text {
  font-size: 1.3em;
  font-weight: 700;
  color: #ff454e;
  margin-top: 6px;
}

.payment-info .limit-time {
  color: #909399;
  font-size: 0.8em;
}

.time-end {
  display: inline;
}

.time-end i,
.time-start i {
  font-style: normal;
}
</style>
