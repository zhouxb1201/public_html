<template>
  <van-cell-group>
    <van-cell icon="edit" title="买家留言"/>
    <van-cell>
      <van-col
        span="24"
        class="text-regular fs-12"
      >{{detail.buyer_message?detail.buyer_message:'该订单没有留言'}}</van-col>
    </van-cell>
  </van-cell-group>
</template>

<script>
export default {
  props: {
    detail: Object
  }
};
</script>