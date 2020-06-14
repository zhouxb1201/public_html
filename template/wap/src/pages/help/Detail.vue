<template>
  <Layout ref="load" class="help-detail">
    <Navbar :title="navbarTitle" :isMenu="false" />
    <div class="content richtext">
      <div v-html="content"></div>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_HELPDETAIL } from "@/api/help";
export default sfc({
  name: "help-detail",
  data() {
    return {
      title: "",
      content: ""
    };
  },
  computed: {
    navbarTitle() {
      let title = this.title;
      if (title) document.title = title;
      return title;
    }
  },
  mounted() {
    GET_HELPDETAIL({ question_id: this.$route.params.id })
      .then(({ data }) => {
        this.title = data.title;
        this.content = data.content;
        if (this.navbarTitle) {
          document.title = this.navbarTitle;
        }
        this.$refs.load.success();
      })
      .catch(() => {
        this.$refs.load.fail();
      });
  },
  methods: {},
  components: {}
});
</script>

<style scoped>
.content {
  padding: 10px 15px;
  max-width: 100%;
  overflow: hidden;
}
</style>
