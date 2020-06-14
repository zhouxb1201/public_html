<template>
  <van-cell center title="EOS" class="cell-panel" :is-link="isHasWallet" @click="onClick">
    <van-icon slot="icon" name="v-icon-eos" color="#ff8f00" class="left-icon" />
    <div class="transfer-price text-right" v-if="isHasWallet">
      <span class="before-price">{{info.balance}}</span>
      <span class="after-price">≈ {{info.money | yuan}}</span>
    </div>
    <div
      class="fs-12 text-right"
      :class="createFlag == 3 || createFlag == 4 ? 'text-maintone':''"
      v-else
    >{{createText}}</div>
  </van-cell>
</template>

<script>
import { isIos } from "@/utils/util";
export default {
  data() {
    return {
      success: false,
      info: {},
      isHasWallet: false,
      createFlag: 0,
      createText: "钱包信息加载中..."
    };
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      this.$store
        .dispatch("getEosInfo")
        .then(({ code, data, message }) => {
          this.success = true;
          this.createFlag = code;
          if (code == 1) {
            this.info = data;
            this.isHasWallet = true;
          }
          this.createText = code == 2 ? "创建钱包" : message;
        })
        .catch(() => {
          this.createText = "钱包信息加载失败";
        });
    },
    onClick() {
      if (!this.success) {
        return false;
      }
      if (this.createFlag == 2 || this.createFlag == 4) {
        if (isIos()) {
          location.assign(`${this.$store.state.domain}/wap/pay/create`);
        } else {
          this.$router.push("/pay/create");
        }
        return false;
      }
      if (this.isHasWallet) {
        return this.$router.push("/blockchain/centre/eos");
      }
    }
  }
};
</script>

<style scoped>
.cell-panel {
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
