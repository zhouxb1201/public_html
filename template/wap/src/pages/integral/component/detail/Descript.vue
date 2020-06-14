<template>
  <div class="descript">
    <div v-html="descript" class="richtext" v-if="descript"></div>
    <div v-else class="empty">暂无详情</div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    descript: String
  }
};
</script>

<style scoped>
.descript >>> div,
.descript >>> p {
  max-width: 100% !important;
  overflow: hidden;
}

.descript >>> img {
  max-width: 100% !important;
  height: auto;
}
</style>
