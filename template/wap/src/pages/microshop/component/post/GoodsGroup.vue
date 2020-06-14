<template>
  <van-cell-group>
    <van-cell value-class="list">
      <div
        class="item"
        v-for="(item,index) in items"
        :key="index"
        @click="$emit('click',item.goods_id,index)"
      >
        <div class="img">
          <img :src="item.pic || item.goods_images[0]" v-lazy="item.pic || item.goods_images[0]">
        </div>
        <div class="name text-nowrap">{{item.goods_name}}</div>
      </div>
    </van-cell>
  </van-cell-group>
</template>

<script>
export default {
  props: {
    items: Array
  }
};
</script>

<style scoped>
.list {
  display: flex;
  overflow-x: auto;
  margin: 0 -10px;
}

.item {
  width: 100px;
  margin: 0 10px;
}

.item .img {
  background: #f1f1f1;
  width: 100px;
  height: 100px;
}

.item .img img {
  width: 100%;
  height: 100%;
}
</style>
