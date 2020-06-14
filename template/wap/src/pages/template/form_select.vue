<template>
  <CellSelector
    :label="item.label"
    :required="item.required"
    placeholder="请选择"
    :columns="options"
    @confirm="onConfirm"
  />
</template>
<script>
import CellSelector from "@/components/CellSelector";
export default {
  name: "form-select",
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
  },
  methods: {
    onConfirm(option, index) {
      this.item.value = option;
    }
  },
  components: {
    CellSelector
  }
};
</script>
<style scoped>
</style>
