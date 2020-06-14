<template>
  <Layout ref="load" class="diy-page" :style="pageStyle">
    <CustomGroup type="6" :items="items" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import CustomGroup from "@/components/CustomGroup";
export default sfc({
  name: "diy-page",
  data() {
    return {
      items: {},
      page: {}
    };
  },
  watch: {
    pageid(id) {
      if (id) {
        this.$refs.load.init();
        this.getCustom();
      }
    }
  },
  computed: {
    pageid() {
      return this.$route.params.pageid;
    },
    pageStyle() {
      return {
        background: this.page.background
      };
    }
  },
  mounted() {
    this.getCustom();
  },
  methods: {
    getCustom() {
      this.$store
        .dispatch("getCustom", { type: 6, id: this.pageid })
        .then(data => {
          if (data.template_data) {
            this.items = data.template_data.items;
            this.page = data.template_data.page;
            if (this.page.title) {
              document.title = this.page.title;
            }
          }
          this.$refs.load.success();
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    }
  },
  components: {
    CustomGroup
  }
});
</script>

<style scoped>
</style>
