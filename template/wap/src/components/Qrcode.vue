<template>
  <Qrcode :options="options" :value="value" :tag="tag"/>
</template>

<script>
import Qrcode from "../../static/js/vue-qrcode.min.js";
export default {
  props: {
    value: String,
    tag: String,
    options: [String, Object]
  },
  components: {
    Qrcode
  }
};
</script>
