<template>
  <van-popup
    v-model="value"
    position="top"
    :style="{color,background}"
    :overlay="false"
    :lock-scroll="false"
    class="notify"
    :class="className"
  >
    <div @click="click">{{message}}</div>
  </van-popup>
</template>

<script>
import { Popup } from "vant";
export default {
  data() {
    return {};
  },
  props: {
    value: {
      type: Boolean,
      default: true
    },
    message: String,
    duration: {
      type: Number,
      default: 3000
    },
    color: {
      type: String,
      default: "#fff"
    },
    background: {
      type: String,
      default: "#1989fa"
    },
    className: String,
    onClick: [String, Function]
  },
  methods: {
    click() {
      this.onClick();
      this.$Notify.clear();
    }
  },
  components: {
    [Popup.name]: Popup
  }
};
</script>

<style scoped>
.notify {
  text-align: center;
  box-sizing: border-box;
  padding: 6px 15px;
  font-size: 14px;
  line-height: 20px;
  white-space: pre-wrap;
}
</style>