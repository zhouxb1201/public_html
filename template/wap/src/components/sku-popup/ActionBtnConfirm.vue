<template>
  <div class="sku-action-group">
    <van-button class="action-btn" bottom-action :type="btnType" @click="click">{{btnText}}</van-button>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    action: String,
    goodsInfo: Object,
    btnText: {
      type: String,
      default: "确定"
    },
    btnType: {
      type: String,
      default: "primary"
    }
  },
  methods: {
    click() {
      this.$emit("click", this.action);
    }
  }
};
</script>

<style scoped>
</style>