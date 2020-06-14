<template>
  <van-cell-group>
    <van-cell>
      <div>{{message}}</div>
    </van-cell>
  </van-cell-group>
</template>

<script>
export default {
  props: {
    message: String
  }
};
</script>

<style scoped>
</style>
