<template>
  <div class="head">
    <img class="bg" :src="$BASEIMGPATH+'microshop-head-bg.png'">
    <div class="info">
      <div class="img">
        <img :src="info.user_headimg | BASESRC" :onerror="$ERRORPIC.noAvatar">
      </div>
      <div class="text">
        <div class="name">亲爱的{{info.member_name}}</div>
        <div class="message">{{message}}</div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    info: Object,
    pageType: {
      type: [String, Number],
      required: true
    }
  },
  computed: {
    message() {
      return this.pageType == 2
        ? "购买下面任意礼包即可成为店主"
        : "购买商品晋升相应等级";
    }
  }
};
</script>

<style scoped>
.head {
  position: relative;
  overflow: hidden;
}
.bg {
  width: 100%;
  height: auto;
  display: block;
}
.info {
  position: absolute;
  left: 20px;
  top: 50%;
  display: flex;
  align-items: center;
  z-index: 1;
  margin-top: -20px;
}

.info .img {
  width: 40px;
  height: 40px;
  overflow: hidden;
  border-radius: 50%;
  border: 1px solid #ffffff;
}

.info .img img {
  width: 100%;
  height: 100%;
}

.info .text {
  margin-left: 10px;
  color: #fff;
}

.info .text .name {
  padding-bottom: 6px;
}
.info .text .message {
  font-size: 12px;
}
</style>
