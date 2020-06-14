<template>
  <van-cell :title="item.label" class="cell-panel" :class="item.required?'van-cell--required':''">
    <van-radio-group v-model="item.value" class="cell-radio-group">
      <van-radio :name="option" v-for="(option,index) in options" :key="index">{{option}}</van-radio>
    </van-radio-group>
  </van-cell>
</template>
<script>
export default {
  name: "form-radio",
  data() {
    return {};
  },
  computed: {
    options() {
      let options = this.item.options ? this.item.options : [];
      if (typeof options == "string") {
        options = options.split("\n").filter(e => e && e.trim());
      }
      return options;
    }
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
