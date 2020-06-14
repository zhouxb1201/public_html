<template>
  <van-cell>
    <van-checkbox v-model="checked" class="text-secondary fs-12 apply-checkbox">我已阅读并同意以下协议</van-checkbox>
    <van-button
      size="normal"
      block
      round
      type="danger"
      class="apply-btn"
      :disabled="!checked"
      @click="submit"
    >{{btnText}}</van-button>
  </van-cell>
</template>

<script>
export default {
  data() {
    return {
      checked: false
    };
  },
  props: {
    btnText: {
      type: String,
      default: "提交申请"
    }
  },
  methods: {
    submit() {
      this.$emit("submit", {});
    }
  }
};
</script>

<style scoped>
.apply-btn {
  margin-bottom: 10px;
}
.apply-checkbox {
  padding-bottom: 10px;
}
</style>

