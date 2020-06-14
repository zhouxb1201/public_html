<template>
<div class="divider">
  <div class="divider-head"><span v-text="title" /></div>
  <div class="divider-content" v-if="content && !$slots.html">
    <div v-html="content" class="richtext"/>
  </div>
  <slot name="html" />
</div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    title: {
      type: String
    },
    content: {
      type: String
    }
  }
};
</script>

<style scoped>
.divider {
  background: #ffffff;
}

.divider .divider-head {
  text-align: center;
  margin: 0 5%;
  position: relative;
  padding: 15px 0;
}

.divider .divider-head::before {
  content: "";
  position: absolute;
  top: 50%;
  left: 0;
  border: 0 solid #eee;
  transform-origin: 0 0;
  pointer-events: none;
  width: 100%;
  border-top-width: 1px;
}

.divider .divider-head span {
  background: #fff;
  padding: 2px 10px;
  position: relative;
  z-index: 1;
  color: #666;
  letter-spacing: 1px;
}

.divider .divider-content {
  padding: 15px;
}
</style>
