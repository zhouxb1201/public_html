<template>
  <div class="item e-handle" @click="click">
    <div class="image">
      <img v-lazy="image" :key="image" pic-type="goods">
    </div>
    <div class="info">
      <slot name="name">
        <div v-if="name" class="name">
          <van-tag v-if="nameTag" type="danger" class="name-tag">{{nameTag}}</van-tag>
          {{ name }}
        </div>
      </slot>
      <slot name="tag">
        <div class="price">
          <span class="sale">{{price | yuan}}</span>
          <span class="market" v-if="marketPrice">
            <small>{{marketPrice | yuan}}</small>
          </span>
        </div>
      </slot>
      <slot name="bottom">
        <div class="sales-volume" v-if="sales != undefined">
          <span>
            <small>{{salesText}} {{sales}}</small>
          </span>
        </div>
      </slot>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    id: [String, Number],
    image: String,
    name: String,
    nameTag: String,
    price: [String, Number],
    marketPrice: [String, Number],
    sales: [String, Number],
    salesText: {
      type: String,
      default: "销量"
    },
    link: String
  },
  methods: {
    click() {
      if (this.link) {
        this.$router.push(this.link);
      } else {
        if (this.id) {
          this.$router.push({
            name: "goods-detail",
            params: {
              goodsid: this.id
            }
          });
        }
      }
    }
  }
};
</script>
<style scoped>
.item {
  position: relative;
  width: calc(50% - 8px);
  float: left;
  margin: 4px;
  background: #ffffff;
  border-radius: 10px;
  overflow: hidden;
  font-size: 14px;
  box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.05);
}

.item .image {
  height: 0;
  width: 100%;
  padding: 50% 0;
  overflow: hidden;
  background: #ffffff;
}

.item .image img {
  display: block;
  width: 100%;
  margin-top: -50%;
  background-color: #fff;
  border: none;
}

.item .info {
  padding: 6px;
}

.item .info .name {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  padding: 6px 0;
}

/* .item .info .name-tag {
} */

.item .info .price {
  margin-bottom: 6px;
  overflow: hidden;
}

.item .info .price .sale {
  font-weight: 800;
  color: #ff454e;
  font-size: 1.1em;
}

.item .info .price .market {
  font-size: 12px;
  color: #999999;
  font-weight: 400;
  margin-left: 6px;
  text-decoration: line-through;
}

.item .info .sales-volume {
  color: #999999;
  font-size: 12px;
}
</style>
