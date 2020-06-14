<template>
  <div class="card card-group-box">
    <div class="card__header">
      <div class="card__head__left">
        <div class="card__img">
          <img :src="items.pic" :onerror="defaultImg(items.type)" />
        </div>
        <div class="card__goods__info">
          <div>{{items.prize_name}}</div>
          <div>{{items.activity_name}}</div>
        </div>
      </div>
      <div class="card__head__right">【{{items.term_name}}】</div>
    </div>
    <div class="card__content">
      <div class="w50 time">{{items.expire_time | formatDate("s")}}过期</div>
      <div class="w50 btn" v-if="items.state == 1">
        <van-button plain type="danger" @click="onConfirm">领奖</van-button>
      </div>
      <div class="w50 btn" v-else-if="items.state == 2">
        <van-button plain type="primary" v-if="items.type == 5" @click="onLogistics">物流</van-button>
        <!--<van-button plain type="danger" disabled class="back">已领奖</van-button>-->
      </div>
      <div class="w50 btn" v-else-if="items.state == 3">
        <van-button plain type="danger" disabled class="back">已过期</van-button>
      </div>
    </div>
  </div>
</template>
<script>
import { _encode } from "@/utils/base64";
export default {
  props: {
    items: [Object, Array]
  },
  computed: {
    defaultImg(type) {
      let imgSrc = null;
      let path = this.$BASEIMGPATH;
      return type => {
        if (type) {
          if (type == 1) {
            // 1 => 余额
            imgSrc = 'this.src="' + path + "default-balance.png" + '"';
          } else if (type == 2) {
            // 2 => 积分
            imgSrc = 'this.src="' + path + "default-integral.png" + '"';
          } else if (type == 3) {
            // 3 => 优惠券
            imgSrc = 'this.src="' + path + "default-coupon.png" + '"';
          } else if (type == 4) {
            // 4 => 礼品券
            imgSrc = 'this.src="' + path + "default-giftvoucher.png" + '"';
          } else if (type == 5) {
            // 5 => 商品
            imgSrc = 'this.src="' + path + "default-goods.png" + '"';
          } else if (type == 6) {
            // 6 => 赠品
            imgSrc = 'this.src="' + path + "default-gift.png" + '"';
          }
        }
        return imgSrc;
      };
    }
  },
  methods: {
    onConfirm() {
      const $this = this;
      const params = {};
      params.member_prize_id = $this.items.member_prize_id;
      $this.$router.push({
        name: "prize-confirm",
        query: {
          params: _encode(JSON.stringify(params))
        }
      });
    },
    onLogistics() {
      const orderid = this.items.receive_id;
      this.$router.push({
        name: "order-logistics",
        params: {
          orderid: orderid
        }
      });
    }
  }
};
</script>
<style scoped>
.card {
  position: relative;
  color: #323233;
  font-size: 12px;
  padding: 0;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  background-color: #ffffff;
  margin-top: 10px;
  padding: 10px;
}
.card__header {
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  position: relative;
  padding-bottom: 10px;
  min-height: 70px;
}
.card__header:after {
  content: "";
  position: absolute;
  pointer-events: none;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  left: 0;
  right: 0;
  bottom: 0;
  -webkit-transform: scaleY(0.5);
  transform: scaleY(0.5);
  border-bottom: 1px solid #ebedf0;
}
.card__head__left {
  flex: 1;
  display: flex;
  display: -webkit-box;
  display: -ms-flexbox;
}
.card__head__left .card__img {
  width: 60px;
  height: 60px;
  position: relative;
  overflow: hidden;
}
.card__head__left .card__img img {
  width: 100%;
  height: auto;
}
.card__goods__info {
  flex: 1;
  padding-left: 10px;
  font-size: 14px;
}
.card__goods__info div:last-child {
  color: #b5b5b5;
  margin-top: 4px;
}
.card__head__right {
  color: #fd5b5b;
  font-size: 14px;
}
.card__content {
  position: relative;
  display: flex;
  display: -webkit-box;
  display: -ms-flexbox;
  padding-top: 10px;
  min-height: 30px;
  line-height: 30px;
}
.card__content .w50 {
  width: 50%;
}
.card__content .time {
  color: #b5b5b5;
  font-size: 12px;
}
.card__content .btn {
  text-align: right;
}
.card__content .btn >>> .van-button {
  height: 30px;
  line-height: 30px;
  border-radius: 5px;
  margin-left: 6px;
  width: 74px;
}
.card__content .btn .back {
  color: #777;
}
.card__content .btn >>> .van-button--primary {
  color: #666;
  border-color: #666;
}
</style>



