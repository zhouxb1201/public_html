<template>
<div class="404">
  <Empty page-type="404" :message="message" btn-text="返回首页" btn-link="/" />
</div>
</template>

<script>
import Empty from "@/components/Empty";
export default {
  name: "page-404",
  data() {
    return {
      message: "很抱歉，找不到你要访问的页面"
    };
  },
  mounted() {
    document.title = this.message;
  },
  components: {
    Empty
  }
};
</script>

<style>
</style>
