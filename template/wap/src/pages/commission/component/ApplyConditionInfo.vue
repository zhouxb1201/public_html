<template>
  <van-cell-group>
    <van-cell :value="title"/>
    <van-cell>
      <div class="condition-item" v-for="(item,index) in items" :key="index" v-html="item"/>
    </van-cell>
  </van-cell-group>
</template>

<script>
export default {
  props: {
    title: String,
    items: {
      type: Array,
      default: []
    }
  }
};
</script>

