<template>
  <van-popup v-model="value" class="popup" :close-on-click-overlay="false" @click-overlay="onClose">
    <div class="box">
      <div class="img">
        <img :src="qrcode">
      </div>
      <div class="code">{{code}}</div>
      <div class="text">提货码为提货凭证，请妥善保管</div>
    </div>
  </van-popup>
</template>

<script>
export default {
  props: {
    value: {
      type: Boolean,
      default: false
    },
    code: String,
    qrcode: String
  },
  methods: {
    onClose() {
      this.$emit("input", false);
    }
  }
};
</script>

<style scoped>
.popup {
  background: none;
  width: 60%;
  padding: 10px 0;
}

.box {
  display: flex;
  flex-direction: column;
}

.box .img {
  height: 0;
  width: 100%;
  padding: 50% 0;
  overflow: hidden;
  background: #ffffff;
}

.box img {
  display: block;
  width: 100%;
  margin-top: -50%;
  background-color: #fff;
  border: none;
}

.box .code {
  background: #fff;
  border-radius: 4px;
  font-weight: 800;
  color: #333;
  text-align: center;
  padding: 4px 10px;
  margin: 10px 0;
}

.box .text {
  font-size: 12px;
  color: #fff;
  text-align: center;
}
</style>
