<template>
  <Layout ref="load" class="protocol">
    <Navbar :isMenu="false" />
    <div class="content" v-html="content"></div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_REGISTERPROTOCOL } from "@/api/config";
export default sfc({
  name: "protocol",
  data() {
    return {
      content: ""
    };
  },
  mounted() {
    GET_REGISTERPROTOCOL()
      .then(res => {
        this.content = res.data.register_protocol;
        this.$refs.load.success();
      })
      .catch(() => {
        this.$refs.load.fail();
      });
  }
});
</script>

<style scoped>
.content {
  padding: 15px;
}
</style>

