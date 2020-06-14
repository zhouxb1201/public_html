<template>
  <Layout ref="load" class="scratchcard-centre bg-f6">
    <Navbar :title="navbarTitle" />
    <HeadBanner :src="$BASEIMGPATH + 'scratchcard-bg.png'"></HeadBanner>
    <div class="right-tag">
      <div class="menu" @click="openExplain">活动说明</div>
      <router-link class="menu" to="/prize/list">我的奖品</router-link>
    </div>
    <!--刮一刮-->
    <Card
      :handCallback="handCallback"
      :frequency="frequency"
      :activity="activity"
      :termname="termname"
      :prizename="prizename"
      :prizeCode="prizeCode"
      ref="scard"
    />

    <!--中奖情况-->
    <PrizeList ref="situation"></PrizeList>

    <!--活动说明-->
    <Explain :isShow="isExplain" @close="closeExplain" :id="scratchcardid"></Explain>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import HeadBanner from "@/components/HeadBanner";
import Explain from "./component/Explain";
import PrizeList from "./component/PrizeList";
import Card from "./component/Card";
import { GET_FREQUENCY, SET_USERSCRATCHCARD } from "@/api/scratchcard";
export default sfc({
  name: "scratchcard-centre",
  data() {
    return {
      navtitle: "",

      scratchcardid: null,

      isExplain: false, //是否弹出活动说明

      frequency: 0, //抽奖次数
      activity: false, //活动是否开始

      termname: null, //奖项名称
      prizename: null, //奖品名称

      prizeCode: null //是否中奖
    };
  },
  computed: {
    navbarTitle() {
      let title = "幸运刮刮乐";
      if (this.navtitle) {
        title = this.navtitle;
      }
      if (title) document.title = title;
      return title;
    }
  },
  mounted() {
    if (this.$store.state.config.addons.scratchcard) {
      if (this.$route.params.scratchcardid) {
        this.scratchcardid = this.$route.params.scratchcardid;
        this.$refs.load.success();
      } else {
        return;
      }
      GET_FREQUENCY(this.scratchcardid)
        .then(({ data }) => {
          this.navtitle = data.scratchcard_name;
          if (data.state == 1) {
            //活动待开始
            this.$refs.load.result();
            this.$Toast("活动还未开始");
            this.$router.replace("/member/centre");
          } else if (data.state == 2) {
            this.frequency = data.frequency;
            this.$refs.scard.init();
            //活动进行中
          } else if (data.state == 3) {
            //活动已结束
            this.activity = true;
            this.$refs.scard.init();
          }
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    } else {
      this.$refs.load.fail({ errorText: "未开启刮刮乐应用", showFoot: false });
    }
  },
  beforeDestroy() {
    //跳转组件的时候清除定时器
    this.$refs.situation.closeInterval();
  },
  methods: {
    //关闭活动说明
    closeExplain() {
      this.isExplain = false;
    },
    //打开活动说明
    openExplain() {
      this.isExplain = true;
    },
    async handCallback() {
      this.termname = "";
      this.prizename = "";
      try {
        let result = await SET_USERSCRATCHCARD(this.scratchcardid).then();
        this.prizeCode = await result.code;
        if (result.code == 0) {
          this.termname = result.message;
          if (this.frequency !== -9999) {
            this.frequency = this.frequency - 1;
          }
        } else if (result.code == 1) {
          this.termname = result.data.term_name;
          this.prizename = result.data.prize_name;
          if (this.frequency !== -9999) {
            this.frequency = this.frequency - 1;
          }
        }
      } catch (e) {
        console.log(e);
      }
    }
  },
  components: {
    HeadBanner,
    Explain,
    PrizeList,
    Card
  }
});
</script>

<style scoped>
.bg-f6 {
  background: linear-gradient(top, #e4421f, #e4233e);
  background: -webkit-gradient(
    linear,
    left top,
    left bottom,
    from(#e4421f),
    to(#e4233e)
  );
  overflow: hidden;
  position: relative;
}
.right-tag {
  position: absolute;
  z-index: 10;
  top: 16%;
  right: 0;
}
.right-tag .menu {
  color: #ff4444;
  background-color: #fff;
  padding: 6px 10px;
  border-bottom-left-radius: 20px;
  border-top-left-radius: 20px;
  font-size: 14px;
  margin-top: 20px;
  display: block;
}
</style>
