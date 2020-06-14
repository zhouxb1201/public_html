<template>
  <Layout ref="load" class="channel-certificate bg-f8">
    <Navbar />
    <AuthCertificate :type="3" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import AuthCertificate from "@/components/AuthCertificate";
export default sfc({
  name: "channel-certificate",
  data() {
    return {};
  },
  mounted() {
    if (this.$store.state.config.addons.credential) {
      this.$refs.load.success();
    } else {
      this.$refs.load.fail({
        errorType: "fail",
        errorText: "未开启授权证书应用",
        showFoot: false
      });
    }
  },
  components: {
    AuthCertificate
  }
});
</script>

<style  scoped>
.cell-group {
  margin: 10px 0;
}

.cert-wrp img {
  width: 100%;
  display: block;
}
</style>