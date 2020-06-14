<template>
  <div :class="item.id">
    <CellBindMobile :info="item"/>
  </div>
</template>

<script>
import CellBindMobile from "../member/component/CellBindMobile";
export default {
  name: "tpl_member_bind_fixed",
  data() {
    return {};
  },
  props: {
    type: [String, Number],
    item: Object
  },
  components: {
    CellBindMobile
  }
};
</script>

<style scoped>
</style>
