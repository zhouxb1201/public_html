<template>
  <ActionBtnConfirm v-if="action" :action="action" :btn-type="btnType" @click="click" />
  <div class="sku-action-group" v-else>
    <van-button class="action-btn" bottom-action @click="buy">
      <div class="btn-flex-column">
        <div>{{goodsInfo.goodsPrice | yuan}}</div>
        <div>{{cartBtnText}}</div>
      </div>
    </van-button>
    <van-button class="action-btn" bottom-action type="primary" @click="group">
      <div class="btn-flex-column">
        <div>{{goodsInfo.groupGoodsPrice | yuan}}</div>
        <div>{{btnText}}</div>
      </div>
    </van-button>
  </div>
</template>

<script>
import ActionBtnConfirm from "./ActionBtnConfirm";
export default {
  data() {
    return {};
  },
  props: {
    // 活动相关参数
    params: Object,
    // 商品基本信息
    goodsInfo: Object,
    action: String,
    cartBtnText: {
      type: String,
      default: "单独购买"
    },
    buyBtnText: {
      type: String,
      default: "发起拼团"
    }
  },
  computed: {
    btnText() {
      return this.params.record_id ? "参与拼团" : this.buyBtnText;
    },
    btnType() {
      return this.action == "buy" ? "default" : "primary";
    }
  },
  methods: {
    click(action) {
      this[action]();
    },
    buy() {
      this.$emit("click", "buy");
    },
    group() {
      this.$emit("click", "group", {
        group_id: this.params.group_id,
        record_id: this.params.record_id
      });
    }
  },
  components: {
    ActionBtnConfirm
  }
};
</script>

<style scoped>
.btn-flex-column {
  display: flex;
  flex-flow: column;
  line-height: 1.2;
  font-size: 12px;
}

.btn-flex-column > div {
  width: 90%;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  margin: 0 auto;
}
</style>