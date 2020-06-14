<template>
  <div>
    <div class="box card-group-box van-hairline--surround">{{content}}</div>
    <div class="foot-btn-group">
      <van-button
        size="normal"
        round
        type="danger"
        block
        class="a-copy"
        :data-clipboard-text="content"
        @click="onCopy"
      >复制 KeyStore</van-button>
    </div>
  </div>
</template>

<script>
import { clipboard } from "@/mixins";
export default {
  props: {
    content: String
  },
  mixins: [clipboard]
};
</script>

<style scoped>
.box {
  padding: 10px 15px;
  background: #ffffff;
  word-break: break-all;
}
</style>
