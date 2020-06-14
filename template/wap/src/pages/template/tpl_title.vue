<template>
  <div :class="item.id">
    <div class="vui-title" :style="itemStyle">{{item.params.title}}</div>
  </div>
</template>

<script>
export default {
  name: "tpl_title",
  data() {
    return {
      itemStyle: {
        background: this.item.style.background,
        color: this.item.style.color,
        fontSize: this.item.style.fontsize + "px",
        padding:
          this.item.style.paddingtop +
          "px" +
          " " +
          this.item.style.paddingleft +
          "px",
        textAlign: this.item.style.textalign
      }
    };
  },
  props: {
    type: [String, Number],
    item: Object
  }
};
</script>

<style scoped>
</style>
