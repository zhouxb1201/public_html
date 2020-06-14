<template>
  <Layout ref="load" class="wheelsurf-centre bg-f6">
    <Navbar :title="navbarTitle" />
    <HeadBanner :src="$BASEIMGPATH+'wheelsurf-bg.png'"></HeadBanner>
    <div class="right-tag">
      <div class="menu" @click="openExplain">活动说明</div>
      <router-link class="menu" to="/prize/list">我的奖品</router-link>
    </div>
    <!--大转盘-->
    <div class="wheel-wrap">
      <div class="wheel-main">
        <div class="wheel-bg" :style="{transform:rotateAngle,transition:rotateTransition}">
          <Adventures :items="winfo" />
        </div>
        <div class="wheel-pointer-box">
          <div class="wheel-pointer" @click="bindMobile('rotateHandle')">
            <img :src="winfo.pointer_pic" :onerror="pointerPic" />
          </div>
        </div>
      </div>
    </div>
    <p class="wheel-chance" v-if="frequency !== (-9999)">
      剩余抽奖次数：
      <span>{{frequency}}</span>次
    </p>
    <!--中奖情况-->
    <PrizeList ref="situation"></PrizeList>

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

    <!--活动结束-->
    <PopupActivityEnd :isShow="activity" @close="closeToast"></PopupActivityEnd>

    <!--分享-->
    <PopupShare :isShow="isShare" @click.native="closeShare"></PopupShare>

    <!--活动说明-->
    <Explain :isShow="isExplain" @close="closeExplain" :id="wheelsurfid"></Explain>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import HeadBanner from "@/components/HeadBanner";
