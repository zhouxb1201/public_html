<template>
  <van-button class="btn" type="danger" size="mini">
    <van-icon name="like-o" class="btn-icon" :loading="isLoading"/>收藏
  </van-button>
</template>

<script>
export default {
  data() {
    return {
      isLoading: false
    };
  },
  props: {}
};
</script>


<style scoped>
.btn {
  margin-left: 10px;
}

.btn .btn-icon {
  vertical-align: middle;
  margin-right: 2px;
}
</style>
