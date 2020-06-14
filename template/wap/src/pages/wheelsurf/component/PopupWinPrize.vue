<template>
  <div class="popup" :style="{transform:isShow ? 'scale(1)' : 'scale(0)'}">
    <div class="popup-win-prize-wrap">
      <div class="prize">
        <img :src="$BASEIMGPATH + 'win_prize_01.png'">
      </div>
      <div class="prize">
        <img :src="$BASEIMGPATH + 'win_prize_02.png'">
        <div class="prize-info">
          <slot name="termname">
            <h3 v-if="termname">{{termname}}</h3>
          </slot>
          <slot name="prizename">
            <div v-if="prizename">{{prizename}}</div>
          </slot>
        </div>
      </div>
      <div class="prize">
        <img :src="$BASEIMGPATH + 'win_prize_03.png'">
        <div class="prize-win-txt">
          <p>运气简直太好了，快去分享吧！</p>
        </div>
      </div>
      <div class="prize">
        <img :src="$BASEIMGPATH + 'win_prize_04.png'">
        <div class="prize-win-txt">
          <van-row type="flex" justify="center" class="btn-wrap">
            <van-button type="default" size="small" @click.native="share">立即分享</van-button>
            <van-button type="danger" size="small" to="/prize/list">去领奖</van-button>
          </van-row>
        </div>
      </div>

      <p class="close" @click="close"></p>
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
    },
    termname: [String],
    prizename: [String]
  },
  data() {
    return {};
  },
  watch:{
    isShow(val){
      if(val === true){
        document.querySelector("html").style.overflow = "hidden";
      }else{
         document.querySelector("html").style.overflow = "";
      }
    }
  },
  methods: {
    close() {
      this.$emit("close");
    },
    share() {
      this.$emit("share");
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
.popup-win-prize-wrap {
  position: relative;
  width: 70%;
  top: 20%;
  left: 15%;
}
.prize {
  position: relative;
  overflow: hidden;
  width: 100%;
  line-height: 0;
}
.prize img {
  width: 100%;
  display: block;
}
.prize-info {
  position: absolute;
  top: 50%;
  left: 50%;
  -webkit-transform: translate(-50%, -70%);
  transform: translate(-50%, -70%);
  width: 62%;
  z-index: 99;
  line-height: 30px;
  font-size: 14px;
  text-align: center;
  color: #333;
}
.prize-info h3 {
  font-size: 18px;
  color: #f33559;
}
.prize-win-txt {
  position: absolute;
  top: 2%;
  left: 10%;
  width: 80%;
  z-index: 99;
  line-height: 30px;
  font-size: 12px;
  text-align: center;
  color: #fff;
}
.btn-wrap {
  width: 100%;
  margin: auto;
}
.btn-wrap >>> .van-button:first-child {
  margin-right: 5%;
  color: #f33559;
  border: 1px solid #fff;
}
.btn-wrap >>> .van-button:last-child {
  background-color: #f33559;
  color: #fff;
  border: 1px solid #f33559;
}
.btn-wrap >>> .van-button {
  width: 45%;
}
.close {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  position: relative;
  overflow: hidden;
  border: 2px solid #fff;
  display: block;
  margin: auto;
  margin-top: 20px;
  background-color: #fff;
}
.close:before {
  content: "";
  position: absolute;
  left: 12px;
  top: 4px;
  width: 2px;
  height: 20px;
  background-color: #333;
  transform: rotate(45deg);
}
.close:after {
  content: "";
  position: absolute;
  left: 12px;
  top: 4px;
  width: 2px;
  height: 20px;
  background-color: #333;
  transform: rotate(-45deg);
}
</style>

