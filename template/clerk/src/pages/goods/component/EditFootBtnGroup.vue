<template>
  <div>
    <div class="btn-group">
      <van-button
        type="default"
        size="normal"
        hairline
        square
        block
        @click="showPopup=true"
        v-if="list.length>1"
      >批量设定</van-button>
      <van-button
        type="danger"
        size="normal"
        hairline
        square
        block
        :loading="loading"
        @click="comfirm"
      >保存</van-button>
    </div>
    <van-popup v-model="showPopup" position="bottom" class="popup">
      <div>
        <div class="van-hairline--top-bottom van-actionsheet__header">
          <div>批量设定</div>
          <van-icon name="close" @click="closePopup" />
        </div>
        <van-cell-group class="cell-group">
          <van-field v-model="item.price" label="销售价" type="number" placeholder="请输入销售价" />
          <van-field v-model="item.market_price" label="市场价" type="number" placeholder="请输入市场价" />
          <van-field v-model="item.stock" label="库存" type="number" placeholder="请输入库存" />
          <van-field
            v-model="item.bar_code"
            label="条形码"
            placeholder="选填，用于O2O扫码"
            :right-icon="$store.state.isWeixin?'v-icon-qr1':''"
            @click-right-icon="scanQR"
          />
        </van-cell-group>
        <div class="btn-group">
          <van-button type="danger" size="normal" hairline square block @click="popupComfirm">确定</van-button>
          <van-button type="default" size="normal" hairline square block @click="closePopup">取消</van-button>
        </div>
      </div>
    </van-popup>
  </div>
</template>

<script>
export default {
  data() {
    return {
      showPopup: false,
      item: {
        price: "",
        market_price: "",
        stock: "",
        bar_code: ""
      }
    };
  },
  props: {
    list: Array,
    loading: Boolean
  },
  methods: {
    comfirm() {
      let list = this.validator(this.list);
      if (list) {
        this.$emit("comfirm", list);
      }
    },
    validator(list) {
      let flag = true;
      let newList = [];
      list.forEach(e => {
        newList.push({
          sku_id: e.sku_id,
          price: e.price,
          market_price: e.market_price,
          stock: e.stock,
          bar_code: e.bar_code
        });
        if (isNaN(parseFloat(e.price))) {
          this.$Toast("请输入销售价");
          flag = false;
        }
        if (parseFloat(e.price) < 0) {
          this.$Toast("销售价不能小于0！");
          flag = false;
        }
        if (isNaN(parseFloat(e.market_price))) {
          this.$Toast("请输入市场价");
          flag = false;
        }
        if (parseFloat(e.market_price) < 0) {
          this.$Toast("市场价不能小于0！");
          flag = false;
        }
        if (isNaN(parseFloat(e.stock))) {
          this.$Toast("请输入库存");
          flag = false;
        }
        if (parseFloat(e.stock) < 0) {
          this.$Toast("库存不能小于0！");
          flag = false;
        }
      });
      return flag ? newList : false;
    },
    closePopup() {
      this.showPopup = false;
    },
    popupComfirm() {
      for (const key in this.item) {
        if (this.item[key]) {
          this.list.forEach(e => {
            e[key] = this.item[key];
          });
        }
      }
      this.closePopup();
    },
    scanQR() {
      this.$store.dispatch("scanQRCode").then(res => {
        this.item.bar_code = res;
      });
    }
  }
};
</script>

<style scoped>
.btn-group {
  display: flex;
  position: fixed;
  z-index: 99;
  width: 100%;
  height: 44px;
  bottom: 0;
  left: 0;
}

.cell-group {
  margin-bottom: 50px;
}
</style>