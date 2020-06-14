<template>
  <div class="unopened">
    <Empty page-type="unopened" :message="message" :show-foot="false"/>
  </div>
</template>

<script>
const title = "很抱歉，本商城已经关闭！";
import Empty from "@/components/Empty";
export default {
  name: "unopened",
  computed: {
    message() {
      const reason = this.$store.state.config.close_reason
        ? this.$store.state.config.close_reason
        : this.$store.getters.config.close_reason;
      document.title = title;
      return `${title}<p class="close-reason">
        关闭原因：${reason}
      </p>`;
    }
  },
  components: {
    Empty
  }
};
</script>

<style>
.close-reason {
  padding: 10px 50px;
  line-height: 1.2;
}
</style>

