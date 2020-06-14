<template>
  <CodeBox :qrcode="detail.card_codeImg" :code="detail.card_code"/>
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
