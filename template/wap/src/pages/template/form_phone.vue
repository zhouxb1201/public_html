<template>
  <van-field
    :required="item.required"
    :label="item.label"
    type="number"
    maxlength="11"
    :placeholder="item.placeholder"
    v-model="item.value"
  />
</template>
<script>
export default {
  name: "form-phone",
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
