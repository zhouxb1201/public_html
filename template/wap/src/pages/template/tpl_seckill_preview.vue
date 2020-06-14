<template>
  <van-cell-group :class="item.id">
    <van-cell :title="item.params.title" value-class="time">
      <span class="text">0点场</span>
      <div class="time-box">
        <span>00</span>
        <i>:</i>
        <span>00</span>
        <i>:</i>
        <span>00</span>
        <i>:</i>
        <span>00</span>
      </div>
    </van-cell>
    <van-cell>
      <div class="list">
        <div class="item" v-for="(item,index) in list" :key="index">
          <div class="img">
            <span class="tag">秒杀</span>
            <img :src="item.pic_cover | BASESRC">
          </div>
          <div class="info">
            <div class="name text-nowrap">{{item.goods_name}}</div>
            <div class="price">
              <span class="sale">{{item.seckill_price | yuan}}</span>
            </div>
          </div>
        </div>
      </div>
    </van-cell>
  </van-cell-group>
</template>

<script>
export default {
  name: "tpl_seckill_preview",
  data() {
    return {
      list: [
        {
          pic_cover: "/public/platform/images/custom/default/goods-1.jpg",
          goods_name: "秒杀商品名称",
          goods_id: 1,
          seckill_price: "1.00"
        },
        {
          pic_cover: "/public/platform/images/custom/default/goods-2.jpg",
          goods_name: "秒杀商品名称",
          goods_id: 2,
          seckill_price: "1.00"
        },
        {
          pic_cover: "/public/platform/images/custom/default/goods-1.jpg",
          goods_name: "秒杀商品名称",
          goods_id: 3,
          seckill_price: "1.00"
        }
      ]
    };
  },
  props: {
    type: [String, Number],
    item: Object
  }
};
</script>

<style scoped>
.time {
  color: #474758;
  font-size: 12px;
  display: flex;
  justify-content: flex-end;
  white-space: nowrap;
  overflow: initial;
}

.time .text {
  padding: 0 10px;
}

.time-box span {
  color: #ffffff;
  background: #474758;
  padding: 0px 4px;
  border-radius: 2px;
}

.time-box i {
  font-style: normal;
}

.list {
  overflow: hidden;
  display: flex;
  margin: 0 -4px;
}

.item {
  position: relative;
  width: calc(33.33333% - 8px);
  float: left;
  margin: 4px;
}

.item .img {
  height: 0;
  width: 100%;
  padding: 50% 0;
  overflow: hidden;
  background: #f9f9f9;
  border-radius: 8px;
  position: relative;
}

.item .img .tag {
  position: absolute;
  top: 0;
  left: 0;
  background: #f23030;
  color: #fff;
  font-size: 12px;
  width: 26px;
  text-align: center;
  line-height: 1.4;
  padding: 4px;
}

.item .img img {
  display: block;
  width: 100%;
  margin-top: -50%;
  background-color: #fff;
  border: none;
}

.item .info {
  font-size: 12px;
  line-height: 1.2;
}

.item .info .name {
  padding: 6px 0;
}

.item .info .price {
  overflow: hidden;
  display: flex;
}

.item .info .price .sale {
  font-weight: 800;
  color: #ff454e;
}

.item .info .price .market {
  color: #999999;
  font-weight: 400;
  margin-left: 6px;
  font-size: 0.8em;
  text-decoration: line-through;
}
</style>

