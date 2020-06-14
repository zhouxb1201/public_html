<template>
  <Layout ref="load" class="mall-index" :style="pageStyle">
    <InviteWechat />
    <CustomGroup type="1" :items="items" />
    <Copyright />
    <HomePopupAdv />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import InviteWechat from "@/components/InviteWechat";
import CustomGroup from "@/components/CustomGroup";
import Copyright from "@/components/Copyright";
import { setSession } from "@/utils/storage";
import HomePopupAdv from "./component/HomePopupAdv";
export default sfc({
  name: "mall-index",
  data() {
    return {
      page: {}
    };
  },
  computed: {
    pageStyle() {
      return {
        background: this.page.background
      };
    },
    items() {
      const template = this.$store.state.custom.template;
      return template.items;
    }
  },
  activated() {
    if (this.page.title) {
      document.title = this.page.title;
    }
  },
  mounted() {
    const template = this.$store.state.custom.template;
    if (template) {
      this.page = template.page;
      if (this.page.title) {
        document.title = this.page.title;
      }
      this.$refs.load.success();
    } else {
      this.$refs.load.fail();
    }
  },
  components: {
    InviteWechat,
    CustomGroup,
    Copyright,
    HomePopupAdv
  }
});
</script>

<style scoped>
</style>
