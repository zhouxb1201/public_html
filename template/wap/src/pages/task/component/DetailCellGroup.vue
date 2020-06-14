<template>
  <div class="cell-group">
    <div class="title">{{title}}</div>
    <slot>
      <div class="content" v-html="content"></div>
    </slot>
  </div>
</template>

<script>
export default {
  props: {
    title: String,
    content: String
  }
};
</script>

<style scoped>
.cell-group {
  display: flex;
  flex-direction: column;
  background: #ffffff;
  line-height: 1.6;
  overflow: hidden;
  padding: 10px 15px;
}

.title {
  color: #323233;
}

.content {
  color: #606266;
}
</style>
