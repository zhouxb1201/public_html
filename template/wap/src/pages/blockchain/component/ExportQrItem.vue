<template>
  <div>
    <div class="box van-hairline--surround">
      <div class="img" v-show="show" @click="show = !show">
        <Qrcode :options="{ size }" :value="content" tag="img" />
      </div>
      <div class="mask" v-show="!show">
        <van-icon class="icon" name="v-icon-invisible" />
        <van-button class="btn" round size="small" type="danger" @click="show = !show">显示二维码</van-button>
      </div>
    </div>
  </div>
</template>

<script>
import Qrcode from "@/components/Qrcode";
export default {
  data() {
    return {
      size: document.body.offsetWidth,
      show: false
    };
  },
  props: {
    content: String
  },
  components: {
    Qrcode
  }
};
</script>

<style scoped>
.box {
  height: 0;
  width: 80%;
  padding: 40% 0;
  overflow: hidden;
  background: #ffffff;
  margin: 20px auto;
  border-radius: 10px;
  position: relative;
}

.box .img {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  padding: 20px;
}

.box .img img {
  display: block;
  width: 100%;
  height: 100%;
  background-color: #fefefe;
  border: none;
}

.box .mask {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  right: 0;
  padding: 20px;
  display: flex;
  justify-content: center;
  flex-direction: column;
  align-items: center;
}

.box .mask .icon {
  font-size: 120px;
  margin: 20px;
  color: #606266;
}

.box .mask .btn {
  width: 60%;
}
</style>
