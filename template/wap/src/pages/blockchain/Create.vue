<template>
  <Layout ref="load" class="blockchain-create bg-f8">
    <Navbar />
    <template v-if="!creating">
      <div class="head-tip">{{tip}}</div>
      <van-cell-group class="cell-group">
        <van-field
          label="账户名"
          clearable
          maxlength="12"
          placeholder="请输入a-z与数字1-5组合的账户名"
          autocomplete="off"
          v-model="params.account_name"
          @blur="onAccountNameBlur"
        />
        <van-field
          label="支付密码"
          clearable
          type="password"
          placeholder="请输入商城的支付密码"
          autocomplete="new-password"
          v-model="params.password"
          @blur="onPasswordBlur"
          v-if="isPayPassword"
        />
        <van-cell title="支付密码" class="cell-panel" v-else>
          <span class="a-link" @click="onSetPayPassword">未设置支付密码，点击设置</span>
        </van-cell>
      </van-cell-group>
      <van-cell-group class="cell-group" v-if="!isPaymented">
        <van-cell title="内存空间" class="cell-panel">
          <span class="text-maintone">{{money | yuan}}</span>
          <span class="fs-12 text-secondary" v-if="notEnoughFlag">{{notEnoughText}}</span>
        </van-cell>
      </van-cell-group>
      <CellPayActionGroup v-model="payType" @change="onPayTypeChange" v-if="!isPaymented" />
      <div class="foot-btn-group">
        <van-button
          size="normal"
          round
          type="danger"
          block
          :disabled="isDisabled"
          :loading="isLoading"
          @click="onCreate"
        >创建钱包</van-button>
      </div>
      <DialogPayPassword
        v-if="!isPayPassword"
        ref="DialogPayPassword"
        :load-data="initBlockchainSetData"
      />
    </template>
    <template v-else>
      <Empty :show-foot="false" :message="creatText">
        <div slot="content">
          <van-icon name="v-icon-wait-creat" size="120px" color="#606266" />
        </div>
      </Empty>
    </template>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import CellPayActionGroup from "@/components/CellPayActionGroup";
