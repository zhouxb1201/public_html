<template>
  <Layout ref="load" class="smashegg-centre bg-f6">
    <Navbar :title="navbarTitle" />
    <HeadBanner :src="$BASEIMGPATH+'smashegg-bg.png'"></HeadBanner>
    <div class="right-tag">
      <div class="menu" @click="openExplain">活动说明</div>
      <router-link class="menu" to="/prize/list">我的奖品</router-link>
    </div>
    <!--砸金蛋-->
    <div class="smashegg-main">
      <div class="smashegg-egg">
        <div class="item-egg" v-for="(item, index) in itemsEgg" :key="index">
          <div class="egg-img" @click="haveHand(index)">
            <img :src="$BASEIMGPATH + item.eggimg" />
          </div>
          <img class="hammer" :src="$BASEIMGPATH + item.hammer" :class="item.hammerMove" />
        </div>
      </div>
      <p class="smashegg-chance" v-if="frequency !== (-9999)">
        剩余抽奖次数：
        <span>{{frequency}}</span>次
      </p>
      <div class="btn-continue">
        <van-button type="default" :disabled="isContinue" @click="onContinue">再砸一次</van-button>
      </div>
    </div>

    <!--中奖情况-->
    <PrizeList ref="situation"></PrizeList>

    <!--活动结束-->
    <PopupActivityEnd :isShow="activity" @close="closeToast"></PopupActivityEnd>

    <!--活动说明-->
    <Explain :isShow="isExplain" @close="closeExplain" :id="smasheggid"></Explain>

    <!--中奖弹出框-->
    <PopupWinPrize
      :isShow="winPrize"
      :termname="termname"
      :prizename="prizename"
      @close="closeToast"
      @share="openShare"
    ></PopupWinPrize>

    <!--未中奖弹出框-->
    <PopupNoPrize :isShow="noPrize" @close="closeToast" @share="openShare"></PopupNoPrize>

    <!--分享-->
    <PopupShare :isShow="isShare" @click.native="closeShare"></PopupShare>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import HeadBanner from "@/components/HeadBanner";
import { GET_FREQUENCY, SET_USERSMASHEGG } from "@/api/smashegg";
import PrizeList from "./component/PrizeList";
import PopupActivityEnd from "../wheelsurf/component/PopupActivityEnd";
import Explain from "./component/Explain";
import PopupNoPrize from "../wheelsurf/component/PopupNoPrize";
import PopupWinPrize from "../wheelsurf/component/PopupWinPrize";
import PopupShare from "../wheelsurf/component/PopupShare";
export default sfc({
  name: "smashegg-centre",
  data() {
    return {
      itemsEgg: [
        { eggimg: "egg-close.png", hammer: "hammer.png", hammerMove: "" },
        { eggimg: "egg-close.png", hammer: "hammer.png", hammerMove: "" },
        { eggimg: "egg-close.png", hammer: "hammer.png", hammerMove: "" }
      ],
      navtitle: "",

      smasheggid: 1,

      frequency: 0, //抽奖次数

      activity: false, //活动是否开始

      isExplain: false, //是否弹出活动说明

      noPrize: false, //未中奖弹框
      winPrize: false, //中奖弹框

      termname: null, //奖项名称
      prizename: null, //奖品名称

      isShare: false, //是否弹出分享层

      isContinue: true, //是否在砸一次

      clickFlag: true //防止砸蛋过程中重复砸蛋
    };
  },
  computed: {
    navbarTitle() {
      let title = "疯狂砸金蛋";
      if (this.navtitle) {
        title = this.navtitle;
      }
      if (title) document.title = title;
      return title;
    }
  },
  mounted() {
    if (this.$store.state.config.addons.smashegg) {
      if (this.$route.params.smasheggid) {
        this.smasheggid = this.$route.params.smasheggid;
      } else {
        return;
      }
      GET_FREQUENCY(this.smasheggid)
        .then(({ data }) => {
          this.navtitle = data.smashegg_name;
          if (data.state == 1) {
            //活动待开始
            this.$refs.load.result();
            this.$Toast("活动还未开始");
            this.$router.replace("/member/centre");
          } else if (data.state == 2) {
            this.frequency = data.frequency;
            //活动进行中
          } else if (data.state == 3) {
            //活动已结束
            this.activity = true;
          }
        })
        .catch(() => {
          this.$refs.load.fail();
        });
      this.$refs.load.success();
    } else {
      this.$refs.load.fail({ errorText: "未开启砸金蛋应用", showFoot: false });
    }
  },
  beforeDestroy() {
    //跳转组件的时候清除定时器
    this.$refs.situation.closeInterval();
  },
  methods: {
    haveHand(index) {
      if (!this.isContinue) return;
      if (this.frequency === 0) {
        this.$Toast("抱歉，您已经没有抽奖机会了。");
        return false;
      }
      if (!this.clickFlag) return;
      this.clickFlag = false; // 砸蛋结束前，不允许再次触发
      SET_USERSMASHEGG(this.smasheggid).then(res => {
        if (res.code == 0) {
          // 0 ==> 未中奖
          this.handld(index);
          setTimeout(() => {
            this.noPrize = true;
          }, 500);
        } else if (res.code == 1) {
          // 1 ==> 中奖
          this.handld(index);
          this.termname = res.data.term_name;
          this.prizename = res.data.prize_name;
          setTimeout(() => {
            this.winPrize = true;
          }, 500);
        } else {
          return false;
        }
      });
      setTimeout(() => {
        this.isContinue = false;
      }, 500);
    },
    handld(index) {
      if (this.frequency !== -9999) {
        this.frequency = this.frequency - 1;
      }
      if (this.itemsEgg[index].eggimg == "egg-close.png") {
        this.itemsEgg[index].hammerMove = "shak";
        setTimeout(() => {
          this.itemsEgg[index].eggimg = "egg-open.png";
          this.clickFlag = true;
        }, 500);
      }
    },
    onContinue() {
      //在砸一次
      this.isContinue = true;
      for (let i = 0; i < this.itemsEgg.length; i++) {
        this.itemsEgg[i].eggimg = "egg-close.png";
        this.itemsEgg[i].hammerMove = "";
      }
    },
    //关闭弹窗
    closeToast() {
      if (this.activity == true) {
        this.activity = false;
        if (window.history.length <= 1) {
          this.$router.replace("/");
        } else {
          this.$router.go(-1);
        }
      } else if (this.noPrize == true) {
        this.noPrize = false;
      } else if (this.winPrize == true) {
        this.winPrize = false;
      }
    },
    //关闭分享
    closeShare() {
      this.isShare = false;
    },
    //打开分享
    openShare() {
      this.winPrize = false;
      this.noPrize = false;
      if (this.$store.state.isWeixin) {
        this.isShare = true;
      } else {
        this.$Toast("请点击下方工具栏“分享”按钮进行分享");
      }
    },
    //关闭活动说明
    closeExplain() {
      this.isExplain = false;
    },
    //打开活动说明
    openExplain() {
      this.isExplain = true;
    }
  },
  components: {
    Explain,
    HeadBanner,
    PrizeList,
    PopupActivityEnd,
    PopupNoPrize,
    PopupWinPrize,
    PopupShare
  }
});
</script>

