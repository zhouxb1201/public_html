<template>
  <van-field
    :required="item.required"
    :label="item.label"
    type="text"
    placeholder="请输入身份证"
    v-model="item.value"
  />
</template>
<script>
export default {
  name: "form-card",
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
