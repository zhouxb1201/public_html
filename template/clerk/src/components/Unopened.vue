<template>
  <div class="unopened">
    <Empty page-type="fail" :message="message" :show-foot="false"/>
  </div>
</template>

<script>
const title = "很抱歉，未开启O2O门店应用！";
import Empty from "@/components/Empty";
export default {
  name: "unopened",
  computed: {
    message() {
      return `${title}`;
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

