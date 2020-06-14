<template>
  <Layout ref="load" class="thingcircle-index">
    <router-view></router-view>
    <Tabbar />
  </Layout>
</template>

<script>
import Tabbar from "./component/Tabbar";
import sfc from "@/utils/create";
export default sfc({
  name: "thingcircle-index",
  data() {
    return {};
  },
  mounted() {
    if (this.$store.state.config.addons.thingcircle) {
      this.$refs.load.success();
    } else {
      this.$refs.load.fail({ errorText: "未开启好物圈应用", showFoot: false });
    }
  },
  components: {
    Tabbar
  }
});
</script>

<style>
</style>