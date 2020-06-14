<template>
  <CodeBox :qrcode="detail.verification_qrcode" :code="detail.verification_code"/>
</template>

<script>
import CodeBox from "@/components/CodeBox";
export default {
  props: {
    detail: Object
  },
  components: {
    CodeBox
  }
};
</script>

<style scoped>
</style>
