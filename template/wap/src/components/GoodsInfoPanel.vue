<template>
  <van-cell-group :border="false">
    <van-cell>
      <slot name="header">
        <div class="head" v-if="showHead">
          <div class="price-group">
            <div class="price">
              <div class="text first-letter" :style="{color:priceColor}">{{price}}</div>
              <van-tag
                class="price-tag"
                round
                size="medium"
                color="#FAE9E6"
                text-color="#ff454e"
                v-if="priceTag"
              >{{priceTag}}</van-tag>
            </div>
            <slot name="originPrice">
              <div class="origin-price" :style="{color:priceLightColor}">{{originPrice}}</div>
            </slot>
          </div>
          <slot name="headerRight" />
        </div>
      </slot>
      <slot name="promote">
        <div class="promote-text">{{promoteText}}</div>
      </slot>
      <div class="name-group">
        <van-tag class="name-tag" color="#ff454e" size="medium" v-if="nameTag">{{nameTag}}</van-tag>
        <span class="name" :style="{color:nameColor}">{{name}}</span>
      </div>
    </van-cell>
    <van-cell v-if="(footInfo && footInfo.length) || $slots.footer" value-class="foot">
      <slot name="footer">
        <van-col class="item" span="8" v-for="(f,i) in footInfo" :key="i">
          <span class="foot-name">{{f.name}}</span>
          <span class="foot-value">{{f.value}}</span>
        </van-col>
      </slot>
    </van-cell>
  </van-cell-group>
</template>

<script>
const defaultInfo = {
  name: "", //商品名称
  nameTag: "", //商品名称标签
  price: "", //商品价格
  originPrice: "", //商品原价
  priceTag: "", //商品价格标签
  showHead: true, //显示价格（默认显示）
  promoteText: "", //活动额外文案
  footInfo: [
    {
      name: "运费",
      value: ""
    },
    {
      name: "销量",
      value: ""
    }
  ] //商品底部信息
};
export default {
  data() {
    return {};
  },
  props: {
    name: String,
    nameTag: String,
    price: String,
    originPrice: String,
    priceTag: String,
    showHead: {
      type: Boolean,
      default: true
    },
    promoteText: String,
    footInfo: [Array, String],
    priceColor: String,
    priceLightColor: String,
    nameColor: String
  }
};
</script>

<style scoped>
.head {
  display: flex;
}

.head .price-group {
  flex: auto;
  display: flex;
  flex-flow: column;
}

.head .price-group .price {
  display: flex;
  align-items: center;
}

.head .price-group .price .text {
  color: #ff454e;
  font-weight: 800;
  font-size: 16px;
}

.head .price-group .price-tag {
  margin-left: 10px;
  display: flex;
  font-size: 10px;
}

.head .origin-price {
  font-size: 12px;
  color: #909399;
  text-decoration: line-through;
  line-height: 20px;
}

.promote-text {
  color: #ff454e;
  font-size: 12px;
}

.name-group {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
  font-size: 14px;
  line-height: 1.6;
}

.name-group .name-tag {
  margin-right: 5px;
}

.name-group .name {
  color: #323233;
}

.foot {
  line-height: 16px;
  font-size: 12px;
  color: #909399;
}

.foot-value {
  margin-left: 5px;
}
</style>