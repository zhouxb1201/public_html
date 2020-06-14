<template>
  <div :class="item.id">
    <div class="vui-line" :style="itemStyle">
        <div class="line" :style="lineStyle"></div>
    </div>
  </div>
</template>

<script>
export default {
  name: "tpl_line",
  data() {
    return {
      itemStyle: {
        background: this.item.style.background,
        padding: this.item.style.padding + "px" + " " + 0
      },
      lineStyle: {
        borderTop:
          this.item.style.height +
          "px" +
          " " +
          this.item.style.linestyle +
          " " +
          this.item.style.border
      }
    };
  },
  props: {
    type: [String, Number],
    item: Object
  },
  created() {}
};
</script>

<style scoped>
</style>
