<template>
  <CellAreaPopup
    :label="item.label"
    :required="item.required"
    :area-type="item.type"
    :info="value"
    @confirm="onConfirm"
  />
</template>
<script>
import CellAreaPopup from "@/components/CellAreaPopup";
export default {
  name: "form-area",
  data() {
    return {};
  },
  props: {
    item: {
      type: Object
    }
  },
  computed: {
    value() {
      let obj = {
        text: "",
        code: "",
        id: []
      };
      let value = this.item.value;
      if (value) {
        let arr = [];
        arr = value.split(",");
        obj.text = arr[0];
        obj.id = arr[1].split("/");
        obj.code = arr[2];
      }
      return obj;
    }
  },
  methods: {
    onConfirm(data) {
      let arr = [];
      arr[0] = data.text;
      arr[1] = data.id.join("/");
      arr[2] = data.code;
      this.item.value = arr.join(",");
    }
  },
  components: {
    CellAreaPopup
  }
};
</script>
<style scoped>
</style>
