<template>
  <Layout ref="load" class="followgift-center">
    <Navbar />
    <img :src="$BASEIMGPATH+'followgift-bg.jpg'" class="bg" />
    <div class="wrap">
      <div class="main">
        <div class="img">
          <img :src="info.prize_pic" :onerror="$ERRORPIC.noGoods" />
        </div>
        <div class="txt">
          <h3>{{info.prize_name ? info.prize_name : ""}}</h3>
          <p>{{info.name ? info.name : ""}}</p>
        </div>
      </div>
      <p class="tip">奖品已放置在列表当中</p>
      <router-link to="/prize/list" class="jump">前往领取</router-link>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_ACCEPTFOLLOWGIFT } from "@/api/followgift";
export default sfc({
  name: "followgift-center",
  data() {
    return {
      info: {}
    };
  },
  computed: {},
  mounted() {
    if (this.$store.state.config.addons.followgift) {
      let prizeid = this.$route.params.prizeid;
      GET_ACCEPTFOLLOWGIFT(prizeid)
        .then(({ data }) => {
          this.info = data;
          this.$refs.load.success();
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    } else {
      this.$refs.load.fail({
        errorText: "未开启关注有礼应用",
        showFoot: false
      });
    }
  }
});
</script>

<style  scoped>
.bg {
  width: 100%;
  height: calc(100vh - 46px);
  position: absolute;
  top: 46px;
  left: 0;
}
.wrap {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -10%);
  width: 100%;
}
.main {
  position: relative;
  background-color: #ffffff;
  display: flex;
  padding: 20px;
  margin: 0px 16px;
  border-radius: 6px;
}
.main:before {
  content: "";
  width: 16px;
  height: 16px;
  background-color: #ee5859;
  border-radius: 50%;
  position: absolute;
  top: 50%;
  left: -8px;
  transform: translateY(-50%);
  z-index: 10;
}
.main:after {
  content: "";
  width: 16px;
  height: 16px;
  background-color: #ee5859;
  border-radius: 50%;
  position: absolute;
  top: 50%;
  right: -8px;
  transform: translateY(-50%);
  z-index: 10;
}
.main .img {
  width: 60px;
  height: 60px;
}
.main .img img {
  width: 100%;
  height: 100%;
}
.main .txt {
  font-size: 14px;
  flex: 1;
  padding-left: 10px;
}
.main .txt h3 {
  font-size: 14px;
  font-weight: normal;
}
.main .txt p {
  font-size: 12px;
  line-height: 30px;
  color: #9e9e9e;
}
.wrap .tip {
  text-align: center;
  margin-top: 10px;
  font-size: 14px;
  color: #fff;
}
.wrap .jump {
  text-align: center;
  font-size: 14px;
  color: #fff542;
  margin-top: 6px;
  position: relative;
  display: block;
}
.wrap .jump:after {
  content: "";
  width: 6px;
  height: 6px;
  border-top: 1px solid #fff542;
  border-right: 1px solid #fff542;
  display: inline-block;
  transform: rotate(45deg) translateY(-1px);
}
</style>

