<template>
  <div :class="item.id">
    <img :src="'/public/platform/images/custom/detail_default.png' | BASESRC">
  </div>
</template>

<script>
export default {
  name: "tpl_detail_fixed_preview",
  data() {
    return {};
  },
  props: {
    type: [String, Number],
    item: Object
  }
};
</script>

<style scoped>
img {
  width: 100%;
  height: auto;
}
</style>