import Empty from "@/components/Empty";
import DialogPayPassword from "@/components/DialogPayPassword";
import { PAY_ALIPAY, GET_PAYRESULT } from "@/api/pay";
import {
  CHECK_BLOCKCHAINEOSACCOUNTNAME,
  CREATE_BLOCKCHAINEOSWALLET,
  PAY_BLOCKCHAINEOSBALANCEPAY,
  CREATE_BLOCKCHAINEOSWALLETUNPAY
} from "@/api/blockchain";
import { _decode, _encode } from "@/utils/base64";
export default sfc({
  name: "blockchain-create",
  data() {
    return {
      creating: false,
      creatText: "钱包创建中，请稍等...",

      isPayPassword: false, //是否有设置支付密码
      isPaymented: false, //是否购买过内存

      money: "",
      tip:
        "EOS钱包向商城购买小部分内存空间才能完成创建，若支付成功创建钱包失败，重新创建则无需要重新购物内存。",
      payType: "",
      params: {
        password: "",
        account_name: ""
      },
      accountNameFlag: false,
      passwordFlag: false,
      isLoading: false
    };
  },
  computed: {
    isDisabled() {
      let flag = true;
      if (!this.isPayPassword) return flag;
      if (!this.isPaymented) {
        if (this.accountNameFlag && this.passwordFlag && this.payType) {
          flag = false;
          if (
            this.payType == "bpay" &&
            parseInt(this.$store.state.member.info.balance) < this.money
          ) {
            flag = true;
          }
        }
      } else {
        if (this.accountNameFlag && this.passwordFlag) {
          flag = false;
        }
      }
      return flag;
    },
    notEnoughFlag() {
      let flag = false;
      if (
        this.payType == "bpay" &&
        parseInt(this.$store.state.member.info.balance) < this.money
      ) {
        flag = true;
      }
      return flag;
    },
    notEnoughText() {
      return (
        "(" + this.$store.state.member.memberSetText.balance_style + "不足)"
      );
    }
  },
  mounted() {
    if (this.$store.state.config.addons.blockchain) {
      this.loadData();
    } else {
      this.$refs.load.fail({
        errorText: "未开启区块链应用",
        showFoot: false
      });
    }
  },
  methods: {
    // 支付密码设置完成重新获取相关设置
    initBlockchainSetData() {
      this.$store.dispatch("getMemberInfo").then(() => {
        this.$store.dispatch("getBlockchainSet", true);
      });
    },
    loadData() {
      this.$store
        .dispatch("getBlockchainSet")
        .then(({ wallet_type, eos_money, password_is_set }) => {
          this.money = eos_money;
          this.isPayPassword = this.$store.state.member.info.is_password_set;
          const arr = wallet_type.split(",");
          if (arr[0] == 2 || arr[1] == 2) {
            if (this.$route.query.out_trade_no) {
              // h5或支付宝支付完成回调
              this.getPayResult(this.$route.query.out_trade_no).then(
                ({ data }) => {
                  if (data.pay_status != 2) {
                    this.$Toast("支付失败，请重新支付创建钱包！");
                  }
                  this.getEosInfo();
                }
              );
            } else {
              this.getEosInfo();
            }
          } else {
            this.$router.replace("/blockchain");
          }
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    },
    // 获取eos信息
    getEosInfo() {
      this.$store
        .dispatch("getEosInfo", true)
        .then(({ code, data, message }) => {
          if (code == 2) {
            this.params.pay_type = "";
            this.params.pay_from = this.$store.state.isWeixin ? 1 : 2;
            this.$refs.load.success();
          } else if (code == 3) {
            this.creating = true;
            this.creatText = message;
            this.$refs.load.success();
          } else if (code == 4) {
            this.isPaymented = true;
            this.creating = false;
            this.$refs.load.success();
          } else {
            this.$router.replace("/blockchain");
          }
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    },
    tranPayType(e) {
      let type = "";
      if (e == "bpay") {
        type = 1;
      } else if (e == "wechat") {
        type = 2;
      } else if (e == "alipay") {
        type = 3;
      }
      return type;
    },
    // 设置支付密码
    onSetPayPassword() {
      this.$refs.DialogPayPassword.isShowPopupPayPassword = true;
    },
    // 创建钱包
    onCreate() {
      let params = this.validatore();
      if (params) {
        this.isLoading = true;
        if (this.isPaymented) {
          this.createNotPay(params);
        } else {
          this.createPay(params);
        }
      }
    },
    // 需要支付创建
    createPay(params) {
      CREATE_BLOCKCHAINEOSWALLET(params)
        .then(({ data }) => {
          if (this.payType == "bpay") {
            this.balancePay(data.out_trade_no);
            this.isLoading = false;
            this.creating = true;
          } else if (this.payType == "wechat") {
            this.wechatPay(data.out_trade_no);
          } else if (this.payType == "alipay") {
            this.aliPay(data.out_trade_no);
          }
        })
        .catch(() => {
          this.isLoading = false;
        });
    },
    // 无需支付直接创建
    createNotPay(params) {
      this.creating = true;
      CREATE_BLOCKCHAINEOSWALLETUNPAY(params)
        .then(({ code, message }) => {
          this.isLoading = false;
          this.$Toast(message);
          this.$router.replace("/blockchain");
        })
        .catch(() => {
          this.$router.replace("/blockchain");
          this.isLoading = false;
        });
    },
    // 余额支付
    balancePay(out_trade_no) {
      PAY_BLOCKCHAINEOSBALANCEPAY(out_trade_no)
        .then(({ code, message }) => {
          this.$Toast(message);
          if (code == 1) {
            this.$store.dispatch("getEosInfo", true);
            this.$router.replace("/blockchain");
          } else if (code == 4) {
            this.$store.dispatch("getEosInfo", true);
            this.$router.replace("/blockchain/centre/eos");
          } else {
            this.loadData();
          }
        })
        .catch(() => {
          this.creating = false;
        });
    },
    // 微信支付
    wechatPay(out_trade_no) {
      this.$store
        .dispatch("wxPay", out_trade_no)
        .then(({ type }) => {
          if (type == "wechat") {
            this.isLoading = false;
            this.creating = true;
            this.loadData();
          } else {
            this.isLoading = false;
          }
        })
        .catch(error => {
          this.isLoading = false;
        });
    },
    // 支付宝支付
    aliPay(out_trade_no) {
      PAY_ALIPAY(out_trade_no)
        .then(({ data }) => {
          if (this.$store.state.isWeixin) {
            let param = _encode(JSON.stringify(data));
            $this.$router.replace({
              name: "pay-guide",
              query: {
                param: param
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
          this.isLoading = false;
        });
    },
    // 获取支付结果
    getPayResult(out_trade_no) {
      return new Promise((resolve, reject) => {
        GET_PAYRESULT(out_trade_no)
          .then(({ data }) => {
            resolve(data);
          })
          .catch(() => {
            reject();
            this.$refs.load.fail();
          });
      });
    },
    onAccountNameBlur(e) {
      this.checkAccountName(e.target.value);
    },
    onPasswordBlur(e) {
      this.checkPaypassword(e.target.value);
    },
    onPayTypeChange(e) {
      this.params.pay_type = this.tranPayType(e);
    },
    // 验证账号名称
    checkAccountName(name) {
      if (name && this.validUsername(name)) {
        CHECK_BLOCKCHAINEOSACCOUNTNAME(name)
          .then(({ code, message }) => {
            if (code == 2) {
              this.accountNameFlag = true;
            } else {
              this.passwordFlag = false;
              this.$Toast(message);
            }
          })
          .catch(() => {
            this.passwordFlag = false;
          });
      } else {
        this.passwordFlag = false;
      }
    },
    //验证支付密码
    checkPaypassword(password) {
      if (password) {
        this.$store
          .dispatch("checkPayPassword", password)
          .then(() => {
            this.passwordFlag = true;
          })
          .catch(() => {
            this.passwordFlag = false;
          });
      } else {
        this.passwordFlag = false;
      }
    },
    //验证
    validatore() {
      const params = this.params;
      if (!params.account_name) {
        this.$Toast("请输入账户名！");
        return false;
      }
      if (!this.validUsername(params.account_name)) {
        return false;
      }
      if (!this.isPaymented) {
        if (!params.password) {
          this.$Toast("请输入商城的支付密码！");
          return false;
        }
        if (!params.pay_type) {
          this.$Toast("请选择支付方式！");
          return false;
        }
      }
      return params;
    },
    validUsername(value) {
      var reg = /^([1-5]{12}$)|(^[a-z]{12}$)|(^[1-5a-z]{12})$/;
      if (!reg.test(value)) {
        this.$Toast("由a-z与数字1-5组合，长度为12位");
        return false;
      } else {
        return true;
      }
    }
  },
  components: {
    CellPayActionGroup,
    Empty,
    DialogPayPassword
  }
});
</script>

<style scoped>
.head-tip {
  padding: 10px;
  color: #f56723;
  font-size: 12px;
  line-height: 1.5;
  background-color: #fff7cc;
}

.cell-group {
  margin: 10px 0;
}
</style>
