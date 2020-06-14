<template>
<div :class="item.id">
  <div class="vui-richtext" :style="itemStyle">
    <div v-html="content" class="richtext"></div>
  </div>
</div>
</template>

<script>
import { _decode } from "@/utils/base64";
export default {
  name: "tpl_richtext",
  data() {
    return {
      itemStyle: {
        padding: this.item.style.padding + "px"
      },
      content: ""
    };
  },
  props: {
    type: [String, Number],
    item: Object
  },
  created() {
    const $this = this;
    let content = $this.item.params.content;
    if (content) {
      $this.content = _decode(content);
    } else {
      $this.content = "这里是自定义区域";
    }
  }
};
</script>

<style scoped>
</style>
