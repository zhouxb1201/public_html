<template>
  <van-popup
    v-model="value"
    position="bottom"
    :close-on-click-overlay="false"
    @click-overlay="onClose"
  >
    <div class="popup-box">
      <div class="van-hairline--top-bottom van-actionsheet__header">
        <div>{{title}}</div>
        <van-icon name="close" @click="onClose" />
      </div>
      <FormValidCode v-if="isValid" @next="isValid = false" :cancel="onClose" />
      <FormPaymentPassword v-else @success="refresh" @fail="isValid = true" :cancel="onClose" />
    </div>
  </van-popup>
</template>

<script>
import FormValidCode from "./FormValidCode";
import FormPaymentPassword from "./FormPaymentPassword";
export default {
  data() {
    return {
      title: "设置支付密码",
      isValid: true
    };
  },
  props: {
    value: {
      type: Boolean,
      default: false
    },
    loadData: Function
  },
  methods: {
    onClose() {
      this.$emit("input", false);
    },
    refresh() {
      this.$emit("input", false);
      this.isValid = true;
      this.$store.dispatch("getMemberInfo").then(() => {
        this.loadData();
      });
    }
  },
  components: {
    FormValidCode,
    FormPaymentPassword
  }
};
</script>

<style scoped>
.popup-box {
  position: relative;
  height: 80vh;
}
</style>