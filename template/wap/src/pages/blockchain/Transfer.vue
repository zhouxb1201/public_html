<template>
  <Layout ref="load" class="blockchain-transfer bg-f8">
    <Navbar :title="navbarTitle" />
    <TransferGroup
      :type="$route.params.type"
      :info="info"
      :params="params"
      :poundage="poundage"
      @address-blur="onAddressBlur"
      @sd-change="onSdChange"
      @scanqr="onScanQR"
    />
    <div class="foot-btn-group">
      <van-button
        size="normal"
        round
        type="danger"
        block
        :disabled="isDisabled"
        :loading="isLoading"
        @click="onTransfer"
      >{{transferBtnText}}</van-button>
    </div>
    <DialogPayPassword
      ref="DialogPayPassword"
      :type="feeType"
      :money="payMoney"
      :address="params[info.tokey]"
      @confirm="onPayPassword"
      @cancel="isLoading=false"
      :load-data="initBlockchainSetData"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import TransferGroup from "./component/TransferGroup";
import DialogPayPassword from "@/components/DialogPayPassword";
import blockchain from "./mixin";
import { payPassword } from "@/mixins";
import {
  GET_BLOCKCHAINETHGAS,
  CHECK_BLOCKCHAINETHADDRESS,
  CHECK_BLOCKCHAINEOSACCOUNTNAME,
  TRANSFER_BLOCKCHAIN
} from "@/api/blockchain";
import { yuan, bi } from "@/utils/filter";
export default sfc({
  name: "blockchain-transfer",
  data() {
    return {
      info: {},
      params: {
        password: ""
      },
      poundage: {},
      isLoading: false,
      addressFlag: false //收款地址是否正确标识
    };
  },
  mixins: [blockchain, payPassword],
  computed: {
    navbarTitle() {
      let type = this.$route.params.type
        ? this.$route.params.type.toUpperCase()
        : "";
      let title = type + "转账";
      if (title) document.title = title;
      return title;
    },
    isDisabled() {
      const type = this.$route.params.type;
      if (!this.addressFlag) {
        return true;
      }
      if (parseFloat(this.info.balance) <= 0) {
        return true;
      }
      return false;
    },
    transferBtnText() {
      const type = this.$route.params.type;
      let text = "";
      if (parseFloat(this.info.balance) <= 0) {
        text = "可用" + type.toUpperCase() + "不足，无法转账！";
      } else {
        text = "转账";
      }
      return text;
    },
    feeType() {
      const pageType = this.$route.params.type;
      let type = null;
      type = pageType == "eth" ? 9 : 13;
      return type;
    },
    payMoney() {
      const pageType = this.$route.params.type;
      let money = null;
      money = this.params[pageType] && parseFloat(this.params[pageType]);
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
      this.info = data;
      if (type == "eth") {
        this.params.gas = 1;
        this.params.fromAddress = data.address;
        this.info.tokey = "toAddress";
        this.getGas();
      } else {
        this.params.fromAccountName = data.accountName;
        this.info.tokey = "toAccountName";
      }
    },
    onSdChange(e) {
      this.params.gas = e;
      this.getGas();
    },
    onAddressBlur(address) {
      if (address) {
        this.checkAddress(address)
          .then(() => {
            this.addressFlag = true;
            this.getGas();
          })
          .catch(() => {
            this.addressFlag = false;
            this.initPoundage();
          });
      } else {
        this.addressFlag = false;
        this.initPoundage();
      }
    },
    // 获取gas费用
    getGas() {
      const type = this.$route.params.type;
      if (type == "eth") {
        const address = this.params[this.info.tokey];
        this.initPoundage(address);
        if (address) {
          GET_BLOCKCHAINETHGAS({
            gas: this.params.gas,
            type: 3,
            toAddress: address
          }).then(({ data }) => {
            this.poundage = data;
            this.poundage.loadingFlag = true;
          });
        }
      }
    },
    // 初始化手续费
    initPoundage(address) {
      this.poundage = {};
      this.poundage.loadingInitText = address
        ? ""
        : "输入收款地址后方可获取手续费";
      this.poundage.loadingFlag = false;
    },
    // 验证收款地址
    checkAddress(address) {
      const type = this.$route.params.type;
      return new Promise((resolve, reject) => {
        if (type == "eth") {
          CHECK_BLOCKCHAINETHADDRESS(address).then(({ code, message }) => {
            if (code == 2) {
              resolve();
            } else {
              this.$Toast(message);
              reject();
            }
          });
        } else {
          CHECK_BLOCKCHAINEOSACCOUNTNAME(address).then(({ code, message }) => {
            if (code == 1) {
              resolve();
            } else {
              this.$Toast(message);
              reject();
            }
          });
        }
      });
    },
    onScanQR(res) {
      this.params[this.info.tokey] = res;
      this.params = Object.assign({}, this.params);
      this.onAddressBlur(res);
    },
    // 验证密码
    onPayPassword(password) {
      this.params.password = password;
      this.onTransfer();
    },
    onTransfer() {
      const type = this.$route.params.type;
      const params = this.validator(type);
      if (params) {
        this.isLoading = true;
        this.validPayPassword(params.password, true)
          .then(() => {
            TRANSFER_BLOCKCHAIN(type, params)
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
      if (!this.addressFlag) return;
      const params = this.params;
      const balance = parseFloat(this.info.balance);
      if (balance <= 0) {
        this.$Toast("可用" + type.toUpperCase() + "不足！");
        return false;
      }
      if (!params[this.info.tokey]) {
        this.$Toast("请输入收款人钱包地址！");
        return false;
      }
      if (!params[type]) {
        this.$Toast("请输入转账" + type.toUpperCase() + "！");
        return false;
      }
      if (parseFloat(params[type]) <= 0) {
        this.$Toast("转账" + type.toUpperCase() + "不可小于0！");
        return false;
      }
      if (params[type] > balance) {
        this.$Toast(
          "转账" +
            type.toUpperCase() +
            "不可大于可用" +
            type.toUpperCase() +
            "！"
        );
        return false;
      }
      params[type] = bi(params[type], type == "eth" ? 6 : 4);
      return params;
    }
  },
  components: {
    TransferGroup,
    DialogPayPassword
  }
});
</script>

<style scoped>
</style>
