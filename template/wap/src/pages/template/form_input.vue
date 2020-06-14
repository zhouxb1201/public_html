<template>
  <van-field
    :required="item.required"
    :label="item.label"
    :placeholder="item.placeholder"
    v-model="item.value"
  />
</template>
<script>
export default {
  name: "form-input",
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
