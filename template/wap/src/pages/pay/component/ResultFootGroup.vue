<template>
  <van-row class="foot" type="flex" justify="center">
    <van-button
      class="btn"
      type="default"
      size="small"
      v-for="(item,index) in items"
      :key="index"
      @click="click(item)"
    >{{item.text}}</van-button>
  </van-row>
</template>

<script>
export default {
  props: {
    items: Array
  },
  methods: {
    click(item) {
      if (item.event) {
        this.$emit("click", item);
      } else {
        this.$router.replace(item.to);
      }
    }
  }
};
</script>

<style scoped>
.foot {
  padding: 20px 0;
  background: #fff;
}

.foot .btn {
  margin: 0 10px;
}
</style>
