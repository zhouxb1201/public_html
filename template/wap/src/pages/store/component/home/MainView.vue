<template>
  <div class="main-view">
    <CategoryView :info="info" />
    <FootSubmitBar />
  </div>
</template>

<script>
import CategoryView from "./CategoryView";
import FootSubmitBar from "./SubmitBar";
export default {
  data() {
    return {};
  },
  props: {
    info: Object
  },
  components: {
    CategoryView,
    FootSubmitBar
  }
};
</script>

<style scoped>
.main-view {
  width: 100%;
}
</style>