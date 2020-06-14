<template>
  <div>
    <div class="scratchcard-main">
      <div class="scratchcard-main-wrap">
        <ul>
          <li></li>
          <li></li>
          <li></li>
          <li></li>
          <li></li>
        </ul>
        <div class="scratchcard-main-box" id="scratchWrap">
          <span>{{termname}}</span>
          <canvas id="canvas" height="120"></canvas>
        </div>
        <ul>
          <li></li>
          <li></li>
          <li></li>
          <li></li>
          <li></li>
        </ul>
      </div>
    </div>
    <p class="scratchcard-chance" v-if="frequency !== (-9999)">
      剩余抽奖次数：
      <span>{{frequency}}</span>次
    </p>
    <div class="btn-continue">
      <van-button type="default" @click="onContinue" :disabled="isContinue">再刮一次</van-button>
    </div>

    <!--活动结束-->
    <PopupActivityEnd :isShow="activity" @close="closeToast"></PopupActivityEnd>

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
  </div>
</template>
<script>
import PopupActivityEnd from "../../wheelsurf/component/PopupActivityEnd";
import PopupNoPrize from "../../wheelsurf/component/PopupNoPrize";
import PopupWinPrize from "../../wheelsurf/component/PopupWinPrize";
import PopupShare from "../../wheelsurf/component/PopupShare";

export default {
  data() {
    return {
      events: [], //touch事件集合
      startMoveHandler: null, //touchstart事件
      moveHandler: null, //touchmove 事件
      endMoveHandler: null, //touchend 事件

      isContinue: true, //是否在再刮一次

      noPrize: false, //未中奖弹框
      winPrize: false, //中奖弹框

      isShare: false //是否弹出分享层
    };
  },
  props: {
    handCallback: {
      //第一次刮回调函数
      type: Function,
      default: function() {}
    },
    frequency: {
      type: [Number, String],
      default: 0
    },
    activity: {
      type: [Boolean],
      default: false
    },
    prizeCode: [Number, String],
    prizename: {
      type: String,
      default: ""
    },
    termname: {
      type: String,
      default: ""
    }
  },
  methods: {
    init() {
      if (!this.isSupportCanvas()) {
        this.$refs.load.fail("当前浏览器不支持canvas");
        return;
      }
      const canvasWrap = document.querySelector("#scratchWrap");
      this.canvas = canvasWrap.querySelector("#canvas");
      this.ctx = this.canvas.getContext("2d");
      this.canvas.width = canvasWrap.clientWidth;
      this.canvas.height = canvasWrap.clientHeight;
      this.createCanvasStyle();
      this.bindEvent();
    },
    isSupportCanvas() {
      var elem = document.createElement("canvas");
      return !!(elem.getContext && elem.getContext("2d"));
    },
    createCanvasStyle() {
      var $this = this;

      $this.ctx.fillStyle = "#cfd4d5";
      $this.ctx.fillRect(0, 0, $this.canvas.width, $this.canvas.height);

      $this.ctx.font = "20px bold sans-serif";
      $this.ctx.fillStyle = "#333";
      $this.ctx.textAlign = "center";
      $this.ctx.textBaseline = "middle";
      $this.ctx.fillText("刮一刮", $this.canvas.width / 2, 60);
    },
    bindEvent() {
      this.events = ["touchstart", "touchmove", "touchend"];
      this.addEvent();
    },
    addEvent() {
      this.num = 0;
      this.pos = 0;
      if (!this.isContinue) return;
      this.startMoveHandler = this.startEventHandler.bind(this);
      this.canvas.addEventListener(
        this.events[0],
        this.startMoveHandler,
        false
      );
    },
    startEventHandler(e) {
      e.preventDefault();
      if (this.frequency === 0) {
        this.$Toast("抱歉，您已经没有抽奖机会了。");
        return false;
      }
      this.moveHandler = this.moveEventHandler.bind(this);
      this.endMoveHandler = this.endEventHandler.bind(this);
      this.canvas.addEventListener(this.events[1], this.moveHandler, false);
      document.addEventListener(this.events[2], this.endMoveHandler, false);
    },
    moveEventHandler(e) {
      e.preventDefault();
      e = e.touches[0];
      const canvasPos = this.canvas.getBoundingClientRect(),
        scrollT = document.documentElement.scrollTop || document.body.scrollTop,
        scrollL =
          document.documentElement.scrollLeft || document.body.scrollLeft,
        mouseX = e.pageX - canvasPos.left - scrollL,
        mouseY = e.pageY - canvasPos.top - scrollT;
      this.ctx.beginPath();
      this.ctx.fillStyle = "#FFFFFF";
      this.ctx.globalCompositeOperation = "destination-out";
      this.num++;
      this.pos += Math.abs(mouseX + mouseY);
      if (this.num === 1 && this.isContinue == true) {
        this.handCallback();
      }
      if (this.prizeCode !== 0 && this.prizeCode !== 1) {
        return false;
      }      
      this.ctx.arc(mouseX, mouseY, 15, 0, 2 * Math.PI);
      this.ctx.fill();
    },
    endEventHandler(e) {
      e.preventDefault();
      this.canvas.removeEventListener(this.events[1], this.moveHandler, false);
      document.removeEventListener(this.events[2], this.endMoveHandler, false);
      this.endMoveHandler = null;
      setTimeout(() => {
        this.caleArea();
      }, 100);
    },
    caleArea() {
      if (this.prizeCode !== 0 && this.prizeCode !== 1) {
        return false;
      }
      if (this.pos >= 3000 && this.isContinue == true) {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.canvas.removeEventListener(this.events[0], this.startMoveHandler);
        this.canvas.removeEventListener(
          this.events[1],
          this.moveHandler,
          false
        );
        document.removeEventListener(
          this.events[2],
          this.endMoveHandler,
          false
        );
        if (this.prizeCode == 0) {
          this.noPrize = true;
        } else if (this.prizeCode == 1) {
          this.winPrize = true;
        }
        this.isContinue = false;
        this.num = 0;
        this.pos = 0;
      }
    },
    onContinue() {
      this.isContinue = true;
      this.init();
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
    }
  },
  components: {
    PopupActivityEnd,
    PopupNoPrize,
    PopupWinPrize,
    PopupShare
  }
};
</script>
<style scoped>
.scratchcard-main {
  position: relative;
  background-color: #fff;
  border-radius: 5px;
  height: 140px;
  margin: auto 30px;
  overflow: hidden;
  margin-top: -80px;
}
.scratchcard-main-wrap {
  margin: 10px;
  height: 120px;
  position: relative;
}
.scratchcard-main-wrap ul:first-child {
  position: absolute;
  top: 4px;
  left: -10px;
  z-index: 12;
}
.scratchcard-main-wrap ul:last-child {
  position: absolute;
  top: 4px;
  right: -10px;
  z-index: 12;
}
.scratchcard-main-wrap ul li {
  background-color: #fff;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  margin-bottom: 8px;
}
.scratchcard-main-box {
  width: 100%;
  height: 100%;
  position: relative;
  text-align: center;
  line-height: 120px;
  font-size: 20px;
  overflow: hidden;
}

.scratchcard-main-box canvas {
  position: absolute;
  left: 0;
  top: 0;
  z-index: 10;
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
.scratchcard-chance {
  text-align: center;
  color: #fff;
  font-size: 14px;
  margin-top: 10px;
}
.scratchcard-chance span {
  color: #ffff00;
}
</style>