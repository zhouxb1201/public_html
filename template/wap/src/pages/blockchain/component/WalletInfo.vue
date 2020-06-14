<template>
  <div class="info">
    <div class="head">
      <div class="title">{{typeToUpperCase}}</div>
      <div class="text">
        <span>{{title}}</span>
        <van-icon
          class="icon a-copy"
          name="v-icon-copy"
          :data-clipboard-text="title"
          @click="onCopy"
        />
      </div>
    </div>
    <div class="qr-box">
      <div class="img">
        <img :src="info.qrCode" />
      </div>
      <div class="text">收款二维码</div>
    </div>
  </div>
</template>

<script>
import { clipboard } from "@/mixins";
export default {
  data() {
    return {
      typeToUpperCase: this.type.toUpperCase()
    };
  },
  props: {
    type: String,
    info: Object
  },
  mixins: [clipboard],
  computed: {
    title() {
      return this.type == "eth" ? this.info.address : this.info.accountName;
    }
  }
};
</script>

<style scoped>
.info {
  border-radius: 8px;
  overflow: hidden;
  margin: 30px;
}

.info .head {
  background: #ff454e;
  padding: 10px 15px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: #fff;
  line-height: 1.4;
}

.info .head .title {
  font-size: 16px;
}

.info .head .text {
  display: flex;
  align-items: center;
  width: 100%;
}

.info .head .text span {
  display: block;
  width: 90%;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  text-align: center;
}

.info .head .text .icon {
  width: 16px;
  height: 16px;
}

.info .qr-box {
  background: #fff;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 0;
  width: 100%;
  padding: 50% 0;
  overflow: hidden;
  background: #ffffff;
}

.info .qr-box .img {
  height: 0;
  width: 70%;
  padding: 35% 0;
  overflow: hidden;
  background: #ffffff;
}

.info .qr-box .img img {
  display: block;
  width: 100%;
  margin-top: -50%;
  background-color: #fff;
  border: none;
}

.info .qr-box .text {
  font-size: 12px;
  color: #909399;
  margin-top: 10px;
}
</style>
