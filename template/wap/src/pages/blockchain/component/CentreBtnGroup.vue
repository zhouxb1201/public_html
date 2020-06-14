<template>
  <div class="btn-group">
    <van-button
      class="btn"
      type="danger"
      size="normal"
      :to="'/blockchain/exchange/'+type"
    >{{typeToUpperCase}}兑换</van-button>
    <van-button
      class="btn"
      type="danger"
      size="normal"
      :to="'/blockchain/transfer/'+type"
    >{{typeToUpperCase}}转账</van-button>
    <van-button
      class="btn"
      type="danger"
      size="normal"
      plain
      hairline
      @click="keyTypeApi('keystore')"
    >导出KeyStore</van-button>
    <van-button
      class="btn"
      type="danger"
      size="normal"
      plain
      hairline
      @click="keyTypeApi('privatekey')"
    >导出私钥</van-button>
    <DialogPayPassword
      ref="DialogPayPassword"
      @confirm="onPayPassword"
      :load-data="initBlockchainSetData"
    />
  </div>
</template>

<script>
import DialogPayPassword from "@/components/DialogPayPassword";
import { payPassword } from "@/mixins";
import { EXPORT_BLOCKCHAINKEY } from "@/api/blockchain";
export default {
  data() {
    return {
      typeToUpperCase: this.type.toUpperCase(),
      exportType: "",
      params: {
        password: ""
      }
    };
  },
  props: {
    type: String
  },
  mixins: [payPassword],
  computed: {
    info() {
      return this.$store.state.blockchain[this.type] || {};
    }
  },
  created() {
    if (this.type == "eth") {
      this.params.address = this.info.address;
    } else {
      this.params.account_name = this.info.accountName;
    }
  },
  methods: {
    // 支付密码设置完成重新获取相关设置
    initBlockchainSetData() {
      this.$store.dispatch("getBlockchainSet", true);
    },
    keyTypeApi(keyType) {
      this.exportType = keyType;
      this.onExport();
    },
    onPayPassword(e) {
      this.params.password = e;
      this.onExport();
    },
    onExport() {
      this.validPayPassword(this.params.password, true)
        .then(() => {
          const type = this.type.toLowerCase();
          const key = this.exportType;
          EXPORT_BLOCKCHAINKEY({ type, key }, this.params).then(({ data }) => {
            this.$store.commit("setBlockchainExportKey", {
              type,
              key,
              value: key == "keystore" ? data.keyStore : data.privateKey
            });
            this.$router.push({
              name: "blockchain-export",
              params: { type, key }
            });
          });
        })
        .catch(() => {
          this.params.password = "";
        });
    }
  },
  components: {
    DialogPayPassword
  }
};
</script>

<style scoped>
.btn-group {
  display: flex;
  flex-flow: wrap;
  margin: 2%;
}

.btn-group .btn {
  width: 46%;
  margin: 2%;
}
</style>
