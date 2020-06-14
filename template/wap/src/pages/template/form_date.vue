<template>
  <CellDatePopup :label="item.label" :required="item.required" :value="value" @confirm="onConfirm"/>
</template>
<script>
import CellDatePopup from "@/components/CellDatePopup";
export default {
  name: "form-date",
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
      if (this.item.value) {
        return this.item.value;
      }
      let timeStamp = 0;
      if (this.item.type == 1) {
        timeStamp = this.item.default
          ? Math.round(new Date(this.item.default) / 1000)
          : Math.round(new Date() / 1000);
      } else {
        timeStamp = Math.round(new Date() / 1000);
      }
      this.item.value = timeStamp;
      return timeStamp;
    }
  },
  methods: {
    onConfirm(value) {
      this.item.value = value;
    }
  },
  components: {
    CellDatePopup
  }
};
</script>
<style scoped>
</style>
