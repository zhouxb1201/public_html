<template>
  <Divider title="商品描述">
    <div slot="html">
      <div v-html="descript" class="richtext" v-if="descript"></div>
      <div v-else class="empty">暂无详情</div>
    </div>
  </Divider>
</template>

<script>
import Divider from "@/components/Divider";
export default {
  data() {
    return {};
  },
  props: {
    descript: String
  },
  components: {
    Divider
  }
};
</script>

<style scoped>
.richtext {
  max-width: 100% !important;
  overflow: hidden;
}
</style>
