<template>
  <div class="header">
    <div class="title">{{typeToUpperCase}}</div>
    <div class="balance-money">
      <span class="balance">{{info.balance}}</span>
      <span class="money">≈ {{info.money | yuan}}</span>
      <van-icon name="replay" class="btn-updata" :class="updataingClass" @click="onUpdata" />
    </div>
    <div class="foot">
      <router-link tag="div" class="text" :to="'/blockchain/wallet/'+type">
        <van-icon name="v-icon-qr3" class="icon" />
        <span class>钱包地址</span>
      </router-link>
      <div class="btn">
        <van-button round size="small" :to="'/blockchain/trade/log/'+type">交易明细</van-button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      updataing: false,
      typeToUpperCase: this.type.toUpperCase()
    };
  },
  props: {
    type: String
  },
  computed: {
    info() {
      return this.$store.state.blockchain[this.type] || {};
    },
    updataingClass() {
      return this.updataing ? "updataing" : "";
    }
  },
  methods: {
    onUpdata() {
      const methods = this.type == "eth" ? "getEthInfo" : "getEosInfo";
      this.updataing = true;
      this.$store
        .dispatch(methods, true)
        .then(() => {
          this.updataing = false;
        })
        .catch(() => {
          this.updataing = false;
        });
    }
  }
};
</script>

<style scoped>
.header {
  padding: 10px 15px;
  background: #ff454e;
  color: #fff;
  line-height: 1.2;
}

.header .title {
  font-size: 16px;
  font-weight: 800;
}

.header .balance-money {
  margin: 8px 0;
  display: flex;
  align-items: center;
}

.header .balance {
  font-weight: 800;
  font-size: 16px;
  padding-right: 4px;
}

.header .foot {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header .foot .text .icon {
  width: 16px;
  height: 16px;
}

.btn-updata {
  font-size: 16px;
  margin: 0 8px;
}

.btn-updata.updataing {
  animation: updataing 1s infinite linear;
}

@keyframes updataing {
  0% {
    -webkit-transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
  }
}
</style>

