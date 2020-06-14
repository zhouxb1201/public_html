<template>
  <div :class="item.id">
    <Header :item="item"/>
  </div>
</template>

<script>
import Header from "../shop/component/Header";
export default {
  name: "tpl_shop_head",
  data() {
    return {};
  },
  props: {
    type: [String, Number],
    item: Object
  },
  components: {
    Header
  }
};
</script>

<style scoped>
</style>
