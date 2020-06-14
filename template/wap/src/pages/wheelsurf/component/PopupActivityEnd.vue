<template>
  <div class="popup" v-if="isShow">
    <div class="popup-wrap">
      <div class="popup-main-end">
        <h3 class="txt-end-title">你来晚了~活动已结束！</h3>
        <p class="txt-end-follow">关注公众号商城，更多好礼等你拿</p>
        <p></p>
        <div class="end">
          <img :src="$BASEIMGPATH + 'end.png'">
        </div>

        <van-row type="flex" justify="center" class="btn-know">
          <van-button type="danger" size="small" @click.native="close">知道了</van-button>
        </van-row>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  props: {
    //是否弹出
    isShow: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {};
  },
  methods: {
    close() {
      this.$emit("close");
    }
  }
};
</script>

<style scoped>
.popup {
  background-color: rgba(0, 0, 0, 0.5);
  position: fixed;
  top: 0;
  left: 0;
  transform: translate3d(0, 0, 0);
  -webkit-transform: translate3d(0, 0, 0);
  height: 100%;
  width: 100%;
  z-index: 2001;
  transition: all 0.2s;
}
.popup-wrap {
  position: relative;
  width: 100%;
  height: 100%;
}
.popup-main-end {
  background-color: #fff;
  width: 86%;
  border-radius: 5px;
  padding: 10px;
  position: relative;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
.end {
  width: 100%;
  margin: auto;
  position: relative;
  padding: 10px;
}
.end >>> img {
  width: 100%;
  height: 100%;
}
.txt-end-title {
  text-align: center;
  color: #333333;
  padding: 14px;
  font-size: 18px;
  font-weight: normal;
}
.txt-end-follow {
  text-align: center;
  color: #999999;
  font-size: 14px;
}
.btn-know {
  width: 80%;
  margin: auto;
  margin-top: 10px;
}
.btn-know >>> .van-button {
  background: linear-gradient(left, #ff6829, #fe1c41);
  background: -webkit-gradient(left, #ff6829, #fe1c41);
  height: 38px;
  border-radius: 20px;
  font-size: 16px;
}
.btn-know >>> .van-button {
  width: 80%;
}
</style>

