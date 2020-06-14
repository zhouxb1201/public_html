<template>
  <div :class="item.id">
    <div :style="itemStyle"></div>
  </div>
</template>

<script>
export default {
  name: "tpl_blank",
  data() {
    return {
      itemStyle:{
        background:this.item.style.background,
        height:this.item.style.height+'px'
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
