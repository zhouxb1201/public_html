<template>
  <div class="goods-box" v-if="goods_list">
    <div
      class="item"
      v-for="(item,index) in goods_list"
      :key="index"
      @click="toGoodsDetail(item.goods_id)"
    >
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
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    goods_list: [Object, Array]
  },
  computed: {},
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
.goods-box {
  display: flex;
  overflow-y: hidden;
  overflow-x: auto;
  box-sizing: content-box;
  margin: 10px 10px 0px 10px;
  padding-bottom: 10px;
}
.goods-box .item {
  position: relative;
  color: #323233;
  font-size: 12px;
  box-sizing: border-box;
  background-color: #f8f8f8;
  display: flex;
  padding: 10px;
  flex-shrink: 0;
  border-radius: 10px;
  margin-left: 10px;
  flex-basis: 240px;
  max-width: 240px;
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
</style>