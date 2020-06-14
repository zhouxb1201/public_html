<template>
  <div class="descript">
    <div v-html="content" :class="emptyClass"></div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    data: String
  },
  computed: {
    emptyClass() {
      return this.data ? "" : "empty";
    },
    content() {
      return this.data || "暂无详情";
    }
  }
};
</script>

<style scoped>
.descript {
  background: #fff;
}

.descript div,
.descript p {
  max-width: 100% !important;
  overflow: hidden;
}

.descript img {
  max-width: 100% !important;
  height: auto;
  vertical-align: top;
}
</style>
