<template>
  <div>
    <img :src="items.background_pic" :onerror="palaceBgImg" class="palace">
    <div class="rotate-wrap">
      <ul v-if="items.prize">
        <li v-for="(item ,index) in items.prize" :key="index" :style="liStyle(index)">
          <div>
            <img :src="item.prize_pic" :onerror="defaultImg(item.prize_type)">
            <span>{{item.prize_name}}</span>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>
<script>
export default {
  props: {
    items: {}
  },
  computed: {
    palaceBgImg() {
      let imgSrc = null;
      if (this.items.prize) {
        const prizes = this.items.prize;
        let path = this.$BASEIMGPATH;
        if (prizes.length == 6) {
          imgSrc = 'this.src="' + path + "wheels-bg-palace-6.png" + '"';
        } else if (prizes.length == 8) {
          imgSrc = 'this.src="' + path + "wheels-bg-palace-8.png" + '"';
        } else if (prizes.length == 10) {
          imgSrc = 'this.src="' + path + "wheels-bg-palace-10.png" + '"';
        } else if (prizes.length == 12) {
          imgSrc = 'this.src="' + path + "wheels-bg-palace-12.png" + '"';
        }
      }
      return imgSrc;
    },
    liStyle(index) {
      const $this = this;
      const prizes = $this.items.prize;
      let deg = 60;
      let wd = null,
        lf = null,
        pt = null;
      if (prizes.length == 6) {
        deg = 60;
        wd = 41.555;
        lf = 29.555;
        pt = 10;
      } else if (prizes.length == 8) {
        deg = 45;
        wd = 32.888;
        lf = 32.333;
        pt = 10;
      } else if (prizes.length == 10) {
        deg = 36;
        wd = 27;
        lf = 36;
        pt = 12;
      } else if (prizes.length == 12) {
        deg = 30;
        wd = 25;
        lf = 38;
        pt = 10;
      }
      return index => {
        return {
          transform: "rotate(" + index * deg + "deg)",
          width: wd + "%",
          left: lf + "%",
          paddingTop: pt + "%"
        };
      };
    },
    defaultImg(type) {
      let imgSrc = null;
      let path = this.$BASEIMGPATH;
      return type => {
          if (type == 0) {
            // 0 => 未中奖
            imgSrc = 'this.src="' + path + "default-no.jpg" + '"';
          } else if (type == 1) {
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
        return imgSrc;
      };
    }
  },
  methods: {}
};
</script>

<style scoped>
.palace {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
  display: block;
}
.rotate-wrap {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
  z-index: 10;
}
ul {
  position: relative;
  overflow: hidden;
  width: 100%;
  height: 100%;
}
ul li {
  display: block;
  position: absolute;
  top: 0;
  -webkit-transform-origin: 50% 100%;
  transform-origin: 50% 100%;
  height: 50%;
}
ul li div {
  position: relative;
  width: 100%;
}
ul li div span {
  display: block;
  font-size: 12px;
  color: #c30b29;
  overflow: hidden;
  height: 20px;
  line-height: 20px;
  text-align: center;
  width: 60%;
  margin: auto;
}
ul li div img {
  width: 30%;
  height: 30%;
  display: block;
  margin: auto;
}
/*ul li:nth-child(odd) {
  background-color: cornflowerblue;
}
ul li:nth-child(even) {
  background-color: #70c0b3;
}*/
</style>
