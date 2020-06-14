<template>
  <van-field
    :required="item.required"
    :label="item.label"
    type="textarea"
    autosize
    rows="2"
    placeholder="请输入内容"
    v-model="item.value"
  />
</template>
<script>
export default {
  name: "form-textarea",
  data() {
    return {};
  },
  props: {
    item: {
      type: Object
    }
  }
};
</script>
<style scoped>
</style>