<style scoped>
.bg-f6 {
  background-color: #b10f11;
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
/****砸金蛋****/
.smashegg-main {
  position: relative;
}

.smashegg-egg {
  position: relative;
  width: 100%;
}
.smashegg-egg:after {
  content: "";
  display: block;
  clear: both;
}
.item-egg {
  position: relative;
  width: 33.333%;
  float: left;
}
.item-egg .egg-img {
  width: 100%;
  display: block;
  position: relative;
}
.item-egg .egg-img img {
  width: 100%;
}
.item-egg .hammer {
  position: absolute;
  width: 35%;
  top: 0;
  right: 0;
  display: none;
}
.item-egg .egg-img span {
  color: #ff0;
  font-size: 24px;
  font-weight: bold;
  display: block;
  position: absolute;
  top: 44%;
  left: 50%;
  transform: translate(-50%, -50%);
}
.btn-continue >>> .van-button {
  background-color: #fff55a;
  border: 1px solid #fff55a;
  color: #a50000;
  margin: 15px auto;
  display: block;
  height: 36px;
  line-height: 36px;
  border-radius: 20px;
  box-sizing: border-box;
  box-shadow: 0px 4px 1px #fec201;
  width: 60%;
}
.btn-continue >>> .van-button--disabled {
  opacity: 1;
  box-shadow: 0px 4px 1px #cccccc;
  color: #999;
  background-color: #e8e8e8;
  border: 1px solid #e5e5e5;
}
/***************/
.smashegg-chance {
  text-align: center;
  color: #fff;
  font-size: 14px;
  margin-top: 10px;
}
.smashegg-chance span {
  color: #ffff00;
}
/***********/
.shak {
  animation: hammer-move 0.5s linear 1 alternate;
  display: block !important;
}
@keyframes hammer-move {
  0% {
    transform: rotate(0deg);
    transform-origin: right bottom;
  }
  30% {
    transform: rotate(10deg);
    transform-origin: right bottom;
  }
  60% {
    transform: rotate(30deg);
    transform-origin: right bottom;
  }
  90% {
    transform: rotate(10deg);
    transform-origin: right bottom;
  }
  100% {
    transform: rotate(0deg);
    transform-origin: right bottom;
  }
}
</style>
