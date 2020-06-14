<template>
  <div class="btn-group">
    <van-button
      round
      type="warning"
      block
      size="normal"
      class="btn"
      :disabled="!isCanBargain ? true : !value"
      @click="onHelpBargain"
    >{{!value ? '已砍' :'帮砍'}}</van-button>
    <van-button
      round
      type="danger"
      block
      size="normal"
      class="btn"
      @click="onBargain"
      v-if="isShowBargain"
    >我要砍价</van-button>
  </div>
</template>
<script>
import { SUB_BARGAIN } from "@/api/bargain";
export default {
  data() {
    return {};
  },
  props: {
    bargain_uid: {
      type: [String, Number],
      required: true
    },
    bargain_record_id: {
      type: [String, Number],
      required: true
    },
    value: Boolean,
    isCanBargain: Boolean,
    isShowBargain: Boolean,
    loadData: {
      type: Function
    }
  },
  methods: {
    onHelpBargain() {
      SUB_BARGAIN(this.bargain_record_id).then(({ message }) => {
        this.$Toast.success(message);
        this.$emit("input", false);
        this.loadData();
      });
    },
    onBargain() {
      const { goodsid, bargainid } = this.$route.params;
      const bargainuid = this.bargain_uid;
      this.$router.push({
        name: "bargain-detail",
        params: { goodsid, bargainid, bargainuid }
      });
    }
  }
};
</script>
<style scoped>
.btn-group .btn {
  margin: 15px 0;
}
</style>
