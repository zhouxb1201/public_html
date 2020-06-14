<template>
  <div>
    <van-cell center title="ETH" class="cell-panel" :is-link="isHasWallet" @click="onCreate">
      <van-icon slot="icon" name="v-icon-eth" color="#f52929" class="left-icon" />
      <div class="transfer-price text-right" v-if="isHasWallet">
        <span class="before-price">{{info.balance}}</span>
        <span class="after-price">≈ {{info.money | yuan}}</span>
      </div>
      <div class="fs-12 text-right" v-else>{{createText}}</div>
    </van-cell>
    <DialogPayPassword
      ref="DialogPayPassword"
      dialog-title="请输入支付密码，创建ETH钱包"
      @confirm="onPayPassword"
      :load-data="initBlockchainSetData"
    />
  </div>
</template>

<script>
import { CREAT_BLOCKCHAINETHACCOUNT } from "@/api/blockchain";
import DialogPayPassword from "@/components/DialogPayPassword";
import { payPassword } from "@/mixins";
export default {
  data() {
    return {
      success: false,
      isHasWallet: false,
      info: {},
      createFlag: 0,
      createText: "钱包信息加载中...",
      password: ""
    };
  },
  mixins: [payPassword],
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      this.$store
        .dispatch("getEthInfo")
        .then(({ code, data }) => {
          this.success = true;
          this.createFlag = code;
          if (code == 1) {
            this.info = data;
            this.isHasWallet = true;
          }
          if (code == 2) this.createText = "创建钱包";
        })
        .catch(() => {
          this.createText = "钱包信息加载失败";
        });
    },
    // 支付密码设置完成重新获取相关设置
    initBlockchainSetData() {
      this.$store.dispatch("getblockchainSet");
    },
    onPayPassword(e) {
      this.password = e;
      this.onCreate();
    },
    onCreate() {
      if (!this.success) {
        return false;
      }
      if (this.isHasWallet) {
        return this.$router.push("/blockchain/centre/eth");
      }
      this.validPayPassword(this.password, true)
        .then(() => {
          CREAT_BLOCKCHAINETHACCOUNT(this.password)
            .then(({ message }) => {
              this.$Toast.success(message);
              this.$router.push("/blockchain/centre/eth");
            })
            .catch(() => {
              this.$refs.DialogPayPassword.onClearPassword();
            });
        })
        .catch(() => {
          this.password = "";
        });
    }
  },
  components: {
    DialogPayPassword
  }
};
</script>

<style scoped>
.cell-panel{
  padding: 20px 15px;
}

.left-icon {
  width: 24px;
  height: 24px;
  font-size: 20px;
  text-align: center;
  line-height: 24px;
  margin-right: 5px;
}
.transfer-price {
  display: flex;
  flex-direction: column;
  line-height: 1.6;
}

.before-price {
  color: #323233;
}

.after-price {
  color: #909399;
  font-size: 12px;
}
</style>
