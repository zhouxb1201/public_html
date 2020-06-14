<template>
  <div :class="item.id">
    <van-notice-bar
      :text="item.params.text"
      :left-icon="item.params.leftIcon | BASESRC"
      :color="item.style.color"
      :background="item.style.background"
    />
  </div>
</template>

<script>
import { NoticeBar } from "vant";
export default {
  name: "tpl_notice",
  data() {
    return {};
  },
  props: {
    type: [String, Number],
    item: Object
  },
  components: {
    [NoticeBar.name]: NoticeBar
  }
};
</script>

<style scoped>
</style>
