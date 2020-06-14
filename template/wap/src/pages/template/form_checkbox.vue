<template>
  <van-cell :title="item.label" class="cell-panel" :class="item.required?'van-cell--required':''">
    <van-checkbox-group v-model="value" class="cell-checkbox-group">
      <van-checkbox
        shape="square"
        :name="option"
        v-for="(option,index) in options"
        :key="index"
      >{{ option }}</van-checkbox>
    </van-checkbox-group>
  </van-cell>
</template>
<script>
export default {
  name: "form-checkbox",
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
    },
    value: {
      get() {
        let value = this.item.value;
        let arr = [];
        if (value && typeof value == "string") {
          arr = value.split(",");
        }
        return arr;
      },
      set(e) {
        this.item.value = e.join(",");
      }
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
