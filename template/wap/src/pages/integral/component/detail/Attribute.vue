<template>
  <div class="attribute">
    <van-cell-group v-if="attribute.length>0">
      <van-cell v-for="(item,index) in attribute" :key="index">
        <van-col span="8">{{item.attr_value}}</van-col>
        <van-col span="16">{{item.attr_value_name}}</van-col>
      </van-cell>
    </van-cell-group>
    <div class="empty" v-else>暂无属性</div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    attribute: Array
  }
};
</script>

<style scoped>
</style>
