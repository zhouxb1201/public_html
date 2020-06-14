<template>
  <div class="promote-info">
    <img :src="bgSrc" class="bg" />
    <div class="box">
      <div class="price-group">
        <div class="price">
          <div class="text first-letter" :style="{color:priceColor}">{{info.price}}</div>
          <van-tag
            class="price-tag"
            round
            size="medium"
            color="rgba(0,0,0,.15)"
            text-color="#ffffff"
            v-if="info.priceTag"
          >{{info.priceTag}}</van-tag>
        </div>
        <div
          class="origin-price"
          :class="type=='presell'?'no-through':''"
          :style="{color:lightColor}"
        >
          <span>{{info.originPrice}}</span>
          <Progressbar
            v-if="type=='seckill'"
            class="seckill-progress"
            bg="#FFD5D2"
            :bar-bg="'url('+$BASEIMGPATH + 'promote-progress-bg.png) center center'"
            :value="info.buyNumPercent"
          >
            <div slot="progress-text">{{info.buyNumPercentText}}</div>
          </Progressbar>
        </div>
      </div>
      <div class="right-time" v-if="type!='group'">
        <div class="time-box" :style="{color:promoteColor}">
          <div class="text">
            <van-icon name="clock-o" class="time-icon" />
            <span>距结束剩余</span>
          </div>
          <CountDown :time="countTime" done-text="00:00:00" class="time-count-down">
            <template v-if="'{%d}'!='00'">
              <span class="span" :style="{background:promoteColor}">{%d}</span>
              <i class="i">天</i>
            </template>
            <span class="span" :style="{background:promoteColor}">{%h}</span>
            <i class="i">:</i>
            <span class="span" :style="{background:promoteColor}">{%m}</span>
            <i class="i">:</i>
            <span class="span" :style="{background:promoteColor}">{%s}</span>
          </CountDown>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import CountDown from "@/components/CountDown";
import Progressbar from "@/pages/seckill/component/Progressbar";
import { yuan } from "@/utils/filter";
export default {
  data() {
    return {};
  },
  props: {
    type: String,
    params: Object,
    goodsInfo: Object,
    priceColor: String,
    lightColor: String
  },
  computed: {
    bgSrc() {
      return this.$BASEIMGPATH + "promote-" + this.type + ".png";
    },
    promoteColor() {
      let color = "";
      if (this.type == "seckill") {
        color = "#EA463D";
      } else if (this.type == "group") {
        color = "#FC5424";
      } else if (this.type == "presell") {
        color = "#E93359";
      } else if (this.type == "bargain") {
        color = "#FF4B1C";
      } else if (this.type == "limit") {
        color = "#FF3419";
      }
      return color;
    },
    // 活动信息
    info() {
      const type = this.type;
      const params = this.params;
      const goodsInfo = this.goodsInfo;
      let info = {};
      info.price = yuan(goodsInfo.goodsPrice);
      info.priceTag = null;
      info.originPrice = yuan(goodsInfo.marketPrice);
      if (type == "seckill" && params.seckill_status == "going") {
        info.price = yuan(goodsInfo.goodsPrice);
        info.priceTag = "已售" + params.robbed_num + "件";
        info.originPrice = yuan(goodsInfo.marketPrice);
        info.buyNumPercent = parseFloat(params.robbed_percent);
        info.buyNumPercentText =
          "已抢" + parseFloat(params.robbed_percent) + "%";
      }
      if (type == "group") {
        info.price = yuan(goodsInfo.groupGoodsPrice);
        info.priceTag = "已有" + params.regiment_count + "人成团";
        info.originPrice = yuan(goodsInfo.goodsPrice);
      }
      if (type == "presell" && params.state == 1) {
        info.price = yuan(goodsInfo.allMoney);
        info.priceTag = "已订" + params.vrnum + "件";
        info.originPrice = "定金 " + yuan(goodsInfo.frontMoney);
      }
      if (type == "bargain" && params.status == 1) {
        info.price = yuan(goodsInfo.goodsPrice);
        info.priceTag = "已砍成功" + params.bargain_sales + "件";
        info.originPrice = yuan(goodsInfo.marketPrice);
      }
      if (type == "limit") {
        info.price = yuan(goodsInfo.goodsPrice);
        info.originPrice = yuan(goodsInfo.marketPrice);
      }
      return info;
    },
    countTime() {
      let time = 0;
      if (this.type == "seckill") {
        time = parseFloat(this.params.end_time) || 0;
      } else if (this.type == "group") {
      } else if (this.type == "presell") {
        time = parseFloat(this.params.end_time) || 0;
      } else if (this.type == "bargain") {
        time = parseFloat(this.params.end_bargain_time) || 0;
      } else if (this.type == "limit") {
        time = parseFloat(this.params.end_time) || 0;
      }
      time = time * 1000;
      return time;
    }
  },
  components: {
    CountDown,
    Progressbar
  }
};
</script>

<style scoped>
.promote-info {
  position: relative;
  background: #ff454e;
  height: auto;
  min-height: 50px;
}

.bg {
  width: 100%;
  height: auto;
  display: block;
}

.box {
  display: flex;
  align-items: center;
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  right: 0;
  z-index: 1;
  background: transparent;
}

.price-group {
  flex: auto;
  display: flex;
  flex-flow: column;
  padding: 0px 15px;
  line-height: 24px;
}

.price-group .price {
  display: flex;
  align-items: center;
}

.price-group .price .text {
  color: #fff;
  font-weight: 800;
  font-size: 16px;
}

.price-group .price-tag {
  margin-left: 10px;
  display: flex;
  font-size: 10px;
}

.price-group .origin-price {
  font-size: 12px;
  color: #909399;
  text-decoration: line-through;
  line-height: 20px;
  display: flex;
  align-items: center;
}

.price-group .origin-price.no-through {
  text-decoration: initial;
}

.right-time {
  display: flex;
  align-items: flex-end;
  padding: 5px 10px;
  height: 100%;
}

.time-box {
  display: flex;
  flex-flow: column;
  font-size: 10px;
  color: #ff454e;
}

.time-box .text {
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1.2;
  margin-bottom: 4px;
  color: inherit;
  font-size: 12px;
}

.time-box .time-icon {
  margin-right: 2px;
  color: inherit;
}

.time-box .time-count-down >>> .box {
  display: flex;
  align-items: center;
  align-content: center;
  line-height: 1;
}

.time-box .time-count-down .span {
  display: flex;
  align-items: center;
  color: #fff;
  background: #ff454e;
  padding: 2px;
  margin: 0 2px;
  font-size: 10px;
  border-radius: 4px;
}

.time-box .time-count-down .i {
  display: flex;
  align-items: center;
  font-style: normal;
  color: inherit;
  font-size: 10px;
}

.box .seckill-progress {
  margin: 0 20px;
  flex: 1;
  height: 14px;
  line-height: 14px;
  font-size: 12px;
}
</style>