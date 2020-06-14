<template>
  <Layout ref="load" class="manage-post bg-f8">
    <Navbar :title="navbarTitle"/>
    <InfoPost :info="info" :jobs="jobs" :is-saving="isSaving" @save="onSave"/>
  </Layout>
</template>

<script>
import InfoPost from "./component/InfoPost";
import { UPADTE_CLERKINFO } from "@/api/manage";
import { _decode } from "@/utils/base64";
export default {
  name: "manage-post",
  data() {
    return {
      jobs: [],
      isSaving: false
    };
  },
  computed: {
    pageType() {
      return this.$route.hash && this.$route.hash == "#edit" ? "edit" : "add";
    },
    navbarTitle() {
      let title = this.pageType == "edit" ? "编辑店员" : "添加店员";
      if (title) document.title = title;
      return title;
    },
    info() {
      const defaultData = {
        assistant_id: null,
        assistant_name: "",
        assistant_tel: "",
        password: "",
        jobs_id: null,
        status: 0
      };
      const info =
        this.pageType == "edit"
          ? JSON.parse(_decode(this.$route.query.info))
          : defaultData;
      return info;
    }
  },
  created() {
    this.$store
      .dispatch("getJobsList")
      .then(jobs => {
        this.jobs = jobs;
        this.$refs.load.success();
      })
      .catch(() => {
        this.$refs.load.fail();
      });
  },
  methods: {
    onSave(data) {
      this.isSaving = true;
      UPADTE_CLERKINFO(data)
        .then(() => {
          this.$Toast.success("保存成功");
          setTimeout(() => {
            this.isSaving = false;
            this.$router.back();
          }, 500);
        })
        .catch(() => {
          this.isSaving = false;
        });
    }
  },
  components: {
    InfoPost
  }
};
</script>

<style scoped>
</style>
