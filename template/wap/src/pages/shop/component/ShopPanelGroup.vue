<template>
  <van-cell-group class="cell-group card-group-box">
    <van-cell :to="'/shop/home/'+items.shop_id" clickable>
      <div class="info">
        <van-col span="10" class="logo-box">
          <div class="logo e-handle">
            <img v-lazy="items.shop_logo" :key="items.shop_logo" pic-type="shop">
          </div>
        </van-col>
        <van-col span="14" class="text-box">
          <div>
            <span class="shop-name">{{items.shop_name}}</span>
            <span class="group-name" v-if="items.group_name && items.is_visible">/ {{items.group_name}}</span>
            <Star class="score" :value="items.comprehensive"/>
          </div>
          <div>
            <van-tag type="danger" class="tag" v-if="items.shop_id == 0">自营</van-tag>
            <van-tag type="primary" class="tag" v-if="items.has_store == 1">O2O</van-tag>
          </div>
        </van-col>
      </div>
    </van-cell>
    <van-cell v-if="items.goods_list.length > 0">
      <div class="goods-list">
        <router-link
          tag="div"
          :to="'/goods/detail/'+item.goods_id"
          class="item e-handle"
          v-for="(item,index) in items.goods_list"
          :key="index"
        >
          <div class="img">
            <img v-lazy="item.logo" :key="item.logo" pic-type="square">
          </div>
          <div class="price">{{item.price | yuan}}</div>
        </router-link>
      </div>
    </van-cell>
  </van-cell-group>
</template>
<script>
import Star from "@/components/Star";
export default {
  props: ["items"],
  components: {
    Star
  }
};
</script>
<style scoped>
.info {
  display: flex;
}

.info .logo-box {
  margin-right: 10px;
}

.info .logo {
  padding-bottom: 56%;
  width: 100%;
  position: relative;
  background: #fafafa;
  margin-right: 10px;
}

.info .logo img {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
}

.info .text-box {
  display: flex;
  flex-flow: column;
  justify-content: space-between;
}

.info .tag {
  line-height: 1.2;
}

.info .score {
  line-height: 16px;
  height: 16px;
}

.info .group-name {
  color: #666;
}

.goods-list {
  margin: 0 -4px;
  overflow: hidden;
}

.goods-list .item {
  position: relative;
  width: calc(25% - 8px);
  float: left;
  margin: 4px;
}

.goods-list .item .img {
  height: 0;
  width: 100%;
  padding: 50% 0;
  overflow: hidden;
  background: #f9f9f9;
}

.goods-list .item .img img {
  display: block;
  width: 100%;
  margin-top: -50%;
  background-color: #f9f9f9;
  border: none;
}

.goods-list .item .price {
  color: #ff454e;
}
</style>
