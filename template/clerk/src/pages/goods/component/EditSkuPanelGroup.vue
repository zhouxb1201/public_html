<template>
  <div class="panel-group">
    <van-cell title="价格库存" title-class="title" />
    <van-cell-group class="cell-group" :border="false" v-for="(item,index) in list" :key="index">
      <van-cell :title="item.sku_name" v-if="item.sku_name" />
      <van-field v-model="item.price" required label="销售价" type="number" placeholder="请输入销售价" />
      <van-field
        v-model="item.market_price"
        required
        label="市场价"
        type="number"
        placeholder="请输入市场价"
      />
      <van-field v-model="item.stock" required label="库存" type="number" placeholder="请输入库存" />
      <van-field
        v-model="item.bar_code"
        label="条形码"
        placeholder="选填，用于O2O扫码"
        :right-icon="$store.state.isWeixin?'v-icon-qr1':''"
        @click-right-icon="scanQR(index)"
      />
    </van-cell-group>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    list: Array
  },
  methods: {
    scanQR(index) {
      this.$store.dispatch("scanQRCode").then(res => {
        this.list[index].bar_code = res;
      });
    }
  },
  components: {}
};
</script>

<style scoped>
.panel-group {
  margin-top: 10px;
  margin-bottom: 50px;
}

.cell-group {
  margin-bottom: 10px;
}

.flex {
  display: flex;
}

.title {
  font-weight: 800;
}

.value {
  height: 48px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
}
</style>