<template>
  <Layout ref="load" class="integral-index">
    <Navbar :title="navbarTitle" />
    <InviteWechat />
    <CustomGroup type="9" :items="items" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import InviteWechat from "@/components/InviteWechat";
import CustomGroup from "@/components/CustomGroup";
export default sfc({
  name: "integral-index",
  data() {
    return {
      page: {},
      items: {}
    };
  },
  computed: {
    navbarTitle() {
      let title = this.page.title;
      if (title) {
        document.title = title;
      }
      return title;
    }
  },
  activated() {
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
    if (this.$store.state.config.addons.integral) {
      this.getCustom();
      this.$refs.load.success();
    } else {
      this.$refs.load.fail({
        errorText: "未开启积分商城应用",
        showFoot: false
      });
    }
  },
  methods: {
    getCustom() {
      this.$store
        .dispatch("getCustom", {
          type: 9
        })
        .then(data => {
          if (data.template_data) {
            this.items = data.template_data.items;
            this.page = data.template_data.page;
          }
        });
    }
  },
  components: {
    InviteWechat,
    CustomGroup
  }
});
</script>

<style scoped>
</style>
