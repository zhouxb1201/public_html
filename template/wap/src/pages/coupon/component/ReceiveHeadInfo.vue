<template>
  <div class="head-receive">
    <div class="head">
      <div class="info">
        <div class="shop-img">
          <img :src="detail.shop_logo" :onerror="$ERRORPIC.noAvatar" />
        </div>
        <p class="shop-name">{{detail.shop_name}}</p>
        <p class="time">{{detail.start_time | formatDate }} ~ {{detail.end_time | formatDate}}</p>
        <div class="coupon-wrap" :style="{color:isDisGray}">
          <div class="num" v-if="detail.coupon_genre > 0 && detail.coupon_genre < 3">
            <span>￥</span>
            {{parseFloat(detail.money)}}
          </div>
          <div class="num" v-else>
            {{parseFloat(detail.discount)}}
            <span class="fs-18">折</span>
          </div>
          <div class="explain" v-if="detail.coupon_genre == 1">
            <p class="mb-4">{{detail.coupon_name}}</p>
            <p>无门槛使用</p>
          </div>
          <div class="explain" v-else>
            <p class="mb-4">{{detail.coupon_name}}</p>
            <p>满{{parseFloat(detail.at_least)}}使用</p>
          </div>
          <div class="btn">
            <van-button
              class="btn"
              size="small"
              round
              type="danger"
              :disabled="isDisabled"
              :class="isDisBack"
              @click="bindMobile('onReceive')"
            >{{couponStateText}}</van-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { RECEIVE_COUPON } from "@/api/coupon";
import { bindMobile } from "@/mixins";
export default {
  props: {
    detail: Object
  },
  mixins: [bindMobile],
  computed: {
    isDisabled() {
      let flag = false;
      if (
        this.detail.is_coupon == 0 ||
        this.detail.is_coupon == -1 ||
        this.detail.is_coupon == -2
      ) {
        flag = true;
      }
      return flag;
    },
    isDisGray() {
      let colors = "";
      if (
        this.detail.is_coupon == 0 ||
        this.detail.is_coupon == -1 ||
        this.detail.is_coupon == -2
      ) {
        colors = "#999";
      }
      return colors;
    },
    isDisBack() {
      let backcolors = "";
      if (
        this.detail.is_coupon == 0 ||
        this.detail.is_coupon == -1 ||
        this.detail.is_coupon == -2
      ) {
        backcolors = "backcr-e8";
      }
      return backcolors;
    },
    couponStateText() {
      let text = "";
      if (this.detail.is_coupon == 0) {
        text = "已领取";
      } else if (this.detail.is_coupon > 0) {
        text = "立即领取";
      } else if (this.detail.is_coupon == -1) {
        text = "未开始";
      } else if (this.detail.is_coupon == -2) {
        text = "已过期";
      }
      return text;
    }
  },
  methods: {
    onReceive() {
      const $this = this;
      const params = {};
      params.coupon_type_id = $this.detail.coupon_type_id;
      params.get_type = 6; // 接口规定复制链接领取领取标识
      RECEIVE_COUPON(params).then(res => {
        if (res.code > 0) {
          $this.detail.is_coupon = 0;
          $this.$Toast.success("领取成功");
        } else {
          $this.$Toast.success("领取失败");
        }
      });
    }
  }
};
</script>
<style scoped>
.head {
  position: relative;
  overflow: hidden;
  width: 100%;
  min-height: 130px;
  background: #ff454e;
  padding: 10px 15px;
  color: #ffffff;
}
.head .info {
  width: 100%;
}
.head .info .shop-img {
  width: 60px;
  height: 60px;
  overflow: hidden;
  border-radius: 50%;
  border: 2px solid #ffffff;
  margin: auto;
}
.head .info .shop-img img {
  width: 100%;
  height: 100%;
}
.head .info .shop-name {
  text-align: center;
  padding: 10px;
}
.head .info .time {
  color: #ccc;
  text-align: center;
  font-size: 12px;
}
.coupon-wrap {
  display: flex;
  align-items: center;
  background-color: #fff;
  position: relative;
  overflow: hidden;
  border-radius: 4px;
  margin: 15px 20px;
  padding: 20px 15px;
  color: #ffab33;
}
.coupon-wrap:before {
  content: "";
  position: absolute;
  top: 50%;
  left: -8px;
  width: 16px;
  height: 16px;
  background-color: #ff454e;
  border-radius: 50%;
  transform: translateY(-50%);
}
.coupon-wrap:after {
  content: "";
  position: absolute;
  top: 50%;
  right: -8px;
  width: 16px;
  height: 16px;
  background-color: #ff454e;
  border-radius: 50%;
  transform: translateY(-50%);
}
.coupon-wrap .num {
  font-size: 35px;
  max-width: 99px;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 800;
}
.coupon-wrap .num span {
  font-size: 14px;
  font-weight: normal;
}
.coupon-wrap .explain {
  width: 100px;
  padding-left: 6px;
  flex: 1;
}
.mb-4 {
  margin-bottom: 4px;
}
.coupon-wrap .btn {
  position: relative;
}
.coupon-wrap .btn >>> .van-button--danger {
  background-color: #ffab33;
  border: 1px solid #ffab33;
}
.coupon-wrap .btn >>> .van-button--small {
  height: 24px;
  line-height: 24px;
}
.backcr-e8 {
  background-color: #e8e8e8 !important;
  border: 1px solid #e5e5e5 !important;
}
.fs-18 {
  font-size: 18px !important;
  font-weight: 600 !important;
}
</style>

