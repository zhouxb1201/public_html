<template>
  <van-cell-group>
    <van-cell center>
      <div slot="icon" class="icon-box">
        <van-icon name="v-icon-card" size="2.5em" color="#ff976a"/>
      </div>
      <div>
        <div>{{text}}</div>
        <span
          class="a-link fs-12"
          @click="onAdd"
          v-if="$store.state.isWeixin && $store.getters.config.is_wchat"
        >前往领取>></span>
      </div>
    </van-cell>
  </van-cell-group>
</template>

<script>
export default {
  props: {
    // 多个id逗号隔开
    params: [String, Number]
  },
  computed: {
    text() {
      return this.$store.state.isWeixin && this.$store.getters.config.is_wchat
        ? "领取到微信卡包，通过卡包快速核销。"
        : "使用微信访问商城可将该消费卡领取到“微信卡包”以便下次使用。";
    }
  },
  methods: {
    onAdd() {
      this.$store.dispatch("wxAddCard", this.params).then(() => {
        this.$emit("success");
      });
    }
  }
};
</script>

<style scoped>
.icon-box {
  margin-right: 10px;
}
</style>

