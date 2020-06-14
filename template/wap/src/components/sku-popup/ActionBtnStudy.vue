<template>
  <ActionBtnConfirm v-if="action" :action="action" :btn-type="btnType" @click="click" />
  <div class="sku-action-group" v-else>
    <van-button class="action-btn" bottom-action type="primary" @click="study">{{btnText}}</van-button>
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
    buyBtnText: {
      type: String,
      default: "前往学习"
    }
  },
  computed: {
    btnText() {
      return this.buyBtnText;
    },
    btnType() {
      return "primary";
    }
  },
  methods: {
    click(action) {
      this[action]();
    },
    study() {
      this.$emit("click", "study", { goods_id: this.goodsInfo.id });
    }
  },
  components: {
    ActionBtnConfirm
  }
};
</script>

<style scoped>
</style>