<template>
  <div :class="item.id">
    <Header :item="item"/>
  </div>
</template>

<script>
import Header from "../member/component/Header";
export default {
  name: "tpl_member_fixed",
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