import PopupWinPrize from "./component/PopupWinPrize";
import PopupNoPrize from "./component/PopupNoPrize";
import PrizeList from "./component/PrizeList";
import PopupActivityEnd from "./component/PopupActivityEnd";
import PopupShare from "./component/PopupShare";
import Explain from "./component/Explain";
import Adventures from "./component/Adventures";
import { bindMobile } from "@/mixins";
import {
  GET_WHEELSURFINFO,
  GET_USERWHEELSURF,
  GET_USERFREQUENCY
} from "@/api/wheelsurf";
export default sfc({
  name: "wheelsurf-centre",
  data() {
    return {
      navtitle: "",
      winfo: {},

      frequency: 0, //抽奖次数
      startRotDegree: 0, //初始旋转角度
      rotateAngle: 0, //将要旋转的角度
      rotateTransition: "transform 5s ease-in-out", //初始化选中的过度属性控制
      clickFlag: true, //是否可以旋转抽奖
      wheelsurfid: 2,

      termname: null, //奖项名称
      prizename: null, //奖品名称

      prizeCode: null, //是否中奖
      noPrize: false, //未中奖弹框
      winPrize: false, //中奖弹框

      activity: false, //活动是否开始
      isShare: false, //是否弹出分享层
      isExplain: false //是否弹出活动说明
    };
  },
  mixins: [bindMobile],
  computed: {
    navbarTitle() {
      let title = "幸运大转盘";
      if (this.navtitle) {
        title = this.navtitle;
      }
      if (title) document.title = title;
      return title;
    },
    pointerPic() {
      let path = this.$BASEIMGPATH;
      return 'this.src="' + path + "wheels-pointer.png" + '"';
    }
  },
  mounted() {
    let $this = this;
    if ($this.$store.state.config.addons.wheelsurf) {
      if (this.$route.params.wheelsurfid) {
        this.wheelsurfid = this.$route.params.wheelsurfid;
      } else {
        return;
      }
      GET_USERFREQUENCY($this.wheelsurfid)
        .then(res => {
          $this.navtitle = res.data.wheelsurf_name;
          if (res.data.state == 1) {
            //活动待开始
            this.$refs.load.result();
            this.$Toast("活动还未开始");
            this.$router.replace("/member/centre");
          } else if (res.data.state == 2) {
            //活动进行中
            $this.frequency = res.data.frequency;
            GET_WHEELSURFINFO($this.wheelsurfid).then(({ data }) => {
              $this.winfo = data;
            });
          } else if (res.data.state == 3) {
            //活动已结束
            $this.activity = true;
          }
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
      $this.$refs.load.success();
    } else {
      $this.$refs.load.fail({ errorText: "未开启大转盘应用", showFoot: false });
    }
  },
  beforeDestroy() {
    //跳转组件的时候清除定时器
    this.$refs.situation.closeInterval();
  },
  methods: {
    rotateHandle() {
      let $this = this;
      if ($this.frequency === 0) {
        $this.$Toast("抱歉，您已经没有抽奖机会了。");
        return false;
      }
      if (!this.clickFlag) return;
      this.clickFlag = false; // 旋转结束前，不允许再次触发
      GET_USERWHEELSURF($this.wheelsurfid).then(res => {
        if (res.code == 0 || res.code == 1) {
          $this.prizeCode = res.code; //0 => 未中奖 1 => 中奖
          $this.termname = res.data.term_name;
          $this.prizename = res.data.prize_name;
          for (let index = 0; index < $this.winfo.prize.length; index++) {
            if ($this.winfo.prize[index].prize_id == res.data.prize_id) {
              //指定每次旋转到的奖品下标
              this.rotating(index);
              break;
            }
          }
        } else {
          return false;
        }
      });
    },
    rotating(index) {
      if (this.frequency !== -9999) {
        this.frequency = this.frequency - 1;
      }
      let resultIndex = index, // 最终要旋转到哪一块，对应prize_list的下标
        randCircle = 10; // 附加多转几圈
      let resultAngle = []; //最终会旋转到下标的位置所需要的度数
      if (this.winfo.prize.length == 6) {
        resultAngle = [360, 300, 240, 180, 120, 60];
      } else if (this.winfo.prize.length == 8) {
        resultAngle = [360, 315, 270, 225, 180, 135, 90, 45];
      } else if (this.winfo.prize.length == 10) {
        resultAngle = [360, 324, 288, 252, 216, 180, 144, 108, 72, 36];
      } else if (this.winfo.prize.length == 12) {
        resultAngle = [360, 330, 300, 270, 240, 210, 180, 150, 120, 90, 60, 30];
      }

      // 转动盘子
      let rotateAngle =
        this.startRotDegree +
        randCircle * 360 +
        resultAngle[resultIndex] -
        (this.startRotDegree % 360);
      this.startRotDegree = rotateAngle;
      this.rotateAngle = "rotate(" + rotateAngle + "deg)";

      // 旋转结束后，允许再次触发
      setTimeout(() => {
        this.clickFlag = true;
        this.gameOver();
      }, 5000); // 延时，保证转盘转完
    },
    gameOver() {
      if (this.prizeCode === 0) {
        //未中奖
        this.noPrize = true;
      } else if (this.prizeCode === 1) {
        //中奖
        this.winPrize = true;
      }
    },
    //关闭弹窗
    closeToast() {
      if (this.winPrize == true) {
        this.winPrize = false;
      } else if (this.noPrize == true) {
        this.noPrize = false;
      } else if (this.activity == true) {
        this.activity = false;
        if (window.history.length <= 1) {
          this.$router.replace("/");
        } else {
          this.$router.go(-1);
        }
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
    HeadBanner,
    PopupWinPrize,
    PopupNoPrize,
    PopupActivityEnd,
    PrizeList,
    PopupShare,
    Explain,
    Adventures
  }
});
</script>

<style scoped>
.bg-f6 {
  background: linear-gradient(top, #e43e23, #e41b47);
  background: -webkit-gradient(
    linear,
    left top,
    left bottom,
    from(#e43e23),
    to(#e41b47)
  );
  overflow: hidden;
  position: relative;
}
.wheel-wrap {
  position: absolute;
  top: 22%;
  left: 0;
  width: 100%;
}
.wheel-main {
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
  margin-top: 10px;
}
.wheel-bg {
  position: relative;
  width: 74%;
  padding-bottom: 74%;
  border-radius: 50%;
}
.wheel-pointer-box {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -56%);
  width: 25%;
  z-index: 100;
}
.wheel-pointer {
  position: relative;
  padding-bottom: 100%;
  width: 100%;
}
.wheel-pointer >>> img {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
}
.wheel-chance {
  text-align: center;
  color: #fff;
  font-size: 14px;
  margin-top: 10px;
}
.wheel-chance span {
  color: #ffff00;
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


    