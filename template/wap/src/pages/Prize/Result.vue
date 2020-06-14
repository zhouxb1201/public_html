<template>
  <div ref="load" class="prize-result bg-f8">
    <Navbar :isMenu="false" :isShowLeft="false" />
    <h3 class="success-txt">恭喜你，奖品已收入囊中！</h3>
    <div class="img">
      <img :src="$BASEIMGPATH+'prize-success.png'" />
    </div>
    <van-row type="flex" justify="center" class="btn-wrap">
      <van-button type="danger" size="small" @click.native="openShare">嘚瑟一下</van-button>
      <van-button type="danger" size="small" @click="onBack">完成</van-button>
    </van-row>
    <!--分享-->
    <PopupShare :isShow="isShare" @click.native="closeShare"></PopupShare>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import PopupShare from "../wheelsurf/component/PopupShare";
export default sfc({
  name: "prize-result",
  data() {
    return {
      isShare: false //是否弹出分享层
    };
  },
  methods: {
    //关闭分享
    closeShare() {
      this.isShare = false;
    },
    //打开分享
    openShare() {
      if (this.$store.state.isWeixin) {
        this.isShare = true;
      } else {
        this.$Toast("请点击下方工具栏“分享”按钮进行分享");
      }
    },
    onBack() {
      this.$router.replace({ name: "prize-list" });
    }
  },
  components: {
    PopupShare
  }
});
</script>

<style scoped>
.success-txt {
  font-size: 16px;
  font-weight: normal;
  color: #666;
  text-align: center;
  margin: 40px;
}
.img {
  margin: auto;
  position: relative;
  width: 30%;
}
.img img {
  width: 100%;
  height: auto;
  display: block;
}
.btn-wrap {
  width: 60%;
  margin: auto;
  margin-top: 20px;
}
.btn-wrap >>> .van-button:first-child {
  margin-right: 10%;
}
.btn-wrap >>> .van-button:last-child {
  background-color: transparent;
  color: #f44;
}
.btn-wrap >>> .van-button {
  width: 45%;
  border-radius: 5px;
}
</style>

