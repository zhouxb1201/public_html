<template>
  <van-popup v-model="isShow" position="bottom" :overlay="true">
    <div class="header-goods">
      <h3>共{{goods_list.length}}件推荐商品</h3>
      <van-icon name="close" size="16px" @click="isShow = false" />
    </div>
    <div class="goods-box" v-if="goods_list">
      <div
        class="item"
        v-for="(item,index) in goods_list"
        :key="index"
        @click="toGoodsDetail(item.goods_id)"
      >
        <div class="item-g">
          <img
            :src="item.goods_img ? item.goods_img : $ERRORPIC.noGoods"
            :onerror="$ERRORPIC.noGoods"
            class="img"
          />
          <div class="content">
            <div class="title">{{item.goods_name}}</div>
            <div class="bottom">
              <span>￥{{item.price}}</span>
              <span>￥{{item.market_price}}</span>
            </div>
          </div>
        </div>
        <div class="cart-i">
          <van-icon name="cart" size="14px" />
        </div>
      </div>
    </div>
  </van-popup>
</template>

<script>
export default {
  data() {
    return {
      isShow: false
    };
  },
  props: {
    goods_list: [Object, Array]
  },
  methods: {
    toGoodsDetail(id) {
      this.$router.push({
        name: "goods-detail",
        params: {
          goodsid: id
        }
      });
    }
  }
};
</script>

<style scoped>
.header-goods {
  width: 100%;
  height: 40px;
  text-align: center;
  color: #333;
  line-height: 40px;
  position: relative;
  background-color: #fff;
}
.header-goods h3 {
  font-weight: normal;
  font-size: 15px;
}
.header-goods >>> .van-icon {
  position: absolute;
  top: 10px;
  right: 10px;
}
.goods-box {
  display: flex;
  overflow-x: hidden;
  overflow-y: auto;
  box-sizing: content-box;
  padding-bottom: 10px;
  flex-direction: column;
  max-height: 400px;
}
.goods-box .item {
  position: relative;
  box-sizing: border-box;
  display: flex;
  padding: 10px;
}
.item-g {
  color: #323233;
  font-size: 12px;
  box-sizing: border-box;
  display: flex;
  flex: 1;
}
.goods-box .item::before {
  content: "";
  position: absolute;
  pointer-events: none;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  top: 0;
  left: 0;
  width: 100%;
  transform: scaleY(0.5);
  height: 1px;
  background-color: #ebedf0;
}
.goods-box .item:first-child {
  margin-left: 0px !important;
}
.goods-box .item .img {
  width: 50px;
  height: 50px;
  display: block;
  margin-right: 10px;
  border-radius: 5px;
  background-color: #fff;
}
.goods-box .item .content {
  position: relative;
  -ms-flex: 1;
  flex: 1;
  height: 50px;
}
.goods-box .item .content .title {
  height: 32px;
  line-height: 16px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  word-break: break-all;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 12px;
  font-weight: 700;
}
.goods-box .item .content .bottom {
  width: 100%;
  line-height: 18px;
  margin-top: 2px;
  position: relative;
}
.goods-box .item .content .bottom span:first-child {
  display: inline-block;
  color: #ff454e;
  font-weight: 700;
  margin-right: 10px;
}
.goods-box .item .content .bottom span:last-child {
  display: inline-block;
  color: #999;
  font-weight: 700;
  text-decoration: line-through;
}
.cart-i {
  width: 30px;
  position: relative;
}
.cart-i >>> .van-icon {
  color: #ff454e;
  border-radius: 50%;
  background-color: #ffffff;
  border: 1px solid #ff454e;
  position: absolute;
  right: 0;
  bottom: 0;
  padding: 3px;
}
</style>